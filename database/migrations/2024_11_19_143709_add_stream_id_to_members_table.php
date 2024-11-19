<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Member;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->unsignedBigInteger('stream_id')->references('id')->on('streams')->after('church_id')->nullable();
        });

        $members = Member::all();
        foreach ($members as $member) {
            if ($member->region) {
                $member->stream_id = $member->region->stream->id;
                $member->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            //
            $table->dropColumn('stream_id');
        });
    }
};
