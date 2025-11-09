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
            // Kantor Pusat
            [
                'name' => 'Kantor Pusat',
                'address' => 'Jl. Raya Purwokerto Timur No. 123, Kec. Purwokerto Timur, Kabupaten Banyumas, Jawa Tengah',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'hybrid',
                'description' => 'Kantor pusat perusahaan dengan fasilitas lengkap',
            ],
            // 5 Kebun
            [
                'name' => 'Kebun Sawit Purwokerto Timur',
                'address' => 'Jl. Raya Purwokerto Timur, Kec. Purwokerto Timur, Kabupaten Banyumas, Jawa Tengah',
                'latitude' => '-7.424154',
                'longitude' => '109.242088',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi kebun sawit utama di area Purwokerto Timur dengan luas 100 hektar',
            ],
            [
                'name' => 'Kebun Karet Banyumas',
                'address' => 'Desa Kalisube, Kec. Banyumas, Kabupaten Banyumas, Jawa Tengah',
                'latitude' => '-7.515342',
                'longitude' => '109.312567',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'hybrid',
                'description' => 'Kebun karet dengan luas 50 hektar, produksi lateks harian',
            ],
            [
                'name' => 'Kebun Pembibitan Cilongok',
                'address' => 'Desa Pernasidi, Kec. Cilongok, Kabupaten Banyumas, Jawa Tengah',
                'latitude' => '-7.432891',
                'longitude' => '109.145673',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Lokasi pembibitan dan persemaian tanaman dengan teknologi modern',
            ],
            [
                'name' => 'Kebun Sawit Ajibarang',
                'address' => 'Desa Karangnangka, Kec. Ajibarang, Kabupaten Banyumas, Jawa Tengah',
                'latitude' => '-7.382456',
                'longitude' => '109.123789',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'location_based_only',
                'description' => 'Kebun sawit area Ajibarang dengan luas 150 hektar',
            ],
            [
                'name' => 'Kebun Kelapa Sawit Sumbang',
                'address' => 'Desa Banjarsari, Kec. Sumbang, Kabupaten Banyumas, Jawa Tengah',
                'latitude' => '-7.456123',
                'longitude' => '109.289456',
                'radius_km' => '1000',
                'is_active' => true,
                'attendance_type' => 'hybrid',
                'description' => 'Kebun kelapa sawit di area Sumbang dengan sistem irigasi modern',
            ],
        ];

        foreach ($locations as $location) {
            Location::updateOrCreate(
                ['name' => $location['name']],
                $location
            );
        }

        $this->command->info('6 locations created/updated successfully (1 Kantor Pusat + 5 Kebun).');
    }
}
