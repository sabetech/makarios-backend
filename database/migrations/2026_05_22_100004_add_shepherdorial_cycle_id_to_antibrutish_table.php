<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antibrutish', function (Blueprint $table) {
            $table->foreignId('shepherdorial_cycle_id')->default(1)->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('antibrutish', function (Blueprint $table) {
            $table->dropForeign(['shepherdorial_cycle_id']);
            $table->dropColumn('shepherdorial_cycle_id');
        });
    }
};
