# Demo: Claude Code running a Satisfy through MCP

Satisfy with the [`mcp`](../plugins/Mcp/README.md) and [`diagnosis`](../plugins/Diagnosis/README.md) plugins enabled, and a `satis.json` with a mistake in it. From Claude Code, or any MCP client, you ask Satisfy what it publishes, build it, find out why the build failed from the diagnosis a Symfony AI agent inside Satisfy wrote, fix `satis.json`, build again, and add a package: all through Satisfy's MCP server.

## What you need

- Docker with Compose v2.
- Claude Code, or another MCP client such as the MCP Inspector (`npx @modelcontextprotocol/inspector`, which needs Node.js).
- A model that can call tools, behind an OpenAI-compatible endpoint, for the diagnosis. A Hugging Face token works with the settings in `.env.example`: create a fine-grained token at huggingface.co, under Settings, Access Tokens, with "Make calls to Inference Providers" ticked. An Ollama on the same machine should work too, with no key, but has not been tried.

## Run it

1. Clone the branch and configure the demo:

   ```bash
   git clone --branch 4.x https://github.com/FivePaths/satisfy.git
   cd satisfy/demo
   cp .env.example .env
   ```

   In `.env`, set `SATISFY_DIAGNOSIS_API_KEY` to your Hugging Face token, or switch the model settings to Ollama.

2. Start Satisfy:

   ```bash
   docker compose up -d
   ```

   This pulls `ghcr.io/fivepaths/satisfy:4.x`. `docker compose up -d --build` builds the same image from your checkout instead, which takes a few minutes.

3. Issue an MCP token that may change Satisfy. It is printed once:

   ```bash
   docker compose exec -u www-data satisfy bin/console satisfy:mcp-token issue demo --write
   ```

   http://localhost:8000/admin/mcp-tokens issues and revokes the same tokens.

4. Connect Claude Code:

   ```bash
   claude mcp add --transport http satisfy-demo http://localhost:8000/admin/mcp --header "Authorization: Bearer <the token>"
   ```

   With the MCP Inspector instead: the Streamable HTTP transport, `http://localhost:8000/admin/mcp`, and the same `Authorization` header.

5. In a new Claude Code session, ask, in your own words:

   1. What Satisfy publishes and what its `satis.json` asks for. It answers with `repositories`, `requirements` and `packages`: nothing is published yet.
   2. To build it and say how it went. It calls `queue_build`, then `build_status` waiting for the build: it failed.
   3. Why it failed. It reads `last_diagnosis`, which the Symfony AI agent inside Satisfy wrote a few seconds after the build failed: a version constraint Satis cannot parse.
   4. To fix that and build again. It calls `require_package` with a corrected constraint and builds: the build succeeds, and `packages` lists `psr/container` and `psr/log`.
   5. To also publish `psr/http-message` from `https://github.com/php-fig/http-message.git`, as a `git` repository like the other two. It calls `add_repository`, `require_package` and `queue_build`, and `package` shows its versions. As `vcs`, Composer would read it through GitHub's API, which allows 60 requests an hour without a token, and a few builds use that up.

   Claude Code asks before it calls each tool; a tool that changes `satis.json` says so in its description.

6. Look at the catalogue at http://localhost:8000.

7. Stop it, and remove its data, `satis.json` and the token among it:

   ```bash
   docker compose down -v
   ```

## What is in it

| File | What it holds |
|---|---|
| `compose.yaml` | Satisfy with the two plugins, and a one-off service that copies `config/satis.json` into a volume Satisfy can write |
| `config/satis.json` | Two public repositories from PHP-FIG, `psr/container` and `psr/log`, and a requirement Satis cannot parse |
| `.env.example` | The port and the model settings, for Hugging Face, Ollama or OpenRouter |

Nothing here needs credentials for the repositories: they are public, and Satis clones them over HTTPS.

## What to look at

- Two agents. Claude Code works Satisfy through the MCP server, which runs inside Satisfy from Symfony's MCP bundle. When a build fails, a Symfony AI agent inside Satisfy reads the redacted build log with three tools of its own and writes the diagnosis, in a process that has no Composer credentials.
- The token decides what a client may do: a read token can only look. Every change goes through the same lock on `satis.json` as the admin UI's, and is recorded with the token's name; `docker compose exec satisfy cat var/mcp/audit.jsonl` shows them.
- A removal that would stop publishing a package sites may still use is refused unless the call says `force`; ask Claude to remove `psr/http-message` again to see it ask you.
