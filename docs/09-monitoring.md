# 09 — Monitoring & Useful Commands

## Service Status

```bash
# Check all at once
sudo systemctl status apache2 mysql php8.3-fpm laravel-queue python-modules

# Restart all
sudo systemctl restart apache2 php8.3-fpm laravel-queue python-modules
```

## Laravel Logs

```bash
# Live tail
tail -f /var/www/multi-vendor/storage/logs/laravel.log

# Last 50 lines
tail -50 /var/www/multi-vendor/storage/logs/laravel.log

# Search for errors
grep -i "error\|exception\|fatal" /var/www/multi-vendor/storage/logs/laravel.log | tail -30
```

## Apache Logs

```bash
tail -f /var/log/apache2/multi-vendor-error.log
tail -f /var/log/apache2/multi-vendor-access.log
```

## Laravel Cache & Optimization

```bash
# Clear everything (use on development / after .env changes)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear

# Cache everything (use on production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

# One-shot clear all
php artisan optimize:clear
```

## Queue Commands

```bash
php artisan queue:work            # foreground, Ctrl+C to stop
php artisan queue:listen          # foreground, reloads on each job
php artisan queue:failed          # list failed jobs
php artisan queue:retry all       # retry all failed
php artisan queue:flush           # delete all failed
php artisan queue:restart         # signal workers to restart after deploy
```

## Tenant Commands

```bash
php artisan tenants:list                                # list all tenants
php artisan tenants:migrate --force                     # migrate all tenants
php artisan tenants:migrate --tenants=UUID --force      # migrate one tenant
php artisan tenants:seed --tenants=UUID --force         # seed one tenant
php artisan tenants:run "cache:clear" --force           # run artisan cmd on all tenants
```

## Artisan Tinker (debugging)

```bash
php artisan tinker

# Inside tinker:
>>> \App\Models\User::count()
>>> \App\Models\Tenant::all()
>>> \Illuminate\Support\Facades\Cache::flush()
```

## Disk & Memory

```bash
df -h                    # disk usage
free -h                  # memory usage
htop                     # live process monitor (sudo apt install htop)
```

## Permissions Quick Fix

```bash
sudo chown -R www-data:www-data /var/www/multi-vendor/storage /var/www/multi-vendor/bootstrap/cache
sudo chmod -R 775 /var/www/multi-vendor/storage /var/www/multi-vendor/bootstrap/cache
```
