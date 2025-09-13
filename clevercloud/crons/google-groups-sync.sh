#!/bin/bash -l

# Important to keep this file
# Clevercloud requires this to load environment
# https://developers.clever-cloud.com/doc/administrate/cron/#access-environment-variables

# Source common utilities for healthcheck monitoring
source ${ROOT}/clevercloud/crons/common.sh

# Initialize healthcheck monitoring
# Requires HEALTHCHECK_GOOGLE_GROUPS_SYNC env var to be set with healthchecks.io UUID
init_healthcheck "HEALTHCHECK_GOOGLE_GROUPS_SYNC" "google-groups-sync"

cd ${APP_HOME}
bin/console google-groups-sync --execute
