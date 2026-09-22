# PCBuild Analyzer

Student portfolio project (Junior/Intern Fullstack). Public PC build analysis app:
browse templates, customize them, build from scratch, check compatibility, estimate power,
analyze price, rule-based score, compare. Admin manages data. No user accounts.

## Where things are

- Full specification: `docs/PROJECT_SPEC.md`
  Do NOT load it all at once. At the start of each phase, read the sections relevant to that phase
  (section numbers are listed per phase in "Development Method", section 39).
  Re-check the spec whenever unsure about scope or a requirement.
- Decisions log: `DECISIONS.md` — read it at the start of every session and stay consistent with it.
- Current progress: "Current status" at the bottom of this file.

## Stack

- Backend: PHP 8.3+, Laravel, Eloquent, Sanctum API tokens, PHPUnit
- Frontend: React + Vite, JavaScript (no TypeScript), React Router, Axios, Tailwind (no Next.js)
- DB: MySQL 8 locally / TiDB Cloud Starter in production
- Images: Cloudinary (store public_id only)
- Hosting: Render (API as Docker web service, React as static site)

## Core rules

- Keep it simple. No unnecessary abstractions. If a pattern adds no value somewhere, say so.
- Controllers stay thin. Business logic lives in services and the Domain layer.
- `config/hardware.php` is the single source of truth for spec keys, labels, units,
  validation, filters, slot rules, and power constants. No magic numbers in code.
- Rules and analyzers take `BuildConfiguration`, never the Eloquent `Build` model.
- Compatibility rules are PHP classes only. Never duplicate them in SQL.
- Power and scores are ESTIMATES. Never present them as benchmarks.
- Comparison never declares a "winner".
- Custom configurations are never stored in the database.
- UI text in Vietnamese; code, identifiers, commits, and docs in English. Currency: VND.

## Working method

- Work phase by phase. Never generate the whole app at once.
- Explain important decisions (architecture, patterns, schema, auth, deployment):
  problem, why, alternatives, trade-offs, what to know for interviews. Keep routine code brief.
- At the end of each phase: commit, push, summarize, update "Current status", STOP for approval.
- Record new important decisions in `DECISIONS.md`.

## Git workflow

- The first commit adds `.gitignore` before any other file.
- One branch per phase: `phase/<number>-<name>` from `main`.
- Commit after every completed logical step. Conventional Commits in English
  (`feat:`, `fix:`, `test:`, `docs:`, `chore:`, `refactor:`).
- Before each commit: run relevant tests, check `git status` and `git diff --staged`,
  make sure no secrets are staged (.env, API keys, CLOUDINARY_URL, DB passwords, tokens).
- Push right after each commit: `git push -u origin <branch>`.
- Merge into `main` only after my approval, through a GitHub pull request with a merge commit
  (`gh pr create` → `gh pr merge --merge`), equivalent to `git merge --no-ff`.
- Pull request titles and descriptions are written in Vietnamese (commit messages stay English).
- Never force push, never rewrite pushed history, never commit build output.
- If a push fails (remote or auth), stop and report. Do not work around it.

## Commands

(Phase 8 adds production commands.)

- Start: `docker compose up -d` (API on http://localhost:8000, MySQL on host port 3307)
- Backend tests: `docker compose exec app php artisan test` (faster: `--parallel --processes=4`; coverage: see `docs/TESTING.md`)
- Migrate + seed: `docker compose exec app php artisan migrate --seed` (fresh: `migrate:fresh --seed`)
- Code style: `docker compose exec app ./vendor/bin/pint`
- DB checks: `docker compose exec app php artisan app:verify-database` (add `--env=tidb` for TiDB, see `docs/DEPLOYMENT.md`)
- Composer: prefer `docker compose exec app composer ...` (host PATH may still resolve `php` to XAMPP 8.0)
- Frontend dev: `npm run dev` (in `frontend/`)

## Current status

- Phase: 8 — Docker & Deployment on branch `phase/8-deployment`
- Last completed step: production image (Alpine, 262 MB) verified locally against a fresh MySQL; trusted proxies fixed; `render.yaml` Blueprint; `docs/DEPLOYMENT.md`
- Waiting on: the user creates the Render Blueprint (branch `phase/8-deployment`) and Cloudinary account, then the live deployment is checked
- Next step: verify the live site (incl. browser check and a real Cloudinary upload), fix issues on this branch, then PR and merge; Phase 9 — Finalization
- Production image check: `docker build -t pcbuild-api:prod backend` then run it with production env vars (see `docs/DEPLOYMENT.md`)
- Pre-commit gate: lint + build + tests must pass before `git commit` (never chain a commit after a failing step)
- Frontend commands: `npm run dev`, `npm test`, `npx oxlint`, `npm run build` (in `frontend/`)
- Engine map: `app/Domain` (pure), `app/Services` (DB orchestration); implementation notes in `docs/ARCHITECTURE.md` § 3
- TiDB: credentials in `backend/.env.tidb` (gitignored), database `pcbuild` — never `sys`
- Note: the full test suite takes about 1 minute because the Windows bind mount is slow
