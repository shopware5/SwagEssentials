FROM webdevops/php-apache-dev:7.4

ENV COMPOSER_HOME=/.composer
ENV WEB_DOCUMENT_ROOT=/var/www/shopware/shopware

COPY wait-for-it.sh /usr/local/bin/
COPY php-config.ini /usr/local/etc/php/conf.d/
COPY xdebug.ini /usr/local/etc/php/conf.d/
COPY vhost.conf /opt/docker/etc/httpd/vhost.common.d/

# https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=863199
RUN mkdir -p /usr/share/man/man1

RUN apt-get update && apt-get install -y ant default-mysql-client siege

RUN chmod +x /usr/local/bin/wait-for-it.sh \
&& ln -s /app/psh.phar /bin/psh

RUN pecl install pcov \
&& docker-php-ext-enable pcov

WORKDIR /var/www/shopware