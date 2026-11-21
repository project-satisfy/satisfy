# diagnosis

When a build fails, asks a model why, and keeps the answer. Enable it with `SATISFY_PLUGINS=diagnosis`, and give it a model.

It uses Symfony AI's agent (`symfony/ai-agent`) and its generic bridge for OpenAI-compatible endpoints (`symfony/ai-generic-platform`), so any provider with a `/v1/chat/completions` endpoint works: Hugging Face's router, OpenRouter, a local Ollama. Both packages are betas and outside Symfony's backward-compatibility promise.

## What happens when a build fails

1. A listener on `BuildFinishedEvent`, which fires only for a build that ran and exited with a code other than 0, keeps a copy of the build's log with credentials redacted, before the next build replaces it.
2. It starts `bin/console satisfy:diagnose <id>` in a process of its own, without `COMPOSER_AUTH`, and returns. The worker holds the build lock while the event is dispatched, and a model can take a minute to answer.
3. The command refuses to run where `COMPOSER_AUTH` is set. It gives the agent the end of the redacted log, and three tools: `log_search` and `log_lines` over the whole redacted log, and `satis_repositories`, the repositories and require list in `satis.json` with credentials redacted and without its `config` section, optionally only the repositories whose redacted URL contains a given text. Every tool takes an argument, because some endpoints, Hugging Face's router among them, refuse a tool whose schema has no parameters. The model gets nothing else.
4. The answer, or the error, is kept with the build, and as the newest diagnosis.

Satisfy runs Satis with `--skip-errors`, so a repository that cannot be cloned is skipped and does not fail the build; such a build is not diagnosed.

## Redaction

`LogRedactor` replaces user information in URLs (`https://token@host/`, `https://user:password@host/`), GitHub and GitLab tokens by their prefixes, `Authorization`, `PRIVATE-TOKEN` and similar headers, `COMPOSER_AUTH=` on a command line, and the credential sections of a `COMPOSER_AUTH` or `auth.json` (`http-basic`, `github-oauth`, `gitlab-token`, `bearer` and the like) with `***`. Package names, hosts and paths are sent as they are.

## Reading the diagnosis

- With the `mcp` plugin enabled too, the newest diagnosis is its server's resource `satisfy://build/diagnosis`, JSON: the build (id, exit code, what it built, when it finished), `status` (`done` or `failed`), the `diagnosis`, any `error`, the `model` and how long it took. It reads `{"status": "none"}` until a build has failed.
- On disk, in `SATISFY_DIAGNOSIS_DIR`.

## Configuration

| Variable | Meaning |
|---|---|
| `SATISFY_DIAGNOSIS_URL` | The endpoint's base URL, without `/v1/chat/completions`: `https://router.huggingface.co`, `https://openrouter.ai/api`, `http://ollama:11434`. Without it, nothing is diagnosed |
| `SATISFY_DIAGNOSIS_MODEL` | The model's name at that endpoint. Without it, nothing is diagnosed |
| `SATISFY_DIAGNOSIS_API_KEY` | Sent as a bearer token, if the endpoint needs one |
| `SATISFY_DIAGNOSIS_DIR` | Where diagnoses are kept. Default `var/diagnosis` |

The model must support tool calls.

## Data

| Path, in `SATISFY_DIAGNOSIS_DIR` | What it holds |
|---|---|
| `<id>/build.json` | The build: id, exit code, scope, repositories, when it finished |
| `<id>/build.log` | Its log, redacted, at most the last 256 KB |
| `<id>/diagnosis.json` | The outcome |
| `<id>/diagnose.log` | The output of `satisfy:diagnose` |
| `last.json` | The newest outcome |

The 20 newest builds are kept.
