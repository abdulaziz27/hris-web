<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            [
                'name' => 'Kantor',
                'address' => 'Kantor Pusat',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'hybrid',
                'description' => 'Kantor pusat perusahaan',
                'nilai_hk' => 0.00,
                'timezone' => 'Asia/Jakarta', // WIB (UTC+7)
            ],
            [
                'name' => 'Kalianta',
                'address' => 'Kebun Kalianta',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi kebun Kalianta',
                'nilai_hk' => 140351.00,
                'timezone' => 'Asia/Jakarta', // WIB (UTC+7)
            ],
            [
                'name' => 'Dalu-Dalu',
                'address' => 'Kebun Dalu-Dalu',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi kebun Dalu-Dalu',
                'nilai_hk' => 127000.00,
                'timezone' => 'Asia/Jakarta', // WIB (UTC+7)
            ],
            [
                'name' => 'P.Mandrsah',
                'address' => 'Kebun P.Mandrsah',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi kebun P.Mandrsah',
                'nilai_hk' => 100000.00,
                'timezone' => 'Asia/Jakarta', // WIB (UTC+7)
            ],
            [
                'name' => 'Sarolangun',
                'address' => 'Kebun Sarolangun',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi kebun Sarolangun',
                'nilai_hk' => 108875.00,
                'timezone' => 'Asia/Jakarta', // WIB (UTC+7)
            ],
            [
                'name' => 'T.Dalam/P.Maria',
                'address' => 'Kebun T.Dalam/P.Maria',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi kebun T.Dalam/P.Maria',
                'nilai_hk' => 119702.00,
                'timezone' => 'Asia/Jakarta', // WIB (UTC+7)
            ],
            [
                'name' => 'Simirik/Pargarutan',
                'address' => 'Kebun Simirik/Pargarutan',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi kebun Simirik/Pargarutan',
                'nilai_hk' => 100000.00,
                'timezone' => 'Asia/Jakarta', // WIB (UTC+7)
            ],
            [
                'name' => 'Unit Usaha Marihat',
                'address' => 'Unit Usaha Marihat',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi Unit Usaha Marihat',
                'nilai_hk' => 70000.00,
                'timezone' => 'Asia/Jakarta', // WIB (UTC+7)
            ],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['name' => $location['name']],
                $location
            );
        }

        $this->command->info('8 locations created/updated successfully (Kantor, Kalianta, Dalu-Dalu, P.Mandrsah, Sarolangun, T.Dalam/P.Maria, Simirik/Pargarutan, Unit Usaha Marihat).');
    }
}
