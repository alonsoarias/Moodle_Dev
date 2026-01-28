# Soluciones Rápidas - Plugin UDES v2.0

## Problemas Más Comunes y Sus Soluciones

### 1. NO APARECE EL BOTÓN "NUEVA CARACTERIZACIÓN"

**Problema:** El plugin se instala correctamente pero no aparece el botón para crear caracterizaciones.

**Causa:** El usuario no tiene los permisos necesarios.

**Solución (5 minutos):**

1. Ir a: **Administración del sitio > Usuarios > Permisos > Definir roles**
2. Click en el icono ⚙️ junto al rol **"Teacher" (Profesor con permiso de edición)** o **"Manager"**
3. En el cuadro de búsqueda, escribir: `mod/udes`
4. Asegurarse de que estas capacidades estén en **"Permitir"** (marca verde ✓):
   - `mod/udes:addinstance`
   - `mod/udes:view`
   - `mod/udes:expertodisciplinar`
   - `mod/udes:asesormetodologico`
   - `mod/udes:manageall` (solo para managers)
5. Click en **"Guardar cambios"**
6. **IMPORTANTE:** Ir a **Administración del sitio > Desarrollo > Purgar todas las cachés**
7. Refrescar la página de la actividad UDES

---

### 2. ERROR: "Class 'mod_udes\caracterizacion_manager' not found"

**Problema:** Error PHP al intentar abrir la actividad.

**Causa:** Las clases no se cargan correctamente por caché antiguo.

**Solución (2 minutos):**

1. Ir a: **Administración del sitio > Desarrollo > Purgar todas las cachés**
2. Verificar que el archivo exista en: `mod/udes/classes/caracterizacion_manager.php`
3. Si persiste, reiniciar el servidor web:
   ```bash
   sudo systemctl restart apache2
   # o
   sudo systemctl restart php-fpm
   ```

---

### 3. ERROR: "Invalid get_string() identifier: 'nueva_caracterizacion'"

**Problema:** Faltan cadenas de idioma o no se cargan.

**Causa:** Caché de idiomas desactualizado.

**Solución (1 minuto):**

1. Ir a: **Administración del sitio > Idioma > Paquetes de idioma**
2. Click en **"Actualizar caché de todos los idiomas"**
3. Si aparece, click en **"Purgar caché de cadenas"**
4. Refrescar la página

---

### 4. FORMULARIO NO SE GUARDA O DA ERROR "sesskey"

**Problema:** Al intentar crear una caracterización, el formulario no se guarda o aparece error de sesskey.

**Causa:** Sesión expiró o problema con tokens CSRF.

**Solución (inmediata):**

1. Cerrar sesión del navegador
2. Borrar cookies de Moodle
3. Iniciar sesión nuevamente
4. Intentar crear la caracterización de nuevo

---

### 5. ERROR 500 o PÁGINA EN BLANCO

**Problema:** La página de la actividad no carga, error 500 o página completamente en blanco.

**Causa:** Error PHP que no se muestra.

**Solución (3 minutos):**

1. Activar modo de depuración:
   - Ir a: **Administración del sitio > Desarrollo > Modo de depuración**
   - Seleccionar: **DEVELOPER: mostrar mensajes de error, advertencias y mensajes de depuración**
   - Marcar: ☑️ **Mostrar origen de la depuración**
   - Guardar cambios

2. Recargar la página problemática

