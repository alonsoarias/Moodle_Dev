# Manual de Usuario - Plugin Caracterización RED

## Módulo de Caracterización de Recursos Educativos Digitales (RED)
### Universidad de Santander - UDES

**Versión:** 1.1.2
**Fecha:** Enero 2024
**Autor:** Alonso Arias - OrionCloud

---

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Requisitos del Sistema](#requisitos-del-sistema)
3. [Instalación](#instalación)
4. [Roles y Permisos](#roles-y-permisos)
5. [Flujo de Trabajo - Las 6 Fases](#flujo-de-trabajo---las-6-fases)
6. [Guía de Uso](#guía-de-uso)
   - [Crear una Actividad](#crear-una-actividad)
   - [Crear una Matriz de Caracterización](#crear-una-matriz-de-caracterización)
   - [Gestionar Fases](#gestionar-fases)
7. [Tipos de Recursos](#tipos-de-recursos)
8. [Notificaciones](#notificaciones)
9. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## 1. Introducción

El **Módulo de Caracterización RED** es un plugin para Moodle desarrollado específicamente para la **Universidad de Santander (UDES)**. Este módulo permite gestionar el proceso completo de producción de Recursos Educativos Digitales (RED), siguiendo un flujo de trabajo estructurado en **6 fases** que involucra a diferentes roles del equipo de producción académica.

### Objetivos del Plugin

- Centralizar la gestión de caracterización de cursos virtuales
- Automatizar el flujo de trabajo de producción de RED
- Facilitar la colaboración entre los diferentes roles del proceso
- Generar un seguimiento detallado del progreso de cada matriz
- Mantener un inventario de recursos educativos por curso

---

## 2. Requisitos del Sistema

| Requisito | Versión Mínima |
|-----------|----------------|
| Moodle | 4.0 o superior |
| PHP | 7.4 o superior |
| Base de datos | MySQL 5.7+ / PostgreSQL 10+ / MariaDB 10.2+ |

---

## 3. Instalación

### Pasos de Instalación

1. Descargue el plugin y extraiga el contenido en la carpeta `/mod/caracterizacion/` de su instalación de Moodle.

2. Inicie sesión como administrador en Moodle.

3. Vaya a **Administración del sitio → Notificaciones**.

4. Moodle detectará el nuevo plugin y mostrará la pantalla de instalación.

5. Haga clic en **Actualizar base de datos de Moodle ahora**.

6. El plugin creará automáticamente:
   - Las tablas necesarias en la base de datos
   - Los 8 roles UDES con sus permisos correspondientes

### Roles Creados Automáticamente

Durante la instalación, el plugin crea los siguientes roles en el sistema:

| Rol | Nombre en Moodle |
|-----|------------------|
| Experto Disciplinar | `udes_experto_disciplinar` |
| Asesor Metodológico | `udes_asesor_metodologico` |
| Revisor Curricular | `udes_revisor_curricular` |
| Par Académico | `udes_par_academico` |
| Corrector de Estilo | `udes_corrector_estilo` |
| Coordinación de Producción | `udes_coord_produccion` |
| Producción | `udes_produccion` |
| Alistamiento | `udes_alistamiento` |

---

## 4. Roles y Permisos

### Descripción de Roles

#### Experto Disciplinar
- **Responsabilidad:** Diligencia los formularios de caracterización y define los recursos educativos necesarios.
- **Fase de participación:** Fase 1
- **Permisos:** Crear y editar matrices, actuar en fase 1

#### Asesor Metodológico (Diseñador Instruccional)
- **Responsabilidad:** Acompaña al experto disciplinar, coordina ajustes y aprueba las fases.
- **Fase de participación:** Fases 1, 2, 3 y 6
- **Permisos:** Crear y editar matrices, aprobar/rechazar fases, ver todas las matrices

#### Revisor Curricular
- **Responsabilidad:** Revisa la caracterización y realiza recomendaciones curriculares.
- **Fase de participación:** Fase 2
- **Permisos:** Ver y actuar en fase 2

#### Par Académico (Par Disciplinar)
- **Responsabilidad:** Revisa el contenido desde la perspectiva disciplinar.
- **Fase de participación:** Fase 3
- **Permisos:** Ver y actuar en fase 3

#### Corrector de Estilo
- **Responsabilidad:** Ajusta textos de caracterización y recursos educativos.
- **Fase de participación:** Fase 3
- **Permisos:** Ver y actuar en fase 3

#### Coordinación de Producción
- **Responsabilidad:** Asigna recursos al equipo de producción y aprueba la fase 4.
- **Fase de participación:** Fase 4
- **Permisos:** Ver todas las matrices, actuar y aprobar fase 4

#### Producción (Diseñador)
- **Responsabilidad:** Desarrolla los recursos educativos digitales.
- **Fase de participación:** Fase 4
- **Permisos:** Ver y actuar en fase 4

#### Alistamiento
- **Responsabilidad:** Alista los recursos en la plataforma UDES Virtual.
- **Fase de participación:** Fase 5
- **Permisos:** Ver, actuar y aprobar fase 5

---

## 5. Flujo de Trabajo - Las 6 Fases

El proceso de producción de RED sigue un flujo secuencial de 6 fases:

```
┌─────────────────────────────────────────────────────────────────────┐
│                     FLUJO DE TRABAJO UDES                           │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  FASE 1                    FASE 2                    FASE 3         │
│  ┌──────────────┐         ┌──────────────┐         ┌──────────────┐ │
│  │ Diligencia   │  ────►  │  Revisión    │  ────►  │ Par/Corrector│ │
│  │Caracterización│        │  Curricular  │         │  de Estilo   │ │
│  └──────────────┘         └──────────────┘         └──────────────┘ │
│  • Experto                • Revisor                • Par Académico  │
│  • Asesor                 • Asesor                 • Corrector      │
│                                                    • Asesor         │
│                                                                     │
│  FASE 4                    FASE 5                    FASE 6         │
│  ┌──────────────┐         ┌──────────────┐         ┌──────────────┐ │
│  │  Producción  │  ────►  │ Alistamiento │  ────►  │  Aprobación  │ │
│  │              │         │  en Moodle   │         │    Final     │ │
│  └──────────────┘         └──────────────┘         └──────────────┘ │
│  • Coord.Prod.            • Alistamiento           • Asesor         │
│  • Producción                                                       │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Detalle de Cada Fase

#### Fase 1: Diligencia la Caracterización
- **Actores:** Experto Disciplinar, Asesor Metodológico
- **Aprobador:** Asesor Metodológico
- **Actividades:**
  - El Experto Disciplinar completa el formulario de caracterización
  - Define información del curso (programa académico, nombre)
  - Asigna los roles del proceso
  - Selecciona recursos generales del curso
  - Define unidades, temas y recursos específicos
  - El Asesor Metodológico acompaña y revisa
  - El Asesor aprueba para pasar a fase 2

#### Fase 2: Revisión Curricular
- **Actores:** Revisor Curricular, Asesor Metodológico
- **Aprobador:** Asesor Metodológico
- **Actividades:**
  - El Revisor Curricular analiza la caracterización
  - Verifica coherencia con el currículo del programa
  - Realiza observaciones y recomendaciones
  - El Asesor coordina los ajustes necesarios
  - El Asesor aprueba para pasar a fase 3

#### Fase 3: Par / Corrector de Estilo
- **Actores:** Par Académico, Corrector de Estilo, Asesor Metodológico
- **Aprobador:** Asesor Metodológico
- **Actividades:**
  - El Par Académico revisa desde la perspectiva disciplinar
  - El Corrector de Estilo ajusta redacción y estilo
  - El Asesor coordina y consolida los ajustes
  - El Asesor aprueba para pasar a fase 4

#### Fase 4: Producción
- **Actores:** Coordinación de Producción, Producción (Diseñador)
- **Aprobador:** Coordinación de Producción
- **Actividades:**
  - Coordinación asigna recursos al equipo de diseño
  - El equipo de Producción desarrolla los RED
  - Se crean videos, infografías, interactivos, etc.
  - Coordinación revisa y aprueba la producción

#### Fase 5: Alistamiento en Moodle
- **Actores:** Alistamiento
- **Aprobador:** Alistamiento
- **Actividades:**
  - El profesional de Alistamiento sube los recursos a UDES Virtual
  - Configura las actividades en el curso Moodle
  - Verifica funcionamiento de todos los elementos
  - Marca la fase como completada

#### Fase 6: Aprobación Final del Curso
- **Actores:** Asesor Metodológico
- **Aprobador:** Asesor Metodológico
- **Actividades:**
  - El Asesor Metodológico realiza revisión final
  - Verifica que todos los elementos estén correctamente alistados
  - Aprueba el curso para su publicación
  - La matriz queda en estado "Completada"

---

## 6. Guía de Uso

### Crear una Actividad de Caracterización

1. Ingrese al curso donde desea agregar la actividad.

2. Active el **modo de edición** del curso.

3. En la sección deseada, haga clic en **"Añadir una actividad o recurso"**.

4. Seleccione **"Caracterización RED"** de la lista.

5. Complete el formulario:
   - **Nombre:** Nombre descriptivo de la actividad
   - **Descripción:** Descripción opcional

6. Haga clic en **"Guardar cambios y mostrar"**.

### Crear una Nueva Matriz de Caracterización

1. Acceda a la actividad de Caracterización RED.

2. Haga clic en el botón **"Nueva Matriz de Caracterización"**.

3. Complete las secciones del formulario:

#### Sección 1: Información del Curso
- **Programa Académico:** Nombre del programa (ej: "Ingeniería de Sistemas")
- **Nombre del Curso:** Nombre completo del curso

#### Sección 2: Asignación de Roles
Seleccione los usuarios que participarán en cada rol:
- Asesor Metodológico
- Experto Disciplinar
- Revisor Curricular
- Par Académico
- Corrector de Estilo
- Coordinación de Producción
- Producción
- Alistamiento

#### Sección 3: Recursos Generales del Curso
Marque los recursos generales que incluirá el curso:
- [ ] Curso Virtual Portable (CVP)
- [ ] Sala para Clases Virtuales
- [ ] Video de Bienvenida
- [ ] Foro del Curso
- [ ] Mapa del Curso

Para cada recurso puede agregar observaciones.

#### Sección 4: Unidades y Temas
Defina la estructura del curso:
1. Agregue unidades con su nombre
2. Dentro de cada unidad, agregue los temas
3. Para cada tema, agregue los recursos educativos necesarios

4. Haga clic en **"Guardar"** para crear la matriz.

### Gestionar Fases

#### Ver el Estado de una Matriz

1. Desde la lista de matrices, haga clic en **"Ver"** en la matriz deseada.

2. La vista muestra:
   - Información del curso
   - Roles asignados
   - Recursos generales
   - Unidades, temas y recursos
   - Resumen de cantidades
   - **Progreso de fases**

#### Acciones sobre las Fases

Dependiendo de su rol y la fase actual, podrá realizar las siguientes acciones:

| Acción | Descripción | Quién puede |
|--------|-------------|-------------|
| **Comentar** | Agregar observaciones a la fase | Actores de la fase |
| **Enviar a Revisión** | Marcar la fase como lista para aprobación | Actores de la fase |
| **Aprobar** | Aprobar la fase y avanzar a la siguiente | Aprobador de la fase |
| **Rechazar** | Rechazar y devolver para correcciones | Aprobador de la fase |

#### Estados de las Fases

| Estado | Descripción | Color |
|--------|-------------|-------|
| Pendiente | La fase no ha iniciado | Gris |
| En Revisión | La fase está siendo trabajada | Azul |
| Aprobada | La fase fue aprobada | Verde |
| Rechazada | La fase fue rechazada | Rojo |

---

## 7. Tipos de Recursos

### Recursos Generales del Curso

| Recurso | Descripción |
|---------|-------------|
| CVP | Curso Virtual Portable |
| Sala Virtual | Espacio para clases sincrónicas |
| Video de Bienvenida | Presentación del curso |
| Foro del Curso | Espacio de comunicación |
| Mapa del Curso | Navegación visual del curso |

### Recursos Educativos Digitales

#### Recursos Educativos Digitales
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
- Video con Diapositivas
- Video Explicativo

#### Recursos Interactivos Digitales
- Hotspots
- Emparejamiento
- Arrastra las Palabras
- Crucigrama
- Ordena los Párrafos
- Sopa de Letras
- Glosario Interactivo

#### Recursos Evaluativos
- Opción Única
- Opción Múltiple
- Verdadero o Falso
- Marca las Palabras
- Espacios en Blanco
- Dictado
- Tarjeta Didáctica
- Tarjetas de Diálogo

#### Recursos Colaborativos
- Wiki
- Tarea
- Lección
- Foro Temático
- Foro Social

#### Recursos Externos
- Video Conferencias
- Paquetes SCORM
- Plataformas Externas

---

## 8. Notificaciones

El sistema envía notificaciones automáticas cuando:

| Evento | Notificados |
|--------|-------------|
| Fase 1 aprobada | Revisor Curricular |
| Fase 2 aprobada | Par Académico, Corrector de Estilo |
| Fase 3 aprobada | Coordinación de Producción |
| Fase 4 aprobada | Alistamiento |
| Fase 5 aprobada | Asesor Metodológico |
| Fase rechazada | Actores de la fase |

Las notificaciones se envían tanto por el sistema de mensajería de Moodle como por correo electrónico (según la configuración del usuario).

---

## 9. Preguntas Frecuentes

### ¿Por qué no puedo ver el botón "Nueva Matriz"?
Necesita tener el permiso `mod/caracterizacion:crearmatriz`. Este permiso está asignado a los roles de Experto Disciplinar y Asesor Metodológico.

### ¿Por qué no puedo aprobar una fase?
Solo el rol designado como "aprobador" de esa fase puede aprobarla. Verifique que tenga el rol correcto asignado y que sea su turno en el flujo.

### ¿Cómo puedo ver todas las matrices del sistema?
Necesita el permiso `mod/caracterizacion:vertodasmatrices`. Este permiso está asignado a los roles de Asesor Metodológico y Coordinación de Producción.

### ¿Qué pasa si se rechaza una fase?
Cuando una fase es rechazada, los actores de esa fase reciben una notificación y deben realizar los ajustes solicitados antes de volver a enviar a revisión.

### ¿Se pueden eliminar matrices?
Sí, pero solo usuarios con el permiso `mod/caracterizacion:eliminarmatriz` pueden hacerlo. Esta acción es irreversible.

---

## Soporte

Para soporte técnico, contacte a:
- **Email:** soporte@orioncloud.com.co
- **Desarrollador:** Alonso Arias

---

**© 2024 Universidad de Santander - UDES. Todos los derechos reservados.**
