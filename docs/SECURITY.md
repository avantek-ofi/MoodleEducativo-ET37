# Seguridad

Riesgos principales: exposición de datos de menores, permisos excesivos, archivos maliciosos, pérdida de datos y contraseñas filtradas. El entorno usa loopback y PostgreSQL sin puerto publicado; secretos locales se excluyen de Git. El ejemplo contiene credenciales inseguras y se debe cambiar. No usar en producción. Moodle administra autenticación, sesiones, capacidades y CSRF; cualquier extensión debe verificar contexto y capacidad, validar entrada y escapar salida. Faltan políticas institucionales de retención, mensajería, archivos, respuesta a incidentes y revisión legal de Ley 25.326 y normativa aplicable.

## Registro, datos personales y Google (10/11/2026)

- El registro pide DNI y fecha de nacimiento, datos personales de menores. Los campos son privados (visibles para el titular y quienes tengan `moodle/user:viewalldetails`), pero antes de usar datos reales hace falta base legal, consentimiento de responsables, finalidad y plazo de conservación según Ley 25.326. El DNI es único en el sitio, lo que también permite averiguar si un número ya está registrado.
- El autorregistro deja crear cuentas a cualquier persona con correo. Las cuentas nuevas no acceden a cursos hasta ser matriculadas, pero conviene limitar dominios (`allowemailaddresses`, `GOOGLE_ALLOWED_DOMAINS`) o desactivar el autorregistro en producción.
- Fotos: se procesan con la API de imagen de usuario de Moodle (redimensiona y descarta el original). Se recomienda moderación y política de uso de imagen.
- Credenciales de Google solo en `.env` (excluido de Git); URI de redirección `MOODLE_URL/admin/oauth2callback.php`. El ícono del botón se carga desde `accounts.google.com`.
- Mailpit y `seed_demo.php` son solo para desarrollo: en producción dejar `MOODLE_DEMO_PASSWORD` vacío y configurar un SMTP real.
- Ejecutar PHP en el contenedor como `www-data`; como root deja archivos de caché que Apache no puede leer.

