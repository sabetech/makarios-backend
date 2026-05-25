<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shepherdorial_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('cycle_start');
            $table->date('cycle_end');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shepherdorial_cycles');
    }
};
