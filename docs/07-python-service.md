# 07 — Python FastAPI Service

## Setup Virtual Environment

```bash
cd /var/www/multi-vendor/python-modules

python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
deactivate
```

## Create Python .env File

```bash
nano /var/www/multi-vendor/python-modules/.env
```

```env
SERVICE_TOKEN=your_secret_token_here
OPENAI_API_KEY=sk-your-openai-key
LOG_LEVEL=INFO
# ALLOWED_ORIGINS=https://yourdomain.com
```

## Install as Systemd Service

```bash
sudo cp /var/www/multi-vendor/docs/configs/python-modules.service /etc/systemd/system/

sudo systemctl daemon-reload
sudo systemctl enable python-modules
sudo systemctl start python-modules
sudo systemctl status python-modules
```

## Test the API

```bash
# Health check (should return {"status":"ok"})
curl http://127.0.0.1:8001/health

# Price finder test
curl -X POST http://127.0.0.1:8001/price \
    -H "Content-Type: application/json" \
    -d '{"product_name": "iPhone 15", "limit": 5}'
```

## Apache Proxy (expose via your domain)

Add to your Apache VHost or create a separate one:

```apache
ProxyPass /python-api/ http://127.0.0.1:8001/
ProxyPassReverse /python-api/ http://127.0.0.1:8001/
```

Then access via: `https://yourdomain.com/python-api/health`

## Logs

```bash
tail -f /var/log/python-modules.log
tail -f /var/log/python-modules-error.log
```

## Restart After Changes

```bash
sudo systemctl restart python-modules
```
