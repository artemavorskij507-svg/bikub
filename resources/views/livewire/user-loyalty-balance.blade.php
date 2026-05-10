<div>
    @if(auth()->check() && $balance)
        @if($full)
            <!-- Full Card View -->
            <div class="bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg border border-purple-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                        Ваші бали лояльності
                    </h3>
                    <div class="text-right">
                        <div class="text-3xl font-bold text-purple-600">{{ number_format($balance->points, 0, '.', ' ') }}</div>
                        <div class="text-sm text-gray-600">≈ {{ number_format($pointsValue, 2, ',', ' ') }} ₴</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 pt-4 border-t border-purple-200">
                    <div>
                        <div class="text-sm text-gray-600">Всього накопичено</div>
                        <div class="text-2xl font-bold text-blue-600">{{ number_format($balance->lifetime_points, 0, '.', ' ') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-600">Останнє оновлення</div>
                        <div class="text-sm text-gray-700">{{ $balance->updated_at->format('d.m.Y') }}</div>
                    </div>
                </div>

                @if($transactions->count() > 0)
                    <div class="border-t border-purple-200 pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Останні операції</h4>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @foreach($transactions as $transaction)
                                <div class="flex items-center justify-between p-2 bg-white rounded hover:bg-gray-50 transition">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $transaction->getTypeColor() }}-100 text-{{ $transaction->getTypeColor() }}-800">
                                            {{ $transaction->getTypeLabel() }}
                                        </span>
                                        <span class="text-sm text-gray-600 max-w-xs truncate">{{ $transaction->description }}</span>
                                    </div>
                                    <span class="text-sm font-semibold {{ $transaction->points_amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->points_amount > 0 ? '+' : '' }}{{ $transaction->points_amount }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @else
            <!-- Badge View (Compact) -->
            <div class="inline-flex items-center gap-2 bg-gradient-to-r from-purple-500 to-blue-500 text-white px-4 py-2 rounded-full font-semibold shadow-md hover:shadow-lg transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span>{{ number_format($balance->points, 0, '.', ' ') }} балів</span>
            </div>
        @endif
    @else
        <div class="text-center py-4 text-gray-500">
            Увійдіть, щоб бачити ваші бали лояльності
        </div>
    @endif
</div>
