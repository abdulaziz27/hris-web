<?php

namespace Database\Seeders;

use App\Models\ShiftKerja;
use Illuminate\Database\Seeder;

class ShiftKerjaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shifts = [
            [
                'name' => 'Shift Pagi',
                'start_time' => '07:00:00',
                'end_time' => '15:00:00',
                'description' => 'Shift pagi dari jam 7 pagi sampai jam 3 sore',
                'is_cross_day' => false,
                'grace_period_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Shift Sore',
                'start_time' => '15:00:00',
                'end_time' => '23:00:00',
                'description' => 'Shift sore dari jam 3 sore sampai jam 11 malam',
                'is_cross_day' => false,
                'grace_period_minutes' => 10,
                'is_active' => true,
            ],
            [
                'name' => 'Shift Malam',
                'start_time' => '23:00:00',
                'end_time' => '07:00:00',
                'description' => 'Shift malam dari jam 11 malam sampai jam 7 pagi (melewati tengah malam)',
                'is_cross_day' => true,
                'grace_period_minutes' => 10,
                'is_active' => true,
            ],
        ];

        foreach ($shifts as $shift) {
            ShiftKerja::updateOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}
