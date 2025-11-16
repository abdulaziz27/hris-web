<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DepartemenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departemens = [
            ['name' => 'Pembibitan', 'description' => 'Departemen yang mengelola pembibitan dan persemaian tanaman'],
            ['name' => 'Kantor/Umum', 'description' => 'Departemen yang mengelola administrasi umum, keuangan, dan operasional kantor'],
            ['name' => 'Tanaman/TBS', 'description' => 'Departemen yang mengelola tanaman dan Tandan Buah Segar (TBS)'],
            ['name' => 'SUS BHT', 'description' => 'Departemen yang mengelola SUS BHT'],
            ['name' => 'Kelapa Kopyor', 'description' => 'Departemen yang mengelola kelapa kopyor'],
        ];

        foreach ($departemens as $departemen) {
            \App\Models\Departemen::updateOrCreate(
                ['name' => $departemen['name']],
                $departemen
            );
        }

        $this->command->info('5 departemens created/updated successfully.');
    }
}
