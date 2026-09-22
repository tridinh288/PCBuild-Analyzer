# Architecture Plan

Phase 1 planning output. Covers scope, architecture, class responsibilities, and where each
design pattern is (and is not) used. Database and `config/hardware.php` design: `docs/DATABASE.md`.
API design: `docs/API.md`. Decisions referenced as `D-XXX` are in `DECISIONS.md`.

---

## 1. Scope

### In scope (MVP)

| Area | What is delivered |
|---|---|
| Templates | List with search/filter/sort/pagination, detail page, analysis, "Tùy chỉnh cấu hình này" |
| Builder | 8 slots, any selection order, candidate status per product, incompatible parts selectable, URL state, copy link, copy as text, localStorage draft |
| Catalog | Component list per category with common + category filters (rendered from config), component detail |
| Analysis engine | 13 MVP compatibility rules, power estimate + recommended PSU, price breakdown, estimated score with 4 profiles |
| Comparison | 2–3 configurations (templates and/or custom), aligned table, "Có gì khác nhau?", no winner |
| Admin | Token login, category editing, product CRUD with schema-driven spec form, images on Cloudinary, build CRUD with live compatibility |
| Quality | Unit + feature tests, GitHub Actions CI, Docker for local dev, deployed demo on Render + TiDB |

### Out of scope

Everything in spec section 38 (non-goals), plus for now:

- The optional AI "explain this build" feature (section 37) → listed as a future improvement.
- phpMyAdmin service → not needed; Laragon ships HeidiSQL, and any MySQL client can connect to the container port.
- The three "future" compatibility rules (AIO radiator, PCIe power connectors, drive bays).
- Multi-language UI (D-003).

### Priority if time runs short

1. Engine + Builder + Build detail (the core value).
2. Catalog, comparison.
3. Admin (seeders can provide data until admin exists).
4. Polish: draft autosave, responsive details, screenshots.

---

## 2. Layers

```text
React (pages → hooks/reducer → services/api.js)
  │ Axios, JSON
  ▼
Routes (routes/api.php) + middleware (auth:sanctum, throttle)
  ▼
Form Requests (validation) ─► Controllers (thin) ─► API Resources (output shape)
  ▼
Services (application layer: orchestration, uses repositories)
  ▼
Domain (pure PHP: configuration, hardware objects, rules, specifications, calculators, strategies)
  ▲
Repositories (read/filter queries) ─► Eloquent Models ─► MySQL / TiDB
```

### Layer rules

| Layer | May use | Must not |
|---|---|---|
| Controller | Form Request, Service, Repository (simple reads), Resource | Contain business rules, call `config('hardware...')` numbers, build queries with filters |
| Service | Repositories, Domain, `ImageStorage` | Return HTTP responses |
| Domain | Other Domain classes, values passed in through constructors | Touch the database, Eloquent, HTTP, or call `config()` directly |
| Repository | Eloquent, query builder | Contain compatibility logic (D-013) |

**Why "Domain never calls `config()` or the database":** domain classes can then be unit tested with
plain `PHPUnit\Framework\TestCase` (no Laravel boot, milliseconds per test), and the engine behaves
the same for templates and custom builds. Configuration values (power constants, scoring weights,
slot rules) are passed in by the service provider when the container builds the object.

### Structural changes vs. spec section 6 (D-030)

