# Decisions Log

Short records of important decisions. Read at the start of every session.

Status values:
- **Accepted**: agreed, follow it.
- **Proposed**: default chosen, confirm with me in Phase 1.
- **Superseded by D-XXX**: replaced; keep the entry for history.

To change a decision: do not edit the old entry's content. Mark it Superseded and add a new entry.

Template:

```text
## D-XXX: Title
Status: Accepted | Proposed | Superseded by D-YYY
Decision: what we do.
Why: the main reason.
Alternatives: what else was considered.
Consequences: what this implies or costs.
```

---

## Scope & product

## D-001: Public analysis app, not e-commerce
Status: Accepted
Decision: Public users need no account. No cart, payment, orders, shipping. One admin for data management.
Why: Keep scope realistic; the analysis engine is the core value, not CRUD.
Alternatives: User accounts with saved builds (rejected: adds auth complexity with little portfolio value).
Consequences: All public endpoints are stateless reads or stateless analysis.

## D-002: Three modes share one Builder
Status: Accepted
Decision: Browse templates / customize a template / build from scratch. Modes 2 and 3 are the same Builder page with different initial state. Templates are never modified by users.
Why: One feature to build and test instead of two; the compatibility engine gets real use.
Alternatives: Templates only (rejected: engine would rarely find problems in curated builds).
Consequences: Engine must handle incomplete configurations and any selection order.

## D-003: UI language, code language, currency
Status: Accepted (Phase 1)
Decision: UI in Vietnamese; code, identifiers, commits, docs in English; prices in VND. No i18n layer for now.
Why: Target employers in Vietnam; English code is standard practice.
Alternatives: Full English UI; Laravel lang files for multi-language (future).
Consequences: Spec labels in `config/hardware.php` are Vietnamese.

---

## Data

## D-004: Product specs in a JSON column, defined in config
Status: Accepted
Decision: `products.specs` is JSON. `config/hardware.php` defines per-category keys, labels, units, types, validation, filters, highlights, slot rules, and power constants.
Why: Avoids a wide table of NULL columns; one place drives display, validation, filters, and admin forms.
Alternatives: One spec table per category (strict but many tables); EAV (flexible but hard to query/validate).
Consequences: The database does not enforce keys. Form Requests and seeders must validate against config.

## D-005: Spec value conventions
Status: Accepted
Decision: Numbers without units, enums as lowercase codes, real booleans, arrays for multi-values. Related categories share enum codes (e.g. form factors).
Why: Rules can compare values directly; labels can change without data migration.
Alternatives: Human-readable strings ("65W", "Micro-ATX").
Consequences: Display text is produced by `SpecFormatter`.

## D-006: Categories are a fixed set
Status: Accepted
Decision: cpu, motherboard, ram, gpu, storage, psu, case, cooler. Admin can edit name, description, sort order only.
Why: A category without spec definitions and rules is meaningless; code depends on these slugs.
Alternatives: Full category CRUD.
Consequences: Adding a category is a code change, not an admin action.

## D-007: Deactivate products instead of deleting
Status: Accepted
Decision: `products.is_active`; `build_items.product_id` uses ON DELETE RESTRICT.
Why: Templates and shared links keep working when a product is retired.
Alternatives: Soft deletes; cascade deletes.
Consequences: Inactive products are hidden from Builder and catalog; APIs report them as missing in custom selections.

## D-008: Build total price is computed, not stored
Status: Superseded by D-032
Decision: Use `withSum` / analyzers.
Why: Stored totals drift when product prices change.
Alternatives: Cached total column.
Consequences: Slightly more work per query; negligible at this data size.

## D-009: Slugs in public URLs; no assumptions about ID order
Status: Accepted
Decision: Public routes use slugs. Code and tests never assume consecutive IDs.
Why: TiDB allocates AUTO_INCREMENT IDs in batches; slugs are also more readable.
Alternatives: Numeric IDs in URLs.
Consequences: Builder URLs still use product IDs (compact), which is fine because they are not assumed sequential.

## D-010: Prices are integer VND
Status: Accepted
Decision: `BIGINT UNSIGNED`; formatted on the frontend with `Intl.NumberFormat('vi-VN')`.
Why: VND has no fractional part; floats cause rounding errors.
Alternatives: DECIMAL.
Consequences: API returns raw numbers.

---

## Analysis engine

## D-011: Engine works on BuildConfiguration, not Eloquent models
Status: Accepted
Decision: Immutable `BuildConfiguration` built by `BuildConfigurationFactory` from a template or a selection. `with()` / `without()` return new instances.
Why: Same engine for saved and custom builds; unit tests need no database; no hidden mutation when testing candidates.
Alternatives: Rules receive `Build` model.
Consequences: Hardware objects created by `HardwareFactory` expose typed getters over JSON specs.

