FROM php:8.4-fpm-alpine AS main

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

RUN install-php-extensions pdo_mysql intl opcache

RUN apk add --no-cache \
    nginx \
    multirun

COPY ./nginx.conf /etc/nginx/http.d/default.conf

COPY . /app
WORKDIR /app

CMD ["multirun", "php-fpm -R -F", "nginx -g 'daemon off;'"]

FROM main AS dev

RUN composer install --no-scripts


FROM main AS prod

ENV APP_ENV=prod
