<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->foreignId('shepherdorial_cycle_id')
                ->nullable()
                ->constrained()
                ->onDelete('set null');
            $table->dropColumn(['cycle_start', 'cycle_end']);
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropForeign(['shepherdorial_cycle_id']);
            $table->dropColumn('shepherdorial_cycle_id');
            $table->dateTime('cycle_start');
            $table->dateTime('cycle_end');
        });
    }
};
