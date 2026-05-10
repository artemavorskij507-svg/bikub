{{-- resources/views/components/service-cards.blade.php --}}
{{-- 3D Service Cards Component (with Feature Flag support) --}}
@props(['categories'])

<section class="py-20 bg-gradient-to-b from-slate-50 to-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl md:text-4xl font-bold text-center mb-4">
            Наші сервіси в Нарвіку
        </h2>
        <p class="text-center text-slate-600 mb-12 max-w-2xl mx-auto">
            7 ключових напрямків GLF Bikube — доставка, переїзд, майстер, еко, евакуатор, доручення, соціальна допомога.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            @forelse($categories as $category)
                <div
                    x-data="card3d()"
                    @mousemove="onMouseMove($event)"
                    @mouseleave="reset()"
                    class="perspective-1000 group relative"
                >
                    <div
                        :style="style"
                        class="service-card transform transition-transform duration-500 w-full h-80 rounded-2xl overflow-hidden shadow-xl bg-white border border-slate-100"
                    >
                        <div class="p-6 h-full flex flex-col">
                            @php($icon = $category->display_icon)
                            @if($icon)
                                <div class="mb-4 text-primary-500">
                                    {!! $icon !!}
                                </div>
                            @endif

                            <h3 class="text-xl font-bold mb-2">
                                {{ $category->name }}
                            </h3>
                            <p class="text-slate-600 mb-4 flex-grow">
                                {{ $category->short_description ?? $category->description }}
                            </p>
                            <a
                                href="{{ route('public.category', $category->slug ?? $category->code) }}"
                                class="inline-flex items-center text-primary-600 hover:text-primary-700 font-semibold mt-auto"
                            >
                                Детальніше
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                    <div class="absolute inset-0 rounded-2xl bg-gradient-to-r from-primary-500/15 to-sky-500/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
                </div>
            @empty
                <p class="text-center text-slate-500 col-span-full">
                    Додайте категорії в адмінці Filament → ServiceCategory (прапорець «Показувати на головній»)
                </p>
            @endforelse
        </div>
    </div>
</section>

@push('styles')
<style>
    .perspective-1000 { perspective: 1000px; }
    .service-card { transform-style: preserve-3d; }
    @media (hover: none) {
        .service-card { transform: none !important; }
    }
</style>
@endpush

@push('scripts')
<script>
    function card3d() {
        return {
            rotateX: 0,
            rotateY: 0,
            style: 'transform: rotateX(0deg) rotateY(0deg);',
            onMouseMove(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                const rotateY = (x / rect.width - 0.5) * 18;
                const rotateX = (0.5 - y / rect.height) * 18;
                this.rotateX = rotateX;
                this.rotateY = rotateY;
                this.style = `transform: rotateX(${this.rotateX}deg) rotateY(${this.rotateY}deg);`;
            },
            reset() {
                this.rotateX = 0;
                this.rotateY = 0;
                this.style = 'transform: rotateX(0deg) rotateY(0deg);';
            }
        };
    }
</script>
@endpush

