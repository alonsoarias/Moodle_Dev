# Sistema de Producción de Recursos Educativos UDES

Plugin de Moodle para gestionar el proceso de producción de recursos educativos digitales de la Universidad de Santander (UDES).

## Descripción

Este plugin implementa un sistema completo de workflow para la producción de recursos educativos digitales, siguiendo las 6 fases del proceso de virtualización de cursos de UDES:

1. **Fase 1: Caracterización** - Diligenciamiento de la caracterización y selección de recursos educativos
2. **Fase 2: Revisión Curricular** - Revisión y validación curricular
3. **Fase 3: Par / Corrector de Estilo** - Revisión por pares y corrección de estilo
4. **Fase 4: Producción** - Desarrollo de recursos educativos
5. **Fase 5: Alistamiento** - Alistamiento en plataforma Moodle
6. **Fase 6: Aprobación Final** - Aprobación final del curso

## Características

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
   - Programa académico
   - Nombre del curso
5. Guarde los cambios

### Fase 1: Caracterización

1. Los usuarios con rol de Experto Disciplinar o Asesor Metodológico acceden a la actividad
2. Completan el formulario de caracterización:
   - Marcan si requieren CVP (Curso Virtual Portable)
   - Seleccionan recursos generales (video de bienvenida, foro, mapa del curso, etc.)
3. Agregan recursos educativos por unidad y tema:
   - Seleccionan el tipo de recurso (categoría)
   - Eligen el recurso específico
   - Completan los campos requeridos según el tipo de recurso

4. Una vez completa la caracterización, el Asesor Metodológico aprueba la fase

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
│   ├── workflow/                 # Gestión de workflow
│   │   └── workflow_manager.php
│   ├── form/                     # Formularios personalizados
│   └── recurso_manager.php       # Gestión de recursos
├── db/
│   ├── access.php                # Definición de capacidades
│   └── install.xml               # Esquema de base de datos
├── lang/
│   └── es/
│       └── udes.php              # Cadenas en español
├── backup/                       # Soporte para backup/restore
├── tests/                        # Tests unitarios
├── lib.php                       # Funciones principales
├── mod_form.php                  # Formulario de configuración
├── version.php                   # Información de versión
├── view.php                      # Vista principal
├── recursos.php                  # Gestión de recursos
└── README.md                     # Este archivo
```

## Base de Datos

### Tablas Principales

- **udes**: Instancias de actividades
- **udes_caracterizacion**: Datos de caracterización
- **udes_recursos**: Recursos educativos
- **udes_workflow**: Seguimiento de fases
- **udes_aprobaciones**: Aprobaciones y rechazos
- **udes_comentarios**: Comentarios y retroalimentación
- **udes_role_assignments**: Asignaciones de roles

## API del Plugin

### Workflow Manager

```php
use mod_udes\workflow\workflow_manager;

$workflow = new workflow_manager($udesid);

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
$recursoid = recurso_manager::create_recurso($udesid, $data, $userid);

// Obtener recursos
$recursos = recurso_manager::get_recursos($udesid);

// Obtener conteo por categoría
$counts = recurso_manager::get_resource_count_by_category($udesid);
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
