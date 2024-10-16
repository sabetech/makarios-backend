<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Council;
use Log;

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
        //
        $councils = Council::all();
        foreach($councils as $council){
            $user = $council->user;

            if ($user)
                $user->assignRole('Overseer');
        }

    }
}
