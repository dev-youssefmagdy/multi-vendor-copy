# 06 — Queue Worker & Scheduler

## Queue Worker (Systemd)

```bash
# Copy service file
sudo cp /var/www/multi-vendor/docs/configs/laravel-queue.service /etc/systemd/system/

# Enable and start
sudo systemctl daemon-reload
sudo systemctl enable laravel-queue
sudo systemctl start laravel-queue
sudo systemctl status laravel-queue
```

## Scheduler (Cron)

```bash
sudo crontab -u www-data -e
```

Add this single line:
```cron
* * * * * cd /var/www/multi-vendor && php artisan schedule:run >> /dev/null 2>&1
```

## Queue Management Commands

```bash
# Check queue status
php artisan queue:monitor

# List failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry a specific failed job by ID
php artisan queue:retry 5

# Delete all failed jobs
php artisan queue:flush

# Clear pending jobs from a queue
php artisan queue:clear database

# Restart workers (after code deploy)
php artisan queue:restart
```

## After Code Deploy

After every deploy you MUST restart the queue worker so it picks up new code:
```bash
php artisan queue:restart
# or
sudo systemctl restart laravel-queue
```

## Watch Queue Log

```bash
tail -f /var/www/multi-vendor/storage/logs/queue.log
tail -f /var/www/multi-vendor/storage/logs/queue-error.log
```
