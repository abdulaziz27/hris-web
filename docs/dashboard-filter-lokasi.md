# Dashboard Filter Lokasi Kebun

## 📋 Deskripsi

Fitur filter lokasi pada dashboard memungkinkan admin/manager untuk melihat data statistik dan laporan berdasarkan lokasi kebun tertentu atau semua lokasi sekaligus.

## ✨ Fitur

### 1. **Filter Global Lokasi**
- Dropdown filter di bagian atas dashboard
- Opsi "Semua Lokasi" (default) - menampilkan data gabungan dari semua lokasi
- Opsi pilihan lokasi spesifik (Kantor Pusat, Kebun Sawit, dll)
- Filter tersimpan di URL (bookmarkable/shareable)
- Real-time update semua widget saat filter berubah

### 2. **Widget yang Terpengaruh Filter**

#### **Dashboard Stats Widget**
- Total Pegawai (per lokasi)
- Overtime Disetujui (per lokasi bulan ini)
- Cuti Disetujui (per lokasi bulan ini)
- Absensi Lengkap (per lokasi bulan ini)
- Deskripsi widget menyesuaikan dengan nama lokasi yang dipilih

#### **Attendance Chart Widget**
- Grafik absensi 30 hari terakhir per lokasi
- Title grafik menampilkan nama lokasi yang dipilih
- Data chart menyesuaikan dengan lokasi

#### **Latest Attendance Widget**
- Tabel absensi terbaru per lokasi
- Kolom lokasi disembunyikan saat filter lokasi aktif
- Menampilkan kolom lokasi saat "Semua Lokasi" dipilih

#### **Pending Approvals Widget (Leave)**
- Daftar cuti menunggu persetujuan per lokasi
- Kolom lokasi disembunyikan saat filter lokasi aktif
- Filter berdasarkan lokasi karyawan yang mengajukan

#### **Pending Overtime Widget**
- Daftar overtime menunggu persetujuan per lokasi
- Kolom lokasi disembunyikan saat filter lokasi aktif
- Filter berdasarkan lokasi karyawan yang mengajukan

## 🎨 Tampilan UI

### **Filter Section**
```
┌─────────────────────────────────────────────────────────────┐
│  📍 Filter Lokasi Kebun                   [Dropdown Select]  │
│  Pilih lokasi untuk melihat data spesifik atau semua lokasi │
│                                                               │
│  ℹ️  Menampilkan data untuk: Kebun Sawit Purwokerto Timur   │
└─────────────────────────────────────────────────────────────┘
```

### **Widget dengan Filter Aktif**
```
Widget Title: "Grafik Absensi 30 Hari Terakhir - Kebun Sawit Purwokerto Timur"
Stats Description: "Pegawai di Kebun Sawit Purwokerto Timur"
```

## 🔧 Implementasi Teknis

### **File yang Dimodifikasi**

1. **Dashboard Page**
   - File: `app/Filament/Pages/Dashboard.php`
   - Menambahkan property `locationFilter` dengan URL binding
   - Method `getLocations()` untuk data dropdown
   - Method `getHeaderWidgetsData()` dan `getWidgetsData()` untuk passing filter ke widget

2. **Custom Blade View**
   - File: `resources/views/filament/pages/dashboard.blade.php`
   - UI filter dengan dropdown lokasi
   - Info badge menampilkan lokasi aktif
   - Livewire integration untuk reactive updates

3. **Widget Updates**
   - `DashboardStatsWidget.php` - Filter stats berdasarkan lokasi
   - `AttendanceChartWidget.php` - Filter chart berdasarkan lokasi
   - `LatestAttendanceWidget.php` - Filter tabel berdasarkan lokasi
   - `PendingApprovalsWidget.php` - Filter leave berdasarkan lokasi employee
   - `PendingOvertimeWidget.php` - Filter overtime berdasarkan lokasi employee

### **Query Logic**

#### **Direct Location Filter**
```php
// Untuk model yang memiliki location_id langsung
$query->where('location_id', $this->locationFilter);
```

#### **Relational Location Filter**
```php
// Untuk model yang memiliki relasi ke User (yang punya location_id)
$query->whereHas('user', function ($q) {
    $q->where('location_id', $this->locationFilter);
});
```

## 📊 Data Lokasi

### **Lokasi yang Tersedia**
1. **Kantor Pusat** - Jl. Raya Purwokerto Timur
2. **Kebun Sawit Purwokerto Timur** - 100 hektar
3. **Kebun Karet Banyumas** - 50 hektar
4. **Kebun Pembibitan Cilongok** - Teknologi modern
5. **Kebun Sawit Ajibarang** - 150 hektar
6. **Kebun Kelapa Sawit Sumbang** - Sistem irigasi modern

## 🚀 Cara Penggunaan

### **Untuk Admin/Manager**

