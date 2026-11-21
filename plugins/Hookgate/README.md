# hookgate

For GitLab system hooks, which fire for every project on an instance. Enable it with `SATISFY_PLUGINS=hookgate`. It acts only on deliveries to `/webhook/gitlab`, and has three parts.

## The registration gate

With `gitlab.auto_add_repo` on, the GitLab webhook adds any repository it does not know, and a system hook names every project on the instance. The gate, a listener on `RepositoryAddingEvent`, lets a repository be added only if some branch or tag has a `composer.json` declaring one of the published types (`HOOKGATE_PACKAGE_TYPES`).

A repository without one would make the next build that widens Composer's pool fail. A site that declares `project` usually carries a copied `composer.json`, and would otherwise register as a package.

The gate fails open. When GitLab cannot be asked, or does not answer clearly, the repository is added, as it would be without the plugin. A refused delivery is answered 200 with `skipped: <reason>`, so GitLab does not count it as a failure.

## Auto-require

`satis.json` publishes a package only when it has both a repository entry and a `require` entry, and a person used to add the second. Auto-require, a listener on `WebhookReceivedEvent`, adds both when a tag is pushed to a package that is not yet required, and the build that follows publishes it.

It refuses:

- a type outside `HOOKGATE_PACKAGE_TYPES`, or `project`;
- a Drupal 7 project, and a `drupal/` name that drupal.org serves;
- a name in `satis.json`'s `blacklist`;
- a `composer.json` without a valid name, or whose `version` is not the tag;
- a `satis.json` with `require-all`, which has no `require` list to add to;
- an edit the checker refuses;
- anything it cannot decide because GitLab or drupal.org did not answer, or because `satis.json` kept changing while it checked.

| `HOOKGATE_AUTO_REQUIRE` | What happens |
|---|---|
| `off` | Nothing |
| `observe` (the default) | It decides and logs what it would add, and writes nothing |
| `on` | It answers GitLab at once and does the work after the response, on `kernel.terminate`. The check runs without the lock on `satis.json`, and the result is written only if the file has not changed meanwhile, or planned again if it has |

## A second build after a branch push

GitLab answers its branches API from a cache kept for 30 seconds per user, and Composer's GitLab driver reads every branch head from it with the one token every build uses. So a build for a push that lands within 30 seconds of another build of the same project can list the old head, exit 0 and publish nothing: a merge pushed a few seconds after its branch stays unpublished until the next build.

A listener on `BuildEvent` therefore queues one more build of the repository `HOOKGATE_REFORWARD_DELAY` seconds (35 by default) after a branch push. Any cache entry still alive then was filled after the push. Another push restarts the wait, so a run of pushes makes one extra build. Tag pushes queue nothing extra, since GitLab does not cache tags. `0` turns it off.

## Configuration

| Variable | Meaning |
|---|---|
| `HOOKGATE_GITLAB_API_TOKEN` | A `read_api` token. `GITLAB_API_TOKEN` is read too. Without one, both parts stand aside |
| `HOOKGATE_GITLAB_API_BASE` | Default `https://<the project's host>/api/v4`. `GITLAB_API_BASE` is read too |
| `HOOKGATE_AUTO_REQUIRE` | `off`, `observe` (the default) or `on` |
| `HOOKGATE_PACKAGE_TYPES` | The Composer types published, comma-separated. The default is `PackageTypes::DEFAULT`: `drupal-module`, `drupal-theme`, `drupal-recipe`, `library`, `drupal-custom-module`, `drupal-custom-theme`, `drupal-profile`, `drupal-drush`, `drupal-library` and `metapackage` |
| `HOOKGATE_CHECKER` | A command that checks a candidate `satis.json` before auto-require writes it. It gets `SATIS_JSON`, `SATIS_CHECK_SCOPE=file`, `GITLAB_API_TOKEN`, `GITLAB_API_BASE` and `PACKAGE_TYPES_FILE`; exit 0 or 4 lets the write go ahead, anything else refuses |
| `HOOKGATE_REFORWARD_DELAY` | Seconds after a branch push to build its repository once more. Default `35`; `0` turns it off |
| `HOOKGATE_DRUPAL_ORG_P2` | Where to ask whether drupal.org serves a `drupal/` name. Default `https://packages.drupal.org/files/packages/8/p2` |

