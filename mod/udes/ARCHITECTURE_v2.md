# Arquitectura v2.0 - Plugin UDES

## Resumen Ejecutivo

La versión 2.0 del plugin UDES introduce un **cambio arquitectónico mayor**: soporte para **múltiples matrices de caracterización** por actividad. Cada caracterización es una entidad independiente con su propio equipo, recursos, workflow de 6 fases, y trazabilidad completa.

## Cambios Arquitectónicos Principales

### v1.x → v2.0

| Aspecto | v1.x | v2.0 |
|---------|------|------|
| **Caracterizaciones por actividad** | 1 (única) | Múltiples (N) |
| **Tabla central** | `udes` | `udes_caracterizacion` |
| **Foreign Keys** | udesid | caracterizacionid |
| **Equipo de trabajo** | En tabla `udes` | En tabla `udes_caracterizacion` |
| **Workflow** | Global por actividad | Individual por caracterización |
| **Estado** | Global | Individual (borrador/en_proceso/aprobado/rechazado) |

## Estructura de Base de Datos v2.0

### Diagrama de Relaciones

```
┌──────────────┐
│     udes     │ (Actividad Moodle - Contenedor)
│  (simplificada) │
└──────┬───────┘
       │ 1:N
       ↓
┌──────────────────────┐
│ udes_caracterizacion │ (Matriz completa - Entidad central)
│                      │
│ • nombre             │ (Identificador único)
│ • programa_academico │
│ • nombre_curso       │
│ • Team (7 campos)    │ (asesor, experto, par, corrector, etc.)
│ • Resources (5)      │ (cvp, sala_clases, video, foro, mapa)
│ • currentphase (1-6) │
│ • estado             │ (borrador/en_proceso/aprobado/rechazado)
└──────┬───────────────┘
       │ 1:N
       ├──────────────────────┐
       │                      │
       ↓                      ↓
┌──────────────┐      ┌──────────────┐
│udes_recursos│      │udes_workflow │
│              │      │              │
│caracterizacionid   │caracterizacionid
└──────────────┘      └──────────────┘
       │                      │
       ↓                      ↓
┌──────────────────┐  ┌──────────────────┐
│udes_aprobaciones │  │udes_comentarios  │
│                  │  │                  │
│caracterizacionid │  │caracterizacionid │
└──────────────────┘  └──────────────────┘
```

### Tablas y Campos Clave

#### 1. `udes` (Contenedor simplificado)
```sql
- id
- course (FK a course)
- name
- intro
- introformat
- timecreated
- timemodified
```

#### 2. `udes_caracterizacion` (Entidad central v2.0)
```sql
-- Identificación
- id
- udesid (FK a udes)
- nombre (VARCHAR 255, UNIQUE per udesid)

-- Información del curso (Excel H1-I2)
- programa_academico (VARCHAR 255)
- nombre_curso (VARCHAR 255)

-- Equipo de trabajo (Excel H3-I9)
- asesor_metodologico (VARCHAR 255)
- experto_disciplinar (VARCHAR 255)
- par_academico (VARCHAR 255)
- corrector_estilo (VARCHAR 255)
- coordinacion_produccion (VARCHAR 255)
- produccion (VARCHAR 255)
- alistamiento (VARCHAR 255)

-- Recursos generales (Excel J11-J15)
- cvp (TINYINT)
- sala_clases (TINYINT)
- video_bienvenida (TINYINT)
- foro_curso (TINYINT)
- mapa_curso (TINYINT)

-- Workflow
- currentphase (INT 1-6, DEFAULT 1)
- estado (VARCHAR 20: borrador/en_proceso/aprobado/rechazado)

-- Auditoría
- timecreated
- timemodified
```

#### 3. Tablas Relacionadas
Todas las siguientes tablas tienen **caracterizacionid** como FK:

- `udes_recursos` - Recursos educativos (dual columns G-H / L-M)
- `udes_workflow` - Seguimiento de fases (1-6)
- `udes_aprobaciones` - Aprobaciones/rechazos por fase
- `udes_comentarios` - Comentarios por fase

