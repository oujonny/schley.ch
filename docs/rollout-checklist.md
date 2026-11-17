# Rollout checklist: blog, PDFs, new preview setup

One-time steps to bring branch `claude/great-meitner-5qzo0g` live. Work through
them in order and tick them off. Delete this file once everything is done.

## 1. cyon ✅ done

- [x] **preview.schleyconsult.ch**: folder `public_html/preview.schleyconsult.ch/`, SSL
- [x] **beta.schleyconsult.ch**: own folder `public_html/beta.schleyconsult.ch/`, SSL
- [ ] **Old beta.schley.ch → redirect.** Its folder no longer gets deploys.
  Empty `public_html/beta.schley.ch/` and upload the two files from
  [`ops/beta.schley.ch/`](../ops/README.md) (redirect + fallback page).
  Later: delete the subdomain.

## 2. GitHub settings (check once)

- [ ] **Workflow permissions:** nothing to change. `sync_development.yml`
  declares its own permissions (`contents: write`, `actions: write`), so the
  safer default "Read repository contents" can stay
  (Settings → Actions → General → Workflow permissions). Switch to
  "Read and write" only if the sync run fails with error 403.
- [ ] **Branch protection on `development`** (Settings → Rules → Rulesets, and
  Settings → Branches): if a rule blocks direct pushes, add GitHub Actions to
  its bypass list, or the sync will fail.

## 3. Customer review on beta (feature branch, with sample posts)

- [ ] **Actions → "Deploy to beta.schleyconsult.ch" → Run workflow →** branch
  `claude/great-meitner-5qzo0g`
- [ ] Check on `https://beta.schleyconsult.ch` (hard-refresh with Cmd/Ctrl +
  Shift + R):
  - [ ] Homepage: "Blog" section with 3 cards, "Blog" in the menu
  - [ ] `/blog/`: card overview; clicking a card or "Weiterlesen" opens the post
  - [ ] "Bahn 2050": PDF as **download only**
  - [ ] "Energiewende": PDF **with viewer** (if the viewer stays empty, send
    the browser console output)
  - [ ] Same on a phone
- [ ] Send the link to the customer; wait for their OK
- [ ] Ask Claude to make any changes, then repeat this step

## 4. Prepare the release branch

- [x] **Sample posts removed** (by Claude). For reference:
  ```bash
  git checkout claude/great-meitner-5qzo0g && git pull
  git rm content/blog/beispiel-* static/files/blog/beispiel-*
  # also delete the "Sample content" warnings in CLAUDE.md and docs/preview.md
  git commit -m "Remove sample blog posts" && git push
  ```
- [x] **`development` is up to date with `main`** (done: `development` already
  contained `main`, and it has been merged into the feature branch, so PR 1
  has no conflicts)

## 5. PR 1: feature → `development`

- [ ] Open a PR: `claude/great-meitner-5qzo0g` → `development`
- [ ] **Review** (Files changed). Most of the diff is `static/pdfjs/` (vendored
  Mozilla code, skip it). Look at:
  - `.github/workflows/`: `deploy.yml`, `deploy_preview.yml`,
    `deploy_main.yml`, `sync_development.yml`
  - `static/.htaccess`: redirects, noindex, MIME types
  - `static/admin/config.yml`: new CMS fields
  - `content/`: no `beispiel-*` files left; `homepage/kontakt` weight 6 → 7
- [ ] Merge → beta deploys automatically from `development`
- [ ] Check beta: site looks like the live site; **no** blog section or menu
  item (correct: no posts yet); `https://beta.schley.ch` redirects to
  beta.schleyconsult.ch

## 6. PR 2: release `development` → `main`

- [ ] Open a PR: `development` → `main`, title e.g. "Release: Blog & PDFs"
- [ ] Review: should contain PR 1 and nothing unexpected
- [ ] Merge → production deploys
- [ ] **Actions**: these must be green
  - [ ] "Deploy to schley.ch" (production)
  - [ ] "Sync main into development" (first run; then beta redeploys)

## 7. Check live

- [ ] `https://schleyconsult.ch` looks as before (blog hidden, no posts yet)
- [ ] `https://schleyconsult.ch/admin/`: login works; blog shows the new fields
  (PDF-Downloads, Vorschau anzeigen)
- [ ] **Test draft:** create a blog post with an image and a PDF with preview
  → **Speichern** → wait 2–3 minutes → **Vorschau ansehen**
  - [ ] It opens on `preview.schleyconsult.ch`, on the right post
  - [ ] The PDF viewer works there
  - [ ] Then **Lösche unveröffentlichten Beitrag**, or publish it as the first
    real post
- [ ] `https://preview.schleyconsult.ch/admin` and
  `https://beta.schleyconsult.ch/admin` redirect to the live CMS

## 8. Afterwards

- [ ] Tell the editor: "Vorschau ansehen" now opens preview.schleyconsult.ch
  (see [docs/cms.md](cms.md))
- [ ] Delete this checklist
- [ ] Optional: delete branch `claude/great-meitner-5qzo0g`

## If something goes wrong

| Problem | What to do |
|---------|-----------|
| "Sync main into development" fails with 403 | Step 2: branch protection on `development`; otherwise set workflow permissions to "Read and write" |
| Sync fails with a merge conflict | `git checkout development && git pull && git merge origin/main`, resolve, push |
| PDF viewer empty | Check the browser console; `.mjs` must be served as `text/javascript` (rule in `static/.htaccess`) |
| "Vorschau ansehen" missing or wrong page | Check "Deploy CMS preview" in Actions; send Claude the link it opens |
| Preview/beta shows a cyon error page | Subdomain or folder in step 1 doesn't match the workflow (`deploy_preview.yml` / `deploy.yml`) |
