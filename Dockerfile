# ----------------------------
# Stage 1: Build frontend assets with Node
# ----------------------------
FROM node:22-alpine AS node-build
WORKDIR /app

# Copy Node dependency files and install
COPY package*.json ./
RUN npm install --production

# Copy source files and build frontend
COPY . .
RUN npm run build

# ----------------------------
# Stage 2: PHP + Laravel
# ----------------------------
FROM php:8.2-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    bash git curl zip unzip \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev sqlite sqlite-dev \
    mariadb-connector-c-dev \
    composer && \
    docker-php-ext-install pdo pdo_mysql mbstring bcmath gd

WORKDIR /var/www/html

# Copy Laravel source
COPY . .

# Copy built frontend assets from Node stage
COPY --from=node-build /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Cache Laravel config/routes/views
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# ----------------------------
# Entrypoint Script for DB migrations and seeders
# ----------------------------
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose Laravel port
EXPOSE 8000

# Run entrypoint script
ENTRYPOINT ["docker-entrypoint.sh"]
