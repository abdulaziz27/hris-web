<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $seeders = [
            // ============================================
            // CORE DATA SEEDERS (Updated with client data)
            // ============================================
            DepartemenSeeder::class,        // ✅ Updated: 5 departemens (Pembibitan, Kantor/Umum, Tanaman/TBS, SUS BHT, Kelapa Kopyor)
            JabatanSeeder::class,           // ✅ Updated: 3 jabatans (Admin, Manager, Pekerja)
            ShiftKerjaSeeder::class,        // ✅ Updated: 3 shifts (Pagi 07:00-15:00, Sore 15:00-23:00, Malam 23:00-07:00)
            LocationSeeder::class,          // ✅ Updated: 8 locations (Kantor + 7 kebun dengan nilai_hk)
            UserSeeder::class,              // ✅ Updated: Admin, Manager + ~300+ employees from client data
            CompanySeeder::class,           // ✅ Updated: PT. Sairna Paor company info

            // ============================================
            // PIVOT TABLE SEEDERS (DEPRECATED - DISABLED)
            // ============================================
            // Note: UserSeeder now uses direct foreign keys (jabatan_id, departemen_id, shift_kerja_id)
            // Pivot tables are kept for backward compatibility only
            // DepartemenUserSeeder::class,    // ❌ DISABLED - Using direct foreign key (departemen_id)
            // JabatanUserSeeder::class,       // ❌ DISABLED - Using direct foreign key (jabatan_id)
            // ShiftKerjaUserSeeder::class,    // ❌ DISABLED - Using direct foreign key (shift_kerja_id)

            // ============================================
            // LEAVE MANAGEMENT SEEDERS
            // ============================================
            LeaveTypeSeeder::class,         // ✅ Already matches company policy
            
            // ============================================
            // HOLIDAY SEEDERS
            // ============================================
            IndonesiaPublicHoliday2025Seeder::class, // ✅ Updated: Official 2025 holidays + cuti bersama
        ];

        // ============================================
        // TESTING SEEDERS - DISABLED di production
        // ============================================
        // Note: Seeder testing hanya dijalankan di development/staging
        // Jangan dijalankan di production karena akan membuat data dummy/testing
        if (!app()->environment('production')) {
            $seeders[] = LeaveTestingSeeder::class;       // ❌ DISABLED di production - Data testing
            $seeders[] = AttendanceSeeder::class;         // ❌ DISABLED di production - Berbahaya! Menghapus semua attendance
            $seeders[] = OvertimeSeeder::class;           // ❌ DISABLED di production - Data testing
            $seeders[] = NoteSeeder::class;               // ❌ DISABLED di production - Data testing
        }

        $this->call($seeders);

        // ============================================
        // DISABLED SEEDERS (Not needed)
        // ============================================
        // QrAbsenSeeder::class,
        // PermissionSeeder::class,
        // EmployeeBulkSeeder::class,     // DISABLED - Using client data from UserSeeder
        // DashboardAttendanceSeeder::class, // DISABLED - Using AttendanceSeeder
        // DashboardStatsSeeder::class,   // DISABLED - Using LeaveTestingSeeder & OvertimeSeeder
    }
}
