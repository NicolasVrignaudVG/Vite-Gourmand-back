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

WORKDIR /var/www/html

COPY . .

# .env minimal pour le build
RUN echo "APP_ENV=prod\nAPP_SECRET=placeholder\nDATABASE_URL=mysql://root:pass@localhost:3306/vite?serverVersion=8.0\nJWT_SECRET_KEY=/var/www/html/config/jwt/private.pem\nJWT_PUBLIC_KEY=/var/www/html/config/jwt/public.pem\nJWT_PASSPHRASE=" > .env

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts

RUN mkdir -p var/cache var/log config/jwt && chmod -R 777 var/

RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

# Copier les clés JWT depuis /etc/secrets vers config/jwt/ et lancer Apache
CMD bash -c "\
    cp /etc/secrets/private /var/www/html/config/jwt/private.pem 2>/dev/null || true && \
    cp /etc/secrets/public  /var/www/html/config/jwt/public.pem  2>/dev/null || true && \
    chmod 644 /var/www/html/config/jwt/*.pem 2>/dev/null || true && \
    chown www-data:www-data /var/www/html/config/jwt/*.pem 2>/dev/null || true && \
    php bin/console cache:clear --env=prod --no-debug 2>/dev/null || true && \
    apache2-foreground"
