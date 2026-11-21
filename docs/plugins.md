# Plugins

A plugin is a Symfony bundle that attaches to Satisfy's events. Enable plugins by naming them in `SATISFY_PLUGINS`, comma-separated:

```
SATISFY_PLUGINS=authz,hookgate
```

A name is one of the plugins in this repository, or the class of a bundle installed with Composer. Each set of plugins gets its own compiled container, so changing the list takes effect on the next start without clearing the cache.

## Writing one

The plugins in this repository live in `plugins/<Name>/`, extend `Playbloom\Plugin\AbstractPlugin`, and are laid out as:

| Path | What it holds |
|---|---|
| `src/` | The bundle class and the plugin's code, namespace `Playbloom\Plugin\<Name>` |
| `config/services.yaml` | Loaded only while the plugin is enabled |
| `config/routes.yaml` | Route imports, such as another bundle's route loader, loaded only while it is enabled |
| `src/Controller/` | Controllers whose route attributes are loaded only while it is enabled |
| `templates/` | Twig templates, as `@<Name>/...` |
| `README.md` | What it does, its configuration, and anything it stores |

A service implementing `Playbloom\Plugin\AdminLinkInterface` adds links to the admin navigation. One implementing `Playbloom\Plugin\AdminPanelInterface` adds a panel to the admin home page's side column, from a template and its context; `authz` uses it to list the sites asking for packages.

A plugin that needs another bundle, such as one it configures in `prependExtension()`, names it in `requiredBundles()`, and that bundle is enabled ahead of it. Symfony takes security firewalls from one configuration file only, which is Satisfy's, so a plugin cannot add a firewall; `mcp` checks its token in a `kernel.request` listener instead.

### Events

| Event | Dispatched | A listener can |
|---|---|---|
| `WebhookReceivedEvent` | For every webhook delivery that passed its secret check, before the repository is looked up | `skip()` it (200) or `accept()` it (202) and do the work itself, for example on `kernel.terminate` once the response has gone |
| `RepositoryAddingEvent` | Before a webhook auto-adds a repository it does not know, which it does only for a delivery checked against its secret | `refuse()` it; the delivery is answered 200 and nothing is written |
| `BuildEvent` | A webhook or the admin UI asks for a build | Stop it before the queue sees it |
| `BuildStartingEvent` | The worker is about to run Satis | `cancel()` the build |
| `BuildFinishedEvent` | Satis has exited | Read the exit code |

A webhook whose secret is not set passes every delivery, so anyone who can reach it can send one. `isAuthenticated()` on both webhook events says whether the delivery was checked against a secret; a listener that writes `satis.json` or sends a credential somewhere the delivery names should do nothing when it is false.

In the image with `authz` enabled, a request for anything but `/admin`, `/login`, `/webhook/` and the admin UI's assets passes the catalogue's check before it reaches PHP, so a plugin's routes belong under `/admin`.

To change `satis.json`, call `Manager::modify()`, which applies a change to the file as it is once the lock is held, or edit the configuration and call `Manager::flush()` with the version it started from.

## The plugins in this repository

| Plugin | What it does |
|---|---|
| [`authz`](../plugins/Authz/README.md) | Per-token access to the catalogue with per-package grants, through nginx's `auth_request`; an approval queue for packages a site asks for and is not granted |
| [`diagnosis`](../plugins/Diagnosis/README.md) | When a build fails, asks a model why through Symfony AI's agent, with the log redacted and without `COMPOSER_AUTH`, and keeps the answer; with `mcp`, the newest answer is the resource `satisfy://build/diagnosis` |
| [`hookgate`](../plugins/Hookgate/README.md) | For GitLab system hooks: a registration gate that lets the webhook auto-add only real Composer packages, and auto-require, which publishes a package when a tag is pushed to it |
| [`mcp`](../plugins/Mcp/README.md) | An MCP server at `/admin/mcp` for agents such as Claude Code: read tools over `satis.json`, the catalogue and the builds, and, for a token issued with write scope, tools that change `satis.json` and queue builds. Tokens are managed at `/admin/mcp-tokens` and with `bin/console satisfy:mcp-token` |

Each README has the plugin's configuration, what it stores and logs, and, for `authz` and `hookgate`, how to move to it from the standalone service it replaces.