3. Ahora verás el error completo. Cópialo y:
   - Si dice "call to undefined function" → Falta una función o extensión PHP
   - Si dice "table not found" → Las tablas no se crearon correctamente (ver #6)
   - Si dice "syntax error" → Error en el código (reportar)

---

### 6. TABLAS DE BASE DE DATOS NO SE CREARON

**Problema:** La instalación parece exitosa pero las tablas no existen.

**Verificación:**

Ejecuta esta consulta SQL:
```sql
SHOW TABLES LIKE 'mdl_udes%';
```

Debes ver 7 tablas:
- mdl_udes
- mdl_udes_aprobaciones
- mdl_udes_caracterizacion ← **Crucial para v2.0**
- mdl_udes_comentarios
- mdl_udes_recursos
- mdl_udes_role_assignments
- mdl_udes_workflow

**Solución (10 minutos):**

1. Desinstalar el plugin:
   - Ir a: **Administración del sitio > Plugins > Plugins > Resumen de plugins**
   - Buscar "UDES"
   - Click en **"Desinstalar"**

2. Verificar que las tablas se eliminaron:
   ```sql
   SHOW TABLES LIKE 'mdl_udes%';
   ```
   (No debe mostrar nada)

3. Reinstalar el plugin:
   - Ir a: **Administración del sitio > Notificaciones**
   - Seguir el proceso de instalación
   - Verificar que muestre "Creando tabla udes_caracterizacion" etc.

---

### 7. NO SE PUEDEN AGREGAR RECURSOS (caracterizacionid requerido)

**Problema:** Al intentar agregar recursos aparece error sobre caracterizacionid.

**Causa:** La URL no incluye el parámetro característicoizacionid.

**Solución:**

NO acceder directamente a `recursos.php`. En su lugar:
1. Ir a la actividad UDES (view.php)
2. Click en "Ver" en una caracterización
3. Desde `caracterizacion_view.php`, click en "Agregar Recurso"

La URL correcta debe ser:
```
/mod/udes/recursos.php?id=XX&caracterizacionid=YY
```

---

### 8. BOTONES "VER", "EDITAR", "ELIMINAR" NO FUNCIONAN

**Problema:** Los botones aparecen pero al hacer click no pasa nada o dan error.

**Causa:** JavaScript deshabilitado o error en la URL.

**Solución:**

1. Verificar que JavaScript esté habilitado en el navegador

2. Abrir la consola del navegador (F12)

3. Ver si hay errores JavaScript

4. Si aparece "Failed to load resource" o error 404:
   - El archivo no existe donde debería
   - Verificar que estos archivos existan:
     - `mod/udes/caracterizacion_view.php`
     - `mod/udes/caracterizacion.php`
     - `mod/udes/approve.php`

---

### 9. RENDIMIENTO LENTO AL LISTAR CARACTERIZACIONES

**Problema:** La página `view.php` tarda mucho en cargar cuando hay muchas caracterizaciones.

**Solución:**

Ejecutar estas consultas SQL para crear índices:

```sql
CREATE INDEX idx_caract_udes ON mdl_udes_caracterizacion(udesid);
CREATE INDEX idx_caract_nombre ON mdl_udes_caracterizacion(nombre);
CREATE INDEX idx_recursos_caract ON mdl_udes_recursos(caracterizacionid);
```

---

### 10. ERROR AL APROBAR/RECHAZAR FASE

**Problema:** Al intentar aprobar o rechazar una fase, aparece error.

**Causa:** El usuario no tiene la capacidad `mod/udes:approve`.

**Solución:**

1. Ir a: **Administración del sitio > Usuarios > Permisos > Definir roles**
2. Editar el rol apropiado
3. Buscar: `mod/udes:approve`
4. Cambiar a **"Permitir"**
5. Guardar y purgar cachés

---

## Checklist Post-Instalación

Después de instalar el plugin, ejecutar en orden:

- [ ] 1. Verificar que las 7 tablas existan (ver #6)
- [ ] 2. Asignar capacidades al rol de profesor (ver #1)
- [ ] 3. Purgar TODAS las cachés
- [ ] 4. Actualizar caché de idiomas (ver #3)
- [ ] 5. Activar modo de depuración DEVELOPER (ver #5)
- [ ] 6. Crear una actividad UDES de prueba
- [ ] 7. Verificar que aparezca el botón "Nueva Caracterización"
- [ ] 8. Crear una caracterización de prueba
- [ ] 9. Verificar que se guarda correctamente
- [ ] 10. Desactivar modo de depuración (si todo funciona)

---

## Comando Mágico de Limpieza Completa

Si TODO falla, ejecuta estos pasos en orden:

```bash
# 1. En la interfaz de Moodle:
# Administración del sitio > Desarrollo > Purgar todas las cachés

# 2. En el servidor (terminal):
cd /ruta/a/moodle
php admin/cli/purge_caches.php

# 3. Reiniciar servicios:
sudo systemctl restart apache2  # o nginx
sudo systemctl restart php8.1-fpm  # ajusta la versión de PHP

# 4. En MySQL/MariaDB:
USE moodle;
DELETE FROM mdl_cache_flags;

# 5. De vuelta en Moodle:
# Administración del sitio > Idioma > Actualizar caché de idiomas
```

---

## Logs Útiles para Diagnóstico

Si el problema persiste, revisar estos logs:

### En Moodle:
1. **Administración del sitio > Informes > Registros**
   - Filtrar por: Módulo de actividad = "UDES"
   - Buscar líneas con "error" o "exception"

### En el servidor:

```bash
# PHP errors:
tail -f /var/log/php_errors.log

# Apache errors:
tail -f /var/log/apache2/error.log

# Moodle errors (si está configurado):
tail -f /path/to/moodle/moodledata/error.log
```

---

## Soporte Adicional

Si ninguna de estas soluciones funciona, proporciona:

1. **Versión exacta de Moodle** (ej: 4.1.5)
2. **Versión de PHP** (ejecutar `php -v`)
3. **Mensaje de error COMPLETO** (con modo depuración activado)
4. **Resultado de:** `SHOW TABLES LIKE 'mdl_udes%';`
5. **Captura de pantalla** del error

---

**Última actualización:** 2026-01-28
**Versión del plugin:** v2.0.0 ALPHA
