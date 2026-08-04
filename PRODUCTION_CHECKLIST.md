# MenuOS Production Checklist

## Before deployment

- [ ] Release tag reviewed and checks green.
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, correct HTTPS URLs and timezone.
- [ ] Unique production `APP_KEY` stored in the secret manager and recovery documentation.
- [ ] MySQL uses strict mode, `utf8mb4`, TLS where available, and least privilege.
- [ ] CORS contains exact frontend origins only.
- [ ] Public storage is persistent and included in backups.
- [ ] SMTP sender is verified or mail remains intentionally disabled.
- [ ] `SANCTUM_TOKEN_PREFIX=menuos_` and token expiration approved.
- [ ] `VITE_API_URL` and `VITE_PUBLIC_URL` set before build.
- [ ] Database and uploads backup completed and recovery point recorded.

## Deployment

- [ ] `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`.
- [ ] `npm ci` and `npm run build`.
- [ ] `php artisan migrate --force`.
- [ ] `php artisan storage:link` verified or S3 configured.
- [ ] `php artisan optimize` completed.
- [ ] Queue worker supervised and restarted.
- [ ] Frontend SPA fallback and `/sitemap.xml` proxy configured.

## Smoke test

- [ ] `/up` returns 200 and external monitoring sees it.
- [ ] Landing page works in Arabic/English, mobile/tablet/desktop, RTL/LTR.
- [ ] Register, login, `me`, logout, and protected-route redirect work.
- [ ] Restaurant setup, images, themes, opening hours, categories, and menu items work.
- [ ] Public menu, search, category navigation, cart, QR attribution, phone, and WhatsApp work.
- [ ] Dashboard analytics appear without exposing visitor identifiers.
- [ ] Bella Pasta public URL works when demo seeding is enabled.
- [ ] 404, unauthorized, network, server, and offline states are branded.
- [ ] Canonical, Open Graph, Twitter, JSON-LD, robots, sitemap, manifest, and service worker verified.

## Operations

- [ ] Daily database and uploads backups configured with retention and off-site copies.
- [ ] Quarterly restore drill scheduled.
- [ ] Log rotation, disk alerts, exception alerts, uptime, latency, queue depth, MySQL, CPU, and memory monitoring configured.
- [ ] Certificate and domain-expiration alerts configured.
- [ ] Owner for incidents and rollback authority documented.
