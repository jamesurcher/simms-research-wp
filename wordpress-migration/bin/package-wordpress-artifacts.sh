#!/usr/bin/env sh
set -eu

SCRIPT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)
MIGRATION_DIR=$(dirname "$SCRIPT_DIR")
DIST_DIR="$MIGRATION_DIR/dist"
THEME_DIR="$MIGRATION_DIR/wp-content/themes/simms-research-wp"
PLUGIN_DIR="$MIGRATION_DIR/wp-content/plugins/simms-lab-results"

mkdir -p "$DIST_DIR"
rm -f "$DIST_DIR/simms-research-wp.zip" "$DIST_DIR/simms-lab-results.zip"

(cd "$MIGRATION_DIR/wp-content/themes" && zip -rq "$DIST_DIR/simms-research-wp.zip" simms-research-wp)
(cd "$MIGRATION_DIR/wp-content/plugins" && zip -rq "$DIST_DIR/simms-lab-results.zip" simms-lab-results)

echo "Created:"
echo "  $DIST_DIR/simms-research-wp.zip"
echo "  $DIST_DIR/simms-lab-results.zip"

