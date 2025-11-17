# Rencana Integrasi Overtime ke Payroll

## 📋 Ringkasan Masalah
Saat ini, sistem tracking overtime sudah ada, tetapi **tidak terintegrasi dengan perhitungan payroll**. Overtime hanya untuk tracking saja, tidak mempengaruhi gaji karyawan.

## 🎯 Tujuan
Mengintegrasikan overtime yang sudah **approved** ke dalam perhitungan payroll, sehingga karyawan mendapat kompensasi untuk jam lembur mereka.

## 📐 Asumsi & Formula

### Formula Perhitungan Overtime
1. **Hitung Durasi Overtime (jam)**
   - Dari `start_time` dan `end_time` di tabel `overtimes`
   - Hanya hitung overtime dengan status `approved`
   - Durasi = `end_time - start_time` (dalam jam, bisa desimal)

2. **Overtime Rate (Multiplier)**
   - **Default**: 1.5x nilai_hk per jam
   - Bisa dikonfigurasi per location atau user (untuk fleksibilitas)
   - Formula: `overtime_payment = overtime_hours × (nilai_hk / 8) × overtime_multiplier`
   - Asumsi: 1 hari kerja = 8 jam, jadi nilai_hk per jam = nilai_hk / 8

3. **Total Gaji Baru**
   - `estimated_salary` = `(nilai_hk × present_days)` + `overtime_payment`
   - `final_salary` = `estimated_salary` (sama)

## 🗄️ Database Schema Changes

### Migration: Add Overtime Fields to Payrolls Table
```php
Schema::table('payrolls', function (Blueprint $table) {
    $table->decimal('overtime_hours', 8, 2)->default(0)->after('present_days');
    $table->decimal('overtime_payment', 12, 2)->default(0)->after('overtime_hours');
});
```

**Field Baru:**
- `overtime_hours`: Total jam lembur yang approved dalam periode
- `overtime_payment`: Total pembayaran lembur (dalam rupiah)

## 🔧 Komponen yang Perlu Diupdate

### 1. PayrollCalculator Service
**Method Baru:**
- `calculateOvertimeHours(int $userId, Carbon $start, Carbon $end): float`
  - Query overtime yang approved dalam periode
  - Hitung total jam dari semua overtime
  
- `calculateOvertimePayment(float $overtimeHours, float $nilaiHK, float $multiplier = 1.5): float`
  - Hitung pembayaran overtime
  - Formula: `overtime_hours × (nilai_hk / 8) × multiplier`

**Method yang Diupdate:**
- `generateMonthlyPayroll()`: Tambah perhitungan overtime
- `calculateEstimatedSalary()`: Update untuk include overtime (atau buat method baru)

### 2. Payroll Model
**Update `booted()` method:**
- Hitung `overtime_hours` dan `overtime_payment` saat save
- Update `estimated_salary` dan `final_salary` untuk include overtime
- Hanya update jika status = 'draft'

### 3. PayrollForm (Filament)
**Field Baru (Read-only):**
- `overtime_hours`: Display total jam lembur
- `overtime_payment`: Display total pembayaran lembur
- Helper text: "Dihitung otomatis dari overtime yang approved"

### 4. PayrollInfolist (Filament)
**Field Baru:**
- Display overtime_hours dan overtime_payment
- Format dengan currency untuk overtime_payment

### 5. OvertimeObserver (Baru)
**Fungsi:**
- Saat overtime di-approve, trigger update payroll untuk periode tersebut
- Recalculate `overtime_hours`, `overtime_payment`, dan `estimated_salary`
- Hanya update jika payroll status = 'draft'

### 6. PayrollAttendanceExport (Optional)
**Kolom Baru (Optional):**
- Tambah kolom "Overtime (Jam)" dan "Overtime Payment" di export Excel
- Atau bisa di sheet terpisah untuk detail overtime

## 🔄 Flow Proses

