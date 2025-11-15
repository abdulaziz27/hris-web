<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Generate payroll otomatis setiap tanggal 1 setiap bulan (pukul 00:00)
Schedule::command('payroll:generate-monthly')
    ->monthlyOn(1, '00:00')
    ->description('Generate payroll otomatis untuk bulan baru')
    ->withoutOverlapping();
