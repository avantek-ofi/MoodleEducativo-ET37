# Reglas del proyecto
- Moodle 5.3 es fuente de verdad; no modificar su núcleo. Extensiones en moodle-custom/.
- No usar datos reales ni conectar servidores institucionales sin autorización.
- Ejecutar `docker compose config` y las pruebas relevantes; documentar lo no verificado.
- Mantener secretos y datos persistentes fuera de Git. Publicar solo `public/`.
- Comprobar capacidades y contexto, CSRF, salida escapada y consultas parametrizadas.
- Reutilizar Moodle antes de agregar componentes. Interfaz accesible y móvil.
- Consultar README.md y docs/PROGRESS.md al retomar; registrar decisiones en docs/DECISIONS.md.
- Desarrollo: copiar .env.example a .env, configurar secretos y usar `docker compose up --build -d`.
- No declarar terminado un flujo sin prueba real de extremo a extremo.
