# Multi-Vendor Python AI Modules

FastAPI microservice that complements the Laravel multi-vendor app with two AI
features:

1. **`POST /price`** — given a product name and/or a product/variant image,
   discovers average/min/max prices by scraping top shopping results from the
   public web. Text-only search needs no API key; image search additionally
   uses OpenAI vision (`OPENAI_API_KEY`) to identify the product from the image
   before searching.
2. **`POST /social-post`** — given a product title, description, and target
   platform, generates ready-to-publish social media captions + a companion
   image using OpenAI (ChatGPT + DALL·E).

> Call these endpoints from Laravel using `Http::post('http://127.0.0.1:8008/price', [...])`.

---

## 1. Local setup

```bash
cd /var/www/multi-vendor/python-modules

# 1. Create a virtual environment (Python 3.10+ recommended)
python3 -m venv venv
source venv/bin/activate

# 2. Install dependencies
pip install --upgrade pip
pip install -r requirements.txt

# 3. Configure environment
cp .env.example .env
# then edit .env and paste your OPENAI_API_KEY
```

## 2. Run in development

```bash
source venv/bin/activate
uvicorn app:app --host 0.0.0.0 --port 8008 --reload
```

Visit `http://127.0.0.1:8008/docs` for the interactive Swagger UI.

## 3. Test the endpoints

```bash
# Health check
curl http://127.0.0.1:8008/health

# Price lookup (text only)
curl -X POST http://127.0.0.1:8008/price \
     -H "Content-Type: application/json" \
     -d '{"product_name": "iPhone 15 Pro 256GB", "limit": 8}'

# Price lookup by image (absolute local path or public URL), optionally with a
# text hint to help the vision lookup
curl -X POST http://127.0.0.1:8008/price \
     -H "Content-Type: application/json" \
     -d '{"image_path": "/var/www/multi-vendor/storage/app/public/products/abc.jpg", "product_name": "wireless earbuds"}'

# Social post generation (needs OPENAI_API_KEY)
curl -X POST http://127.0.0.1:8008/social-post \
     -H "Content-Type: application/json" \
     -d '{
           "title": "Handcrafted Leather Wallet",
           "description": "Full-grain leather, 6 card slots, RFID-blocking, slim design.",
           "platform": "all",
           "include_image": true
         }'
```

If `SERVICE_TOKEN` is set in `.env`, add:
`-H "X-Service-Token: <your-token>"` to every request.

---

## 4. Production deployment (Linux / systemd)

### 4.1 Install onto the server

```bash
sudo mkdir -p /var/www/multi-vendor/python-modules
# ... copy/pull the python-modules folder ...
cd /var/www/multi-vendor/python-modules
python3 -m venv venv
source venv/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
cp .env.example .env && nano .env    # set OPENAI_API_KEY + SERVICE_TOKEN
```

### 4.2 Create a systemd unit

`sudo nano /etc/systemd/system/multi-vendor-ai.service`

```ini
[Unit]
Description=Multi-Vendor AI Modules (FastAPI)
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/multi-vendor/python-modules
EnvironmentFile=/var/www/multi-vendor/python-modules/.env
ExecStart=/var/www/multi-vendor/python-modules/venv/bin/uvicorn app:app \
          --host 127.0.0.1 --port 8008 --workers 2
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

Enable & start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now multi-vendor-ai
sudo systemctl status multi-vendor-ai
```

Logs:

```bash
journalctl -u multi-vendor-ai -f
```

### 4.3 (Optional) Expose via Nginx

```nginx
# /etc/nginx/sites-available/multi-vendor-ai
server {
    listen 80;
    server_name ai.your-domain.com;

    location / {
        proxy_pass         http://127.0.0.1:8008;
        proxy_http_version 1.1;
        proxy_set_header   Host              $host;
        proxy_set_header   X-Real-IP         $remote_addr;
        proxy_set_header   X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
        proxy_read_timeout 120s;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/multi-vendor-ai /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
# add HTTPS via certbot:
sudo certbot --nginx -d ai.your-domain.com
```

### 4.4 Calling from Laravel

Add to `.env` of the Laravel app:

```env
AI_SERVICE_URL=http://127.0.0.1:8008
AI_SERVICE_TOKEN=<same-value-as-python-.env>
```

Example usage:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'X-Service-Token' => config('services.ai.token'),
])->post(config('services.ai.url') . '/price', [
    'product_name' => 'Galaxy S24 Ultra 512GB',
]);

$avg = $response->json('average_price');
```

Add to `config/services.php`:

```php
'ai' => [
    'url'   => env('AI_SERVICE_URL', 'http://127.0.0.1:8008'),
    'token' => env('AI_SERVICE_TOKEN'),
],
```

---

## 5. Notes & limitations

- **Price finder** relies on DuckDuckGo HTML search + unauthenticated page
  fetches. Results are heuristic; outliers are trimmed (values more than 3× the
  median are dropped). Some sites block bots — those simply produce fewer hits.
- **Price finder image search** requires `OPENAI_API_KEY`. Prefer passing an
  absolute local filesystem path in `image_path` (read directly, no network
  hop); a public URL also works as long as it is reachable both by this
  service and by OpenAI.
- **Social post generator** requires a paid OpenAI account. DALL·E 3 image
  generation is billed per image.
- Never expose this service directly to the public internet without setting
  `SERVICE_TOKEN` and/or restricting access at the network layer.
- The service is stateless — scale horizontally by running more workers/replicas.
