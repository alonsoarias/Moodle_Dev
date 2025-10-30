# 📋 Instrucciones de Prueba - Renderer Override para Mostrar Todos los Profesores

## 🎯 Objetivo
Verificar que el theme_inteb muestre **TODOS** los profesores (roles `teacher` y `editingteacher`) en cursos con formato `remuiformat`.

## ✅ Prerrequisitos

1. **Moodle funcional** con acceso de administrador
2. **theme_inteb** instalado y activo
3. **format_remuiformat** instalado
4. Usuarios con roles **teacher** y **editingteacher** disponibles

## 🧪 Procedimiento de Pruebas

### Paso 1: Purgar Cachés

Es **CRÍTICO** purgar las cachés de Moodle después de la actualización:

**Opción A - Desde la interfaz web:**
1. Acceder como administrador
2. Navegar a: `Administración del sitio > Desarrollo > Purgar todas las cachés`
3. Hacer clic en "Purgar todas las cachés"

**Opción B - Desde línea de comandos:**
```bash
cd /home/user/Moodle_Dev
php admin/cli/purge_caches.php
```

### Paso 2: Preparar el Curso de Prueba

1. **Crear o seleccionar un curso**
   - Puede ser un curso nuevo o uno existente

2. **Configurar el formato del curso**
   - Ir a: `Configuración del curso > Formato del curso`
   - Seleccionar: `Edwiser Course Formats` (formato remuiformat)
   - Guardar cambios

3. **Asignar profesores al curso**

   Necesitas al menos:
   - **1 usuario con rol `editingteacher`** (Profesor con permisos de edición)
   - **1 usuario con rol `teacher`** (Profesor sin permisos de edición)

   Para asignar:
   - Ir a: `Participantes > Matricular usuarios`
   - Seleccionar usuario
   - Asignar rol correspondiente
   - Guardar

### Paso 3: Verificar Permisos de Roles

Confirmar que los roles tienen las capabilities correctas:

**Rol `editingteacher`:**
- ✅ Debe tener: `mod/folder:managefiles` → **Permitir**
- ✅ Debe tener: `moodle/course:viewhiddenactivities` → **Permitir**

**Rol `teacher`:**
- ❌ NO debe tener: `mod/folder:managefiles`
- ✅ Debe tener: `moodle/course:viewhiddenactivities` → **Permitir**

Para verificar:
1. Ir a: `Administración del sitio > Usuarios > Permisos > Definir roles`
2. Hacer clic en el rol correspondiente
3. Buscar las capabilities mencionadas

### Paso 4: Ejecutar Script de Debugging (Opcional pero Recomendado)

Antes de probar en el navegador, ejecutar el script de debugging:

**Opción A - Desde navegador:**
```
https://tu-moodle.com/theme/inteb/debug_teachers_display.php?courseid=123
```
(Reemplazar `123` con el ID del curso de prueba)

**Opción B - Desde línea de comandos:**
```bash
cd /home/user/Moodle_Dev/theme/inteb
php debug_teachers_display.php courseid=123
```

**Qué verificar en la salida:**
- ✅ Sección 1️⃣ debe mostrar solo usuarios con rol `editingteacher`
- ✅ Sección 2️⃣ debe mostrar usuarios con roles `teacher` Y `editingteacher`
- ✅ Sección 3️⃣ debe mostrar que el helper retorna ambos tipos de profesores
- ✅ Sección 4️⃣ debe confirmar que el renderer existe

### Paso 5: Probar en el Navegador

1. **Activar theme_inteb:**
   - Ir a: `Administración del sitio > Apariencia > Selector de temas`
   - Seleccionar `inteb` como tema del sitio
   - Guardar cambios

2. **Acceder al curso de prueba:**
   - Navegar al curso configurado con formato remuiformat
   - Ver la página principal del curso

3. **Verificar el header del curso:**
   - En la sección de profesores del header, debes ver:
     - ✅ Profesores con rol `editingteacher`
     - ✅ Profesores con rol `teacher`
     - ✅ Ambos tipos aparecen juntos

### Paso 6: Probar Escenarios Adicionales

#### Escenario A: Solo editingteacher
- Remover todos los profesores con rol `teacher`
- Dejar solo `editingteacher`
- **Resultado esperado:** Se muestra solo el `editingteacher`

#### Escenario B: Solo teacher
- Remover todos los profesores con rol `editingteacher`
- Dejar solo `teacher`
- **Resultado esperado:** Se muestra solo el `teacher`

#### Escenario C: Múltiples de cada tipo
- Asignar 2+ usuarios con rol `editingteacher`
- Asignar 2+ usuarios con rol `teacher`
- **Resultado esperado:** Se muestran TODOS los profesores de ambos tipos