## D-012: Compatibility rule contract
Status: Accepted
Decision: `involves()`, `appliesTo()`, `check()`. Statuses: compatible, warning, incompatible, skipped. Rules registered in a service provider.
Why: Order-independent checks; missing parts are skipped, not errors; new rules need no engine changes (Open/Closed).
Alternatives: One big compatibility service with if/else.
Consequences: Candidate options only show issues from rules involving the candidate's category.

## D-013: Compatibility logic lives only in PHP
Status: Accepted
Decision: Filtering order: repository filters (SQL) → engine evaluates candidates (PHP) → compatible_only.
Why: Single source of truth for rules; catalog is small.
Alternatives: Encode rules as SQL conditions.
Consequences: With a large catalog, add SQL pre-filtering for simple conditions (e.g. socket) while keeping PHP rules authoritative.

## D-014: Rule vs Specification boundary
Status: Accepted (Phase 1)
Decision: A Specification is a small boolean condition over plain values (it knows nothing about `BuildConfiguration`) and is combinable with `and()` / `or()` / `not()`. A Specification exists only when the condition is reused by at least two rules (e.g. value-in-set for sockets and form factors, fits-within for GPU length, cooler height, slots and capacity). A Rule reads the configuration, picks the values, uses specifications, and produces a `CompatibilityResult` with a message. One-off rules (DisplayOutputRule, CpuCoolingRule) compare directly.
Why: Keeps Specification Pattern meaningful instead of wrapping one-line comparisons.
Alternatives: Specification in every rule (uniform but hollow); drop the pattern (simplest, but duplicated comparisons).
Consequences: Confirm the concrete boundary when implementing the first four rules.

## D-015: Estimates, not benchmarks
Status: Accepted
Decision: Power = component estimates × 1.25, rounded up to common PSU sizes. Score = weighted sub-scores using admin-entered `performance_tier`. Always labeled as estimates.
Why: Honest about limitations; no real benchmark data.
Alternatives: Scraped benchmark data (non-goal).
Consequences: README includes an analysis limitations section.

## D-016: Strategy selection
Status: Accepted
Decision: Default profile = template purpose (or General Use for custom builds). User can switch profile in the UI.
Why: Demonstrates Strategy Pattern visibly.
Alternatives: Fixed profile per build.
Consequences: Analysis endpoints accept an optional `profile`.

## D-017: Comparison never declares a winner
Status: Accepted
Decision: Show facts and "what changed" only.
Why: Suitability depends on the user's needs.
Consequences: None technical.

---

## Builder & frontend

## D-018: Incompatible parts can be selected
Status: Accepted
Decision: All products are shown with status and reason. Incompatible parts can be selected; slots turn red and a summary banner counts issues. Toggle to show compatible parts only.
Why: Users select in any order; hard blocking is frustrating.
Alternatives: Hide incompatible parts; block selection.
Consequences: Analysis must still run on invalid configurations.

## D-019: Custom configurations live in the URL
Status: Accepted
Decision: Builder state is synced to the query string. Copy link, copy as text, localStorage draft autosave. Nothing stored in the database.
Why: Shareable without accounts; survives refresh.
Alternatives: Saved builds table (requires accounts or spam handling).
Consequences: APIs return a `missing` list for unknown or inactive product IDs.

## D-020: Builder state management
Status: Accepted
Decision: `useReducer` + `useBuilder()` custom hook; no external state library.
Why: Shows React fundamentals; state is local to one page.
Alternatives: Redux, Zustand.
Consequences: Keep reducer pure and unit-testable.

---

## Infrastructure & security

## D-021: Database per environment
Status: Accepted
Decision: MySQL 8 in Docker for development and tests; TiDB Cloud Starter in production over TLS (port 4000).
Why: TiDB is MySQL-compatible and free long-term; Render's free Postgres expires.
Alternatives: Render Postgres; self-hosted MySQL.
Consequences: Verify migrations and JSON queries on TiDB in Phase 2.

## D-022: Images on Cloudinary behind an interface
Status: Accepted
Decision: Store `image_public_id` only. `ImageStorage` interface with Cloudinary and Fake implementations. Presets: thumb, large. Uploads go through the API; old images are deleted on replace.
Why: Render's filesystem is ephemeral; public_id allows transformations and provider changes.
Alternatives: Local storage (lost on redeploy); storing full URLs.
Consequences: Tests never call Cloudinary.

## D-023: Admin authentication with Sanctum API tokens
Status: Accepted
Decision: Bearer tokens with expiry; logout revokes. Single admin seeded from env. No registration.
Why: Frontend and API are on different onrender.com subdomains; onrender.com is on the Public Suffix List, so cross-subdomain cookies fail.
Alternatives: Sanctum SPA cookies with a custom domain or rewrites.
Consequences: Token stored in the browser; document the XSS trade-off.

## D-024: Hosting on Render
Status: Accepted
Decision: API as Docker web service; React as static site with `/*` → `/index.html` rewrite. Migrations run in the container start script.
Why: Free tier; no native PHP runtime; pre-deploy commands are paid-only.
Alternatives: Other hosts.
Consequences: Cold starts on free tier; frontend shows a "server waking up" message.

