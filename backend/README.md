# Field Forecast Backend (Laravel)

This folder contains the first production-facing building blocks:

- MySQL schema (see `database/schema.sql`)
- Odds comparison/value/arbitrage calculation services
- Odds API provider adapter scaffold + scheduled ingestion command
- `/api/odds/comparison` endpoint returning the payload needed for the UI table

Because this workspace environment doesn’t include PHP/Laravel tooling, I did not scaffold the full Laravel project shell here. You should create a Laravel app in `backend/`, then wire these classes into it.

## 1) Create Laravel app

From the parent folder of `backend/`:
- `composer create-project laravel/laravel backend`

## 2) Mount our files into the Laravel app

Ensure these files exist in the Laravel app root (i.e. `backend/<...>`):

- `backend/database/schema.sql`
- `backend/app/Services/Betting/*`
- `backend/app/Services/Odds/*`
- `backend/app/Console/Kernel.php`
- `backend/app/Console/Commands/FetchOddsCommand.php`
- `backend/app/Http/Controllers/OddsComparisonController.php`
- `backend/routes/api.php`

## 3) Database

Run the SQL in `database/schema.sql` (or convert it into Laravel migrations).

## 4) Odds API integration config

Add `.env` values from `.env.example`.

The ingestion scheduler calls `odds:fetch` and uses configured `services.odds_api.sport_keys`.

## 5) What still needs implementation

The comparison endpoint works only if your DB tables are populated (via the ingestion flow).

This iteration includes an Eloquent implementation of `OddsPersistenceInterface` (`app/Services/Odds/EloquentOddsPersistence.php`) and a service provider scaffold (`app/Providers/OddsServiceProvider.php`).

You still need to:
- Register `App\Providers\OddsServiceProvider::class` in your Laravel `config/app.php` providers (or use auto-discovery).
- Ensure the Laravel app actually loads `backend/routes/api.php`.
- Implement/confirm the The Odds API response mapping for your specific parameters (because The Odds API payload shapes can vary by endpoint/version).

