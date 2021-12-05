#!/usr/bin/env bash
set -e

# Set www-data uid & gid
# Force using passed $APP_USER_ID / $APP_GROUP_ID if they exist.
# If the env vars don't exist, use "stat -c".
# This is a workaround for devs using a VM with shared folders, as most
# supervisors do black magic with file permissions...
# $APP_USER_ID / $APP_GROUP_ID can be defined in docker-compose.override.yml
usermod -u ${APP_USER_ID:-$(stat -c %u /app)} node || true
groupmod -g ${APP_GROUP_ID:-$(stat -c %g /app)} node || true

# change to user node
gosu node xvfb-run "$@"
