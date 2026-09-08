# 05 — Apache Virtual Host

## Create VHost File

```bash
sudo nano /etc/apache2/sites-available/multi-vendor.conf
```

Paste the content from `configs/apache-vhost.conf` (edit `yourdomain.com` to your real domain).

## Enable Site

```bash
sudo a2ensite multi-vendor.conf
sudo a2dissite 000-default.conf    # disable default if not using it
sudo apache2ctl configtest          # verify no syntax errors
sudo systemctl reload apache2
```

## Wildcard Subdomains (stancl/tenancy)

stancl/tenancy uses subdomains per tenant (e.g., `store1.yourdomain.com`).

1. In your DNS provider add an `A` record:
   ```
   *   A   YOUR_SERVER_IP
   ```
2. The Apache VHost already has `ServerAlias *.yourdomain.com` to handle all subdomains.

## PHP-FPM Verification

```bash
sudo systemctl status php8.3-fpm
# Should show "active (running)"

# Test PHP is processing correctly
echo "<?php phpinfo();" | sudo tee /var/www/multi-vendor/public/info.php
# Visit https://yourdomain.com/info.php — then DELETE it:
sudo rm /var/www/multi-vendor/public/info.php
```

## Apache Logs

```bash
tail -f /var/log/apache2/multi-vendor-error.log
tail -f /var/log/apache2/multi-vendor-access.log
```
