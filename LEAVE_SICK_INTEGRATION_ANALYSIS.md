# Analisis: Pengaruh Cuti dan Sakit pada Payroll

## 📋 Status Saat Ini

### Masalah Utama: Cuti dan Sakit **MERUGIKAN** Karyawan

Saat ini, sistem **BELUM terintegrasi** antara Leave (cuti/sakit) dengan perhitungan payroll. Ini menyebabkan:

1. **Karyawan yang cuti/sakit tidak check-in** → Dianggap **tidak hadir (absen)**
2. **Present days berkurang** → Gaji berkurang
3. **Padahal cuti/sakit yang dibayar seharusnya tidak mengurangi gaji**

### Contoh Masalah:

**Scenario:**
- Karyawan A punya 20 hari kerja standar
- Karyawan A cuti 3 hari (paid leave, sudah approved)
- Karyawan A hadir 17 hari (check-in)
- **Sistem menghitung:** `present_days = 17` (hanya dari attendance)
- **Gaji:** `nilai_hk × 17` ❌ (SALAH - seharusnya 20 karena cuti dibayar)

**Seharusnya:**
- `present_days = 17 (hadir) + 3 (cuti dibayar) = 20`
- **Gaji:** `nilai_hk × 20` ✅

## 🔍 Analisis Sistem Saat Ini

### 1. Struktur Data Leave

**Tabel `leave_types`:**
- `name`: Nama tipe cuti (Annual Leave, Sick Leave, dll)
- `quota_days`: Kuota hari per tahun
- `is_paid`: **Boolean** - apakah cuti ini dibayar atau tidak ✅

**Tabel `leaves`:**
- `employee_id`: ID karyawan
- `leave_type_id`: Tipe cuti
- `start_date`, `end_date`: Periode cuti
- `total_days`: Total hari cuti (exclude weekend/holiday)
- `status`: `pending`, `approved`, `rejected`
- `approved_at`, `approved_by`: Info approval

### 2. Perhitungan Present Days Saat Ini

```php
// PayrollCalculator::calculatePresentDays()
// HANYA menghitung dari Attendance (check-in)
return Attendance::where('user_id', $userId)
    ->whereBetween('date', [$start, $end])
    ->whereNotNull('time_in')
    ->count();
```

**Masalah:** Tidak mempertimbangkan Leave yang approved!

### 3. Logika yang Seharusnya

**Present Days = Hari Hadir (Attendance) + Hari Cuti Dibayar (Paid Leave)**

**Kriteria Leave yang Dihitung:**
1. ✅ Status = `approved`
2. ✅ `leave_type.is_paid` = `true`
3. ✅ Tanggal cuti dalam periode payroll
4. ✅ Exclude weekend/holiday (sudah dihitung di `total_days`)

## 🎯 Solusi yang Diperlukan

### Opsi 1: Tambahkan Leave Days ke Present Days (Recommended)

**Formula:**
```
present_days = attendance_days + paid_leave_days
estimated_salary = nilai_hk × present_days
```

**Keuntungan:**
- Simple dan jelas
- Cuti dibayar = dianggap hadir untuk payroll
- Tidak perlu field baru di database

**Implementasi:**
- Update `PayrollCalculator::calculatePresentDays()` untuk include paid leave
- Atau buat method baru `calculateEffectivePresentDays()` yang include leave

### Opsi 2: Pisahkan Leave Days (Advanced)

**Formula:**
```
present_days = attendance_days (tetap)
leave_days = paid_leave_days (field baru)
effective_days = present_days + leave_days
estimated_salary = nilai_hk × effective_days
```

**Keuntungan:**
- Tracking lebih detail (bisa lihat berapa hari cuti vs hadir)
- Lebih transparan untuk reporting

**Kekurangan:**
- Perlu tambah field di database
- Perlu update UI dan export

**Rekomendasi:** Mulai dengan **Opsi 1** (simple), bisa upgrade ke Opsi 2 nanti jika diperlukan.

## 📐 Formula Perhitungan

