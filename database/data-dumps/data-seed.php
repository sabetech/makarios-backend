<?php

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
        Council::create([
            'name' => $key,
            'description' => implode(', ', $council['areas']),
            'leader_id' => $council['leader_id'],
            'stream_id' => 1
        ]);
    }else {
        Council::create([
            'name' => $key,
            'description' => implode(', ', $council['areas']),
            'stream_id' => 1
        ]);
    }
}

$councilsFromDB = Council::all();


$councilLeaders = [

];

// foreach ($councilsFromDB as $key => $council) {
//     foreach ($councils[$council->name]['areas'] as $value) {
//         Bacenta::create([
//             'name' => $value,
//             'council_id' => $council->id,
//         ]);
//     }
// }


// foreach ($councils as $key => $council) {
//     foreach ($council['areas'] as $value) {
//         Bacenta::create([
//             'name' => $value,
//             'council_id' => $key,
//         ]);
//     }
// }


