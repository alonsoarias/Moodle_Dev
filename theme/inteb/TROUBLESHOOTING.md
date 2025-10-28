# Theme Inteb - Campos Personalizados y Profesores

## ⚠️ TROUBLESHOOTING - Campos personalizados no se muestran

Si los campos personalizados (Duration y Skill Level) no aparecen en las course cards del Dashboard, sigue estos pasos:

### 1. Actualizar la Base de Datos

Después de instalar/actualizar el tema, debes actualizar Moodle para registrar el webservice:

```bash
# Opción 1: Via navegador
# Ir a: Administración del sitio > Notificaciones
# Hacer clic en "Actualizar base de datos de Moodle"

# Opción 2: Via CLI
php admin/cli/upgrade.php
```

### 2. Limpiar TODAS las Cachés

```bash
# Opción 1: CLI (Recomendado)
php admin/cli/purge_caches.php

# Opción 2: Via navegador
# Ir a: Administración del sitio > Desarrollo > Purgar todas las cachés
```

### 3. Verificar que el Webservice está Registrado

1. Ir a: **Administración del sitio → Servidor → Servicios web → Funciones externas**
2. Buscar: `theme_inteb_get_enhanced_courses`
3. Debe aparecer en la lista

**Si NO aparece:**
- Volver al paso 1 (Actualizar BD)
- Limpiar cachés nuevamente

### 4. Verificar que el JavaScript se está Cargando

1. Abrir el Dashboard
2. Presionar F12 (Herramientas de desarrollo)
3. Ir a la pestaña **Console**
4. Buscar errores relacionados con `theme_inteb/enhance_course_cards`

**Errores comunes:**
- `Module 'theme_inteb/enhance_course_cards' not found` → Limpiar cachés
- `theme_inteb_get_enhanced_courses not found` → Actualizar BD
- `AJAX error` → Verificar webservice registrado

### 5. Verificar en la Consola del Navegador

En la consola de JavaScript (F12 → Console), ejecuta:

```javascript
// Ver si el módulo está cargado
require(['theme_inteb/enhance_course_cards'], function(module) {
    console.log('Módulo cargado:', module);
    module.init();
});

// Verificar que hay cards
console.log('Cards encontradas:', $('.dashboard-card').length);

// Verificar IDs de cursos
$('.dashboard-card').each(function() {
    console.log('Course ID:', $(this).data('course-id'));
});
```

### 6. Configurar Campos Personalizados en los Cursos

Los campos solo aparecen si están configurados:

1. Ir al curso → **Editar configuración**
2. Buscar sección **"Campos personalizados RemUI"**
3. Configurar:
   - **Course Duration**: Ejemplo: "4 weeks", "8 hours", "30 días"
   - **Skill Level**: Elegir: Beginner, Intermediate, o Advanced
4. **Guardar cambios**
5. Refrescar el Dashboard

**IMPORTANTE:** Si no ves la sección "Campos personalizados RemUI":
- Verificar que theme_remui está instalado
- Verificar que los custom fields están creados en:
  **Administración del sitio → Cursos → Campos personalizados del curso**

### 7. Verificar Logs de Moodle

Si aún no funciona, revisar logs:

**Administración del sitio → Informes → Registros**

Buscar errores relacionados con:
- `theme_inteb`
- `get_enhanced_courses`
- `enhance_course_cards`

### 8. Depuración Avanzada

Habilitar modo de depuración:

1. Ir a: **Administración del sitio → Desarrollo → Depuración**
2. Establecer:
   - **Mensajes de depuración**: DEVELOPER
   - **Mostrar depuración**: Sí
3. Recargar Dashboard y ver errores detallados
4. **NO OLVIDAR** desactivar depuración en producción

---

## ✅ Verificación de Funcionamiento

Después de seguir los pasos, verifica:

### En el Dashboard:

```
┌─────────────────────────────────────┐
│ [Imagen del curso]                  │
│ Categoría: Programación             │
│ Nombre: Intro a Python              │
│ 👤 Juan Pérez                       │
│ 👤 María García                     │
├─────────────────────────────────────┤
│ ⏱️  4 weeks    🏆 Intermediate     │ ← Deben aparecer aquí
│ ████████░░ 80%                      │
│                     [Ver curso →]   │
└─────────────────────────────────────┘
```

### En la Consola (F12):

**NO deben aparecer errores de:**
- `Module not found`
- `Webservice not found`
- `AJAX error`

**DEBE aparecer en Network (F12 → Network → XHR):**
- Request a: `lib/ajax/service.php`
- Con parámetro: `theme_inteb_get_enhanced_courses`
- Status: 200 OK
- Response con datos de cursos

---

## 🔧 Solución de Problemas Específicos

### Problema: "Module theme_inteb/enhance_course_cards not found"

**Causa:** Caché de JavaScript no limpiada

**Solución:**
```bash
php admin/cli/purge_caches.php
```

Si persiste:
```bash
# Eliminar caché de tema manualmente
rm -rf /path/to/moodledata/cache/theme/inteb/*
rm -rf /path/to/moodledata/localcache/*
```

### Problema: "Webservice theme_inteb_get_enhanced_courses not found"

**Causa:** Base de datos no actualizada

**Solución:**
```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

### Problema: Los campos aparecen pero sin valores

**Causa:** Los campos no están configurados en el curso

**Solución:**
1. Editar curso
2. Configurar "Campos personalizados RemUI"
3. Guardar

### Problema: JavaScript se carga pero no hace nada

**Causa:** Selector de cards incorrecto o cards no existen

**En consola (F12):**
```javascript
// Verificar que encuentra cards
console.log('Cards:', $('.dashboard-card').length);

// Si es 0, las cards no se cargaron aún
// Esperar 2 segundos y volver a verificar
setTimeout(function() {
    console.log('Cards después:', $('.dashboard-card').length);
}, 2000);
```

### Problema: AJAX retorna error 403

**Causa:** Usuario sin permisos o webservice no habilitado

**Solución:**
1. Verificar que el usuario está logueado
2. Ir a: Administración → Servidor → Servicios web
3. Verificar que "Habilitar servicios web" está marcado
4. Verificar que "Protocolos habilitados" incluye "rest" o "ajax"

---

## 📋 Checklist Final

Antes de reportar un problema, verificar:

- [ ] Actualicé la base de datos (upgrade.php)
- [ ] Limpié TODAS las cachés (purge_caches.php)
- [ ] El webservice `theme_inteb_get_enhanced_courses` está registrado
- [ ] Los campos personalizados están configurados en el curso
- [ ] No hay errores en consola de JavaScript (F12)
- [ ] El request AJAX a `theme_inteb_get_enhanced_courses` retorna 200 OK
- [ ] La respuesta AJAX contiene los datos esperados
- [ ] El tema está activado y actualizado a versión 2025102807

---

## 🆘 Soporte

Si después de seguir TODOS los pasos el problema persiste:

1. Recopilar información:
   - Screenshot de consola con errores
   - Screenshot de Network tab con request AJAX
   - Logs de Moodle con depuración DEVELOPER
   - Versión de Moodle y PHP

2. Verificar que:
   - Theme RemUI está instalado y actualizado
   - Theme Inteb está activado
   - No hay conflictos con otros plugins

3. Contactar: soporte@ingeweb.co
