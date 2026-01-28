# Diagnóstico del Plugin UDES v2.0

## Checklist de Verificación

### 1. Archivos Requeridos

Verifica que existan todos estos archivos:

```
mod/udes/
├── version.php                      ✓ Requerido
├── lib.php                          ✓ Requerido
├── mod_form.php                     ✓ Requerido
├── view.php                         ✓ Requerido
├── db/
│   ├── access.php                   ✓ Requerido
│   └── install.xml                  ✓ Requerido
├── lang/
│   ├── es/udes.php                  ✓ Requerido
│   └── en/udes.php                  ✓ Requerido
├── classes/
│   ├── caracterizacion_manager.php  ✓ v2.0
│   └── recurso_manager.php          ✓ v2.0
├── caracterizacion.php              ✓ v2.0
├── caracterizacion_form.php         ✓ v2.0
├── caracterizacion_view.php         ✓ v2.0
├── approve.php                      ✓ v2.0
├── save_comentario.php              ✓ v2.0
└── recursos.php                     ✓ v2.0
```

### 2. Verificación de Base de Datos

Ejecuta estas consultas SQL en tu base de datos Moodle:

```sql
-- 1. Verificar que las tablas existan
SHOW TABLES LIKE 'mdl_udes%';
-- Debe mostrar 7 tablas

-- 2. Verificar estructura de udes_caracterizacion
DESCRIBE mdl_udes_caracterizacion;
-- Debe tener los campos: id, udesid, nombre, programa_academico, nombre_curso,
-- asesor_metodologico, experto_disciplinar, par_academico, corrector_estilo,
-- coordinacion_produccion, produccion, alistamiento, cvp, sala_clases,
-- video_bienvenida, foro_curso, mapa_curso, currentphase, estado

-- 3. Verificar FK en udes_recursos
SELECT COLUMN_NAME, REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'tu_base_datos'
  AND TABLE_NAME = 'mdl_udes_recursos'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
-- Debe mostrar FK a caracterizacionid (no udesid)
```

### 3. Problemas Comunes y Soluciones

#### Problema: No aparece el botón "Nueva Caracterización"

**Causa:** El usuario no tiene los permisos necesarios.

**Solución:**
1. Ir a: Administración del sitio > Usuarios > Permisos > Definir roles
2. Editar el rol "Profesor" o "Manager"
3. Buscar las capacidades:
   - `mod/udes:expertodisciplinar` → Permitir
   - `mod/udes:asesormetodologico` → Permitir
   - `mod/udes:manageall` → Permitir (para administradores)
4. Guardar cambios

#### Problema: Error "Class 'mod_udes\caracterizacion_manager' not found"

**Causa:** La clase no se autoload correctamente.

**Solución:**
1. Verificar que el archivo exista en: `mod/udes/classes/caracterizacion_manager.php`
2. Verificar que el namespace sea correcto: `namespace mod_udes;`
3. Ejecutar: Administración del sitio > Desarrollo > Purgar todas las cachés

#### Problema: Error "get_string() error: string does not exist"

**Causa:** Faltan cadenas de idioma.

**Solución:**
1. Verificar que existan los archivos:
   - `mod/udes/lang/es/udes.php`
   - `mod/udes/lang/en/udes.php`
2. Verificar que contengan todas las cadenas necesarias
3. Purgar cachés de idioma

#### Problema: Error al crear caracterización "caracterizacion_nombre cannot be null"

**Causa:** El campo nombre no se está enviando correctamente desde el formulario.

**Solución:**
1. Verificar que `caracterizacion_form.php` tenga el campo nombre definido
2. Verificar que el formulario use `$mform->addElement('text', 'nombre', ...)`
3. Verificar validación en `validation()` method

#### Problema: Error al abrir caracterizacion_view.php

**Causa:** Parámetro caracterizacionid faltante o inválido.

**Solución:**
Verificar que la URL incluya el parámetro:
```
/mod/udes/caracterizacion_view.php?id=XX&caracterizacionid=YY
```

