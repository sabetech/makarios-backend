<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceSeverityThresholdSeeder extends Seeder
{
    public function run(): void
    {
        $thresholds = [
            ['min_absences' => 0, 'max_absences' => 0, 'label' => 'Active', 'color' => '#22c55e'],
            ['min_absences' => 1, 'max_absences' => 1, 'label' => 'Mild', 'color' => '#eab308'],
            ['min_absences' => 2, 'max_absences' => 3, 'label' => 'Moderate', 'color' => '#f97316'],
            ['min_absences' => 4, 'max_absences' => null, 'label' => 'Severe', 'color' => '#ef4444'],
        ];

        foreach ($thresholds as $threshold) {
            DB::table('attendance_severity_thresholds')->updateOrInsert(
                ['min_absences' => $threshold['min_absences']],
                array_merge($threshold, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
