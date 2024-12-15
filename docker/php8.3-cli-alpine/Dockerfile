FROM php:8.3-cli-alpine

ARG UID
ARG GID
ARG XDEBUG_VERSION="xdebug-3.4.0"

RUN apk add --no-cache zip libzip-dev bash build-base autoconf
RUN apk add --update linux-headers

COPY ./docker/shared/assert-uid-and-gid-exist.sh /assert-uid-and-gid-exist.sh
COPY ./docker/shared/setup-host-os-user.sh /setup-host-os-user.sh
RUN bash -c "/setup-host-os-user.sh"

COPY ./docker/php8.3-cli-alpine/install-xdebug.sh /install-xdebug.sh
RUN bash -c "/install-xdebug.sh"

RUN docker-php-ext-configure zip
RUN docker-php-ext-install zip
RUN docker-php-ext-enable xdebug

COPY ./docker/shared/php-install-composer.sh /php-install-composer.sh
RUN bash -c "/php-install-composer.sh"
