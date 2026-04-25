# Google Reviews + Cache diaria

## 1) Configurar credenciales
Define estas variables de entorno en tu hosting:

- `GOOGLE_BUSINESS_CLIENT_ID`
- `GOOGLE_BUSINESS_CLIENT_SECRET`
- `GOOGLE_BUSINESS_REFRESH_TOKEN`
- `GOOGLE_BUSINESS_ACCOUNT_ID`
- `GOOGLE_BUSINESS_LOCATION_ID`
- `GOOGLE_REVIEWS_CACHE_TTL` (opcional, default `86400`)

El proyecto usa `config/google_reviews.php` para leerlas.

## 2) Endpoint publico interno
Frontend consume:

- `app/api/public/get_google_reviews.php`

Este endpoint responde desde cache y solo consulta Google cuando la cache expira.

## 3) Actualizacion manual/cron
Script CLI:

- `app/services/refresh_google_reviews_cache.php`

Ejemplo (Linux/cPanel cron, una vez al dia):

```bash
/usr/local/bin/php /home/USER/public_html/app/services/refresh_google_reviews_cache.php
```

Ejemplo (Windows Task Scheduler):

```powershell
php D:\PROGRAMASAO\MySuite\software_apto_php\app\services\refresh_google_reviews_cache.php
```

## 4) Cache
Se guarda en:

- `logs/cache/google_reviews.json`

Si Google falla, se sirve cache anterior (stale cache) para no romper la web.

