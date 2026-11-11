# Decisiones

- Moodle 5.3.0 LTS: release oficial, commit `42622298fe06f9626d988d60b2bf589bd8f850e8`; PHP 8.3 y PostgreSQL 17 según requisitos oficiales.
- Tema hijo de Boost para reutilizar sus vistas y permisos; primer estilo es una base, no un dashboard terminado.
- Apache local publica exclusivamente `public/`; la publicación real requiere diseño de proxy y TLS.
- Sin datos reales, secretos en Git, permisos nuevos ni funciones ficticias.
- Sin logo hasta verificar autorización de uso; sin Redis hasta contar con mediciones.

## 10/11/2026

- Registro con el autorregistro por correo nativo (`auth_email`) y campos de perfil nativos (DNI con opción "único", fecha de nacimiento). Un complemento local (`local_aulaet37`) solo agrega validaciones y quita ciudad/país; no se modificó el núcleo. Moodle exige nombre de usuario, por eso se conserva, con ayuda y con inicio de sesión también por correo.
- La foto no se pide en el formulario de registro: ahí el usuario aún no tiene sesión y el selector de archivos de Moodle no funciona. Se pide en un paso obligatorio inmediatamente después de confirmar la cuenta (callback `after_require_login`), solo para cuentas `email` y `oauth2`; administradores y cuentas manuales quedan exentos. Se puede desactivar en la configuración del complemento.
- Google OAuth 2 con el servicio estándar de Moodle (`core\oauth2`), configurado por variables de entorno; sin credenciales no se crea. Google ya verifica el correo, así que `requireconfirmation=0`. `GOOGLE_ALLOWED_DOMAINS` permite restringir a un dominio institucional. Los usuarios de Google completan DNI y fecha en el formulario de perfil que Moodle exige por ser campos obligatorios; su foto la toma Moodle de Google si existe, y si no, se pide.
- Ícono del botón de Google: el que Moodle carga de `accounts.google.com` (comportamiento estándar); no se dibujó el logo.
- Mailpit como servicio de desarrollo para probar los correos sin enviarlos a Internet.
- Ancho del contenido: se mantiene el de Boost (830 px) por el anclaje de cajones de Moodle 5.3.
- Accesos rápidos del dashboard renderizados en el `core_renderer` del tema, agregados dentro de la cabecera nativa; solo enlazan a páginas nativas y respetan configuraciones (`messaging`, `enablemycourses`). Sin estadísticas inventadas.
- Textos de acceso corregidos con el paquete `es_local` (mismo mecanismo que la herramienta de personalización de idioma), sin pisar personalizaciones existentes.
- Paleta de 10 colores en `coursecolor1..10` para que los patrones generados de los cursos coincidan con el tema.
- `.gitattributes` fuerza LF en todo el texto (`* text=auto eol=lf`) y marca binarios: los archivos se copian a contenedores Linux y un `core.autocrlf=true` en Windows podía dejar `scripts/install.sh` o la configuración de Apache/PHP con CRLF.

## 10/11/2026: administradores sin DNI ni fecha obligatorios

- DNI y fecha de nacimiento dejan de ser campos "obligatorios" de Moodle (`required=0`, paso de actualización 2026111100). Con `required=1`, Moodle obligaba a todas las cuentas, incluido el administrador, a completarlos antes de usar el sitio. Ahora los exige el complemento solo a cuentas `email` y `oauth2`: en el registro (regla requerida y validación propia) y, si faltan, en un paso "Completá tus datos" (`/local/aulaet37/datos.php`) previo al de la foto. Administradores, cuentas manuales y sesiones "entrar como" quedan exentos. Reemplaza lo anotado arriba sobre usuarios de Google completando los datos en el formulario de perfil.
- Al no ser obligatorio, Moodle muestra la fecha con una casilla "Habilitar"; en el registro el complemento la reemplaza por un selector sin casilla en la misma posición.
- La unicidad del DNI sigue siendo la opción nativa "único" de Moodle; el paso de datos la verifica con `profile_validation`.

## 10/11/2026: menú lateral, cajones, roles y asistencia

