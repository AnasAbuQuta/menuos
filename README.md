# MenuOS

MenuOS is a commercial SaaS product for restaurant menu management. It includes owner authentication, restaurant-scoped management, branding, and a read-only public menu.

## Project structure

- `backend/` — Laravel application
- `frontend/` — Vue 3 application powered by Vite

## Prerequisites

- PHP 8.3 or newer
- Composer 2
- Node.js 24 or newer
- npm 11 or newer
- Git

## Install

Install the backend dependencies:

```bash
cd backend
composer install
```

Install the frontend dependencies:

```bash
cd frontend
npm install
```

The Laravel project already includes a local `.env` file for development. Create one from `.env.example` and generate an application key if it is missing:

```bash
cd backend
php artisan key:generate
php artisan migrate
php artisan storage:link
```

## Run locally

Start the Laravel development server:

```bash
cd backend
php artisan serve
```

In a second terminal, start the Vite development server:

```bash
cd frontend
npm run dev
```

By default, Laravel is available at `http://127.0.0.1:8000` and Vite at `http://localhost:5173`.

## Build the frontend

```bash
cd frontend
npm run build
```

Set `FRONTEND_URL` to the Vue origin, `PUBLIC_FRONTEND_URL` to the publicly reachable Vue origin, and `SANCTUM_TOKEN_EXPIRATION` to the desired token lifetime in minutes. Set `VITE_API_URL` in `frontend/.env` when the API is not available at `http://127.0.0.1:8000/api/v1`.

## Verify

```bash
cd backend
php artisan test
vendor/bin/pint --test

cd ../frontend
npm run lint
npm run build
```

## Current scope

Sprint 3 adds restaurant-scoped menu item CRUD, filtering, ordering, availability and featured flags, category assignment, and image upload. Menu item images are stored on Laravel's public disk under `menu-items/{restaurant_id}`; run `php artisan storage:link` once per deployment so URLs are publicly usable.

Deleting a restaurant cascades to its menu items. Deleting a category that still contains menu items returns HTTP `409`; items must be moved or deleted explicitly first. Menu-item updates accept JSON `PUT`, while image replacements use multipart `POST` with `_method=PUT`. Collections are intentionally unpaginated during the MVP and are expected to remain small.

QR codes, cart, ordering, analytics, variants, modifiers, inventory, subscriptions, branches, POS, and final dashboard features are intentionally not implemented.

Sprint 4 adds authenticated restaurant profile and brand settings. Restaurant slugs remain stable when names change. Logo and cover files use the public disk under `restaurants/{restaurant_id}/logo` and `restaurants/{restaurant_id}/cover`, with old files removed only after successful replacement.

Opening hours require all seven English day keys whenever the field is updated, with one opening and closing time for each open day. Overnight hours and multiple shifts are not supported yet. Supported MVP currencies are `ILS`, `USD`, and `JOD`.

Phone and WhatsApp normalization removes spaces, hyphens, and parentheses while preserving an optional leading `+`. MenuOS does not infer country codes or provide comprehensive international phone validation in this release.

## Public restaurant menus (Sprint 5)

Active restaurants are available without authentication at `GET /api/v1/public/menu/{slug}` and at the Vue route `/menu/{slug}`. The response includes only active categories containing available items, ordered by each record's `sort_order`. The public endpoint is limited to 60 requests per minute per IP and is intentionally unpaginated for moderate-sized MVP menus.

Open status uses Laravel's `APP_TIMEZONE` and the restaurant's single daily interval. Missing or invalid hours return an unknown status; overnight schedules remain unsupported. Image URLs require `php artisan storage:link` in each deployment.

Production hosting must send unknown frontend paths to `frontend/dist/index.html` so direct visits to `/menu/{slug}` work. For Nginx use `try_files $uri $uri/ /index.html`; for Apache enable the standard SPA rewrite; on Netlify add `/* /index.html 200`; on Vercel add an equivalent catch-all rewrite. Configure `VITE_API_URL` before building the frontend.

## QR codes and WhatsApp ordering (Sprint 6)

Authenticated owners can view and download a PNG QR code from `/qr-code`. Laravel generates it locally from `{PUBLIC_FRONTEND_URL}/menu/{restaurant-slug}` through `GET /api/v1/restaurant/qr-code`; deployment configuration must use a public HTTPS frontend URL so phones can open it.

Public-menu carts are client-side only and stored in `localStorage` under a restaurant-specific key. Stored identifiers and quantities are validated against the currently available public menu on every load, preventing stale items and cross-restaurant mixing. The cart is not an order record and is never submitted to MenuOS.

WhatsApp ordering opens `wa.me` with an encoded Arabic summary containing unit prices, quantities, line totals, the grand total, and an optional note. MenuOS strips formatting characters from the configured WhatsApp number but does not infer country codes. Opening WhatsApp does not clear the cart, and no payment, fulfillment, delivery, or internal order tracking is included.

## Product experience (Sprint 7)

The Vue application uses a lightweight in-house design system for buttons, fields, cards, badges, alerts, skeleton loading, empty states, modals, confirmations, and accessible toast feedback. Authenticated routes and public-menu routes are lazy-loaded into separate production chunks to reduce initial JavaScript work.

The owner dashboard summarizes restaurant status, setup completion, category and menu-item counts, and quick links without introducing analytics. The application includes dedicated not-found, unauthorized, and network-error experiences, keyboard focus trapping for modal dialogs, reduced-motion support, responsive owner navigation, touch-friendly controls, image fallbacks, and consistent mobile layouts.

## Bilingual content and direction (Sprint 8A)

Restaurant, category, and menu-item content can be stored independently in Arabic and English. At least one localized name is required; missing requested translations fall back to the other language and then to the legacy value. Existing records are backfilled into Arabic during migration. A restaurant's `default_language` controls public-menu responses when `?lang=ar` or `?lang=en` is omitted.

The Vue interface uses Vue I18n, persists the selected interface language in `localStorage`, and updates the document `lang` and `dir` attributes for LTR/RTL layout. Public menus request localized content from `GET /api/v1/public/menu/{slug}?lang={ar|en}`. Legacy `name` and `description` response fields remain available during this compatibility phase; new integrations should use the bilingual fields on authenticated resources and the localized public response.
