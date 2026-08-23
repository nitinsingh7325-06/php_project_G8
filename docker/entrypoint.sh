#!/bin/sh
set -e

# Wait for database to be ready
echo "Waiting for database..."
while ! nc -z db 3306; do
  sleep 1
done
echo "Database ready!"

# Wait for Redis
echo "Waiting for Redis..."
while ! nc -z redis 6379; do
  sleep 1
done
echo "Redis ready!"

# Run migrations
php scripts/migrate.php

# Clear cache
php scripts/clear-cache.php

# Start PHP-FPM
exec php-fpm