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
        Schema::table('services', function (Blueprint $table) {
            // Add the micro_church_id column to the services table
            $table->foreignId('micro_church_id')->nullable()->constrained('micro_churches')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            //
            // Drop the micro_church_id column from the services table
            $table->dropForeign(['micro_church_id']);
            $table->dropColumn('micro_church_id');
        });
    }
};
