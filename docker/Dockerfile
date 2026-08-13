FROM php:8.3-fpm-alpine

LABEL maintainer="Presensi Online"

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add --no-cache \
        nginx \
        fcgi \
        libpq \
        libzip \
        libpng \
        freetype \
        libjpeg-turbo \
        libwebp \
        libgomp \
        icu-libs \
        postgresql-client \
    && apk add --no-cache --virtual .build-deps \
         postgresql-dev \
         mariadb-connector-c-dev \
        libzip-dev \
        libpng-dev \
        freetype-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        icu-dev \
        oniguruma-dev \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
         pdo_pgsql \
         pdo_mysql \
        pgsql \
        gd \
        zip \
        intl \
        mbstring \
        opcache \
        bcmath \
    && pecl install redis && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

RUN apk add --no-cache netcat-openbsd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY . .

RUN set -eux; \
    mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache; \
    chown -R www-data:www-data /var/www/html

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
