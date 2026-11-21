# mcp

An MCP server at `/admin/mcp`, for agents such as Claude Code: it reads `satis.json`, the catalogue and the builds, and, with a token issued for it, changes `satis.json` and queues builds. Enable it with `SATISFY_PLUGINS=mcp`.

It is served by Symfony's MCP bundle (`symfony/mcp-bundle`, on the official PHP MCP SDK) over Streamable HTTP, and the plugin enables that bundle with it. The bundle is a beta and outside Symfony's backward-compatibility promise.

## Tokens

Every request carries a token as `Authorization: Bearer <token>`. A token has a name, such as the client it is for, and a scope:

| Scope | What it may do |
|---|---|
| `read` | Call the read tools |
| `write` | Call every tool, the ones that change `satis.json` and queue builds too |

Tokens are issued and revoked at `/admin/mcp-tokens`, behind the admin sign-in, or with the command line, which keeps the same tokens:

```
bin/console satisfy:mcp-token issue laptop-claude --write
bin/console satisfy:mcp-token list
bin/console satisfy:mcp-token revoke laptop-claude
```

A token is shown once when it is issued; only its SHA-256 hash is kept, in `tokens.json` under `SATISFY_MCP_DATA_DIR`. Issuing under a name that has a token replaces it. Run the command as the web server's user (`docker exec -u www-data …` in the image), so that the web server can still write the file. While no token is issued, every request is refused.

Issuing tokens, granting packages and approving requests in `authz` stay in the admin UI: no tool does them.

## Tools

| Tool | Scope | What it does |
|---|---|---|
| `repositories` | read | The repositories in `satis.json`, each with its id, type and URL |
| `requirements` | read | The require list, with constraints, and whether `require-all` is on |
| `packages` | read | The packages the last build published, with their versions |
| `package` | read | One package's versions: requirements, release time, source and archive |
| `build_status` | read | Whether a build is running or queued; the last build's exit code, the repositories it skipped, and the end of its log. `waitSeconds`, at most 30, waits for the queue to empty first |
| `queue_build` | write | Queues a full build, or one repository's, through `BuildEvent`, so a plugin can stop it |
| `add_repository` | write | Adds a repository, through `RepositoryAddingEvent`, so `hookgate` and other plugins can refuse it |
| `require_package` | write | Adds a package to the require list, or changes its constraint, checked as Composer reads one |
| `remove_requirement` | write | Removes a package from the require list |
| `remove_repository` | write | Removes a repository |
| `update_repository` | write | Changes a repository's URL or type |

With the `diagnosis` plugin enabled too, the server also has the tool `last_diagnosis` and the resource `satisfy://build/diagnosis`, the newest failed build's diagnosis.

Credentials are replaced with `***` in every URL and log a tool returns. A tool that takes a URL refuses one carrying a credential, which would end up in `satis.json`: give Composer the credential in `COMPOSER_AUTH` or `auth.json` instead. A URL can be given as `satis.json` has it or as the tools show it.

Every change goes through `Manager::modify()` or the methods built on it, under the lock on `satis.json`, as the admin UI's changes do, and is recorded in `audit.jsonl`, under `SATISFY_MCP_DATA_DIR`, with the time, the token's name, the tool and what changed.

### Removals

`remove_requirement` and `remove_repository`, and `update_repository` when it changes a URL, stop the next full build publishing packages. A site can still install those from its lock file while their archives stay in `dist/`, but `composer update` no longer finds them. So these tools refuse, unless the call sets `force` to true:

- with the `authz` plugin enabled, when its audit log shows a site fetched one of the packages in the last 30 days, and names the sites;
- without `authz`, always, since nothing tells who fetches what.

## Configuration

| Variable | Meaning |
|---|---|
| `SATISFY_MCP_DATA_DIR` | Where the tokens are kept. Default `var/mcp` |
| `SATISFY_MCP_ALLOWED_HOSTS` | Host names, comma-separated, that requests may be addressed to besides `localhost`, such as `packages.example.com`. The bundle refuses others, against DNS rebinding. Read when the container is compiled, which the image does on every start |

The token is checked by a `kernel.request` listener before Satisfy's firewall, whatever `admin.auth` says, because the admin sign-in is a form that an MCP client cannot use.

The endpoint is under `/admin`, so with `authz` enabled nginx passes it to PHP without the catalogue's check.

## Connecting Claude Code

```
claude mcp add --transport http satisfy https://packages.example.com/admin/mcp --header "Authorization: Bearer <token>"
```

A session started after that has the tools. Claude Code asks before it calls each one, unless told not to.

## Data

| Path, in `SATISFY_MCP_DATA_DIR` | What it holds |
|---|---|
| `tokens.json` | Each token's name, scope, hash, and when it was issued and last used |
| `tokens.lock` | Taken while `tokens.json` is written |
| `audit.jsonl` | One JSON line for each change a tool made: time, token, tool, and what changed |

The MCP sessions are the bundle's, files under the kernel's cache directory.
