#!/bin/bash
set -euo pipefail

DEFAULT_TARGET="test.clubalpinlyon.fr"
TARGET=${1:-$DEFAULT_TARGET}

if [ $TARGET != "clubalpinlyon.fr" ] && [ $TARGET != "test.clubalpinlyon.fr" ] && [ $TARGET != "clubalpinlyon.top" ]; then
  echo "Invalid target \"$TARGET\", must be one of \"clubalpinlyon.fr\", \"test.clubalpinlyon.fr\", \"clubalpinlyon.top\""
  exit 1;
fi;

TIMESTAMP=$(date +%s)
BASE_TARGET="/var/www/$TARGET"
TARGET_DIR="$BASE_TARGET/deployments/$TIMESTAMP"
CURRENT_DIR="$BASE_TARGET/deployments/current"
mkdir -p $TARGET_DIR

echo "Deploying to $TARGET_DIR"

unzip -q "$BASE_TARGET/package.zip" -d "$TARGET_DIR"
ln -s "$BASE_TARGET/ftp" "$TARGET_DIR/public"
ln -s "$BASE_TARGET/forum" "$TARGET_DIR/public"

if [ $TARGET == "clubalpinlyon.fr" ]; then
  ln -s "$BASE_TARGET/CAFLV-TV" "$TARGET_DIR/public"
  ln -s "$BASE_TARGET/ffcam-ftp-folder" "$TARGET_DIR/legacy/config/www.clubalpinlyon.fr/ffcam"
  ln -s "$BASE_TARGET/ffcam-ftp-folder" "$TARGET_DIR/legacy/config/ffcam-ftp-folder"
fi;

unlink $CURRENT_DIR

ln -s $TARGET_DIR $CURRENT_DIR

$CURRENT_DIR/bin/console doctrine:migrations:sync-metadata-storage --env=prod
$CURRENT_DIR/bin/console doctrine:migrations:migrate --env=prod --no-interaction
$CURRENT_DIR/bin/console messenger:setup-transports --env=prod
