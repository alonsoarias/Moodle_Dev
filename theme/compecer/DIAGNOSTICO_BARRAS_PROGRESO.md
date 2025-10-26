# Diagnóstico y Activación de Barras de Progreso - Theme Compecer

## Fecha: 2025-10-26

## 🔧 SOLUCIÓN IMPLEMENTADA

Se implementó un **enfoque híbrido** para las barras de progreso:

1. **Barra Global**: Renderizada server-side (PHP) - **Funciona inmediatamente**
2. **Barras por Sección**: Inyectadas client-side (JavaScript inline) - **No requiere compilación**

---

## ✅ PASOS PARA ACTIVAR

### Paso 1: Purgar Cachés de Moodle

**Opción A - Interfaz Web:**
1. Ir a: `Administración del sitio > Desarrollo > Purgar todas las cachés`
2. Click en "Purgar todas las cachés"
3. Esperar confirmación

**Opción B - Línea de comandos (si tienes acceso PHP):**
```bash
php admin/cli/purge_caches.php
```

### Paso 2: Actualizar Base de Datos (Registrar Web Service)

**Opción A - Interfaz Web:**
1. Ir a: `Administración del sitio > Notificaciones`
2. Si aparece un aviso de actualización, hacer click en "Actualizar base de datos de Moodle"

**Opción B - Línea de comandos:**
```bash
php admin/cli/upgrade.php --non-interactive
```

### Paso 3: Verificar Web Service Registrado

1. Ir a: `Administración del sitio > Servidor > Servicios web > Funciones externas`
2. Buscar: `theme_compecer_get_course_progress`
3. Verificar que aparezca en la lista

**Si NO aparece:**
- Ejecutar Paso 2 nuevamente
- Verificar que el archivo existe: `theme/compecer/db/services.php`

### Paso 4: Habilitar Completion en un Curso de Prueba

1. Ir a un curso
2. `Configuración del curso > Editar ajustes`
3. Expandir "Rastreo de finalización"
4. Seleccionar: **"Sí"** en "Habilitar seguimiento de finalización"
5. Guardar cambios

### Paso 5: Habilitar Completion en Actividades

1. Editar una actividad (cualquier recurso, cuestionario, tarea, etc.)
2. Expandir sección: **"Finalización de actividad"**
3. Seleccionar condición de completion (ejemplo: "Los estudiantes pueden marcar manualmente...")
4. Guardar

Repetir para varias actividades en diferentes secciones.

---

## 🧪 VERIFICAR QUE FUNCIONA

### Verificación Básica

1. **Abrir un curso con completion habilitado** como estudiante
2. **Abrir el drawer del courseindex** (menú lateral del curso)
3. **Buscar**:
   - ✅ Barra de progreso global en la parte superior
   - ✅ Barras de progreso en cada sección
   - ✅ Porcentajes correctos

### Verificación con Consola del Navegador

1. Abrir DevTools (F12)
2. Ir a pestaña "Console"
3. Buscar mensajes:
   - ✅ No debe haber errores rojos de JavaScript
   - ⚠️ Puede aparecer log informativo si web service falla

### Si NO aparece la Barra Global:

**Causa:** El renderer personalizado no se está usando.

**Solución Temporal - JavaScript Inline:**

Editar `theme/compecer/templates/core_courseformat/local/courseindex/drawer.mustache` y reemplazar la sección `{{#hasprogress}}...{{/hasprogress}}` con:

```mustache
{{! Placeholder para barra global - se inyectará via JavaScript }}
<div id="courseindex-global-progress-placeholder"></div>
```

Luego agregar al inicio del bloque `{{#js}}`:

```javascript
// Inject global progress via JavaScript as fallback
require(['core/ajax'], function(Ajax) {
    var courseId = M.cfg.courseId || {{courseid}};
    if (!courseId) return;

    Ajax.call([{
        methodname: 'theme_compecer_get_course_progress',
        args: {courseid: courseId}
    }])[0].done(function(data) {
        if (!data.global || !data.global.enabled || data.global.percentage === 0) {
            return;
        }

        var placeholder = document.getElementById('courseindex-global-progress-placeholder');
        if (!placeholder) return;

        var html = '<div class="courseindex-progress-global mt-3 mb-3">' +
            '<div class="d-flex justify-content-between align-items-center mb-1">' +
            '<span class="progress-label small">Progreso del Curso</span>' +
            '<span class="progress-percentage small font-weight-bold">' + data.global.percentage + '%</span>' +
            '</div>' +
            '<div class="progress" style="height: 8px;">' +
            '<div class="progress-bar bg-success" role="progressbar" ' +
            'style="width: ' + data.global.percentage + '%;" ' +
            'aria-valuenow="' + data.global.percentage + '" ' +
            'aria-valuemin="0" aria-valuemax="100"></div>' +
            '</div>' +
            '</div>';

        placeholder.outerHTML = html;
    });
});
```

---

## 🐛 SOLUCIÓN DE PROBLEMAS

### Problema 1: Web Service no responde (Error 404)

**Síntomas:**
- Consola muestra: `Error calling web service`
- Barras de sección no aparecen

**Soluciones:**
1. Verificar que ejecutaste Paso 2 (Actualizar BD)
2. Ir a: `Admin > Servidor > Servicios web > Resumen`
3. Verificar que dice: "Servicios web habilitados: Sí"
4. Si dice "No":
   - Ir a `Admin > Servidor > Servicios web > Configuración general`
   - Marcar "Habilitar servicios web"
   - Guardar

### Problema 2: Barras no tienen estilos (aparecen sin color)

**Síntomas:**
- Las barras aparecen pero sin el color verde
- No tienen el diseño correcto

