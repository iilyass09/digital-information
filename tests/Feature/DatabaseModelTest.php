<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseModelTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_users_table_stores_role_for_admin_authorization(): void
    {
        $this->assertTrue(Schema::hasColumn('users', 'role'));
    }

    public function test_content_schema_contains_the_required_tables_and_live_host_foreign_keys(): void
    {
        foreach (['promotions', 'achievements', 'live_channels', 'live_schedules', 'live_hosts', 'hosts', 'birthdays', 'weekly_meetings'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $this->assertTrue(Schema::hasColumn('live_hosts', 'live_schedule_id'));
        $this->assertTrue(Schema::hasColumn('live_hosts', 'host_id'));
        $this->assertTrue(Schema::hasColumn('live_schedules', 'live_channel_id'));
    }
}
