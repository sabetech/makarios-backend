<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sheep_sheekings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('leader_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('member_visited_id')->constrained('members')->onDelete('cascade');
            $table->string('visitation_report')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sheep_sheekings');
    }
};
