<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('live_stream_links', function (Blueprint $table): void {
            $table->string('logo')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('live_stream_links', function (Blueprint $table): void {
            $table->dropColumn('logo');
        });
    }
};
