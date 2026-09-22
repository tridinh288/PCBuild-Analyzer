# API Design

Base URL: `/api`. JSON only (UTF-8, Vietnamese text unescaped). Spec sections 27–29.
Implemented in Phase 4; real examples in § 5. Admin endpoints (§ 3) come in Phase 6.

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
| `public` | public GET | 60 / min / IP |
| `analysis` | `POST /builder/*`, `POST /compare`, `GET /builds/{slug}/analysis` | 30 / min / IP |
| `login` | `POST /admin/login` | 5 / min / IP + email |

Limits live in `config/api.php` (env overridable). A 429 response carries `Retry-After`.

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

The selection shape (builder, compare) is checked by the `ValidSelection` rule: known slots, one
product for single slots, positive integer IDs and quantities, slot quantity limits from config.

---

## 5. Examples (real responses from the seeded demo data, trimmed with `…`)

IDs come from the local seed and differ elsewhere (D-009).

### GET /api/components?category=cpu&socket=am5&sort=price_asc&per_page=1

```json
{
  "success": true,
  "data": [{
    "id": 2, "slug": "amd-ryzen-5-7600", "name": "AMD Ryzen 5 7600", "brand": "AMD", "model": "7600",
    "category": "cpu", "category_name": "CPU", "price": 5290000, "is_active": true, "description": null,
    "specs": [
      { "key": "socket", "label": "Socket", "value": "am5", "display": "AM5", "highlight": true },
      { "key": "tdp", "label": "TDP", "value": 65, "display": "65 W", "highlight": true },
      { "key": "has_integrated_graphics", "label": "Đồ họa tích hợp", "value": true, "display": "Có", "highlight": false },
      …
    ],
    "image": null
  }],
  "meta": { "current_page": 1, "per_page": 1, "total": 3, "last_page": 3 }
}
```

### GET /api/categories/cpu/filters

```json
{
  "success": true,
  "data": {
    "category": "cpu",
    "common": [
      { "param": "search", "type": "search", "label": "Tìm kiếm" },
      { "param": "brand", "type": "select", "label": "Thương hiệu", "options": [{ "value": "AMD", "label": "AMD" }, …] },
      { "param": "price", "type": "range", "label": "Giá (VND)", "params": ["price_min", "price_max"], "min": 2190000, "max": 10990000 }
    ],
    "specs": [
      { "param": "socket", "key": "socket", "label": "Socket", "filter": "exact", "unit": null,
        "options": [{ "value": "am4", "label": "AM4" }, { "value": "am5", "label": "AM5" }, …] },
      { "param": "cores_min", "key": "cores", "label": "Số nhân", "filter": "min", "unit": null },
      { "param": "has_integrated_graphics", "key": "has_integrated_graphics", "label": "Đồ họa tích hợp", "filter": "boolean", "unit": null }
    ],
    "sorts": [{ "value": "name", "label": "Tên A–Z" }, { "value": "price_asc", "label": "Giá tăng dần" }, …]
  }
}
```

### GET /api/builds/gaming-1440p-nguon-sat-gioi-han/analysis

Default profile = the template's purpose. `?profile=programming` rescores the same build.

```json
{
  "success": true,
  "data": {
    "compatibility": {
      "status": "warning", "errors": 0, "warnings": 1,
      "results": [
        { "status": "compatible", "rule": "cpu_motherboard_socket", "title": "Socket CPU và bo mạch chủ",
          "message": "CPU và bo mạch chủ cùng socket AM5.", "details": { "cpu_socket": "am5", "motherboard_socket": "am5" } },
        { "status": "warning", "rule": "psu_wattage", "title": "Công suất nguồn",
          "message": "Công suất PSU thấp hơn mức khuyến nghị 550W (ước tính 392W).",
          "details": { "estimated_power": 392, "recommended_psu": 550, "selected_psu": 450 } },
        …
      ],
      "by_category": { "psu": ["psu_wattage"] }
    },
    "power": { "estimated_watts": 392, "recommended_psu_watts": 550, "selected_psu_watts": 450,
               "breakdown": { "cpu": 65, "gpu": 245, "ram": 10, "storage": 7, "motherboard": 50, "fans": 15 } },
    "price": { "total": 31630000, "by_category": [{ "category": "cpu", "amount": 7490000, "percent": 23.7 }, …] },
    "performance": {
      "profile": "gaming", "profile_label": "Chơi game", "score": …,
      "sub_scores": { "cpu": 70, "gpu": 64, "ram": 95, "storage": 90 },
      "weights": { "cpu": 0.3, "gpu": 0.45, "ram": 0.15, "storage": 0.1 },
      "adjustments": [], "missing_parts": [],
      "notes": ["Điểm cấu hình ước tính theo quy tắc của dự án, không phải kết quả benchmark."]
    },
    "missing_slots": [], "is_complete": true
  },
  "meta": { "build": "gaming-1440p-nguon-sat-gioi-han", "profile": "gaming" }
}
```

