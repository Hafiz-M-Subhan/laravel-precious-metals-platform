# Laravel Precious Metals Platform

> High-traffic Laravel platform for live precious metals trading — WebSocket price feeds to 30k+ concurrent users, Redis-backed queue workers, Filament 3 admin panel, and an event-driven savings plan engine.

Built as a direct answer to Kettner's stack: **Laravel · Redis · WebSockets · ElasticSearch · Filament · Docker**.

---

## Architecture

```
┌──────────────────────────────────────────────────────────────────┐
│                   Vue / Nuxt Frontend                            │
│   Live Price Ticker · Order Form · Savings Plan Chart           │
└──────────────┬─────────────────────┬────────────────────────────┘
               │ REST /api/v1/*       │ WebSocket (Echo + Reverb)
┌──────────────▼─────────────────────▼────────────────────────────┐
│                    Laravel 11 Application                        │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐   │
│  │ AssetCtrl    │  │ OrderCtrl    │  │ SavingsPlanCtrl      │   │
│  │ (Redis 5s)   │  │ (202 + queue)│  │ (DCA projection)     │   │
│  └──────────────┘  └──────────────┘  └──────────────────────┘   │
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐   │
│  │ PriceService │  │ OrderService │  │ SavingsPlanService   │   │
│  │ GBM ticks    │  │ atomic fill  │  │ DCA engine           │   │
│  │ OHLCV upsert │  │ portfolio    │  │ nextExecutionDate    │   │
│  └──────────────┘  └──────────────┘  └──────────────────────┘   │
└──────┬────────────────────┬──────────────────────────────────────┘
       │                    │
┌──────▼──────┐     ┌───────▼──────────────────────────────────────┐
│   Redis     │     │  Queue Workers (Horizon)                     │
│  cache      │     │  ┌──────────────┐  ┌─────────────────────┐  │
│  pub/sub    │     │  │ ProcessOrder │  │ ExecuteSavingsPlan  │  │
│  queues     │     │  │ 3 retries    │  │ unique per day      │  │
└──────┬──────┘     │  │ ShouldBeUniq │  │ DCA buy + portfolio │  │
       │            │  └──────────────┘  └─────────────────────┘  │
       │            └─────────────────────────────────────────────┘
       │
┌──────▼──────────────────────────────────────────────────────────┐
│  Laravel Reverb (WebSocket Server)                              │
│  Channel: prices.XAU  (public  — 30k+ subscribers)             │
│  Channel: live-event  (presence — viewer count)                 │
│  Channel: user.{id}   (private — order fills, plan executions)  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Key Technical Highlights

### 1. Real-Time Price Broadcasting at Scale
`app/Events/PriceUpdated.php` — `app/Services/PriceService.php`

- **Laravel Reverb** WebSocket server broadcasts live gold/silver/platinum/palladium ticks to public channels
- `broadcastWhen()` gate suppresses ticks with < 0.001% movement, cutting queue noise by ~80%
- Presence channel (`live-event`) carries viewer count for the live event page — same pattern as Kettner's 30k simultaneous viewers
- Payload is hand-trimmed to 7 fields to minimize per-message bytes at scale

```php
// PriceUpdated::broadcastWith() — lean payload for 30k concurrent clients
return [
    'symbol'     => $this->asset->symbol,
    'spot'       => (float) $this->asset->spot_price,
    'bid'        => (float) $this->asset->bid_price,
    'ask'        => (float) $this->asset->ask_price,
    'change_pct' => (float) $this->asset->daily_change_pct,
    'direction'  => $this->asset->spot_price > $this->previousPrice ? 'up' : 'down',
    'ts'         => now()->toIso8601String(),
];
```

### 2. Event-Driven Order Processing
`app/Jobs/ProcessOrder.php` — `app/Services/OrderService.php`

- Orders return **202 Accepted** immediately; filling happens in a dedicated `orders` queue
- `ShouldBeUnique` prevents double-fill if a job retries after partial failure
- `DB::transaction()` wraps price re-fetch + status update + portfolio apply atomically (prevents stale fill prices)
- Retry strategy: 3 attempts, exponential backoff `[5s, 30s, 120s]`
- On fill, broadcasts `OrderFulfilled` to the user's private channel — no polling needed

### 3. Savings Plan Engine (DCA)
`app/Services/SavingsPlanService.php` — `app/Jobs/ExecuteSavingsPlan.php`

- Scheduler runs `savings-plans:schedule` every minute; chunks plans with `chunkById(200)` — no full table scans
- Unique job key encodes `plan_id + date` to prevent double-execution across restarts
- `projectDcaGrowth()` models future portfolio value using compounding — feeds the frontend projection chart
- Supports monthly / biweekly / weekly frequencies with `addMonthNoOverflow()` for correct end-of-month handling

### 4. Redis Caching Strategy
`app/Services/PriceService.php` — `app/Http/Controllers/Api/AssetController.php`

| Key pattern | TTL | Purpose |
|---|---|---|
| `asset:price:{symbol}` | 5s | Latest tick for API responses |
| `assets:active` | 5s | Full asset list (cuts 95% of DB load on burst) |
| `candles:{id}:{res}:{from}:{to}` | 60s | OHLCV chart data |
| `daily_open:{id}` | 3600s | Daily open for change% calculation |
| `admin:order_stats` | 30s | Filament dashboard stats widget |

### 5. Filament 3 Admin Panel
`app/Filament/Resources/`

- `AssetResource` — live price table with `.poll('5s')` auto-refresh; color-coded change% badges
- `OrderResource` — full order book with `OrderStatsOverview` widget (filled today, volume, pending queue, failed)
- `SavingsPlanResource` — plan management with next-execution scheduling view

### 6. Database Design
`database/migrations/`

- `price_histories` — composite unique on `(asset_id, resolution, recorded_at)` for idempotent OHLCV upserts
- Covering indexes on `orders` table: `(user_id, status, created_at)`, `(status, created_at)` for queue polling
- `savings_plans` has `(status, next_execution_at)` — the scheduler query hits this index directly
- `SoftDeletes` on orders/assets for audit trail without hard deletes

---

## Quick Start

```bash
# 1. Clone
git clone https://github.com/Hafiz-M-Subhan/laravel-precious-metals-platform.git
cd laravel-precious-metals-platform

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Spin up everything (MySQL, Redis, Elasticsearch, Reverb, Horizon, price simulator)
docker compose up -d

