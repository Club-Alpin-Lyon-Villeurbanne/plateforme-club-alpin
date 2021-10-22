#!/usr/bin/env bash
set -euo pipefail

set -e

# Configure Apache Document Root
mkdir -p $APACHE_DOC_ROOT

# Update of user and group ids with the one of the machine
usermod -u $(stat -c %u $APACHE_DOC_ROOT) www-data &> /dev/null
groupmod -g $(stat -c %g $APACHE_DOC_ROOT) www-data &> /dev/null

sed -i "s|DocumentRoot /var/www/html\$|DocumentRoot $APACHE_DOC_ROOT|" /etc/apache2/sites-available/000-default.conf
echo "<Directory /var/www/html>" > /etc/apache2/conf-available/document-root-directory.conf
echo "	AllowOverride All" >> /etc/apache2/conf-available/document-root-directory.conf
echo "	Require all granted" >> /etc/apache2/conf-available/document-root-directory.conf
echo "</Directory>" >> /etc/apache2/conf-available/document-root-directory.conf
a2enconf "document-root-directory.conf" 2>&1 > /dev/null

# Let's go
if [ "${1:-}" == 'apache2-foreground' ]; then
    # let's start as root
    exec "$@"
elif [ "$*" == "" ]; then
    exec gosu www-data bash
else
    exec gosu www-data "$@"
fi
