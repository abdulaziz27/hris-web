#!/bin/bash
# Script deploy untuk server

# 1. Pull latest code
echo "1. Pulling latest code..."
git pull origin main

# 2. Install dependencies
echo "2. Installing dependencies..."
composer install --no-dev --optimize-autoloader

# 3. Run migrations
echo "3. Running migrations..."
php artisan migrate --force

# 4. Create storage link
echo "4. Creating storage link..."
php artisan storage:link

# 5. Set permissions (tanpa chown dulu, cek user web server terlebih dahulu)
echo "5. Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# 6. Clear cache
echo "6. Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✅ Deploy completed!"
echo ""
echo "⚠️  IMPORTANT: Set ownership manually after checking web server user:"
echo "   ps aux | grep -E 'apache|nginx|php-fpm' | head -1"
echo "   chown -R <web-server-user>:<web-server-group> storage bootstrap/cache"
