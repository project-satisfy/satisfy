# authz

Per-token access to the catalogue, with per-package grants. Enable it with `SATISFY_PLUGINS=authz`.

Each principal, such as a site or a developer's laptop, holds a token, which Composer sends as HTTP Basic credentials: the principal's name as the user, the token as the password. Two checks follow:

- **The credential.** While enforcement is on, every catalogue request needs a valid token. While it is off, requests are allowed and recorded, so the consumers are known before anything is refused.
- **The package.** A principal under package enforcement may fetch only the packages it is granted and everything they require, and gets 403 for the rest. A grant of `vendor/name` or `vendor/*` implies the granted packages' requirements, computed from the catalogue's `p2/` metadata, so nobody keeps a list of dependencies. A refused package is recorded for approval. In observe mode the request is allowed and recorded all the same.

Whole-catalogue documents (`/`, `/index.html`, `/packages.json`, `/include/`, and Composer 1's `/p/provider-*.json`) need only the credential. A package is read from the path nginx serves the file from, so `/p2/x/../vendor/name.json` and `//p2/vendor/name.json` are checked as `vendor/name`; a path that cannot be read for certain, with a `#`, an encoded `?`, a backslash or `..` above the root, is refused while enforcement is on.

## How it attaches

nginx asks `public/verify.php` about every catalogue request through `auth_request`. That endpoint runs without Symfony's kernel, because Composer calls it for every file of every update, and caches its answers in APCu for 60 seconds and the dependency graph for 15 minutes. Tokens are hashed with SHA-256 and `TOKEN_PEPPER` and compared in constant time.

In the Docker image the entrypoint wires it up whenever the plugin is enabled: `auth_request` on everything nginx serves except the admin UI, its assets, `/login` and the webhooks, the endpoints below, and an access log that gives each request's principal. Everything includes whatever Satis writes besides `packages.json`, `p2/`, `include/`, `dist/` and the HTML catalogue, such as `p/` with `providers` on, or an archive directory of another name. Outside the image, take the nginx configuration from `docker/nginx/authz-server.conf`, `authz-catalogue.conf` and `authz-http.conf`, and put `authz-catalogue.conf` in every location that serves the output directory, as `docker/nginx.conf` does. nginx has to pass the `Authorization` header, the original URI and the client's address to `verify.php` explicitly, and php-fpm needs APCu.

| Path | What it is |
|---|---|
| `/_authzcheck` | A canary that always enforces, whatever the global switch says. Poll it with a valid token |
| `/_authzpurge` | `POST` with `X-Authz-Admin-Key: <ADMIN_KEY>` to clear the cached credentials and grants at once. The dependency graph and the audit summary are kept, and so is anything else in APCu |
| `/admin/authz` | Principals, tokens, grants and the approval queue, behind Satisfy's admin login |

Turn on `admin.auth` in `parameters.yml` when this plugin is enabled: `/admin/authz` issues tokens.

## Configuration

| Variable | Meaning |
|---|---|
| `TOKEN_PEPPER` | Required. Mixed into every token hash; changing it invalidates every token |
| `ADMIN_KEY` | Guards `/_authzpurge` |
| `AUTHZ_DATA_DIR` | The database, the audit log and the enforcement switch. Default `var/authz`; `/var/www/authz` in the image, which is a volume |
| `AUTHZ_CATALOGUE_DIR` | Where the catalogue's `p2/` is. Default `public/` |
| `AUTHZ_REALM` | The realm of the Basic challenge, which helps tell this login from the admin one. Default `Composer repository` |
| `ENFORCE` | `true` refuses requests without a valid token when the switch file has never been set |
| `TRUSTED_PROXIES` | Satisfy's own setting. Behind a proxy, set it so that the logs give the client's address rather than the proxy's |

## Data

`AUTHZ_DATA_DIR` holds the only state Satisfy cannot rebuild. Back it up: losing it means issuing every token again.

| File | What it holds |
|---|---|
| `authz.db` | SQLite: principals and their token hashes, grants, and the approval queue. The migrations in `migrations/` are applied on first use, once each |
| `audit.jsonl` | One JSON line per decision: principal, URI, client address, package and outcome. Successful canary probes are left out |
| `enforce` | `true` or `false`: the global switch, which `authz enforce on\|off` writes. It wins over `ENFORCE` |

## Command line

`bin/console authz <action>`, run as the web server's user so the files it creates stay correctly owned. In the image: `docker exec -u www-data <container> bin/console authz list`. Changes made here reach the web server within 60 seconds, or at once after a purge; changes made in the admin UI take effect at once.

| Group | Actions |
|---|---|
| Principals | `init`, `issue <site> [note]`, `reissue <site>`, `revoke <site>`, `restore <site>`, `forget <site>`, `list`, `note <site> <text>` |
| Credential enforcement | `enforce status\|on\|off` |
| Package grants | `grant <site> <pkg>...`, `ungrant <site> <pkg>...`, `entitlements <site>`, `pkg-enforce <site> on\|off` |
| Approval queue | `requests [site]`, `approve <site> <pkg>...`, `dismiss <site> <pkg>...` |
| Diagnostics | `check <site> <token>`, `packages`, `deps <pkg>`, `audit [n]`, `unauth [n]`, `stats`, `selftest` |

`issue` prints the token once, as `COMPOSER_AUTH` and as an `auth.json`, for the host in `satis.json`'s `homepage`.

A typical start: issue a token to each consumer with enforcement off, watch `unauth` until it reaches zero, then `enforce on`. Per principal, grant what it uses, watch `requests`, then `pkg-enforce <site> on`.

## Coming from the standalone authz service

- The database schema and file names are the same. Mount the old data directory at `AUTHZ_DATA_DIR`, owned by the web server's user, and keep `TOKEN_PEPPER` and `ADMIN_KEY`: every token keeps working.
- `authzctl <args>` becomes `bin/console authz <args>`, with the same actions and output.
- The web UI moves from `/_authzui`, with its own Basic login, to `/admin/authz`, with Satisfy's.
- The standalone service's synchronisation with an external portal, which was never switched on, is not ported.

## Layout

| Path | What it holds |
|---|---|
| `src/Verifier.php` | The decision for one request, shared by `verify.php` and the tests |
| `src/Token.php`, `src/Entitlement.php`, `src/Deps.php` | Credentials, grants, and the dependency graph from `p2/` |
| `src/Database.php`, `src/Sites.php`, `src/Audit.php`, `src/Cache.php` | Storage, the audit log and the APCu cache |
| `src/Command/`, `src/Controller/`, `templates/` | `bin/console authz` and `/admin/authz` |
| `public/verify.php`, `public/purge.php` | The two endpoints nginx calls, outside the kernel |
| `migrations/` | The database schema |

Tests are in `tests/Plugin/Authz/`. `docs/plugins.md` describes plugins in general.
