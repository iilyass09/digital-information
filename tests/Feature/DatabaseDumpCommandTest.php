<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\Host;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseDumpCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $dumpPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dumpPath = storage_path('framework/testing/dump-test.sql');

        File::ensureDirectoryExists(dirname($this->dumpPath));
        File::delete($this->dumpPath);
        File::deleteDirectory(storage_path('framework/testing/blocked-dump'));

        // Every test must resolve the auto-dump to the scratch path. Without
        // this, an upload in a test would overwrite the developer's real dump.
        config(['admin.dump_path' => $this->dumpPath]);
    }

    protected function tearDown(): void
    {
        File::delete($this->dumpPath);
        File::deleteDirectory(storage_path('framework/testing/blocked-dump'));

        parent::tearDown();
    }

    public function test_dump_writes_schema_and_rows_to_the_given_path(): void
    {
        Promotion::factory()->count(3)->create(['is_active' => true]);
        Host::factory()->count(5)->create();

        $this->artisan('db:dump', ['--output' => $this->dumpPath, '--force' => true])
            ->assertSuccessful();

        $this->assertFileExists($this->dumpPath);

        $sql = File::get($this->dumpPath);

        $this->assertStringContainsString('CREATE TABLE `promotions`', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `promotions`', $sql);
        $this->assertStringContainsString('INSERT INTO `hosts`', $sql);
    }

    public function test_dump_restores_into_an_empty_database_with_identical_rows(): void
    {
        Promotion::factory()->count(2)->create(['sort_order' => 7]);
        Host::factory()->count(4)->create();
        User::factory()->admin()->create(['username' => 'keeper', 'role' => 'admin']);

        $before = $this->snapshot();

        $this->artisan('db:dump', ['--output' => $this->dumpPath, '--force' => true])
            ->assertSuccessful();

        // Wipe the content tables the way a fresh machine would present them.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach (['promotions', 'hosts', 'users'] as $table) {
            DB::statement('TRUNCATE TABLE `'.$table.'`');
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->assertSame(0, Promotion::count());

        $this->artisan('db:restore', ['--file' => $this->dumpPath, '--force' => true])
            ->assertSuccessful();

        $this->assertSame($before, $this->snapshot());
    }

    public function test_restore_preserves_text_that_contains_sql_syntax(): void
    {
        Achievement::factory()->create([
            'employee_name' => "O'Brien; DROP TABLE `hosts`; --",
            'division' => "Divider || and 'quotes' and \n newline",
            'description' => 'backslash \\ and -- dashes',
        ]);

        $expected = Achievement::query()->first()->only(['employee_name', 'division', 'description']);

        $this->artisan('db:dump', ['--output' => $this->dumpPath, '--force' => true])
            ->assertSuccessful();

        DB::statement('TRUNCATE TABLE `achievements`');
        $this->assertSame(0, Achievement::count());

        $this->artisan('db:restore', ['--file' => $this->dumpPath, '--force' => true])
            ->assertSuccessful();

        // A naive splitter would truncate the table mid-restore.
        $this->assertSame($expected, Achievement::query()->first()->only(['employee_name', 'division', 'description']));
        $this->assertGreaterThan(0, Host::count() + 1);
    }

    public function test_dump_refuses_to_overwrite_without_force(): void
    {
        File::put($this->dumpPath, '-- existing dump');

        $this->artisan('db:dump', ['--output' => $this->dumpPath])
            ->assertFailed();

        $this->assertSame('-- existing dump', File::get($this->dumpPath));
    }

    public function test_restore_refuses_when_the_database_already_has_content(): void
    {
        Promotion::factory()->count(2)->create();

        $this->artisan('db:dump', ['--output' => $this->dumpPath, '--force' => true])
            ->assertSuccessful();

        $this->artisan('db:restore', ['--file' => $this->dumpPath])
            ->assertFailed();
    }

    public function test_restore_fails_cleanly_when_the_dump_is_missing(): void
    {
        File::delete($this->dumpPath);

        $this->artisan('db:restore', ['--file' => $this->dumpPath, '--force' => true])
            ->assertFailed();
    }

    public function test_dump_excludes_ephemeral_runtime_tables(): void
    {
        $this->artisan('db:dump', ['--output' => $this->dumpPath, '--force' => true])
            ->assertSuccessful();

        $sql = File::get($this->dumpPath);

        $this->assertStringNotContainsString('CREATE TABLE `sessions`', $sql);
        $this->assertStringNotContainsString('CREATE TABLE `cache`', $sql);
    }

    public function test_uploading_an_image_refreshes_the_dump_so_it_can_travel(): void
    {
        Storage::fake('public');
        config(['admin.auto_dump' => true]);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->post('/api/admin/promotions', [
            'title' => 'September Ceria',
            'image' => UploadedFile::fake()->image('banner.jpg'),
        ])->assertCreated();

        $dump = config('admin.dump_path');

        $this->assertFileExists($dump, 'Uploading an image should refresh the portable dump.');

        $sql = File::get($dump);

        $this->assertStringContainsString('September Ceria', $sql);
        $this->assertStringContainsString('INSERT INTO `promotions`', $sql);
    }

    public function test_uploading_an_image_still_succeeds_when_the_dump_cannot_be_written(): void
    {
        Storage::fake('public');
        config(['admin.auto_dump' => true]);

        // A directory where the dump file should go makes fopen() fail.
        $blocked = storage_path('framework/testing/blocked-dump');

        File::ensureDirectoryExists($blocked);
        config(['admin.dump_path' => $blocked.DIRECTORY_SEPARATOR.'database.sql']);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->post('/api/admin/promotions', [
            'title' => 'Still Works',
            'image' => UploadedFile::fake()->image('banner.jpg'),
        ])->assertCreated();

        // The upload is committed even though the snapshot could not be written.
        $this->assertDatabaseHas('promotions', ['title' => 'Still Works']);

        File::deleteDirectory($blocked);
    }

    /**
     * Column values for the tables that carry board content.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function snapshot(): array
    {
        return [
            'promotions' => Promotion::query()->orderBy('id')->get()
                ->map(fn (Promotion $p) => $p->only(['id', 'title', 'sort_order', 'is_active']))
                ->all(),
            'hosts' => Host::query()->orderBy('id')->get()
                ->map(fn (Host $h) => $h->only(['id', 'name', 'is_active']))
                ->all(),
            'users' => User::query()->orderBy('id')->get()
                ->map(fn (User $u) => $u->only(['id', 'username', 'role']))
                ->all(),
        ];
    }
}
