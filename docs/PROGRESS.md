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

## 10/11/2026: exención de administradores

El administrador era redirigido a editar su perfil porque DNI y fecha eran obligatorios en Moodle. Complemento 0.2.0: los campos dejan de ser obligatorios en Moodle y el complemento los exige solo a cuentas de autorregistro y Google (registro + paso "Completá tus datos" antes de la foto).

Verificado (imagen reconstruida, actualización aplicada, campos con `required=0` en la base):

- Administrador y cuenta manual **sin** DNI ni fecha (usuarios temporales, luego borrados): entran directo a `/my/` (200).
- Registro a 375 px: la fecha ya no tiene casilla "Habilitar"; DNI vacío rechazado ("Escribí tu DNI."); fecha por defecto rechazada por edad; alta con DNI y fecha guardados; confirmación por Mailpit; ingreso directo al paso de foto.
- Cuenta `email` sin datos (simula Google): al ingresar va a "Completá tus datos"; DNI duplicado rechazado ("Este valor ya ha sido usado"); al guardar pasa al paso de foto; datos guardados en la base.
- El usuario de prueba se eliminó al terminar.

No verificado: el paso de datos con una cuenta real de Google.

## 10/11/2026: menú lateral, cajones, roles y asistencia

Implementado:

- Tema 0.3.0: menú lateral izquierdo en todas las páginas con grupos plegables (`<details>`): "Esta aula" (página, participantes, calificaciones, asistencia, finalización, informes, grupos, insignias, configuración, copia de seguridad, según permisos) seguido del índice del curso; "Mi espacio" (área personal, inicio, mis cursos, próximas entregas, calendario, calificaciones, mensajes, notificaciones, archivos privados, insignias, planes de aprendizaje, blogs, perfil, preferencias); "Mis aulas" (hasta 12 aulas matriculadas y "Explorar todas las aulas"); "Gestión" (administración, cursos y categorías, crear aula, usuarios, cohortes, roles, registros, según permisos). Abierto por defecto en escritorio.
- Cajón de bloques derecho abierto por defecto; sin los bloques Navegación/Administración forzados.
- `local_aulaet37/cli/institution.php` (idempotente, lo llama `aula-install`): roles Rector/a, Vicerrector/a, Director/a, Regente, Coordinador/a de área, Preceptor/a; nombres Docente/Docente sin permiso de edición/Estudiante/Gestor/a de aulas; solicitudes de aulas desactivadas.
- `cli/seed_institution_demo.php` (solo con `MOODLE_DEMO_PASSWORD`): `directivo.demo` (Rector/a en el sitio), `coordinador.demo` (categoría demo), `preceptor.demo` (PROG-DEMO y MAT-DEMO) y una actividad "Asistencia" con 15 sesiones en cada aula demo.
- `Dockerfile`: `mod_attendance` v2026090301 fijado por commit y `composer install --no-dev`.
- Documentación: `ROLES_PERMISSIONS.md`, `PLUGINS.md`.

Verificado (copiando tema y scripts al contenedor en marcha, sin reconstruir la imagen):

- `php -l` de los PHP nuevos; `docker compose config --quiet`; `institution.php` dos veces (la segunda no cambia nada).
- Menú y cajones en 1440×900 como estudiante, docente, coordinador, rector, preceptor y administrador: secciones y enlaces según rol; en un aula, "Esta aula" + índice del curso; el enlace actual marcado (`aria-current`). Ambos cajones abiertos al entrar. Sin scroll horizontal en el cajón tras corregir el ancho de la grilla.
- 360×780: cajones cerrados al cargar; el menú se abre superpuesto, el foco va al botón de cerrar, sin scroll horizontal.
- Matriz de permisos con cuentas temporales `prueba.*` y un aula temporal de otra categoría (ver `ROLES_PERMISSIONS.md`); el coordinador es redirigido a matriculación en el aula de otra área; solo Docente y Admin ven "Modo de edición".
- Causa del 500 en `/r.php/api/...`: `Trait "League\OAuth2\Server\Entities\Traits\ScopeTrait" not found` (sin `vendor/`).

No verificado:

