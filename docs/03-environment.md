# 03 — Environment Configuration

## Create .env from example

```bash
cd /var/www/multi-vendor
cp .env.example .env
nano .env
```

## Required Changes for Production

```env
# ── App ───────────────────────────────────────────────
APP_ENV=production
APP_DEBUG=false
APP_KEY=                        # generated in step 04
APP_URL=https://yourdomain.com

# ── Database ──────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

# ── Sessions ──────────────────────────────────────────
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=.yourdomain.com   # dot prefix = wildcard for all subdomains (tenancy)

# ── Queue ─────────────────────────────────────────────
QUEUE_CONNECTION=database        # uses jobs table in MySQL

# ── Cache ─────────────────────────────────────────────
CACHE_STORE=file

# ── Mail ─────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_mail_password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# ── Filesystem ────────────────────────────────────────
FILESYSTEM_DISK=local

# ── Tenancy (stancl/tenancy) ──────────────────────────
# Central domains — must match APP_URL host(s)
# Check config/tenancy.php for the exact key name used
CENTRAL_DOMAIN=yourdomain.com
```

## Generate App Key

```bash
php artisan key:generate
```
