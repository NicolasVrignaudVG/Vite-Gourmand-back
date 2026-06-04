FROM php:8.4-apache

RUN apt-get update && apt-get install -y \
    git curl zip unzip libzip-dev libssl-dev libicu-dev \
    && docker-php-ext-install zip pdo pdo_mysql intl \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN printf "APP_ENV=prod\nAPP_SECRET=placeholder\nDATABASE_URL=mysql://root:pass@localhost:3306/vite?serverVersion=8.0\nJWT_SECRET_KEY=/etc/secrets/private\nJWT_PUBLIC_KEY=/etc/secrets/public\nJWT_PASSPHRASE=\nMESSENGER_TRANSPORT_DSN=sync://\n" > .env

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts

RUN mkdir -p var/cache/prod var/log config/jwt && chmod -R 777 var/

# Limiter Apache à 4 processus max pour ne pas dépasser la limite MySQL
RUN printf "ServerName localhost\n\
<IfModule mpm_prefork_module>\n\
    StartServers 1\n\
    MinSpareServers 1\n\
    MaxSpareServers 2\n\
    MaxRequestWorkers 4\n\
    MaxConnectionsPerChild 100\n\
</IfModule>\n" > /etc/apache2/conf-available/limits.conf \
    && a2enconf limits

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

CMD bash -c "\
    mkdir -p /var/www/html/public/images && \
    chmod -R 777 /var/www/html/public/images && \
    chown -R www-data:www-data /var/www/html/public/images && \
    apache2-foreground"
