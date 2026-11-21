#!/bin/bash
#
# Starts the image with an empty catalogue and checks what each part does:
# nginx serves the catalogue as files, PHP serves the admin UI and webhooks,
# the worker builds what is queued, and no process serving HTTP holds
# COMPOSER_AUTH. Then the cases a 3.x deployment brings with it: a public/
# volume an older image filled, a mounted parameters.yml, the web server and
# the worker in separate containers, and both starting at once on one volume.
#
# Usage: docker/smoke-test.sh <image>

set -euo pipefail

image="${1:?usage: docker/smoke-test.sh <image>}"
port="${SMOKE_PORT:-8089}"
prefix="satisfy-smoke-$$"
dir="$(mktemp -d)"
base="http://127.0.0.1:$port"
name="$prefix"
containers=()
volumes=()

cleanup() {
  for c in "${containers[@]}"; do
    docker rm -f "$c" >/dev/null 2>&1 || true
  done
  for v in "${volumes[@]}"; do
    docker volume rm -f "$v" >/dev/null 2>&1 || true
  done
  rm -rf "$dir"
}
trap cleanup EXIT

fail() {
  echo "FAIL: $*" >&2
  docker logs "$name" 2>&1 | grep -v 'HTTP/1.1"' | tail -20 >&2 || true
  docker exec "$name" tail -20 var/log/build.log >&2 2>/dev/null || true
  exit 1
}

# Starts a container named $1 with the remaining arguments to docker run.
start() {
  name="$1"
  shift
  containers+=("$name")
  docker run -d --name "$name" "$@" >/dev/null
}

volume() {
  volumes+=("$1")
  docker volume create "$1" >/dev/null
}

status_of() {
  curl -s -o /dev/null -w '%{http_code}' -X "${2:-GET}" "$base$1"
}

expect() {
  local want="$1" path="$2" method="${3:-GET}" got
  got="$(status_of "$path" "$method")"
  [ "$got" = "$want" ] || fail "$method $path answered $got, expected $want"
  echo "ok   $method $path -> $got"
}

wait_for() {
  local want="$1" path="$2"
  for _ in $(seq 1 60); do
    [ "$(status_of "$path")" = "$want" ] && return 0
    sleep 1
  done
}

# Queues a full build the way the web role does, through the container $1.
queue_full_build() {
  docker exec -u www-data "$1" sh -c 'printf "{\"repository\":null}" > "$SATISFY_QUEUE_DIR/full.json"'
}

# Waits for a line matching $1 in the current container's access log.
logged() {
  for _ in $(seq 1 10); do
    grep -qE "$1" <<< "$(docker logs "$name" 2>/dev/null)" && return 0
    sleep 1
  done
  return 1
}

# Prints the environment of each process in the current container whose
# command line starts with $1, read as the user it runs as, since a
# container's root cannot read another user's. "--" starts each one.
environ_of() {
  local user
  for user in root www-data; do
    docker exec -u "$user" "$name" sh -c '
      for d in /proc/[0-9]*; do
        case "$(tr "\0" " " < "$d/cmdline" 2>/dev/null)" in
          "$1"*)
            if env=$(tr "\0" "\n" 2>/dev/null < "$d/environ"); then
              echo "--"
              echo "$env"
            fi
            ;;
        esac
      done' sh "$1"
  done
}

expect_environ() {
  local process="$1" want="$2" environ
  environ="$(environ_of "$process")"
  grep -q '^--$' <<< "$environ" || fail "could not read the environment of $process"
  if grep -q '^COMPOSER_AUTH=' <<< "$environ"; then
    [ "$want" = with ] || fail "$process holds COMPOSER_AUTH"
  else
    [ "$want" = without ] || fail "$process does not have COMPOSER_AUTH"
  fi
}

running() {
  grep -q "^$1" <<< "$(docker exec "$name" sh -c 'for d in /proc/[0-9]*; do tr "\0" " " < "$d/cmdline" 2>/dev/null; echo; done')"
}

cat > "$dir/satis.json" <<'EOF'
{
    "name": "smoke/test",
    "homepage": "http://localhost",
    "repositories": [],
    "require-all": true
}
EOF
chmod 666 "$dir/satis.json"
auth='{"github-oauth":{"github.com":"smoke_token"}}'

