# Guide de Déploiement Laravel avec Swagger sur Render

## Introduction
Ce guide explique étape par étape comment déployer votre projet Laravel avec la documentation Swagger sur Render. Nous utiliserons PostgreSQL comme base de données.

## Prérequis
- Un compte Render (gratuit disponible)
- Votre projet Laravel avec Swagger configuré
- Git pour versionner votre code

## Étape 1 : Préparation du projet pour le déploiement

### 1.1 Configuration de la base de données
Modifiez votre fichier `.env.example` pour utiliser PostgreSQL :

```env
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=5432
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
```

### 1.2 Créer un fichier build.sh
Créez un fichier `build.sh` à la racine de votre projet :

```bash
#!/usr/bin/env bash
# Exit on error
set -o errexit

# Install dependencies
composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations (optionnel, selon votre stratégie)
# php artisan migrate --force

# Generate Swagger documentation
php artisan l5-swagger:generate

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Rendez le fichier exécutable :
```bash
chmod +x build.sh
```

### 1.3 Créer un fichier start.sh
Créez un fichier `start.sh` à la racine de votre projet :

```bash
#!/usr/bin/env bash
# Exit on error
set -o errexit

# Run database migrations
php artisan migrate --force

# Start the application
php artisan serve --host=0.0.0.0 --port=$PORT
```

Rendez le fichier exécutable :
```bash
chmod +x start.sh
```

### 1.4 Modifier composer.json
Assurez-vous que votre `composer.json` a les bonnes configurations pour Render :

```json
{
    "scripts": {
        "post-install-cmd": [
            "@php artisan l5-swagger:generate"
        ]
    }
}
```

## Étape 2 : Configuration de Render

### 2.1 Créer un nouveau service Web
1. Allez sur [Render.com](https://render.com)
2. Cliquez sur "New" → "Web Service"
3. Connectez votre repository Git (GitHub, GitLab, ou Bitbucket)

### 2.2 Configuration du service
Remplissez les informations suivantes :

- **Name** : ges-bank-api (ou le nom que vous voulez)
- **Environment** : Docker (nous allons créer un Dockerfile)
- **Region** : Choisissez la région la plus proche (par exemple, Frankfurt pour l'Europe)
- **Branch** : main (ou votre branche principale)
- **Build Command** : `./build.sh`
- **Start Command** : `./start.sh`

### 2.3 Variables d'environnement
Ajoutez les variables suivantes dans "Environment" :

```
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=5432
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}
APP_KEY=${APP_KEY}
APP_ENV=production
APP_DEBUG=false
L5_SWAGGER_GENERATE_ALWAYS=false
```

## Étape 3 : Créer le Dockerfile

Créez un fichier `Dockerfile` à la racine de votre projet :

```dockerfile
# Use the official PHP image with Apache
FROM php:8.1-apache

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip \
    nodejs \
    npm

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www/html

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www/html

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Generate application key
RUN php artisan key:generate

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Configure Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy Apache configuration
COPY <<EOF /etc/apache2/sites-available/000-default.conf
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

# Expose port 80
EXPOSE 80

# Create startup script
RUN echo '#!/bin/bash\n\
php artisan migrate --force\n\
php artisan l5-swagger:generate\n\
apache2-foreground' > /usr/local/bin/start.sh

RUN chmod +x /usr/local/bin/start.sh

# Start Apache
CMD ["/usr/local/bin/start.sh"]
```

## Étape 4 : Configuration de la base de données PostgreSQL

### 4.1 Créer une base de données sur Render
1. Dans votre dashboard Render, cliquez sur "New" → "PostgreSQL"
2. Donnez un nom à votre base de données (ex: ges-bank-db)
3. Choisissez le plan gratuit
4. Notez les informations de connexion (elles seront utilisées automatiquement par Render)

### 4.2 Variables d'environnement pour la DB
Render va automatiquement créer les variables d'environnement suivantes :
- `DATABASE_URL` (contient toutes les infos de connexion)
- Ou séparément : `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

