<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('monitor_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('monitor_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('monitor_groups')->onDelete('cascade');
            $table->string('monitor_id'); // ID dari Uptime Kuma
            $table->timestamps();
            
            // Prevent duplicate monitor in same group
            $table->unique(['group_id', 'monitor_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('monitor_group_items');
        Schema::dropIfExists('monitor_groups');
    }
};