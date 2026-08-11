cp .env.example .env

yarn
yarn build

php artisan optimize:clear
php artisan key:generate --force --no-interaction
php artisan migrate --force --no-interaction --seed
php artisan storage:link --force

php artisan config:cache
if php artisan route:cache; then
    echo "Routes cached."
else
    echo "Skipping route cache because this project contains closure routes."
fi
php artisan view:cache
php artisan event:cache

chmod -R 775 storage bootstrap/cache

systemctl enable apache2 mysql supervisor >/dev/null
systemctl restart mysql
systemctl restart apache2

# php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=120 --max-time=3600

# php artisan queue:work database --queue=translations --sleep=3 --tries=1 --timeout=300 --max-time=3600

# php artisan queue:work database --queue=tenant-sync --sleep=3 --tries=1 --timeout=600 --max-time=3600

systemctl restart supervisor
supervisorctl reread
supervisorctl update

echo
echo "Supervisor status:"
supervisorctl status
echo
echo "This project uses these runtime commands:"
echo "  php artisan subscriptions:send-renewal-reminders --days=7"
echo "  php artisan currencies:update-rates"
echo "  php artisan tenants:sync-data --all --ensure-database"
echo
echo "This project uses these queues:"
echo "  default"
echo "  translations"
echo "  tenant-sync"
echo
echo "After editing .env, run: php artisan config:cache"
