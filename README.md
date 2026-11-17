# schley consult – Website & CMS

Hugo static site for **schleyconsult.ch**, edited through **Decap CMS** with a
GitHub-based editorial workflow. Drafts are previewed on a staging site before
they go live.

---

## Editing content

1. Open **<https://schleyconsult.ch/admin/>**
2. Click **Login with GitHub** and authorise (you need write access to the
   `oujonny/schleyconsult.ch` repo).
3. Pick a collection in the sidebar (Homepage, Angebot, Über mich, Referenzen,
   Blog, Statische Seiten …), open or create an entry, and edit.
4. Use the live **preview pane** on the right to see roughly how it will look.

> **Safari note:** after the GitHub login the small popup may stay open showing
> *"✓ Anmeldung erfolgreich"*. That's expected — you're already logged in, just
> close the popup. (Safari blocks pop‑ups from closing themselves.)

Guides for specific features (blog, PDFs, previewing changes) are in
**[docs/](docs/README.md)**.

---

## Editorial workflow (draft → review → publish)

The CMS does **not** write directly to the live site. Every change goes through
a review stage first, using the **Workflow** tab at the top of the CMS.

1. **Save** an entry → Decap creates a branch `cms/<collection>/<slug>` and opens
   a pull request against `main`. The entry appears in the **Workflow** board.
2. Move the card through the columns with the **status** selector in the editor:
   - **Drafts** – work in progress
   - **In Review** – ready for someone to look at
   - **Ready** – approved, waiting to be published
3. **Preview:** each `cms/**` branch is automatically deployed to
   **<https://preview.schleyconsult.ch>**. Click **Vorschau ansehen** in the
   entry to open the rendered page there and check it before publishing.
4. When you're happy, click **Publish → Publish now**. Decap merges the pull
   request into `main`, which deploys to **<https://schleyconsult.ch>** (live).

Deleting a draft (or "Delete unpublished entry") removes its `cms/**` branch and
closes the pull request — nothing reaches production.

---

## Branches & deployments

| Branch     | Deploys to                          | Purpose                                   |
|------------|-------------------------------------|-------------------------------------------|
| `main`     | <https://schleyconsult.ch>          | **Production** (live site)                |
| `development` | <https://beta.schleyconsult.ch>         | Developer staging: new features, customer review |
| `cms/**`   | <https://preview.schleyconsult.ch>  | CMS draft previews ("Vorschau ansehen")   |

- `schley.ch` / `www.schley.ch` permanently (301) redirect to `schleyconsult.ch`.
- CI is in `.github/workflows/`: `deploy_main.yml` (production),
  `deploy_preview.yml` (CMS previews), `deploy.yml` (beta) and
  `sync_development.yml` (merges `main` back into `development` after every
  push to `main`). Details: [docs/preview.md](docs/preview.md).
- Both build with Hugo and `rsync` to the cyon host.

> **Note:** preview.schleyconsult.ch shows one draft at a time: the one saved
> most recently. Beta and preview are not indexed by search engines, and their
> `/admin` redirects to the real CMS.

---

## Local development

```bash
docker-compose up
# Site preview: http://localhost:1313   (Hugo, hot-reload)
```

This serves the rendered site locally. Content is normally edited through the
**hosted** CMS (above), not locally.

> The compose file also starts `decap-server` on port `8081` for a local CMS
> backend, but the admin panel still loads the production config and isn't
> wired to it yet (no `config.local.yml` / `local_backend: true` override).
> Ask if you want local CMS editing enabled.

---

## How login works (for maintainers)

Decap authenticates against GitHub through a small PHP OAuth proxy hosted on
cyon (`static/cms-oauth/`), so the GitHub client secret never reaches the
browser. The OAuth app setup, credential location, redirect rules and hosting
specifics are documented in **[CLAUDE.md](CLAUDE.md)**.
</content>
</invoke>