## D-025: Frontend in Docker for local development
Status: Accepted (Phase 1)
Decision: Vite runs on the host (`npm run dev` in `frontend/`). Docker Compose runs only the API (PHP) and MySQL.
Why: The project lives on a Windows drive; Vite inside a container would need file polling for hot reload (slow, CPU heavy) and slow `node_modules` on a bind mount. Node is already installed on the host.
Alternatives: Vite as a Compose service (one-command start, slower HMR); both via a Compose profile (two setups to maintain).
Consequences: README documents two start commands. No `frontend` service in `docker-compose.yml`.

---

## Process

## D-026: Git workflow
Status: Accepted
Decision: `.gitignore` first; one branch per phase; commit per logical step with Conventional Commits; push after each commit; merge to `main` only after approval; no force push; stop on push failure.
Why: Readable history for reviewers; safe automation.
Consequences: Tests and secret checks run before every commit.

## D-027: Testing
Status: Accepted (Phase 1)
Decision: PHPUnit; separate `pcbuild_test` database in the local MySQL container; `FakeImageStorage`. Domain unit tests extend plain `PHPUnit\Framework\TestCase` (no database, no Laravel boot). GitHub Actions CI runs the suite with a MySQL service on every push.
Why: Default in Laravel; tests match production SQL dialect (JSON queries, foreign keys) more closely than SQLite; a green CI badge is visible to reviewers.
Alternatives: Pest; SQLite in-memory; no CI.
Consequences: Docker must be running for feature tests. CI workflow added in Phase 7.

## D-028: Laravel version
Status: Accepted (Phase 1)
Decision: Laravel 13 (`laravel/framework: ^13.0`, latest stable v13.32.0 at project start, 2026-09-22), PHP `^8.3`.
Why: Latest stable release; meets the spec ("12 or newer").
Alternatives: Laravel 12 (more tutorials, shorter remaining support).
Consequences: Docker image and CI use PHP 8.3. README states the versions.

## D-029: Local toolchain
Status: Accepted (Phase 1)
Decision: Docker (PHP 8.3 + MySQL) is the runtime for the API and tests. The host uses Laragon's PHP 8.3 (placed before XAMPP's PHP 8.0 in PATH) for Composer and IDE tooling. Node 24 on the host for the frontend. Default branch is `main`.
Why: Laravel 13 requires PHP 8.3; the machine had XAMPP PHP 8.0 first in PATH.
Alternatives: Run every PHP/Composer command through Docker only (no local PHP for the IDE).
Consequences: README lists the required versions and a `php -v` check.

---

## Planning (Phase 1)

## D-030: Domain has no database or config access; structure adjustments
Status: Accepted (Phase 1)
Decision: `app/Domain` contains pure PHP only (no Eloquent, no queries, no `config()` calls). Config values are passed in through constructors by a service provider. Therefore: analyzers and `CompatibilityEngine` live in `app/Domain`, `BuildConfigurationFactory` (loads products) lives in `app/Services`, `SpecSchema` / `SpecFormatter` live in `app/Support/Hardware`. `compatible_only` removes incompatible candidates and keeps warnings.
Why: Domain unit tests run on plain PHPUnit without Laravel or a database; the folder tree shows the boundary.
Alternatives: Follow spec section 6 literally (analyzers under Services, factory under Domain).
Consequences: One more service provider wiring constructor arguments. Details in `docs/ARCHITECTURE.md`.

## D-031: Strategies differ by more than weights
Status: Accepted (Phase 1)
Decision: Each strategy has its weights (from config) plus one profile-specific adjustment with an explanatory note: Gaming penalizes a large CPU/GPU tier gap (bottleneck); Programming and Workstation add a note/penalty below a RAM capacity threshold; General Use keeps balanced weights with no adjustment. Sub-scores are computed once by a shared `SubScoreCalculator`.
Why: If strategies only differed by four numbers, a single weighted scorer with data would be simpler and the Strategy Pattern would be hard to defend in an interview.
Alternatives: Weights-only strategies (spec baseline); one scorer class with a weights table and no Strategy Pattern.
Consequences: Thresholds and penalties are config values; each strategy gets its own unit tests.

## D-032: Build total price via a subquery scope
Status: Accepted (Phase 1)
Decision: The total is still computed, never stored (as in D-008), but with a `withTotalPrice()` query scope: a correlated subquery `SUM(products.price * build_items.quantity)`. `PriceAnalyzer` computes the same total from a loaded configuration.
Why: `withSum()` sums one column of the related table; it cannot multiply the product price by the item quantity across the join.
Alternatives: `withSum` ignoring quantity (wrong for RAM ×2); a stored `total_price` column (drifts when prices change).
Consequences: The build list can filter and sort by total price in SQL. A test asserts both paths return the same total.
