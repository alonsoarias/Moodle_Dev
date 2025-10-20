# Login personalizado de Educam para Moodle (v2.2.0)

Este plugin local sustituye la pantalla estándar de acceso de Moodle por una experiencia totalmente personalizada para la plataforma Educam. Incluye dos diseños responsivos basados en plantillas Mustache, estilos CSS independientes y compatibilidad con Google reCAPTCHA v3, manteniendo la autenticación y la seguridad nativa de Moodle.

## Compatibilidad
- Moodle 3.9 o superior (`$plugin->requires = 2020061500`)
- PHP 7.2 o superior
- Navegadores modernos con soporte para flexbox, grid y ES6

## Características principales
- Dos diseños («layout1» y «layout2») con estructuras visuales diferenciadas y totalmente responsivas.【F:local/educamlogin/templates/layout1.mustache†L1-L126】【F:local/educamlogin/templates/layout2.mustache†L1-L116】
- Estilos encapsulados por layout con variables CSS que reflejan los colores configurables desde la administración.【F:local/educamlogin/styles/layout1.css†L1-L110】【F:local/educamlogin/styles/layout2.css†L1-L74】
- Configuración completa desde el área de administración: activación, selección de layout, textos, paleta de colores, subida de imágenes, claves reCAPTCHA y lista blanca de IP.【F:local/educamlogin/settings.php†L12-L222】
- Integración automática con la URL de login alternativo de Moodle para redirigir a `/local/educamlogin/index.php` cuando el plugin está activo.【F:local/educamlogin/lib.php†L14-L44】【F:local/educamlogin/db/install.php†L12-L16】
- Gestión de archivos mediante áreas propias (`ed_background_image`, `ed_team_photo`, `ed_logo_educam`, `ed_logo_americas`) con imágenes de respaldo («fallbacks») cuando no hay archivos cargados.【F:local/educamlogin/lib.php†L46-L115】
- Protección adicional con Google reCAPTCHA v3 configurable (site key, secret, acción, umbral y lista blanca de IP para omitir el captcha).【F:local/educamlogin/lib.php†L117-L339】【F:local/educamlogin/settings.php†L199-L222】
- Texto y etiquetas disponibles en español e inglés mediante archivos de idioma dedicados.【F:local/educamlogin/lang/es/local_educamlogin.php†L1-L80】【F:local/educamlogin/lang/en/local_educamlogin.php†L1-L80】

## Estructura del plugin
```
local/educamlogin/
├── index.php           # Punto de entrada del login personalizado
├── lib.php             # Funciones de apoyo, reCAPTCHA y gestión de archivos
├── settings.php        # Definición de ajustes administrativos
├── version.php         # Información de versión, release y requisitos
├── db/
│   ├── access.php      # Capacidades (view/configure)
│   ├── install.php     # Sincroniza alternateloginurl en la instalación
│   └── uninstall.php   # Restaura alternateloginurl al desinstalar
├── lang/
│   ├── en/local_educamlogin.php
│   └── es/local_educamlogin.php
├── styles/
│   ├── layout1.css
│   └── layout2.css
└── templates/
    ├── layout1.mustache
    └── layout2.mustache
```

## Instalación
1. Copia la carpeta `educamlogin` dentro de `local/` en tu instalación de Moodle.
2. Accede a **Administración del sitio → Notificaciones** para ejecutar la instalación.
3. Configura el plugin en **Administración del sitio → Plugins → Plugins locales → Login personalizado Educam**.

Durante la instalación se actualizará automáticamente la configuración `alternateloginurl` de Moodle para apuntar al nuevo formulario personalizado. Al desactivar el plugin se restaurará el valor anterior o se eliminará si no existía.【F:local/educamlogin/lib.php†L14-L44】

## Actualización desde versiones anteriores
1. Realiza una copia de seguridad de la carpeta actual `local/educamlogin`.
2. Sustituye los archivos por la nueva versión y limpia la caché de Moodle si es necesario.
3. Visita **Administración del sitio → Notificaciones** para aplicar cambios en la base de datos.
4. Revisa los ajustes, ya que todas las claves usan el prefijo `ed_` y pueden haberse añadido nuevas opciones.

## Configuración
Todos los ajustes se almacenan con prefijo `ed_` dentro de `local_educamlogin`. Las secciones disponibles en la interfaz de administración son:

### General
- **Habilitar login personalizado (`ed_enabled`)**: activa o desactiva el reemplazo de la pantalla de acceso estándar.【F:local/educamlogin/settings.php†L21-L33】

### Diseño
- **Layout (`ed_layout`)**: selecciona `layout1` (panel lateral con imagen) o `layout2` (panel centrado sobre imagen de fondo).【F:local/educamlogin/settings.php†L35-L58】

### Imágenes
Sube hasta un archivo por área; se admiten formatos JPG, PNG, WEBP (y SVG en los logos). Si no se proporciona una imagen, el plugin mostrará automáticamente un placeholder remoto seguro.
- `ed_background_image`
- `ed_team_photo`
- `ed_logo_educam`
- `ed_logo_americas`【F:local/educamlogin/settings.php†L60-L110】【F:local/educamlogin/lib.php†L69-L115】

