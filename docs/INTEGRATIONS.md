# Integraciones

Evaluar LTI 1.3 y APIs oficiales para Eduplay cuando se conozcan sus capacidades, modelo de identidad y devolución de resultados. No compartir contraseñas de estudiantes. Definir autorización, minimización de datos y pruebas antes de cualquier integración.

## Google OAuth 2

Disponible mediante el servicio estándar de Moodle. Pasos: crear un cliente OAuth "Aplicación web" en Google Cloud Console, autorizar la URI `http://localhost:8080/admin/oauth2callback.php` (o la de `MOODLE_URL`), copiar ID y secreto a `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` en `.env`, opcionalmente `GOOGLE_ALLOWED_DOMAINS`, y ejecutar el instalador. Solo se probó la redirección con credenciales falsas; el ingreso real con una cuenta de Google queda pendiente. Sin otros conectores activos.

## GitHub (11/11/2026)

- Perfil: campo opcional "Usuario de GitHub" (Datos personales), enlazado a `https://github.com/<usuario>`, visible para la persona y sus docentes. Las entregas de trabajos prácticos piden la dirección del repositorio en el texto en línea.
- Inicio de sesión y vinculación: crear una OAuth App en GitHub > Settings > Developer settings > OAuth Apps con "Authorization callback URL" `MOODLE_URL/admin/oauth2callback.php`, copiar Client ID y secreto en `GITHUB_CLIENT_ID` y `GITHUB_CLIENT_SECRET` y ejecutar el instalador. Aparece "Acceder con GitHub" y, en Preferencias > Inicios de sesión vinculados, cada persona puede vincular su cuenta. Permisos pedidos: `read:user user:email` (solo lectura del perfil). GitHub solo entrega el correo si es público; sin correo público Moodle no puede iniciar la sesión. Las cuentas nuevas creadas por GitHub requieren confirmar el correo.
- Verificado solo con credenciales falsas (servicio, botón y redirección).

## Google Drive (11/11/2026)

Con el servicio de Google configurado, el instalador activa el repositorio "Google Drive": en el selector de archivos (entregas, recursos, foros) se puede elegir un archivo de Drive como copia o como enlace. En Google Cloud hay que habilitar la "Google Drive API" en el mismo proyecto del cliente OAuth y, si la pantalla de consentimiento está en modo prueba, agregar los usuarios de prueba. Sin verificar con una cuenta real.

## CodeRunner y Jobe (11/11/2026)

Preguntas de programación con corrección automática dentro de cuestionarios. Crear: Agregar actividad > Cuestionario > Banco de preguntas > Crear una nueva pregunta > CodeRunner; elegir el tipo (por ejemplo `python3`, `c_program`, `c_function`, `java_method`, `nodejs`, `php`), escribir el enunciado, una respuesta modelo y los casos de prueba (código o entrada estándar y salida esperada). Usar el comportamiento "Modo adaptativo" para permitir reintentos con penalización. El código se ejecuta en el servicio `jobe` de `compose.yaml` (nunca en el servidor público de CodeRunner).
