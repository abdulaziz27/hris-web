<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Company::updateOrCreate(
            ['email' => 'info@sairnapaor.com'],
            [
                'name' => 'PT. Sair Napaor COM',
                'email' => 'info@sairnapaor.com',
                'address' => 'Kantor Pusat',
                'latitude' => '0.000000',
                'longitude' => '0.000000',
                'radius_km' => '1000',
                'attendance_type' => 'location_based',
            ]
        );
    }
}
