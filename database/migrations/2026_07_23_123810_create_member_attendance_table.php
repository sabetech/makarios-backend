<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_attendance', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('service_id');
            $table->enum('status', ['present', 'absent'])->default('absent');
            $table->integer('consecutive_absences')->default(0);
            $table->timestamps();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->unique(['member_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_attendance');
    }
};