## Étape 5 : Configuration des fichiers statiques pour Swagger

### 5.1 Modifier le fichier .gitignore
Assurez-vous que ces fichiers ne sont pas ignorés :

```
# Swagger documentation
storage/api-docs/
!storage/api-docs/.gitkeep
```

### 5.2 Générer la documentation avant le déploiement
Avant de pousser votre code, générez la documentation :

```bash
php artisan l5-swagger:generate
```

Puis commitez les fichiers générés :
```bash
git add storage/api-docs/
git commit -m "Add generated Swagger documentation"
```

## Étape 6 : Déploiement

### 6.1 Pousser votre code
```bash
git add .
git commit -m "Prepare for deployment on Render"
git push origin main
```

### 6.2 Déployer sur Render
1. Allez dans votre service Web sur Render
2. Cliquez sur "Manual Deploy" → "Deploy latest commit"
3. Attendez que le déploiement se termine (ça peut prendre 5-10 minutes)

### 6.3 Vérifier le déploiement
Une fois déployé, vous devriez avoir :
- Votre API accessible à l'URL fournie par Render
- La documentation Swagger à : `https://votre-app.render.com/api/documentation`

## Étape 7 : Migration de la base de données

### 7.1 Stratégie 1 : Migration automatique (recommandée)
Dans votre `start.sh`, ajoutez :
```bash
php artisan migrate --force
```

### 7.2 Stratégie 2 : Migration manuelle
Après le déploiement, connectez-vous à votre instance via SSH (si disponible) ou utilisez les logs pour exécuter :
```bash
php artisan migrate
```

## Étape 8 : Configuration finale

### 8.1 Variables d'environnement supplémentaires
Ajoutez si nécessaire :
```
APP_URL=https://votre-app.render.com
L5_SWAGGER_BASE_PATH=https://votre-app.render.com
```

### 8.2 Configuration CORS
Si vous avez des problèmes CORS, modifiez `config/cors.php` :

```php
'allowed_origins' => ['*'], // Pour développement, ou spécifiez votre domaine
```

### 8.3 Logs et débogage
Pour voir les logs :
1. Allez dans votre service sur Render
2. Cliquez sur "Logs"
3. Vérifiez les erreurs éventuelles

## Dépannage courant

### Erreur de connexion à la base de données
- Vérifiez que les variables d'environnement sont correctement définies
- Assurez-vous que la base de données PostgreSQL est créée et liée

### Documentation Swagger ne se charge pas
- Vérifiez que `php artisan l5-swagger:generate` est exécuté pendant le build
- Assurez-vous que les fichiers sont dans `storage/api-docs/`

### Erreur 500
- Vérifiez les logs pour les détails
- Assurez-vous que `APP_KEY` est défini
- Vérifiez les permissions des dossiers `storage` et `bootstrap/cache`

### Mémoire insuffisante
Si vous avez une erreur de mémoire, ajoutez dans votre `build.sh` :
```bash
php -d memory_limit=512M /usr/bin/composer install --no-interaction --prefer-dist --optimize-autoloader
```

## Optimisations pour la production

### Cache
Assurez-vous que ces commandes sont dans votre `build.sh` :
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Sécurité
- Changez `APP_DEBUG=false`
- Configurez correctement CORS
- Utilisez HTTPS (activé par défaut sur Render)

## URLs importantes après déploiement

- **API** : `https://votre-app.render.com/api/v1/comptes`
- **Documentation Swagger** : `https://votre-app.render.com/api/documentation`
- **Base de données** : Accessible via les variables d'environnement

## Conclusion

Suivez ces étapes dans l'ordre et votre application Laravel avec Swagger sera déployée sur Render. Le service gratuit de Render offre 750 heures par mois, ce qui est suffisant pour un projet de développement ou une petite application en production.

N'oubliez pas de surveiller les logs et les performances après le déploiement !
