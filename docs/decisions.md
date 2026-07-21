# Architecture Decision Records

This is a lightweight ADR (Architecture Decision Record) log for AarvixCMS.
Each entry records a decision, its context, the alternatives considered, and the rationale.

---

## ADR-001: Category Tree Storage — Adjacency List + Recursive CTE

**Date:** 2026-07-21
**Status:** Accepted

### Context
Categories need to support unlimited depth nesting. Two common patterns exist:
- **Nested Set (Modified Preorder Tree Traversal):** Very fast reads (single query for entire subtree), but expensive writes — every insert/move/delete requires updating `lft`/`rgt` values for potentially the entire table.
- **Adjacency List + Recursive CTE:** Each row stores only `parent_id`. Tree reads require a recursive CTE, but writes are simple single-row operations.

### Decision
Use **adjacency list** with a **recursive CTE** for tree reads, and **cache the resolved tree in Redis** (invalidated on any category write).

### Rationale
- For a CMS, categories are edited occasionally (hours/days between changes) but read on every page load.
- Caching the resolved tree gives near-nested-set read performance without nested-set's write complexity.
- MySQL 8 and PostgreSQL 15 both support recursive CTEs natively.
- Adjacency list is simpler to understand and maintain; no need for a specialized package.
- Revisit only if: category depth exceeds ~8 levels AND profiling shows CTE performance as a bottleneck AND the cached tree is insufficient.

### Alternatives Considered
- Nested Set: rejected due to write complexity and package dependency risk.
- Closure Table: more complex than adjacency list with marginal gain for our scale.
- Materialized path: string-based, harder to query correctly across DB engines.

---

## ADR-002: Permission Checking — Cached Per-User Permission Set

**Date:** 2026-07-21
**Status:** Accepted

### Context
Every authenticated request needs to check permissions. Naive approach: 3-table JOIN (users → role_user → permission_role → permissions) on each request.

### Decision
Resolve `user → roles → permissions` once at login or first permission check, cache as `Cache::remember("user:{$id}:permissions", ttl, ...)`, invalidate cache when a user's roles change or a role's permissions change.

### Rationale
- Eliminates repeated joins on every request.
- TTL provides a safety net even if cache invalidation has an edge case.
- Simple to implement (~30 lines in PermissionService); no Spatie package needed.

---

## ADR-003: Rich Text Output — Sanitize on Save, Not on Render

**Date:** 2026-07-21
**Status:** Accepted

### Context
TinyMCE produces HTML. This HTML must be safe before rendering with `{!! !!}`.

### Decision
Run HTMLPurifier **when content is saved** (POST/PUT handler), store the clean HTML in the database. Render directly with `{!! $post->body !!}`.

### Rationale
- Purifying on every render is wasteful — a post may be rendered thousands of times but saved once.
- Storing clean HTML at write time means the content in the DB is always safe regardless of rendering code.
- If HTMLPurifier config changes, a one-time re-purify script can be run on existing content.
- Rule: every use of `{!! !!}` in the codebase must be traceable to a field that is purified at write time. This is enforced via code review checklist.

---

## ADR-004: Slug Generation — Custom HasSlug Trait, No Package

**Date:** 2026-07-21
**Status:** Accepted

### Context
Posts, pages, categories, and tags all need URL-safe slugs. Third-party slug packages (e.g. spatie/laravel-sluggable) exist.

### Decision
Implement a custom `HasSlug` trait (~30 lines).

### Rationale
- The full slug behavior we need: generate from title, suffix counter for uniqueness (`my-post`, `my-post-2`), skip regeneration if slug was manually edited.
- A slug package does this plus 15 other things we don't need, with a maintenance dependency we don't need.
- 30 lines of custom code we own is simpler than a package dependency we need to audit.

---

## ADR-005: Settings Storage — One Row Per Key (JSON value column)

**Date:** 2026-07-21
**Status:** Accepted

### Context
The CMS needs application-wide settings (site name, active theme, mail config, SEO defaults, etc.).

### Decision
Store settings as individual rows: `key (unique), value (json), group`. Access via `SettingService::get('site.name')`.

