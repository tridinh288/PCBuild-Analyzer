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
Status: Proposed
Decision: UI in Vietnamese; code, identifiers, commits, docs in English; prices in VND.
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
Status: Accepted
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
Status: Proposed
Decision: Specifications are small combinable boolean conditions; Rules combine them and produce a `CompatibilityResult` with a message.
Why: Keeps Specification Pattern meaningful instead of duplicating rules.
Alternatives: Drop Specification Pattern if it adds no value.
Consequences: Confirm concrete boundary when implementing the first four rules.

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
Status: Proposed
Decision: To confirm in Phase 1: run Vite in a Docker service or on the host.
Why: Trade-off between one-command setup and simpler hot reload.
Consequences: Affects docker-compose and README.

---

## Process

## D-026: Git workflow
Status: Accepted
Decision: `.gitignore` first; one branch per phase; commit per logical step with Conventional Commits; push after each commit; merge to `main` only after approval; no force push; stop on push failure.
Why: Readable history for reviewers; safe automation.
Consequences: Tests and secret checks run before every commit.

## D-027: Testing
Status: Proposed
Decision: PHPUnit; local MySQL test database; `FakeImageStorage`. Optional GitHub Actions CI.
Why: Default in Laravel; tests match production SQL dialect more closely than SQLite.
Alternatives: Pest; SQLite in-memory.
Consequences: Docker must be running for tests.

## D-028: Laravel version
Status: Proposed
Decision: Use the latest stable Laravel release at project start; confirm in Phase 1.
Consequences: Update README and composer constraints accordingly.
