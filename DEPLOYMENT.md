# Field Forecast Deployment

Deploy `backend` as the Laravel API on Railway and `frontend` as the Next.js app on Vercel.

## 1. Prepare GitHub

This workspace root is not currently a Git repository. Create a GitHub repo from `C:\Field Forecast 1.0` so both platforms can deploy from the same monorepo.

## 2. Railway Backend

Create a Railway project from the GitHub repo.

Use these service settings:

- Root directory: `backend`
- Public networking: generate a Railway domain
- Pre-deploy command: `sh ./railway/init-app.sh`
- Health check path: `/up`

Add a MySQL database service in the same Railway project, then set these variables on the Laravel service:

```env
APP_NAME="Field Forecast"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:REPLACE_WITH_GENERATED_KEY
APP_URL=https://YOUR-RAILWAY-DOMAIN.up.railway.app
FRONTEND_URL=https://YOUR-VERCEL-DOMAIN.vercel.app
CORS_ALLOWED_ORIGINS=https://YOUR-VERCEL-DOMAIN.vercel.app
LOG_CHANNEL=stderr
LOG_STDERR_FORMATTER=Monolog\Formatter\JsonFormatter
CMS_ADMIN_NAME="Field Forecast Admin"
CMS_ADMIN_EMAIL=admin@fieldforecast.local
CMS_ADMIN_PASSWORD=CHANGE_ME_NOW

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

ODDS_API_BASE_URL=https://api.the-odds-api.com/v4
ODDS_API_KEY=REPLACE_WITH_YOUR_KEY
ODDS_API_SPORT_KEYS=soccer_epl,soccer_uefa_champs_league
QUEUE_CONNECTION=database
```

The Railway pre-deploy script runs migrations and seeds the initial CMS admin from `CMS_ADMIN_*`.

Generate a production app key locally from `backend` with:

```bash
php artisan key:generate --show
```

The API should respond at:

```text
https://YOUR-RAILWAY-DOMAIN.up.railway.app/api/odds/comparison
```

Optional Railway services:

- Cron service root directory: `backend`
- Cron custom start command: `sh ./railway/run-cron.sh`
- Worker service root directory: `backend`
- Worker custom start command: `sh ./railway/run-worker.sh`

## 3. Vercel Frontend

Import the same GitHub repo into Vercel.

Use these project settings:

- Framework preset: Next.js
- Root directory: `frontend`
- Build command: `npm run build`
- Install command: `npm install`

Set this Vercel environment variable:

```env
NEXT_PUBLIC_API_BASE_URL=https://YOUR-RAILWAY-DOMAIN.up.railway.app
```

The CMS editor will be available at:

```text
https://YOUR-VERCEL-DOMAIN.vercel.app/admin
```

After Vercel gives you the production URL, return to Railway and update:

```env
FRONTEND_URL=https://YOUR-VERCEL-DOMAIN.vercel.app
CORS_ALLOWED_ORIGINS=https://YOUR-VERCEL-DOMAIN.vercel.app
```

Redeploy the Railway backend after changing CORS variables.

## 4. Local Verification Used

Backend:

```bash
php artisan route:list --path=api
php artisan config:cache
php artisan config:clear
```

Frontend:

```bash
npm install
npm run build
```
