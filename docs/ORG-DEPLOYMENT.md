# SZVS tenant: per-organization deployment

This project (`szvs-tenant`) is **not** shared-database multi-tenancy. Each organization gets its own **MySQL database** and its own **application deploy** (same codebase, different config). There is no cross-org data, membership, or login.

`szvs2` remains a backup/reference only — do not modify it.

---

## Architecture decisions

| Topic | Choice |
|--------|--------|
| Isolation | **One org = one MySQL database + one app instance** (vhost/subdomain pointing at that copy’s `public/`). |
| Users | A person exists only in that org’s DB. Same email on another server is a **different row** by design. |
| Org identity at runtime | **`ORG_CODE` in environment** (and optional display name in DB). No org switcher, no session `current_organization_id`. |
| Login URL | **`/login`** on that instance’s `APP_URL`. Legacy `/login/organization` will be removed in step 3. |
| Local dev (WAMP) | One folder per org **or** one folder with `.env` swapped; each org uses a **different database name** (e.g. `szvs_org_demo`). Subdomain optional; `APP_URL` is enough. |
| Production | Prefer **`{org_code}.yourdomain.com`** → dedicated vhost → that org’s docroot + `.env`. Alternative: path-based only if you must share one hostname (not recommended). |
| Email/phone uniqueness | **Within this database only** (unique indexes on `users.email` / `users.phone`). |
| Schema (target) | **Single-org database**: drop `organization_users`, `primary_organization_id`, global multi-org flows. Keep one `organizations` row (id `1`) for name/`org_code`/settings, or metadata mirrored from `ORG_CODE` in env. Put **`role`** and **`member_code`** on `users`. Domain tables (`families`, `schemes`, …) may keep `organization_id` as FK to row `1` during migration, then simplify later. |
| Superadmin | **Yes, but only on a separate control plane** — different deploy + DB (org registry, provisioning). **Org instances do not expose `/superadmin`** once split. |
| Star Admin (`themes/`) | **Bundled per deploy** (copy of `themes/` with each instance). No shared asset CDN required between orgs. |

---

## Target folder structure (one deploy = one org)

Active application code is under the modern stack; legacy CodeIgniter tree is unused for new work.

```
szvs-tenant/                    # Git repo (same artifact for every org)
├── .env                        # Per-server secrets (never commit)
├── .env.example                # Template
├── app/                        # Controllers, Models, Views, Middleware, Services
├── config/
│   ├── app.php                 # APP_URL, ORG_CODE, session, env
│   ├── database.php            # Reads DATABASE_* from env / database.local.php
│   └── database.local.php      # Optional override (WAMP / cPanel); gitignored
├── database/
│   ├── schema.sql              # Single-org schema (evolving)
│   └── migrations/             # Incremental SQL (as added)
├── docs/
│   └── ORG-DEPLOYMENT.md       # This file
├── public/                     # Web docroot (only entry: index.php → routes)
├── routes/
│   └── web.php
├── themes/                     # Star Admin static assets (per deploy)
├── vendor/                     # Composer (commit or install on server)
├── tools/                      # CLI / import helpers
│
├── application/                # Legacy CodeIgniter (reference; not used by public/)
├── system/                     # CI system (legacy)
├── assets/                     # Legacy uploads/CSS
└── index.php                   # Legacy CI front controller (do not use for SaaS)
```

**WAMP (local)** — example for org `DEMO`:

- Document root: `C:\wamp64\www\szvs-tenant\public`
- URL: `http://localhost/szvs-tenant/public` (set `APP_URL` accordingly)
- Database: `szvs_org_demo` (import `database/schema.sql` after single-org migration)

**Production** — example:

- `https://demo.yourdomain.com` → `/var/www/szvs-demo/public`
- `.env`: `ORG_CODE=DEMO`, `DATABASE_NAME=szvs_demo`, …
- MySQL user scoped to that database only

Optional second local instance: clone repo to `szvs-tenant-acme` with a different `.env` and DB — same pattern as production.

---

## Environment variables (per org)

Copy `.env.example` → `.env` on each server. PHP does not load `.env` automatically until step 3; until then use `database.local.php` or set vars in Apache/Nginx/vhost.

