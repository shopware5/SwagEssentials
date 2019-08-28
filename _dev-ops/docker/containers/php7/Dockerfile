FROM webdevops/php-apache-dev:7.2

ENV COMPOSER_HOME=/.composer
ENV WEB_DOCUMENT_ROOT=/var/www/shopware/shopware

COPY wait-for-it.sh /usr/local/bin/
COPY php-config.ini /usr/local/etc/php/conf.d/
COPY xdebug.ini /usr/local/etc/php/conf.d/
COPY addExternalUser.sh /addExternalUser

# https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=863199
RUN mkdir -p /usr/share/man/man1

RUN apt-get update && apt-get install -y ant mysql-client

RUN chmod +x /usr/local/bin/wait-for-it.sh \
&& ln -s /app/psh.phar /bin/psh

RUN pecl install pcov-1.0.6 \
&& docker-php-ext-enable pcov

WORKDIR /var/www/shopware