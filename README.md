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

Sprint 2 adds category creation, editing, activation, deletion, and ordering within the authenticated owner's restaurant. Categories are deleted automatically if their restaurant is deleted, preventing orphaned tenant data. Menu items, image uploads, public menus, QR codes, analytics, ordering, subscriptions, branches, POS, and final dashboard features are intentionally not implemented.