### Method Baru: `calculatePaidLeaveDays()`

```php
public static function calculatePaidLeaveDays(
    int $userId, 
    Carbon $start, 
    Carbon $end
): int {
    return Leave::where('employee_id', $userId)
        ->where('status', 'approved')
        ->whereHas('leaveType', function($query) {
            $query->where('is_paid', true);
        })
        ->where(function($query) use ($start, $end) {
            // Leave yang overlap dengan periode payroll
            $query->where(function($q) use ($start, $end) {
                // Leave start dalam periode
                $q->whereBetween('start_date', [$start, $end]);
            })->orWhere(function($q) use ($start, $end) {
                // Leave end dalam periode
                $q->whereBetween('end_date', [$start, $end]);
            })->orWhere(function($q) use ($start, $end) {
                // Leave mencakup seluruh periode
                $q->where('start_date', '<=', $start)
                  ->where('end_date', '>=', $end);
            });
        })
        ->get()
        ->sum(function($leave) use ($start, $end) {
            // Hitung hari yang overlap dengan periode
            $leaveStart = max($leave->start_date, $start);
            $leaveEnd = min($leave->end_date, $end);
            // total_days sudah exclude weekend/holiday, tapi perlu hitung overlap
            // Simplifikasi: gunakan total_days jika seluruhnya dalam periode
            if ($leave->start_date >= $start && $leave->end_date <= $end) {
                return $leave->total_days;
            }
            // Jika overlap sebagian, hitung manual (bisa kompleks)
            // Untuk sekarang, gunakan total_days sebagai approximation
            return $leave->total_days;
        });
}
```

### Update `calculatePresentDays()` atau Buat Method Baru

**Opsi A: Update Method Existing**
```php
public static function calculatePresentDays(...): int {
    $attendanceDays = Attendance::where(...)->count();
    $paidLeaveDays = self::calculatePaidLeaveDays($userId, $start, $end);
    return $attendanceDays + $paidLeaveDays;
}
```

**Opsi B: Method Baru (Lebih Aman)**
```php
public static function calculateEffectivePresentDays(...): int {
    $attendanceDays = self::calculateAttendanceDays($userId, $start, $end, $locationId);
    $paidLeaveDays = self::calculatePaidLeaveDays($userId, $start, $end);
    return $attendanceDays + $paidLeaveDays;
}
```

**Rekomendasi:** Opsi B - buat method baru untuk backward compatibility.

## 🔄 Flow Proses

### Scenario 1: Generate Payroll Baru
1. Hitung `attendance_days` dari tabel `attendances`
2. Hitung `paid_leave_days` dari tabel `leaves` (approved + is_paid = true)
3. `present_days` = `attendance_days + paid_leave_days`
4. `estimated_salary` = `nilai_hk × present_days`

### Scenario 2: Leave Di-approve Setelah Payroll Dibuat
1. Supervisor approve leave
2. LeaveObserver trigger (atau manual trigger)
3. Cari payroll untuk periode leave tersebut
4. Jika payroll status = 'draft':
   - Recalculate `present_days` (include leave baru)
   - Update `estimated_salary` dan `final_salary`
5. Jika payroll status = 'approved' atau 'paid':
   - Log warning (tidak update otomatis, perlu manual review)

### Scenario 3: Leave Di-reject
- Tidak ada perubahan (leave tidak dihitung)

## ⚠️ Edge Cases & Catatan Penting

### 1. Overlap Leave dengan Attendance
**Masalah:** Jika karyawan check-in tapi juga ada leave approved untuk hari yang sama?

**Solusi:**
- Prioritaskan Attendance (jika check-in, dihitung sebagai hadir)
- Leave hanya dihitung jika TIDAK ada attendance untuk hari tersebut
- Atau: Leave override attendance (jika ada leave, tidak perlu check-in)

**Rekomendasi:** Prioritaskan Attendance - jika sudah check-in, tidak perlu hitung leave untuk hari tersebut.

