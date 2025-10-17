# Plugin de Login Personalizado Educam para Moodle v2.0

Plugin local para Moodle que proporciona **2 diseños diferentes de login** completamente personalizables usando **Mustache templates** y **CSS independiente**.

## 🎨 ¿Qué hay de nuevo en v2.0?

### ✨ Mejoras Principales

- **Mustache Templates**: Cada layout tiene su propio template Mustache para mejor mantenimiento
- **CSS Independiente**: Cada layout tiene su archivo CSS separado
- **Prefijo `ed_`**: Todas las configuraciones usan el prefijo `ed_` para evitar conflictos
- **Mejor Organización**: Estructura de archivos más clara y mantenible
- **index.php**: Archivo principal renombrado de `login.php` a `index.php`
- **Fallbacks Inteligentes**: Si no hay imágenes, se usan placeholders automáticamente

## 📐 Los Dos Diseños

### Layout 1: Dos Columnas
```
┌──────────────────┬─────────────────┐
│  Fotografía del  │  [Logo Educam]  │
│  equipo/         │                 │
│  estudiantes     │  Bienvenido     │
│  (lado izq.)     │                 │
│                  │  Formulario     │
│                  │  de Login       │
│  ⚪⚪⚪⚪⚪       │                 │
│  (Dots)          │  [Logo Americas]│
└──────────────────┴─────────────────┘
```

**Características:**
- Imagen del equipo/estudiantes visible a la izquierda
- Panel de formulario con gradiente a la derecha
- Logo Educam fijo en esquina superior derecha
- Círculos decorativos en la parte inferior
- Blobs decorativos en el fondo

### Layout 2: Centrado con Fondo
```
┌────────────────────────────────┐
│   [Fondo: Imagen completa]     │
│                                │
│     ┌──────────────┐          │
│     │ [Logo SVG]   │          │
│     │ Bienvenido   │          │
│     │ Formulario   │          │
│     │ [Americas]   │          │
│     └──────────────┘          │
└────────────────────────────────┘
```

**Características:**
- Imagen de fondo a pantalla completa
- Panel central flotante con gradiente
- Logo Educam como SVG centrado
- Overlay morado para mejor contraste

## 🚀 Instalación

### Requisitos
- Moodle 3.9 o superior
- PHP 7.2 o superior

### Pasos

1. **Descargar e instalar:**
```bash
cd /ruta/a/moodle/local/
unzip local_educamlogin_v2.0.zip
chmod -R 755 educamlogin/
```

2. **Visitar notificaciones:**
```
Administración del sitio → Notificaciones
```

3. **Configurar:**
```
Administración → Plugins → Plugins locales → Login Personalizado Educam
```

## ⚙️ Configuración

### Parámetros con Prefijo `ed_`

Todos los parámetros de configuración usan el prefijo `ed_`:

#### General
- `ed_enabled` - Habilitar/deshabilitar el plugin

#### Layout
- `ed_layout` - Selección de layout (layout1 o layout2)

#### Imágenes (prefijo `ed_`)
- `ed_background_image` - Imagen de fondo (CRÍTICA para Layout 2)
- `ed_team_photo` - Fotografía del equipo (solo Layout 1)
- `ed_logo_educam` - Logo de Educam
- `ed_logo_americas` - Logo de Americas

#### Colores (prefijo `ed_`)
- `ed_bg_color` - Color de fondo (Layout 1): `#edf7fb`
- `ed_panel_top` - Color superior del panel: `#4a64c8`
- `ed_panel_mid` - Color medio del panel: `#2d448e`
- `ed_panel_bottom` - Color inferior del panel: `#12213e`
- `ed_field_bg` - Color de campos: `#e8eefb`
- `ed_link_color` - Color de enlaces: `#cfe0ff`
- `ed_btn_color` - Color del botón: `#c0328a`
- `ed_btn_hover` - Color del botón (hover): `#a52877`

#### Textos (prefijo `ed_`)
- `ed_welcome_text` - Texto de bienvenida: "Bienvenido"
- `ed_copyright_text` - Texto de copyright

#### reCAPTCHA (prefijo `ed_`)
- `ed_recaptcha_sitekey` - Site Key
- `ed_recaptcha_secretkey` - Secret Key

## 📁 Estructura del Plugin

```
local_educamlogin/
├── index.php                    # Archivo principal (antes login.php)
├── version.php                  # Versión 2.0
├── settings.php                 # Configuración con prefijo ed_
├── lib.php                      # Funciones auxiliares
│
├── templates/                   # Templates Mustache
│   ├── layout1.mustache        # Template Layout 1
│   └── layout2.mustache        # Template Layout 2
│
├── styles/                      # CSS independiente
│   ├── layout1.css             # Estilos Layout 1
│   └── layout2.css             # Estilos Layout 2
│
├── lang/                        # Idiomas
│   ├── es/
│   │   └── local_educamlogin.php
│   └── en/
│       └── local_educamlogin.php
│
└── db/
    └── access.php              # Permisos
```

## 🎨 Guía de Imágenes

### Layout 1

