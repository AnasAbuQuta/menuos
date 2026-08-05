# MenuOS Deployment

## Platform requirements

- PHP 8.3+, Composer 2, required Laravel PHP extensions, and `pdo_mysql`.
- MySQL 8.0+ using `utf8mb4` and strict SQL mode.
- Node.js compatible with Vite 8 for build time only.
- HTTPS for the API and frontend.
- A persistent public uploads volume or S3-compatible object storage.
- A process supervisor for queue workers and scheduled commands.

## First deployment

1. Create the database and a least-privileged application user.
2. Copy `backend/.env.production.example` to `backend/.env` and fill every required value from `ENVIRONMENT.md`.
3. Install backend dependencies: `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`.
4. Generate `APP_KEY` once. Store it in the secret manager and backups; never rotate it casually.
5. Run `php artisan storage:link` for local public storage.
6. Run `php artisan migrate --force`.
7. Optionally provision Bella Pasta with `SEED_DEMO_RESTAURANT=true php artisan db:seed --force`.
8. Cache production bootstrap data: `php artisan optimize`.
9. Build the frontend with its production environment: `npm ci && npm run build`.
10. Serve `frontend/dist` from the frontend origin with SPA fallback to `index.html`. Proxy `/sitemap.xml` to the Laravel sitemap endpoint.
11. Start a supervised queue worker: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
12. Schedule `php artisan schedule:run` every minute if scheduled jobs are added.
13. Verify `/up`, `/sitemap.xml`, the landing page, Bella Pasta, authentication, uploads, and analytics.

## Updates

1. Put the application in maintenance mode only if the migration requires it: `php artisan down --retry=60`.
2. Back up the database and uploads as one recovery point.
3. Pull the immutable release artifact/tag.
4. Run Composer install, frontend build, and `php artisan migrate --force`.
5. Run `php artisan optimize`, then `php artisan queue:restart`.
6. Bring the application up and execute the production checklist.

## Rollback

- Prefer deploying the previous application artifact while keeping forward-compatible migrations.
- Never run `migrate:rollback` blindly in production. Review the exact migration and data-loss risk first.
- If a migration is not backward compatible, restore the paired pre-deploy database and uploads backup using `RESTORE.md`.
- Clear and rebuild caches after rollback: `php artisan optimize:clear && php artisan optimize`.

## Web server requirements

- Redirect HTTP to HTTPS.
- Add HSTS only after all subdomains are HTTPS-ready.
- Serve hashed Vite assets with a one-year immutable cache; serve `index.html` with no-cache/revalidation.
- Preserve the API security headers and configure a frontend Content Security Policy at the CDN/web server.
- Limit request bodies above the application image limit and protect `.env`, storage internals, and source files from public access.
