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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('date_of_birth')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->text('email')->nullable();
            $table->text('address')->nullable();
            $table->text('occupation')->nullable();
            $table->enum('marital_status', ['single', 'married'])->nullable();
            $table->string('img_url')->nullable();
            $table->boolean('is_leader')->default(false);
            $table->unsignedBigInteger('user_id')->references('id')->on('users')->nullable()->comment("member has registered on app so is probably a leaders!");
            $table->unsignedBigInteger('church_id')->references('id')->on('churches')->nullable();
            $table->unsignedBigInteger('bacenta_id')->references('id')->on('bacentas')->nullable();
            $table->unsignedBigInteger('location_id')->references('id')->on('locations')->nullable();
            $table->unsignedBigInteger('council_id')->references('id')->on('councils')->nullable();
            $table->unsignedBigInteger('fellowship_id')->references('id')->on('fellowships')->nullable();
            $table->unsignedBigInteger('fellowship_leader_id')->references('id')->on('users')->nullable();
            $table->unsignedBigInteger('bacenta_leader_id')->references('id')->on('users')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
