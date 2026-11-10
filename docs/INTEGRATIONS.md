# Integraciones

Evaluar LTI 1.3 y APIs oficiales para Eduplay cuando se conozcan sus capacidades, modelo de identidad y devolución de resultados. No compartir contraseñas de estudiantes. Definir autorización, minimización de datos y pruebas antes de cualquier integración.

## Google OAuth 2

Disponible mediante el servicio estándar de Moodle. Pasos: crear un cliente OAuth "Aplicación web" en Google Cloud Console, autorizar la URI `http://localhost:8080/admin/oauth2callback.php` (o la de `MOODLE_URL`), copiar ID y secreto a `GOOGLE_CLIENT_ID` y `GOOGLE_CLIENT_SECRET` en `.env`, opcionalmente `GOOGLE_ALLOWED_DOMAINS`, y ejecutar el instalador. Solo se probó la redirección con credenciales falsas; el ingreso real con una cuenta de Google queda pendiente. Sin otros conectores activos.

