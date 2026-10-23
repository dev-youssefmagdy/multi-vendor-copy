php artisan migrate
php artisan tenants:migrate

php artisan db:seed --class=AdminPermissionSeeder --force --no-interaction
php artisan tenants:seed --class=TenantPermissionSeeder --force --no-interaction


# sudo systemctl restart supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

npm install
npm run build

php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

chmod -R 777 storage/
chmod -R 777 bootstrap/cache/
