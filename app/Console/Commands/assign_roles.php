<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Council;
use Log;
use App\Models\User;

class assign_roles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign_roles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Specifically assign roles to overseers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //Assign Overseers
        $councils = Council::all();
        foreach($councils as $council){
            $user = $council->user;

            if ($user)
                $user->assignRole('Overseer');
        }

        //Assign Bishops
        $bishopEmails = [
            "harrykazo@gmail.com",
            "ebaidoo@yahoo.com",
        ];

        foreach($bishopEmails as $email){
            $user = User::where('email', $email)->first();
            if ($user)
                $user->assignRole('Bishop');
        }




    }
}