| Imagen | Dimensiones | Formatos | Importancia |
|--------|-------------|----------|-------------|
| Fotografía del equipo | 800x600px+ | JPG, PNG, WEBP | ⭐⭐⭐ |
| Imagen de fondo | 1920x1080px | JPG, PNG | ⭐ |
| Logo Educam | 210x66px | PNG, SVG | ⭐⭐ |
| Logo Americas | 180x40px | PNG, SVG | ⭐ |

### Layout 2

| Imagen | Dimensiones | Formatos | Importancia |
|--------|-------------|----------|-------------|
| Imagen de fondo | 1920x1080px+ | JPG, PNG, WEBP | ⭐⭐⭐ CRÍTICA |
| Logo Educam | 160x50px | PNG, SVG | ⭐⭐ |
| Logo Americas | 180x40px | PNG, SVG | ⭐ |

**Nota:** Si no subes una imagen, se usará un placeholder automático.

## 🔧 Uso de Mustache Templates

### Renderizado

El plugin usa el motor Mustache nativo de Moodle:

```php
$mustache = $OUTPUT->mustache();
$template = file_get_contents(__DIR__ . '/templates/layout1.mustache');
echo $mustache->render($template, $context);
```

### Variables Disponibles en Templates

- `{{layout}}` - Nombre del layout (layout1 o layout2)
- `{{wwwroot}}` - URL raíz de Moodle
- `{{team_photo}}` - URL de la foto del equipo
- `{{background_image}}` - URL del fondo
- `{{logo_educam}}` - URL del logo Educam
- `{{logo_americas}}` - URL del logo Americas
- `{{welcome_text}}` - Texto de bienvenida
- `{{copyright_text}}` - Texto de copyright
- `{{has_recaptcha}}` - Boolean si reCAPTCHA está configurado
- `{{recaptcha_sitekey}}` - Site Key de reCAPTCHA
- `{{has_error}}` - Boolean si hay error
- `{{errormsg}}` - Mensaje de error
- Colores: `{{bg_color}}`, `{{panel_top}}`, `{{panel_mid}}`, etc.

## 🎨 Personalización de Colores

### Colores por Defecto

**Layout 1:**
```css
--bg: #edf7fb          /* Fondo general */
--panelTop: #4a64c8    /* Superior del panel */
--panelMid: #2d448e    /* Medio del panel */
--panelBottom: #12213e /* Inferior del panel */
--field: #e8eefb       /* Campos de texto */
--link: #cfe0ff        /* Enlaces y labels */
--btn: #c0328a         /* Botón Acceder */
--btnHover: #a52877    /* Botón Acceder (hover) */
```

**Layout 2:**
```css
--panelTop: #6881d4    /* Superior del panel */
--panelMid: #2f4d91    /* Medio del panel */
--panelBottom: #0f1b34 /* Inferior del panel */
--field: #d7d7df       /* Campos de texto (más gris) */
--hint: #cfe0ff        /* Enlaces y labels */
--btn: #be2f87         /* Botón Acceder */
--btnHover: #a02a73    /* Botón Acceder (hover) */
```

## 📱 Responsive Design

Ambos layouts son completamente responsive:

- **Desktop (>992px)**: Diseño completo
- **Tablet (768-992px)**: Adaptado con columnas ajustadas
- **Móvil (<768px)**: Diseño simplificado, solo formulario

## 🔐 Seguridad

- Autenticación nativa de Moodle
- Integración con Google reCAPTCHA v2
- Sanitización de inputs
- Protección CSRF con sesskey
- Verificación de contexto

## 🌍 Multiidioma

Soporta español (es) e inglés (en) completos.

## 🔄 Migración desde v1.x

Si ya tienes instalada una versión anterior:

1. **Backup de tu configuración**
2. **Actualizar archivos:**
```bash
rm -rf local/educamlogin/*
unzip local_educamlogin_v2.0.zip -d local/
```
3. **Visitar notificaciones** para actualizar la base de datos
4. **Reconfigurar colores y textos** (los nombres cambiaron a prefijo `ed_`)

## 📝 Changelog

### v2.0.0 (2025-10-17)
- Migración completa a Mustache templates
- CSS independiente para cada layout
- Prefijo `ed_` en todas las configuraciones
- Renombrado `login.php` → `index.php`
- Fallbacks automáticos para imágenes
- Mejor estructura de archivos
- Documentación actualizada

### v1.1.0 (2025-10-17)
- Soporte para 2 layouts
- Múltiples formatos de imagen

### v1.0.0 (2025-10-17)
- Versión inicial

## 🏆 Créditos

**Desarrollado para:**
- Educam Virtual Platform
- Americas Business Process

**Características:**
- Sistema de capacitación virtual
- Login personalizado con Mustache
- Totalmente configurable
- Responsive y moderno

## 📜 Licencia

GNU General Public License v3.0 o posterior

---

© 2025 Educam Virtual Platform | Americas Business Process  
**Versión 2.0.0** - Mustache Templates & CSS Modular

Para soporte, consulta la documentación o los logs de Moodle.
