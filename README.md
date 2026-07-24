# News Web

AI-assisted news publication built on CodeIgniter 4, designed for Google News /
Search / Discover eligibility. Every AI-drafted article goes through a
mandatory human editorial review before it can publish — there is no
auto-publish path anywhere in the codebase. See
`app/Libraries/EditorialReviewService.php` for the single enforcement point.

## Stack

- PHP 8.1+, Apache (mod_php or PHP-FPM), MySQL 8 / MariaDB 10.6+
- CodeIgniter 4.5 (installed via Composer, not vendored in this repo)
- No Node/build step for the admin or front-end — plain server-rendered views

## First-time setup

```bash
composer install
cp .env.example .env
# edit .env: database credentials, publisher identity, AI provider API keys
php spark migrate
php spark db:seed DatabaseSeeder
```

Point your Apache vhost's document root at `public/`. Everything outside
`public/` (app/, vendor/, writable/) should not be web-accessible.

Create your first admin user with the dedicated seeder (deliberately not part
of `DatabaseSeeder` — a hardcoded default admin account is exactly the kind
of credential that gets left in place on real deployments):

```bash
php spark db:seed AdminUserSeeder
```

It prompts for an email and name, then either uses a password you supply via
`ADMIN_PASSWORD=... php spark db:seed AdminUserSeeder` or generates a strong
random one and prints it once — copy it immediately, it's never stored or
logged anywhere except as the bcrypt hash in the database. Re-running it is
safe: it checks for an existing user by email first and won't create a
duplicate.

Then log in at `/admin/login`.

## Required cron jobs (production)

| Command | Suggested interval | Purpose |
|---|---|---|
| `php spark ai:process-queue` | every 1–2 min | Runs queued AI article/style-pass/image jobs |
| `php spark articles:publish-scheduled` | every 5 min | Publishes `approved` articles whose `publish_at` has arrived |
| `php spark seo:generate-sitemaps` | every 15–30 min | Regenerates static `sitemap*.xml` files in `public/` |
| `php spark ai:ingest-topics` | hourly/daily (optional) | Pulls RSS/wire metadata into topic candidates — only if `Config\NewsFeeds::$feedUrls` is populated; manual topic entry in the admin panel is the default workflow |

`news-sitemap.xml` is deliberately NOT a cron job — it's served live by
`Front\SitemapController::news()` with a ~90s cache, since Google News
discovery depends on that file reflecting the last 48 hours in near-real-time.

## Before going live — placeholders that must be replaced

- **Six CMS pages** (About/Contact/Editorial Policy/Corrections Policy/Privacy/Terms)
  are seeded `is_published = 0` with bracketed placeholder copy. Edit and
  publish each from `/admin/pages` before launch — Google News and AdSense
  both expect these to reflect a real, transparent publisher.
- **`.env` publisher.* values** — name/logo/URL must exactly match what you
  register in Google Publisher Center.
- **`.env` ai.* API keys** — all placeholders. Text/image provider selection
  and models are config-driven (`Config\AIPipeline`), not hardcoded.
- **AI provider request/response shapes** — `app/Libraries/AI/Providers/*`
  were written against each vendor's documented API shape as of this build,
  but were not tested against live endpoints (no local PHP/network access
  during generation). Verify each provider's exact request/response format
  against current docs before enabling the queue in production, especially
  `StabilityProvider` (flagged in its own docblock as the most speculative).
- **`ads.txt`** — add your real AdSense publisher ID at `public/ads.txt`.
- **Real editorial staff** — `/admin/users` ships with no default accounts;
  create real named editor/writer accounts (author bio pages and bylines
  depend on real users, not a generic "AI" account).

## Architecture notes

- **Editorial workflow**: `draft → in_review → changes_requested/rejected →
  approved → published (→ corrected via audit trail, status stays
  published)`. Enforced by `App\Libraries\Publishing\ArticleWorkflow` +
  `App\Libraries\EditorialReviewService`. The `approve`/`publish` routes
  require both a `role:editor,admin` filter and a `canapprove` filter
  (`app/Filters/RequireApprovalPermissionFilter.php`).
- **AI pipeline**: topic → (async) generation job → draft lands in the
  Review Queue → optional style-pass suggestion (human accept/reject only)
  → optional AI image (human approve only, via `/admin/image-jobs`) →
  human approve → human publish. Generation/style-pass/image jobs all run
  out-of-request via `php spark ai:process-queue` against a DB-backed queue
  (`ai_generation_jobs`/`ai_image_jobs`), not synchronously in the HTTP
  request — see `app/Commands/ProcessAiQueue.php`.
- **Daily generation cap**: `Config\AIPipeline::$dailyGenerationCap`
  (override at runtime via `/admin/settings/ai`, stored in `ai_settings`,
  never in `.env`) — exists so AI draft volume never outpaces what the
  editorial team can actually review.
- **Caching**: file-based via CodeIgniter's `Cache` service today
  (`Config\Cache::$handler = 'file'`); switch to `'redis'` the moment a
  second app server is added — every call site goes through the `cache()`
  helper, so this is a config-only change. Same applies to
  `Config\Session::$driver` (move off `FileHandler` once load-balanced).
- **Permanent URLs**: flat `/{category}/{slug}`. `slug`/`primary_category_id`
  are meant to be treated as immutable once `published_at` is set; use the
  `redirects` table (`/admin/redirects`) for the rare case a URL must change.

## Known follow-ups (intentionally deferred, not bugs)

- Review-queue and revision diffs use a naive line-based comparison; swap in
  a real diff library (e.g. `jfcherng/php-diff`) for word-level highlighting.
- `TopicDiscoveryService::suggestAngles()` is a stub returning `[]` —
  angle-suggestion AI-assist was deliberately deferred rather than force-fit
  into the article-generation provider interface.
- `AltTextService` generates alt text from the headline rather than a vision
  model call — a reasonable non-AI fallback for MVP, upgradeable later.
- Category/tag listing pagination uses CI4's offset-based `Pager` for
  simplicity; the plan's true keyset-cursor pagination
  (`ArticleModel::listPublishedForCategory`) exists and is ready to wire in
  once deep pagination volume justifies it.
