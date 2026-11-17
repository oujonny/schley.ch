# schley.ch – Claude Code Context

## Project
Hugo static site for **schleyconsult.ch** (business consulting, German-language).
Hosted on **cyon.net** (LiteSpeed / Apache-compatible, PHP 8.x, cURL available,
`allow_url_fopen` is off by default).

Repo: `oujonny/schley.ch`
Working branch: `claude/add-netlify-cms-github-cfBgS`

## Branch & Deployment Strategy
| Branch | Deploys to |
|--------|-----------|
| `main` | `schleyconsult.ch` (production) — `schley.ch` 301-redirects here |
| `development` | `beta.schleyconsult.ch` (developer staging, customer review; any branch via manual dispatch) |
| `cms/**` | `preview.schleyconsult.ch` (Decap CMS draft previews, "Vorschau ansehen") |

`sync_development.yml` merges `main` into `development` after every push to `main`
(CMS publishes go straight to `main`). Beta docroot: `public_html/beta.schleyconsult.ch/`; preview docroot:
`public_html/preview.schleyconsult.ch/`. Old `beta.schley.ch` should 301-redirect
to `beta.schleyconsult.ch` (see `docs/preview.md`). Beta + preview send `X-Robots-Tag: noindex`
and redirect `/admin` to the live CMS (host rules in `static/.htaccess`).
Full description: `docs/preview.md`.

**Domain redirect:** `schley.ch` / `www.schley.ch` permanently (301) redirect to
`schleyconsult.ch` via a host-based rule in `static/.htaccess`. On cyon,
`schleyconsult.ch` is an alias of `schley.ch` (same docroot), so the rule fires
only for the old host and leaves `schleyconsult.ch` and `beta.schleyconsult.ch` alone.

CI workflows: `deploy_main.yml` (prod), `deploy_preview.yml` (CMS previews),
`deploy.yml` (beta), `sync_development.yml` (main → development),
`cms_draft_ready.yml` (new CMS drafts start as "Abgeschlossen" via PR label), all in `.github/workflows/`.

## CMS – Decap CMS
Admin panel at `/admin/` (source: `static/admin/`).

- **Production config**: `static/admin/config.yml`
  - Backend: GitHub OAuth via PHP proxy (`base_url: https://schleyconsult.ch`, `auth_endpoint: cms-oauth/auth`)
  - Commits to `branch: main` → publishes live to `schleyconsult.ch`
  - Editorial workflow enabled (`publish_mode: editorial_workflow`); CMS draft branches (`cms/**`) preview on `preview.schleyconsult.ch`, **Publish** merges to `main`
- **Local config**: `static/admin/config.local.yml`
  - `local_backend: true` — bypasses GitHub, uses decap-server instead
  - Mounted over `config.yml` by docker-compose

## PHP OAuth Proxy
Handles the GitHub OAuth code exchange so the client secret never leaves the server.

- Source: `static/cms-oauth/index.php` + `static/cms-oauth/.htaccess`
- Deployed to: `https://schleyconsult.ch/cms-oauth/`
- Uses cURL (not `file_get_contents`) — `allow_url_fopen` is off on cyon
- CSRF protection via PHP sessions + `state` parameter
- Token returned to CMS via `window.opener.postMessage()` (Decap CMS protocol)

### Credentials (NOT in Git)
Stored above the web root on the cyon server — never web-accessible:
```
/home/toxykiwo/cms_oauth_config.php
```
```php
<?php
return [
    'client_id'     => 'GITHUB_CLIENT_ID',
    'client_secret' => 'GITHUB_CLIENT_SECRET',
];
```

## Local Development
```bash
docker-compose up
# Open: http://localhost:1313/admin/
```
- Hugo dev server on port 1313 (hot-reload)
- `decap-server` on port 8081 (local git backend — reads/writes files, commits locally)
- No GitHub account or OAuth credentials needed locally
- Changes commit to your **local** git repo; push to GitHub manually when ready

## Blog
- Overview `/blog/`: `themes/business/layouts/blog/list.html` (card grid, intro from `content/blog/_index.md`)
- Posts: `themes/business/layouts/blog/single.html`; cards: `partials/components/blog_card.html`
- Homepage section `content/homepage/blog.md` (`type: blog_teaser`) shows the 3 newest posts;
  it and its menu item are hidden automatically while there are no posts
- Cover images live next to the posts in `content/blog/` (resources of the `_index.md` bundle)
- CMS blog collection filters on `layout: post` so `_index.md` isn't listed as a post

## Blog PDFs
- CMS field `downloads` (list: `title`, `file`, `preview`) on blog posts; uploads go to `static/files/blog/`
- Rendered by `themes/business/layouts/partials/components/downloads.html`
- `preview: true` embeds Mozilla's PDF.js viewer (`/pdfjs/web/viewer.html?file=…`)
- PDF.js is vendored in `static/pdfjs/` (committed, served from our own server, ~9 MB;
  locales trimmed to de/fr/it/en-US). Update = bump `PDFJS_VERSION` + `PDFJS_SHA256`
  in `scripts/fetch-pdfjs.sh`, run it, commit `static/pdfjs/`.
- Viewer styling: `themes/business/static/css/pdfjs-theme.css` (overrides PDF.js CSS variables)
- Inline "PDF" button in the body editor: custom `registerEditorComponent` in `static/admin/index.html`

## Pending Manual Setup (one-time, after code is deployed)
1. **GitHub OAuth App**: github.com/settings/developers → New OAuth App
   - Callback URL: `https://schleyconsult.ch/cms-oauth/callback`
   - Note the Client ID + generate Client Secret
2. **Credentials on cyon**:
   ```bash
   ssh toxykiwo@s106.cyon.net
   nano ~/cms_oauth_config.php
   ```
   Fill in Client ID + Secret from step 1.

## Key Files
```
static/admin/index.html          # CMS admin panel (loads Decap CMS from CDN)
static/admin/config.yml          # Production CMS config (GitHub backend)
static/admin/config.local.yml    # Local CMS config (local_backend: true)
static/cms-oauth/index.php       # PHP OAuth proxy
static/cms-oauth/.htaccess       # URL rewrite rules for the proxy
scripts/fetch-pdfjs.sh           # Updates the vendored PDF.js viewer
docker-compose.yml               # Local dev environment
.github/workflows/deploy.yml     # CI: beta deployment (development, manual)
.github/workflows/deploy_preview.yml # CI: CMS draft previews (cms/**)
.github/workflows/sync_development.yml # CI: merge main into development
.github/workflows/deploy_main.yml# CI: production deployment (main)
```
