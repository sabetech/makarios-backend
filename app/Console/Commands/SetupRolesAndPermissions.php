<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
        //firstOrCreate Roles
        /*
            Super Admin - hehe
            Bishop, General Admin
            Overseer (View), Overseer Admin
            Governor (View), Governor Admin
            Bacenta Leader, Bacenta Admin
            Fellowship Leader
        */

        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);

        $generalAdmin = Role::firstOrCreate(['name' => 'General Admin']);
        $bishop = Role::firstOrCreate(['name' => 'Bishop']);

        $streamLead = Role::firstOrCreate(['name' => 'Stream Lead']);
        $streamAdmin = Role::firstOrCreate(['name' => 'Stream Admin']);

        $regionLead = Role::firstOrCreate(['name' => 'Region Lead']);
        $regionAdmin = Role::firstOrCreate(['name' => 'Region Admin']);

        $zoneLead = Role::firstOrCreate(['name' => 'Zone Lead']);
        $zoneAdmin = Role::firstOrCreate(['name' => 'Zone Admin']);

        $arrivalAdmin = Role::firstOrCreate(['name' => 'Arrival Admin']);

        $bacentaLeader = Role::firstOrCreate(['name' => 'Bacenta Leader']);
        $bacentaAdmin = Role::firstOrCreate(['name' => 'Bacenta Admin']);

        $fellowshipLeader = Role::firstOrCreate(['name' => 'Fellowship Leader']);

        //firstOrCreate permissions
        Permission::firstOrCreate(['name' => 'view churches']);
        Permission::firstOrCreate(['name' => 'firstOrCreate churches']);
        Permission::firstOrCreate(['name' => 'update churches']);
        Permission::firstOrCreate(['name' => 'delete churches']);

        Permission::firstOrCreate(['name' => 'view streams']);
        Permission::firstOrCreate(['name' => 'firstOrCreate streams']);
        Permission::firstOrCreate(['name' => 'update streams']);
        Permission::firstOrCreate(['name' => 'delete streams']);

        Permission::firstOrCreate(['name' => 'view regions']);
        Permission::firstOrCreate(['name' => 'firstOrCreate regions']);
        Permission::firstOrCreate(['name' => 'update regions']);
        Permission::firstOrCreate(['name' => 'delete regions']);

        Permission::firstOrCreate(['name' => 'view zones']);
        Permission::firstOrCreate(['name' => 'firstOrCreate zones']);
        Permission::firstOrCreate(['name' => 'update zones']);
        Permission::firstOrCreate(['name' => 'delete zones']);

        Permission::firstOrCreate(['name' => 'view users']);
        Permission::firstOrCreate(['name' => 'firstOrCreate users']);
        Permission::firstOrCreate(['name' => 'update users']);
        Permission::firstOrCreate(['name' => 'delete users']);

        Permission::firstOrCreate(['name' => 'view bacentas']);
        Permission::firstOrCreate(['name' => 'firstOrCreate bacentas']);
        Permission::firstOrCreate(['name' => 'update bacentas']);
        Permission::firstOrCreate(['name' => 'delete bacentas']);

        Permission::firstOrCreate(['name' => 'view members']);
        Permission::firstOrCreate(['name' => 'firstOrCreate members']);
        Permission::firstOrCreate(['name' => 'update members']);
        Permission::firstOrCreate(['name' => 'delete members']);

        Permission::firstOrCreate(['name' => 'transfer members']);

        Permission::firstOrCreate(['name' => 'arrival settings']);

        Permission::firstOrCreate(['name' => 'approve arrivals']);

        $superAdmin->givePermissionTo(Permission::all());
        $bishop->givePermissionTo([
            'view churches',
            'firstOrCreate churches',
            'update churches',
            'delete churches',

            'view streams',
            'firstOrCreate streams',
            'update streams',
            'delete streams',

            'view regions',
            'firstOrCreate regions',
            'update regions',
            'delete regions',

            'view zones',
            'firstOrCreate zones',
            'update zones',
            'delete zones',

            'view bacentas',
            'firstOrCreate bacentas',
            'update bacentas',
            'delete bacentas',
        ]);

        $streamLead->givePermissionTo([
            'view streams',
            'firstOrCreate streams',
            'update streams',
            'delete streams',

            'view regions',
            'firstOrCreate regions',
            'update regions',
            'delete regions',

            'view zones',
            'firstOrCreate zones',
            'update zones',
            'delete zones',

            'view bacentas',
            'firstOrCreate bacentas',
            'update bacentas',
            'delete bacentas',
        ]);

        $regionLead->givePermissionTo([
            'view regions',
            'firstOrCreate regions',
            'update regions',
            'delete regions',

            'view zones',
            'firstOrCreate zones',
            'update zones',
            'delete zones',

            'view bacentas',
            'firstOrCreate bacentas',
            'update bacentas',
            'delete bacentas',
        ]);

        $zoneLead->givePermissionTo([
            'view zones',
            'firstOrCreate zones',
            'update zones',
            'delete zones',

            'view bacentas',
            'firstOrCreate bacentas',
            'update bacentas',
            'delete bacentas',
        ]);

        $bacentaLeader->givePermissionTo([
            'view bacentas',
            'firstOrCreate bacentas',
            'update bacentas',
            'delete bacentas',
        ]);


    }
}
