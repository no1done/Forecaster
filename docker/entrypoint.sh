#!/bin/sh
set -e

if [ "$1" = 'frankenphp' ]; then
    bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec docker-php-entrypoint "$@"
