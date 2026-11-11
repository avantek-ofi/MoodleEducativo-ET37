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
# Moodle 5.3 loads some libraries only from Composer's vendor/ (league/oauth2-server for the
# REST router in r.php). Without them every /r.php/api route answers 500, for example saving
# whether a drawer is open. Versions come from Moodle's own composer.lock.
COPY --from=composer:2.8.4 /usr/bin/composer /usr/local/bin/composer
RUN cd /var/www/moodle \
    && composer install --no-dev --no-interaction --no-progress --optimize-autoloader \
    && rm /usr/local/bin/composer
# Attendance activity (mod_attendance), MOODLE_503_STABLE tag v2026090301 fixed by commit.
RUN git init /tmp/attendance && cd /tmp/attendance \
    && git fetch --depth 1 https://github.com/danmarsden/moodle-mod_attendance.git c4b2c883ab72a67159fd3b79c1a829d3fa4c9e56 \
    && git checkout --detach FETCH_HEAD \
    && rm -rf .git && cd / && mv /tmp/attendance /var/www/moodle/public/mod/attendance
# Automatic grading of programming exercises: CodeRunner question type v5.10.6 and its quiz
# behaviour v1.4.8, fixed by commit. Code runs in the separate Jobe sandbox (compose service "jobe").
RUN git init /tmp/coderunner && cd /tmp/coderunner     && git fetch --depth 1 https://github.com/trampgeek/moodle-qtype_coderunner.git e140b88d82c47ef80fedaf73d09281ff1a080136     && git checkout --detach FETCH_HEAD     && rm -rf .git && cd / && mv /tmp/coderunner /var/www/moodle/public/question/type/coderunner     && git init /tmp/crbehaviour && cd /tmp/crbehaviour     && git fetch --depth 1 https://github.com/trampgeek/moodle-qbehaviour_adaptive_adapted_for_coderunner.git c7d4df35ff3c12731a81458f64a74667d367cf69     && git checkout --detach FETCH_HEAD     && rm -rf .git && cd / && mv /tmp/crbehaviour /var/www/moodle/public/question/behaviour/adaptive_adapted_for_coderunner
COPY docker/config.php /var/www/moodle/config.php
COPY docker/php.ini /usr/local/etc/php/conf.d/aula.ini
COPY docker/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/security.conf /etc/apache2/conf-available/zz-aula-security.conf
RUN a2enmod headers && a2enconf zz-aula-security
COPY moodle-custom/theme/et37 /var/www/moodle/public/theme/et37
COPY moodle-custom/local/aulaet37 /var/www/moodle/public/local/aulaet37
RUN mkdir -p /var/moodledata && chown -R www-data:www-data /var/moodledata
WORKDIR /var/www/moodle
COPY scripts/install.sh /usr/local/bin/aula-install
