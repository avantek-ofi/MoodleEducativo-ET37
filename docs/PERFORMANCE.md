# Rendimiento

Objetivos: limitar memoria y procesos segÃºn mediciones reales, evitar consultas adicionales y JavaScript innecesario, usar OPcache y cron. ConfiguraciÃ³n inicial PHP: 256 MB por proceso y OPcache 128 MB; no son lÃ­mites totales de contenedor ni valores validados para el servidor escolar. Medir con `docker stats`, tiempos de respuesta, logs y disco durante pruebas con carga representativa. No existen mediciones del Huawei. No se habilitÃ³ Redis ni cachÃ© pÃºblica de pÃ¡ginas privadas.

Medición puntual con `docker stats` durante la instalación local: web 120,3 MiB, base 131,9 MiB, cron 8,5 MiB. No es una prueba de carga y no predice el rendimiento del servidor escolar. La respuesta de login sin autenticar tardó 1,05 s en una única muestra.