**Nota:** `udes_role_assignments` mantiene `udesid` (nivel actividad, no caracterización).

## Componentes del Sistema v2.0

### 1. Archivos PHP Principales

#### view.php (Lista de Caracterizaciones)
- **Propósito**: Vista principal que muestra todas las caracterizaciones de una actividad
- **Características v2.0**:
  - Cards responsive con información de cada caracterización
  - Badges de fase actual y estado
  - Estadísticas: recursos_count, comentarios_count
  - Botones: Ver, Editar, Eliminar (según permisos)
  - Botón "Nueva Caracterización"
- **Permisos**: expertodisciplinar, asesormetodologico, manageall para crear

#### caracterizacion.php (CRUD Controller)
- **Propósito**: Controlador para crear, editar y eliminar caracterizaciones
- **Acciones**:
  - `edit` (default): Muestra formulario de edición/creación
  - `delete`: Elimina caracterización con cascade
- **Permisos**: expertodisciplinar, asesormetodologico, manageall
- **Validaciones**: Verifica que caracterización pertenece al módulo

#### caracterizacion_form.php (Moodle Form)
- **Propósito**: Formulario Moodle para CRUD de caracterizaciones
- **Secciones**:
  1. **General**: nombre (required, max 255)
  2. **Información del Curso**: programa_academico, nombre_curso
  3. **Equipo de Trabajo**: 7 campos (text, max 255 cada uno)
  4. **Recursos Generales**: 5 checkboxes (cvp, sala_clases, video_bienvenida, foro_curso, mapa_curso)
- **Validación**: nombre no puede estar vacío

#### caracterizacion_view.php (Vista Individual)
- **Propósito**: Muestra detalles completos de una caracterización
- **Secciones**:
  1. Información del curso
  2. Equipo de trabajo (lista con iconos)
  3. Recursos generales (checkmarks)
  4. Workflow actual (badges de fase y estado)
  5. Lista de recursos educativos (tabla)
  6. Comentarios por fase (cards)
  7. Formulario para agregar comentarios
  8. Botones de aprobación/rechazo (según permisos)
- **Permisos dinámicos**: Según fase actual y rol del usuario

#### approve.php (Aprobaciones/Rechazos)
- **Propósito**: Aprobar o rechazar fase actual de una caracterización
- **Acciones**:
  - `approve`: Avanza a siguiente fase, actualiza estado
  - `reject`: Marca caracterización como rechazada
- **Funcionalidad**:
  - Formulario de confirmación con comentario opcional
  - Crea registro en `udes_aprobaciones`
  - Llama a `caracterizacion_manager::advance_to_next_phase()`
  - Actualiza estado de caracterización
  - Notificaciones de éxito/warning
- **Permisos**: mod/udes:approve, mod/udes:manageall

#### save_comentario.php (Guardar Comentarios)
- **Propósito**: Guardar comentarios en la fase actual de una caracterización
- **Funcionalidad**:
  - Valida que comentario no esté vacío
  - Asocia comentario a fase actual
  - Crea registro en `udes_comentarios`
  - Redirección con notificación
- **Permisos**: Cualquier rol UDES puede comentar

#### recursos.php (Gestión de Recursos)
- **Propósito**: CRUD de recursos educativos por caracterización
- **Cambios v2.0**:
  - Requiere `caracterizacionid` como parámetro obligatorio
  - Valida que caracterización pertenece al módulo
  - Todos los enlaces incluyen `caracterizacionid`
  - Breadcrumb apunta a `caracterizacion_view.php`
  - Llama a `recurso_manager` con `caracterizacionid`
- **Acciones**: list, add, edit, save, delete
- **Permisos**: expertodisciplinar, asesormetodologico, manageall

### 2. Clases Manager

#### caracterizacion_manager.php (335 líneas)
**Namespace:** `mod_udes`

**Métodos públicos:**

