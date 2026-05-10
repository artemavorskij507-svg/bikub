# Laravel Modules Migration

## 📦 Migrated Modules

This directory contains the following service modules that have been migrated from the main application:

### ✅ Completed Migrations

1. **Moving** - Переезды / Moving Services
2. **Roadside** - Эвакуация и придорожная помощь / Roadside Assistance & Towing
3. **Handyman** - Мастер на час / Handyman & Repair Services
4. **Errand** - Индивидуальные поручения / Custom Errands
5. **Delivery** - Доставка / Delivery Services
6. **SocialCare** - Социальная помощь / Social Care Services
7. **EcoDisposal** - Утилизация / Eco Disposal Services

## 📊 Migration Statistics

- **Total Files Migrated**: 278
- **Migrations**: 34 database migrations
- **Models**: Multiple domain models per service
- **Controllers**: API, Account, Public, Admin controllers
- **Filament Resources**: Admin panel resources for all modules
- **Services**: Business logic services
- **Events & Listeners**: Event-driven architecture components

## 📁 Directory Structure

```
laravel/
├── app/
│   ├── Console/Commands/      # CLI commands for modules
│   ├── Events/                # Module events
│   ├── Filament/
│   │   ├── Pages/            # Custom admin pages
│   │   ├── Resources/        # CRUD resources
│   │   └── Widgets/          # Dashboard widgets
│   ├── Http/
│   │   ├── Controllers/      # Web & API controllers
│   │   ├── Middleware/       # Module-specific middleware
│   │   └── Requests/         # Form request validators
│   ├── Listeners/            # Event listeners
│   ├── Models/               # Eloquent models
│   ├── Notifications/        # Notification classes
│   ├── Observers/            # Model observers
│   ├── Policies/             # Authorization policies
│   └── Services/             # Business logic
└── database/
    └── migrations/           # Database migrations
```

## ⚙️ Post-Migration Tasks

### 1. Update Composer Autoloading

Add to `composer.json`:

```json
{
    "autoload": {
        "psr-4": {
            "App\\\\": "app/",
            "Laravel\\\\": "laravel/app/"
        }
    }
}
```

Then run:
```bash
composer dump-autoload
```

### 2. Update Namespaces

All files in `laravel/` directory need namespace updates:
- From: `namespace App\\Models;`
- To: `namespace Laravel\\Models;`

Run the namespace update script:
```bash
./update_namespaces.sh
```

### 3. Update Import Statements

All files importing from migrated modules need updates:
- From: `use App\\Models\\Moving\\MovingOrder;`
- To: `use Laravel\\Models\\Moving\\MovingOrder;`

### 4. Update Service Provider Registrations

Register module service providers in `config/app.php` or create a new `LaravelModulesServiceProvider`.

### 5. Update Route Files

If modules have dedicated route files, ensure they're registered in `RouteServiceProvider`.

## 🔍 Module Details

### Moving Module
- **Files**: MovingOrder, MovingItem, MovingOrderPhoto
- **Controllers**: MovingOrderController, MovingPriceEstimateController
- **Migrations**: 4 tables
- **Features**: Photo estimates, item catalog, pricing calculator

### Roadside Module
- **Files**: RoadsideEmergency, RoadsidePreset, RoadsideAssistanceDetail
- **Controllers**: Public SOS, Tracking, Dispatch
- **Migrations**: 8 tables
- **Features**: Emergency dispatch, partner assignment, tracking

### Handyman Module
- **Files**: HandymanAssignment, HandymanService, HandymanKpiSnapshot
- **Controllers**: Booking, Catalog, Assignment management
- **Migrations**: 8 tables
- **Features**: Service catalog, materials tracking, KPI monitoring

### Errand Module
- **Files**: ErrandTask, ErrandOrderDetails
- **Controllers**: ErrandTaskController, ErrandController
- **Migrations**: 4 tables
- **Features**: Custom task management, pricing service

### Delivery Module
- **Files**: DeliveryOrder, DeliveryZone
- **Controllers**: DeliveryQuoteController, CourierDeliveryController
- **Migrations**: 10 tables
- **Features**: Multi-type delivery (grocery, bulky, food), geofencing, ETA calculation

### SocialCare Module
- **Files**: SocialCareEmergencyEvent, SocialCareNotificationSettings
- **Controllers**: SocialCareOrderController, Account management
- **Migrations**: Multiple tables
- **Features**: Emergency triggers, analytics, client management

### EcoDisposal Module
- **Files**: DisposalItem, DisposalPartner, DisposalOrderDetails
- **Controllers**: EcoDisposalController
- **Migrations**: 4 tables
- **Features**: Item catalog, partner management, dispatch service

## 🚀 Next Steps

1. ✅ Files migrated to `laravel/` directory
2. ⏳ Update namespaces (run script)
3. ⏳ Update composer autoload
4. ⏳ Test all module functionality
5. ⏳ Update documentation
6. ⏳ Commit changes to version control

## 📝 Notes

- All original files have been **moved** (not copied)
- Original directory structure is preserved within `laravel/`
- Database migrations maintain their original timestamps
- No code modifications were made during migration

---

**Migration Date**: 2025-12-22
**Migration Script**: `migrate_modules.sh`
