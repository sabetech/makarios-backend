<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Fellowship;
use App\Models\Council;
use App\Models\Bacenta;

class setup_initial_data extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup_initial_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This sets up the pastors and the areas they are working at etc. Later, an admin portal will do this for you.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //fresh database ...
        // $this->call('migrate:fresh');
        //
        $areas = ["Fijai Zongo 1", "Fijai Zongo 2", "Fijai West", "Fijai Town", "Mpintsim"];

        $councils = [
            "FIJAI" => [
                "leader_id" => 29,
                "areas" => ["Fijai Zongo 1", "Fijai Zongo 2", "Fijai West", "Fijai Town", "Mpintsim"]
                ],
            "ESSAMAN" => [
                'areas' => ["Essaman/Ekuase", "Anafo", "Dokodu", "Bakakyir"]
            ],
            "TANOKROM" => [
                'leader_id' => 17,
                "areas" => ["East Tanokrom, West Tanokrom, Roman Down"],
            ],
            "EFFIAKUMA" =>
                ["leader_id" => 11, "areas" => ["Number 9", "Number 1", "Estate", "Bankyease"]],
            "AHENFIE" =>
                ["leader_id" => 18, "areas" => ["STMA School park", "Ahenfie"]],
            "SAWMILL" =>
                ["leader_id" => 27, "areas" => ["Sawmill", "K Kabuza", "Effia"]],
            "EFFIAKUMA_BUS_STOP" =>
                ["areas" =>["Effiakuma Bus stop"]],
            "NTANKOFUL SCHOOL" =>
                ["areas" =>["SKYY FM", 'Ntankoful School']],
            'ANAJI' =>
                ["leader_id" => 25, "areas" => ["Anaji Fie", "Nhyira Hotel", "Elder Taylor", "Choice Mart"]],
            'FBN BANK' =>
                ['leader_id' => 21, 'areas' => ['FBN Bank']],
        ];

        foreach ($councils as $key => $council) {
            if (isset($council['leader_id'])) {
                $newCouncil = Council::create([
                    'name' => $key,
                    'description' => implode(', ', $council['areas']),
                    'leader_id' => $council['leader_id'],
                    'stream_id' => 1
                ]);
            }else {
                $newCouncil = Council::create([
                    'name' => $key,
                    'description' => implode(', ', $council['areas']),
                    'stream_id' => 1
                ]);
            }

            foreach($council['areas'] as $area) {
                $bacenta = Bacenta::create([
                    'council_id' => $newCouncil->id,
                    'name' => $area
                ]);

                $fellowship = Fellowship::create([
                    'name' => $bacenta->name . " Fellowship 1",
                    'bacenta_id' => $bacenta->id,
                    'council_id' => $newCouncil->id,
                    'stream_id' => 1,
                    'church_id' => 1,
                ]);

            }
        }
    }
}
