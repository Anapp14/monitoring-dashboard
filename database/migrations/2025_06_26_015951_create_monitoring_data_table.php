<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('monitoring_data', function (Blueprint $table) {
            $table->id();
            $table->string('monitor_id');
            $table->string('monitor_name');
            $table->string('monitor_type');
            $table->date('date');
            $table->integer('status'); // 0=down, 1=up, 2=paused, 3=maintenance
            $table->decimal('uptime_percentage', 5, 2)->default(0); // 0.00 - 100.00
            $table->integer('ping')->nullable();
            $table->datetime('last_heartbeat')->nullable();
            $table->json('heartbeat_data')->nullable(); // Store raw heartbeat data
            $table->timestamps();
            
            // Indexes
            $table->index(['monitor_id', 'date']);
            $table->index('date');
            $table->unique(['monitor_id', 'date']); // Prevent duplicate data for same monitor on same date
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_data');
    }
};