### Paleta de colores
Selector de colores con vista previa instantánea para fondo, gradiente del panel, campos, enlaces y botón principal. Las selecciones alimentan variables CSS utilizadas en ambos layouts.【F:local/educamlogin/settings.php†L112-L168】【F:local/educamlogin/styles/layout1.css†L18-L109】

### Textos
Personaliza el mensaje de bienvenida, el pie de página (con soporte para HTML) y la URL de recuperación de contraseña. Si dejas la URL vacía, el enlace no se mostrará en la interfaz.【F:local/educamlogin/settings.php†L170-L205】【F:local/educamlogin/lib.php†L244-L307】

### Seguridad (reCAPTCHA)
Configura Google reCAPTCHA v3 indicando `sitekey`, `secretkey`, nombre de acción, puntuación mínima aceptable y direcciones IP de confianza que evitarán el desafío. Si faltan credenciales válidas la verificación fallará y se notificará mediante `debugging`.【F:local/educamlogin/settings.php†L199-L222】【F:local/educamlogin/lib.php†L117-L339】【F:local/educamlogin/index.php†L30-L75】

## Descripción de los layouts
### Layout 1 – Dos columnas
- Imagen destacada en la mitad izquierda con superposición opcional.
- Panel de acceso con gradiente configurable y logo de Educam en la esquina superior.【F:local/educamlogin/templates/layout1.mustache†L37-L123】
- Botón para mostrar/ocultar contraseña y enlace opcional de recuperación.
- Elementos decorativos («blobs») cuando no se carga una imagen de fondo.【F:local/educamlogin/templates/layout1.mustache†L24-L55】

### Layout 2 – Panel centrado
- Imagen de fondo a pantalla completa (obligatoria para la experiencia deseada).
- Panel flotante centrado con logos, mensaje de bienvenida y controles accesibles.【F:local/educamlogin/templates/layout2.mustache†L24-L108】
- El gradiente del panel reutiliza las mismas variables de color que Layout 1, garantizando consistencia visual.【F:local/educamlogin/styles/layout2.css†L5-L71】

## Gestión de archivos e imágenes
Los archivos subidos se almacenan en el contexto del sistema y se exponen a través de `pluginfile.php`. Solo las áreas prefijadas con `ed_` están permitidas, lo que evita accesos no deseados. Cuando no existe un archivo, se usa automáticamente una URL de fallback definida en `local_educamlogin_get_fallback_image()` (Unsplash/dummyimage).【F:local/educamlogin/lib.php†L46-L115】【F:local/educamlogin/lib.php†L296-L339】

## Seguridad y autenticación
- El formulario utiliza la autenticación estándar (`authenticate_user_login`, `complete_user_login`) y respeta el parámetro `wantsurl` para redirigir tras el acceso.【F:local/educamlogin/index.php†L30-L72】
- Se incluye protección CSRF mediante `sesskey()` y validación de reCAPTCHA antes de autenticar al usuario.【F:local/educamlogin/lib.php†L229-L287】【F:local/educamlogin/index.php†L74-L110】
- Capacidades declaradas: `local/educamlogin:view` (acceso de lectura público) y `local/educamlogin:configure` (gestión restringida a managers).【F:local/educamlogin/db/access.php†L9-L28】

## Accesibilidad y experiencia de usuario
Las plantillas incorporan buenas prácticas de accesibilidad: enlaces «skip to form», controles de visibilidad para contraseñas, atributos ARIA y enfoque claro para el feedback de errores.【F:local/educamlogin/templates/layout1.mustache†L31-L123】【F:local/educamlogin/templates/layout2.mustache†L27-L106】

## Internacionalización
Los textos principales del formulario y de la configuración están traducidos al español e inglés. Puedes extender el plugin añadiendo más idiomas mediante archivos adicionales en `lang/<código>/local_educamlogin.php`.【F:local/educamlogin/lang/es/local_educamlogin.php†L7-L80】【F:local/educamlogin/lang/en/local_educamlogin.php†L7-L80】

## Personalización avanzada
- **Plantillas Mustache**: clona o crea nuevas variantes en `templates/` y actualiza el selector de layout si necesitas más opciones. Todas las variables disponibles se ensamblan en `local_educamlogin_prepare_context()`.【F:local/educamlogin/lib.php†L296-L339】
- **Estilos**: los ficheros CSS incluyen comentarios y usan variables CSS para facilitar la personalización o la integración con temas corporativos.【F:local/educamlogin/styles/layout1.css†L1-L122】
- **Pluginfile**: si necesitas exponer archivos adicionales, amplía la lista de áreas válidas en `local_educamlogin_pluginfile()` manteniendo los controles de contexto.【F:local/educamlogin/lib.php†L284-L333】

## Licencia
Distribuido bajo la GNU GPL versión 3 o posterior, en línea con la licencia de Moodle. Consulta el archivo `LICENSE` incluido con el plugin.

---
© 2025 Educam Virtual Platform | Americas Business Process
