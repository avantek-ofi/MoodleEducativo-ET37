# Aula ET37

Plataforma educativa basada en Moodle para la Escuela Técnica N.º 37 D.E. 11 «Hogar Naval Stella Maris». Estado: **base de desarrollo en construcción; no apta para producción**. Moodle conserva usuarios, cursos, tareas, calificaciones, calendario y permisos; el tema ET37 extiende Boost y el complemento `local_aulaet37` adapta el registro.

## Ejecución local

Requisitos: Docker Desktop con motor Linux o Docker Engine y Compose; Internet para construir. En Windows usar PowerShell y Docker Desktop con WSL2. En Ubuntu usar Docker Engine con Compose. No utilizar datos reales.

```powershell
Copy-Item .env.example .env
# Editar .env: cambiar las contraseñas y, si se quiere, completar Google OAuth 2.
docker compose up --build -d
docker compose exec --user www-data web sh /usr/local/bin/aula-install
```

Abrir http://localhost:8080 y entrar con usuario `admin` y la contraseña de `.env`. Si `MOODLE_DEMO_PASSWORD` tiene valor, el instalador crea cursos y los usuarios ficticios `docente.demo` y `estudiante.demo` con esa contraseña. Los correos (por ejemplo, la confirmación del registro) se ven en Mailpit: http://localhost:8025.

El instalador es idempotente: instala si hace falta, aplica actualizaciones de complementos y ejecuta `public/local/aulaet37/cli/setup.php`. Volver a ejecutarlo después de cambiar `.env` o de reconstruir la imagen. Ejecutar comandos PHP del contenedor siempre con `--user www-data`.

Otros comandos: `docker compose down` para detener; `docker compose logs --tail=100` para ver registros; `docker compose ps` para el estado. Los volúmenes persisten; no ejecutar `down -v` salvo que se quieran borrar todos los datos locales. En Ubuntu: `cp .env.example .env`.

## Registro e inicio con Google

- Autorregistro por correo con nombre, apellido, correo, DNI, fecha de nacimiento y, al primer ingreso, foto de perfil obligatoria.
- Google OAuth 2: crear un cliente "Aplicación web" en Google Cloud Console con la URI de redirección `http://localhost:8080/admin/oauth2callback.php`, completar `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` (y opcionalmente `GOOGLE_ALLOWED_DOMAINS`) en `.env`, reiniciar con `docker compose up -d` y volver a ejecutar el instalador. No subir credenciales a Git.

Ver [seguridad](docs/SECURITY.md) antes de usar datos reales: DNI y fecha de nacimiento de menores requieren base legal y consentimiento.

## Estructura y referencias

`moodle-custom/theme/et37` es el tema; `moodle-custom/local/aulaet37` el complemento de registro, configuración y datos ficticios; `docker/` configuración; `scripts/` herramientas; `docs/` decisiones y estado. Moodle 5.3.0 se fija por commit; PHP 8.3 y PostgreSQL 17 cumplen los [requisitos oficiales](https://moodledev.io/general/releases/5.3). La imagen usa etiquetas versionadas pero no digests: no es una construcción idéntica garantizada. Moodle, el tema y el complemento se distribuyen bajo GPL-3.0 o posterior; verificar licencias de imágenes y dependencias antes de distribuir.

Ver [progreso](docs/PROGRESS.md), [sistema visual](docs/DESIGN_SYSTEM.md), [decisiones](docs/DECISIONS.md), [arquitectura](docs/ARCHITECTURE.md), [seguridad](docs/SECURITY.md) y [hoja de ruta](docs/ROADMAP.md).
