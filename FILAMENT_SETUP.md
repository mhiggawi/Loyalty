# Filament 3 Setup Guide - Multi-Tenant Loyalty System

## Installation Commands

Run these commands in your Laravel project:

```bash
# Install Filament 3
composer require filament/filament:"^3.2"

# Install Filament Multi-tenancy Plugin
composer require filament/spatie-laravel-tenancy-plugin:"^3.2"

# Create Admin Panels
php artisan filament:install --panels

# When prompted, create two panels:
# 1. admin (Super Admin Panel)
# 2. merchant (Merchant Panel)

# Publish Filament configuration
php artisan vendor:publish --tag=filament-config

# Publish Filament panels configuration
php artisan vendor:publish --tag=filament-panels

# Create Super Admin user
php artisan make:filament-user --name="Super Admin" --email="admin@loyaltysystem.com"
```

## Panel Structure

After installation, you'll have:

```
app/
└── Filament/
    ├── Admin/           # Super Admin Panel
    │   └── Resources/
    └── Merchant/        # Merchant Panel
        └── Resources/
```

## Configuration Files

```
config/
├── filament.php          # Global Filament config
└── filament-panels.php   # Panels configuration
```

---

## Multi-Tenancy Setup

The system uses **two authentication layers**:

### 1. Super Admin Panel (`/admin`)
- **Model:** `App\Models\Admin` (we'll create this)
- **Access:** Global platform management
- **Resources:** Tenants, GlobalCustomers, Platform Stats

### 2. Merchant Panel (`/merchant`)
- **Model:** `App\Models\Staff`
- **Access:** Tenant-scoped (automatic filtering by tenant_id)
- **Resources:** Customers, Rewards, Tiers, Staff, etc.

---

## Authentication Models

We need to create an `Admin` model for Super Admins, as `Staff` model is for merchant employees.

---

This setup ensures complete data isolation and proper multi-tenancy! 🔒
