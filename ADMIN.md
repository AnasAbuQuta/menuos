# MenuOS Super Admin

Sprint 11A adds a separate platform-management area for trusted MenuOS operators. Super Admin status is system-controlled: it is not accepted by registration, profile, restaurant, or other public request payloads.

The protected API lives under `/api/v1/admin`; the Vue area uses `/admin`, `/admin/users`, `/admin/users/:id`, `/admin/restaurants`, and `/admin/restaurants/:id`. Laravel Sanctum authentication, active-account middleware, and Super Admin middleware protect the entire backend group.

## Create the first Super Admin

The user account must already exist.

```bash
cd backend
php artisan menuos:make-super-admin admin@example.com
```

Use `--force` only in non-interactive deployment automation:

```bash
php artisan menuos:make-super-admin admin@example.com --force
```

The command is idempotent. To remove access:

```bash
php artisan menuos:remove-super-admin admin@example.com
```

MenuOS refuses to remove or disable the final active Super Admin.

## Render free-plan bootstrap

When Render Shell is unavailable, create the normal account first, then temporarily add this backend environment variable:

```text
MENUOS_BOOTSTRAP_SUPER_ADMIN_EMAIL=admin@example.com
```

Trigger one backend deploy. The container promotes the existing user before starting Nginx. Confirm `/admin` works, then **delete the environment variable immediately** and deploy again. Leaving it configured would re-promote that email on every deploy.

Do not place an Artisan command in Render's Docker Command field; keep Docker Command empty so the verified container entrypoint runs.

## Access and suspension behavior

- `/admin` requires a valid Sanctum token, an active account, and Super Admin status.
- Disabling a user revokes all of that user's existing tokens and prevents future login until reactivated.
- Suspending a restaurant hides its public menu, blocks public analytics ingestion, and removes it from the sitemap.
- Suspending a restaurant does not delete owner data or change the owner's account status.
- All status changes are explicit, confirmed in the UI, and performed in database transactions.

## Sprint boundaries

This foundation does not include dynamic roles or permissions, impersonation, subscriptions, billing, payments, deletion, POS, orders, inventory, branches, accounting, delivery, or kitchen tooling. Dynamic RBAC is planned for Sprint 11B; the minimal system-owned flag intentionally remains the only platform role in this sprint.

## Recovery

If every administrator loses access, use the Artisan promotion command from a trusted application runtime. On Render free plans, use the temporary bootstrap environment variable workflow above. Never expose promotion through a public HTTP route.
