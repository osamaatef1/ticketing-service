# Ticketing Service — Project Notes

Standalone **Laravel 11 API-only** microservice extracted from a larger Laravel monolith. Owns the ticket domain (tickets, replies, notes, attachments) plus the supporting taxonomy (`seasons → zones → categories`). Admin authentication and customer/admin profile data live in a separate admin service — this service only validates JWTs the admin service issues.

The full source DBML is in user memory (`monolith_schema.md`). Don't try to recreate any of the omitted tables (faqs, knowledge_bases, pages, telescope_*, model_has_*, ticket_types, etc.) unless asked — they belong to other bounded contexts.

## Tech stack

- PHP 8.3, Laravel 11 (API-only)
- MySQL 8 (database `ticketing`, root/root locally)
- Redis (optional cache; falls back to whatever `CACHE_STORE` is)
- `firebase/php-jwt` v7 — JWT validation only (we are a consumer, not an issuer)
- `spatie/laravel-medialibrary` — attachments + season cover image
- `spatie/laravel-translatable` — multilingual `name` columns (`en` + `ar`)

## Domain model

```
seasons (soft delete, multilingual name, `cover` Spatie media collection)
   └── zones        (FK season_id, cascade)
         └── categories  (FK zone_id nullable cascade, parent_id self-FK null on delete, multilingual)
                └── tickets.category_id (FK, cascade)
```

`tickets` carries the customer info, `status` (string enum), `platform` (string enum), `created_by` / `admin_id` as plain bigints (admin records live in the admin service — resolved on read via `AdminClient` with cache).

