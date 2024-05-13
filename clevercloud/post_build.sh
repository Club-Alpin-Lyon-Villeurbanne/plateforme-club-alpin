#!/bin/bash 

set -ex 

required_vars=("APP_ENV" "DEPLOY_ENV" "JWT_SECRET_KEY" "JWT_PUBLIC_KEY")

for var_name in "${required_vars[@]}"; do
    if [ -z "${!var_name}" ]; then
        echo "Variable $var_name non définie."
        exit 1
    fi
done

JWT_CONF_DIR=config/jwt

cat public/.htaccess.clever > public/.htaccess
mv clevercloud/framework.yaml config/packages/prod/framework.yaml
mv infrastructure/confs/${DEPLOY_ENV}/.env.${APP_ENV} .

mkdir -p ${JWT_CONF_DIR}
echo "${JWT_SECRET_KEY}" > ${JWT_CONF_DIR}/private.pem
echo "${JWT_PUBLIC_KEY}" > ${JWT_CONF_DIR}/public.pem

cat legacy/config/${DEPLOY_ENV}/config.php > legacy/config/config.php

/usr/bin/composer.phar dump-env ${APP_ENV}

bin/console doctrine:migrations:migrate --env=${APP_ENV} --no-interaction

# Frontend build
 npm install && npm run build