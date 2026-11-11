# Roles y permisos

Todo se arma con roles, contextos y capacidades nativos de Moodle. `public/local/aulaet37/cli/institution.php` (lo corre `aula-install`) crea los roles que faltan y concede las capacidades listadas; nunca quita permisos que un administrador agregó después ni pisa un permiso que se haya cambiado a mano. Las capacidades de complementos no instalados se omiten y se aplican en la siguiente ejecución.

## Dónde se asigna cada rol

Las **áreas** son categorías de cursos (por ejemplo "Área Técnica", "Ciencias Exactas"). Un rol asignado en una categoría alcanza a todas las aulas de esa categoría y sus subcategorías.

| Rol (nombre corto) | Dónde asignarlo | Ve | Edita | Crea aulas |
|---|---|---|---|---|
| Administrador | Administración del sitio > Usuarios > Administradores | Todo | Todo | Sí |
| Gestor/a de aulas (`manager`) | Sistema o categoría | Todo en su contexto | Todo en su contexto | Sí |
| Rector/a, Vicerrector/a, Director/a, Regente (`rector`, `vicerrector`, `director`, `regente`) | Sistema (o solo categorías, p. ej. Regente en las técnicas) | Todas las aulas, participantes, calificaciones, asistencia, informes y registros | No | Sí |
| Coordinador/a de área (`coordinador`) | La categoría de su área | Las aulas de su área (mismo alcance que directivos) | No | Sí, en su área |
| Docente (`editingteacher`) | Matriculación en el aula | Su aula | Su aula: contenido, actividades, calificar | No |
| Docente sin permiso de edición (`teacher`) | Matriculación en el aula | Su aula | Califica, no cambia el contenido | No |
| Preceptor/a (`preceptor`) | Matriculación en las aulas de su división (o la categoría de la división) | Participantes, calificaciones (lectura), progreso | Toma y corrige asistencia, crea sesiones de asistencia, registra notas sobre estudiantes | No |
| Estudiante (`student`) | Matriculación en el aula | Sus aulas | Sus entregas | No |

- Directivos y coordinadores no necesitan estar matriculados: entran por "Explorar todas las aulas" (`moodle/course:view`). No aparecen como participantes.
- Habilitar la edición a una persona puntual (por ejemplo, un coordinador en un aula): matricularla como **Docente** en esa aula. No hace falta tocar el rol.
- Quien crea un aula queda como Docente de esa aula (`creatornewroleid`), así que solo edita las aulas que creó.
- Las solicitudes de aulas (`enablecourserequests`) están desactivadas: solo crean aulas administradores, gestores, directivos y coordinadores.
- Los nombres visibles de los roles estándar (Docente, Docente sin permiso de edición, Estudiante, Gestor/a de aulas) solo se fijan si el administrador no los había renombrado.

## Capacidades concedidas

- **Supervisión** (directivos y coordinadores): ver aulas sin participar, aulas, secciones y actividades ocultas, participantes, todos los grupos, calificaciones (informe del calificador y del usuario), informes de registros, actividad, participación, progreso y finalización, insignias otorgadas, notas, entregas de tareas (`mod/assign:viewgrades`), informes de cuestionarios, encuestas, lecciones, H5P, SCORM y talleres, e informes y exportación de asistencia. Más `moodle/course:create`.
- **Preceptoría**: ver aulas, participantes, calificaciones (lectura), progreso, notas (ver y gestionar) y asistencia (tomar, cambiar, gestionar sesiones, informes, exportar y avisos).
- Ningún rol nuevo tiene `moodle/course:update`, `moodle/course:manageactivities`, `mod/assign:grade` ni `moodle/role:assign`.
- `moodle/site:viewuseridentity` (ver DNI/correo en listados) no se concede: datos de menores, decidir con la institución.

## Verificado (10/11/2026)

Con cuentas temporales en un aula de demostración y un aula de otra categoría, por `has_capability`/`can_access_course` y en el navegador:

| | Estudiante | Docente | Rector/a | Coordinador/a | Preceptor/a | Admin |
|---|---|---|---|---|---|---|
| Ver aula del área (sin matrícula) | - | - | Sí | Sí | - | Sí |
| Ver aula de otra área | - | - | Sí | - | - | Sí |
| Editar aula / actividades / calificar | - | Sí | - | - | - | Sí |
| Ver calificaciones | - | Sí | Sí | Sí | Sí | Sí |
| Crear aula en el área / en otra área | - / - | - / - | Sí / Sí | Sí / - | - / - | Sí / Sí |

El coordinador que abre un aula de otra área es enviado a la página de matriculación (sin acceso). El interruptor "Modo de edición" solo aparece para Docente y Admin.

Pendiente: permisos de asistencia en un sitio con `mod_attendance` instalado (requiere reconstruir la imagen); pruebas de mensajes entre roles y de grupos separados por división.

## Avisos, fechas y horario (11/11/2026)

| | Estudiante | Docente | Preceptor/a | Coordinador/a | Directivos | Admin/Gestor |
|---|---|---|---|---|---|---|
| Leer avisos de la escuela (los recibe por correo) | Sí | Sí | Sí | Sí | Sí | Sí |
| Publicar avisos de la escuela | - | - | - | - | Sí | Sí |
| Crear fechas de la escuela (eventos del sitio) | - | - | - | - | Sí | Sí |
| Ver su propio horario | Sí | Sí | Sí | Sí | Sí | Sí |
| Ver el horario de un estudiante de sus aulas | - | Sí | Sí | Sí (su área) | Sí (todas) | Sí |
| Editar el horario del aula | - | Sí | Sí | - | - | Sí |

Capacidades nuevas: `local/aulaet37:managetimetable` (Docente y Gestor por arquetipo; Preceptor/a por `institution.php`) y `local/aulaet37:viewtimetables` (Docente, Docente sin edición, Gestor; Preceptor/a, Coordinador/a y directivos por `institution.php`). Directivos reciben además `mod/forum:addnews`, `replynews`, `createattachment`, `pindiscussions`, `viewdiscussion` y `moodle/calendar:manageentries`. Todas las cuentas leen el foro de avisos por el rol "Usuario autenticado en la página principal" (`defaultfrontpageroleid`). Verificado por CLI con las cuentas demo (ver `PROGRESS.md`).
