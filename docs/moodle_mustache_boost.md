# Sistema de Templates Mustache y Tema Boost en Moodle

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Sistema Mustache](#arquitectura-del-sistema-mustache)
3. [Sintaxis de Mustache](#sintaxis-de-mustache)
4. [Helpers Especiales de Moodle](#helpers-especiales-de-moodle)
5. [Interfaz Templatable](#interfaz-templatable)
6. [Renderizado desde PHP](#renderizado-desde-php)
7. [Renderizado desde JavaScript](#renderizado-desde-javascript)
8. [Crear Templates en Plugins](#crear-templates-en-plugins)
9. [Override de Templates en Temas](#override-de-templates-en-temas)
10. [Tema Boost - Arquitectura](#tema-boost---arquitectura)
11. [Sistema SCSS de Boost](#sistema-scss-de-boost)
12. [Layouts de Boost](#layouts-de-boost)
13. [Crear Tema Heredando de Boost](#crear-tema-heredando-de-boost)
14. [JavaScript AMD en Temas](#javascript-amd-en-temas)
15. [Referencia Rápida](#referencia-rápida)

---

## Introducción

Moodle utiliza dos sistemas fundamentales para la presentación:

1. **Mustache Templates**: Motor de plantillas logic-less para separar la lógica de la presentación
2. **Tema Boost**: Tema base moderno construido sobre Bootstrap 5, diseñado para ser extendido

### Ventajas del Sistema

- **Separación de responsabilidades**: Lógica en PHP, presentación en templates
- **Reutilización**: Templates pueden incluir otros templates (partials)
- **Override en temas**: Los temas pueden sobrescribir cualquier template
- **Renderizado dual**: Templates funcionan en PHP y JavaScript
- **Seguridad**: Escape automático de HTML

---

## Arquitectura del Sistema Mustache

### Estructura de Directorios

```
/lib/classes/output/
├── mustache_engine.php              # Motor Mustache extendido
├── mustache_template_finder.php     # Buscador de templates
├── mustache_filesystem_loader.php   # Cargador de archivos
├── mustache_template_source_loader.php  # Cargador con dependencias
├── mustache_helper_collection.php   # Colección de helpers
├── mustache_string_helper.php       # Helper {{#str}}
├── mustache_pix_helper.php          # Helper {{#pix}}
├── mustache_javascript_helper.php   # Helper {{#js}}
├── mustache_quote_helper.php        # Helper {{#quote}}
├── mustache_shorten_text_helper.php # Helper {{#shortentext}}
├── mustache_user_date_helper.php    # Helper {{#userdate}}
├── mustache_uniqid_helper.php       # Helper {{uniqid}}
├── mustache_clean_string_helper.php # Helper {{#cleanstr}}
├── renderer_base.php                # Clase base de renderizado
├── templatable.php                  # Interfaz para exportar datos
└── named_templatable.php            # Interfaz con nombre de template
```

### Clase `mustache_engine`

**Ubicación**: `/lib/classes/output/mustache_engine.php`

```php
namespace core\output;

class mustache_engine extends \Mustache_Engine {
    private $helpers;
    private $disallowednestedhelpers = [];

    public function __construct(array $options = []) {
        // Configurar helpers no permitidos anidados (seguridad)
        if (isset($options['disallowednestedhelpers'])) {
            $this->disallowednestedhelpers = $options['disallowednestedhelpers'];
        }
        parent::__construct($options);
    }

    public function gethelpers() {
        if (!isset($this->helpers)) {
            $this->helpers = new mustache_helper_collection(
                null,
                $this->disallowednestedhelpers
            );
        }
        return $this->helpers;
    }
}
```

**Características**:
- Extiende la librería Mustache.php
- Implementa validación de helpers anidados (seguridad)
- Previene inyección de código con helper `js`

### Clase `mustache_template_finder`

**Ubicación**: `/lib/classes/output/mustache_template_finder.php`

```php
class mustache_template_finder {

    /**
     * Obtiene la ruta del archivo de template
     *
     * @param string $name Nombre del template (component/templatename)
     * @param string $themename Nombre del tema (opcional)
     * @return string Ruta completa al archivo .mustache
     */
    public static function get_template_filepath($name, $themename = '') {
        // Orden de búsqueda:
        // 1. /theme/{themename}/templates/{component}/
        // 2. Temas padres
        // 3. /{component}/templates/
    }

    /**
     * Obtiene directorios válidos para templates de un componente
     */
    public static function get_template_directories_for_component(
        $component,
        $themename = ''
    ) {
        // Retorna array de directorios donde buscar
    }
}
```

**Orden de Búsqueda de Templates**:

```
1. /theme/{current_theme}/templates/{component}/
2. /theme/{parent_theme}/templates/{component}/  (si hay tema padre)
3. /{component_path}/templates/
```

**Ejemplo**:
```php
// Para cargar 'core/button'
$filepath = mustache_template_finder::get_template_filepath('core/button');
// Busca en:
// 1. /theme/boost/templates/core/button.mustache
// 2. /lib/templates/button.mustache
```

---

## Sintaxis de Mustache

### Tipos de Tags

```mustache
{{! Comentario - no se renderiza }}

{{variable}}                    {{! Variable escapada }}
{{{variable}}}                  {{! Variable sin escapar (HTML raw) }}

{{#section}}...{{/section}}     {{! Sección (condicional/iterador) }}
{{^inverse}}...{{/inverse}}     {{! Inverso (si false/empty) }}

{{> partial}}                   {{! Incluir partial }}
{{> component/template}}

{{#helper}}args{{/helper}}      {{! Helpers especiales }}

{{<parent}}                     {{! Herencia de templates }}
{{$block}}...{{/block}}         {{! Bloque heredable }}
```

### Variables

```mustache
{{! Variable simple }}
<h1>{{title}}</h1>

{{! Variable escapada (default) }}
<p>{{description}}</p>

{{! Variable sin escapar (HTML raw) }}
<div>{{{htmlcontent}}}</div>

{{! Acceso a propiedades anidadas }}
<span>{{user.firstname}} {{user.lastname}}</span>
```

### Secciones Condicionales

```mustache
{{! Si la variable existe y es truthy }}
{{#hasitems}}
    <ul>
        {{#items}}
            <li>{{name}}</li>
        {{/items}}
    </ul>
{{/hasitems}}

{{! Si la variable NO existe o es falsy }}
{{^hasitems}}
    <p>No hay items</p>
{{/hasitems}}
```

### Iteración

```mustache
{{! Iterar sobre array }}
<ul>
{{#items}}
    <li>
        <strong>{{name}}</strong>: {{value}}
        {{#selected}}<span class="selected">*</span>{{/selected}}
    </li>
{{/items}}
</ul>
```

### Partials (Inclusión de Templates)

```mustache
{{! Incluir otro template }}
{{> core/loading}}

{{! Incluir con ruta completa }}
{{> mod_forum/post_header}}

{{! Partial en contexto de sección }}
{{#items}}
    {{> mycomponent/item}}
{{/items}}
```

### Herencia de Templates

```mustache
{{! Template padre: core/base }}
<div class="wrapper">
    {{$header}}
        <h1>Default Header</h1>
    {{/header}}

    {{$content}}
        <p>Default content</p>
    {{/content}}

    {{$footer}}
        <footer>Default Footer</footer>
    {{/footer}}
</div>

{{! Template hijo que extiende }}
{{<core/base}}
    {{$header}}
        <h1>Custom Header</h1>
    {{/header}}

    {{$content}}
        <p>Custom content here</p>
    {{/content}}
{{/core/base}}
```

---

## Helpers Especiales de Moodle

### 1. String Helper - `{{#str}}`

Accede a strings de idioma.

```mustache
{{! Uso básico }}
{{#str}}save{{/str}}

{{! Con componente }}
{{#str}}submit, core{{/str}}

{{! Con componente y parámetro simple }}
{{#str}}welcomeback, core, {{username}}{{/str}}

{{! Con parámetros JSON }}
{{#str}}gradeitem, grades, {"item": "Quiz 1", "grade": "85"}{{/str}}

{{! Anidado }}
{{#str}}confirmdelete, core, {{#str}}activity{{/str}}{{/str}}
```

**Implementación**: `/lib/classes/output/mustache_string_helper.php`

### 2. Pix Helper - `{{#pix}}`

Renderiza iconos.

```mustache
{{! Icono simple }}
{{#pix}}t/edit, core{{/pix}}

{{! Con texto alternativo }}
{{#pix}}t/delete, core, Delete this item{{/pix}}

{{! Icono de módulo }}
{{#pix}}icon, mod_forum{{/pix}}

{{! Variable en icono }}
{{#pix}}{{iconkey}}, {{iconcomponent}}{{/pix}}
```

**Implementación**: `/lib/classes/output/mustache_pix_helper.php`

### 3. JavaScript Helper - `{{#js}}`

Ejecuta JavaScript al final de la página.

```mustache
{{#js}}
require(['jquery', 'core/modal'], function($, Modal) {
    $('#{{uniqid}}-button').on('click', function() {
        Modal.create({
            title: '{{title}}',
            body: '{{body}}'
        }).show();
    });
});
{{/js}}
```

**Características**:
- NO retorna contenido visible
- JavaScript se ejecuta después de cargar la página
- Puede contener otras variables Mustache
- **RESTRICCIÓN DE SEGURIDAD**: No puede usarse dentro de otros helpers

**Implementación**: `/lib/classes/output/mustache_javascript_helper.php`

### 4. Quote Helper - `{{#quote}}`

Escapa contenido para uso en JavaScript/JSON.

```mustache
<script>
var config = {
    title: {{#quote}}{{title}}{{/quote}},
    message: {{#quote}}{{message}}{{/quote}}
};
</script>
```

**Implementación**: `/lib/classes/output/mustache_quote_helper.php`

### 5. Shorten Text Helper - `{{#shortentext}}`

Acorta texto a un número de caracteres.

```mustache
{{! Acortar a 50 caracteres }}
{{#shortentext}}50, {{longtext}}{{/shortentext}}

{{! Acortar descripción }}
<p class="summary">{{#shortentext}}100, {{description}}{{/shortentext}}</p>
```

**Implementación**: `/lib/classes/output/mustache_shorten_text_helper.php`

### 6. User Date Helper - `{{#userdate}}`

Formatea timestamps según preferencias del usuario.

```mustache
{{! Formato simple }}
{{#userdate}}{{timestamp}}, %d/%m/%Y{{/userdate}}

{{! Formato completo }}
{{#userdate}}{{timestamp}}, %A, %d %B %Y, %H:%M{{/userdate}}

{{! Timestamp literal }}
{{#userdate}}1487655635, %Y-%m-%d{{/userdate}}
```

**Formatos disponibles** (strftime):
- `%Y` - Año (4 dígitos)
- `%m` - Mes (01-12)
- `%d` - Día (01-31)
- `%H` - Hora (00-23)
- `%M` - Minutos (00-59)
- `%A` - Nombre del día
- `%B` - Nombre del mes

**Implementación**: `/lib/classes/output/mustache_user_date_helper.php`

### 7. Uniqid Helper - `{{uniqid}}`

Genera IDs únicos para elementos DOM.

```mustache
<div id="container-{{uniqid}}">
    <label for="input-{{uniqid}}">Name:</label>
    <input id="input-{{uniqid}}" type="text">
    <button id="btn-{{uniqid}}">Submit</button>
</div>

{{#js}}
require(['jquery'], function($) {
    $('#btn-{{uniqid}}').on('click', function() {
        var value = $('#input-{{uniqid}}').val();
        console.log(value);
    });
});
{{/js}}
```

**Implementación**: `/lib/classes/output/mustache_uniqid_helper.php`

### 8. Clean String Helper - `{{#cleanstr}}`

Similar a `str` pero limpia HTML del resultado.

```mustache
{{#cleanstr}}description, mycomponent{{/cleanstr}}
```

---

## Interfaz Templatable

### Definición

**Ubicación**: `/lib/classes/output/templatable.php`

```php
interface templatable {
    /**
     * Exporta datos para uso en template Mustache
     *
     * Reglas:
     * 1. Solo tipos simples: stdClass, array, int, string, float, bool
     * 2. Sin objetos complejos
     * 3. Datos pre-calculados
     *
     * @param renderer_base $output Renderer para renderizado adicional
     * @return stdClass|array Datos para el template
     */
    public function export_for_template(renderer_base $output);
}
```

### Implementación en Plugins

```php
namespace mod_mymod\output;

use core\output\renderable;
use core\output\templatable;
use renderer_base;

class mywidget implements renderable, templatable {

    protected $title;
    protected $items;
    protected $showactions;

    public function __construct($title, $items, $showactions = true) {
        $this->title = $title;
        $this->items = $items;
        $this->showactions = $showactions;
    }

    public function export_for_template(renderer_base $output) {
        $data = new \stdClass();
        $data->title = $this->title;
        $data->showactions = $this->showactions;
        $data->hasitems = !empty($this->items);
        $data->items = [];

        foreach ($this->items as $item) {
            $itemdata = new \stdClass();
            $itemdata->id = $item->id;
            $itemdata->name = format_string($item->name);
            $itemdata->description = format_text($item->description);
            $itemdata->url = (new \moodle_url('/mod/mymod/view.php',
                ['id' => $item->id]))->out(false);

            // Renderizar sub-componentes si es necesario
            if ($item->has_icon()) {
                $icon = new \pix_icon($item->icon, $item->name);
                $itemdata->icon = $output->render($icon);
            }

            $data->items[] = $itemdata;
        }

        $data->itemcount = count($data->items);

        return $data;
    }
}
```

### Interfaz `named_templatable`

Permite especificar el nombre del template.

```php
interface named_templatable extends templatable {
    /**
     * Retorna el nombre del template a usar
     *
     * @param renderer_base $renderer
     * @return string Nombre del template (component/name)
     */
    public function get_template_name(renderer_base $renderer): string;
}
```

**Implementación**:

```php
class mywidget implements renderable, named_templatable {

    public function get_template_name(renderer_base $renderer): string {
        return 'mod_mymod/mywidget';
    }

    public function export_for_template(renderer_base $output) {
        // ...
    }
}
```

---

## Renderizado desde PHP

### Método `render_from_template()`

**Ubicación**: `/lib/classes/output/renderer_base.php`

```php
/**
 * Renderiza un template Mustache con contexto dado
 *
 * @param string $templatename Nombre del template (component/name)
 * @param mixed $context Datos para el template (stdClass o array)
 * @return string HTML renderizado
 */
public function render_from_template($templatename, $context) {
    $mustache = $this->get_mustache();

    // Configurar helper uniqid
    $mustache->addHelper('uniqid', new mustache_uniqid_helper());

    // Cargar y renderizar template
    $template = $mustache->loadTemplate($templatename);
    $renderedtemplate = trim($template->render($context));

    return $renderedtemplate;
}
```

### Uso Directo

```php
// En cualquier lugar con acceso al renderer
global $OUTPUT;

$context = (object) [
    'title' => 'Mi Título',
    'items' => [
        (object) ['name' => 'Item 1', 'selected' => true],
        (object) ['name' => 'Item 2', 'selected' => false],
    ],
    'hasitems' => true,
];

echo $OUTPUT->render_from_template('mycomponent/mytemplate', $context);
```

### Uso con Renderables

```php
// Crear widget
$widget = new \mod_mymod\output\mywidget('Title', $items);

// Obtener renderer
$renderer = $PAGE->get_renderer('mod_mymod');

// Renderizar (automáticamente llama a export_for_template)
echo $renderer->render($widget);
```

### Flujo de Renderizado Automático

El método `render()` de `renderer_base` sigue este orden:

1. Busca método `render_classname()` en el renderer
2. Si implementa `named_templatable`, usa `get_template_name()`
3. Si implementa `templatable`, deduce el nombre del template
4. Llama a `export_for_template()` y `render_from_template()`

```php
public function render(renderable $widget) {
    // 1. Buscar método específico
    $rendermethod = "render_{$classname}";
    if (method_exists($this, $rendermethod)) {
        return $this->$rendermethod($widget);
    }

    // 2. named_templatable
    if ($widget instanceof named_templatable) {
        return $this->render_from_template(
            $widget->get_template_name($this),
            $widget->export_for_template($this)
        );
    }

    // 3. templatable
    if ($widget instanceof templatable) {
        $template = $component . '/' . $classname;
        return $this->render_from_template(
            $template,
            $widget->export_for_template($this)
        );
    }
}
```

---

## Renderizado desde JavaScript

### Módulo `core/templates`

```javascript
import * as Templates from 'core/templates';

// Renderizar template
Templates.renderForPromise('core/notification', {
    message: 'Hello World',
    type: 'success'
}).then(({html, js}) => {
    // html: HTML renderizado
    // js: JavaScript del template (de {{#js}})

    document.querySelector('#notification-area').innerHTML = html;

    // Ejecutar JavaScript del template
    Templates.runTemplateJS(js);
}).catch(error => {
    console.error('Error rendering template:', error);
});
```

### Métodos Disponibles

```javascript
// Renderizar y obtener promesa
Templates.renderForPromise(templateName, context)
    .then(({html, js}) => { ... });

// Renderizar (legacy, con callbacks)
Templates.render(templateName, context)
    .done(function(html, js) { ... })
    .fail(function(error) { ... });

// Ejecutar JavaScript de template
Templates.runTemplateJS(js);

// Pre-cargar templates (optimización)
Templates.prefetchTemplates(['core/modal', 'core/notification']);

// Renderizar e insertar en DOM
Templates.renderForPromise('mytemplate', context)
    .then(({html, js}) => {
        Templates.replaceNode(
            document.querySelector('#target'),
            html,
            js
        );
    });
```

### Ejemplo Completo

```javascript
define(['core/templates', 'core/notification'], function(Templates, Notification) {
    return {
        init: function(containerSelector) {
            var container = document.querySelector(containerSelector);

            // Cargar datos via AJAX
            fetch('/mod/mymod/ajax/getitems.php')
                .then(response => response.json())
                .then(data => {
                    // Renderizar template
                    return Templates.renderForPromise('mod_mymod/items_list', {
                        items: data.items,
                        hasitems: data.items.length > 0
                    });
                })
                .then(({html, js}) => {
                    container.innerHTML = html;
                    Templates.runTemplateJS(js);
                })
                .catch(error => {
                    Notification.exception(error);
                });
        }
    };
});
```

---

## Crear Templates en Plugins

### Estructura de Directorios

```
mod_mymod/
├── classes/
│   └── output/
│       ├── renderer.php           # Renderer del plugin
│       └── mywidget.php           # Clase Templatable
├── templates/
│   ├── mywidget.mustache          # Template principal
│   ├── item.mustache              # Partial para items
│   └── actions.mustache           # Partial para acciones
├── amd/
│   └── src/
│       └── mywidget.js            # JavaScript del widget
└── lang/
    └── en/
        └── mod_mymod.php          # Strings de idioma
```

### Paso 1: Crear el Template

**Archivo**: `mod_mymod/templates/mywidget.mustache`

```mustache
{{!
    @template mod_mymod/mywidget

    Widget para mostrar lista de items.

    Classes required for JS:
    * mywidget-container
    * mywidget-item

    Data attributes required for JS:
    * data-region="mywidget"
    * data-item-id

    Context variables required for this template:
    * title - string - Título del widget
    * hasitems - bool - Si hay items
    * items - array - Lista de items
        * id - int - ID del item
        * name - string - Nombre del item
        * url - string - URL del item
    * showactions - bool - Mostrar acciones

    Example context (json):
    {
        "title": "My Items",
        "hasitems": true,
        "items": [
            {"id": 1, "name": "Item 1", "url": "/item/1"},
            {"id": 2, "name": "Item 2", "url": "/item/2"}
        ],
        "showactions": true
    }
}}

<div class="mywidget-container" data-region="mywidget">
    <h3>{{title}}</h3>

    {{#hasitems}}
        <ul class="mywidget-list">
            {{#items}}
                {{> mod_mymod/item}}
            {{/items}}
        </ul>
    {{/hasitems}}

    {{^hasitems}}
        <p class="text-muted">{{#str}}noitems, mod_mymod{{/str}}</p>
    {{/hasitems}}

    {{#showactions}}
        {{> mod_mymod/actions}}
    {{/showactions}}
</div>

{{#js}}
require(['mod_mymod/mywidget'], function(MyWidget) {
    MyWidget.init('[data-region="mywidget"]');
});
{{/js}}
```

### Paso 2: Crear Partials

**Archivo**: `mod_mymod/templates/item.mustache`

```mustache
{{!
    @template mod_mymod/item

    Item individual del widget.
}}
<li class="mywidget-item" data-item-id="{{id}}">
    <a href="{{url}}">
        {{#pix}}i/item, core{{/pix}}
        {{name}}
    </a>
</li>
```

### Paso 3: Crear Clase Templatable

**Archivo**: `mod_mymod/classes/output/mywidget.php`

```php
<?php
namespace mod_mymod\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

class mywidget implements renderable, templatable {

    protected $title;
    protected $items;
    protected $showactions;

    public function __construct(string $title, array $items, bool $showactions = true) {
        $this->title = $title;
        $this->items = $items;
        $this->showactions = $showactions;
    }

    public function export_for_template(renderer_base $output): array {
        $items = [];
        foreach ($this->items as $item) {
            $items[] = [
                'id' => $item->id,
                'name' => format_string($item->name),
                'url' => (new \moodle_url('/mod/mymod/view.php',
                    ['itemid' => $item->id]))->out(false),
            ];
        }

        return [
            'title' => $this->title,
            'hasitems' => !empty($items),
            'items' => $items,
            'showactions' => $this->showactions,
        ];
    }
}
```

### Paso 4: Crear Renderer (Opcional)

**Archivo**: `mod_mymod/classes/output/renderer.php`

```php
<?php
namespace mod_mymod\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    /**
     * Renderiza el widget
     */
    public function render_mywidget(mywidget $widget): string {
        $context = $widget->export_for_template($this);
        return $this->render_from_template('mod_mymod/mywidget', $context);
    }
}
```

### Paso 5: Usar en el Plugin

```php
// En view.php o lib.php
$widget = new \mod_mymod\output\mywidget(
    get_string('myitems', 'mod_mymod'),
    $items,
    has_capability('mod/mymod:manage', $context)
);

$renderer = $PAGE->get_renderer('mod_mymod');
echo $renderer->render($widget);
```

---

## Override de Templates en Temas

### Mecanismo de Override

Para sobrescribir un template en tu tema:

1. Crea el directorio `templates/{component}/` en tu tema
2. Copia el template original
3. Modifica según necesites

### Estructura

```
theme/mytheme/
├── templates/
│   ├── core/
│   │   ├── notification.mustache    # Override de core
│   │   └── modal.mustache           # Override de core
│   ├── mod_forum/
│   │   └── post.mustache            # Override de mod_forum
│   └── theme_mytheme/
│       ├── navbar.mustache          # Template propio
│       └── footer.mustache          # Template propio
```

### Ejemplo de Override

**Original**: `/lib/templates/notification.mustache`
**Override**: `/theme/mytheme/templates/core/notification.mustache`

```mustache
{{!
    @template core/notification

    Custom notification template for mytheme.
}}
<div class="alert alert-{{type}} mytheme-notification" role="alert">
    <div class="notification-icon">
        {{#pix}}i/{{type}}, core{{/pix}}
    </div>
    <div class="notification-content">
        {{{message}}}
    </div>
    {{#closebutton}}
    <button type="button" class="close" data-dismiss="alert">
        <span aria-hidden="true">&times;</span>
    </button>
    {{/closebutton}}
</div>
```

---

## Tema Boost - Arquitectura

### Estructura de Directorios

```
theme/boost/
├── config.php                # Configuración del tema
├── lib.php                   # Funciones del tema
├── settings.php              # Configuración admin
├── version.php               # Versión
├── amd/
│   ├── src/                  # JavaScript fuente
│   │   ├── loader.js         # Cargador principal
│   │   ├── aria.js           # Accesibilidad
│   │   ├── drawers.js        # Drawers laterales
│   │   └── bootstrap/        # Componentes Bootstrap
│   └── build/                # JavaScript compilado
├── classes/
│   └── output/
│       └── core_renderer.php # Renderer personalizado
├── layout/
│   ├── columns1.php          # Layout 1 columna
│   ├── columns2.php          # Layout 2 columnas
│   ├── drawers.php           # Layout con drawers
│   ├── login.php             # Layout login
│   ├── maintenance.php       # Layout mantenimiento
│   └── embedded.php          # Layout embebido
├── lang/
│   └── en/
│       └── theme_boost.php   # Strings
├── pix/                      # Imágenes
├── scss/
│   ├── preset/               # Presets de color
│   ├── bootstrap/            # Bootstrap 5
│   ├── fontawesome/          # FontAwesome
│   └── moodle/               # Estilos Moodle
├── style/
│   └── moodle.css            # CSS precompilado
└── templates/                # Templates Mustache
    ├── columns1.mustache
    ├── columns2.mustache
    ├── drawers.mustache
    ├── navbar.mustache
    └── footer.mustache
```

### Archivo `config.php`

**Ubicación**: `/theme/boost/config.php`

```php
<?php
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'boost';
$THEME->parents = [];  // Sin tema padre

// Hojas de estilo
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->editor_scss = ['editor'];
$THEME->usefallback = true;

// SCSS dinámico
$THEME->scss = function($theme) {
    return theme_boost_get_main_scss_content($theme);
};

// Layouts disponibles
$THEME->layouts = [
    'base' => [
        'file' => 'drawers.php',
        'regions' => [],
    ],
    'standard' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'course' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['langmenu' => true],
    ],
    'coursecategory' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'incourse' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'frontpage' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['nonavbar' => true],
    ],
    'admin' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
    ],
    'mydashboard' => [
        'file' => 'drawers.php',
        'regions' => ['side-pre'],
        'defaultregion' => 'side-pre',
        'options' => ['nonavbar' => true, 'langmenu' => true],
    ],
    'login' => [
        'file' => 'login.php',
        'regions' => [],
        'options' => ['langmenu' => true],
    ],
    'maintenance' => [
        'file' => 'maintenance.php',
        'regions' => [],
    ],
];

// Callbacks SCSS
$THEME->extrascsscallback = 'theme_boost_get_extra_scss';
$THEME->prescsscallback = 'theme_boost_get_pre_scss';
$THEME->precompiledcsscallback = 'theme_boost_get_precompiled_css';

// Renderer personalizado
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// Sistema de iconos
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;

// Características
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
```

### Archivo `lib.php`

**Ubicación**: `/theme/boost/lib.php`

```php
<?php
/**
 * SCSS para prepender (variables)
 */
function theme_boost_get_pre_scss($theme) {
    $scss = '';

    // Variables configurables
    $configurable = [
        'brandcolor' => ['primary'],
    ];

    foreach ($configurable as $configkey => $targets) {
        $value = $theme->settings->{$configkey} ?? null;
        if (empty($value)) {
            continue;
        }
        foreach ((array) $targets as $target) {
            $scss .= '$' . $target . ': ' . $value . ";\n";
        }
    }

    // SCSS personalizado pre
    if (!empty($theme->settings->scsspre)) {
        $scss .= $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Contenido SCSS principal
 */
function theme_boost_get_main_scss_content($theme) {
    global $CFG;

    $scss = '';
    $filename = $theme->settings->preset ?? 'default.scss';

    // Cargar preset
    if ($filename == 'default.scss') {
        $scss .= file_get_contents(
            $CFG->dirroot . '/theme/boost/scss/preset/default.scss'
        );
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents(
            $CFG->dirroot . '/theme/boost/scss/preset/plain.scss'
        );
    }

    return $scss;
}

/**
 * SCSS adicional (al final)
 */
function theme_boost_get_extra_scss($theme) {
    $content = '';

    // Imagen de fondo
    $imageurl = $theme->setting_file_url('backgroundimage', 'backgroundimage');
    if (!empty($imageurl)) {
        $content .= '@media (min-width: 768px) {';
        $content .= "body { background-image: url('$imageurl'); background-size: cover; }";
        $content .= '}';
    }

    // SCSS personalizado
    if (!empty($theme->settings->scss)) {
        $content .= $theme->settings->scss;
    }

    return $content;
}
```

---

## Sistema SCSS de Boost

### Estructura SCSS

```
scss/
├── preset/
│   ├── default.scss          # Preset por defecto
│   └── plain.scss            # Preset simple
├── bootstrap/                # Bootstrap 5 completo
│   ├── bootstrap.scss
│   ├── _variables.scss
│   ├── _mixins.scss
│   └── ...
├── fontawesome/              # FontAwesome 6
│   └── fontawesome.scss
├── moodle/                   # Estilos específicos Moodle
│   ├── _variables.scss
│   ├── _buttons.scss
│   ├── _forms.scss
│   ├── _navbar.scss
│   ├── _drawer.scss
│   ├── _course.scss
│   ├── _modal.scss
│   └── ...
├── bootstrap.scss            # Cargador Bootstrap
├── moodle.scss               # Cargador Moodle
└── editor.scss               # Estilos del editor
```

### Proceso de Compilación

```
1. prescsscallback
   └── Variables personalizadas ($primary, etc.)

2. SCSS principal (preset)
   └── @import 'bootstrap'
   └── @import 'fontawesome'
   └── @import 'moodle'

3. extrascsscallback
   └── Background images
   └── SCSS personalizado
```

### Variables Importantes

```scss
// Colores principales
$primary:       #0f6cbf !default;
$secondary:     #ced4da !default;
$success:       #357a32 !default;
$info:          #008196 !default;
$warning:       #ff7518 !default;
$danger:        #ca3120 !default;

// Layout
$navbar-height: 60px !default;
$drawer-width: 285px !default;
$course-content-maxwidth: 830px !default;

// Tipografía
$font-family-sans-serif: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !default;
$font-size-base: 0.9375rem !default;
```

---

## Layouts de Boost

### Layout Principal: `drawers.php`

**Ubicación**: `/theme/boost/layout/drawers.php`

```php
<?php
defined('MOODLE_INTERNAL') || die();

// Preparar bloques
$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = strpos($blockshtml, 'data-block=') !== false;

// Preferencias de usuario para drawers
$courseindexopen = get_user_preferences('drawer-open-index', true);
$blockdraweropen = get_user_preferences('drawer-open-block', true);

// Construir contexto
$templatecontext = [
    'sitename' => format_string($SITE->shortname),
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $OUTPUT->body_attributes([]),
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation,
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
];

echo $OUTPUT->render_from_template('theme_boost/drawers', $templatecontext);
```

### Template: `drawers.mustache`

```mustache
{{{ output.doctype }}}
<html {{{ output.htmlattributes }}}>
<head>
    <title>{{{ output.page_title }}}</title>
    {{{ output.standard_head_html }}}
</head>
<body {{{ bodyattributes }}}>
{{{ output.standard_top_of_body_html }}}

<div id="page-wrapper" class="d-print-block">
    {{{ output.full_header }}}

    <div id="page" class="drawers {{#courseindexopen}}show-drawer-left{{/courseindexopen}}">
        {{! Drawer izquierdo (índice del curso) }}
        {{#courseindex}}
        <div class="drawer drawer-left" data-region="drawer">
            {{{ courseindex }}}
        </div>
        {{/courseindex}}

        {{! Contenido principal }}
        <div id="page-content" class="pb-3 d-print-block">
            <div id="region-main-box">
                <section id="region-main">
                    {{{ output.main_content }}}
                </section>
            </div>
        </div>

        {{! Drawer derecho (bloques) }}
        {{#hasblocks}}
        <div class="drawer drawer-right {{#blockdraweropen}}show{{/blockdraweropen}}"
             data-region="drawer">
            {{{ sidepreblocks }}}
        </div>
        {{/hasblocks}}
    </div>

    {{{ output.standard_footer_html }}}
</div>

{{{ output.standard_end_of_body_html }}}
</body>
</html>
```

---

## Crear Tema Heredando de Boost

### Paso 1: Estructura del Tema

```
theme/myboost/
├── config.php
├── lib.php
├── settings.php
├── version.php
├── lang/
│   └── en/
│       └── theme_myboost.php
├── scss/
│   ├── preset/
│   │   └── mypreset.scss
│   └── custom.scss
└── templates/
    └── (overrides opcionales)
```

### Paso 2: Archivo `config.php`

```php
<?php
defined('MOODLE_INTERNAL') || die();

$THEME->name = 'myboost';
$THEME->parents = ['boost'];  // Heredar de Boost

$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->usefallback = true;

// SCSS personalizado
$THEME->scss = function($theme) {
    return theme_myboost_get_main_scss_content($theme);
};

$THEME->prescsscallback = 'theme_myboost_get_pre_scss';
$THEME->extrascsscallback = 'theme_myboost_get_extra_scss';

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
```

### Paso 3: Archivo `lib.php`

```php
<?php
defined('MOODLE_INTERNAL') || die();

function theme_myboost_get_main_scss_content($theme) {
    global $CFG;

    // Cargar SCSS de Boost
    $scss = file_get_contents(
        $CFG->dirroot . '/theme/boost/scss/preset/default.scss'
    );

    // Agregar SCSS personalizado
    $scss .= file_get_contents(
        $CFG->dirroot . '/theme/myboost/scss/custom.scss'
    );

    return $scss;
}

function theme_myboost_get_pre_scss($theme) {
    $scss = '';

    // Variables personalizadas
    if (!empty($theme->settings->brandcolor)) {
        $scss .= '$primary: ' . $theme->settings->brandcolor . ";\n";
    }

    // Otras variables
    $scss .= '$navbar-height: 70px;' . "\n";

    return $scss;
}

function theme_myboost_get_extra_scss($theme) {
    return $theme->settings->customscss ?? '';
}
```

### Paso 4: Archivo `version.php`

```php
<?php
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'theme_myboost';
$plugin->version = 2024010100;
$plugin->requires = 2023100900;
$plugin->dependencies = [
    'theme_boost' => 2023100900,
];
```

### Paso 5: Archivo `settings.php`

```php
<?php
defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new admin_settingpage('theme_myboost',
        get_string('configtitle', 'theme_myboost'));

    // Color principal
    $setting = new admin_setting_configcolourpicker(
        'theme_myboost/brandcolor',
        get_string('brandcolor', 'theme_myboost'),
        get_string('brandcolor_desc', 'theme_myboost'),
        '#0f6cbf'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // SCSS personalizado
    $setting = new admin_setting_scsscode(
        'theme_myboost/customscss',
        get_string('customscss', 'theme_myboost'),
        get_string('customscss_desc', 'theme_myboost'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $ADMIN->add('themes', $settings);
}
```

---

## JavaScript AMD en Temas

### Estructura AMD

```
theme/myboost/amd/
├── src/
│   ├── mymodule.js           # Fuente
│   └── init.js               # Inicialización
└── build/
    ├── mymodule.min.js       # Compilado
    └── init.min.js           # Compilado
```

### Crear Módulo AMD

**Archivo**: `theme/myboost/amd/src/mymodule.js`

```javascript
define(['jquery', 'core/templates', 'core/notification'],
function($, Templates, Notification) {

    return {
        init: function(selector) {
            var container = $(selector);

            container.on('click', '.action-button', function(e) {
                e.preventDefault();
                var itemId = $(this).data('item-id');

                // Llamar AJAX
                $.ajax({
                    url: M.cfg.wwwroot + '/theme/myboost/ajax/action.php',
                    method: 'POST',
                    data: {
                        itemid: itemId,
                        sesskey: M.cfg.sesskey
                    }
                }).done(function(response) {
                    // Renderizar resultado
                    Templates.renderForPromise('theme_myboost/result', response)
                        .then(function(result) {
                            container.find('.result-area').html(result.html);
                            Templates.runTemplateJS(result.js);
                        });
                }).fail(Notification.exception);
            });
        }
    };
});
```

### Cargar desde Template

```mustache
{{#js}}
require(['theme_myboost/mymodule'], function(MyModule) {
    MyModule.init('[data-region="mywidget"]');
});
{{/js}}
```

### Cargar desde PHP

```php
$PAGE->requires->js_call_amd('theme_myboost/mymodule', 'init', ['#container']);
```

---

## Referencia Rápida

### Sintaxis Mustache

| Sintaxis | Descripción |
|----------|-------------|
| `{{variable}}` | Variable escapada |
| `{{{variable}}}` | Variable sin escapar |
| `{{#section}}...{{/section}}` | Sección/Iterador |
| `{{^section}}...{{/section}}` | Inverso |
| `{{> partial}}` | Incluir partial |
| `{{! comment }}` | Comentario |
| `{{#str}}key, component{{/str}}` | String de idioma |
| `{{#pix}}icon, component{{/pix}}` | Icono |
| `{{#js}}...{{/js}}` | JavaScript |
| `{{uniqid}}` | ID único |

### Helpers de Moodle

| Helper | Uso | Descripción |
|--------|-----|-------------|
| `str` | `{{#str}}key, component{{/str}}` | String de idioma |
| `pix` | `{{#pix}}icon, component, alt{{/pix}}` | Icono |
| `js` | `{{#js}}code{{/js}}` | JavaScript |
| `quote` | `{{#quote}}text{{/quote}}` | Escapar para JS |
| `shortentext` | `{{#shortentext}}50, text{{/shortentext}}` | Acortar texto |
| `userdate` | `{{#userdate}}timestamp, format{{/userdate}}` | Formatear fecha |
| `uniqid` | `{{uniqid}}` | ID único |
| `cleanstr` | `{{#cleanstr}}key, component{{/cleanstr}}` | String limpia |

### Archivos Clave

| Componente | Ruta |
|------------|------|
| Motor Mustache | `/lib/classes/output/mustache_engine.php` |
| Template Finder | `/lib/classes/output/mustache_template_finder.php` |
| Renderer Base | `/lib/classes/output/renderer_base.php` |
| Templatable | `/lib/classes/output/templatable.php` |
| Config Boost | `/theme/boost/config.php` |
| Lib Boost | `/theme/boost/lib.php` |
| Layout Drawers | `/theme/boost/layout/drawers.php` |
| SCSS Presets | `/theme/boost/scss/preset/` |
| Templates Boost | `/theme/boost/templates/` |

### Renderizado

```php
// Desde PHP
$OUTPUT->render_from_template('component/template', $context);

// Con renderable
$renderer->render($widget);
```

```javascript
// Desde JavaScript
Templates.renderForPromise('component/template', context)
    .then(({html, js}) => { ... });
```
