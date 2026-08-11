# Multi-Vendor VPS Deployment Guide

Laravel 13 + PHP 8.3 + FastAPI Python on Ubuntu VPS with Apache2 + MySQL

## Files in This Folder

| File | Description |
|---|---|
| `01-system-dependencies.md` | PHP extensions, Composer, Node.js, Python |
| `02-project-setup.md` | Permissions, Composer install, npm build |
| `03-environment.md` | `.env` configuration reference |
| `04-database.md` | MySQL user/db creation, migrations, seeders |
| `05-apache.md` | Virtual host setup, wildcard subdomains, PHP-FPM |
| `06-queue-scheduler.md` | Systemd queue worker + cron scheduler |
| `07-python-service.md` | FastAPI virtualenv, systemd service, Apache proxy |
| `08-ssl.md` | Let's Encrypt + wildcard cert |
| `09-monitoring.md` | Log tailing, service checks, useful artisan commands |
| `configs/laravel-queue.service` | Systemd unit — paste to `/etc/systemd/system/` |
| `configs/python-modules.service` | Systemd unit — paste to `/etc/systemd/system/` |
| `configs/apache-vhost.conf` | Apache VHost — paste to `/etc/apache2/sites-available/` |
| `deploy.sh` | One-command deploy script after `git pull` |

## Quick Order

1. `01` → `02` → `03` → `04` → `05` → `06` → `07` → `08`
2. Copy files from `configs/` to their system locations
3. Use `deploy.sh` for all future code deploys