```php
// CRUD básico
public static function create_caracterizacion($udesid, $data): int
public static function update_caracterizacion($caracterizacionid, $data): bool
public static function delete_caracterizacion($caracterizacionid): bool
public static function get_caracterizacion($caracterizacionid): stdClass|false

// Consultas
public static function get_caracterizaciones_by_udes($udesid, $sort = 'nombre ASC'): array
public static function get_caracterizacion_with_progress($caracterizacionid): stdClass

// Workflow
public static function advance_to_next_phase($caracterizacionid, $userid): bool

// Estadísticas
public static function get_stats($udesid): stdClass
```

**Funcionalidad clave:**
- `create_caracterizacion()`: Inicializa caracterización con fase 1, estado borrador, crea registro workflow inicial
- `delete_caracterizacion()`: Cascade delete de todos los registros relacionados
- `get_caracterizacion_with_progress()`: Agrega contadores (recursos, comentarios, aprobaciones)
- `advance_to_next_phase()`: Avanza de fase 1→2→3→4→5→6, actualiza workflow
- `get_stats()`: Retorna estadísticas por fase y por estado

#### recurso_manager.php (358 líneas)
**Namespace:** `mod_udes`

**Métodos públicos (v2.0 - actualizados):**

```php
// CRUD
public static function create_recurso($caracterizacionid, $data, $userid): int
public static function update_recurso($recursoid, $data): bool
public static function delete_recurso($recursoid): bool
public static function get_recurso($recursoid): stdClass|false

// Consultas
public static function get_recursos($caracterizacionid, $filters = []): array
public static function get_recursos_by_unidad($caracterizacionid): array

// Utilidades
public static function get_resource_types(): array
public static function get_resource_count_by_category($caracterizacionid): array
public static function get_total_resource_count($caracterizacionid): int
public static function get_resource_form_fields($tipo_recurso, $recurso): array
```

**Cambios v2.0:**
- TODOS los métodos ahora usan `caracterizacionid` en lugar de `udesid`
- `create_recurso()`: Guarda `caracterizacionid` en tabla
- Consultas filtran por `caracterizacionid`
- Contadores y estadísticas por caracterización

### 3. Archivos de Idioma

#### lang/es/udes.php y lang/en/udes.php

**Cadenas nuevas v2.0:**
```php
// Caracterizaciones
'caracterizaciones' => 'Caracterizaciones'
'caracterizaciones_info' => 'Esta actividad permite crear múltiples...'
'caracterizacion_nombre' => 'Nombre de la Caracterización'
'nueva_caracterizacion' => 'Nueva Caracterización'
'editar_caracterizacion' => 'Editar Caracterización'
'eliminar_caracterizacion' => 'Eliminar Caracterización'
'ver_caracterizacion' => 'Ver Caracterización'
'lista_caracterizaciones' => 'Lista de Caracterizaciones'
'sin_caracterizaciones' => 'No hay caracterizaciones creadas...'
'confirm_delete_caracterizacion' => '¿Está seguro que desea eliminar...?'
'error_caracterizacion_not_found' => 'Caracterización no encontrada'

// UI
'ver' => 'Ver'
'editar' => 'Editar'
'eliminar' => 'Eliminar'
'fase' => 'Fase'

// Estados
'estado_borrador' => 'Borrador'
'estado_en_proceso' => 'En Proceso'
'estado_aprobado' => 'Aprobado'
'estado_rechazado' => 'Rechazado'

// Comentarios
'comentario_opcional' => 'Comentario opcional sobre la decisión'
'error_comentario_empty' => 'El comentario no puede estar vacío'
'error_invalid_action' => 'Acción inválida'
```

## Flujo de Usuario Completo v2.0

### Fase 0: Crear Actividad UDES

1. Profesor crea actividad UDES en curso Moodle
2. Completa formulario básico (nombre, descripción)
3. Sistema crea registro en tabla `udes`

### Fase 1: Crear Caracterización

1. Usuario con permiso (experto/asesor) accede a actividad
2. Ve mensaje "No hay caracterizaciones" + botón "Nueva Caracterización"
3. Click en "Nueva Caracterización" → `caracterizacion.php`
4. Completa formulario `caracterizacion_form.php`:
   - Nombre: "Matemáticas I - 2026-1"
   - Programa: "Ingeniería de Sistemas"
   - Curso: "Matemáticas I"
   - Equipo: Asigna nombres de cada rol
   - Recursos: Marca CVP, video bienvenida, foro
