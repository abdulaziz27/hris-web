<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class JabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jabatans = [
            ['name' => 'Admin', 'description' => 'Administrator/kepala operasional perkebunan'],
            ['name' => 'Manager', 'description' => 'Manager/pengawas operasional di lapangan'],
            ['name' => 'Pekerja', 'description' => 'Pekerja lapangan dan administrasi'],
        ];

        foreach ($jabatans as $jabatan) {
            \App\Models\Jabatan::updateOrCreate(
                ['name' => $jabatan['name']],
                $jabatan
            );
        }

        $this->command->info('3 jabatans created/updated successfully (Admin, Manager, Pekerja).');
    }
}
