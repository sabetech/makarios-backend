<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sheep_sheekings', function (Blueprint $table) {
            $table->foreignId('campaign_id')->after('member_visited_id')->constrained();
        });
    }

    public function down(): void
    {
        Schema::table('sheep_sheekings', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropColumn('campaign_id');
        });
    }
};
