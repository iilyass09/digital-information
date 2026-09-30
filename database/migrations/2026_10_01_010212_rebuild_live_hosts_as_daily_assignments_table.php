<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('live_hosts');

        Schema::create('live_hosts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('live_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('host_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['live_schedule_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_hosts');

        Schema::create('live_hosts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('live_channel_id')->constrained()->cascadeOnDelete();
            $table->string('host_name');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
};
