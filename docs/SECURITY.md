# Seguridad

Riesgos principales: exposición de datos de menores, permisos excesivos, archivos maliciosos, pérdida de datos y contraseñas filtradas. El entorno usa loopback y PostgreSQL sin puerto publicado; secretos locales se excluyen de Git. El ejemplo contiene credenciales inseguras y se debe cambiar. No usar en producción. Moodle administra autenticación, sesiones, capacidades y CSRF; cualquier extensión debe verificar contexto y capacidad, validar entrada y escapar salida. Faltan políticas institucionales de retención, mensajería, archivos, respuesta a incidentes y revisión legal de Ley 25.326 y normativa aplicable.

## Registro, datos personales y Google (10/11/2026)

- El registro pide DNI y fecha de nacimiento, datos personales de menores. Los campos son privados (visibles para el titular y quienes tengan `moodle/user:viewalldetails`), pero antes de usar datos reales hace falta base legal, consentimiento de responsables, finalidad y plazo de conservación según Ley 25.326. El DNI es único en el sitio, lo que también permite averiguar si un número ya está registrado.
- El autorregistro deja crear cuentas a cualquier persona con correo. Las cuentas nuevas no acceden a cursos hasta ser matriculadas, pero conviene limitar dominios (`allowemailaddresses`, `GOOGLE_ALLOWED_DOMAINS`) o desactivar el autorregistro en producción.
- Fotos: se procesan con la API de imagen de usuario de Moodle (redimensiona y descarta el original). Se recomienda moderación y política de uso de imagen.
- Credenciales de Google solo en `.env` (excluido de Git); URI de redirección `MOODLE_URL/admin/oauth2callback.php`. El ícono del botón se carga desde `accounts.google.com`.
- Mailpit y `seed_demo.php` son solo para desarrollo: en producción dejar `MOODLE_DEMO_PASSWORD` vacío y configurar un SMTP real.
- Ejecutar PHP en el contenedor como `www-data`; como root deja archivos de caché que Apache no puede leer.

## Endurecimiento (11/11/2026)

Se aplica con `cli/security.php` (idempotente; sus valores pisan cambios manuales de esos ajustes):

| Área | Ajuste |
|---|---|
| Contraseñas | 10+ caracteres, mayúscula, minúscula y número; máx. 3 caracteres iguales seguidos; no repetir las últimas 5; al cambiarla se cierran las otras sesiones. |
| Fuerza bruta | Bloqueo de la cuenta 30 min tras 10 intentos fallidos en 30 min. |
| Acceso | `forcelogin`, sin botón de invitado, usuarios protegidos en "olvidé mi contraseña", perfiles e imágenes solo con sesión, no se recuerda el usuario (computadoras de laboratorio compartidas). |
| Sesión y cookies | 4 h de inactividad; cookie `secure` si `MOODLE_URL` es https; sin incrustar el sitio en marcos ajenos; `Referrer-Policy`. |
| Contenido | Limpieza de HTML en todo el sitio, sin `object`/`embed`, sin texto confiable, sin temas por usuario/curso; subida máx. 50 MB. |
| Correo | Confirmación al cambiar el correo; envíos solo desde la dirección no-reply. |
| Salidas (SSRF) | Bloqueados todos los rangos privados y `localhost`, salvo `172.30.37.10` (dirección fija de Jobe; `web` y `cron`, que comparten esa red, quedan bloqueados); solo puertos 80 y 443. |
| Cron | Solo por línea de comandos (contenedor `cron`). |
| MFA | `MOODLE_MFA=1`: app TOTP obligatoria para administradores, gestores y directivos (7 días de gracia para configurarla). |

Además: `config.php` con `preventexecpath` y sin instalación/actualización de complementos desde el navegador (se instalan solo en el `Dockerfile`, fijados por commit). Apache (`docker/security.conf`, cargado después del `security.conf` de Debian): `ServerTokens Prod`, sin `TRACE`, `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, HSTS solo bajo HTTPS, 403 para archivos ocultos y 404 para README/UPGRADING, `environment.xml`, `db/`, `tests/`, `cli/`, `vendor/`, `composer.*` y similares (lista del chequeo "rutas públicas" de Moodle). PHP: `expose_php=Off`, sin mostrar errores, `session.use_strict_mode`.

**Código de estudiantes**: se ejecuta solo en Jobe, en una red Docker interna (sin Internet, sin acceso a la base ni al correo), con usuarios sin privilegios de Jobe y límites de 2 GB, 2 CPU y 512 procesos. El contenedor web puede hablar con Jobe; Jobe puede llegar a la web por esa red, como cualquier navegador.

**Reporte de seguridad de Moodle**: el chequeo "Comprobar todas las rutas públicas" consulta `MOODLE_URL` desde el servidor; en local (`localhost:8080` dentro del contenedor no es el sitio, y `localhost` está bloqueado para salidas) siempre da error. Se verificó desde el host con las mismas rutas (todas 404/403). Las advertencias de riesgo XSS y de copias con datos de usuario son las normales de los roles Docente y Gestor. En producción: HTTPS con proxy, `$CFG->sslproxy` si corresponde, `MOODLE_MFA=1`, dominio permitido para registro y Google, y revisar el reporte desde Administración del sitio > Informes > Resumen de seguridad.
