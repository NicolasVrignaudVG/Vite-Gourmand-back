FROM php:8.4-apache

# Extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libssl-dev libicu-dev \
    && docker-php-ext-install zip pdo pdo_mysql intl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && a2enmod rewrite

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers
COPY . .

# Créer un .env minimal pour le build
RUN echo "APP_ENV=prod" > .env && \
    echo "APP_SECRET=placeholder" >> .env && \
    echo "DATABASE_URL=mysql://root:pass@localhost:3306/vite?serverVersion=8.0" >> .env && \
    echo "JWT_SECRET_KEY=/etc/secrets/private" >> .env && \
    echo "JWT_PUBLIC_KEY=/etc/secrets/public" >> .env && \
    echo "JWT_PASSPHRASE=" >> .env

# Installer les dépendances sans scripts post-install
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts

# Créer les dossiers nécessaires
RUN mkdir -p var/cache var/log && chmod -R 777 var/

# Configuration Apache
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Vider le cache au démarrage puis lancer Apache
CMD bash -c "php bin/console cache:clear --env=prod --no-debug 2>/dev/null || true && apache2-foreground"
