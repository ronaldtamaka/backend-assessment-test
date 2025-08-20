#!/bin/bash

echo "Navigating to project directory..."
cd /Applications/XAMPP/xamppfiles/htdocs/freelance/backend-assessment-test

echo "Dropping all tables..."
php artisan db:wipe --force

echo "Running fresh migrations..."
php artisan migrate --force

echo "Running tests..."
php artisan test
