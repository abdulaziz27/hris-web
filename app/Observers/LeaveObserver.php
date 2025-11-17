<?php

namespace App\Observers;

use App\Models\Leave;
use App\Models\Payroll;
use App\Services\PayrollCalculator;
use Carbon\Carbon;

class LeaveObserver
{
    /**
     * Handle the Leave "updated" event.
     * Update payroll when leave status changes (especially when approved)
     */
    public function updated(Leave $leave): void
    {
        // Only process if leave status changed to approved
        if ($leave->status === 'approved' && $leave->wasChanged('status')) {
            $this->updatePayrollFromLeave($leave);
        }
    }

    /**
     * Update payroll based on leave data
     * Only updates if payroll status is 'draft'
     */
    protected function updatePayrollFromLeave(Leave $leave): void
    {
        try {
            \Log::info("LeaveObserver triggered", [
                'leave_id' => $leave->id,
                'employee_id' => $leave->employee_id,
                'status' => $leave->status,
                'start_date' => $leave->start_date,
                'end_date' => $leave->end_date,
            ]);

            // Only process if leave is approved and has paid leave type
            if ($leave->status !== 'approved') {
                return;
            }

            // Check if leave type is paid
            if (!$leave->leaveType || !$leave->leaveType->is_paid) {
                \Log::info("LeaveObserver skipped: leave is not paid", [
                    'leave_id' => $leave->id,
                    'leave_type_id' => $leave->leave_type_id,
                ]);
                return;
            }

            // Get all periods (months) that this leave covers
            $startDate = Carbon::parse($leave->start_date);
            $endDate = Carbon::parse($leave->end_date);
            
            // Process each month that the leave covers
            $current = $startDate->copy()->startOfMonth();
            $endMonth = $endDate->copy()->startOfMonth();

            while ($current->lte($endMonth)) {
                $period = $current->copy();

                // Find payroll for this user and period
                $payroll = Payroll::where('user_id', $leave->employee_id)
                    ->where('period', $period->toDateString())
                    ->first();

                if ($payroll) {
                    // Only update if payroll is still in draft status
                    if ($payroll->status === 'draft') {
                        $this->recalculatePayroll($payroll);
                    } else {
                        \Log::warning("LeaveObserver: Payroll not updated (status is not draft)", [
                            'payroll_id' => $payroll->id,
                            'status' => $payroll->status,
                            'leave_id' => $leave->id,
                        ]);
                    }
                } else {
                    // Payroll doesn't exist yet - it will be created when attendance is recorded
                    // or when payroll is manually generated
                    \Log::info("LeaveObserver: Payroll not found for period", [
                        'employee_id' => $leave->employee_id,
                        'period' => $period->toDateString(),
                        'leave_id' => $leave->id,
                    ]);
                }

                // Move to next month
                $current->addMonth();
            }
        } catch (\Exception $e) {
            // Log error but don't break the leave update
            \Log::error("Failed to update payroll from leave: " . $e->getMessage(), [
                'leave_id' => $leave->id,
                'employee_id' => $leave->employee_id,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Recalculate payroll with updated present days (including paid leave)
     */
    protected function recalculatePayroll(Payroll $payroll): void
    {
        try {
            $period = Carbon::parse($payroll->period);
            $startDate = $period->copy()->startOfMonth();
            $endDate = $period->copy()->endOfMonth();
            
            $user = \App\Models\User::find($payroll->user_id);
            
            // Recalculate effective present days (attendance + paid leave)
            $presentDays = PayrollCalculator::calculateEffectivePresentDays(
                $payroll->user_id,
                $startDate,
                $endDate,
                $user->location_id ?? null
            );

            // Update present_days
            $payroll->present_days = $presentDays;

            // Calculate percentage
            if ($payroll->standard_workdays > 0) {
                $payroll->percentage = round(($presentDays / $payroll->standard_workdays) * 100, 2);
            } else {
                $payroll->percentage = 0;
            }

            // Recalculate salary fields if nilai_hk is available
            if ($payroll->nilai_hk > 0) {
                // Estimated salary = nilai_hk × present_days
                $payroll->estimated_salary = round($payroll->nilai_hk * $presentDays, 2);

                // Final salary = estimated_salary (langsung dari present_days)
                $hkReview = $payroll->hk_review ?? $presentDays;
                $payroll->final_salary = round($payroll->nilai_hk * $hkReview, 2);

                // Calculate selisih HK
                $payroll->selisih_hk = $hkReview - $payroll->standard_workdays;
            } else {
                // If nilai_hk is not set, set salary fields to 0
                $payroll->estimated_salary = 0;
                $payroll->final_salary = 0;
                $payroll->selisih_hk = 0;
            }

            $payroll->save();

            \Log::info("LeaveObserver: Payroll recalculated", [
                'payroll_id' => $payroll->id,
                'user_id' => $payroll->user_id,
                'present_days' => $payroll->present_days,
                'percentage' => $payroll->percentage,
                'estimated_salary' => $payroll->estimated_salary,
            ]);
        } catch (\Exception $e) {
            \Log::error("LeaveObserver: Failed to recalculate payroll", [
                'payroll_id' => $payroll->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

