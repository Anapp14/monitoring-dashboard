<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_uptimes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monitor_id'); // ID dari Uptime Kuma
            $table->string('monitor_name');           // Nama monitor
            $table->date('date');                     // Tanggal monitoring (misalnya 2025-06-26)
            $table->float('uptime');                  // Nilai uptime (misalnya 0.987)
            $table->timestamps();

            $table->unique(['monitor_id', 'date']);   // Tidak boleh ada data ganda per monitor per hari
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_uptimes');
    }
};
