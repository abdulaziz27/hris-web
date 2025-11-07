<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

/**
 * Contoh widget untuk menunjukkan berbagai opsi ukuran columnSpan
 * File ini hanya sebagai referensi pembelajaran, tidak digunakan di aplikasi
 */
class ExampleWidgetSizes extends ChartWidget
{
    // ============================================
    // OPSI 1: FULL WIDTH (100% lebar)
    // ============================================
    protected int|string|array $columnSpan = 'full';

    // ============================================
    // OPSI 2: BERDASARKAN ANGKA (berdasarkan grid)
    // ============================================
    
    // Jika grid = 3 kolom (xl), maka:
    // $columnSpan = 1  → mengambil 1 kolom = 33.33% lebar
    // $columnSpan = 2  → mengambil 2 kolom = 66.67% lebar
    // $columnSpan = 3  → mengambil 3 kolom = 100% lebar (sama dengan 'full')
    
    // Contoh: Setengah lebar di grid 2 kolom
    // protected int|string|array $columnSpan = 1; // 50% lebar
    
    // Contoh: Sepertiga lebar di grid 3 kolom
    // protected int|string|array $columnSpan = 1; // 33.33% lebar
    
    // Contoh: Dua pertiga lebar di grid 3 kolom
    // protected int|string|array $columnSpan = 2; // 66.67% lebar

    // ============================================
    // OPSI 3: RESPONSIF (berbeda per breakpoint)
    // ============================================
    
    // Contoh: Full width di mobile, setengah di tablet, sepertiga di desktop
    // protected int|string|array $columnSpan = [
    //     'sm' => 'full',  // Mobile: full width
    //     'md' => 1,       // Tablet: 1 dari 2 kolom = 50%
    //     'xl' => 1,       // Desktop: 1 dari 3 kolom = 33.33%
    // ];

    // Contoh: Selalu setengah lebar di semua ukuran
    // protected int|string|array $columnSpan = [
    //     'md' => 1,  // 1 dari 2 kolom = 50%
    //     'xl' => 1,  // 1 dari 3 kolom = 33.33% (tapi tampil lebih besar)
    // ];

    protected function getData(): array
    {
        return [
            'datasets' => [],
            'labels' => [],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

