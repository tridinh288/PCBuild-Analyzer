# API Design

Phase 1 planning output. Base URL: `/api`. JSON only. Spec sections 27–29.
Full example requests/responses are added in Phase 4.

---

## 1. Conventions

### Envelope

```json
{ "success": true, "data": { }, "meta": { } }
{ "success": false, "message": "Không tìm thấy cấu hình.", "errors": { } }
```

- `meta` holds pagination (`current_page`, `per_page`, `total`, `last_page`), `missing` IDs,
  the applied `profile`, etc. Omitted when empty.
- `errors` is the Laravel validation bag (`field → [messages]`) on 422; empty object otherwise.
- Messages shown to users are Vietnamese.

### Status codes

| Code | When |
|---|---|
| 200 | Successful read, analysis, update |
| 201 | Admin created a resource |
| 204 | Admin deleted a resource, logout |
| 400 | Malformed JSON body |
| 401 | Missing/expired/revoked token on `/api/admin/*` |
| 403 | Reserved (no roles; not expected in practice) |
| 404 | Unknown slug/ID, or an inactive product on a public detail page |
| 409 | Admin deletes a product still used by a template (D-007) |
| 422 | Validation failed |
| 429 | Rate limit exceeded |
| 500 | Unexpected error (no details when `APP_DEBUG=false`) |

409 is added to the spec's list: "product in use" is a conflict with existing data, not invalid input.

### Identifiers

- Public read routes use slugs (D-009).
- Builder/compare bodies and admin routes use numeric IDs (not assumed sequential).

### Selection format (builder, analysis, compare)

```json
{
  "cpu": 3,
  "motherboard": 7,
  "ram": { "id": 12, "quantity": 2 },
  "storage": [ { "id": 4, "quantity": 1 }, { "id": 9, "quantity": 2 } ]
}
```

- Single slots: an ID, or `{ id, quantity }` (quantity only meaningful for `ram`).
- `storage`: a list of IDs or `{ id, quantity }`.
- Unknown category keys → 422. Unknown or inactive IDs → **not** an error: they go to
  `meta.missing` (`[{ "category": "gpu", "id": 99 }]`) and are left out of the configuration (D-019).

### Rate limits (named limiters)

| Limiter | Routes | Limit |
|---|---|---|
| `api` | public GET | 60 / min / IP |
| `analysis` | `POST /builder/*`, `POST /compare`, `GET /builds/{slug}/analysis` | 30 / min / IP |
| `login` | `POST /admin/login` | 5 / min / IP + email |

Numbers are config values; tuned in Phase 4.

---

## 2. Public endpoints

| Method | Path | Purpose | Notes |
|---|---|---|---|
| GET | `/categories` | Categories in `sort_order`, with slot info | |
| GET | `/categories/{slug}/filters` | Filter definitions for the category (from config) + available brands | Frontend renders filters from this |
| GET | `/components` | Catalog | `category` (required), `search`, `brand`, `price_min`, `price_max`, spec filters, `sort`, `page`, `per_page` |
| GET | `/components/{slug}` | Component detail with formatted specs | 404 if inactive |
| GET | `/builds` | Template list | `search`, `purpose`, `price_min`, `price_max`, `featured`, `sort` (`price_asc`, `price_desc`, `newest`), `page` |
| GET | `/builds/{slug}` | Template detail with items and total | |
| GET | `/builds/{slug}/analysis` | Full analysis | `profile` optional, default = build purpose (D-016) |
| POST | `/builder/options` | Candidates for one category with status against the current selection | Body: `category`, `selected`, `filters`, `compatible_only`, `sort` |
| POST | `/builder/analyze` | Analysis of a custom selection | Body: `selected`, `profile` (default `general_use`) |
| POST | `/compare` | Compare 2–3 configurations | Body: `configurations[]` (`{type: template, slug}` or `{type: custom, selected}`), `profile` |

`POST` for builder/compare because the body carries a configuration; these endpoints are stateless
and write nothing (spec 27).

### Response shapes (outline)

**Component (ProductResource)**

```json
{
  "id": 3, "slug": "amd-ryzen-5-7600", "name": "AMD Ryzen 5 7600", "brand": "AMD", "model": "7600",
  "category": "cpu", "price": 5290000, "is_active": true,
  "specs": [ { "key": "socket", "label": "Socket", "value": "am5", "display": "AM5", "highlight": true } ],
  "image": { "thumb": "https://res.cloudinary.com/...", "large": "https://res.cloudinary.com/..." }
}
```

**Builder option**

