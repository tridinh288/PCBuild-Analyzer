# Deployment

The full Render + TiDB + Cloudinary guide is written in Phase 8. This file starts with the
TiDB compatibility check from Phase 2 (D-021: verify early, not at the end).

---

## TiDB compatibility check (Phase 2, step 9)

Goal: prove that migrations, JSON spec filters, the computed total price (D-032), and foreign keys
behave on TiDB exactly as on local MySQL.

### 1. Create the database

1. Sign in to [TiDB Cloud](https://tidbcloud.com) and create a **Starter** cluster (free tier).
2. Open **Connect**, choose "General" / "PHP", and note host, port (4000), user, and password.
3. Create a database, e.g. `pcbuild` (SQL editor: `CREATE DATABASE pcbuild;`).

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

Keep `APP_KEY` and the other values from `.env`.

### 3. Run the check

`--env=tidb` makes Laravel load `.env.tidb` instead of `.env`:

```bash
docker compose exec app php artisan migrate:fresh --seed --env=tidb
docker compose exec app php artisan app:verify-database --env=tidb
```

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
