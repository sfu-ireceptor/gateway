#!/bin/sh

git pull

composer install --no-interaction --no-dev --prefer-dist
# --no-interaction	Do not ask any interactive question
# --no-dev			Disables installation of require-dev packages.
# --prefer-dist		Forces installation from package dist even for dev versions.

php artisan migrate --force
# --force			Force the operation to run when in production.