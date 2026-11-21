# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Self-hosters of a private Composer repository: developers or ops people who set Satisfy up once and come back rarely, to add a repository, import a `composer.lock`, change the Satis configuration, queue a build and read its log, or, with the `authz` plugin, issue a site token and approve the packages a site asks for. They arrive with one job in mind and leave when it is done, so every visit is effectively a first visit.

## Product Purpose

Satisfy is a web UI over Satis. It reads and writes `satis.json`, queues builds for a worker that runs Satis, and receives webhooks from GitLab, GitHub, Gitea, Bitbucket and Azure DevOps that queue a build of the repository pushed to. Success is a visitor finding the one action they came for, doing it without consulting docs, and seeing that it took effect (the file changed, the build queued, the build passed or failed and why).

## Positioning

Self-hosted and file-based: `satis.json` is the single source of truth, there is no database, the catalogue is served by nginx as static files, and builds run in a worker off the request path. From 4.x the optional `authz` plugin adds per-site tokens with per-package grants and an approval queue for packages a site asks for, which is an important part of what the UI now does.

## Operating Context

- Admin UI under `/admin`, optional form login (`admin.auth`, users in `parameters.yml`), `/login`.
- Pages: repository list (home), new repository, edit repository, delete repository, upload `composer.lock`, edit configuration (the whole Satis schema: name, homepage, require, abandoned, blacklist, archive, stability, providers, etc.), build (queue a full build, live status and last log, polled every 2 s).
- `/` serves the catalogue HTML Satis generates, or an "unavailable" page before the first build.
- Plugins add admin links to the home page (`plugin_admin_links()`); `authz` adds `/admin/authz` (sites, pending package requests, issue token, credential enforcement switch) and a page per site (token shown once, note, requests, grants, settings, revoke).
- The configuration page refuses to save when `satis.json` changed since it was shown.

## Capabilities and Constraints

- Symfony 7.4, Twig templates in `templates/` and `plugins/*/templates/`, Symfony forms with form themes in `templates/form/`.
- No frontend build step: CSS and JS are plain files in `public/`, no npm toolchain.
- Bootstrap 3, jQuery 2.2.4 and glyphicons may be removed and replaced with hand-written CSS and small vanilla JS. The configuration form's add/remove collection rows currently depend on `jquery.collection.js` and need a replacement.
- Working without JavaScript is not a requirement.
- Plugin pages are in scope and must share the admin's look.
- The UI must stay neutral enough to propose upstream to project-satisfy/satisfy.

## Brand Commitments

- Name: Satisfy. The heading shows the repository's own `name` from `satis.json`.
- No logo or brand assets exist beyond `favicon.ico`.

## Evidence on Hand

- No screenshots, testimonials or usage data. Upstream README links old screenshots of the 3.x UI.
- Test fixtures in `tests/fixtures/` (satis.json variants, webhook payloads) are the only sample content.

## Product Principles

1. The file is the truth: show what `satis.json` holds and what a change will write, never a separate state.
2. Rare visits: every page explains itself; nothing depends on remembering how it worked last time.
3. Builds are asynchronous: always say what is queued, running, and how the last build ended.
4. Access decisions are consequential: token issue, revoke and enforcement switches say exactly what changes for whom.
