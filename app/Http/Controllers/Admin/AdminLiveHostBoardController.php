<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Host;
use App\Models\LiveChannel;
use App\Models\LiveHost;
use App\Models\LiveSchedule;
use App\Models\LiveStreamLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminLiveHostBoardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $date = $this->validatedDate($request);

        return response()->json(['success' => true, 'data' => $this->board($date)]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'assignments' => ['required', 'array'],
            'assignments.*.live_schedule_id' => ['required', 'integer', 'exists:live_schedules,id'],
            'assignments.*.host_id' => ['nullable', 'integer', 'exists:hosts,id'],
            'assignments.*.live_stream_link_id' => ['nullable', 'integer', Rule::exists('live_stream_links', 'id')->where('is_active', true)],
        ]);

        $date = $validated['date'];

        foreach ($validated['assignments'] as $assignment) {
            LiveHost::updateOrCreate(
                [
                    'live_schedule_id' => $assignment['live_schedule_id'],
                    'date' => $date,
                ],
                [
                    'host_id' => $assignment['host_id'] ?? null,
                    'live_stream_link_id' => $assignment['live_stream_link_id'] ?? null,
                    'is_active' => true,
                ],
            );
        }

        return response()->json(['success' => true, 'data' => $this->board($date)]);
    }

    private function validatedDate(Request $request): string
    {
        return $request->validate(['date' => ['nullable', 'date_format:Y-m-d']])['date'] ?? today()->toDateString();
    }

    /**
     * @return array{date: string, stream_links: list<array{id: int, name: string, url: string}>, hosts: list<array{id: int, name: string}>, channels: list<array{id: int, name: string, slots: list<array<string, mixed>>}>}
     */
    private function board(string $date): array
    {
        $assignments = LiveHost::query()
            ->whereDate('date', $date)
            ->with('host:id,name')
            ->get()
            ->keyBy('live_schedule_id');

        $streamLinks = LiveStreamLink::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'url'])
            ->map(fn (LiveStreamLink $link): array => [
                'id' => $link->id,
                'name' => $link->name,
                'url' => $link->url,
            ])
            ->all();

        $channels = LiveChannel::query()
            ->whereHas('liveSchedules')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->with(['liveSchedules' => fn ($query) => $query->orderBy('sort_order')])
            ->get()
            ->map(fn (LiveChannel $channel): array => [
                'id' => $channel->id,
                'name' => $channel->name,
                'logo_url' => $channel->logo ? Storage::disk('public')->url($channel->logo) : null,
                'slots' => $channel->liveSchedules->map(function (LiveSchedule $schedule) use ($assignments): array {
                    $assignment = $assignments->get($schedule->id);

                    return [
                        'id' => $schedule->id,
                        'start_time' => substr((string) $schedule->start_time, 0, 5),
                        'end_time' => substr((string) $schedule->end_time, 0, 5),
                        'host_id' => $assignment?->host_id,
                        'host_name' => $assignment?->host?->name,
                        'live_stream_link_id' => $assignment?->live_stream_link_id,
                    ];
                })->all(),
            ])
            ->all();

        return [
            'date' => $date,
            'stream_links' => $streamLinks,
            'hosts' => Host::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')
                ->get(['id', 'name', 'photo'])
                ->map(fn (Host $host): array => [
                    'id' => $host->id,
                    'name' => $host->name,
                    'photo_url' => $host->photo ? Storage::disk('public')->url($host->photo) : null,
                ])
                ->all(),
            'channels' => $channels,
        ];
    }
}
