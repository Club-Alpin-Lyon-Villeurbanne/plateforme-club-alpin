#!/bin/bash 

set -e

# To avoid autorun of composer install by Clevercloud 
# Otherwise we can't dump the environment before it runs

mv composer.json composer.json.old
mv composer.lock composer.lock.old
