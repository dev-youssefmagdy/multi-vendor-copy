# 04 — Database Setup

## Create Database & User

```bash
mysql -u root -p
```

Inside MySQL shell:
```sql
CREATE DATABASE your_db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'your_db_user'@'localhost' IDENTIFIED BY 'your_db_password';
GRANT ALL PRIVILEGES ON your_db_name.* TO 'your_db_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Run Central Migrations

```bash
cd /var/www/multi-vendor
php artisan migrate --force
```

## Create Queue Jobs Table

```bash
php artisan queue:table
php artisan migrate --force
```

## Run Seeders (if needed)

```bash
php artisan db:seed --force
```

## Tenant Migrations (stancl/tenancy)

After tenants are created through the application, run their migrations:

```bash
# Migrate all existing tenants
php artisan tenants:migrate --force

# Migrate a specific tenant
php artisan tenants:migrate --tenants=TENANT_ID --force

# Seed a specific tenant
php artisan tenants:seed --tenants=TENANT_ID --force
```

## Useful Database Commands

```bash
# Check migration status
php artisan migrate:status

# Rollback last batch
php artisan migrate:rollback

# Fresh install (DESTROYS all data)
php artisan migrate:fresh --seed --force
```
