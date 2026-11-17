# Sites, branches and previews

## Three websites

| Site | Built from | For | Who opens it |
|------|-----------|-----|--------------|
| **schleyconsult.ch** | `main` | The live website | Everyone |
| **preview.schleyconsult.ch** | CMS drafts (`cms/**`) | "This is how my change will look" before publishing | The editor, via **"Vorschau ansehen"** in the CMS |
| **beta.schley.ch** | `development` (or any branch, started by hand) | New features: testing, showing the customer | The developer, the customer via a link |

- **preview** always runs the **same code as the live site**, only the draft's
  content differs. It shows exactly what publishing will produce.
- **beta** runs code that isn't released yet. CMS drafts never land there.
- Neither preview nor beta appears in search engines (`X-Robots-Tag: noindex`).
  They aren't password-protected.
- `/admin` on preview and beta redirects to the real CMS on
  `schleyconsult.ch/admin/`. There is only one CMS.

## Editor: preview a change

1. Edit in the CMS on **schleyconsult.ch/admin/** and click **Speichern**.
2. Wait 2–3 minutes, then click **Vorschau ansehen** at the top of the entry.
   It opens the page on preview.schleyconsult.ch.
3. Happy? Set the status to **Abgeschlossen** and **Veröffentlichen**.

There is one preview site: if two drafts are saved, it shows the one saved last.

## Developer: release a new feature

```
feature branch ──► development ──► beta.schley.ch      (you + customer check)
                        │
         PR development → main ──► schleyconsult.ch    (live)
                                        │
            CMS publish ────────────────┤
                                        └──► automatically merged back into development
```

1. Work on a feature branch. To show it on beta without merging: **Actions →
   "Deploy to beta.schley.ch" → Run workflow →** pick the branch.
2. Merge it into `development` → beta updates automatically.
3. Customer OK → pull request `development` → `main` → live.
4. Only now can editors use new CMS fields. The CMS form always comes from the
   live site, and drafts are built from `main`.

## Keeping `development` and `main` in sync

`main` also receives commits that never pass through `development`: everything
published from the CMS. The workflow **"Sync main into development"** runs on
every push to `main`, merges `main` into `development` and redeploys beta.

- Direction is always `main` → `development`. The other direction is only your
  release pull request.
- Content and code rarely touch the same files, so the merge is almost always
  automatic.
- On a **conflict** the run fails (GitHub emails you) and nothing is pushed.
  Fix it locally:
  ```bash
  git checkout development && git pull
  git merge origin/main      # resolve conflicts, then commit
  git push
  ```

## Workflows

| File | Trigger | Deploys to |
|------|---------|-----------|
| `.github/workflows/deploy_main.yml` | push to `main` | schleyconsult.ch |
| `.github/workflows/deploy_preview.yml` | push to `cms/**` | preview.schleyconsult.ch (+ sets the CMS "Vorschau ansehen" link) |
| `.github/workflows/deploy.yml` | push to `development`, or manual for any branch | beta.schley.ch |
| `.github/workflows/sync_development.yml` | push to `main` | merges `main` into `development`, then redeploys beta |

Each site is built with its own address (`--baseURL`), and each deploy has its
own concurrency group, so a beta or preview deploy never cancels a production
deploy.

## Status (17 Nov 2026)

- The blog overview, homepage section and PDF features are on the branch
  `claude/great-meitner-5qzo0g`, not yet on `development` or `main`.
- That branch contains **3 sample posts** for previewing (all files named
  `beispiel-*`). **Remove them before merging to `main`:**
  `git rm content/blog/beispiel-* static/files/blog/beispiel-*`
- **One-time setup on cyon** for preview.schleyconsult.ch: create the
  subdomain with the document root `public_html/preview.schleyconsult.ch/`
  and enable SSL (Let's Encrypt). The DNS entry must point to the cyon server.
