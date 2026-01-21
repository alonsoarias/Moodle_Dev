# Sistema de Producción de Recursos Educativos UDES

Plugin de Moodle para gestionar el proceso de producción de recursos educativos digitales de la Universidad de Santander (UDES).

## Descripción

**v2.0**: Este plugin implementa un sistema completo de workflow para la producción de recursos educativos digitales, con soporte para **múltiples matrices de caracterización** por actividad. Cada caracterización tiene su propio equipo de trabajo, recursos y flujo independiente de 6 fases del proceso de virtualización de cursos de UDES:

1. **Fase 1: Caracterización** - Diligenciamiento de la caracterización y selección de recursos educativos
2. **Fase 2: Revisión Curricular** - Revisión y validación curricular
3. **Fase 3: Par / Corrector de Estilo** - Revisión por pares y corrección de estilo
4. **Fase 4: Producción** - Desarrollo de recursos educativos
5. **Fase 5: Alistamiento** - Alistamiento en plataforma Moodle
6. **Fase 6: Aprobación Final** - Aprobación final del curso

## Características

### v2.0: Múltiples Caracterizaciones

**Nuevo en v2.0**: Cada actividad UDES puede contener múltiples matrices de caracterización, permitiendo gestionar varios cursos o versiones desde una sola actividad. Cada caracterización es independiente y cuenta con:

- **Identificador único**: Nombre personalizado para distinguir cada matriz
- **Equipo de trabajo propio**: Asignación independiente de roles por caracterización
- **Recursos generales**: Selección independiente de CVP, sala de clases, video bienvenida, etc.
- **Workflow independiente**: Cada caracterización progresa por las 6 fases de forma autónoma
- **Trazabilidad individual**: Recursos, comentarios y aprobaciones separados por caracterización

### Gestión de Roles
El sistema maneja 8 roles específicos del proceso UDES:
- Experto Disciplinar
- Asesor Metodológico
- Revisor Curricular
- Par Disciplinar
- Corrector de Estilo
- Coordinación de Producción
- Producción
- Alistamiento

### Tipos de Recursos Educativos

El sistema soporta 5 categorías de recursos educativos:

#### 1. Recursos Educativos Digitales
- E-book
- Video Clase
- Podcast
- Comic Virtual
- Paso a Paso
- Línea de Tiempo
- Infografía
- Mapa Conceptual
- Mapa Mental
- Video Interactivo
- Video con diapositivas
- Video explicativo

#### 2. Recursos Interactivos Digitales
- Opción única
- Opción múltiple
- Verdadero o falso
- Marca las palabras
- Espacios en blanco
- Dictado
- Tarjeta didáctica
- Tarjetas de diálogo
- Hotspots
- Emparejamiento
- Arrastra las palabras
- Crucigrama
- Ordena los párrafos
- Sopa de letras
- Glosario interactivo

#### 3. Recursos Evaluativos
- Tarea
- Lección

#### 4. Recursos Colaborativos
- Wiki
- Foro temático
- Foro social

#### 5. Recursos Externos
- Paquetes
- Plataformas externas
- Video conferencias

### Sistema de Workflow

- **Flujo secuencial**: Las fases deben completarse en orden
- **Validaciones**: Cada fase debe ser aprobada antes de avanzar
- **Comentarios**: Sistema de retroalimentación en cada fase
- **Notificaciones automáticas**: Alertas cuando se completan fases
- **Trazabilidad**: Registro completo de aprobaciones y cambios

### Seguridad

- **Restricción de descarga**: Los archivos solo pueden visualizarse en el navegador
- **Control de acceso por fase**: Cada rol tiene acceso limitado según la fase
- **Validaciones de permisos**: Verificación estricta de capacidades

## Requisitos

- Moodle 4.0 o superior
- PHP 7.4 o superior
- MySQL 5.7 / MariaDB 10.2 o superior

## Instalación

1. Descargue o clone el plugin en el directorio `mod/udes` de su instalación Moodle:
   ```bash
   cd /path/to/moodle/mod
   git clone [repository-url] udes
   ```

2. Visite la página de notificaciones de administración de Moodle (Site administration > Notifications) para completar la instalación.

3. El plugin creará automáticamente las tablas necesarias en la base de datos.

## Configuración

### Asignación de Roles

