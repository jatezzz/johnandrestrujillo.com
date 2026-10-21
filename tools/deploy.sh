#!/usr/bin/env bash
# Publish the committed tree to the shared host.
#
#   DEPLOY_TARGET=<user@host or ssh alias> tools/deploy.sh            # dry run: shows what would change
#   DEPLOY_TARGET=<user@host or ssh alias> DRY_RUN=0 tools/deploy.sh  # publish
#
# Optional: DEPLOY_SSH_KEY (private key file), DEPLOY_KNOWN_HOSTS (known_hosts file).
#
# Layout on the host (the front controller finds the app in either layout):
#   public_html/   public/, with deploy/htaccess.head prepended to .htaccess
#   site/          src, templates, content, vendor, .revision; site/public points at ../public_html
#   site/config/local.php and site/var/ belong to the host and are never overwritten or deleted.
# Every file a publish replaces or removes is kept in deploy-backups/<stamp>/, so a bad
# publish is undone by copying that folder back (see README).
set -euo pipefail

: "${DEPLOY_TARGET:?set DEPLOY_TARGET to user@host or an ssh alias}"
dry_run="${DRY_RUN:-1}"

root="$(cd "$(dirname "$0")/.." && pwd)"
cd "$root"

# Only committed work goes out, so the footer revision always names a real commit.
if [[ -n "$(git status --porcelain --untracked-files=no)" ]]; then
  echo "deploy: the tree has uncommitted changes; commit them first" >&2
  exit 1
fi
revision="$(git rev-parse --short HEAD)"
stamp="$(date -u '+%Y%m%dT%H%M%SZ')"

ssh_opts=(-o BatchMode=yes -o StrictHostKeyChecking=accept-new)
[[ -n "${DEPLOY_SSH_KEY:-}" ]] && ssh_opts+=(-i "$DEPLOY_SSH_KEY" -o IdentitiesOnly=yes)
[[ -n "${DEPLOY_KNOWN_HOSTS:-}" ]] && ssh_opts+=(-o "UserKnownHostsFile=$DEPLOY_KNOWN_HOSTS")
rsh="ssh ${ssh_opts[*]}"

# ---------- Build the two trees ----------
out="$(mktemp -d)"
trap 'rm -rf "$out"' EXIT
mkdir -p "$out/site"
git archive HEAD public src templates content vendor LICENSE | tar -x -C "$out/site"
mv "$out/site/public" "$out/public_html"
{ cat deploy/htaccess.head; echo; cat public/.htaccess; } > "$out/public_html/.htaccess"
ln -s ../public_html "$out/site/public"
printf '%s, %s\n' "$revision" "$(date -u '+%Y-%m-%d %H:%M UTC')" > "$out/site/.revision"

# ---------- First publish: a config that keeps the site closed ----------
# A random form secret, no outgoing mail, no metrics and noindex on, until local.php is edited on the host.
if [[ "$dry_run" == "0" ]]; then
  $rsh "$DEPLOY_TARGET" 'bash -s' <<'REMOTE'
set -euo pipefail
umask 077
mkdir -p site/config site/var deploy-backups
if [[ ! -f site/config/local.php ]]; then
  secret="$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')"
  cat > site/config/local.php <<PHP
<?php
return [
    'env' => 'production',
    'base_url' => 'https://johnandrestrujillo.com',
    'noindex' => true,
    'mail_mode' => 'off',
    'form_secret' => '${secret}',
    'metrics_mode' => 'off',
];
PHP
  echo "deploy: created site/config/local.php (closed: noindex, no mail, no metrics)"
fi
REMOTE
fi

# ---------- Sync: the app first, then the web root ----------
rsync_opts=(-rlptz --delete --itemize-changes --rsh="$rsh")
[[ "$dry_run" == "0" ]] || rsync_opts+=(--dry-run)

rsync "${rsync_opts[@]}" --backup --backup-dir="../deploy-backups/$stamp/site" \
  --exclude '/config/local.php' --exclude '/var/' \
  "$out/site/" "$DEPLOY_TARGET:site/"
rsync "${rsync_opts[@]}" --backup --backup-dir="../deploy-backups/$stamp/public_html" \
  "$out/public_html/" "$DEPLOY_TARGET:public_html/"

if [[ "$dry_run" == "0" ]]; then
  # A syntax error the host's PHP rejects would take the whole site down: check with the same version.
  $rsh "$DEPLOY_TARGET" 'for f in public_html/index.php $(find site/src site/templates -name "*.php"); do php82 -l "$f" >/dev/null || exit 1; done'
  echo "deploy: published $revision (backup of replaced files: deploy-backups/$stamp)"
else
  echo "deploy: dry run of $revision, nothing changed on the host"
fi
