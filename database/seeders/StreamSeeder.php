<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Stream;
use App\Models\User;

class StreamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $streams = [
            [
                'name' => 'Jesus Experience Service',
                'time' => '19:00:00',
                'day' => 'Friday',
                'church_id' => 1,
                'leader_email' => 'ebaidoo@yahoo.com',
            ],
            [
                'name' => 'Fresh Oil Service',
                'time' => '07:30:00',
                'day' => 'Sunday',
                'church_id' => 1,
                'leader_email' => 'opiamensahad817@gmail.com',
            ],
            [
                'name' => 'Fresh Oil Service',
                'time' => '09:30:00',
                'day' => 'Sunday',
                'church_id' => 1,
                'leader_email' => 'opiamensahad817@gmail.com',
            ]
        ];

        foreach($streams as $stream) {
            $leader = User::where('email', 'LIKE', $stream['leader_email'])->first();
            Stream::create([
                'name' => $stream['name'],
                'meeting_day' => $stream['day'],
                'meeting_time' => $stream['time'],
                'church_id' => $stream['church_id'],
                'stream_overseer_id' => $leader->id
            ]);
        }

    }
}