The webhook itself is Satisfy's: `GITLAB_SECRET` checks `X-Gitlab-Token`, and `gitlab.auto_add_repo` turns auto-add on. Set `GITLAB_SECRET`: without it both parts stand aside, since anyone could send the delivery, and the project URL in it picks the host the API token is sent to and the repository written to `satis.json`. Satisfy itself auto-adds nothing without it.

## Decisions

Every decision is one JSON line on standard error, so the container's log has them:

```
docker logs <container> 2>&1 | grep -E 'registered_|skipped_|auto_require'
```

| Decision | Meaning |
|---|---|
| `registered_new_package` | The gate found a published type; the repository is added |
| `registered_undetermined` | GitLab did not answer clearly; added, failing open |
| `registered_no_api_token` | No token to ask GitLab with; added |
| `skipped_unauthenticated` | The delivery was not checked against `GITLAB_SECRET`; refused without asking GitLab |
| `registered_unparseable_url` | The project's path could not be read from the delivery; added |
| `skipped_not_a_package` | No branch or tag has a `composer.json`; refused |
| `skipped_type_project` | The `composer.json` declares `project`; refused |
| `skipped_type_unlisted` | It declares a type outside the list; refused |
| `auto_require_would_add` | In observe mode: what on would have added |
| `auto_require_added` | In on mode: added to `satis.json` |
| `auto_require_skipped` | Not published, for an expected reason: `project`, Drupal 7, served by drupal.org, or blacklisted |
| `auto_require_refused` | Not published, for any other reason, which the line gives; `unauthenticated` when `GITLAB_SECRET` is not set |
| `auto_require_replanned` | `satis.json` changed while it was checking; it planned again |
| `auto_require_error` | It failed; the line gives the error |
| `rebuild_scheduled` | A branch push: one more build of the repository is queued for later |
| `rebuild_failed` | That second build could not be queued; the build for the push itself was |

## Coming from the standalone hookgate service

- Point GitLab's system hook at Satisfy's `/webhook/gitlab`, with the same secret (`GITLAB_SECRET`), or keep the old address and have the web server proxy it there. The `/_hookgate` front and its forwarding are gone.
- The decisions `skipped_*` and `auto_require_*` keep their names. `forwarded_new_package`, `forwarded_undetermined` and `forwarded_no_api_token` become `registered_new_package`, `registered_undetermined` and `registered_no_api_token`.
- Re-forwarding becomes a second build queued in Satisfy, with the same `HOOKGATE_REFORWARD_DELAY`; `HOOKGATE_REFORWARD_STATE` and the extra FPM child are not needed.
- `auto_require_published` and `auto_require_not_visible` are not ported: the build runs in the worker after the delivery, and its result is in the worker's log.

## Layout

| Path | What it holds |
|---|---|
| `src/RegistrationGate.php` | The gate, on `RepositoryAddingEvent` |
| `src/AutoRequire.php`, `src/CatalogueEdit.php`, `src/PackageRules.php` | Auto-require: the decision, the edit to `satis.json`, and the refusals |
| `src/Checker.php` | Runs `HOOKGATE_CHECKER` on a candidate `satis.json` |
| `src/BranchCacheRebuild.php` | The second build after a branch push, on `BuildEvent` |
| `src/HookgateConfig.php`, `src/PackageTypes.php`, `src/Http.php`, `src/HookgateLog.php` | Settings, the type list, GitLab and drupal.org requests, and the decision log |

Tests are in `tests/Plugin/Hookgate/`. `docs/plugins.md` describes plugins in general.
