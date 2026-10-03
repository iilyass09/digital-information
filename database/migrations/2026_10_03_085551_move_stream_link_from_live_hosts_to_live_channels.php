<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A stream link used to be picked per slot. It now lives on the channel, so one
     * link (and logo) serves every slot of that channel for the whole week.
     */
    public function up(): void
    {
        Schema::table('live_channels', function (Blueprint $table): void {
            $table->string('stream_url', 2048)->nullable()->after('logo');
            $table->string('stream_logo')->nullable()->after('stream_url');
        });

        $this->carryExistingLinksOverToChannels();

        if (Schema::hasColumn('live_hosts', 'live_stream_link_id')) {
            Schema::table('live_hosts', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('live_stream_link_id');
            });
        }

        Schema::dropIfExists('live_stream_links');
    }

    public function down(): void
    {
        Schema::create('live_stream_links', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('url', 2048);
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('live_hosts', function (Blueprint $table): void {
            $table->foreignId('live_stream_link_id')->nullable()->after('host_id')
                ->constrained('live_stream_links')->nullOnDelete();
        });

        DB::table('live_channels')->whereNotNull('stream_url')->orderBy('id')->each(function (object $channel): void {
            $linkId = DB::table('live_stream_links')->insertGetId([
                'name' => $channel->name,
                'url' => $channel->stream_url,
                'logo' => $channel->stream_logo,
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $scheduleIds = DB::table('live_schedules')->where('live_channel_id', $channel->id)->pluck('id');

            DB::table('live_hosts')->whereIn('live_schedule_id', $scheduleIds)->update([
                'live_stream_link_id' => $linkId,
            ]);
        });

        Schema::table('live_channels', function (Blueprint $table): void {
            $table->dropColumn(['stream_url', 'stream_logo']);
        });
    }

    /**
     * Old links had no channel of their own; they were referenced from the slots of a
     * channel. Take the first link each channel actually used, preferring active ones.
     */
    private function carryExistingLinksOverToChannels(): void
    {
        $handled = [];

        DB::table('live_schedules')->select('id', 'live_channel_id')->orderBy('id')->each(function (object $schedule) use (&$handled): void {
            if (isset($handled[$schedule->live_channel_id])) {
                return;
            }

            $link = DB::table('live_stream_links')
                ->join('live_hosts', 'live_hosts.live_stream_link_id', '=', 'live_stream_links.id')
                ->where('live_hosts.live_schedule_id', $schedule->id)
                ->orderByDesc('live_stream_links.is_active')
                ->orderBy('live_stream_links.id')
                ->select('live_stream_links.url', 'live_stream_links.logo')
                ->first();

            if (! $link) {
                return;
            }

            DB::table('live_channels')->where('id', $schedule->live_channel_id)->update([
                'stream_url' => $link->url,
                'stream_logo' => $link->logo,
            ]);

            $handled[$schedule->live_channel_id] = true;
        });
    }
};
