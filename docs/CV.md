# CV project description

Phase 9 deliverable (spec section 39). Copy the version that fits the application.

This file is written in both Vietnamese and English because it is material for the author's CV, not
documentation about the code — the D-039 rule ("docs in English") covers the latter.

Every number below was measured on 2026-09-23, not estimated. Re-measure before reusing:
`docker compose exec app php artisan test --parallel --processes=4 --coverage-text=storage/coverage.txt`.

---

## Facts you can quote

| | |
|---|---|
| Compatibility rules | 13 PHP classes, each unit tested for compatible / incompatible / skipped |
| Scoring profiles | 4 (Chơi game, Lập trình, Workstation, Sử dụng chung) |
| Domain layer | 57 classes, no database / HTTP / `config()` access |
| Backend | ~6.250 lines of PHP in `app/` |
| Tests | 272 backend (966 assertions) + 37 frontend, green in CI on every push |
| Line coverage | 98,48 % (1553/1577), methods 95,65 %, classes 85,59 % |
| Production image | 262 MB, multi-stage Alpine build |
| Seed data | 8 categories, 50 components, 10 template builds |
| Decisions recorded | 41, each with alternatives and consequences (`DECISIONS.md`) |

---

## Tiếng Việt

### Bản ngắn (1–2 dòng, cho mục Dự án trong CV)

> **PCBuild Analyzer** — Ứng dụng web phân tích cấu hình PC (Laravel 13 + React): kiểm tra tương
> thích theo 13 luật, ước tính công suất, phân tích giá và chấm điểm theo 4 mục đích sử dụng.
> 272 test backend, độ phủ 98 %, triển khai bằng Docker trên Render.
> Mã nguồn: github.com/tridinh288/PCBuild-Analyzer · Demo: pcbuild-web.onrender.com

### Bản vừa (3–5 gạch đầu dòng)

**PCBuild Analyzer** — Đồ án cá nhân fullstack · PHP 8.3 / Laravel 13 · React + Vite · MySQL 8 / TiDB Cloud · Docker · Render

- Xây dựng **engine phân tích cấu hình PC** gồm 13 luật tương thích, ước tính công suất và chấm điểm
  theo 4 hồ sơ sử dụng; toàn bộ engine là **PHP thuần**, không chạm database hay HTTP, nên test được
  bằng PHPUnit trần với thời gian mili giây mỗi test.
- Thiết kế kiến trúc phân tầng **Controller mỏng → Service → Domain → Repository**, áp dụng
  Strategy (chấm điểm theo hồ sơ), Specification (điều kiện tái sử dụng giữa các luật), Factory và
  Dependency Injection — và ghi rõ trong tài liệu **chỗ nào cố ý không dùng pattern** để tránh
  trừu tượng thừa.
- Viết **272 test backend (966 assertion) và 37 test frontend**, độ phủ dòng **98,48 %**, chạy tự
  động bằng GitHub Actions ở mỗi lần push.
- **Đóng gói và triển khai thật**: image Docker multi-stage nền Alpine 262 MB, chạy trên Render với
  database TiDB Cloud và ảnh trên Cloudinary.
- Xử lý bài toán **lấy đúng IP client phía sau Cloudflare và proxy của Render** để rate limit hoạt
  động đúng — phát hiện được nhờ một test chứng minh cấu hình `'*'` cho phép client tự giả IP.

### Bản dài (đoạn văn, cho portfolio hoặc thư xin việc)

PCBuild Analyzer là ứng dụng web giúp người dùng chọn linh kiện PC mà không lo lắp không vừa. Người
dùng có thể xem các cấu hình mẫu, tùy chỉnh lại hoặc tự build từ đầu; mỗi lần thay đổi, hệ thống
kiểm tra lại tương thích theo 13 luật, ước tính công suất tiêu thụ kèm mức nguồn khuyến nghị, phân
tích phân bổ chi phí và chấm điểm theo mục đích sử dụng. Không cần tài khoản — cấu hình tự build nằm
trên URL nên chia sẻ được bằng link.

