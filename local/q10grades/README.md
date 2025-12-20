# Q10 Grades Sync - Plugin de Moodle

Plugin de Moodle para sincronizar notas de cursos hacia el sistema académico Q10.

## Descripción

Este plugin permite a los profesores:

1. **Mapear cursos de Moodle a materias de Q10**: Configurar la correspondencia entre un curso de Moodle y una materia en Q10.

2. **Crear fórmulas de calificación**: Combinar múltiples actividades de Moodle en una sola nota para subir a Q10. Por ejemplo, si tienes 2 cuestionarios que corresponden a "Trabajos" en Q10, puedes seleccionar esas 2 actividades y definir una fórmula (promedio, ponderado, etc.) para calcular la nota final.

3. **Subir notas a Q10**: Vista previa de las notas calculadas y carga manual o automática hacia Q10.

4. **Historial de sincronización**: Registro completo de todas las sincronizaciones realizadas.

## Características

### Fórmulas de Calificación
- **Promedio simple**: Calcula el promedio de todas las actividades seleccionadas
- **Promedio ponderado**: Asigna pesos diferentes a cada actividad
- **Suma**: Suma todas las notas (máximo 100)
- **Nota más alta**: Toma la mejor nota de las seleccionadas
- **Nota más baja**: Toma la nota más baja
- **Fórmula personalizada**: Expresión matemática personalizada

### Sincronización
- Sincronización manual desde la interfaz
- Sincronización automática programada
- Vista previa de notas antes de subir
- Historial detallado de sincronizaciones

## Instalación

1. Copiar la carpeta `q10grades` en `/local/`
2. Navegar a `Administración del sitio > Notificaciones` para completar la instalación
3. Configurar las credenciales de la API en `Administración del sitio > Plugins > Plugins locales > Q10 Grades Sync`

## Configuración

### Configuración Global (Administrador)

En `Administración del sitio > Plugins > Plugins locales > Q10 Grades Sync`:

1. **URL de la API**: URL base de la API de Q10 (ej: `https://api.q10.com/v1`)
2. **Clave API**: Client ID proporcionado por Q10
3. **Secreto API**: Client Secret proporcionado por Q10
4. **ID de Institución**: Identificador de su institución en Q10
5. **Campo de mapeo de usuarios**: Campo de Moodle usado para identificar estudiantes en Q10 (idnumber, username, email)
6. **Formato de notas**: Porcentaje, valor raw, o letra

### Configuración por Curso (Profesor)

1. Acceder al curso
2. Click en "Q10 Grade Sync" en el menú de administración del curso
3. En la pestaña "Mapeo de Curso":
   - Ingresar el ID de materia de Q10
   - Opcionalmente, período y grupo
4. En la pestaña "Fórmulas":
   - Crear fórmulas para cada componente de evaluación de Q10
   - Seleccionar las actividades de Moodle que corresponden
   - Elegir el tipo de cálculo

## Uso

### Flujo típico para un profesor:

1. **Configurar el mapeo del curso**
   - Ir a "Q10 Grade Sync" > "Mapeo de Curso"
   - Ingresar el ID de materia de Q10

2. **Crear fórmulas**
   - Ir a "Fórmulas"
   - Click en "Agregar fórmula"
   - Dar un nombre (ej: "Nota de Trabajos")
   - Ingresar el ID del componente de Q10 (ej: "TRAB01")
   - Seleccionar las actividades de Moodle
   - Elegir el tipo de cálculo

3. **Subir notas**
   - Ir a "Cargar Notas"
   - Revisar la vista previa de las notas calculadas
   - Click en "Cargar Notas a Q10"

### Ejemplo de fórmulas

**Ejemplo 1: Promedio de cuestionarios**
- Nombre: "Exámenes Parciales"
- Componente Q10: "EXAM"
- Tipo: Promedio simple
- Actividades: Cuestionario 1, Cuestionario 2, Cuestionario 3

**Ejemplo 2: Trabajos ponderados**
- Nombre: "Trabajos del Curso"
- Componente Q10: "TRAB"
- Tipo: Promedio ponderado
- Actividades y pesos:
  - Tarea 1: peso 2
  - Tarea 2: peso 3
  - Tarea 3: peso 5

## Estructura de Archivos

```
local/q10grades/
├── classes/
│   ├── form/
│   │   ├── course_mapping_form.php
│   │   ├── formula_config_form.php
│   │   └── grade_item_mapping_form.php
│   ├── privacy/
│   │   └── provider.php
│   ├── task/
│   │   └── sync_grades_task.php
│   ├── formula_calculator.php
│   ├── grade_sync_manager.php
│   └── q10_api_client.php
├── db/
│   ├── access.php
│   ├── install.xml
│   └── tasks.php
├── lang/
│   ├── en/
│   │   └── local_q10grades.php
│   └── es/
│       └── local_q10grades.php
├── formulas.php
├── history.php
├── lib.php
├── mapping.php
├── README.md
├── settings.php
├── sync.php
├── upload.php
└── version.php
```

## Requisitos

- Moodle 4.0 o superior
- PHP 7.4 o superior
- Acceso a la API de Q10 (credenciales proporcionadas por Q10)

## Permisos (Capabilities)

- `local/q10grades:sync` - Sincronizar notas a Q10 (profesores, managers)
- `local/q10grades:viewlogs` - Ver historial de sincronización (profesores, managers)
- `local/q10grades:configure` - Configurar el plugin (managers)
- `local/q10grades:managemapping` - Gestionar mapeo de cursos (managers)

## Tablas de Base de Datos

- `local_q10grades_mapping` - Mapeo de cursos a materias Q10
- `local_q10grades_usermapping` - Mapeo de usuarios a estudiantes Q10
- `local_q10grades_sync_log` - Historial de sincronizaciones
- `local_q10grades_itemmapping` - Mapeo de ítems de calificación
- `local_q10grades_formula` - Fórmulas de cálculo de notas

## API de Q10

El plugin está diseñado para funcionar con la API REST de Q10. Los endpoints principales utilizados son:

- `POST /auth/token` - Autenticación
- `GET /academic/periods` - Obtener períodos
- `GET /academic/subjects` - Obtener materias
- `GET /academic/subjects/{id}/students` - Obtener estudiantes
- `POST /academic/grades` - Subir calificaciones
- `POST /academic/grades/final` - Subir notas finales

**Nota**: Los endpoints específicos pueden variar según la versión de la API de Q10. Consulte la documentación oficial en [developer.q10.com](https://developer.q10.com/).

## Soporte

Para reportar problemas o solicitar características, contacte al administrador de su institución.

## Licencia

GNU GPL v3 o posterior

## Créditos

Desarrollado para la integración de Moodle con el sistema académico Q10.