Los roles UDES se implementan como capacidades de Moodle. Puede asignarlos a través de:

1. Navegue a Site administration > Users > Permissions > Define roles
2. Edite los roles existentes o cree roles personalizados
3. Asigne las capacidades correspondientes:
   - `mod/udes:expertodisciplinar`
   - `mod/udes:asesormetodologico`
   - `mod/udes:revisorcurricular`
   - `mod/udes:pardisciplinar`
   - `mod/udes:correctorestilo`
   - `mod/udes:coordinacionproduccion`
   - `mod/udes:produccion`
   - `mod/udes:alistamiento`

## Uso

### Creando una Actividad de Producción UDES

1. En un curso, active la edición (Turn editing on)
2. Haga clic en "Add an activity or resource"
3. Seleccione "Producción de Recursos UDES"
4. Complete el formulario:
   - Nombre de la actividad
   - Descripción
5. Guarde los cambios

### v2.0: Creando Caracterizaciones

Una vez creada la actividad, puede crear múltiples caracterizaciones:

1. Acceda a la actividad UDES
2. Haga clic en "Nueva Caracterización"
3. Complete el formulario de caracterización:
   - **Nombre**: Identificador único (ej: "Matemáticas I - 2026-1")
   - **Información del Curso**:
     - Programa Académico (Excel H1-I1)
     - Nombre del Curso (Excel H2-I2)
   - **Equipo de Trabajo** (Excel H3-I9):
     - Asesor Metodológico
     - Experto Disciplinar
     - Par Académico
     - Corrector de Estilo
     - Coordinación de Producción
     - Producción
     - Alistamiento
   - **Recursos Generales** (Excel J11-J15):
     - CVP (Curso Virtual Portable)
     - Sala para Clases Virtuales
     - Video de Bienvenida
     - Foro del Curso
     - Mapa del Curso
4. Guarde la caracterización

### Fase 1: Diligenciando la Caracterización

1. Los usuarios con rol de Experto Disciplinar o Asesor Metodológico acceden a la caracterización
2. Agregan recursos educativos por unidad y tema (Excel estructura dual G-H / L-M):
   - Seleccionan el tipo de recurso (categoría)
   - Eligen el recurso específico
   - Completan los campos requeridos según el tipo de recurso
3. Una vez completa la caracterización, avanzan a la siguiente fase

### Fases 2-6: Revisión y Producción

Cada fase subsecuente:
1. Se desbloquea automáticamente cuando la fase anterior es aprobada
2. Los usuarios con el rol correspondiente reciben notificaciones
3. Pueden revisar, comentar, aprobar o rechazar
4. Al aprobar, se notifica automáticamente a los responsables de la siguiente fase

## Estructura del Plugin

```
mod/udes/
├── classes/
│   ├── event/                    # Eventos del sistema
│   ├── caracterizacion_manager.php  # v2.0: Gestión de caracterizaciones
│   ├── workflow/                 # Gestión de workflow
│   │   └── workflow_manager.php
│   ├── form/                     # Formularios personalizados
│   └── recurso_manager.php       # Gestión de recursos
├── db/
│   ├── access.php                # Definición de capacidades
│   └── install.xml               # Esquema de base de datos
├── lang/
│   ├── es/
│   │   └── udes.php              # Cadenas en español
│   └── en/
│       └── udes.php              # Cadenas en inglés
├── backup/                       # Soporte para backup/restore
├── tests/                        # Tests unitarios
├── lib.php                       # Funciones principales
├── mod_form.php                  # Formulario de configuración
├── version.php                   # Información de versión
├── view.php                      # Vista principal (lista de caracterizaciones)
├── caracterizacion.php           # v2.0: CRUD de caracterizaciones
├── caracterizacion_form.php      # v2.0: Formulario de caracterización
├── caracterizacion_view.php      # v2.0: Vista de caracterización individual
├── recursos.php                  # Gestión de recursos
└── README.md                     # Este archivo
```

## Base de Datos

### v2.0: Arquitectura Centrada en Caracterizaciones

**Cambio arquitectónico importante**: La estructura de datos se reorganizó para soportar múltiples caracterizaciones por actividad.

### Tablas Principales

- **udes**: Instancias de actividades (contenedor simplificado)
  - id, course, name, intro, timecreated, timemodified

