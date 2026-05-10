@if($availableClients->isNotEmpty() || $activeClient)
    <section class="client-context" aria-label="Контекст клиента">
        <div class="client-context-header">
            <span class="client-context-label">Активный контекст</span>
            <span class="client-context-status {{ $activeClient ? 'client-context-status-active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M9.5 12.3l1.6 1.6 3.4-3.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                {{ $activeClient ? 'Режим клиента' : 'Личный режим' }}
            </span>
        </div>

        <div class="client-context-details">
            <span class="client-context-name">
                {{ $activeClient?->full_name ?? 'Мой личный профиль' }}
            </span>
            @if($activeClient)
                <span class="client-context-id">
                    @if($activeClient->city)
                        {{ $activeClient->city }}
                    @else
                        Профиль клиента
                    @endif
                </span>
            @endif
        </div>

        @if($availableClients->isNotEmpty())
            <form
                method="POST"
                action="{{ route('account.client.switch') }}"
                class="mt-4 grid gap-3 md:grid-cols-[1fr_auto]"
                data-current="{{ $activeClient?->id ?? '' }}"
                data-client-switch
                data-confirm="Переключить активный контекст аккаунта?"
            >
                @csrf
                <div class="form-group client-context-form-group">
                    <label for="client_profile_id" class="form-label">Выбор профиля</label>
                    <select id="client_profile_id" name="client_profile_id" class="form-select" aria-label="Выбрать активный профиль клиента">
                        <option value="">Только я</option>
                        @foreach($availableClients as $client)
                            <option value="{{ $client->id }}" @selected($activeClient && $activeClient->id === $client->id)>
                                {{ $client->full_name }}@if($client->city) - {{ $client->city }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-primary">Применить</button>
                </div>
            </form>
        @elseif($activeClient)
            <form
                method="POST"
                action="{{ route('account.client.switch') }}"
                class="mt-4"
                data-confirm="Сбросить контекст клиента и вернуться к личному профилю?"
            >
                @csrf
                <input type="hidden" name="client_profile_id" value="">
                <button type="submit" class="btn btn-secondary btn-sm">Сбросить контекст клиента</button>
            </form>
        @endif
    </section>
@endif
