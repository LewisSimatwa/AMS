FROM php:8.2-fpm

# Install nginx and PostgreSQL extension
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Configure nginx
COPY nginx.conf /etc/nginx/sites-available/default

# Copy PHP code
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Startup script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]