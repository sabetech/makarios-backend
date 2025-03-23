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
        Schema::table('users', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('church_id')->after('email')->references('id')->on('churches')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('stream_id')->after('church_id')->references('id')->on('streams')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('region_id')->after('stream_id')->references('id')->on('regions')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('zone_id')->after('region_id')->references('id')->on('zones')->onDelete('cascade')->nullable();
            $table->unsignedBigInteger('bacenta_id')->after('zone_id')->references('id')->on('bacentas')->onDelete('cascade')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user', function (Blueprint $table) {
            //
            $table->dropColumn('church_id');
            $table->dropColumn('stream_id');
            $table->dropColumn('region_id');
            $table->dropColumn('zone_id');
            $table->dropColumn('bacenta_id');
        });
    }
};
