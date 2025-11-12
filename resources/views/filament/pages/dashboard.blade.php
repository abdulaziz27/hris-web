<x-filament-panels::page>
    {{-- Location Filter Section --}}
    <div class="mb-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-50 dark:bg-primary-900/20">
                                <x-filament::icon
                                    icon="heroicon-o-map-pin"
                                    class="h-6 w-6 text-primary-600 dark:text-primary-400"
                                />
                            </div>
                            <div>
                                <h3 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                                    Filter Lokasi Kebun
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Pilih lokasi untuk melihat data spesifik atau semua lokasi
                                </p>
                            </div>
                        </div>

                        <div class="w-full md:w-72">
                            <select
                                wire:model.live="locationFilter"
                                class="fi-select-input block w-full rounded-lg border-gray-300 shadow-sm transition duration-75 focus:border-primary-600 focus:ring-1 focus:ring-inset focus:ring-primary-600 disabled:opacity-70 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-primary-600"
                            >
                                @foreach($this->getLocations() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($locationFilter)
                        <div class="mt-4 flex items-center gap-2 rounded-lg bg-primary-50 px-4 py-2 dark:bg-primary-900/20">
                            <x-filament::icon
                                icon="heroicon-o-information-circle"
                                class="h-5 w-5 text-primary-600 dark:text-primary-400"
                            />
                            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">
                                Menampilkan data untuk: <strong>{{ $this->getLocations()[$locationFilter] }}</strong>
                            </span>
                        </div>
                    @else
                        <div class="mt-4 flex items-center gap-2 rounded-lg bg-gray-50 px-4 py-2 dark:bg-gray-800">
                            <x-filament::icon
                                icon="heroicon-o-information-circle"
                                class="h-5 w-5 text-gray-600 dark:text-gray-400"
                            />
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Menampilkan data dari <strong>semua lokasi kebun</strong>
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Header Widgets (Stats) --}}
    @if($this->hasHeaderWidgets())
        <div class="mb-6">
            @livewire(\Livewire\Livewire::getAlias($this->getHeaderWidgets()[0]), [
                'locationFilter' => $locationFilter
            ])
        </div>
    @endif

    {{-- Main Widgets --}}
    <div class="grid gap-6 {{ $this->getColumnsClass() }}">
        @foreach($this->getVisibleWidgets() as $widget)
            @livewire(\Livewire\Livewire::getAlias($widget), [
                'locationFilter' => $locationFilter
            ])
        @endforeach
    </div>
</x-filament-panels::page>