### 2. Unpaid Leave
- Leave dengan `is_paid = false` **TIDAK** dihitung
- Karyawan tetap tidak dapat gaji untuk hari unpaid leave

### 3. Leave yang Melewati Periode Payroll
- Jika leave start di bulan sebelumnya, hanya hitung hari yang dalam periode
- Jika leave end di bulan berikutnya, hanya hitung hari yang dalam periode
- Perlu perhitungan overlap yang akurat

### 4. Multiple Leave dalam 1 Hari
- Tidak mungkin (sistem harus prevent duplicate leave)
- Jika terjadi, hanya hitung 1 hari (tidak double)

### 5. Leave di Weekend/Holiday
- `total_days` di tabel `leaves` sudah exclude weekend/holiday
- Tidak perlu perhitungan tambahan

## 📊 Contoh Perhitungan

### Contoh 1: Cuti Dibayar
**Data:**
- Periode: Januari 2025 (21 hari kerja standar)
- Attendance: 18 hari (check-in)
- Cuti: 3 hari (Annual Leave, is_paid = true, approved)

**Perhitungan:**
1. `attendance_days` = 18
2. `paid_leave_days` = 3
3. `present_days` = 18 + 3 = 21 ✅
4. `estimated_salary` = `nilai_hk × 21`

### Contoh 2: Sakit Dibayar
**Data:**
- Periode: Januari 2025 (21 hari kerja standar)
- Attendance: 19 hari
- Sakit: 2 hari (Sick Leave, is_paid = true, approved)

**Perhitungan:**
1. `attendance_days` = 19
2. `paid_leave_days` = 2
3. `present_days` = 19 + 2 = 21 ✅
4. `estimated_salary` = `nilai_hk × 21`

### Contoh 3: Cuti Tidak Dibayar
**Data:**
- Periode: Januari 2025 (21 hari kerja standar)
- Attendance: 18 hari
- Cuti: 3 hari (Unpaid Leave, is_paid = false, approved)

**Perhitungan:**
1. `attendance_days` = 18
2. `paid_leave_days` = 0 (karena is_paid = false)
3. `present_days` = 18 + 0 = 18
4. `estimated_salary` = `nilai_hk × 18` (benar - tidak dibayar)

### Contoh 4: Overlap Leave dengan Attendance
**Data:**
- Tanggal 15 Jan: Karyawan check-in (hadir)
- Tanggal 15 Jan: Ada leave approved untuk tanggal 15 Jan

**Perhitungan:**
- Prioritaskan attendance: hitung sebagai hadir (1 hari)
- Leave untuk tanggal 15 Jan tidak dihitung (karena sudah ada attendance)
- `present_days` = 1 (dari attendance)

## 🚀 Implementasi Steps

1. ✅ Buat method `calculatePaidLeaveDays()` di PayrollCalculator
2. ✅ Buat method `calculateEffectivePresentDays()` yang combine attendance + paid leave
3. ✅ Update `generateMonthlyPayroll()` untuk use method baru
4. ✅ Update `PayrollForm` calculation untuk include paid leave
5. ✅ Update `AttendanceObserver` (atau buat `LeaveObserver`) untuk auto-update payroll
6. ✅ Test dengan berbagai scenario (paid/unpaid leave, overlap, dll)
7. ✅ Update dokumentasi

## 📝 Field Database (Optional - untuk Opsi 2)

Jika ingin tracking lebih detail, bisa tambah field:
```php
Schema::table('payrolls', function (Blueprint $table) {
    $table->integer('attendance_days')->default(0)->after('present_days');
    $table->integer('paid_leave_days')->default(0)->after('attendance_days');
});
```

Tapi untuk sekarang, **tidak perlu** - cukup update perhitungan saja.

## ⚡ Prioritas

**HIGH PRIORITY** - Masalah ini menyebabkan:
- Karyawan kehilangan gaji untuk cuti/sakit yang seharusnya dibayar
- Potensi masalah hukum/ketenagakerjaan
- Ketidakakuratan payroll

**Rekomendasi:** Implementasi segera setelah overtime integration (atau bersamaan).

