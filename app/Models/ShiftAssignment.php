<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'shift_id',
        'date',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Relationship: ShiftAssignment belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship: ShiftAssignment belongs to ShiftKerja
     */
    public function shift()
    {
        return $this->belongsTo(ShiftKerja::class, 'shift_id');
    }

    /**
     * Scope: Get assignments for a specific date
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon|string $date Date in location timezone (Carbon instance or date string Y-m-d)
     */
    public function scopeForDate($query, $date)
    {
        // ✅ Ensure we use date string to avoid timezone issues
        // whereDate() will compare date part only, which is what we want
        if ($date instanceof \Carbon\Carbon) {
            // Use toDateString() to get date in the Carbon instance's timezone
            return $query->whereDate('date', $date->toDateString());
        }
        
        // If it's already a string, use it directly
        return $query->whereDate('date', $date);
    }

    /**
     * Scope: Get assignments for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get scheduled assignments only
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }
}
