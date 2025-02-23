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
        Schema::create('arrivals_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stream_id')->references('id')->on('streams');
            $table->time('pre_mobilization_deadline');
            $table->time('arrival_deadline');
            $table->string('code_of_the_day');
            $table->string('day_of_service');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrivals_settings');
    }
};
