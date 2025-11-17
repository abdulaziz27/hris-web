<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_km',
        'is_active',
        'attendance_type',
        'address',
        'description',
        'default_salary',
        'nilai_hk',
        'timezone',
        'weekend_pattern',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'default_salary' => 'decimal:2',
            'nilai_hk' => 'decimal:2',
            'weekend_pattern' => 'array',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
