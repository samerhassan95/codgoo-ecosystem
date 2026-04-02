@echo off
echo 🚀 Starting Codgoo deployment...

REM Install/Update Composer dependencies
echo 📦 Installing Composer dependencies...
composer install --no-dev --optimize-autoloader

REM Install/Update NPM dependencies (if needed)
if exist package.json (
    echo 📦 Installing NPM dependencies...
    npm install --production
)

REM Create storage directories if they don't exist
echo 📁 Creating storage directories...
if not exist "storage\logs" mkdir "storage\logs"
if not exist "storage\framework\cache" mkdir "storage\framework\cache"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\app\public" mkdir "storage\app\public"
if not exist "storage\keys" mkdir "storage\keys"
if not exist "bootstrap\cache" mkdir "bootstrap\cache"

REM Generate JWT keys if they don't exist
echo 🔐 Checking JWT keys...
if not exist "storage\keys\jwt_private.pem" (
    echo 🔑 Generating JWT keys...
    php generate_keys.php
    echo ✅ JWT keys generated successfully!
) else (
    echo ✅ JWT keys already exist
)

REM Clear and cache configuration
echo ⚙️ Optimizing configuration...
php artisan config:clear
php artisan config:cache

REM Clear and cache routes (skip if there are route conflicts)
echo 🛣️ Optimizing routes...
php artisan route:clear
REM php artisan route:cache

REM Clear and cache views
echo 👁️ Optimizing views...
php artisan view:clear
php artisan view:cache

REM Run database migrations
echo 🗄️ Running database migrations...
php artisan migrate --force

REM Create symbolic link for storage (if not exists)
if not exist "public\storage" (
    echo 🔗 Creating storage link...
    php artisan storage:link
)

REM Clear application cache
echo 🧹 Clearing application cache...
php artisan cache:clear

echo ✅ Deployment completed successfully!
echo.
echo 🔧 Manual steps to complete:
echo 1. Verify .env file has correct database and Firebase credentials
echo 2. Ensure Firebase credentials file exists at the configured path
echo 3. Verify JWT keys are properly generated and secured
echo 4. Test the application endpoints
echo 5. Check logs for any errors

pause