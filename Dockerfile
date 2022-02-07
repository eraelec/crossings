FROM php:7.2-fpm-alpine as base
WORKDIR /app

RUN set -x \
    && docker-php-ext-install -j$(nproc) \
        mysqli pdo_mysql

RUN set -x \
    && php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

RUN wget -O /usr/local/bin/supercronic https://github.com/aptible/supercronic/releases/download/v0.1.12/supercronic-linux-amd64 \
    && chmod +x /usr/local/bin/supercronic

RUN wget -O /usr/local/bin/dumb-init https://github.com/Yelp/dumb-init/releases/download/v1.2.5/dumb-init_1.2.5_x86_64 \
    && chmod +x /usr/local/bin/dumb-init

FROM base as backend

COPY ./composer.* /app/
RUN composer install -n --no-cache --no-ansi --no-autoloader --no-scripts --prefer-dist

COPY --chown=www-data:www-data . /app/
RUN composer dump-autoload -n --optimize

FROM nginx:1.16-alpine as frontend
WORKDIR /app

COPY --from=backend /app/web /app/web
