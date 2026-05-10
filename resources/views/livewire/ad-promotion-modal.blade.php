@if($show)
<div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('show') }" x-show="show" x-transition>
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" x-on:click="show = false"></div>

        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                            Продвинуть объявление
                        </h3>
                        
                        <div class="space-y-3">
                            <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 {{ $selectedPromotion === 'bump' ? 'border-primary-600 bg-primary-50' : 'border-gray-200' }}">
                                <input type="radio" wire:model="selectedPromotion" value="bump" class="mr-3">
                                <div>
                                    <div class="font-semibold text-gray-900">Поднять в топ</div>
                                    <div class="text-sm text-gray-500">Объявление появится в начале списка</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 {{ $selectedPromotion === 'highlight' ? 'border-primary-600 bg-primary-50' : 'border-gray-200' }}">
                                <input type="radio" wire:model="selectedPromotion" value="highlight" class="mr-3">
                                <div>
                                    <div class="font-semibold text-gray-900">✨ Выделить</div>
                                    <div class="text-sm text-gray-500">Выделение на 7 дней</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 {{ $selectedPromotion === 'top' ? 'border-primary-600 bg-primary-50' : 'border-gray-200' }}">
                                <input type="radio" wire:model="selectedPromotion" value="top" class="mr-3">
                                <div>
                                    <div class="font-semibold text-gray-900">⬆️ Топ размещение</div>
                                    <div class="text-sm text-gray-500">В топе на 3 дня</div>
                                </div>
                            </label>

                            <label class="flex items-center p-3 border-2 rounded-lg cursor-pointer hover:bg-gray-50 {{ $selectedPromotion === 'vip' ? 'border-primary-600 bg-primary-50' : 'border-gray-200' }}">
                                <input type="radio" wire:model="selectedPromotion" value="vip" class="mr-3">
                                <div>
                                    <div class="font-semibold text-gray-900">👑 VIP статус</div>
                                    <div class="text-sm text-gray-500">Максимальное продвижение на 14 дней</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button wire:click="promote" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Применить
                </button>
                <button wire:click="closeModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Отмена
                </button>
            </div>
        </div>
    </div>
</div>
@endif