echo "-- one container, every role"
start "$prefix" -p "127.0.0.1:$port:80" \
  -v "$dir/satis.json:/var/www/html/satis.json" \
  -e GITLAB_SECRET=smoke -e COMPOSER_AUTH="$auth" "$image"
wait_for 200 /admin
expect 200 /admin
expect 200 /
expect 404 /packages.json
expect 404 /index.php
expect 400 /webhook/gitlab POST
# Without TRUSTED_PROXIES, X-Forwarded-For is nobody's word.
curl -s -o /dev/null -H 'X-Forwarded-For: 198.51.100.9' "$base/"
logged '"GET / HTTP' || fail "the request was not logged"
grep -qE '^198\.51\.100\.9 ' <<< "$(docker logs "$name" 2>/dev/null)" && fail "X-Forwarded-For was believed without TRUSTED_PROXIES"
echo "ok   X-Forwarded-For is ignored without TRUSTED_PROXIES"
# Without a secret, Bitbucket's webhook takes deliveries only from Atlassian's addresses.
expect 400 /webhook/bitbucket POST
got="$(curl -s -X POST -H 'X-GitHub-Event: ping' -H 'Content-Type: application/json' \
  -d '{"zen":"smoke","hook_id":1}' "$base/webhook/github")"
[ "$got" = 'skipped: ping' ] || fail "the GitHub webhook answered a ping with: $got"
echo "ok   POST /webhook/github ping -> skipped"

queue_full_build "$name"
wait_for 200 /packages.json
expect 200 /packages.json
# Satis writes the HTML catalogue after packages.json.
# Output is captured before grep -q: with pipefail, grep stopping early
# makes curl fail on the rest of a large page.
for _ in $(seq 1 30); do
  grep -q 'smoke/test' <<< "$(curl -s "$base/")" && break
  sleep 1
done
grep -q 'smoke/test' <<< "$(curl -s "$base/")" || fail "/ is not the built catalogue"
echo "ok   the worker built the queued catalogue"

expect_environ 'php-fpm' without
expect_environ 'nginx' without
expect_environ 'php /var/www/html/bin/console satisfy:worker' with
echo "ok   COMPOSER_AUTH reaches the worker only"

started=$(date +%s)
docker stop "$name" >/dev/null
[ $(( $(date +%s) - started )) -lt 10 ] || fail "the container took 10 seconds or more to stop"
echo "ok   the container stops on SIGTERM"

docker run --rm -v "$dir/satis.json:/var/www/html/satis.json" "$image" rebuild >/dev/null \
  || fail "the rebuild role failed"
echo "ok   the rebuild role builds and exits"

echo "-- a public/ volume an older image filled, and a mounted parameters.yml"
volume "$prefix-public"
docker run --rm -v "$prefix-public:/p" --entrypoint sh "$image" -c '
  printf "<?php exit(1);\n" > /p/index.php
  printf "{\"packages\":[],\"smoke\":true}" > /p/packages.json
  chown -R www-data:www-data /p'
cat > "$dir/parameters.yml" <<'EOF'
parameters:
    admin.auth: true
    admin.users:
        admin: { password: smoke, roles: ROLE_ADMIN }
    gitlab.auto_add_repo: true
    gitlab.auto_add_repo_type: vcs
EOF
cp "$dir/satis.json" "$dir/satis-upgrade.json"
chmod 666 "$dir/satis-upgrade.json"
start "$prefix-upgrade" -p "127.0.0.1:$port:80" \
  -v "$dir/satis-upgrade.json:/var/www/html/satis.json" \
  -v "$dir/parameters.yml:/var/www/html/config/parameters.yml:ro" \
  -v "$prefix-public:/var/www/html/public" \
  -e GITLAB_SECRET=smoke -e TRUSTED_PROXIES=private_ranges "$image"
wait_for 200 /login
expect 200 /login
expect 302 /admin

