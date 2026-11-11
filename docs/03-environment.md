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

## Store (vendor) mail servers

The `MAIL_*` values above (or the central admin's Email Configuration page, which overrides them)
are the **central** mail configuration. Each store can add its own server in its panel under
Settings → Mail Configurations (`/admin/settings/mail`). The rules
(`App\Services\Mail\MailConfigurationResolver`, `TenantMailHealth`, `TemplateMailService::deliverAsTenant()`):

- **All or nothing.** A store config is used only when complete (SMTP: host, port, username,
  password, from address; other mailers: from address). Otherwise the central config is used as a
  whole; fields are never mixed (central SMTP + a vendor "from" address gets rejected as spoofing).
- **Verified on save.** Saving sends a confirmation email through the store's server (15s timeout).
  Success → `verified`. Failure → settings are kept, the store is marked `failed`, the vendor gets an
  in-panel notification plus an email to the store owner (sent via central), and all store emails
  go through central.
- **Runtime fallback.** `verified`/`unverified` stores send through their own server; if a send
  fails the same email is retried through central, the store flips to `failed` and is notified once.
  `failed` stores use central only (no repeated SMTP timeouts) until the vendor saves again or a
  "Send Test Email" succeeds.
- The health state lives in the tenant `settings` table (`mail_status_*` rows, group `mail_health`) with a
  fingerprint of the settings, so editing them resets the state to `unverified`. Configs saved before
  verification existed start as `unverified` and verify themselves on their first successful send.
