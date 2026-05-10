@extends('layouts.app')

@section('title', 'Комплексный ремонт — GLF Bikube')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-4xl font-bold text-gray-900 mb-6">Комплексный ремонт с GLF Bikube</h1>
            <p class="text-xl text-gray-700 mb-8">
                Полный цикл ремонтных работ от планирования до сдачи объекта. Профессиональная команда, контроль качества и гарантия на все виды работ.
            </p>

            {{-- Для кого услуга --}}
            <section class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Для кого услуга</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="text-4xl mb-4">🏠</div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Квартиры</h3>
                        <p class="text-gray-600">Косметический и капитальный ремонт квартир любой площади</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="text-4xl mb-4">🏡</div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Дома</h3>
                        <p class="text-gray-600">Ремонт частных домов и коттеджей</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="text-4xl mb-4">🏢</div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Офисы</h3>
                        <p class="text-gray-600">Офисные помещения и коммерческие объекты</p>
                    </div>
                </div>
            </section>

            {{-- Как это работает --}}
            <section class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Как это работает</h2>
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">1</div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Заявка</h3>
                            <p class="text-gray-600">Оставьте заявку с описанием объекта и желаемого ремонта</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">2</div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Выезд специалиста</h3>
                            <p class="text-gray-600">Наш специалист выезжает на объект для оценки и замеров</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">3</div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Смета</h3>
                            <p class="text-gray-600">Подготовка детальной сметы и согласование сроков</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">4</div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Работы</h3>
                            <p class="text-gray-600">Выполнение работ по этапам с контролем качества</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold">5</div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-1">Гарантия</h3>
                            <p class="text-gray-600">Сдача объекта и гарантия на все виды работ</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Преимущества --}}
            <section class="mb-12">
                <h2 class="text-2xl font-semibold text-gray-900 mb-4">Преимущества</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Полный контроль</h3>
                        <p class="text-gray-600">Отслеживайте прогресс работ в личном кабинете</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Прозрачная смета</h3>
                        <p class="text-gray-600">Детальная смета без скрытых платежей</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Гарантия качества</h3>
                        <p class="text-gray-600">Гарантия на все виды выполненных работ</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Один подрядчик</h3>
                        <p class="text-gray-600">Все работы выполняет одна команда профессионалов</p>
                    </div>
                </div>
            </section>

            {{-- CTA --}}
            <section class="text-center">
                <a href="{{ route('repair.request') }}" class="inline-flex items-center px-8 py-4 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Оставить заявку на комплексный ремонт
                </a>
            </section>
        </div>
    </div>
@endsection