5. Submit → `caracterizacion_manager::create_caracterizacion()`
   - Crea registro en `udes_caracterizacion`
   - Inicializa `currentphase = 1`, `estado = 'borrador'`
   - Crea registro inicial en `udes_workflow`
6. Redirección a `view.php` → muestra card de caracterización

### Fase 2: Agregar Recursos (Fase 1 - Diligenciamiento)

1. Click en "Ver" en card → `caracterizacion_view.php`
2. Ve información completa de caracterización
3. Click en "Agregar Recurso" → `recursos.php?caracterizacionid=X`
4. Completa formulario de recurso:
   - Unidad: 1, Nombre: "Introducción"
   - Tema: 1.1, Nombre: "Conjuntos"
   - Tipo: RECURSOS_EDUCATIVOS_DIGITALES
   - Recurso: "Video Clase"
   - Campos dinámicos: título, duración, guion
5. Submit → `recurso_manager::create_recurso(caracterizacionid, ...)`
6. Recurso guardado con `caracterizacionid` como FK

### Fase 3: Agregar Comentarios

1. En `caracterizacion_view.php`, sección comentarios
2. Escribe comentario: "Recursos de la unidad 1 completados"
3. Submit → `save_comentario.php`
4. Comentario guardado en `udes_comentarios`:
   - `caracterizacionid`
   - `phase = 1` (fase actual)
   - `userid`, `comentario`, `timecreated`

### Fase 4: Aprobar Fase y Avanzar

1. Revisor con permiso `mod/udes:approve` accede
2. En `caracterizacion_view.php`, click "Aprobar"
3. Redirección a `approve.php?action=approve`
4. Formulario de confirmación con comentario opcional
5. Submit:
   - `caracterizacion_manager::advance_to_next_phase(caracterizacionid, userid)`
   - Actualiza `currentphase = 2` en `udes_caracterizacion`
   - Crea registro en `udes_aprobaciones`:
     - `caracterizacionid`, `phase = 1`, `userid`
     - `aprobado = 1`, `comentario`, `timecreated`
   - Actualiza `estado = 'en_proceso'` (si < fase 6)
6. Notificación: "Aprobado correctamente"
7. Caracterización avanza a Fase 2: Revisión Curricular

### Fase 5: Continuar Workflow (Fases 2-6)

Se repite el ciclo para cada fase:
- **Fase 2**: Revisor Curricular revisa/comenta/aprueba
- **Fase 3**: Par Disciplinar y Corrector de Estilo revisan
- **Fase 4**: Coordinación de Producción y Producción desarrollan
- **Fase 5**: Alistamiento monta en plataforma
- **Fase 6**: Aprobación Final → `estado = 'aprobado'`

### Fase 6: Gestión de Múltiples Caracterizaciones

1. Usuario regresa a `view.php`
2. Ve grid con múltiples cards de caracterizaciones
3. Cada card muestra:
   - Nombre único
   - Fase actual (badge con color)
   - Estado (badge)
   - Programa académico, nombre curso
   - Estadísticas: X recursos, Y comentarios
   - Botones: Ver, Editar, Eliminar
4. Puede crear nuevas caracterizaciones simultáneamente
5. Cada una progresa de forma independiente

## Patrones de Diseño y Mejores Prácticas

### 1. Manager Pattern
- Clases `*_manager.php` encapsulan lógica de negocio
- Métodos estáticos para operaciones CRUD
- Validaciones centralizadas
- Cascade delete automático

### 2. MVC (Model-View-Controller)
- **Model**: Clases manager + tablas DB
- **View**: Archivos PHP que generan HTML (view.php, caracterizacion_view.php)
- **Controller**: Archivos de acción (caracterizacion.php, approve.php, save_comentario.php)

### 3. Moodle Forms
- Uso de `moodleform` para formularios complejos
- Validación lado servidor
- CSRF protection con sesskey
- Localización automática

### 4. Permisos Granulares
- Capabilities por rol UDES
- Verificación en cada punto de entrada
- Permisos dinámicos según fase actual
- Separación entre view/edit/manage/approve

