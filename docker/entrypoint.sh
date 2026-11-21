#!/bin/bash
#
# The container's entrypoint. The first argument is the role:
#
#   all       nginx, php-fpm and the worker (the default)
#   web       nginx and php-fpm: the catalogue, the admin UI and the webhooks
#   worker    satisfy:worker, which runs the builds the web role queues
#   rebuild   satisfy:rebuild once, with any further arguments
#
# Anything else runs as given. In every role the processes that answer HTTP
# requests start without COMPOSER_AUTH: they queue builds and never run them.

set -euo pipefail

app="${APP_PATH:-/var/www/html}"
pids=()

# Prefix that runs a command as www-data. setpriv replaces itself with the
# command rather than wrapping it, so signals reach the command itself.
as_app=()
if [ "$(id -u)" -eq 0 ]; then
  as_app=(env HOME=/var/www setpriv --reuid=www-data --regid=www-data --init-groups)
fi

# prepare [serves]: "serves" for the roles that answer HTTP requests.
prepare() {
  # A volume mounted on public/ keeps whatever image first filled it. Put this
  # image's front controller and assets back; the catalogue's files are left alone.
  # Only the roles that serve them do it, so a worker sharing the volume does not
  # race the web role. Two web containers starting at once can still both create
  # one file, which makes cp fail; the second pass finds every file there.
  if [ "${1:-}" = serves ] && [ "${SATISFY_SYNC_PUBLIC:-1}" = 1 ]; then
    cp -a /usr/local/share/satisfy-public/. "$app/public/" 2>/dev/null \
      || cp -a /usr/local/share/satisfy-public/. "$app/public/"
  fi
  queue="${SATISFY_QUEUE_DIR:-$app/var/queue}"
  mkdir -p "$app/var" "$queue"
  if [ "$(id -u)" -eq 0 ]; then
    chown www-data:www-data "$app/var" "$queue"
  fi
  if plugin_enabled authz && [ -n "${AUTHZ_DATA_DIR:-}" ]; then
    mkdir -p "$AUTHZ_DATA_DIR"
    if [ "$(id -u)" -eq 0 ]; then
      chown www-data:www-data "$AUTHZ_DATA_DIR"
    fi
  fi
  configure_nginx

  # Compile the configuration on every start, so a changed parameters.yml or
  # environment takes effect without anyone running cache:clear.
  "${as_app[@]}" php "$app/bin/console" cache:clear --no-interaction
}

# Is a plugin named in SATISFY_PLUGINS?
plugin_enabled() {
  local list
  list=",$(printf '%s' "${SATISFY_PLUGINS:-}" | tr -d ' ' | tr '[:upper:]' '[:lower:]'),"
  [[ "$list" == *",$1,"* ]]
}

# The nginx includes for the enabled plugins, and the trusted proxies; empty
# files otherwise.
configure_nginx() {
  local dir=/etc/nginx/satisfy templates=/usr/local/share/satisfy-nginx realm server
  : > "$dir/http.conf"
  : > "$dir/server.conf"
  : > "$dir/catalogue.conf"
  trusted_proxies > "$dir/proxy.conf"
  if plugin_enabled authz; then
    cp "$templates/authz-http.conf" "$dir/http.conf"
    # The realm Composer and browsers show. Quotes, backslashes, "$" and line
    # breaks are dropped: inside nginx's quoted string they would end it, or
    # name a variable.
    realm="$(printf '%s' "${AUTHZ_REALM:-Composer repository}" | tr -d "\"'\\\\\$\r\n")"
    server="$(<"$templates/authz-server.conf")"
    printf '%s\n' "${server//@REALM@/$realm}" > "$dir/server.conf"
    cp "$templates/authz-catalogue.conf" "$dir/catalogue.conf"
  fi
}

# nginx's realip settings for TRUSTED_PROXIES, which Symfony reads too:
# addresses and CIDR ranges, private_ranges, or REMOTE_ADDR for whatever
# connects. Anything else is left out, with a warning.
trusted_proxies() {
  local entry ranges=() private=() recursive=on
  local IFS=,
  for entry in ${TRUSTED_PROXIES:-}; do
    entry="${entry// /}"
    case "$entry" in
      '') ;;
      private_ranges)
        # Symfony's own list, so that nginx and Symfony trust the same addresses.
        read -r -a private <<< "$(php -r 'require $argv[1]; echo implode(",", Symfony\Component\HttpFoundation\IpUtils::PRIVATE_SUBNETS);' "$app/vendor/autoload.php")"
        ranges+=("${private[@]}") ;;
      REMOTE_ADDR)
        # Only the proxy that connected is trusted, so only the address it
        # added counts: walking further left would believe the client.
        ranges+=(0.0.0.0/0 ::/0)
        recursive=off ;;
      *[!0-9A-Fa-f.:/]*)
        echo "TRUSTED_PROXIES: ignoring '$entry', which is not an address or range" >&2 ;;
      *)
        ranges+=("$entry") ;;
    esac
  done
  [ "${#ranges[@]}" -gt 0 ] || return 0
  printf 'set_real_ip_from %s;\n' "${ranges[@]}"
  printf 'real_ip_header X-Forwarded-For;\nreal_ip_recursive %s;\n' "$recursive"
}

start_web() {
  env -u COMPOSER_AUTH php-fpm &
  pids+=($!)
  env -u COMPOSER_AUTH nginx -g 'daemon off;' &
  pids+=($!)
}

start_worker() {
  "${as_app[@]}" php "$app/bin/console" satisfy:worker &
  pids+=($!)
}

# The first process to end ends the container, so that a restart policy brings
# them all back together rather than leaving one half running.
supervise() {
  trap 'kill -TERM "${pids[@]}" 2>/dev/null || true' TERM INT
  set +e
  wait -n "${pids[@]}"
  status=$?
  kill -TERM "${pids[@]}" 2>/dev/null
  wait
  exit "$status"
}

role="${1:-all}"
case "$role" in
  all)
    prepare serves
    start_web
    start_worker
    supervise
    ;;
  web)
    prepare serves
    start_web
    supervise
    ;;
  worker)
    prepare
    exec "${as_app[@]}" php "$app/bin/console" satisfy:worker
    ;;
  rebuild)
    shift
    prepare
    exec "${as_app[@]}" php "$app/bin/console" satisfy:rebuild "$@"
    ;;
  *)
    exec "$@"
    ;;
esac
