<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class LaporanPenggajianExport implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles, WithEvents
{
    protected array $payrollData;
    protected Carbon $period;
    protected Carbon $start;
    protected Carbon $end;
    protected int $standardWorkdays;
    protected ?int $locationId;
    protected ?string $locationName;

    public function __construct(array $payrollData, Carbon $period, Carbon $start, Carbon $end, int $standardWorkdays, ?int $locationId = null, ?string $locationName = null)
    {
        $this->payrollData = $payrollData;
        $this->period = $period;
        $this->start = $start;
        $this->end = $end;
        $this->standardWorkdays = $standardWorkdays;
        $this->locationId = $locationId;
        $this->locationName = $locationName;
    }

    public function collection()
    {
        $data = collect();
        
        // Generate all dates in month
        $dates = [];
        $current = $this->start->copy();
        while ($current <= $this->end) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        foreach ($this->payrollData as $payroll) {
            $user = $payroll['user'];
            $dataItem = $payroll['data'];
            $dailyStatus = $payroll['daily_status'];

            $row = [
                $user->id,
                $user->name,
                $user->departemen->name ?? '-',
                '', // Foto profil - akan diinsert via Drawing di AfterSheet
            ];

            // Add daily status for each date
            foreach ($dates as $date) {
                $dateKey = $date->format('Y-m-d');
                $status = $dailyStatus[$dateKey] ?? 'A';
                $row[] = $status;
            }

            // Add summary columns (store as numbers for proper Excel formatting)
            $row[] = $dataItem['standard_workdays'];
            $row[] = $dataItem['present_days'];
            $row[] = $dataItem['percentage']; // Store as number, will be formatted as percentage in Excel
            $row[] = $dataItem['nilai_hk'];
            $row[] = $dataItem['estimated_salary'];
            $row[] = $dataItem['hk_review'];
            $row[] = $dataItem['selisih_hk'];

            $data->push($row);
        }

        return $data;
    }

    public function headings(): array
    {
        $headings = ['ID', 'NAMA', 'Bagian', 'Foto'];

        // Add date columns
        $current = $this->start->copy();
        while ($current <= $this->end) {
            $headings[] = $current->format('d');
            $current->addDay();
        }

        // Add summary columns
        $headings = array_merge($headings, [
            'Hari Kerja',
            'Hadir',
            'Persentase',
            'Nilai HK',
            'Estimasi Gaji',
            'HK Review',
            'Selisih HK',
        ]);

        return $headings;
    }

    public function title(): string
    {
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        $title = 'Laporan Penggajian - ' . $monthNames[$this->period->month] . ' ' . $this->period->year;
        
        if ($this->locationName) {
            $title .= ' - ' . $this->locationName;
        }

        // Limit to 31 characters (Excel sheet name limit)
        return mb_substr($title, 0, 31);
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 8,  // ID
            'B' => 25, // NAMA
            'C' => 20, // Bagian
            'D' => 15, // Foto
        ];

        // Date columns
        $dateCount = $this->start->diffInDays($this->end) + 1;
        for ($i = 0; $i < $dateCount; $i++) {
            $col = $this->getColumnLetter(5 + $i); // E = 5 (setelah Foto)
            $widths[$col] = 6;
        }

        // Summary columns
        $summaryStartIndex = 4 + $dateCount;
        $summaryCols = ['Hari Kerja', 'Hadir', 'Persentase', 'Nilai HK', 'Estimasi Gaji', 'HK Review', 'Selisih HK'];
        foreach ($summaryCols as $index => $colName) {
            $col = $this->getColumnLetter($summaryStartIndex + $index);
            $widths[$col] = 15;
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
        // Set header information
        $monthNames = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $sheet->setCellValue('A1', 'LAPORAN PENGAJIAN');
        $sheet->setCellValue('A2', 'Periode: ' . $monthNames[$this->period->month] . ' ' . $this->period->year);
        
        if ($this->locationName) {
            $sheet->setCellValue('A3', 'Lokasi: ' . $this->locationName);
        }

        $dateCount = $this->start->diffInDays($this->end) + 1;
        $summaryCols = ['Hari Kerja', 'Hadir', 'Persentase', 'Nilai HK', 'Estimasi Gaji', 'HK Review', 'Selisih HK'];
        $totalCols = 4 + $dateCount + count($summaryCols); // 4 = ID, NAMA, Bagian, Foto
        $lastCol = $this->getColumnLetter($totalCols);

        // Merge header cells
        $headerRow = $this->locationName ? 4 : 3;
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->mergeCells('A2:' . $lastCol . '2');
        if ($this->locationName) {
            $sheet->mergeCells('A3:' . $lastCol . '3');
        }

        // Style header rows
        $headerEndRow = $this->locationName ? 3 : 2;
        $sheet->getStyle('A1:' . $lastCol . $headerEndRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                $dateCount = $this->start->diffInDays($this->end) + 1;
                $dataRowCount = count($this->payrollData);
                $summaryCols = ['Hari Kerja', 'Hadir', 'Persentase', 'Nilai HK', 'Estimasi Gaji', 'HK Review', 'Selisih HK'];
                $totalCols = 4 + $dateCount + count($summaryCols); // 4 = ID, NAMA, Bagian, Foto
                $lastCol = $this->getColumnLetter($totalCols);

                // Determine header row (where headings start)
                $headerRow = $this->locationName ? 5 : 4;

                // Style headings row
                $sheet->getStyle('A' . $headerRow . ':' . $lastCol . $headerRow)->applyFromArray([
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

                if ($dataRowCount > 0) {
                    // Style data rows
                    $lastDataRow = $headerRow + $dataRowCount;
                    $sheet->getStyle('A' . ($headerRow + 1) . ':' . $lastCol . $lastDataRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                            ],
                        ],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    ]);

                    // Insert profile photos
                    $fotoCol = 'D';
                    $rowIndex = $headerRow + 1;
                    foreach ($this->payrollData as $payroll) {
                        $user = $payroll['user'];
                        $imageUrl = $user->getRawOriginal('image_url');
                        
                        if ($imageUrl && file_exists(storage_path('app/public/' . $imageUrl))) {
                            $drawing = new Drawing();
                            $drawing->setName($user->name);
                            $drawing->setDescription('Foto Profil ' . $user->name);
                            $drawing->setPath(storage_path('app/public/' . $imageUrl));
                            $drawing->setHeight(60); // Set height to 60 pixels
                            $drawing->setWidth(60); // Set width to 60 pixels
                            $drawing->setCoordinates($fotoCol . $rowIndex);
                            $drawing->setOffsetX(5);
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);
                            
                            // Set row height to accommodate image
                            $sheet->getRowDimension($rowIndex)->setRowHeight(60);
                        }
                        
                        $rowIndex++;
                    }
                    
                    // Style daily status cells (date columns)
                    $dateStartCol = 'E'; // E = 5 (setelah Foto)
                    $dateEndCol = $this->getColumnLetter(4 + $dateCount); // 4 = setelah ID, NAMA, Bagian, Foto
                    for ($row = $headerRow + 1; $row <= $lastDataRow; $row++) {
                        for ($colIdx = 5; $colIdx <= 4 + $dateCount; $colIdx++) { // 5 = E
                            $col = $this->getColumnLetter($colIdx);
                            $cell = $col . $row;
                            $value = $sheet->getCell($cell)->getValue();
                            
                            // Apply background color based on status
                            $bgColor = match($value) {
                                'H' => 'C6EFCE', // Light green
                                'A' => 'FFC7CE', // Light red
                                'L' => 'BDD7EE', // Light blue
                                'W' => 'FFEB9C', // Light yellow
                                default => 'FFFFFF'
                            };
                            
                            $sheet->getStyle($cell)->applyFromArray([
                                'fill' => [
                                    'fillType' => Fill::FILL_SOLID,
                                    'startColor' => ['rgb' => $bgColor],
                                ],
                                'font' => ['bold' => true],
                            ]);
                        }
                    }

                    // Format number columns
                    $summaryStartIndex = 5 + $dateCount; // 5 = setelah ID, NAMA, Bagian, Foto, dan tanggal
                    $persentaseCol = $this->getColumnLetter($summaryStartIndex + 2);
                    $nilaiHKCol = $this->getColumnLetter($summaryStartIndex + 3);
                    $estimasiGajiCol = $this->getColumnLetter($summaryStartIndex + 4);
                    
                    // Format Persentase as percentage
                    $sheet->getStyle($persentaseCol . ($headerRow + 1) . ':' . $persentaseCol . $lastDataRow)->applyFromArray([
                        'numberFormat' => ['formatCode' => '0.00"%"'],
                    ]);
                    
                    // Format Nilai HK and Estimasi Gaji as number with thousand separator
                    $sheet->getStyle($nilaiHKCol . ($headerRow + 1) . ':' . $nilaiHKCol . $lastDataRow)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                    ]);
                    
                    $sheet->getStyle($estimasiGajiCol . ($headerRow + 1) . ':' . $estimasiGajiCol . $lastDataRow)->applyFromArray([
                        'numberFormat' => ['formatCode' => '#,##0'],
                    ]);
                }
            },
        ];
    }
}