#### Escenario D: Otro formato de curso
- Cambiar el formato del curso a otro (ej: `topics`, `weeks`)
- **Resultado esperado:** Los profesores deberían seguir mostrándose correctamente
  (el renderer de inteb para otros formatos ya funcionaba antes)

## 🔍 Troubleshooting

### Problema: No aparecen los profesores con rol `teacher`

**Soluciones:**
1. ✅ Purgar cachés nuevamente
2. ✅ Verificar que el rol `teacher` tenga la capability `moodle/course:viewhiddenactivities`
3. ✅ Verificar que los usuarios estén matriculados activamente
4. ✅ Ejecutar el script de debugging para ver qué profesores detecta
5. ✅ Verificar que theme_inteb esté activo
6. ✅ Verificar que el curso tenga formato `remuiformat`

### Problema: Error al cargar la página

**Soluciones:**
1. Revisar logs de Moodle: `Administración del sitio > Informes > Registros`
2. Revisar logs del servidor web (Apache/Nginx)
3. Verificar sintaxis PHP: `php -l theme/inteb/renderers.php`

### Problema: Aparecen duplicados

**Soluciones:**
1. Esto NO debería ocurrir (el helper elimina duplicados)
2. Si ocurre, reportar como bug con detalles del escenario

## 📊 Criterios de Éxito

La implementación es exitosa si:

- ✅ En cursos con formato `remuiformat`, se muestran ambos tipos de profesores
- ✅ No hay errores en logs de Moodle
- ✅ No hay errores en logs del servidor web
- ✅ Los profesores se muestran ordenados alfabéticamente
- ✅ No hay duplicados
- ✅ Otros formatos de curso siguen funcionando correctamente
- ✅ El comportamiento es consistente en layout `card` y `list`

## 📁 Archivos Involucrados

```
theme/inteb/
├── config.php                             # Configuración del tema
├── classes/
│   ├── output/
│   │   └── format_remuiformat_renderer.php  # Renderer override (NUEVO)
│   └── format_remuiformat_helper.php      # Helper con lógica dual capability
├── debug_teachers_display.php             # Script de debugging (NUEVO)
└── lib.php                                # Funciones del tema

course/format/remuiformat/
├── renderer.php                           # Renderer original (NO MODIFICADO)
├── format.php                             # Instanciación de renderables
└── classes/output/
    ├── card_one_section_renderable.php    # Renderable card (NO MODIFICADO)
    └── list_one_section_renderable.php    # Renderable list (NO MODIFICADO)
```

## 🎓 Explicación Técnica

### ¿Cómo Funciona?

1. **Moodle detecta que theme_inteb tiene renderer override** (vía autoloading PSR-4)

2. **Cuando format_remuiformat necesita renderizar**, Moodle carga:
   - Original: `format_remuiformat_renderer`
   - Override: `\theme_inteb\output\format_remuiformat_renderer` (extiende el original)

3. **Nuestro renderer intercepta los métodos:**
   - `render_card_one_section()` para layout de tarjetas
   - `render_list_one_section()` para layout de lista

4. **En estos métodos:**
   - Obtiene contexto del template original (padre)
   - Usa **Reflection** para acceder a propiedad privada `$course`
   - Llama a `format_remuiformat_helper::get_enrolled_teachers_context()`
   - Reemplaza datos de profesores con versión completa
   - Renderiza el template con datos modificados

5. **El helper obtiene profesores usando dos capabilities:**
   - `mod/folder:managefiles` → Solo `editingteacher`
   - `moodle/course:viewhiddenactivities` → `teacher` + `editingteacher`
   - Combina ambos eliminando duplicados
   - Retorna array completo

### Ventajas de Este Enfoque

- ✅ **NO modifica plugins de terceros** (format_remuiformat, theme_remui)
- ✅ **Usa mecanismo estándar de Moodle** (renderer override)
- ✅ **Sobrevivirá actualizaciones** de format_remuiformat
- ✅ **Reutiliza lógica existente** (helper ya implementado)
- ✅ **Fácil de mantener y debuggear**

## 📞 Soporte

Si encuentras problemas durante las pruebas:

1. Ejecutar el script de debugging y capturar la salida
2. Revisar logs de Moodle y servidor web
3. Verificar que todos los prerrequisitos estén cumplidos
4. Documentar el escenario exacto que causa el problema

---

**Última actualización:** 2025-10-30
**Commit:** bb47a90cc
**Branch:** claude/analyze-teacher-roles-themeinteb-011CUe8hR8732ssp3KfdjyeG
