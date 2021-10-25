#!/bin/bash
set -euo pipefail

DEFAULT_TARGET="test.clubalpinlyon.fr"
TARGET=${1:-$DEFAULT_TARGET}

if [ $TARGET != "clubalpinlyon.fr" ] && [ $TARGET != "test.clubalpinlyon.fr" ]; then
  echo "Invalid target \"$TARGET\", must be one of \"clubalpinlyon.fr\", \"test.clubalpinlyon.fr\""
  exit 1;
fi;

TIMESTAMP=$(date +%s)
BASE_TARGET="/home/kahe0589/$TARGET"
TARGET_DIR="$BASE_TARGET/deployments/$TIMESTAMP"
CURRENT_DIR="$BASE_TARGET/deployments/current"

echo "Deploying to $TARGET_DIR"

unzip -q "$BASE_TARGET/package.zip" -d "$TARGET_DIR"
ln -s "$BASE_TARGET/ftp" "$TARGET_DIR/public"
ln -s "$BASE_TARGET/forum" "$TARGET_DIR/public"

if [ $TARGET == "clubalpinlyon.fr" ]; then
  ln -s "$BASE_TARGET/CAFLV-TV" "$TARGET_DIR/public"
  ln -s "$BASE_TARGET/ffcam-ftp-folder" "$TARGET_DIR/config/www.clubalpinlyon.fr/ffcam"
fi;

unlink $CURRENT_DIR
ln -s $TARGET_DIR $CURRENT_DIR