**Soluciones:**
1. Purgar cachés (Paso 1)
2. Verificar que existe: `theme/compecer/scss/courseindex.scss`
3. Forzar regeneración de estilos:
   - `Admin > Apariencia > Temas > Compecer`
   - Hacer cualquier cambio en settings y guardar
   - Esto fuerza reconstrucción de CSS

### Problema 3: CourseId undefined

**Síntomas:**
- Consola muestra: `courseId is undefined`
- JavaScript no se ejecuta

**Solución:**

Editar template y cambiar:
```javascript
var courseId = M.cfg.courseId || {{courseid}};
```

Por:
```javascript
var courseId = {{courseid}};
if (!courseId && typeof M !== 'undefined' && M.cfg) {
    courseId = M.cfg.courseId;
}
```

### Problema 4: Completion no está habilitado

**Síntomas:**
- No aparecen barras aunque todo esté configurado
- Mensaje en consola: "completion not enabled"

**Solución:**
1. Verificar Paso 4 y Paso 5
2. Como admin, ir al curso
3. `Admin > Informes del curso > Finalización de actividades`
4. Verificar que las actividades tengan criterios configurados

---

## 🧑‍💻 VERIFICACIÓN TÉCNICA AVANZADA

### Verificar Renderer se está usando

Agregar temporalmente al inicio de `theme/compecer/classes/output/courseformat/courseindex_drawer.php`:

```php
public function export_for_template(renderer_base $output): array {
    error_log('COMPECER RENDERER USED'); // ← Agregar esta línea
    $data = parent::export_for_template($output);
    // ...resto del código
```

Luego:
1. Purgar cachés
2. Recargar página del curso
3. Revisar logs de PHP: `moodledata/error_log` o logs del servidor
4. Buscar: `COMPECER RENDERER USED`

**Si NO aparece:** El renderer no se está usando → Usar solución JavaScript inline del Problema "Si NO aparece la Barra Global".

### Verificar Web Service con cURL

```bash
# Reemplazar valores entre <>
curl -X POST 'https://tu-moodle.com/webservice/rest/server.php' \
  -d 'wstoken=<TU_TOKEN>' \
  -d 'moodlewsrestformat=json' \
  -d 'wsfunction=theme_compecer_get_course_progress' \
  -d 'courseid=<COURSE_ID>'
```

Para obtener token:
1. `Admin > Servidor > Servicios web > Gestionar tokens`
2. Crear token para tu usuario

**Respuesta esperada:**
```json
{
  "courseid": 2,
  "global": {
    "percentage": 50,
    "enabled": true
  },
  "sections": [
    {
      "sectionnumber": 1,
      "sectionid": 10,
      "percentage": 75,
      "total": 4,
      "completed": 3,
      "enabled": true
    }
  ]
}
```

---

## 📊 ESTRUCTURA DE ARCHIVOS

### Archivos Críticos (DEBEN existir):

```
theme/compecer/
├── classes/
│   ├── courseindex_helper.php ✅
│   ├── external/
│   │   └── get_course_progress.php ✅
│   └── output/
│       └── courseformat/
│           └── courseindex_drawer.php ✅ (opcional si JS inline funciona)
├── db/
│   └── services.php ✅ (debe incluir theme_compecer_get_course_progress)
├── templates/
│   └── core_courseformat/
│       └── local/
│           └── courseindex/
│               └── drawer.mustache ✅ (modificado con JS inline)
├── scss/
│   └── courseindex.scss ✅
└── lang/
    ├── en/theme_compecer.php ✅ (strings de progreso)
    └── es/theme_compecer.php ✅ (strings de progreso)
```

### Verificar que archivos existen:

```bash
cd /path/to/moodle
ls -la theme/compecer/classes/courseindex_helper.php
ls -la theme/compecer/classes/external/get_course_progress.php
ls -la theme/compecer/db/services.php
ls -la theme/compecer/scss/courseindex.scss
```

---

## ✨ RESULTADO ESPERADO

Cuando todo funcione correctamente:

### 1. Barra Global (Superior del CourseIndex)
```
Course Progress                    65%
█████████████████░░░░░░░░░░░░░░
```

### 2. Barra por Sección (Dentro de cada sección)
```
Section 1: Introduction
3 of 4 activities completed        75%
████████████████████░░░░░░
  □ Introduction video
  ✓ Course overview
  ✓ Forum discussion
  □ Quiz 1
```

### 3. Traffic Lights (Indicadores de estado)
- 🟢 Verde = Completado
- 🟡 Amarillo = En progreso
- 🔴 Rojo = Pendiente
- ⚫ Gris = No disponible

---

## 🆘 CONTACTO SOPORTE

Si después de seguir todos los pasos aún no funciona:

1. **Recopilar información:**
   - Versión de Moodle
   - Mensaje de error exacto (captura de pantalla)
   - Logs de consola JavaScript
   - Logs de PHP

2. **Verificar requisitos mínimos:**
   - Moodle 4.0 o superior
   - Theme Compecer instalado y activo
   - Completion habilitado en el sitio

3. **Crear issue en GitHub** con la información recopilada

---

## 📝 NOTAS IMPORTANTES

- ⚠️ **NO** se requiere compilar JavaScript - La versión actual usa JavaScript inline
- ⚠️ La barra global puede mostrarse via server-side O client-side (ambas formas funcionan)
- ⚠️ Las barras por sección SIEMPRE usan client-side (AJAX)
- ✅ Los estilos CSS se incluyen automáticamente desde `courseindex.scss`
- ✅ Los strings bilingües están incluidos (EN/ES)

---

**Última actualización:** 2025-10-26
**Versión:** 2.0 (Simplificada - Sin compilación AMD)
