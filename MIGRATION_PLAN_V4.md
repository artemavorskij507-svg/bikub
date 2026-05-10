# Filament v3 → v4 Migration Plan для 7 модулей

## 📋 Список модулей для миграции:

1. **Moving** - Переезды
2. **Roadside** - Эвакуация и придорожная помощь
3. **Handyman** - Мастер на час
4. **Errand** - Индивидуальные поручения
5. **Delivery** - Доставка
6. **SocialCare** - Социальная помощь
7. **EcoDisposal** - Утилизация

## 🔄 Основные изменения для миграции

### 1. Сигнатуры методов форм и таблиц

**v3:**
```php
use Filament\Resources\Form;
use Filament\Resources\Table;

public static function form(Form $form): Form
{
    return $form->schema([...]);
}

public static function table(Table $table): Table
{
    return $table->columns([...]);
}
```

**v4:**
```php
use Filament\Forms\Form;
use Filament\Tables\Table;

public static function form(Form $form): Form
{
    return $form->schema([...]);
}

public static function table(Table $table): Table
{
    return $table->columns([...]);
}
```

### 2. BadgeColumn → TextColumn с ->badge()

**v3:**
```php
use Filament\Tables\Columns\BadgeColumn;

BadgeColumn::make('status')
    ->colors([
        'warning' => 'pending',
        'success' => 'completed',
    ])
```

**v4:**
```php
use Filament\Tables\Columns\TextColumn;

TextColumn::make('status')
    ->badge()
    ->color(fn (string $state): string => match ($state) {
        'pending' => 'warning',
        'completed' => 'success',
        default => 'gray',
    })
```

### 3. Schema API (для Infolists)

**v4 добавляет:**
```php
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;

public static function infolist(Schema $schema): Schema
{
    return $schema->components([
        TextEntry::make('name'),
    ]);
}
```

### 4. Actions импорты

**v3:**
```php
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;
```

**v4:**
```php
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
```

### 5. Wizard Steps

**v3:**
```php
use Filament\Forms\Components\Wizard\Step;
```

**v4:** Остаётся так же, но есть изменения в API

### 6. Toggle Column

**v3:**
```php
Tables\Columns\ToggleColumn::make('is_active')
```

**v4:**
```php
Tables\Columns\ToggleColumn::make('is_active')
    ->onColor('success')
    ->offColor('gray')
```

## 🛠️ План миграции

### Этап 1: Подготовка
- ✅ Скопировать модули в `D:\Bikube\laravel\`
- ⏳ Обновить composer autoload
- ⏳ Создать backup текущих файлов

### Этап 2: Автоматизированная замена
- ⏳ Заменить импорты `Filament\Resources\Form` → `Filament\Forms\Form`
- ⏳ Заменить импорты `Filament\Resources\Table` → `Filament\Tables\Table`
- ⏳ Заменить `BadgeColumn` → `TextColumn` с `->badge()`
- ⏳ Обновить Actions импорты

### Этап 3: Ручной Review
- ⏳ Проверить каждый Resource на наличие deprecated методов
- ⏳ Адаптировать Pages (ListRecords, CreateRecord, EditRecord, ViewRecord)
- ⏳ Проверить RelationManagers
- ⏳ Проверить Widgets

### Этап 4: Тестирование
- ⏳ Запустить `php artisan filament:optimize`
- ⏳ Проверить каждый модуль в админ панели
- ⏳ Исправить возникшие ошибки

### Этап 5: Финализация
- ⏳ Обновить README и документацию
- ⏳ Commit изменений в Git

## 📝 Замены (RegEx паттерны)

### 1. Form/Table импорты
```regex
Find: use Filament\\Resources\\(Form|Table);
Replace: use Filament\\$1s\\$1;
```

### 2. BadgeColumn
```regex
Find: use Filament\\Tables\\Columns\\BadgeColumn;
Replace: use Filament\\Tables\\Columns\\TextColumn;
```

```regex
Find: BadgeColumn::make\(
Replace: TextColumn::make(
```

### 3. Actions
```regex
Find: Tables\\Actions\\(View|Edit|Delete)
Replace: Filament\\Actions\\$1
```

## 🚨 Критические моменты

1. **Colors в Badge** - требует переписывания с массива на callback
2. **IconColumn** - теперь поддерживает state-based colors
3. **Notifications** - API остался прежним, но есть новые возможности
4. **RelationManagers** - минимальные изменения
5. **Custom Pages** - возможно потребуется обновление

## 📊 Статистика

- **Всего Resources**: ~24 файла
- **Всего Pages**: ~50+ файлов
- **Всего RelationManagers**: ~10 файлов
- **Estimated Time**: 4-6 часов для всех модулей

## ✅ Checklist по модулям

- [ ] Moving (3 resources)
- [ ] Roadside (4 resources)
- [ ] Handyman (4 resources)
- [ ] Errand (2 resources)
- [ ] Delivery (2 resources)
- [ ] SocialCare (3 resources)
- [ ] EcoDisposal (3 resources)

---

**Автор**: Antigravity AI  
**Дата**: 2025-12-23
