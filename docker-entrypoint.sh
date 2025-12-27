#!/bin/sh
set -e

# Run migrations and seeders
echo "Running migrations..."
php artisan migrate --force

echo "Running seeders..."
php artisan db:seed --force

# Start Laravel
echo "Starting Laravel server..."
php artisan serve --host 0.0.0.0 --port ${PORT:-8000}