### 5. Internacionalización (i18n)
- Todas las cadenas en archivos lang/
- Soporte completo ES + EN
- Strings parametrizados con placeholders

### 6. Seguridad
- `require_login()` en todos los archivos
- `require_sesskey()` en operaciones POST
- Validación de FK (caracterización pertenece a módulo)
- SQL preparado (Moodle DML API)
- XSS prevention (`format_string()`, `format_text()`)

## Migración v1.x → v2.0

### Breaking Changes

⚠️ **La v2.0 NO es compatible con v1.x**. Requiere reinstalación limpia.

**Razón:** La estructura de tablas cambió radicalmente:
- Foreign keys cambiaron de `udesid` a `caracterizacionid`
- Campos movidos de `udes` a `udes_caracterizacion`
- `currentphase` y `estado` ahora por caracterización

### Script de Migración (Conceptual)

Si se requiere migrar datos de v1.x a v2.0:

```sql
-- 1. Para cada actividad UDES v1.x
FOR EACH udes_instance IN v1.x:
  -- 2. Crear una caracterización "default"
  INSERT INTO udes_caracterizacion (
    udesid, nombre,
    programa_academico, nombre_curso,
    asesor_metodologico, experto_disciplinar, ...
    cvp, sala_clases, video_bienvenida, foro_curso, mapa_curso,
    currentphase, estado
  )
  VALUES (
    udes_instance.id, 'Caracterización Principal',
    udes_instance.programa_academico, udes_instance.nombre_curso,
    udes_instance.asesor_metodologico, ... (copiar campos de equipo)
    udes_instance.cvp, ... (copiar recursos generales)
    udes_instance.currentphase, udes_instance.estado
  )

  -- 3. Actualizar FKs en tablas relacionadas
  UPDATE udes_recursos
    SET caracterizacionid = new_caracterizacion_id
    WHERE udesid = udes_instance.id

  UPDATE udes_workflow
    SET caracterizacionid = new_caracterizacion_id
    WHERE udesid = udes_instance.id

  -- ... (repetir para aprobaciones, comentarios)
```

**Nota:** Este script es conceptual. La reinstalación limpia es el método recomendado.

## Testing y Validación

### Casos de Prueba Críticos

#### 1. CRUD de Caracterizaciones
- [ ] Crear caracterización con todos los campos
- [ ] Crear caracterización solo con nombre (campos opcionales vacíos)
- [ ] Editar caracterización existente
- [ ] Eliminar caracterización → verificar cascade delete
- [ ] Validar que nombre no esté vacío
- [ ] Validar unicidad de nombre por actividad

#### 2. Workflow de 6 Fases
- [ ] Crear caracterización → verificar fase inicial = 1
- [ ] Aprobar fase 1 → avanza a fase 2
- [ ] Aprobar fases 2-5 → avanzan correctamente
- [ ] Aprobar fase 6 → estado cambia a 'aprobado'
- [ ] Rechazar en cualquier fase → estado = 'rechazado'

#### 3. Recursos por Caracterización
- [ ] Agregar recurso a caracterización A
- [ ] Agregar recurso a caracterización B
- [ ] Verificar recursos no se mezclan
- [ ] Eliminar caracterización → recursos se eliminan (cascade)
- [ ] Contadores por caracterización correctos

#### 4. Comentarios y Aprobaciones
- [ ] Agregar comentario a fase actual
- [ ] Comentario se guarda con fase correcta
- [ ] Aprobar con comentario opcional
- [ ] Rechazar con comentario opcional
- [ ] Verificar registros en `udes_aprobaciones`

#### 5. Permisos
- [ ] Experto disciplinar puede crear caracterización
- [ ] Asesor metodológico puede crear caracterización
- [ ] Usuario sin permisos no puede crear
- [ ] Solo usuarios con `approve` pueden aprobar
- [ ] Permisos dinámicos por fase funcionan

#### 6. Múltiples Caracterizaciones
- [ ] Crear 3 caracterizaciones en misma actividad
- [ ] Cada una con diferente equipo
- [ ] Cada una en diferente fase
- [ ] Verificar independencia total
- [ ] Estadísticas por caracterización correctas

