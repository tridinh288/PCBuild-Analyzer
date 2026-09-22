# Deployment

Render (API + static site) · TiDB Cloud Starter (database) · Cloudinary (images). All free tiers.
Decisions: D-021 (database), D-022/D-037 (images), D-023 (tokens), D-024 (Render).

```text
Browser ──► React (Render Static Site) ──Axios──► Laravel API (Render Web Service, Docker)
   │                                                   ├──► TiDB Cloud (TLS, port 4000)
   │                                                   └──► Cloudinary (upload / delete)
   └──► images straight from the Cloudinary CDN
```

The Render services are stateless: data lives in TiDB, images in Cloudinary. Redeploying or a
restarted container loses nothing.

---

## 1. Before you start

| You need | Where |
|---|---|
| The GitHub repository | already connected |
| TiDB Cloud Starter cluster with database `pcbuild` | done in Phase 2 (§ TiDB check below) |
| Cloudinary account | cloudinary.com → free plan → Dashboard shows the **API environment variable** `cloudinary://<key>:<secret>@<cloud>` |
| Render account | render.com, sign in with GitHub |
| An `APP_KEY` | `docker compose exec app php artisan key:generate --show` (copy the whole `base64:…` value) |
| A strong admin password | any password manager |

Never paste these values into the repository, an issue, or a chat message.

## 2. Create both services from the Blueprint

1. Render Dashboard → **New → Blueprint** → select `PCBuild-Analyzer` → Render reads `render.yaml`.
2. It lists `pcbuild-api` (Docker) and `pcbuild-web` (static site) and asks for every `sync: false`
   value. Service URLs are `https://<name>.onrender.com`; if a name is taken, Render adds a suffix —
   use the real URLs shown in the dashboard.

| Service | Variable | Value |
|---|---|---|
| pcbuild-api | `APP_KEY` | the `base64:…` value from step 1 |
| | `APP_URL` | `https://pcbuild-api.onrender.com` |
| | `FRONTEND_URL` | `https://pcbuild-web.onrender.com` (no trailing slash; the only CORS origin) |
| | `DB_HOST`, `DB_USERNAME`, `DB_PASSWORD` | TiDB **Connect** dialog (host `gateway01…tidbcloud.com`, user `xxxx.root`) |
| | `CLOUDINARY_URL` | `cloudinary://<key>:<secret>@<cloud>` |
| | `ADMIN_EMAIL`, `ADMIN_PASSWORD` | the admin account to create |
| pcbuild-web | `VITE_API_URL` | `https://pcbuild-api.onrender.com/api` |

3. **First deploy only:** on `pcbuild-api` → Environment, set `SEED_DATABASE=true` so the start script
   loads the demo data and creates the admin. After the service is live, set it back to `false`.
   (Seeders are idempotent: an accidental second run does not duplicate data.)
4. Apply. The API build takes a few minutes (Docker image); the static site about a minute.

`VITE_API_URL` is baked in at build time: after changing it, trigger **Manual Deploy → Clear build
cache & deploy** on `pcbuild-web`.

## 3. What happens on each API deploy

`backend/Dockerfile` builds a multi-stage Alpine image (Composer without dev packages, OPcache,
non-root user). The free plan has no pre-deploy command, so `backend/docker/start.sh` runs at start:

```sh
php artisan migrate --force          # schema changes before serving
[SEED_DATABASE=true] php artisan db:seed --force
php artisan config:cache && php artisan route:cache
exec php artisan serve --host=0.0.0.0 --port=$PORT --no-reload   # PHP_CLI_SERVER_WORKERS=4
```

Render routes traffic only after `GET /up` answers 200.

## 4. Check the deployment

```bash
API=https://pcbuild-api.onrender.com
curl -s $API/up                                        # 200 (first call can take ~1 min: cold start)
curl -s "$API/api/builds?per_page=1"                   # success: true, demo data
curl -s -D - -o /dev/null -H "Origin: https://pcbuild-web.onrender.com" $API/api/categories \
  | grep -i access-control-allow-origin                 # the frontend URL
```

Then in the browser: open the static site, open a template, customize it in the Builder, copy the
link and open it in a private window, log in at `/admin/login`, upload a product image.

Optional database check against TiDB from your machine: § TiDB check below
(`php artisan app:verify-database --env=tidb`).

## 5. Troubleshooting

