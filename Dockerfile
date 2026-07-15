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

RUN printf "APP_ENV=prod\nAPP_SECRET=\${APP_SECRET}\nDATABASE_URL=\${DATABASE_URL}\nJWT_SECRET_KEY=/etc/secrets/private\nJWT_PUBLIC_KEY=/etc/secrets/public\nJWT_PASSPHRASE=\${JWT_PASSPHRASE}\nMESSENGER_TRANSPORT_DSN=sync://\n" > .env

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs --no-scripts

# Installe les assets des bundles (Swagger UI de NelmioApiDocBundle, etc.)
# dans public/assets/. Nécessaire car composer install est lancé avec --no-scripts,
# ce qui empêche l'installation automatique des assets.
RUN php bin/console assets:install public --no-interaction || true

RUN mkdir -p var/cache/prod var/log config/jwt && chmod -R 775 var/ && chown -R www-data:www-data var/

# Configuration PHP pour l'upload de fichiers
RUN echo "upload_tmp_dir = /tmp\nfile_uploads = On\nupload_max_filesize = 10M\npost_max_size = 12M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/uploads.ini

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

RUN echo "Listen 10000" >> /etc/apache2/ports.conf \
    && sed -i 's/Listen 80//' /etc/apache2/ports.conf

RUN echo '<VirtualHost *:10000>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
        FallbackResource /index.php\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000

CMD bash -c "\
    rm -rf /var/www/html/var/cache/prod && \
    mkdir -p /var/www/html/var/cache/prod && \
    chmod -R 777 /var/www/html/var && \
    chown -R www-data:www-data /var/www/html/var && \
    su www-data -s /bin/bash -c 'php /var/www/html/bin/console cache:warmup --env=prod --no-debug' 2>&1 || true && \
    apache2-foreground"
