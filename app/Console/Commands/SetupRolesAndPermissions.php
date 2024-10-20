<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class SetupRolesAndPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-roles-and-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command initializes the roles and permissions for the application.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        //Create Roles
        /*
            Super Admin - hehe
            Bishop, General Admin
            Overseer (View), Overseer Admin
            Governor (View), Governor Admin
            Bacenta Leader, Bacenta Admin
            Fellowship Leader
        */

        $superAdmin = Role::create(['name' => 'Super Admin']);

        $bishop = Role::create(['name' => 'Bishop']);
        $generalAdmin = Role::create(['name' => 'General Admin']);

        $overseer = Role::create(['name' => 'Overseer']);
        $overseerAdmin = Role::create(['name' => 'Overseer Admin']);

        $arrivalAdmin = Role::create(['name' => 'Arrival Admin']);

        $bacentaLeader = Role::create(['name' => 'Bacenta Leader']);
        $bacentaAdmin = Role::create(['name' => 'Bacenta Admin']);

        $fellowshipLeader = Role::create(['name' => 'Fellowship Leader']);

    }
}