| Symptom | Cause / fix |
|---|---|
| First request takes ~1 minute | Free instances sleep after 15 min idle. The frontend shows "Máy chủ đang khởi động…". Expected. |
| Browser: CORS error | `FRONTEND_URL` differs from the site URL (scheme, trailing slash, renamed service). Fix it, redeploy the API. |
| Frontend calls `localhost:8000` | `VITE_API_URL` was missing at build time → clear build cache & deploy the static site. |
| Page refresh on `/builds/…` gives 404 | The `/* → /index.html` rewrite is missing on the static site (it is in `render.yaml`). |
| API crashes at start: `SQLSTATE[HY000] [2002]` / TLS error | Wrong `DB_HOST`/port, or `MYSQL_ATTR_SSL_CA` missing (must be `/etc/ssl/certs/ca-certificates.crt`). |
| `Access denied … for table 'migrations'` in `sys` | `DB_DATABASE` must be `pcbuild`, never `sys`. |
| "No application encryption key" | `APP_KEY` missing or without the `base64:` prefix. |
| Image upload: "Chưa cấu hình dịch vụ ảnh" | `CLOUDINARY_URL` not set on the API service. |
| Everyone gets 429 at once | Rate limits must see the real client IP; check `trustProxies` in `bootstrap/app.php` (private networks trusted, not `*`). |

## 6. Security checklist

- `APP_DEBUG=false` (500 responses show no internal details), `APP_ENV=production`.
- Secrets only in Render environment variables; `.env*` files are gitignored and dockerignored.
- CORS allows only `FRONTEND_URL`; admin uses expiring bearer tokens, logout revokes them.
- The API container runs as a non-root user; the static site sends `X-Frame-Options: DENY`,
  `X-Content-Type-Options: nosniff` and a strict referrer policy.

---

## TiDB compatibility check (Phase 2, step 9)

Goal: prove that migrations, JSON spec filters, the computed total price (D-032), and foreign keys
behave on TiDB exactly as on local MySQL.

### 1. Create the database

1. Sign in to [TiDB Cloud](https://tidbcloud.com) and create a **Starter** cluster (free tier).
2. Open **Connect**, choose "General" / "PHP", and note host, port (4000), user, and password.
3. Create a database, e.g. `pcbuild` (SQL editor: `CREATE DATABASE pcbuild;`).

> **Warning:** the Connect dialog pre-fills the database as `sys` (a TiDB system schema).
> Never point `DB_DATABASE` at `sys`: `migrate:fresh` drops every table in the target database.
> Check with `php artisan db:show --env=tidb` before running migrations.

### 2. Local env file for TiDB (never committed)

Create `backend/.env.tidb` (ignored by `.gitignore` through `.env.*`), starting from `backend/.env`:

```dotenv
APP_ENV=tidb
DB_CONNECTION=mysql
DB_HOST=<gateway host from the Connect dialog>
DB_PORT=4000
DB_DATABASE=pcbuild
DB_USERNAME=<user, e.g. xxxxxxxx.root>
DB_PASSWORD=<password>
# CA bundle inside the Docker app container (Debian); TiDB requires TLS
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
```

Keep `APP_KEY` and the other values from `.env`, but **leave `ADMIN_EMAIL` and `ADMIN_PASSWORD`
empty**: otherwise seeding from this file creates an admin with the local development password in the
real database (this happened in Phase 2; the password was replaced when the production admin was
seeded in Phase 8).

### 3. Run the check

`--env=tidb` makes Laravel load `.env.tidb` instead of `.env`:

```bash
docker compose exec app php artisan db:show --env=tidb             # confirm the target database first
docker compose exec app php artisan app:verify-database --env=tidb
```

> **Danger — production data.** Since Phase 8 this TiDB database is the live database. Never run
> `migrate:fresh`, `migrate:refresh`, `db:wipe` or `db:seed` against it from your machine: the first
> three delete everything. `app:verify-database` only reads, plus one insert inside a transaction that
> is rolled back. The first-time setup in Phase 2 used `migrate:fresh --seed --env=tidb` on an empty
> database; schema changes now happen through the deploy start script (`migrate --force`).

Expected output: every line `PASS`, then `All checks passed.` The command compares each SQL result
with the same filter computed in PHP, so a silent difference (e.g. JSON numbers compared as strings)
shows up as `FAIL`.

### 4. If a check fails

| Check | Likely cause | Fix (inside `ProductRepository`, never in rules) |
|---|---|---|
| Numeric min/max | JSON value compared as a string | `whereRaw('CAST(JSON_EXTRACT(specs, ?) AS SIGNED) >= ?', ...)` |
| Boolean | JSON `true` vs `1` handling | `whereRaw('JSON_EXTRACT(specs, ?) = CAST(? AS JSON)', ...)` |
| Foreign key | FK not enforced on the cluster | Confirm TiDB version ≥ 6.6 with FK enabled; RESTRICT is also checked in `ProductService` |
| Total price | `having` + `paginate` count query | Filter with `whereRaw` on the subquery instead of `having` |

Record the result (and any fix) in `DECISIONS.md`.

### Result (2026-09-22)

TiDB `v8.5.3-serverless` (Starter, `ap-southeast-1`): all migrations ran, seeders completed, and all
9 checks passed with no repository changes needed (D-033). Seeding takes about 45 s because every
insert is a network round trip from the local machine to the cloud region.
