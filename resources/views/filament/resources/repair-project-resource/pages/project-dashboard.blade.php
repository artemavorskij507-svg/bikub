<x-filament::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $project->title }}</h2>
                        <p class="text-sm text-gray-600">
                            Статус: <span class="font-medium">{{ $project->status }}</span>
                        </p>
                        @if($project->address_line)
                            <p class="text-sm text-gray-600">
                                Адрес: {{ $project->address_line }}, {{ $project->city }}
                            </p>
                        @endif
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-primary-600">
                            {{ $project->overall_progress_percent ?? 0 }}%
                        </div>
                        <div class="text-xs text-gray-500">Общий прогресс</div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3 text-sm text-gray-700">
                    <div>
                        <div class="text-xs uppercase text-gray-500">Плановые даты</div>
                        <div>{{ optional($project->planned_start_at)->format('d.m.Y') }} – {{ optional($project->planned_finish_at)->format('d.m.Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500">Фактические даты</div>
                        <div>{{ optional($project->actual_start_at)->format('d.m.Y') }} – {{ optional($project->actual_finish_at)->format('d.m.Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs uppercase text-gray-500">PM</div>
                        <div>{{ $project->projectManager?->user?->name ?? 'Не назначен' }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Этапы проекта</h3>
                    <span class="text-sm text-gray-500">{{ $stages->count() }} этап(ов)</span>
                </div>
                <div class="space-y-4">
                    @foreach($stages as $stage)
                        <div class="border rounded-lg p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $stage->name }}</div>
                                    <div class="text-sm text-gray-600">
                                        Статус: {{ $stage->status }}
                                        @if($stage->progress_percent !== null)
                                            · {{ $stage->progress_percent }}%
                                        @endif
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ optional($stage->planned_start_at)->format('d.m.Y') }}
                                    @if($stage->planned_finish_at)
                                        – {{ $stage->planned_finish_at->format('d.m.Y') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @if($stages->isEmpty())
                        <p class="text-sm text-gray-500">Этапы ещё не добавлены.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Последние обновления</h3>
                <div class="space-y-4">
                    @forelse($updates as $update)
                        <div class="border rounded-lg p-3">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>{{ $update->created_at->format('d.m.Y H:i') }}</span>
                                <span>{{ $update->author?->name }}</span>
                            </div>
                            @if($update->title)
                                <div class="font-medium text-gray-900">{{ $update->title }}</div>
                            @endif
                            @if($update->body)
                                <div class="text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($update->body, 120) }}</div>
                            @endif
                            @if($update->progress_percent !== null)
                                <div class="mt-1 text-xs text-gray-500">Прогресс: {{ $update->progress_percent }}%</div>
                            @endif
                            @if($update->stage)
                                <div class="mt-1 text-xs text-gray-500">Этап: {{ $update->stage->name }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Обновлений пока нет.</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Медиа</h3>
                    <span class="text-sm text-gray-500">{{ $project->media_count }} файлов</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @forelse($media as $item)
                        <div class="rounded-lg overflow-hidden border">
                            <img
                                src="{{ \Illuminate\Support\Facades\Storage::disk($item->disk)->url($item->thumbnail_path ?? $item->path) }}"
                                alt="{{ $item->caption }}"
                                class="w-full h-24 object-cover"
                            >
                            @if($item->caption)
                                <div class="px-2 py-1 text-xs text-gray-600">{{ \Illuminate\Support\Str::limit($item->caption, 40) }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500 col-span-2">Медиа-файлы ещё не добавлены.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-filament::page>

