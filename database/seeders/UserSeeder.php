<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Location;
use App\Models\ShiftKerja;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shiftIds = ShiftKerja::pluck('id', 'name');
        $departemenIds = Departemen::pluck('id', 'name');
        $jabatanIds = Jabatan::pluck('id', 'name');
        $locationIds = Location::pluck('id', 'name');

        $users = [
            // Admin - di kantor pusat (Kebun Sawit Purwokerto Timur)
            [
                'name' => 'Admin User',
                'email' => 'admin@admin.com',
                'role' => 'admin',
                'position' => 'Manager Operasional',
                'department' => 'Administrasi & Keuangan',
                'departemen_id' => $departemenIds['Administrasi & Keuangan'] ?? null,
                'jabatan_id' => $jabatanIds['Manager Operasional'] ?? null,
                'location_id' => $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567890',
            ],
            // Manager - di Kebun Sawit
            [
                'name' => 'Budi Santoso',
                'email' => 'manager@company.com',
                'role' => 'manager',
                'position' => 'Manager Operasional',
                'department' => 'Operasional Lapangan',
                'departemen_id' => $departemenIds['Operasional Lapangan'] ?? null,
                'jabatan_id' => $jabatanIds['Manager Operasional'] ?? null,
                'location_id' => $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567891',
            ],
            // Pekerja di Kebun Sawit
            [
                'name' => 'John Doe',
                'email' => 'john@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Operasional Lapangan',
                'departemen_id' => $departemenIds['Operasional Lapangan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567892',
            ],
            // Supervisor di Kebun Karet
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'siti@company.com',
                'role' => 'employee',
                'position' => 'Supervisor Lapangan',
                'department' => 'Pemanenan & Pengolahan',
                'departemen_id' => $departemenIds['Pemanenan & Pengolahan'] ?? null,
                'jabatan_id' => $jabatanIds['Supervisor Lapangan'] ?? null,
                'location_id' => $locationIds['Kebun Karet Banyumas'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567893',
            ],
            // Pekerja di Kebun Karet
            [
                'name' => 'Bob Johnson',
                'email' => 'bob@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Pemanenan & Pengolahan',
                'departemen_id' => $departemenIds['Pemanenan & Pengolahan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Karet Banyumas'] ?? null,
                'shift_name' => 'Shift Siang',
                'phone' => '+6281234567894',
            ],
            // Pekerja di Pembibitan
            [
                'name' => 'Ahmad Fauzi',
                'email' => 'ahmad@company.com',
                'role' => 'employee',
                'position' => 'Supervisor Lapangan',
                'department' => 'Pemeliharaan Lingkungan',
                'departemen_id' => $departemenIds['Pemeliharaan Lingkungan'] ?? null,
                'jabatan_id' => $jabatanIds['Supervisor Lapangan'] ?? null,
                'location_id' => $locationIds['Kebun Pembibitan Cilongok'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567895',
            ],
            // Staff Administrasi di Kantor Pusat
            [
                'name' => 'Maria Clara',
                'email' => 'maria@company.com',
                'role' => 'employee',
                'position' => 'Staff Administrasi',
                'department' => 'Administrasi & Keuangan',
                'departemen_id' => $departemenIds['Administrasi & Keuangan'] ?? null,
                'jabatan_id' => $jabatanIds['Staff Administrasi'] ?? null,
                'location_id' => $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
                'shift_name' => 'Shift Flexible',
                'phone' => '+6281234567896',
            ],
            // Pekerja Kebun di Pembibitan
            [
                'name' => 'Dewi Lestari',
                'email' => 'dewi@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Pemeliharaan Lingkungan',
                'departemen_id' => $departemenIds['Pemeliharaan Lingkungan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Pembibitan Cilongok'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567897',
            ],
        ];

        foreach ($users as $userData) {
            $shiftId = $shiftIds->get($userData['shift_name']) ?? $shiftIds->first();

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'phone' => $userData['phone'],
                    'role' => $userData['role'],
                    'position' => $userData['position'],
                    'department' => $userData['department'],
                    'departemen_id' => $userData['departemen_id'],
                    'jabatan_id' => $userData['jabatan_id'],
                    'shift_kerja_id' => $shiftId,
                    'location_id' => $userData['location_id'],
                ]
            );
        }

        $this->command->info('8 users created/updated successfully with locations assigned.');
    }
}
