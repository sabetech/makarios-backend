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
        Schema::create('streams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('meeting_day', [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ]);
            $table->time('meeting_time');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('church_id')->references('id')->on('churches');
            $table->unsignedBigInteger('stream_overseer_id')->references('id')->on('users')->nullable();
            $table->unsignedBigInteger('stream_admin_id')->references('id')->on('users')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streams');
    }
};
