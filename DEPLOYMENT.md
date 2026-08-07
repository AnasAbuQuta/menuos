# MenuOS deployment on Render

This guide deploys the monorepo as three Render resources: a PostgreSQL database, a Docker Web Service for Laravel, and a Static Site for Vue. Production secrets belong in the Render dashboard and must never be committed.

## 1. Create PostgreSQL

1. In Render, choose **New > Postgres**.
2. Name it `menuos-postgres` and select the same region as the backend service.
3. After creation, copy the **Internal Database URL**. Use the internal URL for the backend because it stays on Render's private network.
4. Keep the database and backend in the same Render region.

MenuOS migrations use Laravel's portable schema builder and are compatible with PostgreSQL. The production container includes `pdo_pgsql`. Do not set `DB_CONNECTION=sqlite` in production.

## 2. Create the Laravel backend Web Service

1. Choose **New > Web Service**, connect the MenuOS GitHub repository, and select the deployment branch (normally `main` after this feature branch is reviewed and merged).
2. Set **Root Directory** to `backend`.
3. Select the **Docker** runtime. The Dockerfile path is `Dockerfile`, relative to the backend root.
4. Choose the instance type that fits the environment. The free plan is supported without Shell or pre-deploy commands.
5. Set **Health Check Path** to `/up`.
6. Leave **Pre-Deploy Command** empty. The production entrypoint runs `php artisan migrate --force` before starting the web server.
7. Leave **Docker Command** empty so Render uses the Dockerfile entrypoint. Do not place shell operators such as `&&` in this field.

The entrypoint validates production configuration, prepares Laravel's writable directories, runs pending migrations, optionally refreshes the demo restaurant, creates `public/storage` only when safe, caches configuration/routes/views, and starts Nginx with PHP-FPM. Nginx listens on `0.0.0.0:$PORT`; Render supplies `PORT` (normally `10000`).

### Backend environment variables

Set these in the backend Web Service's **Environment** page:

| Variable | Production value |
| --- | --- |
| `APP_NAME` | `MenuOS` |
| `APP_ENV` | `production` |
| `APP_KEY` | Generate locally with `php artisan key:generate --show`; store only in Render |
| `APP_DEBUG` | `false` |
| `APP_URL` | Backend HTTPS URL, for example `https://menuos-api.onrender.com` |
| `FRONTEND_URL` | Exact frontend origin, for example `https://menuos.onrender.com`; comma-separate additional trusted origins only when needed |
| `PUBLIC_FRONTEND_URL` | Public frontend HTTPS origin, for example `https://menuos.onrender.com` |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Render PostgreSQL **Internal Database URL** (secret) |
| `DB_SSLMODE` | `prefer` for Render's internal connection; use `require` for an external connection |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `warning` |
| `SESSION_DRIVER` | `database` |
| `SESSION_ENCRYPT` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `public` when using the persistent disk described below |
| `SANCTUM_TOKEN_EXPIRATION` | `10080` (or your chosen token lifetime in minutes) |
| `SEED_DEMO_RESTAURANT` | `true` to keep the Bella Pasta public demo and its bundled images available; otherwise `false` |

`DB_URL` supplies all PostgreSQL connection fields, so do not also set stale `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, or `DB_PASSWORD` values. Never paste secrets into `.env.production.example` or commit a real `.env` file.

### Persistent uploads

Render service filesystems are ephemeral. Restaurant logos, covers, and menu item images will be lost on redeploy unless storage is persistent. Add a Render Persistent Disk mounted at:

```text
/var/www/html/storage/app/public
```

Choose capacity appropriate for expected uploads. The container creates Laravel's `public/storage` symlink at startup. Object storage can replace the disk later, but that requires an application/storage configuration change outside this deployment-only task.

## 3. Create the Vue frontend Static Site

1. Choose **New > Static Site** and connect the same repository.
2. Set **Root Directory** to `frontend`.
3. Set **Build Command** to:

   ```bash
   npm ci && npm run build
   ```

4. Set **Publish Directory** to `dist`.
5. Add this environment variable before the first build:

   | Variable | Value |
   | --- | --- |
   | `VITE_API_URL` | Backend API base URL, for example `https://menuos-api.onrender.com/api/v1` |

6. Add a **Rewrite** rule so Vue Router handles direct navigation:

   | Source | Destination | Action |
   | --- | --- | --- |
   | `/*` | `/index.html` | `Rewrite` |

7. Deploy the Static Site, then copy its final HTTPS origin into the backend's `FRONTEND_URL` and `PUBLIC_FRONTEND_URL` values and redeploy the backend.

`VITE_API_URL` is compiled into the frontend bundle. Rebuild the Static Site whenever this value changes. It is public configuration and must not contain secrets.

## 4. CORS and URL checklist

- `FRONTEND_URL` controls the Laravel CORS allowlist. Use origins only: include the scheme and host, with no path and preferably no trailing slash.
- For both a Render URL and a custom domain during transition, set a comma-separated list such as `https://menuos.onrender.com,https://menu.example.com`.
- `APP_URL` must be the public backend HTTPS origin and must not include `/api/v1`.
- `PUBLIC_FRONTEND_URL` must be the public customer-facing frontend origin; it is used for generated menu, QR, and sitemap URLs.
- `VITE_API_URL` must include the backend `/api/v1` prefix.
- After changing any backend URL variable, redeploy so the entrypoint regenerates Laravel's configuration cache.

## 5. Verify the deployment

1. Open `https://<backend-host>/up` and confirm HTTP `200`.
2. Open `https://<backend-host>/api/v1/public/menu/<restaurant-slug>` and confirm the API returns the active restaurant menu.
3. Open the frontend, register or log in, and verify protected API requests succeed without CORS errors.
4. Open a public menu in a private browser window and verify images load from the backend's `/storage/...` URLs.
5. Trigger a manual backend deploy and confirm the uploaded images remain available from the persistent disk.
6. Check the Render deploy log for successful Laravel cache commands and both `nginx` and `php-fpm` processes.

## Rollback and migration safety

Render preserves the last successful service deployment when a pre-deploy command fails. Database schema changes are not automatically rolled back when application code is rolled back, so take a PostgreSQL backup before destructive migrations and design migrations to remain compatible with the preceding application version.
