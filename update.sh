php artisan migrate
php artisan tenants:migrate
# sudo systemctl restart supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all
npm install
npm run build
php artisan optimize:clear
chmod -R 777 storage/
chmod -R 777 bootstrap/cache/
