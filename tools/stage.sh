#!/usr/bin/env bash
# Publish the working tree to the staging machine and restart the staging container.
#
#   STAGE_HOST=<ssh alias> tools/stage.sh
#
# Optional: STAGE_DIR (default staging/johnandrestrujillo.com, relative to the
# remote home), STAGE_SUDO=1 when the remote user needs sudo for docker.
# The first run creates a staging config (random form secret, mail logged, noindex)
# next to the checkout on the staging machine; it is never part of the repository.
set -euo pipefail

: "${STAGE_HOST:?set STAGE_HOST to the ssh alias of the staging machine}"
stage_dir="${STAGE_DIR:-staging/johnandrestrujillo.com}"
docker_cmd="docker"
[[ "${STAGE_SUDO:-0}" == "1" ]] && docker_cmd="sudo -n docker"

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

# The footer shows which commit staging runs; "+" marks uncommitted changes.
revision="$(git rev-parse --short HEAD 2>/dev/null || echo 'no-commit')"
[[ -n "$(git status --porcelain 2>/dev/null)" ]] && revision="${revision}+"
printf '%s, %s\n' "$revision" "$(date -u '+%Y-%m-%d %H:%M UTC')" > .revision

ssh "$STAGE_HOST" "mkdir -p '$stage_dir'"
rsync -a --delete --exclude '.git/' --exclude 'var/' --exclude 'config/local.php' \
  "$root/" "$STAGE_HOST:$stage_dir/"

ssh "$STAGE_HOST" "STAGE_DIR=$(printf '%q' "$stage_dir") DOCKER=$(printf '%q' "$docker_cmd") bash -s" <<'REMOTE'
set -euo pipefail
cd "$STAGE_DIR"
config_dir="$(cd .. && pwd)/portfolio-staging-config"
if [[ ! -f "$config_dir/local.php" ]]; then
  install -d -m 0755 "$config_dir"   # the web server user in the container must read it
  host_ip="$(hostname -I | awk '{print $1}')"
  secret="$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')"
  cat > "$config_dir/local.php" <<PHP
<?php
return [
    'env' => 'staging',
    'base_url' => 'http://${host_ip}:8096',
    'noindex' => true,
    'mail_mode' => 'log',
    'form_secret' => '${secret}',
    'var_dir' => '/srv/var',
    'metrics_mode' => 'log',
];
PHP
  chmod 0644 "$config_dir/local.php"
fi
STAGE_CONFIG_DIR="$config_dir" $DOCKER compose -f docker/compose.staging.yml up -d --build --remove-orphans
STAGE_CONFIG_DIR="$config_dir" $DOCKER compose -f docker/compose.staging.yml ps --format '{{.Name}}  {{.Status}}  {{.Ports}}'
REMOTE
