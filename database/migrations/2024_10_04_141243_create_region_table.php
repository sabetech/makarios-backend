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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('region');
            $table->unsignedBigInteger('leader_id')->references('id')->on('users')->nullable();
            $table->unsignedBigInteger('assistant_id')->references('id')->on('users')->nullable();
            $table->unsignedBigInteger('stream_id')->references('id')->on('streams');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('region');
    }
};
