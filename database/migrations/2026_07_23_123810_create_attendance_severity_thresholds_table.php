<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_severity_thresholds', function (Blueprint $table) {
            $table->id();
            $table->integer('min_absences');
            $table->integer('max_absences')->nullable();
            $table->string('label');
            $table->string('color');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_severity_thresholds');
    }
};