| Spec | Plan | Why |
|---|---|---|
| `CompatibilityEngine`, `PowerCalculator`, `PriceAnalyzer`, `PerformanceAnalyzer`, `BuildAnalyzer` in `app/Services` | Move to `app/Domain/Compatibility` and `app/Domain/Analysis` | They are pure logic over `BuildConfiguration`; keeping them in Domain makes the "no DB in Domain" rule visible from the folder tree |
| `BuildConfigurationFactory` in `app/Domain/Configuration` | Move to `app/Services` | It loads products through the repository (DB access), so it is application code, not pure domain |
| `SpecFormatter` in `app/Services` | `app/Support/Hardware/SpecFormatter` next to a new `SpecSchema` | Both are helpers around `config/hardware.php`, used by Resources, Form Requests, seeders |
| — | New `app/Support/Hardware/SpecSchema` | One class reads `config('hardware')`; avoids `config('hardware.categories.cpu.specs...')` scattered across Form Requests, filters, seeders |
| — | New `app/Enums/CompatibilityStatus` | Backed enum for `compatible / warning / incompatible / skipped` instead of strings |
| — | New `ProductService`, `BuildService` (admin writes) | Image replace/delete and build item sync need coordination between DB and Cloudinary; keeps admin controllers thin |

### Main request flows

**Build analysis** — `GET /api/builds/{slug}/analysis?profile=gaming`

```text
AnalysisController
  → BuildRepository::findBySlug()                       (404 if not found)
  → BuildConfigurationFactory::fromBuild($build)        → BuildConfiguration
  → BuildAnalyzer::analyze($config, $profile)
        ├ CompatibilityEngine::check()                  → CompatibilityReport
        ├ PowerCalculator::calculate()                  → PowerResult
        ├ PriceAnalyzer::analyze()                      → PriceResult
        └ PerformanceAnalyzer::analyze()                → PerformanceResult (strategy by profile)
  → BuildAnalysisResource
```

**Builder options** — `POST /api/builder/options`

```text
BuilderController
  → BuilderOptionsRequest (validates shape)
  → BuilderService::optionsFor($category, $selected, $filters, $compatibleOnly, $sort)
        1. BuildConfigurationFactory::fromSelection($selected) → config + missing IDs
        2. ProductRepository::search($category, $filters, $sort)     (SQL filters)
        3. for each candidate: $config->with($candidate)             (new instance each time)
           CompatibilityEngine::checkCandidate($candidateConfig, $category)
           → only rules whose involves() contains $category
        4. compatible_only → drop incompatible candidates
  → BuilderOptionResource collection + meta.missing
```

`compatible_only` keeps `compatible` and `warning` candidates (a warning is still buildable).
See D-030.

---

## 3. Backend class responsibilities

### Domain — Hardware (`app/Domain/Hardware`)

| Class | Responsibility |
|---|---|
| `Components/HardwareComponent` (abstract) | Common data: id, category, name, slug, brand, price, image public id, raw specs. No Eloquent |
| `Components/Cpu, Motherboard, Ram, Gpu, Storage, Psu, PcCase, Cooler` | Typed getters over specs (`$cpu->socket()`, `$case->supportsFormFactor('matx')`, `$ram->totalModules($qty)`) |
| `HardwareFactory` | Maps a category slug + attributes (or a `Product` model) to the right component class. The only place that knows the category → class mapping |

### Domain — Configuration (`app/Domain/Configuration`)

| Class | Responsibility |
|---|---|
| `BuildConfiguration` | Immutable set of selected components per category with quantities. `with()`, `without()`, `get()`, `all()`, `quantityOf()`, `missingRequiredSlots()`. Single-slot categories replace; `storage` appends |
| `ConfigurationItem` | Value object: component + quantity |
| `SlotRules` | Required / conditional / multiple rules per slot, built from `config/hardware.php` `slots`. Answers "which required slots are missing" (GPU required if CPU has no iGPU, cooler required if CPU has no boxed cooler) |

### Domain — Compatibility (`app/Domain/Compatibility`)