### POST /api/builder/options

```json
{ "category": "ram", "selected": { "motherboard": 10 }, "sort": "price_asc" }
```

```json
{
  "success": true,
  "data": [
    {
      "product": { "id": 16, "name": "Kingston FURY Beast 16GB (2x8GB) DDR4 3200MHz", "price": 1090000, … },
      "status": "incompatible",
      "selected": false,
      "issues": [{ "status": "incompatible", "rule": "motherboard_ram_type", "title": "Loại RAM",
                   "message": "Bo mạch chủ hỗ trợ DDR5, RAM này là DDR4.",
                   "details": { "motherboard_ram_type": "ddr5", "ram_type": "ddr4" } }]
    },
    { "product": { "id": 18, "name": "Kingston FURY Beast 16GB (2x8GB) DDR5 5600MHz", … }, "status": "compatible", "selected": false, "issues": [] },
    …
  ],
  "meta": { "category": "ram", "total": 5, "missing": [] }
}
```

`"compatible_only": true` drops incompatible candidates (warnings stay); `"filters": { "capacity_gb": 32 }`
accepts the same filters as the catalog.

### POST /api/builder/analyze

```json
{ "selected": { "cpu": 3, "motherboard": 10, "gpu": 999999 }, "profile": "gaming" }
```

```json
{
  "success": true,
  "data": {
    "compatibility": { "status": "compatible", "errors": 0, "warnings": 0, "results": ["… rules without their parts are \"skipped\" …"], "by_category": {} },
    "power": { "estimated_watts": 130, "recommended_psu_watts": 450, "selected_psu_watts": null,
               "breakdown": { "cpu": 65, "motherboard": 50, "fans": 15 } },
    "price": { "total": 11780000, "by_category": [{ "category": "cpu", "amount": 7490000, "percent": 63.6 },
                                                   { "category": "motherboard", "amount": 4290000, "percent": 36.4 }] },
    "performance": { "profile": "gaming", "score": …, "missing_parts": ["ram", "storage"],
                     "notes": ["Điểm cấu hình ước tính …", "Cấu hình chưa hoàn chỉnh; phần còn thiếu được tính 0 điểm."] },
    "missing_slots": ["ram", "storage", "psu", "case"],
    "is_complete": false
  },
  "meta": { "profile": "gaming", "missing": [{ "category": "gpu", "id": 999999 }] }
}
```

### POST /api/compare

```json
{
  "configurations": [
    { "type": "template", "slug": "gaming-1080p-am5" },
    { "type": "template", "slug": "gaming-2k-ryzen-7-7800x3d" }
  ],
  "profile": "gaming"
}
```

```json
{
  "success": true,
  "data": {
    "configurations": [
      { "label": "Gaming 1080p AM5", "total_price": 26330000, "estimated_watts": 262, "recommended_psu_watts": 450,
        "compatibility_status": "compatible", "errors": 0, "warnings": 0, "score": 64, "profile": "gaming", "missing_slots": [] },
      { "label": "Gaming 2K Ryzen 7 7800X3D", "total_price": 48720000, "estimated_watts": 422, "recommended_psu_watts": 550,
        "compatibility_status": "compatible", "errors": 0, "warnings": 0, "score": 82, "profile": "gaming", "missing_slots": [] }
    ],
    "slots": [
      { "category": "cpu", "values": [["AMD Ryzen 5 7600"], ["AMD Ryzen 7 7800X3D"]] },
      { "category": "cooler", "values": [[], ["Thermalright Peerless Assassin 120 SE"]] },
      …
    ],
    "differences": [{
      "from": 0, "to": 1,
      "changes": [
        { "type": "slot", "category": "cpu", "from": ["AMD Ryzen 5 7600"], "to": ["AMD Ryzen 7 7800X3D"] },
        …
        { "type": "metric", "metric": "estimated_watts", "from": 262, "to": 422, "delta": 160 },
        { "type": "metric", "metric": "total_price", "from": 26330000, "to": 48720000, "delta": 22390000 },
        { "type": "metric", "metric": "score", "from": 64, "to": 82, "delta": 18 }
      ]
    }]
  },
  "meta": { "profile": "gaming", "missing": [[], []] }
}
```

No field says which configuration is "better" (D-017).

### Errors

| Case | Status | Body |
|---|---|---|
| `GET /api/components` without category | 422 | `{ "success": false, "message": "Dữ liệu không hợp lệ.", "errors": { "category": ["Trường loại linh kiện là bắt buộc."] } }` |
| `GET /api/builds/does-not-exist` | 404 | `{ "success": false, "message": "Không tìm thấy cấu hình.", "errors": {} }` |
| Body is not valid JSON | 400 | `{ "success": false, "message": "Dữ liệu JSON gửi lên không hợp lệ.", "errors": {} }` |
| Too many analysis requests | 429 | `{ "success": false, "message": "Bạn gửi quá nhiều yêu cầu, vui lòng thử lại sau.", "errors": {} }` + `Retry-After` |
