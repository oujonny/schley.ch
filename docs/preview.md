# Previewing changes

There are two kinds of changes, and they are previewed differently.

## Content changes (posts, texts) — through the CMS

This is the normal editorial workflow (details in the main
[README](../README.md#editorial-workflow-draft--review--publish)):

```
CMS "Save"  →  branch cms/<collection>/<slug>  →  beta.schley.ch  (preview)
CMS "Publish"  →  merged into main  →  schleyconsult.ch  (live)
```

Every saved draft is built and deployed to **<https://beta.schley.ch>**, where
you can check it on the real site before publishing.

## Code / template changes — through Git branches

| Branch | Deploys to |
|--------|-----------|
| `main` | schleyconsult.ch (live) |
| `development` | beta.schley.ch |
| `cms/**` | beta.schley.ch |
| anything else (e.g. `claude/…`) | **nowhere**, only stored on GitHub |

Work on other branches has to reach `development` (or a `cms/**` branch) to be
visible on beta.

> `beta.schley.ch` is a **single** site. It always shows whichever of these
> branches was deployed **last**.

## Important: CMS drafts start from `main`

The CMS creates every draft branch from **`main`**. A draft therefore only
contains templates and CMS fields that are **already on `main`**.

Consequence for new features (like the blog overview and PDF previews):
until they're merged into `main`, a blog post written in the CMS is previewed
on beta with the **old** templates, and the new CMS fields (PDF-Downloads,
Vorschau anzeigen) aren't available yet.

## Ways to preview an unreleased feature

| Option | What you see on beta | Touches `development`/`main` |
|--------|---------------------|------------------------------|
| **Demo branch** `cms/preview-<name>` = feature branch + sample content | Everything, with realistic sample posts | No |
| Merge feature into `development` | New code, but only with existing content (e.g. empty blog) | Yes (`development`) |
| Locally with `docker-compose up` | Everything, on your machine at `localhost:1313` | No |

**Demo branch** (recommended for showing a feature):

1. Create a branch named `cms/preview-<something>` from the feature branch.
2. Add sample content (posts, images, PDFs). Never merge this branch.
3. Push it. `deploy.yml` builds it and beta shows it.
4. When done, delete the branch. Beta goes back to normal on the next
   `development` deploy.

**Rolling out a feature:** merge it into `development` → check on beta →
merge `development` into `main`. Only then write real content with the new
CMS fields.

## Status (15 Nov 2026)

- **No real blog posts** exist on `main` or `development` yet.
- The feature branch contains **3 sample posts** for previewing (all files
  named `beispiel-*`). **Remove them before merging:**
  `git rm content/blog/beispiel-* static/files/blog/beispiel-*`
- The blog overview, homepage section and PDF features are on the branch
  `claude/great-meitner-5qzo0g` and **not yet on beta or live**.
- Once on `main`: the blog section and menu item stay hidden until the first
  post is published.
