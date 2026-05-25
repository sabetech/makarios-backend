<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multiplication_campaigns', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('souls_saved');
            $table->foreignId('region_id')->constrained();
            $table->foreignId('campaign_id')->constrained();
            $table->foreignId('leader_id')->constrained('users');
            $table->foreignId('shepherdorial_cycle_id')->constrained();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multiplication_campaigns');
    }
};
