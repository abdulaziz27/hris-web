<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period',
        'standard_workdays',
        'present_days',
        'hk_review',
        'nilai_hk',
        'basic_salary',
        'estimated_salary',
        'final_salary',
        'selisih_hk',
        'percentage',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'standard_workdays' => 'integer',
            'present_days' => 'integer',
            'hk_review' => 'integer',
            'nilai_hk' => 'decimal:2',
            'basic_salary' => 'decimal:2',
            'estimated_salary' => 'decimal:2',
            'final_salary' => 'decimal:2',
            'selisih_hk' => 'integer',
            'percentage' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Auto-calculate fields when saving payroll
     */
    protected static function booted(): void
    {
        static::saving(function (Payroll $payroll) {
            try {
                // Always calculate percentage (data faktual)
                if ($payroll->present_days !== null && $payroll->standard_workdays !== null && $payroll->standard_workdays > 0) {
                    $payroll->percentage = round(($payroll->present_days / $payroll->standard_workdays) * 100, 2);
            }

                // Only calculate salary fields if status is draft (to preserve approved/paid payrolls)
                if ($payroll->status === 'draft' || !$payroll->exists) {
                    // Calculate selisih_hk = hk_review - present_days
                    if ($payroll->hk_review !== null && $payroll->present_days !== null) {
                        $payroll->selisih_hk = $payroll->hk_review - $payroll->present_days;
            }

            // Calculate estimated_salary = nilai_hk * present_days
                    if ($payroll->nilai_hk !== null && $payroll->nilai_hk > 0 && $payroll->present_days !== null) {
                        $payroll->estimated_salary = round($payroll->nilai_hk * $payroll->present_days, 2);
            }

                    // Calculate final_salary = nilai_hk * hk_review (prioritas hk_review)
                    if ($payroll->nilai_hk !== null && $payroll->nilai_hk > 0) {
                        if ($payroll->hk_review !== null && $payroll->hk_review > 0) {
                            $payroll->final_salary = round($payroll->nilai_hk * $payroll->hk_review, 2);
                        } elseif ($payroll->estimated_salary !== null) {
                            // If no hk_review, use estimated_salary
                    $payroll->final_salary = $payroll->estimated_salary;
                }
            }

                    // Calculate basic_salary = nilai_hk * standard_workdays (untuk informasi)
                    if ($payroll->nilai_hk !== null && $payroll->nilai_hk > 0 && $payroll->standard_workdays !== null && $payroll->standard_workdays > 0) {
                        $payroll->basic_salary = round($payroll->nilai_hk * $payroll->standard_workdays, 2);
                    }

                    // Set default hk_review = present_days if null (only for new records)
                    if ($payroll->hk_review === null && $payroll->present_days !== null && ! $payroll->exists) {
                $payroll->hk_review = $payroll->present_days;
                    }
                }
                // If status is approved/paid, don't recalculate salary fields (preserve existing values)
            } catch (\Exception $e) {
                // Log error but don't break save
                \Log::error('Error in Payroll booted method: ' . $e->getMessage());
            }
        });
    }
}