- Menú lateral izquierdo en todas las páginas: el tema reemplaza el layout `drawers.php` de Boost (copia con cambios en `theme/et37/layout/drawers.php` y `templates/drawers.mustache`). El cajón izquierdo usa la misma preferencia que el índice del curso (`drawer-open-index`, abierto por defecto) y dentro de un aula muestra "Esta aula" y el índice del curso. Cada enlace se muestra solo si el usuario tiene la capacidad o la opción del sitio que exige la página destino; el menú no concede accesos.
- Cajón de bloques derecho abierto por defecto: se lee `drawer-open-block` con valor por defecto `true` (Boost usa `false`). Se puede cerrar y en pantallas < 992 px arranca cerrado (comportamiento de Boost).
- El `config.php` del tema no repetía `requiredblocks = ''` ni `addblockposition` de Boost (las propiedades de tema no se heredan), así que Moodle forzaba los bloques Navegación y Administración en todas las páginas. Se corrigió y `institution.php` borra esas dos instancias globales.
- Roles: nuevos roles propios sin arquetipo (no copian permisos de docente) para directivos (`rector`, `vicerrector`, `director`, `regente`), `coordinador` (solo categoría) y `preceptor`. Se separan los cuatro directivos aunque compartan permisos, para poder diferenciarlos después y que se lean bien en informes. Áreas = categorías. Ver `ROLES_PERMISSIONS.md`.
- Creación de aulas: administradores, gestores, directivos y coordinadores (en su área). Interpretación del pedido "solo admin, gestor o coordinador pueden crear; directivos también". Sacar `moodle/course:create` de un rol si no corresponde.
- Asistencia con `mod_attendance` (complemento comunitario estándar, versión oficial para 5.3) fijado por commit en el `Dockerfile`; Moodle no trae asistencia propia.
- Composer: Moodle 5.3 necesita `vendor/` (al menos `league/oauth2-server` para el router `r.php`). La imagen no lo instalaba y toda ruta `/r.php/api/...` respondía 500 (por ejemplo, guardar el estado de los cajones). El `Dockerfile` ahora corre `composer install --no-dev` con el `composer.lock` de Moodle.
- Complementos recomendados y ajustes nativos en `PLUGINS.md`.

## 11/11/2026: seguridad, integraciones, avisos, horario y código

- Avisos generales con el **foro de avisos del sitio** (nativo) y suscripción forzosa en vez de un tablero propio: ya notifica por la web, el correo y la app de Moodle, guarda historial, permite adjuntos y fijar. Publican los directivos (asignados en el sitio). Los preceptores están asignados en aulas o categorías, por eso no publican en el sitio; si se quiere, se les asigna un rol en el sitio.
- **Fechas compartidas** con eventos de calendario del sitio (nativos), visibles para todas las cuentas y en la exportación de calendario. `moodle/calendar:manageentries` para directivos también les permite crear eventos de curso en las aulas que supervisan.
- **Horario** como tabla propia mínima por aula (día, horas, grupo, aula física, observaciones): Moodle no tiene horario semanal. No se guardan listas de alumnos; el horario de cada persona se calcula con sus matriculaciones activas y grupos, así sigue los cambios de matrícula. No se generan eventos de calendario por cada clase (duplicarían el calendario y dejarían basura al cambiar el horario). Quien tiene `local/aulaet37:viewtimetables` en una de las aulas de un estudiante ve su horario completo (todas sus aulas): es lo que necesita preceptoría para ubicar a un estudiante.
- **Corrección automática**: CodeRunner (tipo de pregunta para cuestionarios, el más usado para programación en Moodle, con versión que declara Moodle 4.3+) con un servidor Jobe propio. Se descartó VPL como primera opción porque su servidor de ejecución no tiene imagen oficial mantenida; queda como alternativa (ver `PLUGINS.md`). CodeRunner por defecto envía el código a un servidor público de la Universidad de Canterbury: `setup.php` lo apunta siempre a `jobe` interno.
- Jobe en una red Docker `internal` con subred fija `172.30.37.0/24` y límites de memoria, CPU y procesos. Moodle bloquea pedidos salientes a redes privadas (SSRF); se mantiene el bloqueo de todos los rangos privados salvo esa subred. Jobe no admite `no-new-privileges` porque usa `sudo` para ejecutar cada trabajo con un usuario sin privilegios.
- Filtro de emoticones desactivado en todo el sitio: altera el código (`(n)`, `8)`, `:P`).
- **GitHub** con un servicio OAuth 2 personalizado de Moodle (sin código propio). GitHub no es OpenID Connect y solo informa el correo si es público: quien lo tenga privado no puede entrar con GitHub, pero sí cargar su usuario en el perfil. El identificador es el id numérico (el nombre de usuario puede cambiar). Sin URL base para que Moodle no intente el descubrimiento OpenID.
- **Google Drive** con el repositorio nativo `googledocs`, que reutiliza el servicio de Google ya configurado; requiere habilitar la API de Drive en el proyecto de Google Cloud.
- Seguridad solo con ajustes nativos de Moodle, Apache y PHP; MFA queda opcional (`MOODLE_MFA`) para no bloquear el desarrollo local, recomendado en producción.
- Panel del área personal renderizado por el tema (como los accesos rápidos) con datos del complemento local cuando está instalado; el tema sigue funcionando sin él.
