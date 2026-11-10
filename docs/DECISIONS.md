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
