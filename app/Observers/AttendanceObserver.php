<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Payroll;
use App\Services\PayrollCalculator;
use Carbon\Carbon;

class AttendanceObserver
{
    /**
     * Handle the Attendance "created" event.
     * Update payroll when new attendance is created
     */
    public function created(Attendance $attendance): void
    {
        $this->updatePayrollFromAttendance($attendance);
    }

    /**
     * Handle the Attendance "updated" event.
     * Update payroll when attendance is updated (e.g., time_out added)
     */
    public function updated(Attendance $attendance): void
    {
        $this->updatePayrollFromAttendance($attendance);
    }

    /**
     * Handle the Attendance "deleted" event.
     * Update payroll when attendance is deleted
     */
    public function deleted(Attendance $attendance): void
    {
        $this->updatePayrollFromAttendance($attendance);
    }

    /**
     * Update payroll based on attendance data
     */
    protected function updatePayrollFromAttendance(Attendance $attendance): void
    {
        try {
            \Log::info("AttendanceObserver triggered", [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'date' => $attendance->date,
            ]);

            // Only process if attendance has user_id and date
            if (!$attendance->user_id || !$attendance->date) {
                \Log::warning("AttendanceObserver skipped: missing user_id or date", [
                    'attendance_id' => $attendance->id,
                    'user_id' => $attendance->user_id,
                    'date' => $attendance->date,
                ]);
                return;
            }

            // Get the period (month) for this attendance
            // ✅ Parse date as date-only in app timezone to avoid timezone shift
            $dateStr = $attendance->date instanceof \Carbon\Carbon 
                ? $attendance->date->format('Y-m-d') 
                : $attendance->date;
            $period = Carbon::createFromFormat('Y-m-d', $dateStr, config('app.timezone'))->startOfMonth();

            // Find or create payroll for this user and period
            $payroll = Payroll::firstOrNew([
                'user_id' => $attendance->user_id,
                'period' => $period->toDateString(),
            ]);

            // If payroll doesn't exist yet, create it with initial data
            if (!$payroll->exists) {
                // Calculate standard workdays for this month (with userId for user-specific weekend)
                $standardWorkdays = PayrollCalculator::calculateStandardWorkdays(
                    $period->copy()->startOfMonth(),
                    $period->copy()->endOfMonth(),
                    $attendance->user_id
                );

                // Get nilai_hk (bisa 0 jika belum diisi, tidak masalah)
                $nilaiHK = PayrollCalculator::getNilaiHK($attendance->user_id, $attendance->location_id);

                // Set initial values
                $payroll->standard_workdays = $standardWorkdays;
                $payroll->nilai_hk = $nilaiHK; // Bisa 0 jika belum diisi, akan diisi nanti
                $payroll->basic_salary = $nilaiHK > 0 ? PayrollCalculator::calculateBasicSalary($nilaiHK, $standardWorkdays) : 0;
                $payroll->hk_review = null; // Akan diisi manual nanti
                $payroll->status = 'draft';
                $payroll->created_by = 1; // System user
            }

            // Recalculate effective present_days (attendance + paid leave) with location timezone awareness
            $startDate = $period->copy()->startOfMonth();
            $endDate = $period->copy()->endOfMonth();
            $user = \App\Models\User::find($attendance->user_id);
            // Use calculateEffectivePresentDays to include paid leave
            $presentDays = PayrollCalculator::calculateEffectivePresentDays($attendance->user_id, $startDate, $endDate, $user->location_id ?? null);

            // Update present_days (selalu update, tidak peduli nilai_hk)
            $payroll->present_days = $presentDays;

            // Calculate percentage (tidak perlu nilai_hk)
            if ($payroll->standard_workdays > 0) {
                $payroll->percentage = ($presentDays / $payroll->standard_workdays) * 100;
            } else {
                $payroll->percentage = 0;
            }

            // Recalculate estimated_salary and final_salary (hanya jika nilai_hk sudah ada DAN status masih draft)
            // Untuk payroll yang sudah approved/paid, kita hanya update present_days dan percentage
            if ($payroll->status === 'draft' && $payroll->nilai_hk > 0) {
                // Estimated salary = nilai_hk × present_days
                $payroll->estimated_salary = $payroll->nilai_hk * $presentDays;

                // Final salary = nilai_hk × hk_review (or present_days if hk_review is not set)
                $hkReview = $payroll->hk_review ?? $presentDays;
                $payroll->final_salary = $payroll->nilai_hk * $hkReview;

                // Calculate selisih HK
                $payroll->selisih_hk = $hkReview - $presentDays;
            } elseif ($payroll->status === 'draft' && $payroll->nilai_hk <= 0) {
                // Jika nilai_hk belum ada, set ke 0 (akan diisi nanti)
                $payroll->estimated_salary = 0;
                $payroll->final_salary = 0;
                $payroll->selisih_hk = 0;
            }
            // Jika status bukan draft, kita tidak mengubah salary fields (tetap menggunakan nilai yang sudah ada)

            // Always update present_days and percentage for all statuses
            // But only update salary fields if status is draft
            $payroll->save();
            \Log::info("AttendanceObserver: Payroll saved", [
                'payroll_id' => $payroll->id,
                'user_id' => $attendance->user_id,
                'present_days' => $payroll->present_days,
                'percentage' => $payroll->percentage,
                'status' => $payroll->status,
                'salary_updated' => $payroll->status === 'draft' ? 'yes' : 'no (status: ' . $payroll->status . ')',
            ]);
        } catch (\Exception $e) {
            // Log error but don't break the attendance creation
            \Log::error("Failed to update payroll from attendance: " . $e->getMessage(), [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'date' => $attendance->date,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

}

