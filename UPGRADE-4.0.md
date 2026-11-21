# Upgrading to Satisfy 4.0

4.0 keeps 3.x's `satis.json`, output URLs, webhook paths, admin UI, `parameters.yml` keys and environment variables. What changes is how builds run, what serves the catalogue, and how `satis.json` is written. `docs/4.0-architecture.md` describes the design; the sections below list every change.

## What a 3.x deployment has to do

With the Docker image:

1. Pull the 4.0 image. The default role runs nginx, php-fpm and the worker together, so the same `docker run` or Compose service keeps working.
2. If you mounted your own `config/config.yml`, move its overrides into a file under `config/packages/`. A mounted `parameters.yml` is read again and needs nothing.
3. If anything reads a webhook's response to learn whether the build passed, have it read the worker's output or `var/log/build.log` instead.
4. If Bitbucket's webhook reaches Satisfy through a reverse proxy, set `BITBUCKET_SECRET` and give the webhook the same secret.
5. If another program writes `satis.json`, have it take an exclusive `flock()` on the file and write it in place.
6. If a webhook auto-adds repositories (`*.auto_add_repo`), set its secret: 4.0 auto-adds only for deliveries checked against one.
7. If `admin.auth` is on, replace each password in `admin.users` with the output of `bin/console security:hash-password`. Plaintext still signs in, with a warning in the log each time.

Without the image:

1. Run PHP 8.4 or later, then `composer install`.
2. Steps 2 to 7 above.
3. Run `bin/console satisfy:worker` as a service, as the user the web server runs as, or from cron with `--once`. Nothing builds without it.
4. Point the web server's document root at `public/` as before. Serving `packages.json`, `p2/`, `include/` and `dist/` as static files, as the image's `docker/nginx.conf` does, keeps PHP out of Composer's requests.

## Plugins

- `SATISFY_PLUGINS` enables plugins, comma-separated; none by default. Two ship with 4.0: `authz` (per-token access with per-package grants) and `hookgate` (a registration gate and auto-require for GitLab system hooks). Each has a README in `plugins/<Name>/`, which also says how to move from the standalone service it replaces; `docs/plugins.md` describes plugins in general.
- With the image, enabling `authz` puts the catalogue behind `auth_request`: everything nginx serves from `public/` except the admin UI, its assets, `/login` and the webhooks, so that a file Satis writes outside `packages.json`, `p2/`, `include/` and `dist/` is not served to everyone. Nothing changes while it is not enabled.
- `authz` reads the package a request is for from the path nginx serves the file from, dot segments and repeated slashes resolved, and refuses a path it cannot read for certain while enforcement is on.
- `hookgate` acts only on deliveries checked against `GITLAB_SECRET`. Without it, the project URL a delivery gives would pick the host its API token is sent to.
- A webhook delivery that a plugin skips, or whose auto-add a plugin refuses, is answered 200 with `skipped: <reason>`.
- For code that extends Satisfy: two new events, `WebhookReceivedEvent` and `RepositoryAddingEvent`; `AbstractWebhook::addRepository()` dispatches the second before an auto-add, and the GitLab, GitHub and Gitea webhooks use it. `Manager::modify()` applies a change to `satis.json` under the lock, and `JsonPersister::serialize()` gives the JSON a flush would write.

## The admin UI no longer uses Bootstrap or jQuery

- The admin pages keep their URLs and form field names, and are restyled by one stylesheet, `public/css/satisfy.css`, and one script, `public/js/satisfy.js`. Bootstrap 3, jQuery, `jquery.collection.js` and the glyphicon fonts are gone from `public/`.
- The forms use the `form/satisfy.html.twig` theme instead of `bootstrap_3_layout.html.twig`. A template of your own that extends `base.html.twig` and relies on Bootstrap's classes needs restyling.
- The home page shows the build's state and the last lines of its log beside the repository list; the configuration page shows each option's `satis.json` key; the repository pages show the entry they write.
- Plugin links from `AdminLinkInterface` are in the navigation rather than buttons on the home page. `AdminPanelInterface` adds a panel to the home page; `authz` lists the sites asking for packages there.
- `/admin/satis/build/status` also returns `updated`, when the last build's log was written, as a Unix timestamp or `null`.

