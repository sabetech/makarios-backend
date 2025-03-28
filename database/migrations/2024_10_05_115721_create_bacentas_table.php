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
        Schema::create('bacentas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('location_id')->references('id')->on('locations')->nullable();
            $table->unsignedBigInteger('leader_id')->references('id')->on('users')->nullable();
            $table->unsignedBigInteger('region_id')->references('id')->on('regions')->nullable();
            $table->unsignedBigInteger('zone_id')->references('id')->on('zones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bacentas');
    }
};
