FROM php:8.4.3-fpm AS image
RUN useradd -l -r -u 1139400047 gitlab-runner \
    && apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    procps \
    zip \
    unzip \
    && mkdir -p -m 0600 ~/.ssh \
    && ssh-keyscan rsa git.byside-nos.local >> ~/.ssh/known_hosts \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets intl \
    && pecl install -o -f redis \
    && rm -rf /tmp/pear \
    && echo "extension=redis.so" > /usr/local/etc/php/conf.d/docker-php-ext-redis.ini \
    && echo 'memory_limit=2G' >> /usr/local/etc/php/php.ini
WORKDIR /app

FROM image AS base
COPY . .
RUN --mount=type=ssh bin/composer install --no-dev --optimize-autoloader

FROM image AS dev
RUN pecl install xdebug-3.4.1 \
    && docker-php-ext-enable xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9002" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

FROM base AS ci
RUN pecl install xdebug-3.4.1 \
    && docker-php-ext-enable xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN --mount=type=ssh bin/composer install

FROM base AS prod
RUN mkdir -p /app/cache /app/log \
    && chown -R www-data:www-data /app/cache /app/log

FROM prod AS issue_print
RUN apt-get update && apt-get install -y cron

COPY cronjob /etc/cron.d/my-cronjob
COPY bin/entrypoint/issue_print_entrypoint.sh /usr/bin/issue_print_entrypoint.sh

RUN chmod 0644 /etc/cron.d/my-cronjob \
    && crontab /etc/cron.d/my-cronjob && touch /var/log/cron.log

ENTRYPOINT ["/usr/bin/issue_print_entrypoint.sh"]
