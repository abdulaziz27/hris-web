# 🎯 Solusi Filter Dashboard - 3 Opsi

## 📋 **Opsi 1: Menggunakan HasFiltersForm (RECOMMENDED)** ⭐

### ✅ **Keuntungan:**
- ✅ **Native Filament** - Styling otomatis konsisten dengan Filament
- ✅ **Auto-styled** - Tidak perlu custom CSS
- ✅ **Reactive** - Widget otomatis update saat filter berubah
- ✅ **Session persistence** - Filter tersimpan di session
- ✅ **Validation** - Bisa validasi filter form
- ✅ **Modal option** - Bisa pakai modal atau form inline

### 📚 **Link Dokumentasi:**
1. **Dashboard Filters (Official):**
   - https://filamentphp.com/docs/panels/dashboard#filtering-widget-data
   - https://filamentphp.com/docs/panels/dashboard (Scroll ke "Filtering widget data")

2. **Form Components:**
   - https://filamentphp.com/docs/forms/fields
   - https://filamentphp.com/docs/forms/fields/select
   - https://filamentphp.com/docs/forms/fields/date-picker

3. **Schemas Components (Filament 4):**
   - https://filamentphp.com/docs/schemas
   - https://filamentphp.com/docs/schemas/components

4. **Widget Concerns:**
   - https://filamentphp.com/docs/widgets/overview#filtering-widget-data

### 🔧 **Cara Implementasi:**

#### **1. Update Dashboard.php:**
```php
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Filter Lokasi Kebun')
                    ->description('Pilih lokasi untuk melihat data spesifik atau semua lokasi')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Select::make('location')
                            ->label('Lokasi Kebun')
                            ->options([
                                null => 'Semua Lokasi',
                            ] + Location::where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->live(),
                    ])
                    ->columns(1),
            ]);
    }
}
```

#### **2. Update Widget (contoh DashboardStatsWidget.php):**
```php
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class DashboardStatsWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $locationId = $this->pageFilters['location'] ?? null;
        $locationName = $locationId ? Location::find($locationId)?->name : null;

        return [
            Stat::make('Total Pegawai', $this->getTotalUsers($locationId))
                ->description($locationName ? "Pegawai di {$locationName}" : 'Jumlah seluruh pegawai')
                // ...
        ];
    }

    private function getTotalUsers(?int $locationId): int
    {
        $query = User::query();
        
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        
        return $query->count();
    }
}
```

#### **3. Alternative: Modal Filter (HasFiltersAction)**
Jika ingin filter di modal (button di header):
```php
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Pages\Dashboard\Actions\FilterAction;

class Dashboard extends BaseDashboard
{
    use HasFiltersAction;

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->label('Filter Lokasi')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Select::make('location')
                        ->label('Lokasi Kebun')
                        ->options([...])
                        ->searchable(),
                ]),
        ];
    }
}
```

---

## 📋 **Opsi 2: Custom Widget/Component**

### ⚠️ **Keuntungan:**
- ✅ Full control over design
- ✅ Custom behavior

### ⚠️ **Kekurangan:**
- ❌ Harus handle styling sendiri
- ❌ Bisa tidak konsisten dengan Filament design
- ❌ Perlu dokumentasi Filament untuk styling

### 📚 **Link Dokumentasi yang Diperlukan:**

1. **Creating Custom Widgets:**
   - https://filamentphp.com/docs/widgets/overview#custom-widgets
   - https://filamentphp.com/docs/widgets/custom-widgets

2. **Filament View Components:**
   - https://filamentphp.com/docs/components
   - https://filamentphp.com/docs/components/view-components

3. **Styling dengan Tailwind (Filament uses Tailwind):**
   - https://tailwindcss.com/docs
   - https://filamentphp.com/docs/styling

4. **Filament Color System:**
   - https://filamentphp.com/docs/styling/colors
   - https://filamentphp.com/docs/styling/dark-mode

5. **Form Components untuk styling:**
   - https://filamentphp.com/docs/forms/fields/select
   - https://filamentphp.com/docs/forms/fields#styling

6. **Section Component:**
   - https://filamentphp.com/docs/schemas/components/section

### 🔧 **Yang Perlu Dikirim ke Saya:**
1. Screenshot tampilan filter saat ini
2. Screenshot tampilan Filament yang diinginkan (reference)
3. Informasi tentang masalah styling (apakah CSS tidak ter-load?)
4. Preferensi design (inline form atau modal?)

