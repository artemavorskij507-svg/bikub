{{-- resources/views/components/fast-order-form.blade.php --}}
@props(['serviceTypes' => collect()])

<section class="bg-white border-t border-slate-100">
    <div class="max-w-5xl mx-auto px-6 py-10">
        <div class="bg-slate-50 border border-slate-200 rounded-2xl p-6 md:p-8 shadow-sm">
            <h2 class="text-xl md:text-2xl font-semibold text-slate-900 mb-2">
                Швидкий запит на послугу
            </h2>

            <p class="text-sm text-slate-600 mb-6">
                Залиште короткий запит — диспетчер зв'яжеться з вами та підбере оптимальний варіант доставки або послуги.
            </p>

            <form
                action="{{ route('public.fast-order.store') }}"
                method="POST"
                class="space-y-4"
            >
                @csrf

                {{-- Тип послуги --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Що вам потрібно?
                    </label>
                    <select
                        name="service_type_id"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500 bg-white"
                        required
                    >
                        <option value="">Оберіть послугу…</option>
                        @foreach($serviceTypes as $type)
                            <option value="{{ $type->id }}">
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Адреса --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Адреса (звідки / куди)
                    </label>
                    <input
                        type="text"
                        name="address"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                        placeholder="Наприклад: Dronningens gate 45, Narvik"
                        required
                    >
                </div>

                {{-- Короткий опис --}}
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">
                        Коротко опишіть задачу
                    </label>
                    <textarea
                        name="comment"
                        rows="3"
                        class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                        placeholder="Що потрібно доставити / перевезти / починити?"
                    ></textarea>
                </div>

                {{-- Контакти --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Ім'я
                        </label>
                        <input
                            type="text"
                            name="name"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                            required
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Телефон
                        </label>
                        <input
                            type="tel"
                            name="phone"
                            class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-sky-500"
                            placeholder="+47…"
                            required
                        >
                    </div>
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full md:w-auto inline-flex justify-center items-center px-5 py-2.5 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 transition"
                    >
                        Відправити запит
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

