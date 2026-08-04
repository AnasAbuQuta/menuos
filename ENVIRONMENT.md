# MenuOS Production Environment

Start from `backend/.env.production.example` and `frontend/.env.production.example`. Never commit the resulting `.env` files.

## Application

| Variable | Required | Production value / purpose |
|---|---:|---|
| `APP_NAME` | Yes | `MenuOS` |
| `APP_ENV` | Yes | `production` |
| `APP_KEY` | Yes | Generate once with `php artisan key:generate`; back it up securely. |
| `APP_DEBUG` | Yes | `false` to prevent stack traces and configuration disclosure. |
| `APP_URL` | Yes | HTTPS backend origin, without a trailing slash. |
| `PUBLIC_FRONTEND_URL` | Yes | HTTPS frontend origin used by QR codes and sitemap URLs. |
| `FRONTEND_URL` | Yes | Comma-separated exact HTTPS origins allowed by CORS. Never use `*`. |
| `APP_TIMEZONE` | Yes | IANA timezone used for opening-hours calculations, for example `Asia/Riyadh`. |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | Yes | Application fallback locales. |
| `APP_MAINTENANCE_DRIVER`, `APP_MAINTENANCE_STORE` | Yes | Use `cache` / `database` for multi-process deployments. |
| `APP_PREVIOUS_KEYS` | No | Comma-separated previous keys during controlled key rotation. |
| `BCRYPT_ROUNDS` | Yes | `12` or a measured production-safe cost. |

## Database, cache, queues, and sessions

| Variable | Required | Production value / purpose |
|---|---:|---|
| `DB_CONNECTION` | Yes | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Yes | Least-privileged MySQL credentials. |
| `DB_CHARSET`, `DB_COLLATION` | Yes | `utf8mb4` / `utf8mb4_unicode_ci`. |
| `MYSQL_ATTR_SSL_CA` | When supported | CA bundle path for TLS database connections. |
| `CACHE_STORE` | Yes | `database`; Redis may be adopted later without code changes. |
| `CACHE_PREFIX` | Yes | Unique per environment. |
| `QUEUE_CONNECTION` | Yes | `database`; run a supervised queue worker. |
| `SESSION_DRIVER` | Yes | `database`. Token authentication does not depend on browser sessions, but Laravel still requires a safe session configuration. |
| `SESSION_ENCRYPT` | Yes | `true` |
| `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY` | Yes | `true` |
| `SESSION_SAME_SITE` | Yes | `lax` for the separate token-based Vue frontend. |
| `SESSION_DOMAIN` | No | Leave `null` unless intentionally sharing cookies. |

## Files, mail, logs, and Sanctum

| Variable | Required | Production value / purpose |
|---|---:|---|
| `FILESYSTEM_DISK` | Yes | `public` for a single server. Use `s3` plus the documented AWS variables for horizontally scaled deployments. |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT` | For S3 | Object-storage credentials and public URL. |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` | Yes when mail is enabled | SMTP transport. MenuOS currently has no transactional mail workflow. |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Yes | Verified sender identity. |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL`, `LOG_DAILY_DAYS` | Yes | Recommended: `stack`, `daily`, `warning`, `14`. Forward logs at the platform layer if required. |
| `SANCTUM_TOKEN_EXPIRATION` | Yes | Minutes before bearer tokens expire; default `10080` (7 days). |
| `SANCTUM_TOKEN_PREFIX` | Yes | `menuos_` to improve secret-scanner detection. |

`SANCTUM_STATEFUL_DOMAINS` is not required for the current bearer-token frontend. `supports_credentials` remains disabled in CORS.

## Demo and frontend build

| Variable | Required | Production value / purpose |
|---|---:|---|
| `SEED_DEMO_RESTAURANT` | No | Set `true` only for the deployment that should host Bella Pasta. |
| `DEMO_OWNER_EMAIL` | With demo | Demo owner email. |
| `DEMO_OWNER_PASSWORD` | Optional | Set a strong secret if owner login is required. If omitted, the seeder generates an unknown random password. |
| `VITE_API_URL` | Yes | Full HTTPS API prefix, e.g. `https://api.example.com/api/v1`. |
| `VITE_PUBLIC_URL` | Yes | Public frontend origin used to generate `robots.txt`. |
| `VITE_APP_NAME` | Yes | `MenuOS`. |

Vite variables are embedded at build time. Rebuild the frontend after changing them.
