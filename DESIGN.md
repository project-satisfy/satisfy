---
name: Satisfy
description: Admin UI over Satis for a self-hosted private Composer repository, set out like a PHP manual page.
colors:
  php-900: "#262c52"
  php-800: "#39437a"
  php-750: "#45508a"
  php-700: "#4f5b93"
  php-300: "#b9bedc"
  php-200: "#d5d8eb"
  php-100: "#e8e9f3"
  php-50: "#f3f4f9"
  now: "#a35900"
  now-dot: "#d98a16"
  now-bg: "#fdf3e4"
  now-line: "#efcd98"
  now-ink: "#6b3b00"
  ok: "#2a7548"
  ok-bg: "#e9f4ed"
  ok-line: "#b5d9c2"
  bad: "#b42318"
  bad-bg: "#fdeeec"
  bad-line: "#f1bdb7"
  ground: "#f4f4f8"
  surface: "#ffffff"
  code-bg: "#fafafd"
  ink: "#1d1f27"
  ink-2: "#4d5163"
  ink-3: "#656a7d"
  line: "#d9dbe6"
  line-strong: "#c3c6d6"
  line-soft: "#ebecf2"
  bar-text: "#dfe2f2"
typography:
  headline:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Fira Sans", "Helvetica Neue", Arial, sans-serif'
    fontSize: "26px"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "-0.015em"
  title:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Fira Sans", "Helvetica Neue", Arial, sans-serif'
    fontSize: "17px"
    fontWeight: 600
    lineHeight: 1.25
  title-sm:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Fira Sans", "Helvetica Neue", Arial, sans-serif'
    fontSize: "15px"
    fontWeight: 600
    lineHeight: 1.25
  body:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Fira Sans", "Helvetica Neue", Arial, sans-serif'
    fontSize: "15px"
    fontWeight: 400
    lineHeight: 1.55
    fontFeature: '"tnum"'
  body-sm:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Fira Sans", "Helvetica Neue", Arial, sans-serif'
    fontSize: "14px"
    fontWeight: 400
    lineHeight: 1.55
  label:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Fira Sans", "Helvetica Neue", Arial, sans-serif'
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.2
  meta:
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Fira Sans", "Helvetica Neue", Arial, sans-serif'
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.55
  code:
    fontFamily: 'ui-monospace, "SF Mono", "Fira Mono", "Cascadia Mono", Menlo, Consolas, monospace'
    fontSize: "13px"
    fontWeight: 400
    lineHeight: 1.6
  key:
    fontFamily: 'ui-monospace, "SF Mono", "Fira Mono", "Cascadia Mono", Menlo, Consolas, monospace'
    fontSize: "14px"
    fontWeight: 600
    lineHeight: 1.55
rounded:
  radius-sm: "4px"
  radius: "6px"
  pill: "999px"
spacing:
  tight: "6px"
  inline: "10px"
  field: "16px"
  panel: "18px"
  stack: "20px"
  card: "24px"
  gutter: "28px"
  gutter-mobile: "16px"
  section: "36px"
