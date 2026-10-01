<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_hosts', function (Blueprint $table): void {
            $table->foreignId('live_stream_link_id')->nullable()->after('host_id')
                ->constrained('live_stream_links')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('live_hosts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('live_stream_link_id');
        });
    }
};