### Verificación de Integridad

```sql
-- Verificar que todas las caracterizaciones tienen udesid válido
SELECT * FROM udes_caracterizacion
WHERE udesid NOT IN (SELECT id FROM udes);

-- Verificar que todos los recursos tienen caracterizacionid válido
SELECT * FROM udes_recursos
WHERE caracterizacionid NOT IN (SELECT id FROM udes_caracterizacion);

-- Verificar que currentphase está en rango 1-6
SELECT * FROM udes_caracterizacion
WHERE currentphase < 1 OR currentphase > 6;

-- Verificar que estado es válido
SELECT * FROM udes_caracterizacion
WHERE estado NOT IN ('borrador', 'en_proceso', 'aprobado', 'rechazado');
```

## Rendimiento y Optimización

### Índices Recomendados

```sql
-- Índice compuesto para búsqueda por actividad
CREATE INDEX idx_caract_udes_nombre
ON udes_caracterizacion(udesid, nombre);

-- Índice para ordenamiento por fecha
CREATE INDEX idx_caract_timecreated
ON udes_caracterizacion(timecreated DESC);

-- Índice para filtrado por estado
CREATE INDEX idx_caract_estado
ON udes_caracterizacion(estado);

-- Índice para recursos por caracterización
CREATE INDEX idx_recursos_caract
ON udes_recursos(caracterizacionid);

-- Índice para workflow por caracterización
CREATE INDEX idx_workflow_caract
ON udes_workflow(caracterizacionid, phase);
```

### Consultas Optimizadas

**Evitar:**
```php
// MAL: N+1 queries
foreach ($caracterizaciones as $caract) {
    $recursos = $DB->get_records('udes_recursos', ['caracterizacionid' => $caract->id]);
}
```

**Usar:**
```php
// BIEN: 1 query con JOIN o subquery
$sql = "SELECT c.*, COUNT(r.id) as recursos_count
        FROM {udes_caracterizacion} c
        LEFT JOIN {udes_recursos} r ON r.caracterizacionid = c.id
        WHERE c.udesid = :udesid
        GROUP BY c.id
        ORDER BY c.nombre";
```

## Extensibilidad y Futuras Mejoras

### 1. Notificaciones por Email
- Implementar envío de emails al aprobar/rechazar fases
- Usar Moodle messaging API
- Notificar a responsables de siguiente fase

### 2. Plantillas de Caracterización
- Permitir guardar caracterización como plantilla
- Duplicar caracterización existente
- Biblioteca de plantillas predefinidas

### 3. Reportes Avanzados
- Dashboard con métricas por caracterización
- Comparativa entre caracterizaciones
- Tiempo promedio por fase
- Export a PDF/Excel

### 4. Versionado de Caracterizaciones
- Guardar versiones al aprobar cada fase
- Historial de cambios (audit trail)
- Rollback a versiones anteriores

### 5. Integración con Otros Plugins
- Export/import de caracterizaciones (JSON/XML)
- Integración con mod_folder para recursos
- Integración con mod_forum para discusiones

### 6. API REST
- Endpoints RESTful para integración externa
- Autenticación con tokens
- Webhooks para eventos (aprobación, rechazo)

## Conclusión

La arquitectura v2.0 del plugin UDES representa un rediseño completo centrado en la flexibilidad y escalabilidad. El soporte para múltiples caracterizaciones por actividad permite:

✅ Gestionar varios cursos desde una sola actividad
✅ Workflow independiente por caracterización
✅ Trazabilidad completa y separada
✅ Equipos de trabajo diferentes por proyecto
✅ Estados independientes (borrador/proceso/aprobado/rechazado)

La arquitectura sigue las mejores prácticas de Moodle, con código limpio, bien documentado, y totalmente localizado en español e inglés.

---

**Documento generado:** 2026-01-21
**Versión del plugin:** v2.0.0 ALPHA
**Autor:** Alonso Arias <soporte@orioncloud.com.co>
**Cliente:** Universidad de Santander - UDES (udes.edu.co)