1. **Akses Dashboard**
   - Login ke admin panel (`/admin`)
   - Navigate ke Dashboard

2. **Gunakan Filter Lokasi**
   - Klik dropdown "Filter Lokasi Kebun"
   - Pilih "Semua Lokasi" untuk data gabungan
   - Pilih lokasi spesifik untuk data per kebun

3. **View Data**
   - Semua widget akan otomatis update
   - Stats, grafik, dan tabel menyesuaikan dengan filter
   - URL di browser akan menyimpan pilihan filter

4. **Share/Bookmark**
   - URL bisa di-copy dan share ke rekan kerja
   - Bookmark URL dengan filter tertentu untuk quick access

## 🔍 Detail Fitur

### **Sticky Filter**
- Filter tersimpan di URL parameter
- Tetap aktif saat refresh page
- Bisa di-share via URL

### **Dynamic Widget Titles**
- Title widget menampilkan nama lokasi aktif
- Contoh: "Absensi Terbaru - Kebun Sawit Purwokerto Timur"

### **Smart Column Visibility**
- Kolom lokasi disembunyikan saat filter lokasi spesifik aktif
- Kolom lokasi ditampilkan saat "Semua Lokasi" dipilih
- Menghemat space dan meningkatkan readability

### **Info Badge**
- Badge berwarna menampilkan status filter saat ini
- Blue badge: Lokasi spesifik aktif
- Gray badge: Semua lokasi ditampilkan

## 🎯 Use Cases

### **Case 1: Manager Kebun**
Seorang manager ingin melihat performa kebun yang dia kelola:
1. Pilih lokasi kebunnya dari dropdown
2. Lihat stats pegawai, absensi, overtime, dan cuti khusus kebunnya
3. Monitor grafik absensi 30 hari terakhir
4. Review pending approvals untuk karyawan di kebunnya

### **Case 2: Admin Pusat**
Admin HQ ingin membandingkan performa antar kebun:
1. Buka beberapa tab browser
2. Set setiap tab ke lokasi kebun berbeda
3. Compare stats side-by-side
4. Atau pilih "Semua Lokasi" untuk overview keseluruhan

### **Case 3: Reporting**
Membuat laporan untuk stakeholder:
1. Pilih lokasi tertentu
2. Take screenshot dari dashboard
3. Share URL ke stakeholder untuk live view
4. Export/print report dengan filter aktif

## 🔐 Permission & Access Control

- Fitur ini tersedia untuk role: **admin** dan **manager**
- Filter menampilkan semua lokasi yang `is_active = true`
- Untuk pembatasan akses per lokasi, bisa ditambahkan logic di method `getLocations()`

## 🛠️ Customization

### **Menambah Widget Baru dengan Filter**

```php
use App\Models\Location;

class MyCustomWidget extends BaseWidget
{
    public ?int $locationFilter = null;

    protected function getData(): array
    {
        $query = MyModel::query();
        
        if ($this->locationFilter) {
            $query->where('location_id', $this->locationFilter);
        }
        
        return $query->get();
    }
    
    public function getHeading(): ?string
    {
        $locationName = Location::find($this->locationFilter)?->name;
        
        return $locationName 
            ? "My Widget - {$locationName}"
            : "My Widget";
    }
}
```

### **Mengubah Lokasi Default**

Edit `Dashboard.php`:
```php
#[Url]
public ?int $locationFilter = 1; // Set ke ID lokasi default
```

### **Menambah Filter Tambahan**

Bisa menambahkan filter lain seperti:
- Filter Departemen
- Filter Date Range
- Filter Status

## 🐛 Troubleshooting

### **Filter Tidak Bekerja**
- Check apakah `locationFilter` di-pass ke widget
- Pastikan widget memiliki property `public ?int $locationFilter = null;`
- Check apakah location_id ada di database

### **Widget Tidak Update**
- Pastikan Livewire properly loaded
- Check browser console for errors
- Clear cache: `php artisan cache:clear`

### **Data Tidak Sesuai**
- Check relasi antar model
- Pastikan location_id correct di database
- Verify seeder data

## 📝 Future Enhancements

Potensi pengembangan fitur:
- [ ] Export report per lokasi (PDF/Excel)
- [ ] Comparison mode (compare multiple locations)
- [ ] Filter history (recent filters)
- [ ] Saved filters (favorite locations)
- [ ] Location-based notifications
- [ ] Manager role restriction (only see their location)
- [ ] Map view dengan semua lokasi
- [ ] Location performance ranking
- [ ] Drill-down analysis per lokasi

## 📞 Support

Jika ada pertanyaan atau issue:
- Check dokumentasi API di `docs/`
- Review Filament documentation
- Contact: Developer Team

---

**Version:** 1.0  
**Last Updated:** November 2025  
**Author:** Development Team

