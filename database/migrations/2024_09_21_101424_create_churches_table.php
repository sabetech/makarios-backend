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
        Schema::create('churches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('head_pastor_id')->references('id')->on('users')->nullable();
            $table->unsignedBigInteger('admin_id')->references('id')->on('users')->nullable();
            $table->string('img-url')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('location_id')->references('id')->on('locations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};
