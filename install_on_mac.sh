#!/usr/bin/env bash
#
# install.sh — First-time setup for Multi-Vendor (Laravel 13 + stancl/tenancy) on macOS
#
# What this does:
#   1. Installs Homebrew, PHP 8.3, Composer, Node.js, MySQL, Redis (if missing)
#   2. Installs Laravel Valet and registers the local TLD + wildcard subdomains
#   3. Installs PHP & JS dependencies, builds assets
#   4. Creates the MySQL database/user and the .env file
#   5. Generates app key, runs migrations + seeders, links storage
#   6. Starts a queue worker + scheduler (via launchd) and Reverb (websockets)
#
# Usage:
#   chmod +x install.sh
#   ./install.sh
#
# Safe to re-run — every step is idempotent.

set -euo pipefail

# ── Config (edit or override via env vars before running) ───────────────────
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOMAIN="${DOMAIN:-dokan-v2.loc}"                  # central app domain
DB_NAME="${DB_NAME:-dokan_v2}"
DB_USER="${DB_USER:-dokan_v2}"
DB_PASS="${DB_PASS:-dokan_v2}"
PHP_VERSION="${PHP_VERSION:-8.3}"

echo "=================================================================="
echo " Multi-Vendor — macOS install"
echo " Project dir : $APP_DIR"
echo " Domain      : $DOMAIN  (tenants will resolve as *.${DOMAIN})"
echo " Database    : $DB_NAME"
echo "=================================================================="
cd "$APP_DIR"

# ── 1. Homebrew ───────────────────────────────────────────────────────────
if ! command -v brew >/dev/null 2>&1; then
    echo "-> Installing Homebrew..."
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    eval "$(/opt/homebrew/bin/brew shellenv 2>/dev/null || /usr/local/bin/brew shellenv)"
fi
echo "-> Homebrew ready ($(brew --version | head -1))"

# ── 2. PHP + extensions ──────────────────────────────────────────────────
if ! brew list "php@${PHP_VERSION}" >/dev/null 2>&1; then
    echo "-> Installing PHP ${PHP_VERSION}..."
    brew install "php@${PHP_VERSION}"
fi
brew link --overwrite --force "php@${PHP_VERSION}"
php -v

# ── 3. Composer ───────────────────────────────────────────────────────────
if ! command -v composer >/dev/null 2>&1; then
    echo "-> Installing Composer..."
    brew install composer
fi
composer --version

# ── 4. Node.js ────────────────────────────────────────────────────────────
if ! command -v node >/dev/null 2>&1; then
    echo "-> Installing Node.js..."
    brew install node
fi
node --version
npm --version

# ── 5. MySQL ──────────────────────────────────────────────────────────────
if ! brew list mysql >/dev/null 2>&1; then
    echo "-> Installing MySQL..."
    brew install mysql
fi
brew services start mysql >/dev/null 2>&1 || true
sleep 2

# ── 6. Redis (optional, used for cache/queue if you switch off 'database') ─
if ! brew list redis >/dev/null 2>&1; then
    echo "-> Installing Redis..."
    brew install redis
fi
brew services start redis >/dev/null 2>&1 || true

# ── 7. Laravel Valet (local nginx + dnsmasq, gives wildcard *.loc domains) ─
if ! composer global show laravel/valet >/dev/null 2>&1; then
    echo "-> Installing Laravel Valet..."
    composer global require laravel/valet
fi

COMPOSER_BIN="$(composer global config bin-dir --absolute 2>/dev/null || echo "$HOME/.composer/vendor/bin")"
export PATH="$COMPOSER_BIN:$PATH"

if ! command -v valet >/dev/null 2>&1; then
    echo "ERROR: valet binary not found on PATH. Add this to your shell profile:"
    echo "  export PATH=\"$COMPOSER_BIN:\$PATH\""
    exit 1
fi

TLD="${DOMAIN##*.}"           # e.g. "loc" from "dokan-v2.loc"
BASE_DOMAIN="${DOMAIN%.*}"    # e.g. "dokan-v2"

echo "-> Running valet install (requires sudo password)..."
valet install
valet tld "$TLD" || true

echo "-> Parking this project's parent directory with Valet..."
PARENT_DIR="$(dirname "$APP_DIR")"
(cd "$PARENT_DIR" && valet park) || true

echo "-> Linking $APP_DIR as $BASE_DOMAIN.$TLD ..."
(cd "$APP_DIR" && valet link "$BASE_DOMAIN") || true

echo "-> Securing with a trusted local TLS cert (https://$DOMAIN)..."
(cd "$APP_DIR" && valet secure "$BASE_DOMAIN") || true

echo
echo "NOTE: Wildcard subdomains for tenants (e.g. store1.$DOMAIN)"
echo "      are resolved automatically by Valet's dnsmasq '*.${TLD}' entry."
echo "      No extra DNS/hosts edits are needed for *.${TLD} on this machine."
echo

# ── 8. PHP dependencies ───────────────────────────────────────────────────
echo "-> composer install"
composer install --optimize-autoloader