components:
  button:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    rounded: "{rounded.radius-sm}"
    padding: "6px 14px"
    height: "36px"
  button-hover:
    backgroundColor: "{colors.php-50}"
  button-active:
    backgroundColor: "{colors.php-100}"
  button-primary:
    backgroundColor: "{colors.php-700}"
    textColor: "{colors.surface}"
    typography: "{typography.label}"
    rounded: "{rounded.radius-sm}"
    padding: "6px 14px"
    height: "36px"
  button-primary-hover:
    backgroundColor: "{colors.php-800}"
  button-primary-active:
    backgroundColor: "{colors.php-900}"
  button-quiet:
    backgroundColor: "transparent"
    textColor: "{colors.php-700}"
    rounded: "{rounded.radius-sm}"
    padding: "6px 14px"
  button-quiet-hover:
    backgroundColor: "{colors.php-100}"
    textColor: "{colors.php-900}"
  button-danger:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.bad}"
    rounded: "{rounded.radius-sm}"
    padding: "6px 14px"
  button-danger-hover:
    backgroundColor: "{colors.bad-bg}"
  button-danger-solid:
    backgroundColor: "{colors.bad}"
    textColor: "{colors.surface}"
    rounded: "{rounded.radius-sm}"
    padding: "6px 14px"
  button-danger-solid-hover:
    backgroundColor: "#8f1c13"
  button-sm:
    padding: "4px 10px"
    height: "30px"
  input:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink}"
    typography: "{typography.body}"
    rounded: "{rounded.radius-sm}"
    padding: "6px 10px"
    height: "36px"
  header-bar:
    backgroundColor: "{colors.php-700}"
    textColor: "{colors.surface}"
    height: "58px"
  nav-link:
    textColor: "#e6e8f5"
    rounded: "{rounded.radius-sm}"
    padding: "7px 12px"
  nav-link-hover:
    backgroundColor: "{colors.php-750}"
    textColor: "{colors.surface}"
  nav-link-current:
    backgroundColor: "{colors.php-800}"
    textColor: "{colors.surface}"
  panel:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.radius}"
    padding: "10px 18px 16px"
  form-card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.radius}"
    padding: "6px 24px 24px"
  table-head:
    backgroundColor: "{colors.php-50}"
    textColor: "{colors.ink-2}"
    typography: "{typography.meta}"
    padding: "10px 16px"
  example-box:
    backgroundColor: "{colors.code-bg}"
    textColor: "{colors.php-900}"
    typography: "{typography.code}"
    rounded: "{rounded.radius}"
    padding: "12px 14px"
  example-head:
    backgroundColor: "{colors.php-100}"
    textColor: "{colors.ink}"
    typography: "{typography.label}"
    padding: "8px 8px 8px 14px"
  entry-key:
    textColor: "{colors.php-700}"
    typography: "{typography.key}"
  log:
    backgroundColor: "{colors.code-bg}"
    textColor: "#2a2d3a"
    typography: "{typography.code}"
    rounded: "{rounded.radius-sm}"
    padding: "10px 12px"
  chip:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink-2}"
    rounded: "{rounded.pill}"
    padding: "1px 8px"
  chip-now:
    backgroundColor: "{colors.now-bg}"
    textColor: "{colors.now}"
  count:
    backgroundColor: "{colors.now-bg}"
    textColor: "{colors.now}"
    rounded: "{rounded.pill}"
    padding: "0 7px"
    height: "20px"
  badge:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.ink-2}"
    rounded: "{rounded.pill}"
    padding: "1px 9px"
  badge-ok:
    backgroundColor: "{colors.ok-bg}"
    textColor: "{colors.ok}"
  badge-bad:
    backgroundColor: "{colors.bad-bg}"
    textColor: "{colors.bad}"
  badge-php:
    backgroundColor: "{colors.php-100}"
    textColor: "{colors.php-800}"
  callout-now:
    backgroundColor: "{colors.now-bg}"
    textColor: "{colors.now-ink}"
    rounded: "{rounded.radius}"
    padding: "16px 18px"
---

# Design System: Satisfy

## Overview

**Creative North Star: "The PHP Manual"**

Satisfy's admin is laid out as a reference entry, not a dashboard. It uses the PHP manual's materials, which this audience already trusts: a solid PHP-purple header, a cool lilac-grey page, white reading surfaces, every satis.json option set out as a documented entry with its key, type and default, and "Example #n" boxes that print the JSON a page reads or writes. Visitors come rarely and treat each visit as a first visit, so every page explains itself in a lead sentence and shows the file instead of a separate state.

Density is moderate: 15px body text, hairline rules, compact tables, and a two-column home with a 384px status column. The surface is flat; separation comes from white panels on the lilac ground and 1px rules. Colour carries meaning: purple is structure and links, amber means something is waiting on the visitor, green and red report outcomes. Only state changes move. All tokens are custom properties on `:root` in one plain stylesheet, `public/css/satisfy.css`.

Rejected: the Bootstrap admin of stacked full-width buttons, the metric-card SaaS dashboard, web fonts, icon libraries, and anything that hides what satis.json will contain.

