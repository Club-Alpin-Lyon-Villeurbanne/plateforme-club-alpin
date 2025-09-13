#!/bin/bash -l

# Important to keep this file
# Clevercloud requires this to load environment
# https://developers.clever-cloud.com/doc/administrate/cron/#access-environment-variables

# Source common utilities for healthcheck monitoring
source ${ROOT}/clevercloud/crons/common.sh

# Initialize healthcheck monitoring
# Requires HEALTHCHECK_SEND_REMINDERS env var to be set with healthchecks.io UUID
init_healthcheck "HEALTHCHECK_SEND_REMINDERS" "send-reminders"

cd ${APP_HOME}
if [ "$DEPLOY_ENV" = "production" ]; then
    bin/console event-to-publish-reminder-cron
fi