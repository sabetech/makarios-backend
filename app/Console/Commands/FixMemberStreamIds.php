<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class FixMemberStreamIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-member-stream-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resolve the stream_id for members that are missing it by referencing their bacenta -> region -> stream. Saves null when it cannot be resolved.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $members = Member::whereNull('stream_id')
            ->with('bacenta.region.stream')
            ->get();

        $updated = 0;

        foreach ($members as $member) {
            $member->stream_id = $member->bacenta?->region?->stream?->id;
            $member->save();
            $updated++;
        }

        $this->info("{$updated} members had their stream_id resolved.");
    }
}