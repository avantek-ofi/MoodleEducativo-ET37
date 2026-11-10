# Sistema visual

Dirección: *Playful Flat Design* + *Modern Educational Dashboard* aplicada sobre los componentes reales de Moodle 5.3 (tema hijo de Boost). No hay maquetas aparte: todo se ve en el sitio en ejecución.

## Tokens

Definidos en `moodle-custom/theme/et37/scss/pre.scss` (variables SCSS que Bootstrap/Boost toman antes de compilar) y expuestos como propiedades CSS `--et37-*` en `et37.scss` para que puedan cambiar en modo oscuro. Contraste WCAG medido con fórmula de luminancia relativa.

| Token | Color | Uso | Contraste |
|---|---|---|---|
| violet | `#5b3fd1` | acción primaria, enlaces, pestaña activa | 6,8:1 sobre blanco; 6,2:1 sobre lavanda |
| violet-700 | `#4329b0` | hover, texto sobre violet-50 | 9,6:1 blanco; 8,3:1 sobre violet-50 |
| violet-50 | `#efebff` | superficies tintadas, chips, pestaña activa | — |
| teal | `#0f766e` | progreso, calendario | 5,5:1 blanco; 4,8:1 sobre teal-50 |
| pink | `#be185d` | calificaciones | 6,0:1 blanco; 5,1:1 sobre pink-50 |
| orange | `#c2410c` | foco de teclado, "Último paso" | 5,2:1 blanco; 4,7:1 lavanda; 4,5:1 sobre orange-50 |
| lavender | `#f5f3fe` | fondo de página | — |
| ink | `#23213a` | texto | 14,2:1 sobre lavanda |
| muted | `#5c5975` | texto secundario | 6,7:1 blanco; 6,1:1 lavanda |
| line | `#e3def6` | bordes de tarjetas | decorativo |

Los acentos claros (`*-50`) solo se usan como fondo de íconos o chips; el texto sobre ellos usa el tono oscuro correspondiente. También se ajustan los tokens del sistema de diseño de Moodle (`--mds-*`) para que los componentes React (navegación principal en píldoras) usen el violeta y el foco naranja.

## Forma, tipografía y espaciado

- Tipografía: Noto Sans que ya distribuye Boost (sin fuentes externas). Encabezados 700/800, etiquetas de formulario 600.
- Radios: tarjetas 1rem, cabecera del dashboard 1,25rem, botones 0,75rem, campos 0,625rem, chips en píldora.
- Sombras discretas en dos niveles (`--et37-shadow`, `--et37-shadow-hover`). Sin gradientes de fondo; la única forma radial es el punto decorativo del isotipo en la barra.
- Ancho de contenido: se mantiene el de Boost (830 px) porque Moodle 5.3 ancla los cajones laterales a ese ancho; ensancharlo solo en el dashboard superponía el botón del cajón con las tarjetas.

## Componentes

- **Acceso** (`templates/core/login_layout.mustache`, `core/login_panel.mustache`): panel izquierdo violeta plano con figuras geométricas SVG (`aria-hidden`) y cuatro funciones reales de la plataforma; se quitaron las estadísticas genéricas de Moodle ("500.000.000 usuarios") y la foto generada por IA. En < 992 px el panel se oculta y aparece una cabecera compacta con la marca. Formulario en tarjeta blanca; campos de 3rem de alto; botón de Google con el mismo estilo.
- **Registro y paso de foto**: usan el mismo layout. El paso de foto tiene etiqueta "Último paso", tres consejos y el selector de archivos nativo.
- **Dashboard** (`classes/output/core_renderer.php`, `templates/dashboard_quicklinks.mustache`): la cabecera nativa (título y saludo de Moodle) se convierte en tarjeta con fecha y accesos rápidos a páginas nativas: Próximas entregas, Calendario, Calificaciones, Mensajes (si la mensajería está activa) y Mis cursos (solo si `enablemycourses` está activo). Cada acceso tiene un tono de la paleta. En móvil pasan a una grilla 2×2 con ícono arriba.
- **Tarjetas de curso** (`templates/block_myoverview/progress-bar.mustache`): imagen o patrón generado por Moodle con la paleta de 10 colores configurada en `coursecolor1..10`, nombre en negrita, categoría como chip y barra de progreso (decorativa, `aria-hidden`; el porcentaje se sigue leyendo como texto).
- **Estados vacíos**: los mensajes nativos (`data-region="empty-message"`) se muestran en un panel con borde punteado y fondo lavanda.
- **Navegación**: barra blanca con borde suave e isotipo geométrico; píldoras activas en violet-50; cajones con ítems redondeados; breadcrumb que envuelve en pantallas chicas.

## Accesibilidad

- Foco visible en todo control: contorno naranja de 3 px con separación de 2 px (verificado con Tab en el acceso).
- `prefers-reduced-motion: reduce` anula transiciones y el desplazamiento de tarjetas al pasar el mouse (regla presente en el CSS compilado).
- Íconos decorativos con `aria-hidden`; la navegación de accesos rápidos es un `nav` con `aria-label`.
- Adaptable desde 360 px sin scroll horizontal en acceso, registro, foto y dashboard.

## Marca

No se usa el escudo institucional (sin recurso autorizado en el proyecto). La marca es tipográfica: "ET37" dentro de un cuadrado redondeado más el nombre "Aula ET37". Ver `BRANDING.md`.

## Pendiente

- Modo oscuro: hay tokens oscuros, pero los modos de color de Boost están desactivados en el sitio y no se verificó con ellos activos.
- Lector de pantalla real (NVDA/Orca) y zoom 200 %.
- Revisión con estudiantes y docentes.
