# MonColis Particulier — API backend (Laravel)

Backend of the **MonColis Particulier** mobile app (Flutter, sibling repo `moncolis-particulier-go`).
Target experience: **Yango-like** — phone-first identity with SMS verification, saved places
(home / work), map-centric flows, real-time courier tracking.

Stack: Laravel 12+, PHP 8.3+, MySQL 8, Sanctum (bearer tokens). Tests: PHPUnit on SQLite in memory.

## Setup

```powershell
powershell -ExecutionPolicy Bypass -File scripts\setup.ps1   # skeleton + Sanctum + .env + tests
# create the MySQL database "moncolis_particulier", then:
php artisan migrate --seed          # demo account: demo / password
php artisan serve --host=0.0.0.0    # reachable from a phone on the LAN
```

## Architecture — modular monolith

Each business module mirrors a Flutter feature (`lib/features/*`) and is self-contained:

```
app/
├── Models/User.php                 # account (Laravel convention: auth, Sanctum, factories)
├── Shared/                         # cross-module infrastructure, no business logic
│   ├── Providers/ModuleServiceProvider.php   # auto-loads Routes/api.php + Database/Migrations
│   ├── Http/Middleware/            # ForceJsonResponse, EnsureClientAppCode
│   ├── Exceptions/BusinessException.php      # functional error -> {message, code}
│   ├── Sms/                        # SmsGateway (LogSmsGateway until a provider is chosen)
│   ├── Support/PhoneNumber.php     # E.164 normalization (+237...)
│   └── Rules/
└── Modules/
    └── <Module>/
        ├── <Module>ServiceProvider.php     # registered in bootstrap/providers.php
        ├── Routes/api.php                  # prefixed with /api
        ├── Http/{Controllers,Requests,Resources}
        ├── Services/                       # business logic (controllers stay thin)
        ├── Models/  Enums/  Rules/  Support/
        └── Database/{Migrations,Factories}
```

| Backend module | Flutter feature | Status |
|---|---|---|
| `Identity` — login, register, logout, OTP | `authentication` | **done** |
| `Profile` — info, avatar, phone (OTP), password, preferences, saved places, account deletion | `authentication/account_page`, `settings` | **done** |
| `Delivery` — Expedier: reference data, pricing (quotes), creation, history, cancellation — payment, couriers, tracking next (see docs/PLAN_BACKEND.md) | `delivery` | **in progress** |
| `Payments` — mobile money (MTN MoMo, Orange Money) | `delivery` (payment step), `wallet` | planned |
| `Rides` — Course / VTC / coursier | `rides` | planned |
| `Travel` — interurban tickets | `travel` | planned |
| `Order` / `FreshGoods` / `Gas` — marketplace | `order`, `fresh_goods`, `gas` | planned |
| `Wallet` | `wallet` | planned |
| `Promotions` | `promotions` | planned |
| `Notifications` — push FCM | `core/notifications` | planned |

### Adding a module

1. Create `app/Modules/<Name>/<Name>ServiceProvider.php` extending `App\Shared\Providers\ModuleServiceProvider`.
2. Add it to `bootstrap/providers.php`.
3. Put routes in `Routes/api.php`, migrations in `Database/Migrations` — both are picked up automatically.
4. Feature tests in `tests/Feature/<Name>/`.

### API conventions

- Base path `/api`. JSON in **camelCase**, responses wrapped in `{ "data": ... }`.
- Headers: `Authorization: Bearer <token>`, `client-app-code: <code>` (checked when `MONCOLIS_CLIENT_APP_CODES` is set).
- Validation errors: `422 { message, errors: { field: [..] } }`. Business errors: `{ message, code }`
  with a stable `code` (e.g. `INVALID_CREDENTIALS`, `OTP_EXPIRED`) the app can switch on.
- Legacy contract kept for the existing Flutter code: `POST /api/login_check` → `{ token, data }`.

Endpoint details: [docs/API-PROFIL.md](docs/API-PROFIL.md). Roadmap and status: [docs/PLAN_BACKEND.md](docs/PLAN_BACKEND.md).

### API documentation (Swagger)

Generated from the code by [Scramble](https://scramble.dedoc.co) — FormRequests, Resources and PHPDoc
(write controller PHPDoc in French: first line = title, then description + business error codes).

| URL | |
|---|---|
| `/docs/swagger` | Swagger UI ("Try it out", "Authorize" with the bearer token) |
| `/docs/api` | Scramble UI (Stoplight Elements) |
| `/docs/api.json` | OpenAPI 3.1 document — versioned copy in `docs/openapi.json` |

Open in the local environment; elsewhere only with `API_DOCS_PUBLIC=true`.
Refresh the versioned copy: `php artisan scramble:export --path=docs/openapi.json`.

## Work reports

One report per realization in [docs/rapports/](docs/rapports/README.md).

## Tests

```powershell
php artisan test
```
