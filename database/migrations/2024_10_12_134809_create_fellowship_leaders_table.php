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
        Schema::create('fellowships', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('location_id')->nullable();
            $table->unsignedBigInteger('bacenta_id')->references('id')->on('bacentas')->nullable();
            $table->unsignedBigInteger('council_id')->references('id')->on('councils')->nullable();
            $table->unsignedBigInteger('stream_id')->references('id')->on('streams')->nullable();
            $table->unsignedBigInteger('church_id')->references('id')->on('churches')->nullable();
            $table->unsignedBigInteger('leader_id')->references('id')->on('users')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fellowships');
    }
};
