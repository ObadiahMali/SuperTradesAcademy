# Stage 1: Build frontend assets with Node
FROM node:22-alpine AS node-build
WORKDIR /app

# Install Node dependencies
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: PHP + Composer for Laravel
FROM php:8.2-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    bash git curl zip unzip \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    oniguruma-dev libxml2-dev sqlite sqlite-dev \
    mariadb-connector-c-dev \
    composer

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath gd

WORKDIR /var/www/html

# Copy Laravel source
COPY . .

# Copy built frontend assets from Node stage
COPY --from=node-build /app/public/build ./public/build

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Cache Laravel config/routes/views
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

# Expose port for Render
EXPOSE 8000

# Start Laravel using Artisan
CMD php artisan serve --host 0.0.0.0 --port $PORT