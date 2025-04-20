<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PopulateUserChurchInfo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-user-church-info';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command gleans from the data in the application and tries to populate the users_church_info table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        // Get all users
        $users = \App\Models\User::all();

        foreach ($users as $user) {

            $stream = $user->stream;

            \App\Models\UserChurchInfo::create([

                "user_id" => $user->id,
                "church_id" => 1,
                "stream_id" => $stream->id ?? null,
                "region_id" => $user->region->id ?? null,
                "zone_id" => $user->zone->id ?? null,
                "bacenta_id" => $user->bacenta->id ?? null,

            ]);

            $this->info("User {$user->name} church info populated.");
        }
    }
}
