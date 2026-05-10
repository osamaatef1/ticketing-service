# Ticketing Service

A standalone **Laravel 11 API-only** microservice for creating, managing, and tracking customer support tickets. Designed to be consumed by an admin frontend and other internal services (WhatsApp ingestion worker, web-chat gateway, etc.).

Auth is delegated: callers present a **JWT issued by the separate admin service**. This service validates the token (HS256, shared secret) and uses the `sub` claim as the acting admin id for audit fields.

## Scope (v1)

- Ticket CRUD + lifecycle (assign, close, reopen, soft delete)
- Replies (admin or customer), with `send_email` / `send_whatsapp` / `send_sms` flags persisted (dispatch is left to a future event listener)
- Internal notes
- Attachments on tickets / replies / notes via Spatie MediaLibrary
- Admin objects (`created_by`, `assigned_admin`, reply.admin, note.admin) are resolved on read by HTTP-calling the admin service, with cache (`ADMIN_CACHE_TTL`).
- **Seasons → Zones → Categories** CRUD with multilingual `name` (Spatie Translatable, `en` + `ar`). Real FK chain with cascade: deleting a season cascades through zones and categories. Categories form a self-referencing tree via `parent_id`. Seasons accept a single `cover` image via Spatie MediaLibrary. `tickets.category_id` is also a real FK to categories.

Out of scope (deferred): WhatsApp / web-chat ingestion, taxonomy management, customer portal, FAQs, knowledge base, admin authentication.

## Requirements

- PHP 8.3+
- MySQL 8+
- Redis (optional; falls back to whatever `CACHE_STORE` is set to)

## Setup

```bash
cp .env.example .env
php artisan key:generate

# Create the database first, then:
php artisan migrate

# For local file uploads served via /storage:
php artisan storage:link

php artisan serve
```

The admin service is the source of truth for admin records; this service stores `admin_id` as a plain bigint with no FK. Taxonomy ids (`category_id`, `season_id`, `zone_id`, etc.) are also plain bigints — validation lives in the calling service.

## Configuration

| Env var | Purpose |
|---|---|
| `JWT_SECRET` | Shared secret with the admin service (HS256). Required. |
| `JWT_ISSUER` | Expected `iss` claim (default `admin-service`) |
| `JWT_AUDIENCE` | Expected `aud` claim (default `ticketing-service`) |
| `JWT_LEEWAY` | Clock skew tolerance in seconds (default 10) |
| `ADMIN_SERVICE_URL` | Base URL of the admin service |
| `ADMIN_SERVICE_TOKEN` | Optional `X-Internal-Token` header for outbound calls |
| `ADMIN_CACHE_TTL` | Cache TTL (seconds) for admin lookups (default 300) |
| `FILESYSTEM_DISK` | Disk used for attachments (`public` for dev, `s3` for prod) |

### Rate limits

Three named limiters are registered in `app/Providers/AppServiceProvider.php`:

| Limiter | Where it's applied | Limit | Key |
|---|---|---|---|
| `api` | Auto-applied to every `/api/*` route by Laravel's `api` middleware group | 60/min | client IP — coarse safety net |
| `admin` | All authenticated routes (the `auth.jwt` group) | 120/min | admin id from JWT (falls back to IP at 30/min if missing) |
| `public-tickets` | `POST /public/tickets` only | 10/min | client IP |

The `JwtAuthMiddleware` is bumped ahead of `ThrottleRequests` in the global priority list (`bootstrap/app.php`) so the `admin` limiter can read `acting_admin` from the request.

The admin service is expected to expose:

- `GET /api/admins/{id}` → `{ id, name, email, avatar_url }` (or wrapped in `{ data: ... }`)
- `GET /api/admins?ids=1,2,3` → list of the same shape

If the admin service is unreachable, ticket responses still succeed — admin fields come back with `_stale: true` and null `name` / `email`.

## Generating a dev JWT

```bash
php scripts/jwt.php 1 "Osama" "developers@intcore.com"
# prints a Bearer token valid for 1 hour, signed with JWT_SECRET
```

## API endpoints

All under `/api/v1`. All except `/health` require `Authorization: Bearer <jwt>`.

