# 08 — SSL (Let's Encrypt)

## Install Certbot

```bash
sudo apt install -y certbot python3-certbot-apache
```

## Single Domain Certificate

```bash
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
```

## Wildcard Certificate (required for tenant subdomains)

Wildcard certs require DNS challenge — you must add a TXT record in your DNS provider.

```bash
sudo certbot certonly \
    --manual \
    --preferred-challenges=dns \
    -d yourdomain.com \
    -d "*.yourdomain.com"
```

Certbot will show you a DNS TXT record to add:
```
_acme-challenge.yourdomain.com   TXT   "some-random-value"
```

Add it in your DNS provider, wait ~1 minute, then press Enter to continue.

## Auto-Renewal

Certbot installs a systemd timer by default. Verify it:

```bash
sudo systemctl status certbot.timer
sudo certbot renew --dry-run    # test renewal works
```

## Force Apache to Use HTTPS

After certbot runs, your VHost will have an HTTP→HTTPS redirect added automatically. Verify:

```bash
sudo apache2ctl configtest
sudo systemctl reload apache2
```

## Check Certificate Expiry

```bash
sudo certbot certificates
# or
openssl s_client -connect yourdomain.com:443 -servername yourdomain.com 2>/dev/null | openssl x509 -noout -dates
```