- **udes_caracterizacion**: Matrices de caracterización (tabla central v2.0)
  - id, udesid, nombre
  - Información del curso: programa_academico, nombre_curso
  - Equipo de trabajo: asesor_metodologico, experto_disciplinar, par_academico, corrector_estilo, coordinacion_produccion, produccion, alistamiento
  - Recursos generales: cvp, sala_clases, video_bienvenida, foro_curso, mapa_curso
  - Workflow: currentphase (1-6), estado (borrador/en_proceso/aprobado/rechazado)

- **udes_recursos**: Recursos educativos por caracterización
  - FK: caracterizacionid (antes: udesid)

- **udes_workflow**: Seguimiento de fases por caracterización
  - FK: caracterizacionid (antes: udesid)

- **udes_aprobaciones**: Aprobaciones y rechazos por caracterización
  - FK: caracterizacionid (antes: udesid)

- **udes_comentarios**: Comentarios por caracterización
  - FK: caracterizacionid (antes: udesid)

- **udes_role_assignments**: Asignaciones de roles (mantiene udesid)

## API del Plugin

### v2.0: Caracterizacion Manager (Nuevo)

```php
use mod_udes\caracterizacion_manager;

// Crear nueva caracterización
$caracterizacionid = caracterizacion_manager::create_caracterizacion($udesid, $data);

// Actualizar caracterización
caracterizacion_manager::update_caracterizacion($caracterizacionid, $data);

// Eliminar caracterización (cascade delete)
caracterizacion_manager::delete_caracterizacion($caracterizacionid);

// Obtener caracterización
$caract = caracterizacion_manager::get_caracterizacion($caracterizacionid);

// Obtener todas las caracterizaciones de una actividad
$caracterizaciones = caracterizacion_manager::get_caracterizaciones_by_udes($udesid, 'nombre ASC');

// Obtener caracterización con estadísticas de progreso
$progress = caracterizacion_manager::get_caracterizacion_with_progress($caracterizacionid);
// Retorna: recursos_count, workflow_phases, aprobaciones_count, comentarios_count

// Avanzar a la siguiente fase
caracterizacion_manager::advance_to_next_phase($caracterizacionid, $userid);

// Obtener estadísticas generales
$stats = caracterizacion_manager::get_stats($udesid);
```

### Workflow Manager

```php
use mod_udes\workflow\workflow_manager;

$workflow = new workflow_manager($caracterizacionid); // v2.0: usa caracterizacionid

// Obtener fase actual
$phase = $workflow->get_current_phase();

// Aprobar fase
$workflow->approve_phase($userid, $rol, $comentario);

// Rechazar fase
$workflow->reject_phase($userid, $rol, $comentario);

// Agregar comentario
$workflow->add_comment($userid, $comentario);
```

### Resource Manager

```php
use mod_udes\recurso_manager;

// Crear recurso
$recursoid = recurso_manager::create_recurso($caracterizacionid, $data, $userid); // v2.0: caracterizacionid

// Obtener recursos
$recursos = recurso_manager::get_recursos($caracterizacionid); // v2.0: caracterizacionid

// Obtener conteo por categoría
$counts = recurso_manager::get_resource_count_by_category($caracterizacionid); // v2.0: caracterizacionid
```

## Desarrollo

### Compilación de Módulos AMD (JavaScript)

Este plugin utiliza módulos AMD (Asynchronous Module Definition) para el manejo de JavaScript, siguiendo las mejores prácticas de Moodle.

#### Requisitos para Desarrollo

```bash
# Instalar Node.js y npm
# Verificar instalación:
node --version
npm --version
```

#### Compilar Módulos AMD

1. **Instalar dependencias:**

```bash
cd mod/udes
npm install
```

2. **Compilar módulos:**

```bash
# Opción 1: Usar Grunt directamente
npx grunt amd

# Opción 2: Usar script npm
npm run build
```

3. **Desarrollo continuo:**

```bash
# Para recompilar automáticamente al detectar cambios:
npx grunt watch
```

#### Estructura de Módulos AMD

Los módulos JavaScript están organizados en:

- **Código fuente:** `amd/src/*.js` - Archivos JavaScript editables
- **Código compilado:** `amd/build/*.min.js` - Archivos minificados para producción

**Ejemplo de módulo AMD (amd/src/recursos.js):**

