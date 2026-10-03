<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A schedule now applies to every day, so each live schedule keeps exactly one
     * assignment. Historical per-day rows are collapsed to the most recent one.
     */
    public function up(): void
    {
        if (Schema::hasColumn('live_hosts', 'date')) {
            $this->collapseToLatestAssignmentPerSchedule();

            // The live_schedule_id foreign key borrows this index because it is the
            // leftmost column, so the replacement has to exist before it is dropped.
            Schema::table('live_hosts', function (Blueprint $table): void {
                $table->unique('live_schedule_id');
            });

            Schema::table('live_hosts', function (Blueprint $table): void {
                $table->dropUnique(['live_schedule_id', 'date']);
                $table->dropColumn('date');
            });
        } else {
            Schema::table('live_hosts', function (Blueprint $table): void {
                $table->unique('live_schedule_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('live_hosts', function (Blueprint $table): void {
            $table->date('date')->default(today()->toDateString());
        });

        DB::table('live_hosts')->update(['date' => today()->toDateString()]);

        Schema::table('live_hosts', function (Blueprint $table): void {
            $table->unique(['live_schedule_id', 'date']);
            $table->dropUnique(['live_schedule_id']);
        });
    }

    private function collapseToLatestAssignmentPerSchedule(): void
    {
        $latestDates = DB::table('live_hosts')
            ->selectRaw('live_schedule_id, MAX(date) AS latest_date')
            ->groupBy('live_schedule_id')
            ->pluck('latest_date', 'live_schedule_id');

        foreach ($latestDates as $scheduleId => $latestDate) {
            $keepId = DB::table('live_hosts')
                ->where('live_schedule_id', $scheduleId)
                ->where('date', $latestDate)
                ->orderByDesc('id')
                ->value('id');

            DB::table('live_hosts')
                ->where('live_schedule_id', $scheduleId)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }
};
