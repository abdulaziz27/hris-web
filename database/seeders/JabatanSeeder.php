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
            ['name' => 'Manager Operasional', 'description' => 'Pengelola operasional perkebunan dan perhutanan'],
            ['name' => 'Supervisor Lapangan', 'description' => 'Pengawas operasional di lapangan, pembibitan, dan pemeliharaan'],
            ['name' => 'Pekerja Kebun', 'description' => 'Pekerja lapangan yang melakukan penanaman, perawatan, dan pemanenan'],
            ['name' => 'Staff Administrasi', 'description' => 'Staff yang mengelola administrasi, keuangan, dan dokumentasi'],
        ];

        foreach ($jabatans as $jabatan) {
            \App\Models\Jabatan::updateOrCreate(
                ['name' => $jabatan['name']],
                $jabatan
            );
        }

        $this->command->info('4 jabatans created/updated successfully.');
    }
}