## Auto-add needs the webhook's secret

- `gitlab.auto_add_repo`, `github.auto_add_repo` and `gitea.auto_add_repo` add a repository only for a delivery checked against the webhook's secret: `GITLAB_SECRET`, `GITHUB_SECRET` or `GITEA_SECRET`. Without one, anyone who could reach the webhook could add a repository of theirs to `satis.json`, and the catalogue would publish its packages. Such a delivery for a repository `satis.json` does not list is answered `200 skipped: auto-add needs the webhook's secret to be set`.
- GitLab's `X-Gitlab-Token` is compared in constant time, and the URL a GitLab delivery gives is matched as written; 3.x made a pattern of it with only `.` and `:` escaped.
- For code that extends Satisfy: `AbstractWebhook::isAuthenticated()`, and `isAuthenticated()` on `WebhookReceivedEvent` and `RepositoryAddingEvent`, which take it as a fourth constructor argument, false when left out.

## Admin passwords are hashed, and sign-in is throttled

- A password in `admin.users` may be a hash, as `bin/console security:hash-password` prints one. 3.x stored them as plaintext, and plaintext still signs in, with a warning in the log each time; a value that is a hash is never compared as plaintext, so the hash itself is not a password.
- Five failed sign-ins in a minute for one username from one address, or twenty-five from one address for any username, refuse further attempts until the minute is up. Behind a proxy, set `TRUSTED_PROXIES` so that the address counted is the client's and not the proxy's.
- `symfony/rate-limiter` is a new dependency.

## Deleting a repository checks the form's token

The delete page's form is checked for its CSRF token, as every other admin form is. 3.x deleted the repository on any `DELETE` request, or `POST` with `_method=DELETE`, which a page on another site could send. `DeleteFormType` now has the `submit` button the page renders.

## `satis.json` is written under a lock on the file

- Every write takes an exclusive `flock()` on `satis.json` itself, and writes the file in place, so a single-file bind mount keeps pointing at it. 3.x locked a separate file inside the container, which nothing else could take.
- Adding, editing and deleting repositories, from the admin UI, a webhook's auto-add or a `composer.lock` upload, re-reads `satis.json` once the lock is held and applies the change to that. A repository another program added in the meantime is kept; in 3.x the write replaced it.
- The configuration page replaces almost the whole file, so it is saved only if `satis.json` is unchanged since the page was shown. Otherwise it says so and saves nothing. A submission without the form's version field is saved as in 3.x.
- Satisfy waits up to 10 seconds for the lock, then fails the write. Another program that writes `satis.json` should take the same exclusive lock, write in place, and hold it briefly.
- Satis reads `satis.json` without a lock, as it always has.
- For code that extends Satisfy: `Manager` no longer takes a `LockFactory`, and `Manager::acquireLock()` is gone. `Manager::flush()` takes the version the edit started from, which `Manager::getVersion()` returns. `FilePersister` and `JsonPersister` implement `LockablePersisterInterface`. The `satis` lock is gone from `config/packages/lock.yaml`; `LOCK_DSN` now serves only the build lock.

## PHP 8.4 and Symfony 7.4

- Satisfy needs PHP 8.4 or later. Satis itself has required 8.4 since July 2026, so no release that updates its dependencies could run on less.
- Symfony 7.4, the long-term release with security fixes until November 2029, replaces 6.4.
- `doctrine/annotations`, `doctrine/instantiator`, `ramunasd/symfony-container-mocks` and `symfony/proxy-manager-bridge` are no longer dependencies.
- Forms still use session-based CSRF tokens, as in 3.x. The stateless tokens Symfony's recipe now proposes check the `Origin` header, which no longer matches the request behind a proxy that terminates TLS.
- For code that extends Satisfy: the form types, the data transformer, the validators and `ConfigurationNormalizer` carry Symfony 7's parameter and return types. `ConfigurationNormalizer` now builds `Configuration`'s repositories itself, because Symfony 7.4's type resolution tries `\ArrayIterator` first among the types `setRepositories()` accepts and so never asks for `RepositoryInterface[]`.

## The Docker image

- NGINX Unit is gone. The image is `php:8.4-fpm-bookworm` with nginx, php-fpm and the worker, built from `docker/Dockerfile`. It still listens on port 80 and keeps the application in `/var/www/html`, so the same volume mounts work.
- The first argument picks what runs: `all` (the default), `web` (nginx and php-fpm), `worker`, or `rebuild` to build once and exit. Anything else runs as given.
- nginx serves `packages.json`, `p2/`, `include/`, `dist/` and the HTML catalogue from `public/` directly. PHP serves only the admin UI, `/login` and `/webhook/*`, and its responses carry `X-Frame-Options: DENY` and `X-Content-Type-Options: nosniff`.
- nginx and php-fpm start without `COMPOSER_AUTH`; only the worker gets it. A Composer `auth.json` mounted into `/var/www/.composer` is still readable by every process in the container, so mount it into a separate worker container if that matters to you (README, "Running the web server and the worker apart").
- The configuration is compiled afresh on every start, so a changed `parameters.yml` or environment takes effect without `cache:clear`.
- If `public/` is a volume, the entrypoint copies the image's `index.php` and assets back into it on every start, because a volume keeps whatever image first filled it, and 3.x's `index.php` does not run on 4.0. The catalogue's files are not touched. Set `SATISFY_SYNC_PUBLIC=0` to stop it, as `docker-compose.yml` does for development.
- In the image the queue is `/var/www/queue` (`SATISFY_QUEUE_DIR`), a volume of its own, so separate web and worker containers can share it.
- The image is published to GHCR by `.github/workflows/docker-image.yaml`, after `docker/smoke-test.sh` has passed against it.

## `TRUSTED_PROXIES` is read

`.env` names `TRUSTED_PROXIES`, but before 4.0 nothing read it, so behind a proxy that terminates TLS Satisfy built `http://` URLs, its login redirect among them, unless a mounted configuration file set `framework.trusted_proxies`. 4.0 passes it to Symfony, which then believes `X-Forwarded-For`, `X-Forwarded-Proto` and `X-Forwarded-Port` from those proxies and nobody else. It takes addresses and CIDR ranges, comma-separated, or `private_ranges`, or `REMOTE_ADDR` for whatever connects. A 3.x deployment that set `framework.trusted_proxies` in a mounted `config.yml` for this can drop it.

In the image, nginx reads the same list: behind those proxies, its access log and authz's audit log give the client's address from `X-Forwarded-For` rather than the proxy's. PHP still sees the proxy's address and applies the list itself. An entry that is neither an address nor a range is left out, with a warning at start.

## `config/parameters.yml` is read again

3.x read `config/parameters.yml` through `config/config.yml`; the move to Symfony Flex on `main` stopped reading it, so a mounted one was silently ignored. 4.0 reads it again, after `config/services.yaml`, so its values override the defaults there, as in 3.x.

A 3.x deployment that mounted its own `config/config.yml` has to move those overrides into a file under `config/packages/`.

## Builds run in a worker

- Run `bin/console satisfy:worker` alongside the web server. Webhooks and the admin UI queue builds; nothing builds until the worker takes them. `--once` runs whatever is queued and exits, which also suits a cron job.
- `SATISFY_QUEUE_DIR` (default `var/queue`, and `/var/www/queue` in the image) is where requests wait. The web server and the worker must both be able to write it.
- `SATISFY_BUILD_TIMEOUT` (default `600`) is how many seconds one Satis build may run.
- The worker writes the last build's output to `var/log/build.log`, and one JSON line per build to its own output.
- `bin/console satisfy:rebuild` still builds in the calling process. It now takes the same lock as the worker, so the two never run at once. With no output directory given it uses the one `satis.json` names, or Satisfy's default `public`, as the worker does; in 3.x it failed when `satis.json` named none.

## Webhooks answer before the build

- A webhook answers `202 Accepted` with the body `queued` once the build is queued. In 3.x it answered after the build, `200` with the exit code or `500`.
- If a listener stops `BuildEvent` before the queue sees it, the webhook answers `200` with `skipped`.
- Anything that read the webhook's response to learn the build result has to read the worker's output or `var/log/build.log` instead.

## The GitHub, Gitea, Bitbucket and Azure DevOps webhooks

Each was checked against the deliveries its provider documents, and GitHub's and Gitea's also against real deliveries, JSON and form-encoded: GitHub's from repository webhooks on github.com, Gitea's from a Gitea 1.27.3 instance. `tests/Webhook/WebhookProvidersTest.php` replays them all.

- GitHub and Gitea answer as the others do: `202 queued`. 3.x answered `200 OK` and then built while the connection closed. Their deliveries now pass through `WebhookReceivedEvent` and `RepositoryAddingEvent` like the rest.
- GitHub's `ping`, sent when a webhook is created, is answered `200 skipped: ping` without looking for a repository. An organisation's webhook names none, so 3.x answered its ping 400.
- GitHub deliveries sent as `application/x-www-form-urlencoded` are accepted. 3.x accepted only JSON.
- `github.source_urls` and `gitea.source_urls` now default to `clone_url`, `ssh_url`, `git_url`, `svn_url`, `html_url`. `html_url` matches a `satis.json` URL written without `.git`, and auto-add takes the first URL a delivery has, which is now the HTTPS clone URL rather than the `git://` one GitHub stopped serving in 2022. A `parameters.yml` that sets either list keeps its own.
- Gitea's `X-Gitea-Signature` is checked over the body as sent; 3.x trimmed it first. A form-encoded delivery is checked over its `payload` field, because that is what Gitea signs; 3.x and earlier 4.x builds refused every form-encoded Gitea delivery when a secret was set. A secret in the payload, which Gogs and older Gitea releases send, is still accepted. Gitea's Gogs-type webhooks send the same signature headers and are checked the same way.
- Bitbucket Cloud: `BITBUCKET_SECRET` (parameter `bitbucket.secret`) is new. With it set, the delivery's `X-Hub-Signature` is checked and its address is not. Without it, the address must be one of Bitbucket's, as in 3.x, from a list updated to Atlassian's published ranges of 19 October 2026, IPv6 included; 3.x's list lacked ranges Bitbucket sends from. Behind a reverse proxy the address is the proxy's unless `TRUSTED_PROXIES` names it, so set the secret.
- Bitbucket repositories are found by HTTPS URLs with a user name or without `.git`, by `ssh://git@bitbucket.org/...` URLs, and regardless of case. The `ssh://hg@` pattern is gone; Bitbucket dropped Mercurial in 2020.
- Azure DevOps: the repository is found by `resource.repository.remoteUrl`, the clone URL. 3.x used `resource.repository.url`, which in Azure DevOps' deliveries is the REST API URL, so no delivery Azure DevOps sends matched a repository. An SSH URL, `git@ssh.dev.azure.com:v3/<organisation>/<project>/<repository>`, matches too. The service hook's "Resource details to send" must stay at its default, All; Minimal and None leave out the repository.
- Every signature and token is compared in constant time.
- `WebhookReceivedEvent::getCreatedTag()` reads Bitbucket's `push.changes` and Azure DevOps' `resource.refUpdates`, as well as the `ref` that GitLab, GitHub and Gitea send.
- For code that extends Satisfy: `GithubWebhook::getResponse()` and `GithubWebhook::createPsr7Request()` are gone, and `AbstractWebhook::verifySignature()` checks an HMAC-SHA256 header. `swop/github-webhook`, `symfony/psr-http-message-bridge` and `laminas/laminas-diactoros` are no longer dependencies.

## The admin UI's build page

- Opening the page no longer starts a build. Its button queues a full build, and the page shows the queue and the last build's log.
- The streaming endpoint `/admin/satis/buildRun` is gone.

## For code that extends Satisfy

- `AbstractWebhook::handle()` returns the dispatched `BuildEvent` instead of an exit code.
- `SatisBuildRunner::run()` and `SatisBuildRunner::onBuild()` are gone. `SatisBuildRunner::build(BuildRequest)` runs one build and returns a `BuildResult`. Only the worker calls it.
- `BuildEvent` is now handled by `BuildQueue::onBuild()`, which queues the build and marks the event queued.
- `Playbloom\Http\ProcessResponse` is gone.
- New events: `BuildStartingEvent`, which a listener can cancel, and `BuildFinishedEvent`, with Satis's exit code.
