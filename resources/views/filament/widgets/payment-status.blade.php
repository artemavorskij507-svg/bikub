<x-filament::widget>
    <x-filament::card>
        <div class="flex items-start justify-between">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Платежная система</h3>
                <p class="mt-2 text-2xl font-bold {{ $isActive ? 'text-emerald-600' : 'text-gray-400' }}">
                    {{ $isActive ? 'Активна' : 'Неактивна' }}
                </p>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $payment->label ?? 'Stripe Payment Gateway' }} • {{ $payment->currency ?? 'NOK' }}
                </p>
                <div class="mt-2 flex items-center gap-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $isTest ? 'bg-yellow-50 text-yellow-700' : 'bg-emerald-50 text-emerald-700' }}">
                        {{ $isTest ? 'Test Mode' : 'Live Mode' }}
                    </span>
                    @if(!empty($payment?->publishable_key))
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">
                            pk_…{{ substr($payment->publishable_key, -6) }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <a href="/admin/payment-settings" class="inline-flex items-center text-sm text-emerald-600 hover:underline">Открыть настройки →</a>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>


