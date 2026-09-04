#!/usr/bin/env bash
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

APP_DIR=/var/www/garment-erp
APP_URL=http://82.29.167.99/manufacturing
GIT_REPO=https://github.com/Arshath0708/garment-erp.git
GIT_BRANCH=main
WEB_PATH=/manufacturing

echo "==> ensure Node 20 (for Vite)"
if ! node -v 2>/dev/null | grep -qE 'v2[0-9]\.'; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
fi

echo "==> clone/update app"
mkdir -p /var/www
if [ -d "$APP_DIR/.git" ]; then
  cd "$APP_DIR"
  git fetch --all --prune
  git checkout "$GIT_BRANCH"
  git reset --hard "origin/$GIT_BRANCH"
else
  rm -rf "$APP_DIR"
  git clone --branch "$GIT_BRANCH" "$GIT_REPO" "$APP_DIR"
  cd "$APP_DIR"
fi

composer install --no-dev --optimize-autoloader --no-interaction

if [ ! -f .env ]; then
  cp .env.example .env
fi

php artisan key:generate --force || true

python3 - <<'PY'
from pathlib import Path
p = Path('.env')
text = p.read_text()
repl = {
    'APP_NAME': '"Garment ERP"',
    'APP_ENV': 'production',
    'APP_DEBUG': 'false',
    'APP_URL': 'http://82.29.167.99/manufacturing',
    'ASSET_URL': 'http://82.29.167.99/manufacturing',
    'DB_CONNECTION': 'sqlite',
    'SESSION_DRIVER': 'file',
    'SESSION_PATH': '/manufacturing',
    'SESSION_DOMAIN': 'null',
    'LOG_LEVEL': 'error',
    'CACHE_STORE': 'file',
    'QUEUE_CONNECTION': 'sync',
}
lines = text.splitlines()
keys = set()
out = []
for line in lines:
    if not line or line.startswith('#') or '=' not in line:
        out.append(line)
        continue
    k = line.split('=', 1)[0]
    if k in repl:
        out.append(f"{k}={repl[k]}")
        keys.add(k)
    else:
        out.append(line)
for k, v in repl.items():
    if k not in keys:
        out.append(f"{k}={v}")
final = []
for line in out:
    if line.startswith(('DB_HOST=', 'DB_PORT=', 'DB_DATABASE=', 'DB_USERNAME=', 'DB_PASSWORD=')):
        if not line.startswith('#'):
            final.append('#' + line)
        else:
            final.append(line)
    else:
        final.append(line)
p.write_text('\n'.join(final) + '\n')
print('env updated')
PY

mkdir -p database
touch database/database.sqlite

php artisan storage:link || true
php artisan migrate --force
php artisan db:seed --force || true

npm install --no-audit --no-fund
npm run build

# Force root URL for subdirectory if not already patched
php -r '
$path="app/Providers/AppServiceProvider.php";
$c=file_get_contents($path);
if (strpos($c, "forceRootUrl") === false) {
  $c=preg_replace(
    "/public function boot\(\): void\s*\{/",
    "public function boot(): void\n    {\n        \\Illuminate\\Support\\Facades\\URL::forceRootUrl(config(\"app.url\"));",
    $c,
    1
  );
  file_put_contents($path, $c);
  echo "AppServiceProvider patched\n";
} else {
  echo "AppServiceProvider already patched\n";
}
'

php artisan config:cache
php artisan route:cache
php artisan view:cache

chown -R www-data:www-data "$APP_DIR"
chmod -R ug+rwx storage bootstrap/cache database

# Symlink into web root (helps PHP SCRIPT_FILENAME with alias)
mkdir -p /var/www/html
ln -sfn "$APP_DIR/public" /var/www/html/manufacturing

# Update nginx: keep guru-traders + add manufacturing
cat >/etc/nginx/sites-available/spirezen-apps <<'NGINX'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    client_max_body_size 40M;
    root /var/www/html;

    location = / {
        default_type text/html;
        return 200 '<!doctype html><html><body style="font-family:sans-serif;padding:40px"><h2>Spirezen Server</h2><ul><li><a href="/guru-traders/">Guru Traders ERP</a></li><li><a href="/manufacturing/">Garment / Manufacturing ERP</a></li></ul></body></html>';
    }

    location /guru-traders {
        alias /var/www/guru-traders-erp/public;
        try_files $uri $uri/ @guru_traders;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }
    }

    location @guru_traders {
        rewrite ^/guru-traders/(.*)$ /guru-traders/index.php?/$1 last;
    }

    location /manufacturing {
        alias /var/www/garment-erp/public;
        try_files $uri $uri/ @manufacturing;

        location ~ \.php$ {
            include snippets/fastcgi-php.conf;
            fastcgi_param SCRIPT_FILENAME $request_filename;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        }
    }

    location @manufacturing {
        rewrite ^/manufacturing/(.*)$ /manufacturing/index.php?/$1 last;
    }
}
NGINX

ln -sfn /etc/nginx/sites-available/spirezen-apps /etc/nginx/sites-enabled/spirezen-apps
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl enable --now php8.3-fpm
systemctl restart php8.3-fpm
systemctl restart nginx

echo "==> DONE"
curl -sI http://127.0.0.1/manufacturing/ | head -15
curl -sI http://127.0.0.1/guru-traders/ | head -8
curl -s http://127.0.0.1/manufacturing/login | head -c 400 || true
echo
