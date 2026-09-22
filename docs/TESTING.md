# Test Report

State at the end of Phase 7 (2026-09-22). Decisions: D-027 (PHPUnit + MySQL + CI), D-022/D-037 (no
external calls in tests).

## Summary

| Suite | Tool | Tests | Result |
|---|---|---|---|
| Backend | PHPUnit 12 (Laravel 13) on MySQL 8.4 | 270 tests, 959 assertions | ✅ pass |
| Frontend | Vitest 5 | 37 tests (11 files) | ✅ pass |
| Lint | Pint (PSR-12 / Laravel), oxlint (warnings denied) | — | ✅ clean |
| CI | GitHub Actions: backend + frontend jobs on every push | — | ✅ green |

**Backend coverage (PCOV):** lines **98.5 %** (1553/1577), methods 95.7 %, classes 85.6 %.
Uncovered lines are hardware getters the engine does not use yet (`Cpu::threads`, `Psu::efficiencyRating`…),
defensive branches (unknown strategy profile) and a few `VerifyDatabaseCommand` failure messages.

## What is tested

### Unit tests (no database, no Laravel boot — D-030)

`tests/Unit/Domain` extends plain `PHPUnit\Framework\TestCase` through `DomainTestCase`, which builds
components with valid default specs so each test only states what it checks.

| Spec § 30 requirement | Where |
|---|---|
| Each MVP rule: compatible, incompatible, skipped case | `Compatibility/Rules/FirstRulesTest`, `RemainingRulesTest` (13 rules) |
| `BuildConfiguration::with()` returns a new instance | `Configuration/BuildConfigurationTest` |
| RAM quantity exceeding `ram_slots` → incompatible | `FirstRulesTest::test_ram_quantity_exceeding_slots_is_incompatible` |
| Total RAM exceeding `max_ram_gb` → incompatible | `FirstRulesTest::test_total_ram_exceeding_max_capacity_is_incompatible` |
| DisplayOutputRule: CPU without iGPU and no GPU | `RemainingRulesTest::test_display_output` |
| Candidate RAM not flagged by an existing CPU/motherboard mismatch | `CompatibilityEngineTest`, `BuilderServiceTest` |
| PowerCalculator: estimate, safety factor, PSU rounding | `Analysis/PowerCalculatorTest` |
| PriceAnalyzer: totals with quantity, percentages | `Analysis/PriceAnalyzerTest` |
| Each strategy: weights produce expected scores | `Analysis/PerformanceAnalysisTest` (plus profile-specific adjustments, D-031) |
| Comparison: differences detected | `Analysis/ComparisonAnalyzerTest`, `Feature/Services/ComparisonServiceTest` |
| SpecFormatter: display values and units | `Unit/Support/SpecFormatterTest` |

Also: config integrity (`HardwareConfigTest`: every spec references a real enum, weights sum to 1…),
strict spec validation (`SpecSchemaTest`), specifications and combinators, Cloudinary signing and
error handling with `Http::fake()`.

### Feature tests (HTTP + MySQL)

| Spec § 30 requirement | Where |
|---|---|
| `GET /api/builds` filters, pagination | `Api/BuildApiTest` |
| `GET /api/builds/{slug}` and 404 | `Api/BuildApiTest` |
| `GET /api/builds/{slug}/analysis` | `Api/BuildApiTest` (default profile, switching, 404, 422) |
| `GET /api/components` with spec filters | `Api/CatalogApiTest`, `Repositories/ProductRepositoryTest` |
| Builder options: DDR4 RAM incompatible with DDR5 board | `Api/BuilderApiTest` |
| Builder options with `compatible_only` | `Api/BuilderApiTest` |
| Builder analyze: missing IDs for deleted/inactive products | `Api/BuilderApiTest` |
| Builder analyze: invalid input → 422 | `Api/BuilderApiTest` (8 malformed selections) |
| `POST /api/compare` | `Api/CompareApiTest` |
| Admin login/logout, unauthorized → 401 | `Admin/AuthTest` (plus expiry, revocation, rate limit) |
| Admin product create/update/delete, spec validation errors | `Admin/ProductAdminTest` (plus 409 when in use) |
| Admin image upload with FakeImageStorage | `Admin/ProductImageTest`, `Admin/BuildAdminTest` |

Also: response envelope and Vietnamese error messages (400, 404, 405, 422, 429, 500 without details),
rate limits and CORS, seeders validated against config and idempotent, `withTotalPrice()` equal to
`PriceAnalyzer` for every template (D-032), every seeded template free of incompatible parts, and
`app:verify-database` on MySQL.

### Frontend (Vitest)

Pure modules only: Builder and comparison URL formats, `builderReducer`, price/power formatting,
draft storage (including blocked storage), text export, recommendations, compatibility helpers,
spec form conversion, server-waking store. Components are not unit-tested; see Limitations.

## Test isolation

- `Tests\TestCase` binds `FakeImageStorage` and calls `Http::preventStrayRequests()`: no test can reach
  Cloudinary or any other service.
- Feature tests use `RefreshDatabase` (one transaction per test) on the separate `pcbuild_test` database.
- Tests that need demo data call `$this->seed()` in `setUp()`. `protected $seed = true` is not used: it
  only seeds on the first migration of a run, which made tests pass alone and fail in the full suite
  (fixed in Phase 3).

## Flaky test found and fixed (Phase 7)

`CategoryFactory` used `fake()->slug(2)`, occasionally longer than the `VARCHAR(32)` slug column. About
one full run in three failed at a random test with "Data too long for column 'slug'". Found by running
the suite repeatedly and saving every failure; fixed with a fixed-length slug. Three consecutive full
runs and every CI run since have passed.

## Running the tests

```bash
docker compose exec app php artisan test                          # sequential
docker compose exec app php artisan test --parallel --processes=4 # ~40 % faster locally
docker compose exec app php artisan test --parallel --processes=4 \
  --cache-directory=.phpunit.cache --coverage-text=storage/coverage.txt   # coverage (PCOV)
cd frontend && npm test
```

## Timing

| Where | Backend suite |
|---|---|
| GitHub Actions (Linux) | ~26 s |
| Local Docker on Windows, sequential | ~130–145 s |
| Local Docker on Windows, 4 processes | ~80–105 s |

The local cost is per-test application boot and database work over a Windows bind mount. Enabling
OPcache for the CLI was measured and gave no improvement, so it is not configured.

## Limitations

- No browser end-to-end tests (Playwright/Cypress): out of scope for the MVP.
- React components are not unit-tested; their logic lives in the tested pure modules.
- The UI was not verified in a real browser during Phases 5–6 (browser tooling unavailable).
- Cloudinary is tested with faked HTTP only; a live upload needs `CLOUDINARY_URL` (Phase 8).
