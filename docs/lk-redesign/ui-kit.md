# LK UI Kit (`/lk`)

Практический набор Blade partials/components для быстрой сборки интерфейсов `/lk`.

## 1) Class Naming (BEM-light)

- Namespace: `lk-`
- Блоки: `.lk-topbar`, `.lk-stats`, `.lk-filters`, `.lk-list-row`, `.lk-empty`, `.lk-action-panel`
- Элементы: `__` (например `.lk-topbar__title`)
- Модификаторы: `--` (например `.lk-stats__card--warning`)
- Utility-state: `.is-active`, `.is-muted`, `.is-loading`

## 2) Base Layout Wrapper

```blade
<section class="lk-page">
    <div class="lk-page__container">
        @include('lk.partials.top-bar')
        @include('lk.partials.stats-cards')
        @include('lk.partials.filters')

        <div class="lk-page__content">
            {{-- list / table --}}
        </div>

        @includeWhen($items->isEmpty(), 'lk.partials.empty-state')
        @include('lk.partials.action-panel')
    </div>
</section>
```

## 3) Top Bar (`lk/partials/top-bar.blade.php`)

```blade
<header class="lk-topbar">
    <div class="lk-topbar__left">
        <h1 class="lk-topbar__title">{{ $title ?? 'Dashboard' }}</h1>
        <p class="lk-topbar__subtitle">{{ $subtitle ?? 'Overview and operations' }}</p>
    </div>
    <div class="lk-topbar__right">
        <span class="lk-topbar__meta">Updated: {{ now()->format('H:i') }}</span>
        <button type="button" class="lk-btn lk-btn--primary">Create</button>
    </div>
</header>
```

## 4) Stats Cards (`lk/partials/stats-cards.blade.php`)

```blade
<section class="lk-stats" aria-label="Key metrics">
    <article class="lk-stats__card">
        <span class="lk-stats__label">Total</span>
        <strong class="lk-stats__value">{{ $stats['total'] ?? 0 }}</strong>
        <span class="lk-stats__hint">all records</span>
    </article>

    <article class="lk-stats__card lk-stats__card--success">
        <span class="lk-stats__label">Active</span>
        <strong class="lk-stats__value">{{ $stats['active'] ?? 0 }}</strong>
        <span class="lk-stats__hint">in progress</span>
    </article>

    <article class="lk-stats__card lk-stats__card--warning">
        <span class="lk-stats__label">Pending</span>
        <strong class="lk-stats__value">{{ $stats['pending'] ?? 0 }}</strong>
        <span class="lk-stats__hint">need attention</span>
    </article>

    <article class="lk-stats__card lk-stats__card--danger">
        <span class="lk-stats__label">Overdue</span>
        <strong class="lk-stats__value">{{ $stats['overdue'] ?? 0 }}</strong>
        <span class="lk-stats__hint">blocked</span>
    </article>
</section>
```

## 5) Filters (`lk/partials/filters.blade.php`)

```blade
<form method="GET" class="lk-filters" role="search">
    <div class="lk-filters__group">
        <label class="lk-filters__label" for="q">Search</label>
        <input id="q" name="q" value="{{ request('q') }}" class="lk-input" placeholder="ID, title, customer..." />
    </div>

    <div class="lk-filters__group">
        <label class="lk-filters__label" for="status">Status</label>
        <select id="status" name="status" class="lk-select">
            <option value="">All</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="pending" @selected(request('status')==='pending')>Pending</option>
            <option value="done" @selected(request('status')==='done')>Done</option>
        </select>
    </div>

    <div class="lk-filters__actions">
        <button class="lk-btn lk-btn--ghost" type="reset">Reset</button>
        <button class="lk-btn lk-btn--primary" type="submit">Apply</button>
    </div>
</form>
```

## 6) List Row (`lk/partials/list-row.blade.php`)

```blade
<article class="lk-list-row {{ ($item->is_overdue ?? false) ? 'lk-list-row--danger' : '' }}">
    <div class="lk-list-row__main">
        <a href="{{ route('lk.items.show', $item) }}" class="lk-list-row__title">
            {{ $item->title }}
        </a>
        <p class="lk-list-row__meta">
            #{{ $item->id }} • {{ $item->customer_name ?? 'No customer' }} • {{ $item->updated_at?->format('d.m.Y H:i') }}
        </p>
    </div>

    <div class="lk-list-row__status">
        <span class="lk-badge lk-badge--{{ $item->status }}">
            {{ ucfirst($item->status) }}
        </span>
    </div>

    <div class="lk-list-row__actions">
        <button class="lk-btn lk-btn--sm lk-btn--ghost" type="button">Open</button>
        <button class="lk-btn lk-btn--sm lk-btn--primary" type="button">Run</button>
    </div>
</article>
```

## 7) Empty State (`lk/partials/empty-state.blade.php`)

```blade
<section class="lk-empty" role="status" aria-live="polite">
    <div class="lk-empty__icon">[]</div>
    <h2 class="lk-empty__title">No results</h2>
    <p class="lk-empty__text">Try changing filters or create a new item.</p>
    <div class="lk-empty__actions">
        <a href="{{ route('lk.items.create') }}" class="lk-btn lk-btn--primary">Create item</a>
        <a href="{{ url()->current() }}" class="lk-btn lk-btn--ghost">Clear filters</a>
    </div>
</section>
```

## 8) Action Panel (`lk/partials/action-panel.blade.php`)

```blade
<aside class="lk-action-panel" aria-label="Quick actions">
    <h3 class="lk-action-panel__title">Quick Actions</h3>

    <div class="lk-action-panel__grid">
        <button class="lk-action-panel__item" type="button">
            <span class="lk-action-panel__item-title">Bulk update</span>
            <span class="lk-action-panel__item-meta">Apply status to selected</span>
        </button>

        <button class="lk-action-panel__item" type="button">
            <span class="lk-action-panel__item-title">Export CSV</span>
            <span class="lk-action-panel__item-meta">Current filtered list</span>
        </button>

        <button class="lk-action-panel__item" type="button">
            <span class="lk-action-panel__item-title">Assign owner</span>
            <span class="lk-action-panel__item-meta">Set responsible user</span>
        </button>
    </div>
</aside>
```

## 9) Optional Blade Components Alias

Если нужна компонентная обертка вместо `@include`:

- `<x-lk.top-bar />`
- `<x-lk.stats-cards :stats="$stats" />`
- `<x-lk.filters />`
- `<x-lk.list-row :item="$item" />`
- `<x-lk.empty-state />`
- `<x-lk.action-panel />`

Именование файлов компонентов:

- `resources/views/components/lk/top-bar.blade.php`
- `resources/views/components/lk/stats-cards.blade.php`
- `resources/views/components/lk/filters.blade.php`
- `resources/views/components/lk/list-row.blade.php`
- `resources/views/components/lk/empty-state.blade.php`
- `resources/views/components/lk/action-panel.blade.php`
