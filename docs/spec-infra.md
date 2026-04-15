# Papyrus — Spec infrastructure

**Raspberry Pi 4 / Pi 5 · Ubuntu Server · Nginx · PostgreSQL 16**
Version 1.0

---

## 1. Matériel et OS

### 1.1 Compatibilité Pi 4 / Pi 5

| | Pi 4 (4GB+) | Pi 5 (4GB+) |
|---|---|---|
| OS recommandé | Ubuntu Server 24.04 LTS | Ubuntu Server 24.04 LTS |
| PostgreSQL 16 | ✓ | ✓ |
| PHP 8.3 | ✓ | ✓ |
| Node 20 (build Vite) | ✓ | ✓ |
| Remarque | Suffisant pour usage perso | Plus réactif sur les builds |

> Le Pi 4 avec 4GB RAM convient parfaitement. Si tu constates des lenteurs sur le build Vite, faire le build sur ta machine de dev et déployer uniquement le `dist/`.

### 1.2 Stockage recommandé

- Carte SD Class 10 **ou de préférence SSD USB** (plus fiable pour une base de données)
- Minimum 32GB, 64GB conseillé

---

## 2. Installation initiale

### 2.1 Paquets système

```bash
sudo apt update && sudo apt upgrade -y

# PHP 8.3
sudo add-apt-repository ppa:ondrej/php
sudo apt install -y php8.3 php8.3-fpm php8.3-pgsql php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl

# PostgreSQL 16
sudo apt install -y postgresql-16

# Nginx
sudo apt install -y nginx

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Node 20 (pour build Vite si besoin)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2.2 Répertoire de l'application

```bash
sudo mkdir -p /var/www/novelcraft
sudo chown -R www-data:www-data /var/www/novelcraft
sudo usermod -aG www-data $USER
```

---

## 3. PostgreSQL

### 3.1 Création base et utilisateur

```bash
sudo -u postgres psql

CREATE USER novelcraft WITH PASSWORD 'mot_de_passe_fort';
CREATE DATABASE novelcraft OWNER novelcraft;
GRANT ALL PRIVILEGES ON DATABASE novelcraft TO novelcraft;
\q
```

### 3.2 Configuration PostgreSQL pour le Pi

```bash
# /etc/postgresql/16/main/postgresql.conf
# Ajuster pour limiter la RAM utilisée (Pi 4 = 4GB)

shared_buffers = 256MB          # ~10% de la RAM
work_mem = 16MB
maintenance_work_mem = 64MB
max_connections = 50            # on n'a pas besoin de plus
effective_cache_size = 1GB
```

```bash
sudo systemctl restart postgresql
```

### 3.3 Sauvegarde automatique

```bash
# /etc/cron.d/novelcraft-backup
0 3 * * * www-data pg_dump novelcraft | gzip > /var/backups/novelcraft/$(date +\%Y\%m\%d).sql.gz

