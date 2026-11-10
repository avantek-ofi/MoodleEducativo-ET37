# Arquitectura

Navegador → Apache/PHP → Moodle 5.3 → PostgreSQL 17 y Moodledata; un proceso cron separado ejecuta tareas programadas. Solo `public/` es raíz web; PostgreSQL no publica puertos. Las extensiones viven fuera del núcleo en este repositorio y se copian en la imagen. El tema hereda Boost. No existe una segunda base de usuarios ni una aplicación frontend independiente.

La imagen local y los volúmenes no constituyen una arquitectura de producción validada; Caddy, TLS y respaldo coordinado se deben diseñar y probar antes del despliegue.