```json
{
  "product": { "...": "ProductResource" },
  "status": "incompatible",
  "issues": [ { "status": "incompatible", "rule": "motherboard_ram_type", "title": "Loại RAM",
                "message": "Bo mạch chủ hỗ trợ DDR5, RAM này là DDR4.", "details": { } } ]
}
```

**Analysis (BuildAnalysisResource)**

```json
{
  "compatibility": {
    "status": "warning", "errors": 0, "warnings": 1,
    "results": [ { "status": "warning", "rule": "psu_wattage", "title": "...", "message": "...", "details": { } } ],
    "by_category": { "psu": ["psu_wattage"] }
  },
  "power": { "estimated_watts": 520, "recommended_psu_watts": 650, "selected_psu_watts": 550, "breakdown": { "cpu": 105, "gpu": 285 } },
  "price": { "total": 25490000, "by_category": [ { "category": "gpu", "amount": 11990000, "percent": 47.0 } ] },
  "performance": {
    "profile": "gaming", "score": 74, "sub_scores": { "cpu": 70, "gpu": 80, "ram": 60, "storage": 80 },
    "weights": { "cpu": 0.3, "gpu": 0.45, "ram": 0.15, "storage": 0.1 },
    "notes": [ "Điểm cấu hình ước tính, không phải kết quả benchmark." ]
  },
  "missing_slots": [ "cooler" ]
}
```

**Compare**: per configuration → label, items per slot, total, power, compatibility status, score
(same profile); plus `differences` between consecutive configurations
(`{ "slot": "cpu", "from": "Ryzen 5 7600", "to": "Ryzen 7 7700" }`, `{ "metric": "price", "delta": 3000000 }`).
No winner field (D-017).

---

## 3. Admin endpoints (`auth:sanctum`)

| Method | Path | Purpose |
|---|---|---|
| POST | `/admin/login` | Email + password → `{ token, expires_at, user }` (no auth) |
| POST | `/admin/logout` | Revokes the current token → 204 |
| GET | `/admin/me` | Current admin |
| GET | `/admin/categories` | List |
| PUT | `/admin/categories/{id}` | Edit name, description, sort_order only |
| GET | `/admin/categories/{slug}/spec-schema` | Spec definitions for the product form (types, enums with labels, units, bounds, required conditions) |
| GET | `/admin/products` | Paginated, includes inactive; filters `category`, `search`, `is_active` |
| POST | `/admin/products` | Create (specs validated against config) → 201 |
| GET | `/admin/products/{id}` | Detail with raw specs for editing |
| PUT | `/admin/products/{id}` | Update (category not changeable) |
| DELETE | `/admin/products/{id}` | 204, or 409 if used in a template → deactivate instead |
| POST | `/admin/products/{id}/image` | Multipart upload/replace (jpg, png, webp, ≤ 2 MB) |
| DELETE | `/admin/products/{id}/image` | Remove image |
| GET | `/admin/builds` | Paginated |
| POST | `/admin/builds` | Create → 201 |
| GET | `/admin/builds/{id}` | Detail with items |
| PUT | `/admin/builds/{id}` | Update info, purpose, featured |
| DELETE | `/admin/builds/{id}` | 204 (items cascade, Cloudinary image deleted) |
| PUT | `/admin/builds/{id}/items` | Replace the full item list `[{ product_id, quantity }]`; returns the build + compatibility report for live feedback |
| POST | `/admin/builds/{id}/image` | Upload/replace |
| DELETE | `/admin/builds/{id}/image` | Remove image (added for symmetry with products) |

Admin lists use numeric IDs because they are not public URLs.

---

## 4. Validation (Form Requests)

| Request | Key rules |
|---|---|
| `BuilderOptionsRequest` | `category` in config categories; `selected` passes selection format; `filters` keys allowed for the category; `compatible_only` boolean; `sort` in list |
| `BuilderAnalyzeRequest` | `selected` format; `profile` in `BuildPurpose` values |
| `CompareRequest` | `configurations` array size 2–3; each `type` template (slug required) or custom (selected required) |
| `ProductStoreRequest` / `ProductUpdateRequest` | Common fields + `specs.*` rules from `SpecSchema::rulesFor($category)`; unknown spec keys rejected |
| `ProductImageRequest` / build image | `image` file, mimes jpg/png/webp, max 2048 KB |
| `BuildStoreRequest` / `BuildUpdateRequest` | name, slug unique, purpose enum, is_featured |
| `BuildItemsRequest` | items array; product exists; quantity 1–4; one product per category for single slots (compatibility itself is reported, not blocked — D-018) |
| `CategoryUpdateRequest` | name, description, sort_order |
| `LoginRequest` | email, password |
