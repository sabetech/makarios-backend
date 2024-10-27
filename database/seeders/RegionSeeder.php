<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $regions_second_service = [
            'Charis' => [
                'Windy Ridge',
                'Pipe Ano',
                'Apowa',
                'Beahu',
                'Kwesimintsim',
                'Cocoa Villa',
                'Effia',
                'Market Circle',
                'Anaji Fie',
            ],
            'Tsalach' => [
                'Sekondi European Town',
                'Adiembra',
                'Kweikuma',
                'Ketan',
                'Kojokrom',
            ],
            'Genesis' => [
                'Mpintin',
                'Kojokrom',
                'Obokrom',
                'Fijai Hills',
                'Fijai Town',
            ],
            'Grace' => [
                'Kwesimintim Town',
                'East Tanokrom',
                'West Tanokrom',
                'Effiakuma No. 9',
                'Effiakuma Bus Stop',
                'Adientem',
            ],
            'Dunamis' => [
                'Lagos Town-Sawmill',
                'Kwesimintim Gabusu',
                'Effia No.9'
            ],
            'Elaia City' => [
                'Nkontompo',
                'Esaman',
                'Sekondi Anaafo',
                'Bakaekyir',
            ]
        ];

        $region_experience_service = [
            'UMAT NTC' => [
                'UMAT',
                'NMTC'
            ],
            'TTU' => [
                'TTU ZONE 1',
                'TTU ZONE 2',
                'TTU ZONE 3',
                'TTU ZONE 4',
                'TTU ZONE 5'
            ]
        ];

        foreach ($regions_second_service as $region => $zones) {
            $region = \App\Models\Region::create([
                'region' => $region,
                'stream_id' => 3,
            ]);

            $this->command->info('Region ' . $region->region . ' created successfully.');

            foreach ($zones as $zone) {
                $zone = $region->zones()->create([
                    'name' => $zone,
                    'region_id' => $region->id
                ]);

                $this->command->info('Zone ' . $zone->name . ' created successfully.');

               $bacenta = \App\Models\Bacenta::create([
                    'name' => $zone->name . ' 1 Bacenta',
                    'region_id' => $region->id,
                    'zone_id' => $zone->id,
                ]);

                $this->command->info('Bacenta ' . $bacenta->name . ' created successfully.');
            }
        }

        foreach ($region_experience_service as $region => $zones) {
            $region = \App\Models\Region::create([
                'region' => $region,
                'stream_id' => 1,
            ]);

            foreach ($zones as $zone) {
                $region->zones()->create([
                    'name' => $zone,
                    'region_id' => $region->id
                ]);
            }
        }
    }
}
