#!/bin/sh
 
# activate maintenance mode
echo "Shutting down App"
php artisan down

# update source code
echo "Doing git pull"
git pull

# update PHP dependencies
echo "Composer install"
export COMPOSER_HOME='/tmp/composer'
composer install --no-interaction --no-dev --prefer-dist
	# --no-interaction	Do not ask any interactive question
	# --no-dev			Disables installation of require-dev packages.
	# --prefer-dist		Forces installation from package dist even for dev versions.

# clear cache
echo "Cache clear"
php artisan cache:clear

# clear config cache
echo "Config clear"
php artisan config:clear

# cache config
echo "Cache config"
php artisan config:cache

# restart queues 
echo "Start queues"
php artisan -v queue:restart

# update database
echo "Update database"
php artisan migrate --force
	# --force			Required to run when in production.

# seed tables
echo "Seed database tables"
php artisan db:seed --force

# create symbolic link from public/storage to storage/app/public 
echo "Set up storage"
php artisan -q storage:link

# stop maintenance mode
echo "Stop maintenance mode"
php artisan up