| Class | Responsibility |
|---|---|
| `Contracts/CompatibilityRule` | `involves()`, `appliesTo()`, `check()` (D-012) |
| `Rules/*Rule` (13 MVP rules) | One hardware constraint each; message in Vietnamese; `details` array with the numbers used |
| `Specifications/Specification` (abstract) | `isSatisfiedBy($candidate)`, `and()`, `or()`, `not()` |
| `Specifications/ValueInSet`, `FitsWithin`, `AtLeast` | Reusable conditions (D-014). `AndSpecification`, `OrSpecification`, `NotSpecification` for combination |
| `Results/CompatibilityResult` | status, rule key, title, message, details |
| `Results/CompatibilityReport` | All results + overall status, error/warning counts, issues per category (for red slots) |
| `CompatibilityEngine` | Runs registered rules; `appliesTo() === false` → `skipped`. `checkCandidate()` restricts to rules involving the candidate's category |

### Domain — Analysis (`app/Domain/Analysis`)

| Class | Responsibility |
|---|---|
| `PowerCalculator` | Estimated watts from component TDP + constants; recommended PSU = estimate × safety factor, rounded up to `psu_steps` |
| `PriceAnalyzer` | Total (with quantity) and per-category amount + percentage |
| `Contracts/AnalysisStrategy` | `profile()`, `score()` |
| `Strategies/Gaming, Programming, Workstation, GeneralUse` | Weights from config + one profile-specific adjustment/note each (see D-031) |
| `SubScoreCalculator` | CPU/GPU/RAM/Storage sub-scores 0–100 (shared by all strategies) |
| `PerformanceAnalyzer` | Picks the strategy for the profile (default General Use), attaches "cannot be assembled" note when incompatible |
| `BuildAnalyzer` | Composes the four analyzers into `BuildAnalysis` (spec section 11) |
| `ComparisonAnalyzer` | Aligns 2–3 analyzed configurations by slot and computes "what changed" deltas. No winner (D-017) |
| `Results/*` | `PowerResult`, `PriceResult`, `PerformanceResult`, `BuildAnalysis`, `ComparisonResult` (readonly value objects) |

### Services (`app/Services`)

| Class | Responsibility |
|---|---|
| `BuildConfigurationFactory` | `fromBuild(Build)`, `fromSelection(array)` → configuration + list of missing/inactive IDs |
| `BuilderService` | Candidate options for one category (flow above) |
| `ComparisonService` | Resolves each input (template slug or selection) into a configuration, analyzes with the same profile, delegates to `ComparisonAnalyzer` |
| `ProductService` | Admin create/update/delete; image upload/replace/remove through `ImageStorage`; delete blocked if used in a build (D-007) |
| `BuildService` | Admin create/update/delete; sync build items; image handling |

### Repositories (`app/Repositories`)

| Class | Responsibility |
|---|---|
| `Contracts/ProductRepositoryInterface` + `Eloquent/ProductRepository` | `findBySlug`, `findActiveByIds`, `search` (category + common + spec filters + sort), `paginate` (catalog, admin) |
| `Contracts/BuildRepositoryInterface` + `Eloquent/BuildRepository` | `findBySlug` (eager loads items.product.category), `featured`, `search` (filters, computed total price, sort, pagination) |

### Support (`app/Support`)

| Class | Responsibility |
|---|---|
| `Hardware/SpecSchema` | Reads `config/hardware.php`: spec definitions, enum labels, validation rules for a category, filter definitions, slot rules, power/scoring constants |
| `Hardware/SpecFormatter` | Raw spec → `{ key, label, value, display, highlight }` |
| `Images/ImageStorage` + `CloudinaryImageStorage` + `FakeImageStorage` | D-022 |
| `ApiResponse` | Builds the `{ success, data, meta }` / `{ success, message, errors }` envelope |

### HTTP (`app/Http`)

- Controllers: `Api/{Category, Component, Build, Analysis, Builder, Compare}Controller`,
  `Admin/{Auth, Category, Product, ProductImage, Build, BuildItems, BuildImage}Controller`.
  Each action: validate → call one service/repository method → return a Resource. ~5–15 lines.
- Form Requests: list in spec section 29. `ProductStoreRequest` gets spec rules from `SpecSchema::rulesFor($category)`.
- Resources: `CategoryResource`, `ProductResource` (formatted specs, image URLs), `BuildResource`,
  `BuildAnalysisResource`, `BuilderOptionResource`, `ComparisonResource`.
