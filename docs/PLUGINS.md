# Complementos

Regla: primero lo que trae Moodle; un complemento externo solo si cubre algo que Moodle no hace, tiene versión para 5.3, mantenimiento activo y se instala fijado por commit en el `Dockerfile` (nunca desde la interfaz web, para que la imagen sea reproducible).

## Instalado

- **Asistencia (`mod_attendance`)**, v2026090301, rama `MOODLE_503_STABLE`. Actividad que se agrega al aula ("Agregar actividad > Asistencia"). Se crean sesiones (por ejemplo, cada día de clase) y en cada una el docente o preceptor marca Presente, Falta injustificada, Retraso o Falta justificada para los estudiantes matriculados (o por grupo/división). Calcula porcentajes, informes por estudiante y por aula, exporta a planilla y puede avisar por correo al superar un umbral de faltas. Los estados y su peso se configuran por actividad. Recomendado: una actividad "Asistencia" por aula/división, sin calificación (Calificación: Ninguna) para que no altere el promedio.

## Recomendados (no instalados)

| Complemento | Para qué | Notas |
|---|---|---|
| `block_attendance` | Acceso rápido a "Tomar asistencia" desde el cajón de bloques | Complementa `mod_attendance`; mismo autor. |
| `local_mail` | Correo interno con bandeja (más formal que la mensajería de Moodle) | Útil para comunicaciones de preceptoría. Evaluar si alcanza con la mensajería nativa. |
| `mod_customcert` | Certificados/constancias en PDF | Constancias de cursos o talleres. |
| `block_completion_progress` | Barra de progreso por actividades | El tema ya muestra progreso en las tarjetas; útil dentro del aula. |
| `report_coursesize` | Espacio ocupado por aula | Control de almacenamiento. |
| `tool_coursearchiver` | Archivar/ocultar aulas por año lectivo | Cierre de ciclo. |
| `format_tiles` | Formato de aula con mosaicos | Solo si el formato "Temas" no alcanza. |
| `qtype_*` / `mod_hvp` | No necesarios: H5P viene integrado en Moodle. | |

Antes de instalar cualquiera: verificar compatibilidad con 5.3 en el repositorio del complemento, licencia GPL y que no envíe datos a terceros.

## Qué activar y qué desactivar (nativo)

Activar:
- **Finalización de actividades** (ya activa en las aulas de demostración) y **condiciones de acceso**: secuencias y seguimiento.
- **Grupos** por división dentro de cada aula ("Grupos separados") si una misma aula reúne varias divisiones.
- **Cohortes** (por división o año) para matricular de una vez con el método "Sincronización de cohortes".
- **Insignias** de curso, **calendario** y **notificaciones** por correo.
- **Copias de seguridad automáticas** programadas (ver `BACKUPS.md`).

Desactivar o dejar desactivado:
- **Solicitudes de aulas** (`enablecourserequests`): ya desactivado; crean aulas solo los roles autorizados.
- **Acceso de invitados** e **inscripción automática** (`enrol_self`) en aulas de menores: matriculación manual o por cohorte.
- **Blogs** y **Planes de aprendizaje/competencias** si la institución no los va a usar (aparecen en el menú lateral solo si están activos).
- **Servicios web/API móviles**: activar la app Moodle solo con una política definida.
- **Analíticas/modelos de predicción** hasta tener datos y una política de privacidad.

## Instalados el 11/11/2026

- **CodeRunner (`qtype_coderunner`)** 5.10.6 y **`qbehaviour_adaptive_adapted_for_coderunner`** 1.4.8, fijados por commit. Corrección automática de código en cuestionarios (Python, C, C++, Java, JavaScript/Node.js, PHP, Pascal, Octave). La versión declara Moodle 4.3 o posterior; el directorio de complementos aún lista hasta 5.2, por eso se probó de extremo a extremo en 5.3 (Python, C y JavaScript) antes de adoptarlo. Necesita el servidor **Jobe** (servicio `jobe`, imagen `trampgeek/jobeinabox` fijada por digest porque solo se publica `latest`).

## Evaluados para programación (no instalados)

| Complemento | Para qué | Por qué no ahora |
|---|---|---|
| `mod_vpl` (Virtual Programming Lab) | Actividad con IDE en el navegador, entregas de proyectos de varios archivos, ejecución y evaluación automática, detección de similitud. Declara Moodle 4.5–5.3. | Necesita su propio servidor de ejecución (`vpl-jail-system`) sin imagen oficial mantenida; evaluarlo para proyectos largos. |
| `tiny_codepro` | Editor de código con resaltado dentro de TinyMCE para docentes. | Opcional; los bloques `<pre><code>` ya se ven con el estilo del tema. |
| `plagiarism_*` / MOSS | Similitud entre entregas de código. | Requiere servicio externo; definir política antes. |

## Integraciones nativas activadas

- Repositorio **Google Drive** (`repository_googledocs`), con el servicio de Google.
- Servicio OAuth 2 personalizado de **GitHub** (`core\oauth2`), con `GITHUB_CLIENT_ID/SECRET`.
- Filtro de **emoticones desactivado** (rompía código en enunciados).
