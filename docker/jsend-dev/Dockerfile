FROM ubuntu:22.04

ARG PHP_VERSION
ENV TZ=Europe/Amsterdam
ENV DEBIAN_FRONTEND=noninteractive

RUN sed --in-place --regexp-extended "s/(\/\/)(archive\.ubuntu)/\1nl.\2/" /etc/apt/sources.list

RUN apt-get update && apt-get install -y \
    gosu \
    software-properties-common

RUN LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php

RUN apt-get update && apt-get install -y -o Dpkg::Options::="--force-confnew" \
    curl \
    git \
    php$PHP_VERSION-ast \
    php$PHP_VERSION-cli \
    php$PHP_VERSION-curl \
    php$PHP_VERSION-mbstring \
    php$PHP_VERSION-common \
    php$PHP_VERSION-readline \
    php$PHP_VERSION-xdebug \
    php$PHP_VERSION-xml \
    php$PHP_VERSION-zip \
    unzip \
    zip \
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

COPY scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
WORKDIR /usr/src
CMD ["/bin/bash"]