- Exception handling in `bootstrap/app.php`: all `/api/*` errors rendered in the error envelope;
  no stack traces when `APP_DEBUG=false`.

### Providers

- `AppServiceProvider`: repository bindings, `ImageStorage` binding (Cloudinary; Fake in tests).
- `AnalysisServiceProvider`: registers the list of compatibility rules and strategies; passes
  config values (power constants, scoring weights, slot rules) into domain constructors.

### Implementation notes (Phase 3)

Differences from the plan above, found while implementing:

| Item | What was built | Why |
|---|---|---|
| `Rules/Rule` (abstract) | Base class for rules: default `appliesTo()` (all involved slots filled), result helpers, `blames()` (slots to highlight) | Removes repeated result-building code from 13 rules |
| `CompatibilityRule::key()` / `title()` | Added to the interface | The engine must report skipped rules with a key and title |
| `Contracts/PresenceRule` | Marker for DisplayOutputRule and CpuCoolingRule; excluded from candidate checks | D-035 |
| `Hardware/EnumLabels` | Code → label lookup for rule messages ('matx' → 'Micro-ATX') | Domain cannot read config; the provider passes the enums in |
| `CompatibilityResult::categories` | Slots to highlight, e.g. only `psu` for the wattage rule | A PSU problem should not paint the CPU slot red |
| `Strategies/WeightedStrategy` | Shared weighted sum (integer arithmetic) + `adjustments()` hook + minimum-RAM helper | D-031 without duplicating the scoring loop |
| `BuilderService::analyze()` | Resolves a selection and runs `BuildAnalyzer`, returns `missing` | Keeps the builder controller a one-liner |
| `BuilderOption` | Candidate product + status + issues + `selected` flag | Typed result instead of nested arrays |
| `PowerCalculator` | Motherboard and fans constants are counted for any non-empty build | Every PC has them; simple to explain |

**Rule vs Specification boundary (D-014, confirmed):** the Rule decides whether it applies, extracts
values from the configuration (e.g. counts M.2 drives), chooses the severity and writes the message.
The Specification only answers one yes/no question about a value (`ValueInSet`, `FitsWithin`,
`AtLeast`). Combinators are used where they read naturally: the PSU warning band is
`AtLeast(estimate)->and(AtLeast(recommended)->not())`.

---

## 4. Design patterns — where and where not

| Pattern | Used in | Real value here | Not used in (and why) |
|---|---|---|---|
| **MVC** | Laravel as API: Model = Eloquent, Controller = thin controllers, View = API Resources (JSON) + React | Standard separation | — |
| **Service Layer** | `BuilderService`, `ComparisonService`, `ProductService`, `BuildService`, `BuildConfigurationFactory` | Controllers stay thin; the same logic serves several endpoints (e.g. analysis for template and builder) | Category editing and admin login: a controller + Form Request is enough; a service would only forward calls |
| **Repository** | Products and Builds | Centralizes filtering queries (JSON specs, computed total price) used by catalog, builder and admin; services can be tested with a mocked repository | Categories, users, admin writes: plain Eloquent. A repository per model would be ceremony without value |
| **Strategy** | `AnalysisStrategy` per purpose | Same build scored differently per profile; the UI switch makes it visible; new profile = new class | Power and price: one algorithm each, no variation → no strategy |
| **Factory** | `HardwareFactory` (category → component class), `BuildConfigurationFactory` (template or selection → configuration) | Rules never read raw JSON; two very different sources produce the same object | Results/value objects: plain constructors. No "factory per component" |
| **Specification** | `ValueInSet`, `FitsWithin`, `AtLeast` + combinators, used inside rules | Each condition reused by 2–4 rules; combinable (e.g. PSU: not `AtLeast(estimate)` → incompatible, else not `AtLeast(recommended)` → warning) | One-off checks (DisplayOutputRule, CpuCoolingRule) compare directly (D-014) |
| **Dependency Injection** | Constructor injection everywhere; bindings in providers; rule list injected into `CompatibilityEngine` | Swap Cloudinary for Fake in tests; add a rule without touching the engine (Open/Closed) | No service locator (`app()`) inside domain classes |
| **Value Object / Immutability** | `BuildConfiguration`, results | Evaluating candidates cannot corrupt the current configuration | — |

