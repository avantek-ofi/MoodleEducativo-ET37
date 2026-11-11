#!/bin/sh
set -eu
# Ejecutar dentro del contenedor como www-data. Es idempotente: instala si hace falta,
# aplica actualizaciones de complementos y la configuración de Aula ET37.
if php admin/cli/cfg.php --name=version >/dev/null 2>&1; then
    echo 'Moodle ya está instalado; se aplican actualizaciones de complementos.'
    php admin/cli/upgrade.php --non-interactive
else
    php admin/cli/install_database.php --agree-license --lang=es --fullname='Aula ET37' --shortname='Aula ET37' --adminuser=admin --adminpass="$MOODLE_ADMIN_PASSWORD" --adminemail=admin@example.invalid
    php admin/cli/cfg.php --name=timezone --set=America/Argentina/Buenos_Aires
fi
php admin/cli/cfg.php --name=theme --set=et37
php public/local/aulaet37/cli/setup.php
php public/local/aulaet37/cli/institution.php
php public/local/aulaet37/cli/security.php
if [ -n "${MOODLE_DEMO_PASSWORD:-}" ]; then
    php public/local/aulaet37/cli/seed_demo.php --confirm
    php public/local/aulaet37/cli/seed_institution_demo.php --confirm
    php public/local/aulaet37/cli/seed_classrooms_demo.php --confirm
fi
