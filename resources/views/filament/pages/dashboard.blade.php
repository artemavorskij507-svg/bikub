<x-filament::page>
    <x-bikube.os-shell container-class="space-y-6">
        <x-bikube.page-header
            eyebrow="Admin Core"
            title="BiKuBe Operations Core"
            subtitle="Unified operations overview for dispatch, contracts, support, and lifecycle monitoring."
            :chips="['Orders', 'Dispatch', 'Payouts', 'Support']"
            badge="Wave 3B Admin OS v1"
        />

        @if ($widgets = $this->getWidgets())
            <x-filament::widgets
                :widgets="$widgets"
                :columns="$this->getColumns()"
            />
        @endif
    </x-bikube.os-shell>
</x-filament::page>
