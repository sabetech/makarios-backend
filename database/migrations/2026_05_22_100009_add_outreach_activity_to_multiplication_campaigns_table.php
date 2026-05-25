<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('multiplication_campaigns', function (Blueprint $table) {
            $table->string('outreach_activity')->after('shepherdorial_cycle_id');
        });
    }

    public function down(): void
    {
        Schema::table('multiplication_campaigns', function (Blueprint $table) {
            $table->dropColumn('outreach_activity');
        });
    }
};