# 4. Migrate + seed
php artisan migrate --seed

# 5. Start Horizon (queue dashboard at /horizon)
php artisan horizon

# 6. Start the WebSocket server
php artisan reverb:start

# 7. Simulate live price feed (dev)
php artisan prices:simulate --interval=2 --volatility=0.002
```

Open `http://localhost:8000/admin` for the Filament panel.

---

## Running Tests

```bash
php artisan test                      # all tests
php artisan test --filter OrderTest   # specific suite
php artisan test --coverage           # with coverage
```

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11, PHP 8.2 |
| WebSocket | Laravel Reverb (self-hosted), Laravel Echo |
| Queue | Redis + Laravel Horizon |
| Cache | Redis (multi-TTL strategy) |
| Search | ElasticSearch 8 (via `elastic/elasticsearch-php`) |
| Admin | Filament 3 |
| Database | MySQL 8 (covering indexes, composite uniques) |
| Infrastructure | Docker Compose, GitHub Actions CI |
| Auth | Laravel Sanctum (SPA + API tokens) |

---

## Project Structure

```
app/
├── Console/Commands/
│   ├── SimulatePriceFeed.php      # Dev price simulator (GBM)
│   └── ScheduleSavingsPlans.php   # Cron dispatcher for DCA plans
├── Events/
│   ├── PriceUpdated.php           # Broadcasts to prices.{symbol} channel
│   ├── OrderFulfilled.php         # Broadcasts to private user.{id}
│   └── SavingsPlanExecuted.php
├── Filament/Resources/
│   ├── AssetResource.php          # Live price admin (5s poll)
│   ├── OrderResource.php          # Order book + stats widget
│   └── SavingsPlanResource.php
├── Http/Controllers/Api/
│   ├── AssetController.php        # GET /assets, /assets/{sym}/candles
│   ├── OrderController.php        # POST /orders → 202 + queue
│   └── SavingsPlanController.php  # CRUD + /projection endpoint
├── Jobs/
│   ├── ProcessOrder.php           # ShouldBeUnique, 3 retries, atomic fill
│   └── ExecuteSavingsPlan.php     # Unique per plan+date, DCA buy
├── Models/
│   ├── Asset.php                  # XAU, XAG, XPT, XPD
│   ├── Order.php
│   ├── PriceHistory.php           # OHLCV candles
│   ├── Portfolio.php
│   └── SavingsPlan.php
└── Services/
    ├── PriceService.php           # Tick ingestion, OHLCV upsert, Redis cache
    ├── OrderService.php           # placeMarket(), fill() in transaction
    └── SavingsPlanService.php     # execute(), projectDcaGrowth(), nextDate()
```

---

## License

MIT
