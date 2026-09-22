# PCBuild Analyzer

> Status: in development (Phase 1 — Planning). Sections marked _TBD_ are filled in later phases.

A public web app for analyzing PC builds: browse admin-curated templates, customize them or build
from scratch, and get real-time compatibility checks, an estimated power draw with a recommended
PSU, a price breakdown, and an estimated configuration score. Configurations can be compared
side by side and shared by link — no account needed.

This is a student portfolio project (Junior/Intern Fullstack). The focus is the
**PC Build Analysis Engine**, not CRUD. It is **not** an e-commerce site: no cart, payment, or orders.

## Live Demo

_TBD (Phase 8)._ Free hosting may take about 1 minute to wake up on the first request.

## Features

- Browse, filter, and compare PC build templates
- Builder: customize a template or start from scratch, in any order
- Real-time compatibility checks (13 rules: socket, RAM type/capacity, form factors, clearances, PSU wattage, …)
- Power estimate and recommended PSU size
- Price breakdown by category (VND)
- Estimated configuration score per profile: Gaming, Programming, Workstation, General Use
- Share configurations by link or as text; draft autosave
- Component catalog with category-specific filters
- Admin area: products, templates, images

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Laravel 13, Eloquent, Sanctum (API tokens), PHPUnit |
| Frontend | React, Vite, JavaScript, React Router, Axios, Tailwind CSS |
| Database | MySQL 8 (local, Docker) · TiDB Cloud Starter (production) |
| Images | Cloudinary |
| Hosting | Render (API as Docker web service, React as static site) |
| CI | GitHub Actions |

## Architecture

```text
React → Axios → Laravel REST API → Controller (thin) → Service → Domain → Repository → Eloquent → MySQL/TiDB
```

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md). Deployment diagram: _TBD (Phase 8)._

## Database

See [docs/DATABASE.md](docs/DATABASE.md). ERD image: _TBD (Phase 9)._

## API Documentation

See [docs/API.md](docs/API.md). Example requests: _TBD (Phase 4)._

## Design Patterns

MVC, Service Layer, Repository, Strategy, Factory, Specification, Dependency Injection — where each
is used and where it is deliberately not used: [docs/ARCHITECTURE.md § 4](docs/ARCHITECTURE.md#4-design-patterns--where-and-where-not).

## Analysis Limitations

- Power consumption is a rule-based **estimate** from component TDP plus fixed constants, not a measurement.
- The configuration score is a **project-defined** weighted score based on admin-entered performance
  tiers. It is not benchmark data.
- Compatibility covers the rules listed above; some physical constraints (radiator size, PCIe power
  connectors, drive bays) are not checked yet.

## Requirements

- PHP 8.3+ and Composer 2 (host, for tooling)
- Node.js 20+ (tested with 24) and npm
- Docker Desktop with Docker Compose
- Git

Check: `php -v` must report 8.3 or newer.

## Installation (local)

The API and MySQL run in Docker; the React app runs on the host (D-025).

```bash
# 1. Environment
cp backend/.env.example backend/.env
#    set DB_PASSWORD=pcbuild_local (matches docker-compose.yml) and ADMIN_EMAIL / ADMIN_PASSWORD

# 2. API + MySQL
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed

# 3. Check
curl http://localhost:8000/api/status

# 4. Frontend (Phase 5)
cd frontend && npm install && npm run dev
```

MySQL is published on host port `3307` (user `pcbuild`) for GUI clients such as HeidiSQL.

## Deployment

_TBD (Phase 8): Render, TiDB Cloud, Cloudinary._

## Testing

PHPUnit runs against a separate `pcbuild_test` MySQL database (created by `docker/mysql/init`).

```bash
docker compose exec app php artisan test
docker compose exec app php artisan app:verify-database   # JSON queries / FK checks on the current DB
```

Full test report: _TBD (Phase 7)._

## Screenshots

_TBD (Phase 9)._

## Future Improvements

- More compatibility rules: AIO radiator size vs case, GPU power connectors vs PSU, drive bays
- "Explain this build" (AI) based only on verified analysis data
- Multi-language UI

## Project Documents

- [Project specification](docs/PROJECT_SPEC.md)
- [Decisions log](DECISIONS.md)
