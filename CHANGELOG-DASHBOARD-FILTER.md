# Changelog: Dashboard Filter Lokasi

## 🎉 New Feature: Dashboard Filter Lokasi Kebun

**Release Date:** November 2025  
**Version:** 1.0.0

---

## 📝 Summary

Implementasi fitur filter lokasi kebun pada dashboard admin Filament yang memungkinkan filtering data berdasarkan lokasi (Kantor Pusat, Kebun Sawit, Kebun Karet, dll). Semua widget dashboard akan secara otomatis menyesuaikan datanya berdasarkan lokasi yang dipilih.

---

## ✅ What's New

### 1. **Global Location Filter**
- ✨ Dropdown filter lokasi di header dashboard
- ✨ Opsi "Semua Lokasi" untuk view agregat
- ✨ Real-time widget updates saat filter berubah
- ✨ URL-based filter (bookmarkable & shareable)
- ✨ Elegant UI dengan icon dan info badge

### 2. **Enhanced Widgets**
Semua widget sekarang support location filtering:

#### **DashboardStatsWidget** ✅
- Filter pegawai per lokasi
- Filter overtime per lokasi
- Filter cuti per lokasi
- Filter absensi per lokasi
- Dynamic descriptions dengan nama lokasi

#### **AttendanceChartWidget** ✅
- Grafik 30 hari terakhir per lokasi
- Dynamic chart title dengan nama lokasi
- Data visualization per kebun

#### **LatestAttendanceWidget** ✅
- Tabel absensi terbaru per lokasi
- Smart column visibility (hide location column saat filter aktif)
- Enhanced dengan lokasi badge

#### **PendingApprovalsWidget** ✅
- Filter cuti pending per lokasi employee
- Smart column visibility
- Location badge integration

#### **PendingOvertimeWidget** ✅
- Filter overtime pending per lokasi employee
- Smart column visibility
- Location badge integration

---

## 🗂️ Files Changed

### Modified Files

1. **app/Filament/Pages/Dashboard.php**
   - Added `locationFilter` property with URL binding
   - Added `getLocations()` method
   - Added `getHeaderWidgetsData()` & `getWidgetsData()` methods
   - Added `updatedLocationFilter()` Livewire hook
   - Set custom view path

2. **app/Filament/Widgets/DashboardStatsWidget.php**
   - Added `locationFilter` property
   - Enhanced stats queries with location filtering
   - Added `getTotalUsers()` method
   - Updated overtime, leave, attendance queries
   - Added `getLocationName()` helper
   - Dynamic stat descriptions

3. **app/Filament/Widgets/AttendanceChartWidget.php**
   - Added `locationFilter` property
   - Enhanced chart data query with location filtering
   - Override `getHeading()` for dynamic title
   - Added `getLocationName()` helper

4. **app/Filament/Widgets/LatestAttendanceWidget.php**
   - Added `locationFilter` property
   - Enhanced table query with location filtering
   - Override `getHeading()` for dynamic title
   - Added location column with smart visibility
   - Added `getLocationName()` helper

5. **app/Filament/Widgets/PendingApprovalsWidget.php**
   - Added `locationFilter` property
   - Enhanced query with `whereHas('employee')` filtering
   - Override `getHeading()` for dynamic title
   - Added location column with smart visibility
   - Added `getLocationName()` helper

6. **app/Filament/Widgets/PendingOvertimeWidget.php**
   - Added `locationFilter` property
   - Enhanced query with `whereHas('user')` filtering
   - Override `getHeading()` for dynamic title
   - Added location column with smart visibility
   - Added `getLocationName()` helper

### New Files

7. **resources/views/filament/pages/dashboard.blade.php** 🆕
   - Custom dashboard view with filter UI
   - Elegant filter section with icon
   - Dropdown select for locations
   - Info badge showing active filter
   - Livewire integration for reactive updates
   - Responsive design (mobile-friendly)

