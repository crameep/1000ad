#!/bin/bash
set -e

# Create .env from .env.example if it doesn't exist
# (.env is excluded from Docker image via .dockerignore — this creates it on first run)
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Create SQLite database if it doesn't exist
DB_PATH="${DB_DATABASE:-database/database.sqlite}"
if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database at $DB_PATH..."
    touch "$DB_PATH"
    chown www-data:www-data "$DB_PATH"
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Cache config and routes for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache database

echo "1000 A.D. is ready!"
exec "$@"
