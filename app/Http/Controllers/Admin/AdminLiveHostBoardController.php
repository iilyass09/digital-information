<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Host;
use App\Models\LiveChannel;
use App\Models\LiveHost;
use App\Models\LiveSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminLiveHostBoardController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->board()]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'assignments' => ['required', 'array'],
            'assignments.*.live_schedule_id' => ['required', 'integer', 'exists:live_schedules,id'],
            'assignments.*.host_id' => ['nullable', 'integer', 'exists:hosts,id'],
            'channels' => ['sometimes', 'array'],
            'channels.*.live_channel_id' => ['required', 'integer', 'exists:live_channels,id'],
            'channels.*.stream_url' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'channels.*.stream_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        foreach ($validated['assignments'] as $assignment) {
            LiveHost::updateOrCreate(
                ['live_schedule_id' => $assignment['live_schedule_id']],
                [
                    'host_id' => $assignment['host_id'] ?? null,
                    'is_active' => true,
                ],
            );
        }

        $this->saveChannelStreams($request, $validated['channels'] ?? []);

        return response()->json(['success' => true, 'data' => $this->board()]);
    }

    /**
     * @param  list<array{live_channel_id: int, stream_url?: ?string}>  $channels
     */
    private function saveChannelStreams(Request $request, array $channels): void
    {
        foreach ($channels as $index => $attributes) {
            $channel = LiveChannel::query()->findOrFail($attributes['live_channel_id']);
            $channel->stream_url = $attributes['stream_url'] ?? null;

            $logo = $request->file("channels.{$index}.stream_logo");
            if ($logo) {
                $channel->stream_logo = $logo->store('live-channels', 'public');
            }

            $channel->save();
        }
    }

    /**
     * @return array{hosts: list<array{id: int, name: string, photo_url: ?string}>, channels: list<array{id: int, name: string, logo_url: ?string, stream_url: ?string, stream_logo_url: ?string, slots: list<array{id: int, start_time: string, end_time: string, host_id: ?int, host_name: ?string}>}>}
     */
    private function board(): array
    {
        $assignments = LiveHost::query()
            ->with('host:id,name')
            ->get()
            ->keyBy('live_schedule_id');

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
                'stream_url' => $channel->stream_url,
                'stream_logo_url' => $channel->stream_logo ? Storage::disk('public')->url($channel->stream_logo) : null,
                'slots' => $channel->liveSchedules->map(function (LiveSchedule $schedule) use ($assignments): array {
                    $assignment = $assignments->get($schedule->id);

                    return [
                        'id' => $schedule->id,
                        'start_time' => substr((string) $schedule->start_time, 0, 5),
                        'end_time' => substr((string) $schedule->end_time, 0, 5),
                        'host_id' => $assignment?->host_id,
                        'host_name' => $assignment?->host?->name,
                    ];
                })->all(),
            ])
            ->all();

        return [
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
