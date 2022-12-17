FROM ubuntu:20.04

ARG PHP=7.4
ARG DEBIAN_FRONTEND=noninteractive

# Install development tooling and utils
RUN apt-get update -yqq && \
	apt-get install -yqq \
	software-properties-common build-essential \
	mc htop git unzip iputils-ping

# Install PHP and extensions including PostgreSQL support
RUN apt-get install -yqq \
	php${PHP}-dev php${PHP}-mbstring php${PHP}-curl php${PHP}-pgsql

# Install Composer 2.0
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer

COPY ./ /var/www/
WORKDIR /var/www

RUN composer install --optimize-autoloader --classmap-authoritative --no-dev

# https://github.com/spiral/roadrunner#installation
RUN ./vendor/bin/rr get-binary
RUN ["chmod", "+x", "./rr"]

CMD ./rr serve
