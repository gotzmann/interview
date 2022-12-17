FROM ubuntu:20.04

ARG PHP=7.4
ARG DEBIAN_FRONTEND=noninteractive

RUN apt-get update -yqq && \
    apt-get -y upgrade && \
    apt-get -y dist-upgrade && \
    apt-get install -yqq \
    software-properties-common build-essential mc htop 

RUN apt-get install -yqq php${PHP}-dev php${PHP}-mbstring php${PHP}-curl && \
    update-alternatives --set php /usr/bin/php${PHP}

RUN apt-get install -yqq php-pear pkg-config libevent-dev 

# Setup libevent to speed up Workerman and Comet
RUN printf "\n\n /usr/lib/x86_64-linux-gnu/\n\n\nno\n\n\n" | \
    pecl install event && \
    echo "extension=event.so" > /etc/php/${PHP}/cli/conf.d/event.ini

# Install composer 2.0
RUN curl -sS https://getcomposer.org/installer -o composer-setup.php && \
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer

COPY . /app
RUN mkdir -p /app/log && chmod -R 777 /app/log

WORKDIR /app
#RUN composer install --optimize-autoloader --classmap-authoritative --no-dev --quiet

#CMD php app.php start
CMD tail -f /dev/null