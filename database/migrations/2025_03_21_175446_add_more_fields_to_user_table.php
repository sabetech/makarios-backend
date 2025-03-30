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
        Schema::create('users_church_info', function (Blueprint $table) {
            //
            $table->increments('id');
            $table->unsignedBigInteger('user_id')->references('id')->on('users')->onDelete('cascade')->unique();
            $table->unsignedBigInteger('church_id')->references('id')->on('churches')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('stream_id')->references('id')->on('streams')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('region_id')->references('id')->on('regions')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('zone_id')->references('id')->on('zones')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('bacenta_id')->references('id')->on('bacentas')->onDelete('cascade')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_church_info');
    }
};
