<x-filament::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h3 class="text-sm font-semibold text-slate-700 mb-4">Фильтры</h3>
            <form wire:submit.prevent="loadData">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    {{ $this->form }}
                </div>
            </form>
        </div>

        {{-- KPI Widgets --}}
        @if($this->getWidgets())
            <div>
                @livewire(\App\Filament\Widgets\SocialCareKpiWidget::class, [
                    'periodPreset' => $periodPreset,
                    'helperLevel' => $helperLevel,
                    'careServiceId' => $careServiceId,
                    'city' => $city,
                ], key('kpi-widget'))
            </div>
        @endif

        {{-- Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                @livewire(\App\Filament\Widgets\SocialCareVisitsChartWidget::class, [
                    'periodPreset' => $periodPreset,
                    'helperLevel' => $helperLevel,
                    'careServiceId' => $careServiceId,
                    'city' => $city,
                ], key('visits-chart'))
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4">
                @livewire(\App\Filament\Widgets\SocialCareServicesChartWidget::class, [
                    'periodPreset' => $periodPreset,
                    'helperLevel' => $helperLevel,
                    'city' => $city,
                ], key('services-chart'))
            </div>
        </div>

        {{-- Tables --}}
        <div class="space-y-6">
            <div class="bg-white rounded-xl border border-slate-200 p-4">
                @livewire(\App\Filament\Widgets\SocialCareHelpersTableWidget::class, [
                    'periodPreset' => $periodPreset,
                    'helperLevel' => $helperLevel,
                ], key('helpers-table'))
            </div>

            <div class="bg-white rounded-xl border border-slate-200 p-4">
                @livewire(\App\Filament\Widgets\SocialCareClientsTableWidget::class, [
                    'periodPreset' => $periodPreset,
                    'city' => $city,
                ], key('clients-table'))
            </div>
        </div>
    </div>
</x-filament::page>

