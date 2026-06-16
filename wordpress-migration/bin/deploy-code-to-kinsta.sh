#!/usr/bin/env bash
set -euo pipefail

# Code-only deploy for Simms WordPress migration.
# This syncs ONLY the custom theme and custom plugin. It does not touch the database,
# uploads, orders, products, customers, payment settings, or WooCommerce settings.
#
# Required environment variables:
#   KINSTA_SSH_TARGET="username@hostname"
#   KINSTA_WP_ROOT="/www/sitename/public"
# Optional:
#   KINSTA_SSH_PORT="12345"
#   DEPLOY_THEME="1"
#   DEPLOY_PLUGIN="1"
#   DRY_RUN="1"
#
# Example:
#   KINSTA_SSH_TARGET="kinsta_user@123.45.67.89" \
#   KINSTA_SSH_PORT="12345" \
#   KINSTA_WP_ROOT="/www/simmsresearch_123/public" \
#   ./wordpress-migration/bin/deploy-code-to-kinsta.sh

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
MIGRATION_DIR=$(dirname "$SCRIPT_DIR")
THEME_SRC="$MIGRATION_DIR/wp-content/themes/simms-research-wp/"
PLUGIN_SRC="$MIGRATION_DIR/wp-content/plugins/simms-lab-results/"

: "${KINSTA_SSH_TARGET:?Set KINSTA_SSH_TARGET, e.g. user@host from MyKinsta SSH/SFTP details}"
: "${KINSTA_WP_ROOT:?Set KINSTA_WP_ROOT, e.g. /www/site/public from MyKinsta}"

KINSTA_SSH_PORT="${KINSTA_SSH_PORT:-22}"
DEPLOY_THEME="${DEPLOY_THEME:-1}"
DEPLOY_PLUGIN="${DEPLOY_PLUGIN:-1}"
DRY_RUN="${DRY_RUN:-0}"

SSH_CMD=(ssh -p "$KINSTA_SSH_PORT")
RSYNC_SSH="ssh -p $KINSTA_SSH_PORT"
RSYNC_FLAGS=(-az --delete --human-readable --itemize-changes)

if [ "$DRY_RUN" = "1" ]; then
  RSYNC_FLAGS+=(--dry-run)
  echo "DRY RUN: no remote files will be changed."
fi

if [ ! -d "$THEME_SRC" ]; then
  echo "Missing theme source: $THEME_SRC" >&2
  exit 1
fi

if [ ! -d "$PLUGIN_SRC" ]; then
  echo "Missing plugin source: $PLUGIN_SRC" >&2
  exit 1
fi

echo "Checking remote WordPress root..."
"${SSH_CMD[@]}" "$KINSTA_SSH_TARGET" "test -d '$KINSTA_WP_ROOT/wp-content'"

if [ "$DEPLOY_THEME" = "1" ]; then
  echo "Deploying theme -> $KINSTA_WP_ROOT/wp-content/themes/simms-research-wp/"
  rsync "${RSYNC_FLAGS[@]}" -e "$RSYNC_SSH" "$THEME_SRC" "$KINSTA_SSH_TARGET:$KINSTA_WP_ROOT/wp-content/themes/simms-research-wp/"
fi

if [ "$DEPLOY_PLUGIN" = "1" ]; then
  echo "Deploying plugin -> $KINSTA_WP_ROOT/wp-content/plugins/simms-lab-results/"
  rsync "${RSYNC_FLAGS[@]}" -e "$RSYNC_SSH" "$PLUGIN_SRC" "$KINSTA_SSH_TARGET:$KINSTA_WP_ROOT/wp-content/plugins/simms-lab-results/"
fi

echo "Deploy complete. Now hard-refresh the Kinsta temporary URL and test: home, shop, hero PDP, cart, checkout."
