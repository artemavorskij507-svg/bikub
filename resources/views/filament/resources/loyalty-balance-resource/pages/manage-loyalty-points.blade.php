<x-filament::page>
    <div class="filament-page-header">
        <h1 class="text-2xl font-bold">{{ \Illuminate\Support\Str::of($this->getTitle() ?? 'Керування балами лояльності')->__toString() }}</h1>
    </div>

    <div class="mt-6">
        {{ $this->form }}
    </div>
</x-filament::page>
