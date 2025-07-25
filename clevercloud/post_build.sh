#!/bin/bash 

set -e

required_vars=("APP_ENV" "DEPLOY_ENV" "JWT_SECRET_KEY" "JWT_PUBLIC_KEY")

for var_name in "${required_vars[@]}"; do
    if [ -z "${!var_name}" ]; then
        echo "Variable $var_name non définie."
        exit 1
    fi
done

JWT_CONF_DIR=config/jwt

cat public/.htaccess.clever > public/.htaccess
mv infrastructure/confs/.env.prod .
mv infrastructure/confs/${DEPLOY_ENV}/robots.txt public/robots.txt

mkdir -p ${JWT_CONF_DIR}
echo "${JWT_SECRET_KEY}" > ${JWT_CONF_DIR}/private.pem
echo "${JWT_PUBLIC_KEY}" > ${JWT_CONF_DIR}/public.pem

mv composer.json.old composer.json
mv composer.lock.old composer.lock

composer.phar install --no-ansi --no-progress --no-interaction --optimize-autoloader --apcu-autoloader --no-dev --no-scripts
composer.phar dump-env prod

bin/console cache:clear
bin/console assets:install public

bin/console doctrine:migrations:migrate --no-interaction
bin/console doctrine:migrations:sync-metadata-storage
bin/console messenger:setup-transports

# Frontend build
npm install && npm run build
