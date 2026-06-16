#!/usr/bin/env sh
set -eu

if [ "$#" -ne 1 ]; then
  echo "Usage: $0 /absolute/path/to/wordpress-root" >&2
  exit 1
fi

WP_ROOT=$1
SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
MIGRATION_DIR=$(dirname "$SCRIPT_DIR")
THEME_SRC="$MIGRATION_DIR/wp-content/themes/simms-research-wp"
PLUGIN_SRC="$MIGRATION_DIR/wp-content/plugins/simms-lab-results"

if [ ! -d "$WP_ROOT/wp-content" ]; then
  echo "Could not find wp-content under: $WP_ROOT" >&2
  exit 1
fi

mkdir -p "$WP_ROOT/wp-content/themes" "$WP_ROOT/wp-content/plugins"

ln -sfn "$THEME_SRC" "$WP_ROOT/wp-content/themes/simms-research-wp"
ln -sfn "$PLUGIN_SRC" "$WP_ROOT/wp-content/plugins/simms-lab-results"

echo "Linked:"
echo "  $WP_ROOT/wp-content/themes/simms-research-wp -> $THEME_SRC"
echo "  $WP_ROOT/wp-content/plugins/simms-lab-results -> $PLUGIN_SRC"