`ticket_replies` and `ticket_notes` belong to a ticket. `replied_by` on a reply is `'admin' | 'customer'` (not a FK — it's a discriminator).

Attachments use Spatie polymorphic `media` table with the `attachments` collection on Ticket/Reply/Note, and a `cover` single-file collection on Season.

## Enums (string-backed, in `app/Support/Enums/`)

- `TicketStatus`: `new` | `assigned` | `in_progress` | `pending_customer` | `resolved` | `closed`
- `ReplyAuthor`: `admin` | `customer`
- `TicketPlatform`: `manual` | `whatsapp` | `web` | `api`

## Auth model

Two trust boundaries:

1. **Authenticated admin path** — `/api/v1/*` except `/health` and `/public/*` requires a JWT (HS256, shared secret with admin service). `JwtAuthMiddleware` validates `iss`/`aud`/`exp`/`sub` and binds an `ActingAdmin { id, name, email }` value object onto the request and the container. Middleware alias is `auth.jwt`.
2. **Public path** — `POST /api/v1/public/tickets` for customer submissions from a website / WhatsApp gateway. No JWT. Uses `StorePublicTicketRequest` (narrower schema) so admin-only fields like `admin_id`, `status`, `created_by` are silently stripped by `$request->validated()`. Controller hardcodes `status=new`, `created_by=null`.

JWT fixture for local dev: `php scripts/jwt.php <admin_id> "<name>" "<email>"`.
**Important:** `JWT_SECRET` must be ≥32 bytes (firebase/php-jwt v7 enforces this).

## Rate limiting

Three named limiters in `app/Providers/AppServiceProvider.php`:

| Limiter | Where | Limit | Key |
|---|---|---|---|
| `api` | All `/api/*` (Laravel default) | 60/min | client IP |
| `admin` | All authenticated routes | 120/min | admin id from JWT (IP fallback at 30/min) |
| `public-tickets` | `POST /public/tickets` only | 10/min | client IP |

Critical detail: in `bootstrap/app.php` we `prependToPriorityList(before: ThrottleRequests::class, prepend: JwtAuthMiddleware::class)`. Without this, Laravel's middleware priority list runs `ThrottleRequests` before any custom middleware — which means the `admin` limiter would resolve its key before `acting_admin` is set, falling back to IP and putting all admins in one bucket. If you ever add another custom middleware that needs to run before throttling, repeat the same prepend pattern.

## Pagination shape (global)

`AppServiceProvider::boot()` registers a macro on `JsonResource::paginationInformation` so every paginated response uses a slimmer shape:

```json
{ "data": [...],
  "links": { "first", "last", "prev", "next" },
  "meta":  { "current_page", "last_page", "per_page", "total", "from", "to", "has_more" } }
```

Drops Laravel's default `meta.links` page-button array and `meta.path`; adds `has_more`. Applies to every resource without per-resource code.

## Admin enrichment

`app/Services/AdminClient.php` wraps `Http::baseUrl(services.admin.url)` to fetch admin records on read. `find($id)` and `findMany($ids)` (batched). Cache key `admin:{id}`, TTL `ADMIN_CACHE_TTL` (default 300s). On 404 → cached `false`; on timeout/non-2xx → null + log warning, ticket response degrades gracefully with `_stale: true` markers on admin objects rather than failing.

`TicketResource` and `TicketReplyResource` / `TicketNoteResource` call `app(AdminClient::class)->find/findMany()` to inflate `created_by`, `assigned_admin`, reply.admin, note.admin objects.

## API surface (all under `/api/v1`)

**Public**
- `GET  /health` — liveness probe
- `POST /public/tickets` — customer submission (no auth, throttled 10/min/IP, requires email or phone)

**Tickets** (auth.jwt + throttle:admin)
- `apiResource('tickets')` — index/store/show/update(soft) destroy. `index` is a full admin-dashboard listing: multi-status (`status[]=...`), `assigned=me|unassigned`, customer search across name/email/phone/subject/ticket_number, descendant-aware category (`include_subcategories=1`), `has_replies|has_notes|has_attachments`, three date ranges (`created_*`, `occurrence_*`, `closed_*`), and `sort=latest|oldest|updated|closed`. All filters live in `TicketService::applyFilters($query, $filters, $actingAdminId)`. Invalid status values are silently dropped.
- `PATCH /tickets/{id}/status` — single endpoint replacing close/reopen/assign. Body `{ status, admin_id? }`. Side effects baked in: `assigned` → stamps `assigned_at`, clears `closed_at`, requires `admin_id`; `closed` → stamps `closed_at`; reopening any closed ticket → clears `closed_at`.
- `GET|POST /tickets/{id}/replies` — multipart `attachments[]` accepted
- `GET|POST /tickets/{id}/notes`   — internal notes, multipart `attachments[]` accepted

**Taxonomy** (auth.jwt + throttle:admin)
- `apiResource('seasons')` — multilingual name + `cover` upload (Spatie). Filters: `status`, `q`, `with_trashed`, `per_page`. Update accepts `remove_cover: true`.
- `apiResource('zones')`   — required `season_id`. Filters: `season_id`, `status`, `q`, `with_season`, `with_categories`, `with_trashed`.
- `apiResource('categories')` — self-FK tree via `parent_id`. Filters: `status`, `zone_id`, `parent_id` or `roots_only`, `with_children`, `with_trashed`. Update rejects `parent_id == self`.

## Project layout

```
app/
├── Http/
│   ├── Controllers/Api/V1/   Health, Ticket, TicketStatus, TicketReply, TicketNote,
│   │                         PublicTicket, Season, Zone, Category
│   ├── Middleware/           JwtAuthMiddleware
│   ├── Requests/             Store/Update FormRequests per resource
│   └── Resources/            Ticket, TicketReply, TicketNote, Season, Zone, Category, Media
├── Models/                   Ticket, TicketReply, TicketNote, Season, Zone, Category
├── Services/                 TicketService, TicketNumberGenerator, AdminClient, AdminDTO
├── Support/
│   ├── ActingAdmin           value object bound from JWT
│   └── Enums/                TicketStatus, ReplyAuthor, TicketPlatform
└── Providers/                AppServiceProvider (rate limiters, pagination macro, AdminClient binding)

bootstrap/app.php             auth.jwt alias + middleware priority bump
config/services.php           admin + jwt config
database/migrations/          run order: cache → jobs → media → seasons → zones → categories → tickets → ticket_replies → ticket_notes
database/seeders/             Database, Season (4), Zone (3 per season), Category (3 roots × 2-3 children)
routes/api.php                public + auth.jwt + throttle groups
postman/                      ticketing-service.postman_collection.json (9 folders, 35 requests)
scripts/jwt.php               dev token generator
```

## Conventions / decisions worth knowing

- **Microservice boundaries.** No FK across services — admin records are remote, taxonomy `*_id` columns that don't have a local owning table are plain unsigned bigints (e.g., `tickets.zone_id`, `tickets.season_id`, `tickets.sub_category_id`). Only `tickets.category_id` is a real FK because `categories` lives in this service.
- **Soft deletes vs FK cascades.** Eloquent `$model->delete()` only sets `deleted_at` — MySQL FK cascade only fires on hard SQL `DELETE` / `forceDelete()`. So deleting a season via the API is non-destructive; force-deleting it cascades through zones/categories/tickets.
- **Translatable JSON columns.** `name` on Season/Zone/Category. Send `{ "name": { "en": "...", "ar": "..." } }`. Updates are merged per locale — sending only `name.ar` patches Arabic without touching English. Resources return both the full translations object (`name`) and a single locale-resolved string (`name_localized`) using `app()->getLocale()`.
- **Status changes** go through one endpoint (`PATCH /tickets/{id}/status`), not three. The match in `TicketService::transition` handles all side effects centrally.
- **No tests.** User explicitly opted out. Smoke tests done with curl during build.
- **Default DB credentials in .env.** `root/root` locally — matches sibling projects.

## Common operations

```bash
# Database
mysql -uroot -proot -e "CREATE DATABASE IF NOT EXISTS ticketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php artisan migrate:fresh --seed
php artisan storage:link

# Run
php artisan serve            # uses APP_URL=http://localhost:8000

# Dev JWT
TOKEN=$(php scripts/jwt.php 1 "Osama" "developers@intcore.com")

# Smoke checks
curl -s http://localhost:8000/api/v1/health
curl -s "http://localhost:8000/api/v1/seasons" -H "Authorization: Bearer $TOKEN"
```

## Out of scope (deferred)

- WhatsApp / web-chat ingestion workers (will introduce `threads` + `messages`)
- `ticket_types` (FK to seasons in the original schema)
- `sub_category_id`, `sub_sub_category_id`, `ticket_category_id` as real FKs (currently plain bigints — `category_id` is the only one promoted)
- Customer-facing portal / guest token flow (the `tickets.token` column is reserved)
- Email / WhatsApp / SMS dispatch on reply (`send_email` / `send_whatsapp` / `send_sms` are persisted but not wired to a dispatcher — easiest follow-up: queued listener on a `TicketReplyCreated` event)
- Spatie permissions, FAQs, knowledge base, pages, settings, Telescope, Filament admin UI