Trọng tâm kỹ thuật của dự án không phải CRUD mà là **engine phân tích**. Tôi tách toàn bộ logic
nghiệp vụ vào một tầng Domain 57 lớp bằng PHP thuần: các lớp ở đây không được phép chạm database,
HTTP hay gọi `config()`, mọi giá trị cấu hình được truyền vào qua constructor. Ràng buộc đó mang lại
hai lợi ích cụ thể: engine test được bằng `PHPUnit\Framework\TestCase` trần (không khởi động
Laravel), và nó chạy **giống hệt nhau** cho cấu hình mẫu lấy từ database lẫn cấu hình tự build chỉ
tồn tại trên URL.

Tôi cũng chủ động giới hạn phạm vi và nói rõ giới hạn đó trong sản phẩm: công suất và điểm số đều
được trình bày là **ước tính theo quy tắc của dự án, không phải số đo hay benchmark**, và phần so
sánh **không bao giờ tuyên bố cấu hình nào thắng** — chỉ đặt các con số cạnh nhau để người dùng tự
quyết. Mọi quyết định kiến trúc đáng kể đều được ghi lại trong `DECISIONS.md` (41 mục) kèm phương án
thay thế và hệ quả.

---

## English

### Short (1–2 lines)

> **PCBuild Analyzer** — PC build analysis web app (Laravel 13 + React): 13 compatibility rules,
> power estimation, price breakdown and rule-based scoring across 4 usage profiles.
> 272 backend tests at 98 % line coverage, deployed with Docker on Render.
> Code: github.com/tridinh288/PCBuild-Analyzer · Demo: pcbuild-web.onrender.com

### Medium (bullets)

**PCBuild Analyzer** — Personal fullstack project · PHP 8.3 / Laravel 13 · React + Vite · MySQL 8 / TiDB Cloud · Docker · Render

- Built a **PC build analysis engine**: 13 compatibility rules, a power estimate with a recommended
  PSU size, and scoring across 4 usage profiles. The engine is **pure PHP** with no database or HTTP
  access, so it runs under a bare PHPUnit `TestCase` in milliseconds per test.
- Designed a layered architecture — **thin controllers → services → domain → repositories** — using
  Strategy (per-profile scoring), Specification (conditions shared between rules), Factory and
  dependency injection, and documented **where each pattern was deliberately not used** to avoid
  abstraction for its own sake.
- Wrote **272 backend tests (966 assertions) and 37 frontend tests** at **98.48 % line coverage**,
  run by GitHub Actions on every push.
- **Shipped it**: a 262 MB multi-stage Alpine Docker image on Render, backed by TiDB Cloud and
  Cloudinary.
- Solved **recovering the real client IP behind Cloudflare and Render's proxy** so rate limiting
  works — caught by a test proving that a `'*'` trusted-proxy setting lets a client spoof its own IP.

### Interview talking points

Questions this project is good at answering, with the short version of each answer:

| Question | Where to take it |
|---|---|
| Why a service layer, not logic in the controller? | The same analysis serves a template, a custom build and the admin preview. Three entry points, one implementation. |
| Why a repository on top of Eloquent? | Spec filtering is JSON queries plus a computed total price, used by catalog, builder and admin. Services can be tested against a mocked repository. |
| Why Strategy for scoring but not for power? | Scoring genuinely varies by profile and the UI exposes the switch. Power has one algorithm — a strategy there would be a class with one implementation. |
| Where is the line between a Rule and a Specification? | A rule answers "is this build valid" and produces a message; a specification answers "does this value satisfy a condition" and is reused by several rules. One-off checks stay inline (D-014). |
| Why keep the Domain free of `config()`? | So domain tests need no Laravel boot, and so the engine behaves identically for DB-backed and URL-only configurations. |
| How would you add a 14th rule? | Write the class, register it in the provider. The engine takes an iterable and is not touched — Open/Closed in practice. |
| What went wrong in production? | Rate limiting silently did nothing: behind Cloudflare the edge IP looked like the client, so each edge server got its own bucket. Fixed by listing every proxy hop explicitly (D-038). |
| What are the limits of this project? | Power and scores are rule-based estimates, not measurements; three physical constraints are unchecked; comparison deliberately declares no winner. |
