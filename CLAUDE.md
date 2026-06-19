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
| `development` | `beta.schley.ch` (staging) |
| `cms/**` | `beta.schley.ch` (Decap CMS editorial workflow previews) |

**Domain redirect:** `schley.ch` / `www.schley.ch` permanently (301) redirect to
`schleyconsult.ch` via a host-based rule in `static/.htaccess`. On cyon,
`schleyconsult.ch` is an alias of `schley.ch` (same docroot), so the rule fires
only for the old host and leaves `schleyconsult.ch` and `beta.schley.ch` alone.

CI workflows: `.github/workflows/deploy.yml` (beta), `.github/workflows/deploy_main.yml` (prod).

## CMS – Decap CMS
Admin panel at `/admin/` (source: `static/admin/`).

- **Production config**: `static/admin/config.yml`
  - Backend: GitHub OAuth via PHP proxy (`base_url: https://schleyconsult.ch`, `auth_endpoint: cms-oauth/auth`)
  - Commits to `branch: main` → publishes live to `schleyconsult.ch`
  - Editorial workflow enabled (`publish_mode: editorial_workflow`); CMS draft branches (`cms/**`) preview on `beta.schley.ch`, **Publish** merges to `main`
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
docker-compose.yml               # Local dev environment
.github/workflows/deploy.yml     # CI: beta deployment (development + cms/**)
.github/workflows/deploy_main.yml# CI: production deployment (main)
```
