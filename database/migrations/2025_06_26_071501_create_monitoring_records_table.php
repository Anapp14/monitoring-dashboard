<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('monitoring_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monitor_id'); // ID dari Uptime Kuma
            $table->string('name'); // Nama friendly monitor
            $table->string('type'); // Jenis monitor
            $table->date('date'); // Tanggal monitoring
            $table->float('uptime')->default(0); // Persentase uptime (misal: 99.5)
            $table->timestamps();

            $table->unique(['monitor_id', 'date']); // Supaya tidak ganda per monitor per hari
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_records');
    }
};