8. **docs/dashboard-filter-lokasi.md** 🆕
   - Comprehensive documentation
   - Usage guide
   - Technical implementation details
   - Troubleshooting guide
   - Use cases & examples

9. **CHANGELOG-DASHBOARD-FILTER.md** 🆕
   - This file - changelog & summary

---

## 🔧 Technical Details

### Architecture

```
Dashboard Page (Livewire Component)
    │
    ├─> locationFilter (public property with #[Url] attribute)
    │
    ├─> Filter UI (dropdown select)
    │       └─> wire:model.live="locationFilter"
    │
    ├─> Header Widgets
    │       └─> DashboardStatsWidget
    │               └─> Receives locationFilter as data
    │
    └─> Main Widgets
            ├─> AttendanceChartWidget
            ├─> LatestAttendanceWidget
            ├─> PendingApprovalsWidget
            └─> PendingOvertimeWidget
                    └─> All receive locationFilter as data
```

### Query Patterns

**Direct Location Filter** (for models with location_id):
```php
if ($this->locationFilter) {
    $query->where('location_id', $this->locationFilter);
}
```

**Relational Location Filter** (for models related to User):
```php
if ($this->locationFilter) {
    $query->whereHas('user', function ($q) {
        $q->where('location_id', $this->locationFilter);
    });
}
```

### UI Components

**Filter Section:**
- Filament section component styling
- Primary color scheme
- Icon: `heroicon-o-map-pin`
- Info icons: `heroicon-o-information-circle`

**Badges:**
- `color('info')` for location badges
- `color('primary')` for active filter badge

---

## 📊 Database Schema

Relies on existing schema:

```sql
-- locations table
- id
- name
- address
- latitude
- longitude
- radius_km
- is_active (boolean)
- attendance_type
- description

-- users table
- location_id (FK to locations)

-- attendances table
- location_id (FK to locations)

-- overtimes table (via user relationship)
- user_id -> users.location_id

-- leaves table (via employee relationship)
- employee_id -> users.location_id
```

---

## 🎯 Features in Detail

### 1. URL-Based Filter (Bookmarkable)

**Example URLs:**
```
/admin                              # All locations (default)
/admin?locationFilter=1            # Specific location ID 1
/admin?locationFilter=2            # Specific location ID 2
```

**Benefits:**
- Can bookmark specific location view
- Can share URL with team members
- Filter persists on page refresh
- Browser back/forward works correctly

### 2. Smart Column Visibility

**When "All Locations" selected:**
```
| Name | Location | Date | Time In | Time Out | Status |
```

**When specific location selected:**
```
| Name | Date | Time In | Time Out | Status |
```
(Location column hidden - saves space)

### 3. Dynamic Widget Titles

**Without filter:**
```
"Absensi Terbaru"
"Grafik Absensi 30 Hari Terakhir"
```

**With filter:**
```
"Absensi Terbaru - Kebun Sawit Purwokerto Timur"
"Grafik Absensi 30 Hari Terakhir - Kebun Sawit Purwokerto Timur"
```

### 4. Enhanced Stats Descriptions

**Without filter:**
```
Total Pegawai: 150
Description: "Jumlah seluruh pegawai"
```

**With filter:**
```
Total Pegawai: 25
Description: "Pegawai di Kebun Sawit Purwokerto Timur"
```

---

## 🎨 UI/UX Improvements

### Visual Enhancements
- ✨ Elegant filter section with icon
- ✨ Color-coded info badges
- ✨ Consistent spacing and padding
- ✨ Dark mode support
- ✨ Responsive design (mobile, tablet, desktop)

### User Experience
- ⚡ Real-time updates (no page reload)
- ⚡ Fast query performance (optimized with proper indexes)
- ⚡ Intuitive dropdown interface
- ⚡ Clear visual feedback of active filter

---

## 🚀 Performance Considerations

### Optimizations Applied

1. **Eager Loading**
   ```php
   ->with(['user:id,name,position,location_id', 'location:id,name'])
   ```