### Rationale
- A single JSON blob for all settings is not queryable (can't filter by group, can't use SQL WHERE on individual keys).
- Individual key rows are queryable, groupable, and can be cached per-key or per-group.
- Avoids the "magic JSON object" anti-pattern where the schema is invisible.

---

## ADR-006: Authentication — Laravel Fortify, Admin-Only

**Date:** 2026-07-21
**Status:** Accepted

### Context
Need admin authentication. Options: custom auth, Jetstream, Fortify, Breeze.

### Decision
Use **Laravel Fortify** (headless) with admin-only registration (no public signup). First admin seeded via `AdminUserSeeder`. 2FA is an optional per-user toggle.

### Rationale
- Fortify is official, headless, and gives us full control over views.
- No public registration needed — this is an admin CMS, not a user-facing app.
- Jetstream and Breeze include team/profile scaffolding we don't need.
- 2FA as optional toggle: Fortify supports this out of the box.

---

## ADR-007: Phase 5 Scope — Form Builder Only

**Date:** 2026-07-21
**Status:** Accepted

### Context
Phase 5 originally included Form Builder, Multi-language/i18n, and an optional API layer.

### Decision
**Form Builder only** for v1. i18n and API layer are explicitly cut (not silently dropped).

### Rationale
- i18n requires schema changes (post_translations table) that can be additive post-v1.
- API layer should only be built when a confirmed consumer (mobile app, headless frontend) exists.
- Form Builder has a clear user need (contact forms) and a contained scope.
- This decision is logged here so it's not "forgotten" — it's a deliberate v2 candidate.

### v2 Candidates (re-evaluate after v1 ships)
- Multi-language: add `post_translations` table, locale switcher middleware.
- API layer: Sanctum tokens, API Resources for Post/Page — only if consumer confirmed.

---

## ADR-008: Deployment Target — WAMP (Windows Apache MySQL PHP)

**Date:** 2026-07-21
**Status:** Accepted

### Context
Deployment target is WAMP (UniWamp distribution on Windows), not Docker or Linux.

### Decision
Document WAMP-specific deployment steps. No Docker Compose.

### Rationale
- Existing infrastructure is WAMP-based.
- UniWamp bundles PHP 8.3/8.4/8.5, MySQL (MariaDB), Apache, Node.js, Redis, Composer — all needed tools present.
- Docker would add operational complexity with no benefit given the existing WAMP stack.

### Implications
- PHP CLI path: `L:\UniWamp\runtime\php\php83\php.exe`
- Composer: `L:\UniWamp\runtime\tools\composer\composer.phar`
- Node.js: `L:\UniWamp\runtime\nodejs\node-v22.23.1-win-x64\`
- Redis: available at `L:\UniWamp\runtime\tools\redis\`
- Queue workers: run via Windows Task Scheduler or UniWamp's process manager.

---

## ADR-009: Testing Framework — PHPUnit (not Pest) for v1

**Date:** 2026-07-21
**Status:** Accepted

### Context
The plan specified Pest or PHPUnit. Pest 3.x requires PHPUnit ^11.5, Pest 4.x requires PHPUnit ^12 but `pestphp/pest-plugin-laravel` v4 only supports Laravel ≤12. Laravel 13 ships with PHPUnit 12.5.x. As of project start, no Pest version resolves cleanly with Laravel 13 + PHPUnit 12.

### Decision
Use **PHPUnit 12** directly (already bundled with Laravel 13). Skip Pest for v1.

### Rationale
- PHPUnit is already installed; tests can be written immediately.
- Pest adds syntactic sugar but not capability. The feature/unit/policy tests required are fully expressible in PHPUnit.
- Adding a broken dependency to force Pest in would violate the "no unnecessary complexity" principle.

### When to revisit
When `pestphp/pest-plugin-laravel` releases a version requiring `laravel/framework ^13`, run:
```bash
composer require pestphp/pest pestphp/pest-plugin-laravel --dev
```
Existing PHPUnit tests are compatible with Pest after running `php artisan pest:install`.
