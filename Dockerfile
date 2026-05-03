FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

# Install system + PHP extensions
RUN apk add --no-cache \
        bash \
        curl \
        icu-libs \
        libzip \
        oniguruma \
        postgresql-libs \
        unzip \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install \
        gd \
        pdo \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Install composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy project
COPY . .

# Install vendor
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

# Permission
RUN mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

CMD ["php-fpm"]