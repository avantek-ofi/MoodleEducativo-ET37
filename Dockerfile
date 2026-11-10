FROM php:8.3-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends git libpq-dev libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libxml2-dev libonig-dev libxslt1-dev libldap2-dev libcurl4-openssl-dev libssl-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j2 pgsql pdo_pgsql intl zip gd soap exif opcache mbstring xsl ldap \
    && rm -rf /var/lib/apt/lists/*
# Moodle 5.3.0 tag dereferenced to a fixed commit.
RUN git init /var/www/moodle && cd /var/www/moodle \
    && git fetch --depth 1 https://github.com/moodle/moodle.git 42622298fe06f9626d988d60b2bf589bd8f850e8 \
    && git checkout --detach FETCH_HEAD \
    && rm -rf .git
COPY docker/config.php /var/www/moodle/config.php
COPY docker/php.ini /usr/local/etc/php/conf.d/aula.ini
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY moodle-custom/theme/et37 /var/www/moodle/public/theme/et37
COPY moodle-custom/local/aulaet37 /var/www/moodle/public/local/aulaet37
RUN mkdir -p /var/moodledata && chown -R www-data:www-data /var/moodledata
WORKDIR /var/www/moodle
COPY scripts/install.sh /usr/local/bin/aula-install
