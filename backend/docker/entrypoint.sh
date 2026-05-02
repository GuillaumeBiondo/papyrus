#!/bin/sh
set -e

# Mode worker : lance la queue à la place de Nginx+PHP-FPM
if [ "$1" = "worker" ]; then
    echo "[worker] Démarrage du queue worker..."
    exec php artisan queue:work database --sleep=3 --tries=3 --timeout=120 --max-jobs=500
fi

# Mode backend : migrations + cache + démarrage des services
echo "[backend] Attente de la base de données..."
until php artisan db:monitor 2>/dev/null || php -r "
    \$pdo = new PDO(
        'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD')
    );
" 2>/dev/null; do
    sleep 2
done

echo "[backend] Lancement des migrations..."
php artisan migrate --force

echo "[backend] Mise en cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[backend] Lien de stockage..."
php artisan storage:link 2>/dev/null || true

echo "[backend] Démarrage Nginx + PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