# Behind a proxy TRUSTED_PROXIES names, Symfony believes X-Forwarded-Proto and
# nginx logs the client X-Forwarded-For names.
redirect() { curl -s -o /dev/null -w '%{redirect_url}' "$@" "$base/admin"; }
# With a public client address (Symfony's private_ranges include the
# documentation ranges): PHP must still see the proxy's, or Symfony would not
# trust the request and would ignore X-Forwarded-Proto.
[[ "$(redirect -H 'X-Forwarded-Proto: https' -H 'X-Forwarded-For: 1.2.3.4')" == https://* ]] \
  || fail "X-Forwarded-Proto from a trusted proxy was ignored"
[[ "$(redirect)" == http://* ]] || fail "/admin redirected to https without X-Forwarded-Proto"
curl -s -o /dev/null -H 'X-Forwarded-For: 1.2.3.4' "$base/login"
logged '^1\.2\.3\.4 .*"GET /login' || fail "the client behind a trusted proxy was not logged"
echo "ok   behind a trusted proxy: https redirects, and the client's address in the log"
grep -q '"smoke":true' <<< "$(curl -s "$base/packages.json")" || fail "the catalogue in the volume was not kept"
echo "ok   the old front controller was replaced and the catalogue kept"

# A webhook auto-adds a repository: satis.json, mounted as a single file, has
# to be written in place, or the container and the host part company.
inode() { python3 -c 'import os, sys; print(os.stat(sys.argv[1]).st_ino)' "$1"; }
before="$(inode "$dir/satis-upgrade.json")"
got="$(curl -s -o /dev/null -w '%{http_code}' -X POST -H 'X-Gitlab-Token: smoke' \
  -H 'Content-Type: application/json' \
  -d '{"object_kind":"push","project":{"git_http_url":"https://git.example.com/smoke/added.git"}}' \
  "$base/webhook/gitlab")"
[ "$got" = 202 ] || fail "the GitLab webhook answered $got, expected 202"
grep -q 'smoke\\\?/added.git' "$dir/satis-upgrade.json" || fail "the webhook did not add the repository to satis.json"
[ "$(inode "$dir/satis-upgrade.json")" = "$before" ] || fail "satis.json was replaced rather than written in place"
echo "ok   a webhook added a repository to the mounted satis.json in place"
docker rm -f "$name" >/dev/null

echo "-- the web server and the worker in separate containers"
volume "$prefix-output"
volume "$prefix-queue"
shared=(-v "$dir/satis.json:/var/www/html/satis.json"
  -v "$prefix-output:/var/www/html/public" -v "$prefix-queue:/var/www/queue")
start "$prefix-worker" "${shared[@]}" -e COMPOSER_AUTH="$auth" "$image" worker
running 'nginx' && fail "the worker container runs nginx"
start "$prefix-web" -p "127.0.0.1:$port:80" "${shared[@]}" "$image" web
wait_for 200 /admin
running 'php /var/www/html/bin/console satisfy:worker' && fail "the web container runs the worker"
queue_full_build "$name"
wait_for 200 /packages.json
expect 200 /packages.json
echo "ok   a build queued through the web container was built by the worker container"
docker rm -f "$prefix-web" "$prefix-worker" >/dev/null

echo "-- a web and a worker container started at once on one public/ volume an older image filled"
volume "$prefix-race"
docker run --rm -v "$prefix-race:/p" --entrypoint sh "$image" -c '
  printf "<?php exit(1);\n" > /p/index.php
  chown -R www-data:www-data /p'
race=(-v "$dir/satis.json:/var/www/html/satis.json" -v "$prefix-race:/var/www/html/public")
start "$prefix-race-worker" "${race[@]}" "$image" worker
start "$prefix-race-web" -p "127.0.0.1:$port:80" "${race[@]}" "$image" web
wait_for 200 /login
expect 200 /login
sleep 2
for c in "$prefix-race-worker" "$prefix-race-web"; do
  [ "$(docker inspect -f '{{.State.Running}}' "$c")" = true ] || { name="$c"; fail "$c stopped"; }
done
docker exec "$prefix-race-web" grep -q 'exit(1)' public/index.php && fail "the old front controller was kept"
echo "ok   only the web container put the image's files in the shared volume, and both kept running"
docker rm -f "$prefix-race-web" "$prefix-race-worker" >/dev/null

echo "-- the authz and hookgate plugins"
start "$prefix-plugins" -p "127.0.0.1:$port:80" \
  -v "$dir/satis.json:/var/www/html/satis.json" \
  -e SATISFY_PLUGINS=authz,hookgate -e TOKEN_PEPPER=smoke-pepper -e ADMIN_KEY=smoke-admin-key \
  -e AUTHZ_REALM='Smoke Packages' -e TRUSTED_PROXIES=private_ranges "$image"
wait_for 200 /admin
console() { docker exec -u www-data "$name" php bin/console "$@"; }
grep -q AutoRequire <<< "$(console debug:event-dispatcher 'Playbloom\Event\WebhookReceivedEvent')" \
  || fail "hookgate's auto-require is not listening"
queue_full_build "$name"
wait_for 200 /packages.json
expect 200 /packages.json

# Two packages in the catalogue; the site is granted one of them.
docker exec -u www-data "$name" sh -c '
  mkdir -p public/p2/smoke
  for p in app other; do
    printf "{\"packages\":{\"smoke/%s\":[{\"version\":\"1.0.0\"}]}}" "$p" > "public/p2/smoke/$p.json"
  done'
token="$(console authz issue smoke-site | sed -n 's/^  token  //p')"
[ -n "$token" ] || fail "authz issue printed no token"
console authz grant smoke-site smoke/app >/dev/null
console authz enforce on >/dev/null

expect 401 /packages.json
grep -qi 'WWW-Authenticate: Basic realm="Smoke Packages"' <<< "$(curl -s -D - -o /dev/null "$base/packages.json")" \
  || fail "no Basic challenge with the configured realm"
expect_as() {
  local want="$1" path="$2" got
  got="$(curl -s -o /dev/null -w '%{http_code}' -u "smoke-site:$token" "$base$path")"
  [ "$got" = "$want" ] || fail "GET $path with the site's token answered $got, expected $want"
  echo "ok   GET $path as smoke-site -> $got"
}
expect_as 200 /packages.json
expect_as 200 /p2/smoke/app.json
expect_as 403 /p2/smoke/other.json
expect 401 /_authzcheck
expect_as 200 /_authzcheck
expect 200 /admin
expect 200 /login
expect 200 /images/favicon.ico

# A package's path written another way is checked as the file nginx serves.
expect_as_sent() {
  local want="$1" path="$2" got
  got="$(curl -s --path-as-is -o /dev/null -w '%{http_code}' -u "smoke-site:$token" "$base$path")"
  [ "$got" = "$want" ] || fail "GET $path as sent, with the site's token, answered $got, expected $want"
  echo "ok   GET $path as sent, as smoke-site -> $got"
}
expect_as_sent 200 /p2/smoke/x/../app.json
expect_as_sent 403 /p2/smoke/x/../other.json
expect_as_sent 403 //p2/smoke/other.json
expect_as_sent 403 /_authzcheck/../p2/smoke/other.json

# Files Satis writes outside the usual layout are behind the check too.
docker exec -u www-data "$name" sh -c '
  mkdir -p public/p/smoke public/archives
  printf "{}" > "public/p/smoke/other\$0123abcd.json"
  printf "zip" > public/archives/smoke-other.zip'
expect 401 '/p/smoke/other$0123abcd.json'
expect_as 403 '/p/smoke/other$0123abcd.json'
expect 401 /archives/smoke-other.zip
expect_as 200 /archives/smoke-other.zip
grep -q 'smoke/other' <<< "$(console authz requests)" || fail "the refused package was not queued for approval"
curl -s -o /dev/null -u "smoke-site:$token" -H 'X-Forwarded-For: 1.2.3.4' "$base/p2/smoke/app.json"
logged '^1\.2\.3\.4 site=smoke-site ' || fail "the access log does not give the client and its site"
grep -q '"ip":"1.2.3.4"' <<< "$(console authz audit 200)" || fail "authz's audit log does not give the client's address"
echo "ok   authz: tokens, grants, the canary, the approval queue and the client's address"

echo "smoke test passed"
