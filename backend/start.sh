#!/bin/bash
set -e

# Start PHP-FPM in background
php-fpm -D

# Wait for php-fpm to be ready
sleep 1

# Start nginx in foreground (keeps container alive)
nginx -g "daemon off;"