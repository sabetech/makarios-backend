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
        Schema::create('arrivals', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->unsignedBigInteger('bacenta_id')->references('id')->on('bacentas');
            $table->integer('number_bussed');
            $table->string('img_proof');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrivals');
    }
};
