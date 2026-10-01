<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('live_schedules')
            ->where('start_time', '13:00:00')
            ->where('end_time', '19:00:00')
            ->update(['end_time' => '18:00:00', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('live_schedules')
            ->where('start_time', '13:00:00')
            ->where('end_time', '18:00:00')
            ->update(['end_time' => '19:00:00', 'updated_at' => now()]);
    }
};
