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
        Schema::table('members', function (Blueprint $table) {
            //
            // Add the micro_churches_id column to the members table
            $table->foreignId('micro_churches_id')->nullable()->constrained('micro_churches')->onDelete('set null');
            // This will create a foreign key constraint on
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            //
            // Drop the micro_churches_id column from the members table
            $table->dropForeign(['micro_churches_id']);
            $table->dropColumn('micro_churches_id');
            // This will remove the foreign key constraint and the column
        });
    }
};
