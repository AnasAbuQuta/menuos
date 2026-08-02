# MenuOS

MenuOS is a commercial SaaS product for menu management. The current implementation includes token authentication, one restaurant per owner, and restaurant-scoped category management.

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

Set `FRONTEND_URL` to the Vue origin and `SANCTUM_TOKEN_EXPIRATION` to the desired token lifetime in minutes. Set `VITE_API_URL` in `frontend/.env` when the API is not available at `http://127.0.0.1:8000/api/v1`.

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

Public menus, QR codes, cart, WhatsApp ordering, analytics, variants, modifiers, inventory, subscriptions, branches, POS, and final dashboard features are intentionally not implemented.

Sprint 4 adds authenticated restaurant profile and brand settings. Restaurant slugs remain stable when names change. Logo and cover files use the public disk under `restaurants/{restaurant_id}/logo` and `restaurants/{restaurant_id}/cover`, with old files removed only after successful replacement.

Opening hours require all seven English day keys whenever the field is updated, with one opening and closing time for each open day. Overnight hours and multiple shifts are not supported yet. Supported MVP currencies are `ILS`, `USD`, and `JOD`.

Phone and WhatsApp normalization removes spaces, hyphens, and parentheses while preserving an optional leading `+`. MenuOS does not infer country codes or provide comprehensive international phone validation in this release.