2. **Indexed Columns**
   - `location_id` should be indexed on users, attendances tables
   - Improves WHERE clause performance

3. **Query Limiting**
   ```php
   ->limit(10)  // For table widgets
   ```

4. **Efficient Relationships**
   - Using `whereHas()` instead of loading all data then filtering
   - Proper use of eager loading to prevent N+1 queries

### Performance Metrics (Expected)
- Filter change: < 500ms
- Widget refresh: < 300ms per widget
- Total dashboard load: < 2s (with all widgets)

---

## 📱 Responsive Design

### Breakpoints

**Mobile (< 768px):**
- Filter dropdown full width
- Single column widget layout
- Compact table columns

**Tablet (768px - 1024px):**
- Filter dropdown 72 width
- 2 column widget layout
- Standard table layout

**Desktop (> 1024px):**
- Filter dropdown 72 width
- 3 column widget layout
- Full table with all columns

---

## 🔒 Security & Permissions

### Access Control
- ✅ Feature available for: `admin` and `manager` roles
- ✅ Only active locations shown (`is_active = true`)
- ✅ No SQL injection risk (using Eloquent ORM)
- ✅ CSRF protection (Livewire/Laravel built-in)

### Future Enhancements (Optional)
- Role-based location restrictions
- Manager can only see their assigned location
- Audit log for filter usage
- Permission-based export capabilities

---

## 🧪 Testing Recommendations

### Manual Testing Checklist

- [ ] Select "Semua Lokasi" → Verify all data shown
- [ ] Select specific location → Verify filtered data
- [ ] Change filter → Verify widgets update
- [ ] Refresh page → Verify filter persists
- [ ] Copy URL → Open in new tab → Verify filter works
- [ ] Check mobile view → Verify responsive layout
- [ ] Check dark mode → Verify styling
- [ ] Test with empty data → Verify no errors
- [ ] Test with large datasets → Verify performance

### Automated Testing (TODO)

```php
// Example test
public function test_dashboard_filters_by_location()
{
    $location = Location::factory()->create();
    $user = User::factory()->create(['location_id' => $location->id]);
    
    $this->actingAs($admin)
        ->get("/admin?locationFilter={$location->id}")
        ->assertSuccessful();
}
```

---

## 🐛 Known Issues & Limitations

### Current Limitations
1. No multi-select filter (one location at a time)
2. No comparison mode (side-by-side locations)
3. No saved filters/favorites
4. No export with filter applied (can be added)

### Future Improvements
- [ ] Multi-location comparison
- [ ] Export filtered data
- [ ] Saved filter presets
- [ ] Filter history
- [ ] Location-based alerts

---

## 📚 Related Documentation

- [Main Documentation](docs/dashboard-filter-lokasi.md)
- [Filament Documentation](https://filamentphp.com/docs)
- [Laravel Livewire](https://livewire.laravel.com/docs)
- [Location Seeder](database/seeders/LocationSeeder.php)

---

## 🤝 Contributing

Untuk menambahkan fitur atau melakukan modifikasi:

1. **Adding New Widget with Filter:**
   - Copy pattern dari existing widget
   - Add `public ?int $locationFilter = null;`
   - Implement location filtering in query
   - Override `getHeading()` if needed

2. **Modifying Filter UI:**
   - Edit `resources/views/filament/pages/dashboard.blade.php`
   - Follow Filament component conventions
   - Test dark mode compatibility

3. **Adding More Filter Types:**
   - Add new property in Dashboard.php
   - Pass via `getWidgetsData()`
   - Implement in each widget

---

## 📞 Support & Contact

**Developer:** Development Team  
**Documentation:** `/docs/dashboard-filter-lokasi.md`  
**Version:** 1.0.0  
**Date:** November 2025

---

## 🙏 Acknowledgments

- Filament Team for excellent framework
- Laravel Team for robust foundation
- Livewire for reactive components

---

**Status:** ✅ Ready for Production  
**Review Status:** Pending Code Review  
**Testing Status:** Manual Testing Required

