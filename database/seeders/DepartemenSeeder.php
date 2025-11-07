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
            ['name' => 'Operasional Lapangan', 'description' => 'Departemen yang mengelola operasional di lapangan termasuk penanaman, pembibitan, dan pemeliharaan tanaman'],
            ['name' => 'Pemanenan & Pengolahan', 'description' => 'Departemen yang mengelola proses pemanenan hasil kebun dan pengolahan produk'],
            ['name' => 'Administrasi & Keuangan', 'description' => 'Departemen yang mengelola administrasi umum, keuangan, dan dokumentasi perusahaan'],
            ['name' => 'Pemeliharaan Lingkungan', 'description' => 'Departemen yang mengelola pemeliharaan lahan, konservasi, dan kelestarian lingkungan'],
        ];

        foreach ($departemens as $departemen) {
            \App\Models\Departemen::updateOrCreate(
                ['name' => $departemen['name']],
                $departemen
            );
        }

        $this->command->info('4 departemens created/updated successfully.');
    }
}
