#!/bin/bash

# Codgoo Deployment Script
# This script should be run after pulling from Git on the server

echo "🚀 Starting Codgoo deployment..."

# Install/Update Composer dependencies
echo "📦 Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader

# Install/Update NPM dependencies (if needed)
if [ -f "package.json" ]; then
    echo "📦 Installing NPM dependencies..."
    npm install --production
fi

# Create storage directories if they don't exist
echo "📁 Creating storage directories..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/app/public
mkdir -p storage/keys
mkdir -p bootstrap/cache

# Generate JWT keys if they don't exist
echo "🔐 Checking JWT keys..."
if [ ! -f "storage/keys/jwt_private.pem" ] || [ ! -f "storage/keys/jwt_public.pem" ]; then
    echo "🔑 Generating JWT keys..."
    php generate_keys.php
    echo "✅ JWT keys generated successfully!"
else
    echo "✅ JWT keys already exist"
fi

# Set proper permissions
echo "🔐 Setting permissions..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public
chmod 600 storage/keys/*.pem  # Secure key files

# Clear and cache configuration
echo "⚙️ Optimizing configuration..."
php artisan config:clear
php artisan config:cache

# Clear and cache routes (skip if there are route conflicts)
echo "🛣️ Optimizing routes..."
php artisan route:clear
# php artisan route:cache  # Uncomment if route conflicts are resolved

# Clear and cache views
echo "👁️ Optimizing views..."
php artisan view:clear
php artisan view:cache

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Create symbolic link for storage (if not exists)
if [ ! -L "public/storage" ]; then
    echo "🔗 Creating storage link..."
    php artisan storage:link
fi

# Clear application cache
echo "🧹 Clearing application cache..."
php artisan cache:clear

echo "✅ Deployment completed successfully!"
echo ""
echo "🔧 Manual steps to complete:"
echo "1. Verify .env file has correct database and Firebase credentials"
echo "2. Ensure Firebase credentials file exists at the configured path"
echo "3. Verify JWT keys are properly generated and secured"
echo "4. Test the application endpoints"
echo "5. Check logs for any errors: tail -f storage/logs/laravel.log"