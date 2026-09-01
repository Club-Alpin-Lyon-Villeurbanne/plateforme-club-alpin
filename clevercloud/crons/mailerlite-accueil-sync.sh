#! /bin/bash -l

# Important to keep this file
# Clevercloud requires this to load environment
# https://developers.clever-cloud.com/doc/administrate/cron/#access-environment-variables

cd "${APP_HOME}" || exit 1
if [ "$DEPLOY_ENV" = "production" ]; then
    bin/console mailerlite-accueil-sync --execute
fi