---

## 5. Frontend architecture

### Routes

| Path | Page |
|---|---|
| `/` | Home |
| `/builds` | Template list (filters in query string) |
| `/builds/:slug` | Build detail |
| `/builder` | Builder. `?template=<slug>` preloads a template, then the URL is rewritten to explicit IDs |
| `/components` | Catalog (`?category=cpu&...filters`) |
| `/components/:slug` | Component detail |
| `/compare` | Comparison (configurations encoded in the query string) |
| `/analysis` | Full analysis of `?build=<slug>` or of a Builder query string |
| `/admin/login`, `/admin/*` | Admin area behind a `RequireAdmin` route guard |

### Builder URL format

```text
/builder?cpu=3&motherboard=7&ram=12x2&storage=4,9x2&gpu=8&psu=20&case=25&cooler=30
```

`<id>` or `<id>x<quantity>`; comma-separated for multiple items. Parsed and serialized only in
`utils/builderUrl.js` (unit-testable).

### State

- `builderReducer` (pure): `SELECT_PART`, `REMOVE_PART`, `SET_QUANTITY`, `LOAD_TEMPLATE`, `LOAD_FROM_URL`, `RESET`.
- `useBuilder()`: reducer + URL sync + draft autosave + calls `/builder/analyze` (debounced) and `/builder/options`.
- `useFilters()`: filter state ↔ query string.
- No global store. Admin token in a small `AuthContext` + storage (D-023 trade-off documented).

### API client

`services/api.js`: Axios instance with `VITE_API_URL`, bearer token interceptor, error normalization
to `{ message, errors }`, and a "server waking up" flag when a request takes longer than ~5 s.

### Implementation notes (Phase 5)

| Piece | What was built |
|---|---|
| `services/serverStatus.js` | Tiny external store of slow requests (> 5 s), read with `useSyncExternalStore` by the "Máy chủ đang khởi động…" banner |
| `hooks/useApi(load, key)` | Loads one api.* call; aborts the previous request when `key` changes; `loading` is derived during render (no setState inside the effect) |
| `hooks/useFilters()` | Filters ↔ query string; changing a filter resets `page`; `reset(keep, values)` does one URL update |
| `utils/builderUrl.js`, `utils/compareUrl.js` | The only code that reads or writes the Builder (`cpu=3&ram=12x2&storage=4,9x2`) and comparison (`c=slug&c=~<builder query>`) URL formats |
| `reducers/builderReducer.js` | Pure reducer; slot rules come from the API categories in the action payload |
| `hooks/useBuilder()` | Reducer + template loading (`?template=` rewritten to explicit IDs) + URL sync + draft autosave + debounced `/builder/analyze` |
| `utils/draft.js`, `utils/clipboard.js` | localStorage and clipboard access, both guarded; draft restored only when the URL holds no configuration |
| `utils/recommendations.js` | Next steps on the analysis page, restating only what the engine found |
| Backend change | `/builder/analyze` returns `items` (selected products) so a Builder opened from a link can show names and prices |

Unit tests (Vitest, `npm test`) cover every pure module: URL formats, reducer, formatters, draft storage,
text export, recommendations, compatibility helpers and the server status store.

---

## 6. Open points for the end of Phase 1

Recorded as Proposed in `DECISIONS.md` (D-030, D-031):

- Structural changes vs. spec section 6 and the `compatible_only` semantics.
- Making strategies differ by more than weights.
