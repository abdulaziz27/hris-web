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
        $this->call([
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
            LeaveTestingSeeder::class,       // ✅ Updated: 8 employees from 8 different locations

            // ============================================
            // HOLIDAY & ATTENDANCE SEEDERS
            // ============================================
            IndonesiaPublicHoliday2025Seeder::class, // ✅ Updated: Official 2025 holidays + cuti bersama
            AttendanceSeeder::class,        // ✅ Updated: Last 2 months, workdays only, 5-8 sample employees

            // ============================================
            // OVERTIME & OTHER DATA SEEDERS
            // ============================================
            OvertimeSeeder::class,          // ✅ Updated: 10-15 sample requests from seeded employees
            NoteSeeder::class,              // ⚠️ Optional: Dummy notes for testing (can be disabled if not needed)

            // ============================================
            // DISABLED SEEDERS (Not needed)
            // ============================================
            // QrAbsenSeeder::class,
            // PermissionSeeder::class,
            // EmployeeBulkSeeder::class,     // DISABLED - Using client data from UserSeeder
            // DashboardAttendanceSeeder::class, // DISABLED - Using AttendanceSeeder
            // DashboardStatsSeeder::class,   // DISABLED - Using LeaveTestingSeeder & OvertimeSeeder
        ]);
    }
}