# ── 9. .env setup ─────────────────────────────────────────────────────────
if [ ! -f .env ]; then
    echo "-> Creating .env from .env.example"
    cp .env.example .env
else
    echo "-> .env already exists, leaving it untouched"
fi

set_env() {
    local key="$1" value="$2"
    if grep -q "^${key}=" .env; then
        sed -i '' "s#^${key}=.*#${key}=${value}#" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

set_env "APP_URL" "https://${DOMAIN}"
set_env "SESSION_DOMAIN" ".${DOMAIN}"
set_env "DB_DATABASE" "${DB_NAME}"
set_env "DB_USERNAME" "${DB_USER}"
set_env "DB_PASSWORD" "${DB_PASS}"

# ── 10. Database ──────────────────────────────────────────────────────────
echo "-> Creating database and user (if missing)..."
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ── 11. App key, migrations, seeders ─────────────────────────────────────
php artisan key:generate --force --no-interaction
php artisan migrate --force --no-interaction
php artisan db:seed --force --no-interaction
php artisan storage:link --force

# ── 12. JS dependencies + asset build ────────────────────────────────────
echo "-> npm install && npm run build"
npm install
npm run build

# ── 13. Cache warm-up ─────────────────────────────────────────────────────
php artisan optimize:clear
php artisan config:cache
php artisan event:cache

chmod -R 775 storage bootstrap/cache

# ── 14. Queue worker + scheduler via launchd (macOS's cron replacement) ──
LAUNCH_AGENTS="$HOME/Library/LaunchAgents"
mkdir -p "$LAUNCH_AGENTS"

QUEUE_PLIST="$LAUNCH_AGENTS/com.dokan-v2.queue.plist"
cat > "$QUEUE_PLIST" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key><string>com.dokan-v2.queue</string>
    <key>ProgramArguments</key>
    <array>
        <string>$(command -v php)</string>
        <string>${APP_DIR}/artisan</string>
        <string>queue:work</string>
        <string>database</string>
        <string>--sleep=3</string>
        <string>--tries=3</string>
        <string>--timeout=120</string>
    </array>
    <key>WorkingDirectory</key><string>${APP_DIR}</string>
    <key>RunAtLoad</key><true/>
    <key>KeepAlive</key><true/>
    <key>StandardOutPath</key><string>${APP_DIR}/storage/logs/queue.log</string>
    <key>StandardErrorPath</key><string>${APP_DIR}/storage/logs/queue-error.log</string>
</dict>
</plist>
PLIST

SCHEDULER_PLIST="$LAUNCH_AGENTS/com.dokan-v2.scheduler.plist"
cat > "$SCHEDULER_PLIST" <<PLIST
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
    <key>Label</key><string>com.dokan-v2.scheduler</string>
    <key>ProgramArguments</key>
    <array>
        <string>$(command -v php)</string>
        <string>${APP_DIR}/artisan</string>
        <string>schedule:run</string>
    </array>
    <key>WorkingDirectory</key><string>${APP_DIR}</string>
    <key>StartInterval</key><integer>60</integer>
    <key>StandardOutPath</key><string>${APP_DIR}/storage/logs/scheduler.log</string>
    <key>StandardErrorPath</key><string>${APP_DIR}/storage/logs/scheduler-error.log</string>
</dict>
</plist>
PLIST

echo "-> Loading queue worker + scheduler launchd agents..."
launchctl unload "$QUEUE_PLIST" >/dev/null 2>&1 || true
launchctl unload "$SCHEDULER_PLIST" >/dev/null 2>&1 || true
launchctl load "$QUEUE_PLIST"
launchctl load "$SCHEDULER_PLIST"

echo
echo "=================================================================="
echo " Install complete!"
echo "=================================================================="
echo
echo "Central app:      https://${DOMAIN}"
echo "Tenant example:    https://store1.${DOMAIN}   (create the tenant/domain in-app first)"
echo
echo "Registering a NEW tenant subdomain:"
echo "  Tenants are created through the app (e.g. vendor onboarding), which"
echo "  writes a row to the 'domains' table (store2.${DOMAIN}, etc.)."
echo "  Because Valet's dnsmasq answers '*.${TLD}' for this machine, the new"
echo "  subdomain resolves immediately — no extra DNS or /etc/hosts edit needed."
echo "  Verify any time with:  dscacheutil -q host -a name store2.${DOMAIN}"
echo
echo "Registering a NEW top-level domain (not just a *.${TLD} subdomain):"
echo "  1. valet link <folder-name> --secure"
echo "  2. Add the domain to config/tenancy.php -> central_domains (if it's a central domain)"
echo "  3. php artisan config:cache"
echo
echo "Useful commands:"
echo "  php artisan tenants:migrate --force        # migrate all tenant DBs"
echo "  php artisan tenants:seed --tenants=ID       # seed one tenant"
echo "  php artisan queue:restart                   # after deploying new code"
echo "  launchctl list | grep dokan-v2               # check queue/scheduler status"
echo "  tail -f storage/logs/queue.log                # queue worker output"
echo
echo "Start Reverb (websockets), in a separate terminal:"
echo "  php artisan reverb:start"
echo
