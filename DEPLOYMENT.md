# Codgoo Deployment Guide

## Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL/MariaDB
- Node.js and NPM (optional)
- Git

## Server Deployment Steps

### 1. Clone/Pull Repository
```bash
git clone https://github.com/samerhassan95/codgoo-ecosystem.git
# OR if already cloned:
git pull origin main
```

### 2. Run Deployment Script
**Linux/Mac:**
```bash
chmod +x deploy.sh
./deploy.sh
```

**Windows:**
```cmd
deploy.bat
```

### 3. Environment Configuration
1. Copy `.env.example` to `.env` (if not exists)
2. Configure database settings:
   ```
   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3307
   DB_DATABASE=codgoo
   DB_USERNAME=root
   DB_PASSWORD=your_password
   ```

3. Configure Firebase credentials:
   ```
   FIREBASE_CREDENTIALS=/path/to/your/firebase/credentials.json
   FIREBASE_DATABASE_URL=https://your-project.firebaseio.com
   ```

4. Set application URL:
   ```
   APP_URL=http://your-domain.com
   ```

### 4. JWT Keys Generation
The application uses JWT keys for authentication. These are automatically generated during deployment, but you can also generate them manually:

```bash
php generate_keys.php
```

**IMPORTANT:** 
- JWT keys are NOT stored in Git for security reasons
- Keys are automatically generated on first deployment
- Existing keys are preserved during updates
- Keys should have restricted permissions (600)

### 5. File Permissions (Linux/Mac only)
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
sudo chmod 600 storage/keys/*.pem  # Secure JWT keys
```

### 6. Web Server Configuration

#### Apache (.htaccess)
Ensure mod_rewrite is enabled and document root points to `/public` directory.

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/codgoo/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Important Files to Verify

### 1. Firebase Credentials
Ensure the Firebase JSON file exists at the path specified in `.env`:
```bash
ls -la /path/to/firebase/credentials.json
```

### 2. Storage Permissions
```bash
ls -la storage/
ls -la bootstrap/cache/
```

### 3. JWT Keys
Verify JWT keys are generated and secure:
```bash
ls -la storage/keys/
# Should show jwt_private.pem and jwt_public.pem with 600 permissions
```

### 4. Database Connection
Test database connection:
```bash
php artisan db:show
```

## Testing Deployment

### 1. Check Application Status
```bash
curl -I http://your-domain.com
```

### 2. Test API Endpoints
```bash
curl http://your-domain.com/api/client/our-products
curl http://your-domain.com/api/client/sections
```

### 3. Check Logs
```bash
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Common Issues

1. **500 Internal Server Error**
   - Check storage permissions
   - Verify .env configuration
   - Check error logs

2. **Firebase Errors**
   - Verify credentials file path
   - Check file permissions
   - Ensure credentials file is valid JSON

3. **JWT Key Issues**
   - Generate keys: `php generate_keys.php`
   - Check file permissions: `chmod 600 storage/keys/*.pem`
   - Verify paths in .env file

4. **Database Connection Issues**
   - Verify database credentials
   - Check if database exists
   - Ensure MySQL service is running

4. **Route Caching Issues**
   - Clear route cache: `php artisan route:clear`
   - Don't cache routes if using closures

### Log Files
- Application logs: `storage/logs/laravel.log`
- Web server logs: `/var/log/apache2/error.log` or `/var/log/nginx/error.log`

## Maintenance Commands

### Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Update Dependencies
```bash
composer update --no-dev
npm update --production
```

### Database Operations
```bash
php artisan migrate:status
php artisan migrate --force
php artisan db:seed --force
```

## Security Notes

1. Never commit `.env` file to Git
2. Keep Firebase credentials secure
3. Use HTTPS in production
4. Regularly update dependencies
5. Monitor logs for suspicious activity

## Support

For deployment issues, check:
1. Laravel logs: `storage/logs/laravel.log`
2. Web server error logs
3. Database connection status
4. File permissions

Contact: [Your contact information]