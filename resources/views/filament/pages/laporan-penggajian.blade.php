<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Filter Laporan
            </x-slot>
            <x-slot name="description">
                Pilih periode dan lokasi untuk melihat laporan penggajian
            </x-slot>
            
            <form wire:submit.prevent class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Periode
                        </label>
                        <input 
                            type="month" 
                            wire:model.live="filters.period"
                            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-500 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500 dark:focus:ring-primary-400 sm:text-sm sm:leading-6"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Lokasi Kebun
                        </label>
                        <select 
                            wire:model.live="filters.location"
                            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-500 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500 dark:focus:ring-primary-400 sm:text-sm sm:leading-6"
                        >
                            <option value="">Semua Lokasi</option>
                            @foreach(\App\Models\Location::where('is_active', true)->orderBy('name')->get() as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($filters['location'] ?? null)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Karyawan
                        </label>
                        <select 
                            wire:model.live="filters.user"
                            class="fi-input block w-full rounded-lg border-none bg-white px-3 py-1.5 text-base text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-500 dark:bg-white/5 dark:text-white dark:placeholder:text-gray-500 dark:focus:ring-primary-400 sm:text-sm sm:leading-6"
                        >
                            <option value="">Semua Karyawan</option>
                            @foreach(\App\Models\User::where('role', 'employee')->where('location_id', $filters['location'])->orderBy('name')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </form>
        </x-filament::section>

        {{-- Payroll Table --}}
        @php
            $payrollData = $this->getPayrollData();
            $period = $payrollData['period'];
            $start = $payrollData['start'];
            $end = $payrollData['end'];
            $standardWorkdays = $payrollData['standard_workdays'];
            $payrolls = $payrollData['payrolls'];
            
            // Generate all dates in month
            $dates = [];
            $current = $start->copy();
            while ($current <= $end) {
                $dates[] = $current->copy();
                $current->addDay();
            }
        @endphp

        @if(count($payrolls) > 0)
            <x-filament::section>
                <div class="overflow-x-auto -mx-4 sm:-mx-6 lg:-mx-8">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th scope="col" class="sticky left-0 z-20 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                            ID
                                        </th>
                                        <th scope="col" class="sticky left-[60px] z-20 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[150px]">
                                            NAMA
                                        </th>
                                        <th scope="col" class="sticky left-[210px] z-20 bg-gray-50 dark:bg-gray-800 px-4 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700 min-w-[120px]">
                                            Bagian
                                        </th>
                                        
                                        {{-- Date columns --}}
                                        @foreach($dates as $date)
                                            <th scope="col" class="px-2 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider min-w-[40px] {{ $date->isWeekend() ? 'bg-yellow-50 dark:bg-yellow-900/20' : 'bg-gray-50 dark:bg-gray-800' }}">
                                                <div class="flex flex-col items-center">
                                                    <span class="text-[10px] font-medium">{{ $date->format('D') }}</span>
                                                    <span class="text-xs font-bold">{{ $date->format('d') }}</span>
                                                </div>
                                            </th>
                                        @endforeach
                                        
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-l border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                            Hari Kerja
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-50 dark:bg-gray-800">
                                            Hadir
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-50 dark:bg-gray-800">
                                            Persentase
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-50 dark:bg-gray-800">
                                            Nilai HK
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-50 dark:bg-gray-800">
                                            Estimasi Gaji
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-50 dark:bg-gray-800">
                                            HK Review
                                        </th>
                                        <th scope="col" class="px-4 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider bg-gray-50 dark:bg-gray-800">
                                            Selisih HK
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($payrolls as $payroll)
                                        @php
                                            $user = $payroll['user'];
                                            $data = $payroll['data'];
                                            $dailyStatus = $payroll['daily_status'];
                                        @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                            <td class="sticky left-0 z-10 bg-white dark:bg-gray-900 px-4 py-3 whitespace-nowrap text-xs font-mono text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700">
                                                {{ $user->id }}
                                            </td>
                                            <td class="sticky left-[60px] z-10 bg-white dark:bg-gray-900 px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 border-r border-gray-200 dark:border-gray-700">
                                                {{ $user->name }}
                                            </td>
                                            <td class="sticky left-[210px] z-10 bg-white dark:bg-gray-900 px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300 border-r border-gray-200 dark:border-gray-700">
                                                {{ $user->departemen->name ?? '-' }}
                                            </td>
                                            
                                            {{-- Daily status cells --}}
                                            @foreach($dates as $date)
                                                @php
                                                    $dateKey = $date->format('Y-m-d');
                                                    $status = $dailyStatus[$dateKey] ?? 'A';
                                                    $isWeekend = $date->isWeekend();
                                                    $bgColor = match($status) {
                                                        'H' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200',
                                                        'A' => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200',
                                                        'L' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200',
                                                        'W' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-200',
                                                        default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
                                                    };
                                                @endphp
                                                <td class="px-1 py-2 text-center text-xs font-bold {{ $bgColor }} {{ $isWeekend ? 'border-l-2 border-yellow-400 dark:border-yellow-500' : '' }}">
                                                    {{ $status }}
                                                </td>
                                            @endforeach
                                            
                                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold text-gray-900 dark:text-gray-100 border-l border-gray-200 dark:border-gray-700">
                                                {{ $data['standard_workdays'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold {{ $data['present_days'] >= $data['standard_workdays'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $data['present_days'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold {{ $data['percentage'] >= 100 ? 'text-green-600 dark:text-green-400' : ($data['percentage'] >= 80 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                                {{ number_format($data['percentage'], 2) }}%
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-mono text-gray-900 dark:text-gray-100">
                                                Rp {{ number_format($data['nilai_hk'], 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-mono font-semibold text-gray-900 dark:text-gray-100">
                                                Rp {{ number_format($data['estimated_salary'], 0, ',', '.') }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $data['hk_review'] }}
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-center text-sm font-semibold {{ $data['selisih_hk'] > 0 ? 'text-green-600 dark:text-green-400' : ($data['selisih_hk'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400') }}">
                                                {{ $data['selisih_hk'] > 0 ? '+' : '' }}{{ $data['selisih_hk'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada data payroll untuk periode yang dipilih.</p>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
