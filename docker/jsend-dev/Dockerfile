FROM ubuntu:16.04

RUN apt-get update && apt-get install --no-install-recommends -y -o Dpkg::Options::="--force-confnew" \
        ca-certificates \
        gosu \
        software-properties-common \
        && rm -rf /var/lib/apt/lists/* /var/cache/apt/*

RUN LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php

RUN apt-get update && apt-get install --no-install-recommends -y -o Dpkg::Options::="--force-confnew" \
    curl \
    git \
    php-ast \
    php7.2-cli \
    php7.2-common \
    php7.2-curl \
    php7.2-dom \
    php7.2-gmp \
    php7.2-intl \
    php7.2-json \
    php7.2-mbstring \
    php7.2-mysql \
    php7.2-readline \
    php7.2-xdebug \
    php7.2-xml \
    php7.2-zip \
    && rm -rf /var/lib/apt/lists/* /var/cache/apt/*

# Register the COMPOSER_HOME environment variable
ENV COMPOSER_HOME /var/lib/composer

# Add global binary directory to PATH and make sure to re-export it
ENV PATH /var/lib/composer/vendor/bin:$PATH

# Allow Composer to be run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Setup the Composer installer
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
  && curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
  && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
  && php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer  && rm -rf /tmp/composer-setup.php



RUN composer global require infection/infection

# loosen up composer cache because other users need to access it later
RUN chmod -R 777 /var/lib/composer/cache


COPY scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
WORKDIR /usr/src
CMD ["/bin/bash"]