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

### Pointing the services at another branch

`render.yaml` does not declare `branch`, so each service tracks whatever branch it was created from —
during development that is the phase branch. After a phase is merged, move both services to `main`,
or they keep deploying the old branch and `main` never reaches production.

Do it per service, twice (`pcbuild-api` and `pcbuild-web`):

1. Dashboard → the service → **Settings**.
2. In **Build & Deploy**, find **Branch** → **Edit** → pick `main` → **Save changes**.
3. Render redeploys from the new branch. If it does not, use **Manual Deploy → Deploy latest commit**.

For `pcbuild-web`, use **Manual Deploy → Clear build cache & deploy** instead: it is a static site and
`VITE_API_URL` is compiled into the bundle, so a cached build can ship the old value.

After the redeploy, confirm the browser is really running the new bundle — assets are served with
`Cache-Control: immutable`, so a stale one survives an ordinary reload. Open the site in a private
window, or check that the `index-*.js` filename in DevTools → Network matches the one in the new
`dist/`. A deploy that looks fine to `curl` can still be broken in a browser that cached the previous
bundle.

### Loading the placeholder images

The seeded parts and templates have no photographs. `backend/resources/seed-images/` holds a
generated card per row — the real model name, the real highlight specs and the real price on a
category-coloured background (D-041). Upload them once, after the database is seeded:

```bash
# Render Dashboard → pcbuild-api → Shell
php artisan app:import-images --dry-run   # matches files to rows, uploads nothing
php artisan app:import-images             # 50 products + 10 builds
```

**The free plan has no Shell.** Run the same command from your own machine instead, pointed at the
production database with `--env=tidb` (§ TiDB check below). The command only needs the database and
the Cloudinary API, both reachable from anywhere — nothing about it requires running on the server:

```bash
# 1. Put the CLOUDINARY_URL from the Render dashboard into backend/.env.tidb (the key is
#    already there, empty). That file is gitignored; leave ADMIN_PASSWORD empty as before.
#    Paste only the cloudinary://... part. Both the Cloudinary and the Render pages show the
#    whole `CLOUDINARY_URL=cloudinary://...` line, and pasting that after the `=` already in
#    the file gives `CLOUDINARY_URL=CLOUDINARY_URL=cloudinary://...`, which parses as a value
#    with no scheme — so the app reports the variable as unset while it looks present.
# 2. Confirm the target is `pcbuild`, never `sys`:
docker compose exec app php artisan db:show --env=tidb
# 3. Match files to rows without uploading, then upload:
docker compose exec app php artisan app:import-images --env=tidb --dry-run
docker compose exec app php artisan app:import-images --env=tidb
```

This writes `image_public_id` on live rows and uploads to the live Cloudinary account, so it is a
real production change — but a narrow one: it touches no other column, creates and deletes no rows,
and the `--dry-run` above shows exactly which rows it will touch first. Unlike `migrate:fresh` or
`db:seed`, it is safe to run against the live database from a laptop.

Files are matched to rows by slug (`products/<slug>.jpg`, `builds/<slug>.jpg`). Rows that already
have an image are skipped, so an interrupted run can just be repeated; `--force` replaces them and
deletes the image it replaced. A file whose slug matches no row is reported and fails the command
rather than being ignored.

To regenerate the cards — after editing the seed data, or to change the design:

```bash
# Locally, with the stack running; needs Chrome, which the server does not have
node tools/export-seed-data.mjs      # refresh tools/seed-data.json from the API
node tools/generate-seed-images.mjs  # redraw the 60 cards
```

The export reads the **public API**, not the database, so the specs printed on a card are formatted
by `config/hardware.php` exactly as the UI formats them ("2.000 GB", "65 W", "AM5") instead of being
re-derived from raw JSON. Point it elsewhere with `--api https://pcbuild-api-2mwk.onrender.com/api`.

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
| Image upload: "Chưa cấu hình dịch vụ ảnh" | `CLOUDINARY_URL` not set on the API service, or set to something that is not a `cloudinary://` URL. A variable name pasted in with the value is stripped automatically, so this now means the value is wrong in some other way. |
| Images exist in the database but every `image` field is `null` | The API cannot parse `CLOUDINARY_URL`, so it has no cloud name to build a URL from. Check the value on the API service and redeploy. Nothing logs this — the rows look fine and the pictures simply never appear. |
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
>
> `--env=tidb` is not off limits in itself — those four commands are. `app:import-images --env=tidb`
> is the intended way to load the images on the free plan (§ 2): it only sets `image_public_id` on
> rows that already exist.

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
