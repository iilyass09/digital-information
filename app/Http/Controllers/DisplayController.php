<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\Birthday;
use App\Models\LiveChannel;
use App\Models\LiveHost;
use App\Models\LiveSchedule;
use App\Models\Promotion;
use App\Models\WeeklyMeeting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class DisplayController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $today = today();

        $promotions = Promotion::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('start_date')->orWhereDate('start_date', '<=', $today))
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', $today))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'image', 'description', 'start_date', 'end_date'])
            ->map(fn (Promotion $promotion): array => [
                'id' => $promotion->id,
                'title' => $promotion->title,
                'image_url' => $promotion->image ? Storage::disk('public')->url($promotion->image) : null,
                'description' => $promotion->description,
                'start_date' => $promotion->start_date?->toDateString(),
                'end_date' => $promotion->end_date?->toDateString(),
            ]);

        $achievements = Achievement::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'employee_name', 'division', 'title', 'description', 'image', 'achievement_date'])
            ->map(fn (Achievement $achievement): array => [
                'id' => $achievement->id,
                'employee_name' => $achievement->employee_name,
                'division' => $achievement->division,
                'title' => $achievement->title,
                'description' => $achievement->description,
                'image_url' => $achievement->image ? Storage::disk('public')->url($achievement->image) : null,
                'achievement_date' => $achievement->achievement_date?->toDateString(),
            ]);

        $assignments = LiveHost::query()
            ->where('is_active', true)
            ->whereDate('date', $today)
            ->with('host:id,name,photo')
            ->get()
            ->keyBy('live_schedule_id');

        $liveChannels = LiveChannel::query()
            ->where('is_active', true)
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
                    $host = $assignments->get($schedule->id)?->host;

                    return [
                        'id' => $schedule->id,
                        'start_time' => substr((string) $schedule->start_time, 0, 5),
                        'end_time' => substr((string) $schedule->end_time, 0, 5),
                        'host_name' => $host?->name,
                        'host_photo' => $host?->photo ? Storage::disk('public')->url($host->photo) : null,
                    ];
                })->all(),
            ]);

        $birthdays = Birthday::query()
            ->where('is_active', true)
            ->whereMonth('birth_date', $today->month)
            ->whereDay('birth_date', $today->day)
            ->orderBy('employee_name')
            ->orderBy('id')
            ->get(['id', 'employee_name', 'division', 'birth_date', 'image'])
            ->map(fn (Birthday $birthday): array => [
                'id' => $birthday->id,
                'employee_name' => $birthday->employee_name,
                'division' => $birthday->division,
                'birth_date' => $birthday->birth_date->toDateString(),
                'image_url' => $birthday->image ? Storage::disk('public')->url($birthday->image) : null,
            ]);

        $weeklyMeetings = WeeklyMeeting::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'photo'])
            ->map(fn (WeeklyMeeting $meeting): array => [
                'id' => $meeting->id,
                'name' => $meeting->name,
                'photo_url' => $meeting->photo ? Storage::disk('public')->url($meeting->photo) : null,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'promotions' => $promotions,
                'achievements' => $achievements,
                'birthdays' => $birthdays,
                'weekly_meetings' => $weeklyMeetings,
                'live_channels' => $liveChannels,
            ],
        ]);
    }
}