### 4. Habilitar Modo de Depuración

Para ver errores detallados:

1. Ir a: Administración del sitio > Desarrollo > Modo de depuración
2. Seleccionar: **DEVELOPER: mostrar mensajes de error, advertencias y mensajes de depuración**
3. Marcar: **Mostrar origen de la depuración**
4. Guardar cambios

Luego intenta reproducir el error y copia el mensaje completo.

### 5. Verificar Logs de Moodle

1. Ir a: Administración del sitio > Informes > Registros
2. Filtrar por:
   - **Módulo de actividad**: Producción de Recursos UDES
   - **Fecha**: Última hora
3. Buscar entradas con "error" o "exception"

### 6. Comandos de Diagnóstico (Terminal/SSH)

Si tienes acceso al servidor, ejecuta:

```bash
# Verificar permisos de archivos
ls -la /ruta/a/moodle/mod/udes/
# Todos los archivos deben ser legibles por el servidor web

# Verificar logs de PHP
tail -f /var/log/php_errors.log
# O donde esté configurado tu log de PHP

# Verificar logs de Apache/Nginx
tail -f /var/log/apache2/error.log
# O el log de tu servidor web
```

### 7. Test Rápido de Funcionalidad

Ejecuta este código en Moodle para probar la clase manager:

```php
// En: Administración del sitio > Desarrollo > Ejecutar código PHP

global $DB;

// 1. Verificar que la clase existe
if (class_exists('mod_udes\caracterizacion_manager')) {
    echo "✓ Clase caracterizacion_manager encontrada\n";
} else {
    echo "✗ Clase caracterizacion_manager NO encontrada\n";
}

// 2. Verificar que las tablas existen
$tables = $DB->get_tables();
$required_tables = ['udes', 'udes_caracterizacion', 'udes_recursos'];
foreach ($required_tables as $table) {
    if (in_array($table, $tables)) {
        echo "✓ Tabla {$table} existe\n";
    } else {
        echo "✗ Tabla {$table} NO existe\n";
    }
}

// 3. Verificar cadenas de idioma
$strings = ['nueva_caracterizacion', 'lista_caracterizaciones', 'sin_caracterizaciones'];
foreach ($strings as $string) {
    try {
        $value = get_string($string, 'mod_udes');
        echo "✓ Cadena '{$string}' encontrada: {$value}\n";
    } catch (Exception $e) {
        echo "✗ Cadena '{$string}' NO encontrada\n";
    }
}
```

### 8. Reinstalación Limpia (Si es necesario)

Si hay problemas graves:

```sql
-- 1. Desinstalar el plugin desde la interfaz de Moodle
-- 2. Luego ejecutar estas consultas SQL para limpieza completa:

DROP TABLE IF EXISTS mdl_udes_role_assignments;
DROP TABLE IF EXISTS mdl_udes_comentarios;
DROP TABLE IF EXISTS mdl_udes_aprobaciones;
DROP TABLE IF EXISTS mdl_udes_workflow;
DROP TABLE IF EXISTS mdl_udes_recursos;
DROP TABLE IF EXISTS mdl_udes_caracterizacion;
DROP TABLE IF EXISTS mdl_udes;

DELETE FROM mdl_config_plugins WHERE plugin = 'mod_udes';
```

```bash
# 3. Eliminar archivos del plugin
rm -rf /ruta/a/moodle/mod/udes/

# 4. Copiar nuevamente los archivos del plugin

# 5. Instalar desde: Administración del sitio > Notificaciones
```

### 9. Contacto para Soporte

Si después de seguir estos pasos el problema persiste, proporciona:

1. **Versión de Moodle**: (ej: 4.0, 4.1, 4.3)
2. **Versión de PHP**: (ejecuta `php -v`)
3. **Mensaje de error completo** (con modo depuración activado)
4. **Logs de Moodle** (últimas entradas relevantes)
5. **Resultado de las consultas SQL** de verificación de tablas

---

**Última actualización:** 2026-01-21
**Versión del plugin:** v2.0.0 ALPHA