```javascript
define(['jquery', 'core/ajax'], function($, Ajax) {
    return {
        init: function() {
            // Código de inicialización
        }
    };
});
```

**Uso en PHP:**

```php
// Cargar módulo AMD
$PAGE->requires->js_call_amd('mod_udes/recursos', 'init');
```

#### Importante

- **SIEMPRE** compile los módulos AMD antes de commit
- Los archivos en `amd/build/` deben estar versionados
- No modifique archivos `.min.js` directamente
- Use `grunt` para minificar y optimizar el código

### Contribuir

1. Fork el repositorio
2. Cree una rama para su feature (`git checkout -b feature/AmazingFeature`)
3. Commit sus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abra un Pull Request

### Estándares de Código

El plugin sigue los estándares de codificación de Moodle:
- [Moodle Coding Style](https://docs.moodle.org/dev/Coding_style)
- [Moodle Plugin Development](https://docs.moodle.org/dev/Plugin_files)

## Licencia

Este plugin está licenciado bajo GNU GPL v3 o posterior.

Copyright © 2026 Universidad de Santander - UDES

## Soporte

Para reportar bugs o solicitar nuevas características, por favor abra un issue en el repositorio del proyecto.

## Autores

- **Alonso Arias** <soporte@orioncloud.com.co> - Desarrollador principal
- **Universidad de Santander - UDES** (udes.edu.co) - Cliente
- Desarrollo basado en análisis exhaustivo de documentación UDES:
  - Actividad.txt
  - 00_Caracterizacion_RED (1) (3).xlsx
  - DIAGRAMA DE FLUJO-ACT1-UDES (1).docx

## Changelog

### Versión 2.0.0 ALPHA (2026-01-21)
- 🆕 **CAMBIO ARQUITECTÓNICO MAYOR**: Soporte para múltiples caracterizaciones por actividad
- 🆕 Tabla `udes_caracterizacion` como entidad central independiente
- 🆕 Cada caracterización con su propio:
  - Nombre identificador único
  - Equipo de trabajo (7 roles)
  - Recursos generales (CVP, sala clases, video bienvenida, foro, mapa)
  - Workflow de 6 fases independiente
  - Estado (borrador/en_proceso/aprobado/rechazado)
- 🆕 Nueva clase `caracterizacion_manager` para gestión CRUD
- 🆕 Archivos nuevos:
  - `caracterizacion.php` - Controlador CRUD
  - `caracterizacion_form.php` - Formulario Moodle
  - `caracterizacion_view.php` - Vista de caracterización individual
  - `classes/caracterizacion_manager.php` - Manager con 9 métodos
- 🔄 `view.php` actualizado: lista de caracterizaciones en cards
- 🔄 `lib.php` actualizado: delete_instance con cascade por caracterización
- 🔄 `db/install.xml` restructurado: FKs cambiados de udesid a caracterizacionid
- 🔄 Cadenas de idioma actualizadas (ES + EN)
- ⚠️ **BREAKING CHANGE**: No compatible con v1.x (requiere reinstalación limpia)

### Versión 1.0.3 (2026-01-21)
- ✅ Agregadas cadenas de idioma en inglés (lang/en/udes.php)
- ✅ Cumplimiento completo de estándares Moodle
- ✅ Documentación de cumplimiento GDPR

### Versión 1.0.2 (2026-01-21)
- 🐛 Correcciones de bugs críticos

### Versión 1.0.1 (2026-01-21)
- ✅ Versión inicial funcional con 25 archivos

### Versión 1.0.0 (2026-01-21)
- ✅ Versión inicial
- ✅ Implementación completa del workflow de 6 fases
- ✅ Soporte para 5 categorías de recursos educativos con 50+ tipos
- ✅ Sistema de roles y permisos (8 roles específicos)
- ✅ Sistema de notificaciones automáticas
- ✅ Restricción de descarga de archivos (solo visualización)
- ✅ Interfaz de usuario responsive
- ✅ Módulos AMD JavaScript compilados
- ✅ Base de datos completa con 7 tablas
- ✅ Workflow manager con validaciones por fase
- ✅ Resource manager con formularios dinámicos
- ✅ Contadores automáticos de recursos
- ✅ Sistema de comentarios y aprobaciones
- ✅ Trazabilidad completa del proceso
