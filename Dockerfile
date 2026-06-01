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
    echo "DATABASE_URL=mysql://root:pass@localhost:3306/vite_gourmand?serverVersion=8.0" >> .env

# Installer les dépendances sans exécuter les scripts post-install
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts

# Vider le cache manuellement
RUN mkdir -p var/cache var/log && chmod -R 777 var/

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Configuration Apache
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

EXPOSE 80

CMD ["apache2-foreground"]