### 🛠️ **Cara Membuat Custom Widget:**

```bash
php artisan make:filament-widget LocationFilterWidget
```

```php
namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Components\Select;

class LocationFilterWidget extends Widget
{
    protected static string $view = 'filament.widgets.location-filter-widget';
    
    public ?int $location = null;

    protected function getViewData(): array
    {
        return [
            'locations' => Location::where('is_active', true)->get(),
        ];
    }
}
```

**View:**
```blade
<div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="fi-section-content-ctn">
        <div class="fi-section-content p-6">
            <x-filament::section>
                <x-slot name="heading">
                    Filter Lokasi Kebun
                </x-slot>
                <x-slot name="description">
                    Pilih lokasi untuk melihat data spesifik
                </x-slot>
                
                {{ filament()->form([
                    Select::make('location')
                        ->options([...])
                        ->live(),
                ]) }}
            </x-filament::section>
        </div>
    </div>
</div>
```

---

## 📋 **Opsi 3: Open Source Package**

### 🔍 **Package yang Tersedia:**

1. **Filament Filter Sets (Filament 2/3):**
   - https://github.com/filamentphp/filament
   - Note: Untuk Filament 4 mungkin belum tersedia

2. **Filament Spatie Laravel Data:**
   - https://github.com/filamentphp/spatie-laravel-data-plugin
   - Untuk advanced filtering

3. **Community Packages:**
   - Search di Packagist: https://packagist.org/search/?q=filament+filter
   - GitHub: https://github.com/topics/filament-filter

### ⚠️ **Pertimbangan:**
- ✅ Tidak perlu develop dari scratch
- ❌ Mungkin tidak kompatibel dengan Filament 4
- ❌ Dependency tambahan
- ❌ Kurang control

---

## 🎯 **Rekomendasi Saya:**

### **Opsi 1: HasFiltersForm** ⭐⭐⭐⭐⭐

**Alasan:**
1. ✅ **Native Filament** - Styling otomatis perfect
2. ✅ **Simple** - Hanya perlu tambah trait dan method
3. ✅ **Maintained** - Dijaga oleh Filament team
4. ✅ **Future-proof** - Compatible dengan update Filament
5. ✅ **No CSS issues** - Styling sudah handle oleh Filament

### **Implementasi Cepat:**
1. Update `Dashboard.php` dengan trait `HasFiltersForm`
2. Update semua widget dengan trait `InteractsWithPageFilters`
3. Ganti `getLocationFilter()` dengan `$this->pageFilters['location']`
4. Done! Styling otomatis perfect

---

## 📞 **Next Steps:**

### **Jika Pilih Opsi 1 (Recommended):**
Saya bisa langsung implement `HasFiltersForm` sekarang. Hanya perlu:
1. Update Dashboard.php
2. Update semua widget
3. Test

### **Jika Pilih Opsi 2 (Custom):**
Kirimkan ke saya:
1. Screenshot masalah styling saat ini
2. Screenshot reference design yang diinginkan
3. Informasi tentang masalah CSS (console errors?)
4. Preferensi: inline form atau modal?

### **Jika Pilih Opsi 3 (Open Source):**
Saya bisa cari dan evaluate package yang cocok untuk Filament 4.

---

## 🔗 **Link Dokumentasi Lengkap:**

### **Filament 4 Official Docs:**
- **Main:** https://filamentphp.com/docs
- **Dashboard:** https://filamentphp.com/docs/panels/dashboard
- **Widgets:** https://filamentphp.com/docs/widgets
- **Forms:** https://filamentphp.com/docs/forms
- **Schemas:** https://filamentphp.com/docs/schemas

### **Specific Pages:**
- **Dashboard Filters:** https://filamentphp.com/docs/panels/dashboard#filtering-widget-data
- **Select Field:** https://filamentphp.com/docs/forms/fields/select
- **Section Component:** https://filamentphp.com/docs/schemas/components/section
- **InteractsWithPageFilters:** https://filamentphp.com/docs/widgets/overview#filtering-widget-data

---

**Status:** Ready untuk implementasi  
**Rekomendasi:** Opsi 1 (HasFiltersForm)  
**Waktu implementasi:** ~30 menit