- Reconstrucción de la imagen (`docker compose up --build -d`): la bloqueó la política de permisos de la sesión. Por eso no se probaron: `composer install` en la imagen, que se recuerde el cierre de un cajón (depende de ese arreglo), la instalación de `mod_attendance`, los permisos de asistencia de los roles ni `seed_institution_demo.php`.
- Lector de pantalla y modo oscuro del menú lateral; tablet (992–1199 px) con ambos cajones abiertos deja el contenido angosto.

## Pendiente

- Cambiar la contraseña inicial de desarrollo; credenciales reales de Google y dominio permitido; política de privacidad y consentimiento para datos de menores antes de usar DNI y fecha de nacimiento reales.
- Validar el DNI también al editar el perfil (hoy se valida en el registro y en el paso de datos, no en `user/edit.php`).
- Logo autorizado, flujos de entrega y devolución, aislamiento de permisos, respaldo probado, preparación de producción.
- El directorio ignorado `.moodle-reference/` contiene una copia temporal del código consultado; su eliminación automática fue rechazada por la política del entorno.
- No se utilizaron servidores institucionales ni datos personales reales.

## 11/11/2026: seguridad, GitHub/Drive, avisos, fechas, horario, código autocorregido y aulas de prueba

Implementado:

- **Seguridad** (`cli/security.php`, idempotente, lo llama `aula-install`): política de contraseñas (10+ caracteres, mayúscula, minúscula y número; sin repetir las 5 últimas), bloqueo de 30 min tras 10 intentos fallidos, `forcelogin`, sin invitados, usuarios protegidos, sin recordar el usuario en el acceso (computadoras compartidas), sesión de 4 h, limpieza de HTML en todo el sitio, sin `object`/`embed` ni texto confiable, confirmación de cambio de correo, cron solo por CLI, subida máxima 50 MB, bloqueo de salidas a redes privadas salvo la dirección fija de Jobe (`172.30.37.10`; corregido después de una revisión de seguridad que señaló que permitir la subred entera dejaba alcanzables `web` y `cron`). MFA con app TOTP para administradores, gestores y directivos si `MOODLE_MFA=1`. Apache: sin versión en `Server`, `nosniff`, `Referrer-Policy`, `X-Frame-Options`, `Permissions-Policy`, HSTS solo bajo HTTPS y 404 para archivos internos (README, `db/`, `tests/`, `cli/`, `composer.*`, ocultos). `config.php`: `preventexecpath`, sin instalación de complementos desde el navegador. PHP: `expose_php=Off`, `session.use_strict_mode`.
- **GitHub**: servicio OAuth 2 propio ("Acceder con GitHub" y "Inicios de sesión vinculados") desde `GITHUB_CLIENT_ID/SECRET`; campo de perfil opcional "Usuario de GitHub" (enlace a github.com, visible para la persona y sus docentes), que también completa el inicio con GitHub.
- **Google Drive**: repositorio nativo `googledocs` en el selector de archivos, activado automáticamente cuando el servicio de Google está configurado.
- **Tablero de avisos de la escuela**: foro de avisos del sitio renombrado "Avisos de la escuela", con suscripción forzosa (cada aviso llega como notificación y correo a todas las cuentas). Directivos (rol en el sitio) pueden publicar, fijar y adjuntar. Panel en el área personal con los últimos avisos y botón "Publicar aviso" para quien puede.
- **Fechas de la escuela**: eventos de calendario del sitio (visibles para todos); directivos pueden crearlos (`moodle/calendar:manageentries`). Panel con las próximas fechas.
- **Horario de clases** (`local_aulaet37` 0.3.0, tabla `local_aulaet37_slots`): cada aula carga sus bloques (día, hora, aula/laboratorio, grupo opcional, observaciones). "Mi horario" arma la semana de cada estudiante con sus matriculaciones y grupos, con "Hoy" destacado y el bloque en curso. "Horario del aula" (menú del aula) lo editan Docente y Preceptor/a; Preceptor/a, docentes, coordinación y directivos abren el horario personal de cada estudiante (también desde su perfil). Panel "Hoy" en el área personal.
- **Corrección automática de código**: CodeRunner 5.10.6 + comportamiento 1.4.8 (fijados por commit) y servidor Jobe (`trampgeek/jobeinabox` fijado por digest) en una red interna sin Internet ni acceso a la base. Lenguajes: Python 3, C, C++, Java, Node.js, PHP, Pascal, Octave. Filtro de emoticones desactivado (convertía `(n)` del código en un emoji).
- **Diseño de actividades** (tema 0.4.0, `scss/activities.scss`): secciones y actividades como tarjetas, íconos con color por propósito según la paleta, encabezado de actividad, tablas de entrega y cuestionario, bloques de código oscuros con desplazamiento horizontal, editor de CodeRunner, selector de actividades, panel del área personal y horario. Favicon (el tema hijo no lo heredaba).
- **Aulas de prueba** (`cli/seed_classrooms_demo.php`, solo con `MOODLE_DEMO_PASSWORD`): "Programación II · 5.º 1.ª" (grupos A/B, página de herramientas, foro de consultas, cuestionarios autocorregidos de Python y C, TP con entrega por GitHub/Drive) y "Laboratorio de Programación Web · 6.º 2.ª" (JavaScript autocorregido, proyecto, glosario). Estudiantes ficticios `lucia.demo` y `joaquin.demo`, horario semanal para todas las aulas demo, dos avisos y cuatro fechas de prueba.

