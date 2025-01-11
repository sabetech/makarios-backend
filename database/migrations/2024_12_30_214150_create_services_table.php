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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date');
            $table->unsignedBigInteger('bacenta_id')->references('id')->on('bacentas')->nullable();
            $table->unsignedBigInteger('zone_id')->references('id')->on('zones')->nullable();
            $table->unsignedBigInteger('region_id')->references('id')->on('regions')->nullable();
            $table->unsignedBigInteger('stream_id')->references('id')->on('streams')->nullable();
            $table->unsignedBigInteger('church_id')->references('id')->on('churches');
            $table->unsignedBigInteger('service_type_id')->references('id')->on('service_types');

            $table->decimal('offering', 8, 2)->default(0);
            $table->string('foreign_currency')->nullable();
            $table->integer('attendance')->default(0);
            $table->string('treasurers')->comment('Comma separated user names. Must be upgraded to members ids');
            $table->string('treasurer_photo');
            $table->string('service_photo');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
