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
            // Admin - di kantor pusat
            [
                'name' => 'Admin User',
                'email' => 'admin@admin.com',
                'role' => 'admin',
                'position' => 'Manager Operasional',
                'department' => 'Administrasi & Keuangan',
                'departemen_id' => $departemenIds['Administrasi & Keuangan'] ?? null,
                'jabatan_id' => $jabatanIds['Manager Operasional'] ?? null,
                'location_id' => $locationIds['Kantor Pusat'] ?? $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
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
            // 13 Karyawan dengan variasi jabatan
            // 1. Supervisor Lapangan
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
                'phone' => '+6281234567892',
            ],
            // 2. Supervisor Lapangan
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
                'phone' => '+6281234567893',
            ],
            // 3. Staff Administrasi
            [
                'name' => 'Maria Clara',
                'email' => 'maria@company.com',
                'role' => 'employee',
                'position' => 'Staff Administrasi',
                'department' => 'Administrasi & Keuangan',
                'departemen_id' => $departemenIds['Administrasi & Keuangan'] ?? null,
                'jabatan_id' => $jabatanIds['Staff Administrasi'] ?? null,
                'location_id' => $locationIds['Kantor Pusat'] ?? $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
                'shift_name' => 'Shift Flexible',
                'phone' => '+6281234567894',
            ],
            // 4. Staff Administrasi
            [
                'name' => 'Rina Sari',
                'email' => 'rina@company.com',
                'role' => 'employee',
                'position' => 'Staff Administrasi',
                'department' => 'Administrasi & Keuangan',
                'departemen_id' => $departemenIds['Administrasi & Keuangan'] ?? null,
                'jabatan_id' => $jabatanIds['Staff Administrasi'] ?? null,
                'location_id' => $locationIds['Kantor Pusat'] ?? $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
                'shift_name' => 'Shift Flexible',
                'phone' => '+6281234567895',
            ],
            // 5-15. Pekerja Kebun (11 orang)
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
                'phone' => '+6281234567896',
            ],
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
                'phone' => '+6281234567897',
            ],
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
                'phone' => '+6281234567898',
            ],
            [
                'name' => 'Ahmad Kurniawan',
                'email' => 'ahmad.k@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Operasional Lapangan',
                'departemen_id' => $departemenIds['Operasional Lapangan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Sawit Ajibarang'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567899',
            ],
            [
                'name' => 'Bambang Sutrisno',
                'email' => 'bambang@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Pemanenan & Pengolahan',
                'departemen_id' => $departemenIds['Pemanenan & Pengolahan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Kelapa Sawit Sumbang'] ?? null,
                'shift_name' => 'Shift Siang',
                'phone' => '+6281234567900',
            ],
            [
                'name' => 'Cahyo Prasetyo',
                'email' => 'cahyo@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Operasional Lapangan',
                'departemen_id' => $departemenIds['Operasional Lapangan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
                'shift_name' => 'Shift Malam',
                'phone' => '+6281234567901',
            ],
            [
                'name' => 'Dedi Kurniawan',
                'email' => 'dedi@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Pemanenan & Pengolahan',
                'departemen_id' => $departemenIds['Pemanenan & Pengolahan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Karet Banyumas'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567902',
            ],
            [
                'name' => 'Eko Wijaya',
                'email' => 'eko@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Pemeliharaan Lingkungan',
                'departemen_id' => $departemenIds['Pemeliharaan Lingkungan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Pembibitan Cilongok'] ?? null,
                'shift_name' => 'Shift Siang',
                'phone' => '+6281234567903',
            ],
            [
                'name' => 'Fajar Hidayat',
                'email' => 'fajar@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Operasional Lapangan',
                'departemen_id' => $departemenIds['Operasional Lapangan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Sawit Ajibarang'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567904',
            ],
            [
                'name' => 'Gunawan Setiawan',
                'email' => 'gunawan@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Pemanenan & Pengolahan',
                'departemen_id' => $departemenIds['Pemanenan & Pengolahan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Kelapa Sawit Sumbang'] ?? null,
                'shift_name' => 'Shift Malam',
                'phone' => '+6281234567905',
            ],
            [
                'name' => 'Hadi Santoso',
                'email' => 'hadi@company.com',
                'role' => 'employee',
                'position' => 'Pekerja Kebun',
                'department' => 'Operasional Lapangan',
                'departemen_id' => $departemenIds['Operasional Lapangan'] ?? null,
                'jabatan_id' => $jabatanIds['Pekerja Kebun'] ?? null,
                'location_id' => $locationIds['Kebun Sawit Purwokerto Timur'] ?? null,
                'shift_name' => 'Shift Pagi',
                'phone' => '+6281234567906',
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

        $this->command->info('15 users created/updated successfully (1 Admin + 1 Manager + 13 Employees) with locations assigned.');
    }
}