| Variable | Required | Purpose |
|----------|----------|---------|
| `APP_ENV` | No | `development` \| `production` (default `development`) |
| `APP_URL` | **Yes** | Public base URL, no trailing slash — e.g. `http://localhost/szvs-tenant/public` |
| `ASSET_URL` | No | Parent URL for `/themes` if different from auto-detect; usually project root without `/public` |
| `ORG_CODE` | **Yes** | 3-letter org code (e.g. `DEM`) — member IDs, labels; must match DB `organizations.org_code` |
| `ORG_NAME` | No | Display name in UI/emails if not loaded from DB |
| `DATABASE_HOST` | **Yes** | MySQL host |
| `DATABASE_PORT` | No | Default `3306` |
| `DATABASE_NAME` | **Yes** | This org’s database only |
| `DATABASE_USER` | **Yes** | MySQL user |
| `DATABASE_PASSWORD` | Yes* | MySQL password (*empty allowed on local WAMP) |
| `SESSION_NAME` | No | Cookie name (default `SZVS_ORG_SESS`) — set per host if multiple orgs on same domain (rare) |

**Transitional aliases** (current `config/*.php` — will be unified in step 3):

| New (`.env`) | Current (env / config) |
|--------------|-------------------------|
| `APP_URL` | `SAAS_BASE_URL` → `config/app.php` `base_url` |
| `ASSET_URL` | `SAAS_ASSET_BASE_URL` → `asset_base_url` |
| `DATABASE_*` | `SAAS_DB_*` → `config/database.php` |

---

## Example `.env` files

**Local — org DEMO on WAMP**

```ini
APP_ENV=development
APP_URL=http://localhost/szvs-tenant/public
ASSET_URL=http://localhost/szvs-tenant
ORG_CODE=DEM
ORG_NAME=Demo Samaj

DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
DATABASE_NAME=szvs_org_demo
DATABASE_USER=root
DATABASE_PASSWORD=
```

**Production — org ACME**

```ini
APP_ENV=production
APP_URL=https://acme.yourdomain.com
ASSET_URL=https://acme.yourdomain.com
ORG_CODE=ACM
ORG_NAME=ACME Vishwa Samaj

DATABASE_HOST=localhost
DATABASE_PORT=3306
DATABASE_NAME=szvs_acme
DATABASE_USER=szvs_acme_app
DATABASE_PASSWORD=change-me
```

**Control plane (future, separate repo or `APP_MODE=control_plane`)**

```ini
APP_ENV=production
APP_URL=https://admin.yourdomain.com
DATABASE_NAME=szvs_control
# ORG_CODE not used; provisions new org DBs + deploys
```

---

## What we remove (from szvs2 multi-org model)

- `organization_users` membership join (roles move to `users`)
- `users.primary_organization_id`
- Session `current_organization_id` and `/organization/switch`
- Superadmin “add existing platform user to another org” on org instances
- Cross-org `family_membership_requests` (invites across org DBs)
- Global unique email/phone across orgs (impossible across DBs; only per-DB uniqueness remains)

---

## Implementation checklist (ordered)

1. **This doc** + `.env.example` — deployment contract. **Done**
2. **Schema** — `database/schema.sql` (single-org) + `database/migrations/001_single_org_from_multi.sql`. **Done**
3. **Auth** — `/login`, `.env` loader, `organization_id()` helper; org switcher removed; superadmin routes only when `APP_MODE=control_plane`. **Done**
4. **Users** — `role` + `member_code` on `users`; lookups scoped to this DB only. **Done**
5. **Family invites** — in-org only; no `inviteOrganizationId` on requests. **Done**
6. **Remaining** — run migration or fresh schema on your org DB; copy `.env.example` → `.env`; align `organizations.org_code` with `ORG_CODE`.

---

## Control plane vs org instance

| | Org instance | Control plane |
|--|--------------|-----------------|
| Users | Org members & admins | Provisioner admins only |
| DB | One org’s data | Registry of orgs, deploy metadata |
| Routes | `/login`, `/organization/*` | Provision org, run migrations, optional import |
| `ORG_CODE` | Set in `.env` | N/A |

Org instances should not depend on another org’s database.
