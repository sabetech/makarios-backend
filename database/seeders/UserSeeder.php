<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User; // Import the User model

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //populate the db with users from here ...
        //read a csv file and insert into users table
        $regionHeadEmails = [
            'kanyomark9@gmail.com',
            'psblayand@gmail.com',
            'pubfriend6@gmail.com',
            'sacheampong80@gmail.com',
            'oseibonsunana81@gmail.com',
            'iamsakyi@yahoo.com',
            'nhyiramint@gmail.com',
            'oduropa1@gmail.com',
            'atkofi877@gmail.com',
            'oladeleolagoke@gmail.com',
            'wodameamin@gmail.com',
            'anthonyackon40@gmail.com',
            'kennedykorsitse@gmail.com',
            'myyjustice@yahoo.co.uk',
        ];

        $zoneHeadEmails = [
            'Princeabakah@gmail.com',
            'kwabrokyir@gmail.com',
            'nkotey121@gmail.com',
            'rolandsackey87@gmail.com',
            'kwabrokyir@gmail.com',
        ];

        $bishops = [
            'harrykazo@gmail.com',
            'ebaidoo@yahoo.com',
        ];

        // Read the CSV file
        $filePath = database_path('data-dumps/UserSeed.csv');
        $rows = array_map('str_getcsv', file($filePath));

        // Insert the data into the users table
        foreach ($rows as $row) {

            //skip first row
            if ($row[1] == 'name') {
                continue;
            }

            $user = User::create([
                'name' => $row[1],
                'email' => $row[2],
                'img_url' => $row[3],
                'password' => bcrypt($row[5]), // Hash the password
            ]);

            $this->command->info('User ' . $row[1] . ' created successfully.');

            if (in_array($row[2], $regionHeadEmails)) {
                $user->assignRole('Region Lead');
            } elseif (in_array($row[2], $zoneHeadEmails)) {
                $user->assignRole('Zone Lead');
            } elseif (in_array($row[2], $bishops)) {
                $user->assignRole('Bishop');
            }
        }
    }
}
