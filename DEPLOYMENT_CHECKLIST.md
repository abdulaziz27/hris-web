# Deployment Checklist - Timezone Implementation

## ✅ **Pre-Deployment Checklist:**

### **1. Migration**
- [x] Migration file sudah dibuat: `2025_11_15_000000_add_timezone_to_locations_table.php`
- [ ] **PENTING:** Jalankan migration di server setelah push:
  ```bash
  php artisan migrate
  ```

### **2. Update Existing Locations**
- [ ] **PENTING:** Setelah migration, update timezone untuk semua lokasi yang sudah ada:
  - Buka halaman Locations di Filament admin
  - Set timezone untuk setiap lokasi:
    - WIB → `Asia/Jakarta`
    - WITA → `Asia/Makassar`
    - WIT → `Asia/Jayapura`

### **3. Code Review**
- [x] Semua fitur critical sudah timezone-aware
- [x] Tidak ada linter errors
- [x] Flutter app tidak perlu perubahan

### **4. Testing (Recommended)**
- [ ] Test check-in dari lokasi berbeda (WIB, WITA, WIT)
- [ ] Test leave request dengan berbagai tanggal
- [ ] Test payroll calculation
- [ ] Verify waktu yang tersimpan sesuai timezone lokasi

## 🚀 **Deployment Steps:**

### **Backend (Laravel):**

1. **Push ke Git:**
   ```bash
   git add .
   git commit -m "feat: implement timezone per location (WIB/WITA/WIT)"
   git push origin main
   ```

2. **Di Server:**
   ```bash
   git pull origin main
   composer install --no-dev --optimize-autoloader
   php artisan migrate
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Update Locations Timezone:**
   - Login ke admin panel
   - Buka halaman Locations
   - Set timezone untuk setiap lokasi

### **Flutter App:**

1. **Build APK/IPA:**
   ```bash
   cd flutter_absensi_app_final
   flutter clean
   flutter pub get
   flutter build apk --release  # untuk Android
   # atau
   flutter build ios --release  # untuk iOS
   ```

2. **Tidak perlu perubahan kode** - Flutter app sudah OK

## ⚠️ **Important Notes:**

1. **Migration Wajib:**
   - Migration harus dijalankan di server
   - Field `timezone` akan ditambahkan dengan default `Asia/Jakarta`

2. **Update Locations:**
   - **PENTING:** Setelah migration, update timezone untuk semua lokasi
   - Jika tidak di-update, semua lokasi akan pakai default `Asia/Jakarta` (WIB)

3. **Backward Compatibility:**
   - Jika `location.timezone` null, akan fallback ke `config('app.timezone')`
   - Tapi sebaiknya semua lokasi sudah di-set timezone-nya

4. **Testing:**
   - Test dengan berbagai timezone sebelum production
   - Monitor log untuk error timezone

## ✅ **Ready to Deploy:**

**Backend:** ✅ Siap
**Flutter App:** ✅ Siap (tidak perlu perubahan)

**Next Steps:**
1. Push ke Git
2. Pull di server
3. Jalankan migration
4. Update timezone untuk semua lokasi
5. Test dengan berbagai timezone

