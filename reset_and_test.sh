#!/bin/bash

echo "Resetting database and running tests..."

# Drop all tables first
php artisan db:wipe --force

# Run fresh migration
php artisan migrate --force

# Run tests
php artisan test
