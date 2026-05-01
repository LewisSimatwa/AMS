FROM php:8.2-apache

# Install PostgreSQL extension
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Fix MPM conflict: directly remove all MPM configs and create only prefork
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load \
    /etc/apache2/mods-enabled/mpm_*.conf \
    && echo "LoadModule mpm_prefork_module /usr/lib/apache2/modules/mod_mpm_prefork.so" \
    > /etc/apache2/mods-enabled/mpm_prefork.load \
    && echo "<IfModule mpm_prefork_module>\n\
    StartServers 5\n\
    MinSpareServers 5\n\
    MaxSpareServers 10\n\
    MaxRequestWorkers 150\n\
    MaxConnectionsPerChild 0\n\
    </IfModule>" > /etc/apache2/mods-enabled/mpm_prefork.conf

# Enable mod_rewrite
RUN a2enmod rewrite

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy PHP code
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80