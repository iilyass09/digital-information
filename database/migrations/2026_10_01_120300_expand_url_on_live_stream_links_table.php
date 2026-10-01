<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_stream_links', function (Blueprint $table): void {
            $table->string('url', 2048)->change();
        });
    }

    public function down(): void
    {
        Schema::table('live_stream_links', function (Blueprint $table): void {
            $table->string('url', 255)->change();
        });
    }
};
