<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leave extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'attachment_url',
        'status',
        'approved_by',
        'approved_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Mutator to ensure start_date is stored as date-only (no timezone conversion)
     */
    public function setStartDateAttribute($value)
    {
        if ($value) {
            // If it's a Carbon instance, convert to date string
            if ($value instanceof \Carbon\Carbon) {
                $this->attributes['start_date'] = $value->format('Y-m-d');
            } else {
                // If it's a string, parse it as date-only in app timezone
                $date = \Carbon\Carbon::createFromFormat('Y-m-d', $value, config('app.timezone'));
                $this->attributes['start_date'] = $date->format('Y-m-d');
            }
        } else {
            $this->attributes['start_date'] = null;
        }
    }

    /**
     * Mutator to ensure end_date is stored as date-only (no timezone conversion)
     */
    public function setEndDateAttribute($value)
    {
        if ($value) {
            // If it's a Carbon instance, convert to date string
            if ($value instanceof \Carbon\Carbon) {
                $this->attributes['end_date'] = $value->format('Y-m-d');
            } else {
                // If it's a string, parse it as date-only in app timezone
                $date = \Carbon\Carbon::createFromFormat('Y-m-d', $value, config('app.timezone'));
                $this->attributes['end_date'] = $date->format('Y-m-d');
            }
        } else {
            $this->attributes['end_date'] = null;
        }
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
