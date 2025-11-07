<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget as BaseAccountWidget;

class CustomAccountWidget extends BaseAccountWidget
{
    protected static ?string $heading = 'Akun Saya';

    protected static ?int $sort = 5;
}