# Créer le dossier
sudo mkdir -p /var/backups/novelcraft
sudo chown www-data:www-data /var/backups/novelcraft
```

> Conserver 30 jours de backups. Ajouter une copie vers un stockage externe (clé USB, NAS, ou rclone vers un cloud).

---

## 4. Nginx

### 4.1 Vhost Laravel (API)

```nginx
# /etc/nginx/sites-available/novelcraft-api
server {
    listen 80;
    server_name api.novelcraft.local;   # ou ton domaine en prod
    root /var/www/novelcraft/backend/public;
    index index.php;

    # Sécurité basique
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Upload max (images fiches)
    client_max_body_size 10M;
}
```

### 4.2 Vhost Vue (frontend)

```nginx
# /etc/nginx/sites-available/novelcraft-front
server {
    listen 80;
    server_name novelcraft.local;   # ou ton domaine
    root /var/www/novelcraft/frontend/dist;
    index index.html;

    # Vue Router — toujours servir index.html
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache long pour les assets Vite (hash dans le nom)
    location ~* \.(js|css|png|jpg|ico|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PWA — manifest et service worker sans cache
    location ~* (manifest\.webmanifest|sw\.js)$ {
        expires off;
        add_header Cache-Control "no-store";
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/novelcraft-api  /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/novelcraft-front /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 5. HTTPS — Let's Encrypt ou certificat local

### 5.1 Accès depuis l'extérieur (domaine public)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d novelcraft.mondomaine.fr -d api.novelcraft.mondomaine.fr
```

Certbot modifie automatiquement les vhosts Nginx et renouvelle via un cron systemd.

### 5.2 Accès local uniquement (réseau domestique)

Utiliser **mkcert** pour un certificat auto-signé reconnu par les navigateurs :

```bash
sudo apt install -y libnss3-tools
curl -JLO https://github.com/FiloSottile/mkcert/releases/latest/download/mkcert-linux-arm64
chmod +x mkcert-linux-arm64 && sudo mv mkcert-linux-arm64 /usr/local/bin/mkcert

mkcert -install
mkcert novelcraft.local api.novelcraft.local

# Copier les certificats générés dans Nginx
ssl_certificate     /etc/ssl/novelcraft/novelcraft.local.pem;
ssl_certificate_key /etc/ssl/novelcraft/novelcraft.local-key.pem;
```

> Sur mobile (pause déjeuner), s'assurer que le téléphone est sur le même réseau Wi-Fi, ou configurer un accès VPN (WireGuard sur le Pi) pour accès depuis l'extérieur.

---

## 6. Laravel — configuration production

### 6.1 Variables d'environnement

```env
# /var/www/novelcraft/backend/.env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.novelcraft.local

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=novelcraft
DB_USERNAME=novelcraft
DB_PASSWORD=mot_de_passe_fort

QUEUE_CONNECTION=database
SESSION_DRIVER=cookie
SESSION_SECURE_COOKIE=true

SANCTUM_STATEFUL_DOMAINS=novelcraft.local
```

### 6.2 Commandes post-déploiement

```bash
cd /var/www/novelcraft/backend

composer install --no-dev --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 7. Queue worker — systemd

```ini
# /etc/systemd/system/novelcraft-worker.service
[Unit]
Description=Papyrus Queue Worker
After=network.target postgresql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/novelcraft/backend
ExecStart=/usr/bin/php artisan queue:work database \
  --sleep=3 --tries=3 --timeout=120 --max-jobs=500
Restart=on-failure
RestartSec=5s
StandardOutput=append:/var/log/novelcraft/worker.log
StandardError=append:/var/log/novelcraft/worker.log

[Install]
WantedBy=multi-user.target
```

```bash
sudo mkdir -p /var/log/novelcraft
sudo chown www-data:www-data /var/log/novelcraft
sudo systemctl daemon-reload
sudo systemctl enable novelcraft-worker
sudo systemctl start novelcraft-worker
```

---

## 8. Déploiement — script

```bash
#!/bin/bash
# /var/www/novelcraft/deploy.sh

set -e

echo "=== Déploiement Papyrus ==="

# Backend
cd /var/www/novelcraft/backend
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Redémarrer le worker
sudo systemctl restart novelcraft-worker

# Frontend — build sur la machine de dev, on déploie le dist
cd /var/www/novelcraft/frontend
# Si build sur le Pi (Pi 5 recommandé) :
# npm ci && npm run build
# Sinon copier le dist depuis la machine de dev via rsync :
# rsync -avz --delete dist/ pi@novelcraft.local:/var/www/novelcraft/frontend/dist/

sudo systemctl reload nginx

echo "=== Déploiement terminé ==="
```

### Depuis la machine de dev (recommandé pour Pi 4)

```bash
# Sur ta machine de dev
npm run build
rsync -avz --delete dist/ pi@novelcraft.local:/var/www/novelcraft/frontend/dist/
```

> Le Pi 4 peut builder Vite mais c'est lent (~2-3 min). Faire le build en local et pousser uniquement le `dist/` est bien plus rapide.

---

## 9. Monitoring léger

```bash
# Voir les logs Laravel
tail -f /var/www/novelcraft/backend/storage/logs/laravel.log

# Voir les logs du worker
tail -f /var/log/novelcraft/worker.log

# Status des services
sudo systemctl status novelcraft-worker
sudo systemctl status nginx
sudo systemctl status postgresql

# Charge du Pi
htop

# Espace disque
df -h
```

### Logrotate — éviter que les logs gonflent

```
# /etc/logrotate.d/novelcraft
/var/www/novelcraft/backend/storage/logs/*.log
/var/log/novelcraft/*.log {
    daily
    missingok
    rotate 14
    compress
    notifempty
    create 0664 www-data www-data
}
```

---

## 10. Accès mobile (pause déjeuner)

Deux options selon le contexte :

### Option A — Réseau local Wi-Fi
Le téléphone et le Pi sont sur le même réseau. Accès via l'IP locale ou le hostname `novelcraft.local` (mDNS).

```bash
# S'assurer que avahi-daemon est actif (mDNS)
sudo apt install -y avahi-daemon
sudo systemctl enable avahi-daemon
```

### Option B — Accès depuis l'extérieur via WireGuard

```bash
sudo apt install -y wireguard

# Générer les clés
wg genkey | tee /etc/wireguard/private.key | wg pubkey > /etc/wireguard/public.key

# Config /etc/wireguard/wg0.conf
[Interface]
PrivateKey = <clé privée Pi>
Address = 10.0.0.1/24
ListenPort = 51820

[Peer]
PublicKey = <clé publique téléphone>
AllowedIPs = 10.0.0.2/32
```

```bash
sudo systemctl enable wg-quick@wg0
sudo systemctl start wg-quick@wg0
```

> Ouvrir le port 51820/UDP sur la box. L'appli WireGuard sur Android/iOS permet de se connecter au VPN en un tap — ensuite `novelcraft.local` est accessible comme si on était sur le réseau domestique.

---

## 11. Résumé des ports et services

| Service | Port | Notes |
|---|---|---|
| Nginx (HTTP) | 80 | Redirige vers HTTPS |
| Nginx (HTTPS) | 443 | Frontend + API |
| PostgreSQL | 5432 | Local uniquement, pas exposé |
| PHP-FPM | socket unix | Via Nginx uniquement |
| Queue worker | — | Service systemd |
| WireGuard | 51820/UDP | Optionnel, accès externe |
