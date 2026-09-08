# 01 — System Dependencies

Run once on a fresh Ubuntu VPS (PHP and MySQL already installed — only install what is missing).

## PHP 8.3 Extensions

```bash
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring \
    php8.3-xml php8.3-bcmath php8.3-curl php8.3-zip php8.3-gd php8.3-intl \
    php8.3-redis php8.3-tokenizer php8.3-fileinfo
```

## Apache Modules

```bash
sudo apt install -y libapache2-mod-fcgid
sudo a2enmod proxy_fcgi setenvif rewrite headers proxy proxy_http
sudo a2enconf php8.3-fpm
sudo systemctl restart apache2
```

## Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

## Node.js 22 (required for Vite asset build)

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
node --version
npm --version
```

## Python 3 + pip + venv

```bash
sudo apt install -y python3 python3-pip python3-venv
python3 --version
```
