<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Location;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollCalculator;
use App\Support\WorkdayCalculator;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PayrollAttendanceExport implements WithMultipleSheets
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected ?int $locationId;

    public function __construct(Carbon $startDate, Carbon $endDate, ?int $locationId = null)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->locationId = $locationId;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        // Get locations to export
        $locations = $this->locationId 
            ? Location::where('id', $this->locationId)->get()
            : Location::all();

        foreach ($locations as $location) {
            $sheets[] = new PayrollAttendanceSheet($this->startDate, $this->endDate, $location);
        }

        return $sheets;
    }
}

class PayrollAttendanceSheet implements FromCollection, WithTitle, WithColumnWidths, WithStyles, WithCustomStartCell, WithEvents
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected Location $location;
    protected array $payrollData = []; // Store payroll data for each user (key: user_id)

    public function __construct(Carbon $startDate, Carbon $endDate, Location $location)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->location = $location;
    }

    public function startCell(): string
    {
        return 'A7'; // Start from row 7 - data will be here, we'll set headings and dates manually in AfterSheet
    }

    public function title(): string
    {
        // Limit sheet name to 31 characters (Excel limit)
        $name = $this->location->name;
        return mb_substr($name, 0, 31);
    }

    // Removed headings() - we'll set headings manually in AfterSheet to have full control

    public function collection()
    {
        // Get users for this location
        $users = User::where('location_id', $this->location->id)
            ->where('role', 'employee')
            ->orderBy('name')
            ->get();

        $data = collect();

        foreach ($users as $user) {
            // Get payroll for this period (exact match dengan start of month)
            $period = $this->startDate->copy()->startOfMonth();
            $payroll = Payroll::where('user_id', $user->id)
                ->where('period', $period->toDateString())
                ->first();

            $row = [
                $user->id,
                $user->name,
                $user->department ?? $user->position ?? '-',
            ];

            // Get daily attendance status for each date
            // Use numeric values: 1 = hadir, 0 = tidak hadir
            $currentDate = $this->startDate->copy();
            $dailyStatus = [];
            
            while ($currentDate <= $this->endDate) {
                $status = PayrollCalculator::getDailyAttendanceStatus($user->id, $currentDate);
                // Use numeric: 1 for present, 0 for absent
                $dailyStatus[] = ($status === 'H') ? 1 : 0;
                $currentDate->addDay();
            }
            
            $row = array_merge($row, $dailyStatus);

            // Add summary data
            // Note: Store numbers as numbers (not formatted strings) so formulas can work
            // Formatting will be applied in AfterSheet
            // HK Review and Selisih HK are hidden - Estimasi Gaji = Nilai HK × Hadir
            // Sesuai Hasil Wawancara column removed
            // Jobdesk = user.position (from master data)
            // Hadir: gunakan present_days dari payroll jika ada dan sudah approved, otherwise formula SUM
            if ($payroll) {
                // Store payroll data for use in AfterSheet
                $this->payrollData[$user->id] = [
                    'present_days' => $payroll->present_days,
                    'status' => $payroll->status,
                ];
                
                $row[] = $payroll->standard_workdays ?? 0;
                // Hadir: akan di-set di AfterSheet (nilai jika approved, formula jika draft)
                $row[] = 0; // Placeholder, will be replaced in AfterSheet
                // Persentase will be formula: (Hadir / Hari Kerja) * 100
                $row[] = 0; // Placeholder, will be replaced by formula in AfterSheet
                $row[] = $payroll->nilai_hk ?? 0; // Store as number, not formatted string
                // Estimasi Gaji will be formula: Nilai HK × Hadir
                $row[] = 0; // Placeholder, will be replaced by formula in AfterSheet
                $row[] = $user->position ?? '-'; // Jobdesk from user.position (master data)
            } else {
                // Use calculated data
                $standardWorkdays = PayrollCalculator::calculateStandardWorkdays(
                    $this->startDate->copy()->startOfMonth(),
                    $this->endDate->copy()->endOfMonth()
                );
                $nilaiHK = PayrollCalculator::getNilaiHK($user->id, $user->location_id);

                $row[] = $standardWorkdays;
                // Hadir will be formula: SUM of attendance columns (1/0 values)
                $row[] = 0; // Placeholder, will be replaced by formula in AfterSheet
                // Persentase will be formula: (Hadir / Hari Kerja) * 100
                $row[] = 0; // Placeholder, will be replaced by formula in AfterSheet
                $row[] = $nilaiHK; // Store as number, not formatted string
                // Estimasi Gaji will be formula: Nilai HK × Hadir
                $row[] = 0; // Placeholder, will be replaced by formula in AfterSheet
                $row[] = $user->position ?? '-'; // Jobdesk from user.position (master data)
            }

            $data->push($row);
        }

        return $data;
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 8,  // ID (index 1)
            'B' => 25, // NAMA (index 2)
            'C' => 20, // Bagian (index 3)
        ];

        $dateCount = $this->startDate->diffInDays($this->endDate) + 1;

        // Kolom tanggal mulai dari D (index 4)
        for ($i = 0; $i < $dateCount; $i++) {
            $col = $this->getColumnLetter(4 + $i); // 4 = D
            $widths[$col] = 6;
        }

        // Kolom summary dimulai setelah kolom tanggal
        $summaryStartIndex = 4 + $dateCount;
        $summaryCols = ['Hari Kerja', 'Hadir', 'Persentase', 'Nilai HK', 'Estimasi Gaji', 'Jobdesk'];

        foreach ($summaryCols as $index => $colName) {
            $col = $this->getColumnLetter($summaryStartIndex + $index);
            // Jobdesk lebih lebar karena isinya bisa panjang
            if ($colName === 'Jobdesk') {
                $widths[$col] = 35;
            } else {
                $widths[$col] = 15;
            }
        }

        return $widths;
    }

    protected function getColumnLetter($num): string
    {
        $letter = '';
        while ($num > 0) {
            $num--;
            $letter = chr(65 + ($num % 26)) . $letter;
            $num = intval($num / 26);
        }
        return $letter;
    }

    public function styles(Worksheet $sheet)
    {
        // Set header rows (rows 1-4) - sesuai format yang diminta
        $monthYear = $this->startDate->format('F Y');
        $sheet->setCellValue('A1', $monthYear);
        
        $sheet->setCellValue('A2', 'Mulai');
        // Format: 7/26/2025 (month/day/year tanpa leading zero)
        $startDateFormatted = $this->startDate->format('n/j/Y');
        $sheet->setCellValue('B2', $startDateFormatted);
        
        $sheet->setCellValue('A3', 'Selesai');
        // Format: 8/25/2025 (month/day/year tanpa leading zero)
        $endDateFormatted = $this->endDate->format('n/j/Y');
        $sheet->setCellValue('B3', $endDateFormatted);
        
        // Header text di kolom D
        $locationName = strtoupper($this->location->name);
        // Format: Juli2025 (bulan tanpa spasi + tahun)
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $monthYearShort = $monthNames[$this->startDate->month] . $this->startDate->format('Y'); // Juli2025
        $sheet->setCellValue('D2', 'DAFTAR ABSENSI KEBUN ' . $locationName . ' PT. SAIR NAPAOR COM BULAN : ' . $monthYearShort);
        
        $periodText = 'PRIODE TGL ' . $this->startDate->format('d M') . ' S/D ' . $this->endDate->format('d M Y');
        $sheet->setCellValue('D3', $periodText);

        // Build headings array to calculate last column
        $headings = ['ID', 'NAMA', 'Bagian'];
        $dayNames = ['Sab', 'Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum'];
        $currentDate = $this->startDate->copy();
        while ($currentDate <= $this->endDate) {
            $dayIndex = $currentDate->dayOfWeek;
            $dayIndex = ($dayIndex === 0) ? 1 : (($dayIndex === 6) ? 0 : $dayIndex + 1);
            $headings[] = $dayNames[$dayIndex];
            $currentDate->addDay();
        }
        $headings = array_merge($headings, ['Hari Kerja', 'Hadir', 'Persentase', 'Nilai HK', 'Estimasi Gaji', 'Jobdesk']);
        
        // Calculate lastCol using 1-based index (A = 1, B = 2, etc.)
        $lastCol = $this->getColumnLetter(count($headings));
        
        // Merge cells for header
        $sheet->mergeCells('D2:' . $lastCol . '2');
        $sheet->mergeCells('D3:' . $lastCol . '3');

        // Style header rows (rows 1-4) - FULL WIDTH sampai lastCol
        $sheet->getStyle('A1:' . $lastCol . '4')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        // Note: Headings, dates, and styling will be done in AfterSheet event to ensure proper order

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $dateCount = $this->startDate->diffInDays($this->endDate) + 1;
                $dataRowCount = User::where('location_id', $this->location->id)->where('role', 'employee')->count();
                
                // Build headings array
                $headings = ['ID', 'NAMA', 'Bagian'];
                $dayNames = ['Sab', 'Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum'];
                $currentDate = $this->startDate->copy();
                while ($currentDate <= $this->endDate) {
                    $dayIndex = $currentDate->dayOfWeek;
                    $dayIndex = ($dayIndex === 0) ? 1 : (($dayIndex === 6) ? 0 : $dayIndex + 1);
                    $headings[] = $dayNames[$dayIndex];
                    $currentDate->addDay();
                }
                $headings = array_merge($headings, ['Hari Kerja', 'Hadir', 'Persentase', 'Nilai HK', 'Estimasi Gaji', 'Jobdesk']);
                
                // Calculate lastCol using 1-based index (A = 1, B = 2, etc.)
                $lastCol = $this->getColumnLetter(count($headings));
                
                // STEP 1: Set headings in row 5 FIRST
                $colIndex = 1; // A = 1
                foreach ($headings as $heading) {
                    $col = $this->getColumnLetter($colIndex);
                    $sheet->setCellValue($col . '5', $heading);
                    $colIndex++;
                }
                
                // STEP 2: Set date numbers in row 6 IMMEDIATELY after headings
                $colIndex = 4; // D = 4
                $currentDate = $this->startDate->copy();
                while ($currentDate <= $this->endDate) {
                    $col = $this->getColumnLetter($colIndex);
                    $dateValue = (string) $currentDate->format('d');
                    $cell = $col . '6';
                    // Set date value explicitly as STRING
                    $sheet->setCellValueExplicit($cell, $dateValue, DataType::TYPE_STRING);
                    $currentDate->addDay();
                    $colIndex++;
                }
                
                // STEP 3: Merge cells (data is already at row 7, so no need to move)
                $sheet->mergeCells('A5:A6'); // ID
                $sheet->mergeCells('B5:B6'); // NAMA
                $sheet->mergeCells('C5:C6'); // Bagian
                
                // Merge summary columns
                $summaryStartColIndex = 4 + $dateCount; // D = 4, setelah kolom tanggal
                $summaryCols = ['Hari Kerja', 'Hadir', 'Persentase', 'Nilai HK', 'Estimasi Gaji', 'Jobdesk'];
                foreach ($summaryCols as $index => $colName) {
                    $colIndex = $summaryStartColIndex + $index;
                    $col = $this->getColumnLetter($colIndex);
                    $sheet->mergeCells($col . '5:' . $col . '6');
                }
                
                // STEP 4: RE-SET date numbers in row 6 AFTER merge to ensure they're preserved
                $colIndex = 4; // D = 4
                $currentDate = $this->startDate->copy();
                while ($currentDate <= $this->endDate) {
                    $col = $this->getColumnLetter($colIndex);
                    $dateValue = (string) $currentDate->format('d');
                    $cell = $col . '6';
                    $sheet->setCellValueExplicit($cell, $dateValue, DataType::TYPE_STRING);
                    $currentDate->addDay();
                    $colIndex++;
                }
                
                // STEP 5: Style row 5 and 6 - FULL WIDTH dengan background abu-abu sampai lastCol
                // Pastikan background abu-abu penuh tanpa area kosong
                $sheet->getStyle('A5:' . $lastCol . '6')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);
                
                // Style date row specifically (row 6) - pastikan font bold dan size 11
                $dateEndColIndex = 4 + $dateCount - 1; // D = 4, sampai kolom tanggal terakhir
                $dateEndCol = $this->getColumnLetter($dateEndColIndex);
                $sheet->getStyle('D6:' . $dateEndCol . '6')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 11],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E0E0E0'],
                    ],
                ]);
                
                if ($dataRowCount > 0) {
                    // Style all data rows (including last row) - ensure ALL rows get styled
                    $lastDataRow = 6 + $dataRowCount;
                    $sheet->getStyle('A7:' . $lastCol . $lastDataRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);
                    
                    // Style attendance cells as numeric (1/0 values)
                    // Ensure they are treated as numbers and can be edited by user
                    // IMPORTANT: Ensure 0 values are displayed (not empty)
                    $attendanceEndColIndex = 4 + $dateCount - 1; // D = 4, sampai kolom tanggal terakhir
                    $attendanceStartCol = $this->getColumnLetter(4); // D = 4
                    $attendanceEndCol = $this->getColumnLetter($attendanceEndColIndex);
                    
                    for ($row = 7; $row <= $lastDataRow; $row++) {
                        for ($colIdx = 4; $colIdx <= $attendanceEndColIndex; $colIdx++) { // D = 4
                            $col = $this->getColumnLetter($colIdx);
                            $cell = $col . $row;
                            
                            // Get current value
                            $cellValue = $sheet->getCell($cell)->getValue();
                            
                            // Ensure 0 is displayed (not empty/null)
                            // If cell is empty, null, or not numeric, set to 0
                            if ($cellValue === null || $cellValue === '' || !is_numeric($cellValue)) {
                                $sheet->setCellValueExplicit($cell, 0, DataType::TYPE_NUMERIC);
                            } else {
                                // Ensure it's stored as numeric
                                $sheet->setCellValueExplicit($cell, (int)$cellValue, DataType::TYPE_NUMERIC);
                            }
                            
                            // Ensure numeric format and center alignment
                            $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('0');
                            $sheet->getStyle($cell)->applyFromArray([
                                'alignment' => [
                                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                                    'vertical' => Alignment::VERTICAL_CENTER,
                                ],
                            ]);
                        }
                    }
                    
                    // STEP 6: Set Excel formulas for automatic calculation
                    // Calculate column indices for summary columns
                    $hariKerjaColIndex = $summaryStartColIndex + 0; // Index 0 = "Hari Kerja"
                    $hadirColIndex = $summaryStartColIndex + 1;     // Index 1 = "Hadir"
                    $persentaseColIndex = $summaryStartColIndex + 2; // Index 2 = "Persentase"
                    $nilaiHKColIndex = $summaryStartColIndex + 3;    // Index 3 = "Nilai HK"
                    $estimasiGajiColIndex = $summaryStartColIndex + 4; // Index 4 = "Estimasi Gaji"
                    $jobdeskColIndex = $summaryStartColIndex + 5;    // Index 5 = "Jobdesk"
                    
                    $hariKerjaCol = $this->getColumnLetter($hariKerjaColIndex);
                    $hadirCol = $this->getColumnLetter($hadirColIndex);
                    $persentaseCol = $this->getColumnLetter($persentaseColIndex);
                    $nilaiHKCol = $this->getColumnLetter($nilaiHKColIndex);
                    $estimasiGajiCol = $this->getColumnLetter($estimasiGajiColIndex);
                    $jobdeskCol = $this->getColumnLetter($jobdeskColIndex);
                    
                    // Set formulas for each data row
                    for ($row = 7; $row <= $lastDataRow; $row++) {
                        // Get user_id from column A (index 0) to check payroll data
                        $userIdCell = 'A' . $row;
                        $userId = (int) $sheet->getCell($userIdCell)->getValue();
                        
                        // Check if payroll exists and is approved - use present_days directly
                        $usePresentDays = false;
                        $presentDaysValue = null;
                        if (isset($this->payrollData[$userId]) && $this->payrollData[$userId]['status'] === 'approved') {
                            $usePresentDays = true;
                            $presentDaysValue = $this->payrollData[$userId]['present_days'];
                        }
                        
                        // Formula 1: Hadir
                        // Jika payroll sudah approved, gunakan nilai present_days langsung (bukan formula)
                        // Jika tidak, gunakan formula SUM dari attendance columns
                        $hadirCell = $hadirCol . $row;
                        if ($usePresentDays && $presentDaysValue !== null) {
                            // Gunakan nilai present_days yang sudah dikoreksi
                            $sheet->setCellValue($hadirCell, $presentDaysValue);
                        } else {
                            // Gunakan formula SUM dari attendance columns
                            $attendanceStartCell = $attendanceStartCol . $row;
                            $attendanceEndCell = $attendanceEndCol . $row;
                            $hadirFormula = "=SUM({$attendanceStartCell}:{$attendanceEndCell})";
                            $sheet->setCellValue($hadirCell, $hadirFormula);
                        }
                        
                        // Formula 2: Persentase = (Hadir / Hari Kerja) * 100
                        // Handle division by zero: IF(HariKerja=0, 0, (Hadir/HariKerja)*100)
                        $persentaseCell = $persentaseCol . $row;
                        $hariKerjaCell = $hariKerjaCol . $row;
                        $persentaseFormula = "=IF({$hariKerjaCell}=0, 0, ({$hadirCell}/{$hariKerjaCell})*100)";
                        $sheet->setCellValue($persentaseCell, $persentaseFormula);
                        
                        // Formula 3: Estimasi Gaji = Nilai HK × Hadir
                        $estimasiGajiCell = $estimasiGajiCol . $row;
                        $nilaiHKCell = $nilaiHKCol . $row;
                        // Formula: =NilaiHK*Hadir
                        $estimasiGajiFormula = "={$nilaiHKCell}*{$hadirCell}";
                        $sheet->setCellValue($estimasiGajiCell, $estimasiGajiFormula);
                    }
                    
                    // Format cells to display properly
                    // Persentase column: format as percentage with 0 decimal places
                    $sheet->getStyle($persentaseCol . '7:' . $persentaseCol . $lastDataRow)->applyFromArray([
                        'numberFormat' => ['formatCode' => '0"%"'],
                    ]);
                    
                    // Nilai HK column: format as number with thousand separator (Indonesian format: dot as thousand separator)
                    $sheet->getStyle($nilaiHKCol . '7:' . $nilaiHKCol . $lastDataRow)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                    ]);
                    
                    // Estimasi Gaji column: format as number with thousand separator
                    $sheet->getStyle($estimasiGajiCol . '7:' . $estimasiGajiCol . $lastDataRow)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                    ]);
                    
                    // STEP 7: Add Total row at the bottom
                    $totalRow = $lastDataRow + 1;
                    
                    // Merge cells for "TOTAL" label (spanning ID, NAMA, Bagian columns and date columns)
                    $totalLabelStartCol = 'A';
                    $totalLabelEndCol = $this->getColumnLetter($summaryStartColIndex - 1); // End before summary columns
                    $sheet->mergeCells($totalLabelStartCol . $totalRow . ':' . $totalLabelEndCol . $totalRow);
                    $sheet->setCellValue($totalLabelStartCol . $totalRow, 'TOTAL');
                    
                    // Add SUM formulas for relevant columns
                    // Hari Kerja: SUM
                    $hariKerjaTotalCell = $hariKerjaCol . $totalRow;
                    $sheet->setCellValue($hariKerjaTotalCell, "=SUM({$hariKerjaCol}7:{$hariKerjaCol}{$lastDataRow})");
                    
                    // Hadir: SUM
                    $hadirTotalCell = $hadirCol . $totalRow;
                    $sheet->setCellValue($hadirTotalCell, "=SUM({$hadirCol}7:{$hadirCol}{$lastDataRow})");
                    
                    // Persentase: Average (or leave empty, as percentage total doesn't make sense)
                    $persentaseTotalCell = $persentaseCol . $totalRow;
                    $sheet->setCellValue($persentaseTotalCell, ""); // Leave empty for percentage
                    
                    // Nilai HK: Leave empty (average doesn't make sense here)
                    $nilaiHKTotalCell = $nilaiHKCol . $totalRow;
                    $sheet->setCellValue($nilaiHKTotalCell, "");
                    
                    // Estimasi Gaji: SUM (this is the main total we need)
                    $estimasiGajiTotalCell = $estimasiGajiCol . $totalRow;
                    $sheet->setCellValue($estimasiGajiTotalCell, "=SUM({$estimasiGajiCol}7:{$estimasiGajiCol}{$lastDataRow})");
                    
                    // Jobdesk: Leave empty
                    $jobdeskTotalCell = $jobdeskCol . $totalRow;
                    $sheet->setCellValue($jobdeskTotalCell, "");
                    
                    // Style the total row
                    $sheet->getStyle($totalLabelStartCol . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D3D3D3'], // Light grey background
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                    ]);
                    
                    // Format total row number cells
                    $sheet->getStyle($hariKerjaTotalCell)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                    ]);
                    $sheet->getStyle($hadirTotalCell)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                    ]);
                    $sheet->getStyle($estimasiGajiTotalCell)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                    ]);
                    
                    // Add a summary row below total with "Total Estimasi Gaji yang harus dibayarkan"
                    $summaryRow = $totalRow + 1;
                    
                    // Merge cells for the summary label
                    $summaryLabelEndCol = $this->getColumnLetter($estimasiGajiColIndex - 1); // End before Estimasi Gaji column
                    $sheet->mergeCells($totalLabelStartCol . $summaryRow . ':' . $summaryLabelEndCol . $summaryRow);
                    $sheet->setCellValue($totalLabelStartCol . $summaryRow, 'Total Estimasi Gaji yang harus dibayarkan:');
                    
                    // Set the total value (reference to the total cell above)
                    $summaryValueCell = $estimasiGajiCol . $summaryRow;
                    $sheet->setCellValue($summaryValueCell, "={$estimasiGajiTotalCell}");
                    
                    // Style the summary row
                    $sheet->getStyle($totalLabelStartCol . $summaryRow . ':' . $lastCol . $summaryRow)->applyFromArray([
                        'font' => ['bold' => true, 'size' => 12],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'B0B0B0'], // Darker grey background
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_MEDIUM,
                            ],
                        ],
                    ]);
                    
                    // Format the summary value cell
                    $sheet->getStyle($summaryValueCell)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);
                }
            },
        ];
    }
}