| Method | Path | Purpose |
|---|---|---|
| `GET`  | `/health` | Liveness probe (no auth) |
| `POST` | `/public/tickets` | **No auth.** Public customer submission (web form, WhatsApp gateway). Throttled 10/min/IP via the `public-tickets` limiter. Requires `email` or `phone`. Server-controlled fields (`admin_id`, `status`, `created_by`, etc.) are ignored — ticket is always created with `status=new`, `created_by=null`. |
| `POST` | `/tickets` | Create a ticket (admin-authenticated) |
| `GET`  | `/tickets` | List with filters: `status`, `admin_id`, `category_id`, `season_id`, `zone_id`, `platform`, `q`, `from`, `to`, `per_page` |
| `GET`  | `/tickets/{id}` | Show with replies, notes, attachments, enriched admin objects |
| `PATCH`| `/tickets/{id}` | Update mutable fields |
| `DELETE` | `/tickets/{id}` | Soft delete |
| `PATCH` | `/tickets/{id}/status` | Single endpoint for every status transition. Body `{ status, admin_id? }`. `admin_id` is required when `status=assigned`. Side effects: `assigned` → stamps `assigned_at` + clears `closed_at`; `closed` → stamps `closed_at`; reopening any closed ticket → clears `closed_at` automatically. |
| `GET`  | `/tickets/{id}/replies` | Paginated replies |
| `POST` | `/tickets/{id}/replies` | Add reply (multipart `attachments[]` accepted) |
| `GET`  | `/tickets/{id}/notes` | Paginated notes |
| `POST` | `/tickets/{id}/notes` | Add internal note (multipart `attachments[]` accepted) |
| `GET`  | `/seasons` | List seasons (filters: `status`, `q`, `with_trashed`, `per_page`, `page`) |
| `POST` | `/seasons` | Create season. Body `{ name: { en, ar }, status? }`. Multipart with `cover` file accepted. |
| `GET`  | `/seasons/{id}` | Show season |
| `PATCH`| `/seasons/{id}` | Update. Sending `name.ar` only patches Arabic. `remove_cover: true` clears the cover. |
| `DELETE` | `/seasons/{id}` | Soft delete |
| `GET`  | `/zones` | List (filters: `season_id`, `status`, `q`, `with_season`, `with_categories`, `with_trashed`) |
| `POST` | `/zones` | Create. Body `{ name: { en, ar }, season_id, status? }`. `season_id` required + FK-checked. |
| `GET`  | `/zones/{id}` | Show with `season` + `categories` + `categories_count` |
| `PATCH`| `/zones/{id}` | Update |
| `DELETE` | `/zones/{id}` | Soft delete |
| `GET`  | `/categories` | List (filters: `status`, `zone_id`, `parent_id` or `roots_only`, `with_children`, `with_trashed`, `q`) |
| `POST` | `/categories` | Create. Body `{ name: { en, ar }, zone_id?, parent_id?, status?, is_custom?, enable_email? }`. |
| `GET`  | `/categories/{id}` | Show with `children` + `children_count` |
| `PATCH`| `/categories/{id}` | Update. A category cannot be its own parent. |
| `DELETE` | `/categories/{id}` | Soft delete |

### Status enum (string)

`new`, `assigned`, `in_progress`, `pending_customer`, `resolved`, `closed`

### Reply author enum (string)

`admin`, `customer` — defaults to `admin` from the JWT.

### Platform enum (string)

`manual`, `whatsapp`, `web`, `api`

## Sample requests

```bash
TOKEN=$(php scripts/jwt.php 1 "Osama" "developers@intcore.com")

# Health
curl -s http://localhost:8000/api/v1/health

# Create
curl -s -X POST http://localhost:8000/api/v1/tickets \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "subject": "Lost luggage at hotel",
    "description": "Customer cannot find suitcase after transfer",
    "first_name": "Ali",
    "email": "ali@example.com",
    "phone": "501234567",
    "country_code": "+966",
    "platform": "manual",
    "category_id": 4,
    "season_id": 1,
    "zone_id": 2
  }'

# List
curl -s "http://localhost:8000/api/v1/tickets?status=new&per_page=10" \
  -H "Authorization: Bearer $TOKEN"

# Assign to admin 7
curl -s -X PATCH http://localhost:8000/api/v1/tickets/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "assigned", "admin_id": 7}'

# Reply with attachment
curl -s -X POST http://localhost:8000/api/v1/tickets/1/replies \
  -H "Authorization: Bearer $TOKEN" \
  -F "description=We are looking into this" \
  -F "send_email=1" \
  -F "attachments[]=@./photo.jpg"

# Internal note
curl -s -X POST http://localhost:8000/api/v1/tickets/1/notes \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"note": "Forwarded to operations team"}'

# Close
curl -s -X PATCH http://localhost:8000/api/v1/tickets/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "closed"}'

# Reopen (or move to any other status)
curl -s -X PATCH http://localhost:8000/api/v1/tickets/1/status \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"status": "in_progress"}'
```

## Project layout

```
app/
├── Http/
│   ├── Controllers/Api/V1/
│   │   ├── HealthController.php
│   │   ├── TicketController.php
│   │   ├── TicketStatusController.php       # PATCH /tickets/{id}/status — all transitions
│   │   ├── TicketReplyController.php
│   │   └── TicketNoteController.php
│   ├── Middleware/JwtAuthMiddleware.php
│   ├── Requests/                            # form-request validation
│   └── Resources/                           # JSON transformers
├── Models/
│   ├── Ticket.php
│   ├── TicketReply.php
│   └── TicketNote.php
├── Services/
│   ├── AdminClient.php                      # HTTP client to admin service
│   ├── AdminDTO.php
│   ├── TicketNumberGenerator.php            # TKT-YYYY-NNNNN
│   └── TicketService.php                    # domain logic
└── Support/
    ├── ActingAdmin.php                      # bound from JWT
    └── Enums/
        ├── TicketStatus.php
        ├── ReplyAuthor.php
        └── TicketPlatform.php
config/services.php                          # admin + jwt config
routes/api.php                               # /api/v1/*
database/migrations/                         # tickets, replies, notes, media
scripts/jwt.php                              # dev token generator
```

## Notes for next iterations

- **Channel ingestion (WhatsApp + web chat).** Add `threads` / `messages` tables and a service-account JWT scope; auto-create tickets from inbound messages; map `replied_by = customer` for incoming customer messages.
- **Reply dispatch.** `send_email` / `send_whatsapp` / `send_sms` are persisted but not yet acted on. Dispatch via a queued listener on a `TicketReplyCreated` event.
- **Service-to-service auth hardening.** Move outbound calls from a static `X-Internal-Token` header to a signed service JWT, and consider RS256/JWKS for inbound validation.
- **Customer-facing portal.** Use the existing `token` column to issue per-ticket guest URLs.
