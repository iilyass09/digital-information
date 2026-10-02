<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Birthday;
use App\Models\Host;
use App\Models\LiveChannel;
use App\Models\LiveHost;
use App\Models\LiveStreamLink;
use App\Models\Promotion;
use App\Models\WeeklyMeeting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminContentController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const RESOURCES = [
        'promotions' => Promotion::class,
        'achievements' => Achievement::class,
        'birthdays' => Birthday::class,
        'hosts' => Host::class,
        'channels' => LiveChannel::class,
        'weekly-meetings' => WeeklyMeeting::class,
        'stream-links' => LiveStreamLink::class,
    ];

    /** @var list<string> */
    private const FILE_FIELDS = ['image', 'logo', 'photo'];

    public function dashboard(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'active_promotions' => Promotion::query()->where('is_active', true)
                ->where(fn (Builder $query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', today()))
                ->where(fn (Builder $query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
                ->count(),
            'active_achievements' => Achievement::where('is_active', true)->count(),
            'today_hosts' => LiveHost::where('is_active', true)->whereNotNull('host_id')->whereDate('date', today())->count(),
            'today_birthdays' => Birthday::where('is_active', true)
                ->whereMonth('birth_date', today()->month)
                ->whereDay('birth_date', today()->day)
                ->count(),
        ]]);
    }

    public function channels(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => LiveChannel::query()
            ->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])]);
    }

    public function index(Request $request, string $resource): JsonResponse
    {
        $model = $this->model($resource);

        $records = $model::query()->orderBy('sort_order')->orderByDesc('id')->paginate(15);

        return response()->json(['success' => true, 'data' => $records->through(fn (Model $record): array => $this->serialize($record))]);
    }

    public function show(string $resource, int $id): JsonResponse
    {
        $record = $this->model($resource)::query()->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->serialize($record)]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $modelClass = $this->model($resource);
        $attributes = $this->validated($request, $resource, false);
        $uploads = $this->uploads($request, $resource);
        $record = new $modelClass($attributes);
        foreach ($uploads as $field => $path) {
            $record->{$field} = $path;
        }
        $record->save();
        $this->dumpAfterUpload($uploads);

        return response()->json(['success' => true, 'data' => $this->serialize($record)], 201);
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $record = $this->model($resource)::query()->findOrFail($id);
        $attributes = $this->validated($request, $resource, true);
        $uploads = $this->uploads($request, $resource);
        $previousFiles = [];
        foreach (self::FILE_FIELDS as $field) {
            $previousFiles[$field] = $record->getAttribute($field);
            unset($attributes[$field]);
        }
        $record->fill($attributes);
        foreach ($uploads as $field => $path) {
            $record->{$field} = $path;
        }
        $record->save();
        foreach ($uploads as $field => $path) {
            if ($previousFiles[$field]) {
                Storage::disk('public')->delete($previousFiles[$field]);
            }
        }
        $this->dumpAfterUpload($uploads);

        return response()->json(['success' => true, 'data' => $this->serialize($record)]);
    }

    /**
     * Refresh the portable dump whenever an upload changes the database.
     *
     * The dump is the only artefact that travels with the project when the
     * board is moved to another machine, so it must stay in step with the
     * rows and filenames the upload just wrote. Failures are logged rather
     * than surfaced: losing the snapshot must never fail a successful upload.
     *
     * @param  array<string, string>  $uploads
     */
    private function dumpAfterUpload(array $uploads): void
    {
        if ($uploads === [] || ! config('admin.auto_dump')) {
            return;
        }

        try {
            Artisan::call('db:dump', ['--force' => true]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function destroy(string $resource, int $id): JsonResponse
    {
        $record = $this->model($resource)::query()->findOrFail($id);
        foreach (self::FILE_FIELDS as $field) {
            $file = $record->getAttribute($field);
            if ($file) {
                Storage::disk('public')->delete($file);
            }
        }
        $record->delete();

        return response()->json(['success' => true, 'data' => null]);
    }

    public function toggle(string $resource, int $id): JsonResponse
    {
        abort_unless(in_array($resource, ['promotions', 'achievements', 'birthdays', 'hosts', 'channels', 'weekly-meetings', 'stream-links'], true), 404);
        $record = $this->model($resource)::query()->findOrFail($id);
        $record->is_active = ! $record->is_active;
        $record->save();

        return response()->json(['success' => true, 'data' => $this->serialize($record)]);
    }

    /** @return array<string, string> */
    private function uploads(Request $request, string $resource): array
    {
        $uploads = [];
        foreach (self::FILE_FIELDS as $field) {
            if ($request->hasFile($field)) {
                $uploads[$field] = $request->file($field)->store($resource, 'public');
            }
        }

        return $uploads;
    }

    /** @return class-string<Model> */
    private function model(string $resource): string
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);

        return self::RESOURCES[$resource];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $resource, bool $updating): array
    {
        $required = $updating ? 'sometimes' : 'required';
        $rules = match ($resource) {
            'promotions' => [
                'title' => [$required, 'string', 'max:255'],
                'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'start_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
                'end_date' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
                'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['sometimes', 'integer', 'min:0'],
                'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:51200'],
            ],
            'achievements' => [
                'employee_name' => [$required, 'string', 'max:255'], 'division' => [$required, 'string', 'max:255'],
                'title' => [$required, 'string', 'max:255'], 'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
                'achievement_date' => [$required, 'date_format:Y-m-d'], 'is_active' => ['sometimes', 'boolean'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
                'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            'hosts' => [
                'name' => [$required, 'string', 'max:255', Rule::unique('hosts', 'name')->ignore($updating ? $request->route('id') : null)],
                'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['sometimes', 'integer', 'min:0'],
                'photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            'channels' => [
                'name' => [$required, 'string', 'max:255', Rule::unique('live_channels', 'name')->ignore($updating ? $request->route('id') : null)],
                'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['sometimes', 'integer', 'min:0'],
                'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            'weekly-meetings' => [
                'name' => [$required, 'string', 'max:255', Rule::unique('weekly_meetings', 'name')->ignore($updating ? $request->route('id') : null)],
                'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['sometimes', 'integer', 'min:0'],
                'photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            'birthdays' => [
                'employee_name' => [$required, 'string', 'max:255'], 'division' => [$required, 'string', 'max:255'],
                'birth_date' => [$required, 'date_format:Y-m-d'], 'is_active' => ['sometimes', 'boolean'],
                'sort_order' => ['sometimes', 'integer', 'min:0'],
                'image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            'stream-links' => [
                'name' => [$required, 'string', 'max:255'],
                'url' => [$required, 'string', 'max:2048', 'url:http,https'],
                'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'is_active' => ['sometimes', 'boolean'], 'sort_order' => ['sometimes', 'integer', 'min:0'],
            ],
            default => throw ValidationException::withMessages(['resource' => 'Jenis konten tidak valid.']),
        };

        return $request->validate($rules, $this->messages($resource));
    }

    /**
     * Validation messages in the language the CMS speaks. The application has
     * no lang/ folder, so Laravel falls back to its English defaults for
     * everything else; only the banner rules are translated here.
     *
     * @return array<string, string>
     */
    private function messages(string $resource): array
    {
        if ($resource !== 'promotions') {
            return [];
        }

        return [
            'image.max' => 'Ukuran banner maksimal 50 MB.',
            'image.mimes' => 'Banner harus berformat JPG, PNG, WebP, atau GIF.',
        ];
    }

    /** @return array<string, mixed> */
    private function serialize(Model $record): array
    {
        $data = $record->toArray();
        foreach (self::FILE_FIELDS as $field) {
            $file = $record->getAttribute($field);
            if ($file) {
                $data[$field.'_url'] = Storage::disk('public')->url($file);
            }
        }

        return $data;
    }
}