Verificado:

- `docker compose config --quiet`; imagen reconstruida (`docker compose up --build -d`); `apache2ctl -t`; instalador repetido sobre el sitio existente sin cambios indebidos.
- **Instalación limpia en volúmenes nuevos** (proyecto Docker aparte, luego borrado): instalación, roles, seguridad y todos los datos de prueba sin errores; un ejercicio de Python corregido en ese sitio.
- Jobe: responde a Moodle, no resuelve la base (`db`) ni sale a Internet.
- Estudiante (navegador, 1366/1440 px y 375 px): panel con avisos, fechas y clases de hoy; "Mi horario" (respeta grupos: el bloque del Grupo B no aparece al Grupo A); sin scroll horizontal; hoy primero en celular.
- CodeRunner de extremo a extremo: Python (respuesta errónea → 4 pruebas fallidas; corregida → 1,00 con penalización 10 % → 0,90), C (compila y pasa 3 pruebas), JavaScript/Node.js (2 pruebas). Intento finalizado: nota 3,00/10 registrada.
- Preceptora: ve y edita "Horario del aula"; validación (fin antes que inicio) rechazada; bloque guardado y HTML ingresado limpiado; abre el horario de un estudiante.
- Permisos por CLI (`has_capability`): estudiante no ve el horario de otro; preceptor, docente, coordinador y directivo sí; solo docente y preceptor editan; solo directivo publica avisos y crea fechas del sitio; 9/9 cuentas suscriptas a los avisos.
- Aviso publicado por `directivo.demo` desde el formulario web; tras el cron del foro llegaron 8 correos a Mailpit (todas las cuentas con correo válido) y el aviso aparece en el panel del estudiante.
- GitHub con credenciales **falsas**: servicio creado con endpoints y mapeos, botón "Acceder con GitHub" en el acceso y redirección a `github.com/login/oauth/authorize` con `read:user user:email` y el callback de Moodle; luego se borró el servicio.
- Rutas internas desde el host: README, `db/install.xml`, `tests/`, `cli/`, `composer.lock`, `environment.xml` → 404; ocultos → 403; CSS, JS e imágenes del tema → 200. Cabecera `Server: Apache` sin versión. Reporte de seguridad de Moodle por CLI: sin errores salvo "rutas públicas", que no se puede medir desde dentro del contenedor (ver `SECURITY.md`).
- Sesión inválida rechazada al iniciar un cuestionario con `sesskey` falso; con `forcelogin` las páginas sin sesión redirigen al acceso; el acceso ya no recuerda el usuario anterior.

No verificado:

- Inicio de sesión real con GitHub y con Google, y el repositorio Google Drive con una cuenta real (requieren credenciales del proyecto).
- MFA (`MOODLE_MFA=1`) con una app TOTP real.
- Creación de una fecha del sitio desde la interfaz del calendario por un directivo (sí el permiso y la visualización).
- Rendimiento de Jobe con un curso completo usando cuestionarios a la vez; lector de pantalla; modo oscuro.
- En una instalación limpia, las dos aulas de prueba reciben la actividad Asistencia recién en la segunda ejecución del instalador (el script de asistencia corre antes que el de las aulas).
