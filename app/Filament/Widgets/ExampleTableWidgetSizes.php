<?php

namespace App\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Contoh Table Widget untuk menunjukkan berbagai opsi ukuran columnSpan
 * File ini hanya sebagai referensi pembelajaran, tidak digunakan di aplikasi
 * 
 * CATATAN: Table Widget menggunakan $columnSpan yang SAMA PERSIS dengan Chart Widget
 */
class ExampleTableWidgetSizes extends BaseWidget
{
    // ============================================
    // OPSI 1: FULL WIDTH (100% lebar)
    // ============================================
    protected int|string|array $columnSpan = 'full';

    // ============================================
    // OPSI 2: BERDASARKAN ANGKA
    // ============================================
    
    // SAMA PERSIS dengan Chart Widget!
    // Jika grid = 3 kolom (xl), maka:
    // $columnSpan = 1  → 1 kolom = 33.33% lebar
    // $columnSpan = 2  → 2 kolom = 66.67% lebar
    
    // Contoh: Setengah lebar
    // protected int|string|array $columnSpan = 1;
    
    // Contoh: Dua pertiga lebar
    // protected int|string|array $columnSpan = 2;

    // ============================================
    // OPSI 3: RESPONSIF
    // ============================================
    
    // SAMA PERSIS dengan Chart Widget!
    // protected int|string|array $columnSpan = [
    //     'sm' => 'full',  // Mobile: full width
    //     'md' => 1,       // Tablet: 1 dari 2 kolom = 50%
    //     'xl' => 1,       // Desktop: 1 dari 3 kolom = 33.33%
    // ];

    public function table(Table $table): Table
    {
        return $table
            ->query(/* your query */)
            ->columns([
                // your columns
            ]);
    }
}

