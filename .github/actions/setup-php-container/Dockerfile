ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && install-php-extensions intl gd xsl gmp bcmath zip pdo_mysql pdo_pgsql pcov @composer \
    && curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony*/bin/symfony /usr/local/bin/symfony \
    && rm -rf /var/lib/apt/lists/* /root/.symfony*

RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf 'memory_limit=-1\ndate.timezone=UTC\n' > "$PHP_INI_DIR/conf.d/zz-ci.ini"
