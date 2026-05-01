FROM php:8.2-fpm

# Install nginx and PostgreSQL extension
RUN apt-get update && apt-get install -y \
    nginx \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Configure nginx
RUN echo 'server { \n\
    listen 80; \n\
    root /var/www/html; \n\
    index index.php index.html; \n\
    \n\
    location / { \n\
    try_files $uri $uri/ /index.php?$query_string; \n\
    } \n\
    \n\
    location ~ \.php$ { \n\
    fastcgi_pass 127.0.0.1:9000; \n\
    fastcgi_index index.php; \n\
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \n\
    include fastcgi_params; \n\
    } \n\
    }' > /etc/nginx/sites-available/default

# Copy PHP code
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# Startup script: run both php-fpm and nginx
RUN echo '#!/bin/sh\nphp-fpm -D\nnginx -g "daemon off;"' > /start.sh \
    && chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]