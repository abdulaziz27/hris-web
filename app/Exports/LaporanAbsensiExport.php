<?php

namespace App\Exports;

use App\Models\Attendance;
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

class LaporanAbsensiExport implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles, WithEvents
{
    protected $attendances;

    public function __construct($attendances)
    {
        $this->attendances = $attendances;
    }

    public function collection()
    {
        $data = collect();

        foreach ($this->attendances as $index => $attendance) {
            $timeIn = $attendance->time_in ? Carbon::parse($attendance->time_in)->format('H:i') : '-';
            $timeOut = $attendance->time_out ? Carbon::parse($attendance->time_out)->format('H:i') : '-';

            $workingHours = '-';
            if ($attendance->time_in && $attendance->time_out) {
                $diff = Carbon::parse($attendance->time_in)->diff(Carbon::parse($attendance->time_out));
                $workingHours = sprintf('%d jam %d menit', $diff->h, $diff->i);
            }

            // Status kehadiran (berdasarkan ada/tidaknya check-in/out)
            $status = 'Hadir';
            if (! $attendance->time_in) {
                $status = 'Tidak Masuk';
            } elseif (! $attendance->time_out) {
                $status = 'Belum Pulang';
            }

            // Status ketepatan waktu (berdasarkan field status di database)
            $punctualStatus = match ($attendance->status) {
                'on_time' => 'Tepat Waktu',
                'late' => 'Terlambat',
                'absent' => 'Tidak Hadir',
                default => ucfirst(str_replace('_', ' ', (string) $attendance->status)),
            };

            $data->push([
                $index + 1,
                $attendance->user->name ?? '-',
                $attendance->location->name ?? '-',
                $attendance->user->position ?? '-',
                $attendance->user->department ?? '-',
                Carbon::parse($attendance->date)->format('d/m/Y'),
                $timeIn,
                $timeOut,
                $workingHours,
                $status,
                $punctualStatus,
            ]);
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama Karyawan',
            'Lokasi',
            'Jabatan',
            'Departemen',
            'Tanggal',
            'Jam Masuk',
            'Jam Keluar',
            'Jam Kerja',
            'Status',
            'Status Ketepatan',
        ];
    }

    public function title(): string
    {
        return 'Laporan Absensi';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // No
            'B' => 25,  // Nama Karyawan
            'C' => 20,  // Lokasi
            'D' => 20,  // Jabatan
            'E' => 20,  // Departemen
            'F' => 15,  // Tanggal
            'G' => 12,  // Jam Masuk
            'H' => 12,  // Jam Keluar
            'I' => 15,  // Jam Kerja
            'J' => 15,  // Status
            'K' => 18,  // Status Ketepatan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $this->attendances->count() + 1; // +1 for header
        $lastCol = 'K';

        // Style header row
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Style data rows
        if ($lastRow > 1) {
            $sheet->getStyle('A2:' . $lastCol . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Left align for text columns (Nama Karyawan, Lokasi, Jabatan, Departemen)
            $sheet->getStyle('B2:E' . $lastRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                ],
            ]);
        }

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->attendances->count() + 1;

                if ($lastRow > 1) {
                    // Apply status badge colors
                    for ($row = 2; $row <= $lastRow; $row++) {
                        $statusCell = 'J' . $row;
                        $status = $sheet->getCell($statusCell)->getValue();

                        $bgColor = match($status) {
                            'Hadir' => 'C6EFCE',        // Light green
                            'Belum Pulang' => 'FFEB9C', // Light yellow
                            'Tidak Masuk' => 'FFC7CE',  // Light red
                            default => 'FFFFFF'
                        };

                        $sheet->getStyle($statusCell)->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $bgColor],
                            ],
                            'font' => ['bold' => true],
                        ]);
                    }
                }
            },
        ];
    }
}

