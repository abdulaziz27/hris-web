<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftKerja extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'description',
        'is_cross_day',
        'grace_period_minutes',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_cross_day' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * One-to-many relationship: Users assigned to this shift (using shift_kerja_id)
     * This is the primary relationship used in the system
     */
    public function users()
    {
        return $this->hasMany(User::class, 'shift_kerja_id');
    }

    /**
     * Many-to-many relationship via pivot table (deprecated, kept for backward compatibility)
     * Note: Pivot table is no longer populated, use users() relationship instead
     */
    public function usersViaPivot()
    {
        return $this->belongsToMany(User::class, 'shift_kerja_user');
    }
}
