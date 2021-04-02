FROM ubuntu:18.04

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install --no-install-recommends -y -o Dpkg::Options::="--force-confnew" \
        ca-certificates \
        gosu \
        software-properties-common \
        && rm -rf /var/lib/apt/lists/* /var/cache/apt/*

RUN LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php

RUN apt-get update && apt-get install --no-install-recommends -y -o Dpkg::Options::="--force-confnew" \
    curl \
    git \
    php7.4-ast \
    php7.4-cli \
    php7.4-common \
    php7.4-json \
    php7.4-mbstring \
    php7.4-readline \
    php7.4-xdebug \docker
    php7.4-xml \
    php7.4-zip \
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