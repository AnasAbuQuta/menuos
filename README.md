# MenuOS

MenuOS is a bilingual digital-menu SaaS for restaurants. Owners manage restaurant branding, opening hours, categories, dishes, QR sharing, and anonymous guest analytics from a responsive Vue workspace. Guests browse a fast themed public menu, search dishes, build a local cart, and send their selection through WhatsApp.

## Stack

- Laravel 13, Sanctum token authentication, MySQL for production, SQLite for isolated development/tests.
- Vue 3, Vue Router, Pinia, Axios, Vite, Vue I18n, and a dependency-free chart layer.
- PWA manifest, versioned service worker, offline shell, dynamic restaurant SEO, and sitemap generation.

## Local development

Requirements: PHP 8.3+, Composer 2, Node.js 24+, npm 11+, and Git.

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan serve
```

In another terminal:

```bash
cd frontend
npm install
npm run dev
```

Laravel runs at `http://127.0.0.1:8000`; Vite runs at `http://localhost:5173`.

## Bella Pasta demo

The demo is opt-in and idempotent. It copies versioned optimized brand assets to the public disk and creates bilingual showcase categories and dishes.

```bash
cd backend
php artisan db:seed --class=DemoRestaurantSeeder
```

The menu is available at `/menu/bella-pasta`. Set `DEMO_OWNER_PASSWORD` before seeding only when an owner login is required; otherwise an unknown random password is stored.

## Verification

```bash
cd backend
php artisan test
vendor/bin/pint --test
composer audit --locked

cd ../frontend
npm run test
npm run lint
npm run build
npm audit --audit-level=high
```

## Production

Start with these launch documents:

- [Environment variables](ENVIRONMENT.md)
- [Deployment and rollback](DEPLOYMENT.md)
- [Production checklist](PRODUCTION_CHECKLIST.md)
- [Security review](SECURITY.md)
- [Backup strategy](BACKUP.md)
- [Restore procedure](RESTORE.md)
- [Monitoring recommendations](MONITORING.md)
- [Localization terminology and guard](LOCALIZATION.md)

Production hosting must use HTTPS, serve the Vue application with SPA fallback, persist public uploads or use object storage, supervise queue workers, and proxy the frontend `/sitemap.xml` path to Laravel's dynamic sitemap endpoint.

## Product boundaries

Version 1.0 does not include POS, inventory, internal order processing, payments, delivery, branches, accounting, subscriptions, or offline data synchronization. The WhatsApp cart remains client-side and MenuOS never records it as an order.
