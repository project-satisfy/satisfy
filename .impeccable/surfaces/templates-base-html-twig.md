---
version: 1
slug: "templates-base-html-twig"
primary_target: "templates/base.html.twig"
related_targets: ["templates/views/home.html.twig","templates/views/configuration.html.twig","plugins/Authz/templates/index.html.twig"]
---

# Satisfy admin

Scope: every page under /admin and /login, the "unavailable" page at /, and the Authz plugin pages. Visitor mode: Operate.

Audience and job: self-hosters who come back rarely to add or import a repository, change satis.json, queue a build and read its log, or approve a site's package request. Each visit is effectively a first visit.

Content: satis.json (repositories, the full Satis option set), the build queue state and last build log, authz sites, grants and requests. No invented data.

Constraints: no build step (plain CSS and JS in public/), Bootstrap 3, jQuery and glyphicons removed, no web fonts or icon libraries, routes and form field names unchanged, neutral enough to propose upstream.

Lead information on /admin: repository list, build state, access requests (when authz is enabled), the composer.json snippet.

Wrong if: it looks like a SaaS dashboard, hides what satis.json will contain, or is heavy.

## Direction contract

THESIS: Satisfy's admin reads like the PHP manual, the reference this audience already trusts: every option is a documented entry and every write shows the JSON it produces. It refuses the Bootstrap admin of stacked full-width buttons and the metric-card dashboard.

OWN-WORLD: Muted PHP purple (#4f5b93) owns a solid header field; cool lilac-grey ground (#f4f4f8), white reading surfaces, ink #1d1f27. One reserved amber marks only what needs the visitor now (running or queued build, pending requests). System sans for prose, system mono only for keys, URLs, JSON and logs. Manual entries: key in purple mono, type and default as quiet meta, one-line description. Example boxes: lavender-ruled frame, "Example #n" caption, code on near-white.

STORY: The visitor sees what satis.json holds, what is building, and what is waiting on them, changes one thing, and sees the exact JSON it wrote.

FIRST VIEWPORT: Purple header with the satis.json name, nav (Repositories, Build, Configuration, Import, plugin links), View catalogue and Log out at right. Left two thirds: "Repositories" heading, count line, filter field, Import and Add buttons, the repository table. Right column: Build (state with amber dot, log tail, Queue a full build), plugin panels (Access requests), and "Example #1: use this repository" with the composer.json snippet and Copy. Signature interaction: on the new and edit repository pages the Example box under the form rewrites itself as you type, showing the entry satis.json will get, with the changed value briefly highlighted. Motion grammar: only state changes move (150 to 200 ms ease-out fades); nothing enters on load.

FORM: PHP Manual, grounded direction 1 of 3 in the safer re-roll; seed:71728dbd53bf93c6

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
