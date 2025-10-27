# Utiliser l'image PHP officielle avec Apache
FROM php:8.3-apache

# Installer les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers du projet
COPY . /var/www/html

# Installer les dépendances PHP
RUN composer install --optimize-autoloader --no-dev

# Créer le fichier .env et générer la clé d'application Laravel
RUN echo "APP_NAME=\"GES Bank API\"" > .env && \
    echo "APP_ENV=production" >> .env && \
    echo "APP_KEY=" >> .env && \
    echo "APP_DEBUG=false" >> .env && \
    echo "DB_CONNECTION=pgsql" >> .env && \
    echo "DB_HOST=dpg-d3v8l6ali9vc73ckvc00-a.oregon-postgres.render.com" >> .env && \
    echo "DB_PORT=5432" >> .env && \
    echo "DB_DATABASE=bank_laravel_api" >> .env && \
    echo "DB_USERNAME=bank_laravel_api_user" >> .env && \
    echo "DB_PASSWORD=zNk24j1UwvkG8eM9P4EyUJ8lBj2vzczX" >> .env && \
    echo "L5_SWAGGER_GENERATE_ALWAYS=false" >> .env && \
    php artisan key:generate

# Générer la documentation Swagger
RUN php artisan l5-swagger:generate

# Définir les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Configurer Apache pour Laravel
RUN a2enmod rewrite
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

# Exposer le port 80
EXPOSE 80

# Commande de démarrage
CMD ["apache2-foreground"]
