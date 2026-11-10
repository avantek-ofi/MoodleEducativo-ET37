# Progreso

Fecha: 10/11/2026. Base local instalada; MVP incompleto.

## Estado anterior

- Investigación: Moodle 5.3 LTS requiere PHP 8.3, PostgreSQL 17, sodium y max_input_vars >= 5000. Sitio institucional consultado; autorización del escudo pendiente.
- Compose local con PostgreSQL, PHP/Apache, cron y volúmenes; tema hijo inicial de Boost; instalador, español, zona horaria y documentación. Moodle 5.3.0 fijado por commit.
- Verificado entonces: construcción de imagen; instalación limpia en volúmenes nuevos; PostgreSQL saludable; `docker compose config --quiet`; login de administrador por HTTP y `/my/` 200; cron correcto.

## 10/11/2026: diseño, registro y Google

Implementado:

- Tema `theme_et37` 0.2.0: tokens de diseño, estilos de navegación, tarjetas, botones, formularios, estados vacíos, calendario y línea de tiempo; nueva pantalla de acceso; cabecera del dashboard con fecha y accesos rápidos a páginas nativas; barra de progreso en tarjetas de curso. Detalle en `DESIGN_SYSTEM.md`.
- Complemento `local_aulaet37`: campos de perfil nativos DNI (único) y fecha de nacimiento, visibles en el registro; validación de DNI (7 u 8 dígitos sin puntos) y de edad (10–100 años); se quitan ciudad y país del registro; paso obligatorio de foto de perfil al primer ingreso para cuentas de autorregistro o Google; ajuste `requirephoto`; proveedor de privacidad.
- `cli/setup.php` (idempotente): registro por correo, inicio con correo, colores de cursos, textos `es_local`, SMTP y Google OAuth 2 desde variables de entorno.
- `cli/seed_demo.php`: 5 cursos, docente y estudiante ficticios con tareas, fechas y avance real de finalización. Solo si existe `MOODLE_DEMO_PASSWORD`.
- Servicio Mailpit (solo desarrollo, `127.0.0.1:8025`) para capturar los correos de confirmación.
- `scripts/install.sh` ahora es idempotente: instala o actualiza, configura y genera datos ficticios.

Verificado (imagen reconstruida con `docker compose up --build -d`):

- `docker compose config --quiet`; `php -l` de todos los PHP del tema y del complemento; instalador repetido sobre el sitio existente; actualización de complementos aplicada.
- Acceso en 1366×860 y 360×780: panel nuevo, campos, foco visible, botón de mostrar contraseña, sin scroll horizontal. Orden de tabulación: saltar al contenido → usuario → contraseña → mostrar → olvido → Acceder → Crear cuenta → Google → idioma; foco naranja de 3 px en cada uno.
- Inicio de sesión con usuario y con correo (`estudiante.demo`), por navegador y por HTTP; `/my/`, calendario, calificaciones y mensajes responden 200.
- Dashboard de estudiante en escritorio y 360 px: accesos rápidos, tarjetas con datos reales (33 %, 66 %, 0 %), línea de tiempo con entregas, notificaciones, calendario mensual y de próximos eventos, página de curso.
- Registro de extremo a extremo (360 px): errores de DNI con puntos y de fecha por defecto; DNI duplicado rechazado por Moodle ("Este valor ya ha sido usado"); alta correcta; correo recibido en Mailpit; enlace de confirmación; redirección obligatoria al paso de foto; subida real por el área de arrastre de Moodle (imagen generada en el navegador); regreso al dashboard con foto. En la base: `auth=email`, `confirmed=1`, DNI y fecha guardados, `picture` asignado.
- Google OAuth 2 con credenciales **falsas**: el servicio se crea, aparece "Acceder con Google" y la redirección va a `accounts.google.com/o/oauth2/v2/auth` con `redirect_uri=http://localhost:8080/admin/oauth2callback.php` y `openid profile email`. Luego se eliminó el servicio falso.
- Contrastes de la paleta calculados (todos ≥ 4,5:1 para texto). Regla `prefers-reduced-motion` presente en el CSS compilado.

Errores encontrados y corregidos durante la prueba: faltaba `filelib.php` en el paso de foto (error al abrirlo); comandos de verificación ejecutados como root dejaron caché de `moodledata` sin permisos para Apache (se corrigió el dueño; ejecutar PHP en el contenedor siempre con `--user www-data`); campos de acceso angostos por una regla previa de Moodle; botón de contraseña desalineado en móvil; breadcrumb desbordado en 360 px; "Mis cursos" apuntaba a una página desactivada.

No verificado:

- Inicio de sesión real con Google (requiere credenciales del proyecto en Google Cloud), creación de cuenta por Google ni completado de DNI/fecha por esos usuarios.
- Instalación limpia en volúmenes nuevos con estos cambios (solo se probó la actualización del sitio existente).
- Vista de docente y administrador después de los últimos ajustes; modo oscuro; lector de pantalla; `prefers-reduced-motion` emulado en el navegador.
- PHPUnit/Behat: no hay infraestructura de pruebas configurada.

## Pendiente

- Cambiar la contraseña inicial de desarrollo; credenciales reales de Google y dominio permitido; política de privacidad y consentimiento para datos de menores antes de usar DNI y fecha de nacimiento reales.
- Validar el DNI también al editar el perfil (hoy solo se valida en el registro).
- Logo autorizado, flujos de entrega y devolución, aislamiento de permisos, respaldo probado, preparación de producción.
- El directorio ignorado `.moodle-reference/` contiene una copia temporal del código consultado; su eliminación automática fue rechazada por la política del entorno.
- No se utilizaron servidores institucionales ni datos personales reales.