**Key Characteristics:**
- A solid PHP-purple header field; everything below it is lilac-grey ground and white surfaces.
- System mono for satis.json keys, URLs, package names, JSON and logs; system sans for everything else.
- Manual entries: key, type and default beside each configuration control.
- Example boxes show the exact JSON a page reads or writes; on the repository forms the box rewrites itself as you type.
- Amber is reserved for what needs the visitor now.
- Flat and ruled: no drop shadows, no web fonts, no icon library.

## Colors

One muted purple carries structure, one amber carries urgency, green and red report outcomes, and cool neutrals tinted toward the purple do the rest.

### Primary
- **PHP Purple** (#4f5b93): the header field, primary buttons, links, satis.json keys in manual entries, the "Example #n" caption, JSON keys, checkbox and radio accent, and the focus outline.
- **Deep PHP Purple** (#39437a): h1 and h2 text, the current nav item, primary button hover, the "enforcing" badge text.
- **Ink Purple** (#262c52): primary button active, link hover, quiet button hover text, text selection, and the code inside Example boxes.
- **Header Hover Purple** (#45508a): nav item hover inside the header only.
- **Lavender Rule** (#b9bedc): the Example box frame, button and input hover borders, the scrollbar thumb.
- **Lavender Tint** (#d5d8eb): the input focus ring, the rule under the Example box heading, text selection background.
- **Pale Lavender** (#e8e9f3): the Example box heading band, quiet button hover, default button active, the "enforcing" badge background.
- **Lavender Wash** (#f3f4f9): table header row, table row hover, default button hover.

### Secondary
- **Now Amber** (#a35900): text of pending counts and of chips for packages a site is asking for.
- **Now Dot** (#d98a16): the build-state dot while a build is queued or running.
- **Now Wash** (#fdf3e4): background of counts, asking chips, the busy dot's halo, the warning flash, the token-shown-once box and the pending-requests callout.
- **Now Rule** (#efcd98): the border on every Now Wash surface.
- **Now Ink** (#6b3b00): body text inside amber boxes (warning flash, token box).

### Tertiary
- **Pass Green** (#2a7548), with **Pass Wash** (#e9f4ed) and **Pass Rule** (#b5d9c2): the success flash and the "active" badge.
- **Fail Red** (#b42318), with **Fail Wash** (#fdeeec) and **Fail Rule** (#f1bdb7): error text, invalid field borders, the required asterisk, the error flash, the "revoked" badge and the danger buttons.

### Neutral
- **Lilac Ground** (#f4f4f8): the page background; at 94% opacity behind the sticky action bar.
- **White Surface** (#ffffff): panels, tables, form cards, inputs, default buttons; text on purple.
- **Code Paper** (#fafafd): inside Example boxes and build logs.
- **Ink** (#1d1f27): body text.
- **Ink Two** (#4d5163): lead paragraphs, help text, table header text, state detail lines.
- **Ink Three** (#656a7d): muted lines, table meta columns, empty-state and footnote text.
- **Line** (#d9dbe6): borders of panels, tables and form cards; the rule under section headings.
- **Strong Line** (#c3c6d6): button, input, chip and badge borders; the rule under the table header; the dashed empty-state border.
- **Soft Line** (#ebecf2): dividers between table rows, manual entries and settings rows.
- **Header Text** (#dfe2f2): the repository name and the right-hand links in the header.

### Named Rules
**The Amber Means Now Rule.** Amber marks only what needs the visitor now: a queued or running build, sites waiting for packages, a token shown once, a warning, and the value that just changed in an Example box. It is never emphasis, decoration or brand. A screen with nothing waiting has no amber.

**The One Field Rule.** Solid purple fills two things: the header bar and the primary button. Everywhere else purple is text, a 1px rule or a pale tint.

**The Triplet Rule.** Status colour comes as a set of three from one family (`ok`, `bad`, `now`): dark text, a pale wash and a mid-tone rule. The only saturated status fill is the solid danger button on an irreversible confirmation.

## Typography

**Display Font:** none; headings use the body sans at weight 600.
**Body Font:** the system sans stack (-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Fira Sans", "Helvetica Neue", Arial, sans-serif)
**Label/Mono Font:** the system mono stack (ui-monospace, "SF Mono", "Fira Mono", "Cascadia Mono", Menlo, Consolas, monospace)

**Character:** The platform's own sans and mono, as a manual page looks in the reader's browser. Mono marks every literal the visitor might copy or type. Numerals are tabular throughout.

### Hierarchy
- **Headline** (600, 26px, 1.25, -0.015em): the page h1, one per page, in Deep PHP Purple; 23px below 760px. A site or object name inside an h1 is set in mono at 0.92em.
- **Title** (600, 17px, 1.25): section and panel h2 in Deep PHP Purple; a section h2 sits on a 1px Line rule with 8px below it.
- **Title Small** (600, 15px, 1.25): setting names in settings rows, in Ink.
- **Body** (400, 15px, 1.55): prose. Lead paragraphs are Ink Two and capped at 70ch; help text at 72ch.
- **Body Small** (400, 14px, 1.55): panel text, section leads, muted lines, table cells, crumbs.
- **Label** (600, 14px, 1.2): form labels, buttons, the Example box heading.
- **Meta** (400, 13px): table header cells, the type and default under a manual entry key, footnotes (13 to 13.5px).
- **Code** (mono 400, 13px, 1.6): Example boxes and the full build log; the log tail and chips run at 12.5px.
- **Key** (mono 600, 14px): satis.json keys in manual entries, in PHP Purple; site names in request lists. Repository URLs in the table are mono 14px with the last path segment in 600.

### Named Rules
**The Mono Means Literal Rule.** System mono marks only literal values: satis.json keys, URLs, package and site names, file names, JSON, logs, and values such as `vcs` or `dist`. Prose, labels, buttons, headings and status words on badges stay in sans.

**The System Faces Rule.** Only the platform sans and mono stacks; no web font is loaded. Hierarchy comes from the size steps (26, 17, 15, 14, 13) and weight 600, not from another face.

## Layout

The page container is centred at 1320px with 28px side gutters (16px below 760px), 28px above the content and 64px below. The admin home is a two-column grid: a fluid main column and a 384px aside, 28px apart, with aside panels stacked 20px apart. Below 1080px the aside drops under the main column and its panels tile at a 300px minimum; below 760px everything is one column. Form, configuration, delete and per-site pages use an 860px narrow column. Standalone pages without nav (sign in at 380px, catalogue-unavailable at 600px) take a 56px top margin.

Every page opens with a page head: optional crumbs ("Repositories /", 14px), the h1, an optional mono object id, and a lead sentence. Sections stand 36px apart. Action rows sit 24px below their content with 10px gaps; buttons keep their natural width, the primary comes first, and a spacer pushes a destructive action to the far right. The configuration page's action row is sticky at the bottom of the viewport.

Manual entries use a 250px key column beside a fluid control column, 28px apart, with 18px of padding per row; below 760px the key and its meta run inline above the control, separated by " · ". On narrow screens the header keeps the name and right-hand links on the first row and turns the nav into a horizontally scrolling strip below it; there is no menu button. Spacing in use is 4, 6, 8, 10, 12, 14, 16, 18, 20, 24, 28 and 36px with no formal scale; the spacing tokens record the recurring values.

**The Natural Width Rule.** Buttons size to their label and sit in a wrapping row. They are never stretched to full width or stacked, at any breakpoint.

## Elevation & Depth

The system is flat. No surface casts a drop shadow. Depth comes from white surfaces on the lilac ground, 1px rules, and pale tint bands (table header, Example box heading). `box-shadow` appears only as a zero-offset ring that reports state. The one overlapping layer is the sticky action bar: Lilac Ground at 94% opacity, a 6px backdrop blur and a 1px top rule.

### Shadow Vocabulary
- **Input focus ring** (`box-shadow: 0 0 0 3px var(--php-200)`): with a PHP Purple border on a focused input, select or textarea.
- **Busy halo** (`box-shadow: 0 0 0 4px var(--now-bg)`): around the amber build-state dot while a build is queued or running.
- **Changed highlight** (`box-shadow: 0 0 0 2px #fde7b6`, with the same background): on the JSON value just rewritten in an Example box, fading out over 1.1s.

### Named Rules
**The Ruled, Not Raised Rule.** Surfaces separate by a 1px rule and the step from ground to white. A box-shadow is only ever a zero-offset ring that reports state.

## Shapes

Two radii: 4px on controls (buttons, inputs, nav items, logs, the pre inside the token box) and 6px on containers (panels, form cards, table frames, Example boxes, flashes, callouts). Chips, counts and badges are full pills; the build-state dot is a 10px circle. Borders are 1px solid; the empty state is the one dashed border. Containers with a heading band clip their contents so the band follows the corner.

## Components

### Buttons
Plain, bordered, natural width.
- **Shape:** gently rounded (4px), 36px minimum height (30px small), 6px 14px padding (4px 10px small), Label type.
- **Default:** White Surface, Strong Line border, Ink text. Hover: Lavender Wash with a Lavender Rule border. Active: Pale Lavender.
- **Primary:** solid PHP Purple, white text. Hover Deep PHP Purple, active Ink Purple. One per action row, first in the row.
- **Quiet:** transparent, PHP Purple text; hover Pale Lavender. Used for Cancel, Keep it, Copy and Remove.
- **Danger:** white with Fail Red text and a Fail Rule border; hover Fail Wash with a Fail Red border. Used for the Delete link on the edit page, Replace the token, Enable enforcement.
- **Danger solid:** Fail Red fill, white text, hover #8f1c13. Only on the final confirmation of an irreversible action (Delete repository, Revoke).
- **Disabled:** 55% opacity, not-allowed cursor.
- **Motion:** background and border colour transition over 150ms on the system ease.

### Chips, Counts and Badges
- **Chip:** pill, mono 12.5px, white with a Strong Line border, Ink Two text; one package name. The amber variant marks a package a site is asking for.
- **Count:** pill, 600 12px on a 20px line, 22px minimum width, amber triplet; the number of things waiting, placed after a heading's text.
- **Badge:** pill, sans 600 12.5px; a status word. Green for active, red for revoked, lavender for enforcing, neutral for observing.

### Cards / Containers
- **Corner Style:** 6px.
- **Background:** White Surface on Lilac Ground.
- **Shadow Strategy:** none; see Elevation & Depth.
- **Border:** 1px Line.
- **Panel:** aside panels; head 14px 18px 0 with the h2 and a right-hand link, body 10px 18px 16px, foot 12px 18px above a Line rule holding the panel's one button.
- **Form card:** 6px 24px 24px padding (4px 16px 20px on narrow screens).
- **Table:** framed and clipped; header row in Lavender Wash, 13px 600 Ink Two; rows 9px 16px with Soft Line dividers and Lavender Wash hover; meta columns mono 13px Ink Three; an optional foot row at 13.5px.
- **Callout:** 16px 18px; the amber variant holds pending access requests.
- **Token box:** the amber triplet with Now Ink text and a white inner pre on a Now Rule border; holds a secret shown once.
- **Empty state:** white with a dashed Strong Line border, 32px 24px, centred heading, 52ch text and centred actions.

### Inputs / Fields
- **Style:** 36px minimum height, 6px 10px padding, 1px Strong Line, 4px radius, white, 15px sans. URLs, keys, paths and JSON use mono at 14px/1.5.
- **Label and help:** label above (14px 600, 6px gap); help below (13.5px Ink Two, 72ch); a required field adds a Fail Red asterisk after the label.
- **Hover:** Lavender Rule border.
- **Focus:** PHP Purple border and the 3px Lavender Tint ring; no outline.
- **Error:** Fail Red border, an error list below in Fail Red 14px 500; form-level errors sit in a Fail triplet box at the top of the form.
- **Checkbox and radio:** native at 17px with a PHP Purple accent colour, label to the right at 15px 500.
- **Collections:** rows of paired inputs (package and constraint) with a quiet Remove at the row's end and a small "Add package" button below.

### Navigation
- **Header bar:** solid PHP Purple, 58px minimum height. Left: "Satisfy" in white 700 17px, then the satis.json name in mono 14px Header Text. Nav items 14.5px 500 in #e6e8f5, 7px 12px padding, 4px radius; hover Header Hover Purple, current page (`aria-current`) Deep PHP Purple, both with white text. Right: View catalogue and Log out at 14px in Header Text, underlined on hover. Focus outlines turn white inside the header.
- **Mobile:** the nav becomes a horizontally scrolling strip under the name row.
- **Crumbs:** the parent page as a 14px link followed by " /", above the h1 on child pages.

### Manual Entry
The configuration page's signature. Each row sets the satis.json key in PHP Purple mono 600 14px in a 250px left column, with its type and default below in 13px Ink Three ("bool · default `false`"). The right column holds the label, control and help. Rows are divided by Soft Line and grouped under section h2s. Below 760px the key and meta sit inline above the control.

### Example Box
The manual's code example, used wherever a page reads or writes JSON.
- **Frame:** 1px Lavender Rule, 6px radius, Code Paper inside, clipped.
- **Heading band:** Pale Lavender with a Lavender Tint rule below, 14px 600: "Example #n" in PHP Purple, then the title in Ink; an optional small quiet Copy button at the right.
- **Body:** mono 13px/1.6 in Ink Purple, 12px 14px padding; JSON keys in PHP Purple, strings in #1e5f7a.
- **Foot:** optional, 13.5px Ink Two.
- **Content:** always the real JSON: the composer.json snippet on the home page, the entry a repository form will write, the entry a delete removes. On the repository forms the box rewrites itself as you type; the changed value gets the Changed highlight and the pre is an `aria-live` region.
- **Placement:** directly under the form it describes, 20px below, or in the home aside.

### Build State
- **State line:** a 10px dot and a 600 headline ("Building now", "Queued, waiting for the worker", "No build running"). The dot is Strong Line grey when idle and Now Dot with the Busy halo when queued or running, crossfading over 200ms. A 14px Ink Two detail line follows with timestamps as YYYY-MM-DD HH:MM.
- **Log:** mono 12.5px/1.6 on Code Paper, 1px Line, 4px radius; eight lines and 168px maximum in the home panel; on the build page 13px, 62vh tall, wrapped, following the tail. An empty log reads "No build has written a log yet."
- **Refresh:** polled from the status endpoint (5s on the home page, 2s on the build page), paused while the tab is hidden.

### Flash Messages
Stacked 10px apart above the page content; each has a 1px border, 6px radius, 11px 14px padding and 14.5px text. Success, warning and danger use their triplets with darker text (#1d4f31, Now Ink, #7d180f). A 26px square transparent dismiss button sits at the right and the message fades out over 200ms. The shipped dismiss button uses a × text glyph; that is a known defect, not the pattern.

## Do's and Don'ts

### Do:
- **Do** show the JSON: any page that writes satis.json carries an Example box with the exact entry, captioned "Example #n" and a title saying what it is.
- **Do** give every configuration control a manual entry head: the key in PHP Purple mono, then its type and default as 13px meta.
- **Do** open every page with an h1 and a lead sentence (Ink Two, at most 70ch) that says what the page changes and when it takes effect.
- **Do** set every literal in mono: keys, URLs, package and site names, file names, JSON, logs.
- **Do** keep status in triplets (text, wash, rule) from the `ok`, `bad` or `now` family.
- **Do** write timestamps as YYYY-MM-DD HH:MM in tabular numerals.
- **Do** limit motion to state changes: 150ms on hover colour, 200ms fades, `cubic-bezier(.16, 1, .3, 1)`, and zero durations under `prefers-reduced-motion`.
- **Do** keep the solid danger button for the final confirmation of an irreversible action; use the outline danger button everywhere else.

### Don't:
- **Don't** build a SaaS dashboard: no metric cards, KPI tiles or charts on the admin pages.
- **Don't** stack full-width buttons; buttons keep their natural width in a wrapping row at every breakpoint.
- **Don't** use amber for anything that is not waiting on the visitor now.
- **Don't** fill any surface with solid purple other than the header bar and the primary button.
- **Don't** add drop shadows or raised cards; separation is a 1px rule and white on lilac.
- **Don't** load web fonts or an icon library.
- **Don't** animate anything on page load.
- **Don't** set prose, labels or headings in mono, or literals in sans.
- **Don't** hide what satis.json will contain behind a summary or a separate state.