### Scenario 1: Generate Payroll Baru
1. User generate payroll untuk periode tertentu
2. System hitung:
   - `present_days` dari attendance
   - `overtime_hours` dari overtime yang approved
   - `overtime_payment` = `overtime_hours × (nilai_hk / 8) × 1.5`
   - `estimated_salary` = `(nilai_hk × present_days) + overtime_payment`

### Scenario 2: Overtime Di-approve Setelah Payroll Dibuat
1. Supervisor approve overtime
2. OvertimeObserver trigger
3. Cari payroll untuk periode overtime tersebut
4. Jika payroll status = 'draft':
   - Recalculate `overtime_hours` dan `overtime_payment`
   - Update `estimated_salary` dan `final_salary`
5. Jika payroll status = 'approved' atau 'paid':
   - Log warning (tidak update otomatis, perlu manual review)

## ⚙️ Konfigurasi Overtime Rate

### Opsi 1: Hard-coded (Simple)
- Default: 1.5x
- Bisa diubah di config file atau constant

### Opsi 2: Per Location (Recommended)
- Tambah field `overtime_multiplier` di tabel `locations`
- Default: 1.5
- Bisa berbeda per lokasi

### Opsi 3: Per User (Advanced)
- Tambah field `overtime_multiplier` di tabel `users`
- Override location multiplier jika ada

**Rekomendasi:** Mulai dengan Opsi 1 (hard-coded 1.5x), bisa upgrade ke Opsi 2 nanti jika diperlukan.

## 📝 Contoh Perhitungan

**Data:**
- Nilai HK: Rp 100,000
- Present Days: 20 hari
- Overtime: 3 jam (approved)

**Perhitungan:**
1. Gaji dari kehadiran: Rp 100,000 × 20 = Rp 2,000,000
2. Nilai HK per jam: Rp 100,000 / 8 = Rp 12,500
3. Overtime payment: 3 jam × Rp 12,500 × 1.5 = Rp 56,250
4. **Total Gaji: Rp 2,000,000 + Rp 56,250 = Rp 2,056,250**

## ⚠️ Catatan Penting

1. **Hanya Overtime Approved yang Dihitung**
   - Overtime dengan status `pending` atau `rejected` tidak masuk perhitungan

2. **Periode Overtime**
   - Overtime dihitung berdasarkan `date` di tabel `overtimes`
   - Harus match dengan periode payroll (bulan yang sama)

3. **Timezone Awareness**
   - Pastikan perhitungan overtime aware dengan timezone lokasi
   - Sudah ada di OvertimeController, perlu pastikan konsisten

4. **Backward Compatibility**
   - Payroll yang sudah approved tidak akan ter-update otomatis
   - Perlu manual review jika ada overtime baru yang di-approve

5. **Edge Cases**
   - Overtime yang melewati tengah malam (start_time > end_time)
   - Overtime di hari libur (bisa pakai multiplier berbeda nanti)

## 🚀 Implementasi Steps

1. ✅ Buat migration untuk tambah field overtime di payrolls
2. ✅ Update PayrollCalculator dengan method calculateOvertimeHours dan calculateOvertimePayment
3. ✅ Update Payroll model booted() untuk include overtime
4. ✅ Update PayrollForm untuk display overtime (read-only)
5. ✅ Update PayrollInfolist untuk display overtime
6. ✅ Buat OvertimeObserver untuk auto-update payroll saat approve
7. ✅ Test dengan data real
8. ✅ Update PayrollAttendanceExport (optional)

## 📊 Testing Checklist

- [ ] Generate payroll baru dengan overtime approved → overtime terhitung
- [ ] Generate payroll baru tanpa overtime → overtime_hours = 0
- [ ] Approve overtime setelah payroll dibuat (draft) → payroll ter-update
- [ ] Approve overtime setelah payroll approved → tidak ter-update (log warning)
- [ ] Multiple overtime dalam 1 bulan → total jam terhitung benar
- [ ] Overtime dengan status pending/rejected → tidak terhitung
- [ ] Perhitungan overtime payment sesuai formula

