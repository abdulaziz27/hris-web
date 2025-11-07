<?php

namespace Database\Seeders;

use App\Models\Departemen;
use App\Models\Jabatan;
use App\Models\Location;
use App\Models\ShiftKerja;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployeeBulkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generate 132 employees with realistic distribution across positions, departments, and locations.
     */
    public function run(): void
    {
        $shiftIds = ShiftKerja::pluck('id', 'name');
        $departemenIds = Departemen::pluck('id', 'name');
        $jabatanIds = Jabatan::pluck('id', 'name');
        $locationIds = Location::where('is_active', true)->pluck('id', 'name');

        // Check if we already have enough employees
        $existingEmployees = User::where('role', 'employee')->count();
        $targetCount = 132;
        $needed = max(0, $targetCount - $existingEmployees);

        if ($needed <= 0) {
            $this->command->info("Already have {$existingEmployees} employees. Target: {$targetCount}. Skipping...");

            return;
        }

        $this->command->info("Generating {$needed} employees to reach total of {$targetCount}...");

        // Realistic distribution by position
        $positionDistribution = [
            'Manager Operasional' => (int) ($needed * 0.025), // 2.5% = ~6-7 people
            'Supervisor Lapangan' => (int) ($needed * 0.16), // 16% = ~40-41 people
            'Pekerja Kebun' => (int) ($needed * 0.70), // 70% = ~180 people
            'Staff Administrasi' => (int) ($needed * 0.105), // 10.5% = ~30 people
        ];

        // Ensure total matches needed (adjust largest group if needed)
        $total = array_sum($positionDistribution);
        if ($total < $needed) {
            $positionDistribution['Pekerja Kebun'] += ($needed - $total);
        }

        // Realistic department distribution
        $departmentDistribution = [
            'Operasional Lapangan' => 0.47, // 47%
            'Pemanenan & Pengolahan' => 0.31, // 31%
            'Administrasi & Keuangan' => 0.14, // 14%
            'Pemeliharaan Lingkungan' => 0.08, // 8%
        ];

        // Location distribution (most employees in kebun locations)
        $locationNames = $locationIds->keys()->toArray();
        $kantorPusatId = $locationIds->get('Kantor Pusat');
        $kebunLocations = array_filter($locationNames, fn ($name) => $name !== 'Kantor Pusat');

        $employeeCount = 0;
        $firstNames = $this->getIndonesianFirstNames();
        $lastNames = $this->getIndonesianLastNames();

        // Generate employees by position
        foreach ($positionDistribution as $positionName => $count) {
            $jabatanId = $jabatanIds->get($positionName);

            if (! $jabatanId) {
                $this->command->warn("Position '{$positionName}' not found. Skipping...");

                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                // Determine department based on position
                $department = $this->getDepartmentForPosition($positionName, $departmentDistribution, $departemenIds);

                // Determine location based on position and department
                $locationId = $this->getLocationForEmployee($positionName, $department, $kantorPusatId, $kebunLocations, $locationIds);

                // Determine shift based on position
                $shiftName = $this->getShiftForPosition($positionName);
                $shiftId = $shiftIds->get($shiftName) ?? $shiftIds->first();

                // Generate name
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $name = $firstName.' '.$lastName;

                // Generate unique email
                $email = strtolower(str_replace(' ', '.', $firstName.'.'.$lastName)).'.'.($employeeCount + 1).'@company.com';
                $phone = '+628'.rand(100000000, 999999999);

                User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'phone' => $phone,
                    'role' => 'employee',
                    'position' => $positionName,
                    'department' => $department,
                    'departemen_id' => $departemenIds->get($department),
                    'jabatan_id' => $jabatanId,
                    'shift_kerja_id' => $shiftId,
                    'location_id' => $locationId,
                ]);

                $employeeCount++;

                if ($employeeCount % 50 === 0) {
                    $this->command->info("Generated {$employeeCount} employees...");
                }
            }
        }

        $totalEmployees = User::where('role', 'employee')->count();
        $this->command->info("✅ Successfully generated employees. Total employees: {$totalEmployees}");
    }

    /**
     * Get department for position with realistic distribution
     */
    private function getDepartmentForPosition(string $position, array $distribution, $departemenIds): string
    {
        // Managers and Staff Administrasi mostly in Administrasi & Keuangan
        if ($position === 'Manager Operasional' || $position === 'Staff Administrasi') {
            $roll = rand(1, 100);
            if ($roll <= 80) {
                return 'Administrasi & Keuangan';
            }

            $departments = ['Operasional Lapangan', 'Pemanenan & Pengolahan'];

            return $departments[array_rand($departments)];
        }

        // Supervisors and Workers distributed across field departments
        $roll = rand(1, 100);
        if ($roll <= 47) {
            return 'Operasional Lapangan';
        } elseif ($roll <= 78) {
            return 'Pemanenan & Pengolahan';
        } elseif ($roll <= 92) {
            return 'Administrasi & Keuangan';
        } else {
            return 'Pemeliharaan Lingkungan';
        }
    }

    /**
     * Get location for employee based on position and department
     */
    private function getLocationForEmployee(string $position, string $department, $kantorPusatId, array $kebunLocations, $locationIds)
    {
        // Staff Administrasi and Managers mostly in Kantor Pusat
        if ($position === 'Staff Administrasi' || $position === 'Manager Operasional') {
            $roll = rand(1, 100);
            if ($roll <= 70) {
                return $kantorPusatId;
            }
        }

        // Others distributed across kebun locations
        return $locationIds->get($kebunLocations[array_rand($kebunLocations)]) ?? $locationIds->first();
    }

    /**
     * Get shift for position
     */
    private function getShiftForPosition(string $position): string
    {
        if ($position === 'Staff Administrasi' || $position === 'Manager Operasional') {
            return 'Shift Flexible';
        }

        $roll = rand(1, 100);
        if ($roll <= 50) {
            return 'Shift Pagi';
        } elseif ($roll <= 85) {
            return 'Shift Siang';
        } else {
            return 'Shift Malam';
        }
    }

    /**
     * Get Indonesian first names
     */
    private function getIndonesianFirstNames(): array
    {
        return [
            'Ahmad', 'Budi', 'Cipto', 'Dedi', 'Eko', 'Fajar', 'Gunawan', 'Hadi', 'Indra', 'Joko',
            'Kurniawan', 'Lukman', 'Mulyadi', 'Nur', 'Oki', 'Prasetyo', 'Rahmat', 'Sari', 'Tono', 'Udin',
            'Wahyu', 'Yanto', 'Zainal', 'Ade', 'Bayu', 'Cahya', 'Dani', 'Eka', 'Fauzi', 'Guntur',
            'Hari', 'Irfan', 'Jaya', 'Krisna', 'Lina', 'Maya', 'Nina', 'Oka', 'Putra', 'Rizki',
            'Sari', 'Tina', 'Umar', 'Vina', 'Wati', 'Yani', 'Zulfa', 'Aji', 'Bima', 'Candra',
            'Darma', 'Evan', 'Feri', 'Gita', 'Hendra', 'Ika', 'Joni', 'Kiki', 'Lia', 'Mira',
            'Nanda', 'Omar', 'Puji', 'Rina', 'Sari', 'Tari', 'Umi', 'Vera', 'Winda', 'Yulia',
        ];
    }

    /**
     * Get Indonesian last names
     */
    private function getIndonesianLastNames(): array
    {
        return [
            'Santoso', 'Wijaya', 'Prasetyo', 'Kurniawan', 'Saputra', 'Setiawan', 'Hidayat', 'Rahman',
            'Sari', 'Lestari', 'Nugroho', 'Siregar', 'Sihombing', 'Simanjuntak', 'Situmorang', 'Siregar',
            'Nasution', 'Lubis', 'Harahap', 'Daulay', 'Pangaribuan', 'Sitorus', 'Sinaga', 'Manurung',
            'Saragih', 'Siahaan', 'Tambunan', 'Pardede', 'Sihotang', 'Nainggolan', 'Sihombing', 'Simbolon',
            'Pasaribu', 'Marpaung', 'Samosir', 'Hutagalung', 'Sianturi', 'Sidabutar', 'Sihaloho', 'Sitorus',
            'Samosir', 'Hutapea', 'Situmorang', 'Sirait', 'Saragih', 'Sihombing', 'Saragih', 'Sihotang',
            'Tambunan', 'Pardede', 'Sihotang', 'Nainggolan', 'Sihombing', 'Simbolon', 'Pasaribu', 'Marpaung',
        ];
    }
}
