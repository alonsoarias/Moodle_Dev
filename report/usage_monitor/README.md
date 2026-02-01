# Usage Monitor

Plugin exclusivo de [IngeWeb](https://ingeweb.co) para monitoreo de uso en plataformas Moodle.

## Descripcion

Usage Monitor es un plugin de reporte para Moodle que proporciona a los administradores capacidades de monitoreo y reportes de actividad de usuarios y uso de disco. El plugin incluye tareas programadas para recoleccion de datos y notificaciones por correo electronico cuando se exceden los umbrales configurados.

**Este plugin es de uso exclusivo del servicio de hosting para Moodle proporcionado por IngeWeb.**

## Requisitos

- Moodle 4.1 o superior
- PHP 8.0 o superior
- Servidor autorizado por IngeWeb

## Caracteristicas

### Monitoreo

- **Actividad de Usuarios**: Seguimiento de inicios de sesion unicos por dia con datos historicos de los ultimos 90 dias.
- **Uso de Disco**: Monitoreo del espacio total incluyendo base de datos, directorio de archivos, cache y otros componentes.
- **Analisis de Cursos**: Identificacion de los cursos mas grandes por uso de almacenamiento.
- **Alertas de Umbral**: Niveles de advertencia configurables para conteo de usuarios y uso de disco.

### Dashboard

El plugin proporciona un dashboard completo con:

- Porcentaje de uso de disco en tiempo real con indicadores visuales
- Conteo de usuarios diarios con comparacion de umbrales
- Graficas historicas de uso de disco (30 dias) y actividad de usuarios (10 dias)
- Desglose de almacenamiento por directorio
- Top de cursos por tamano
- Informacion general del sistema
- Recomendaciones contextuales basadas en niveles de uso actuales

### API REST

El plugin expone funciones de API externa para integracion con sistemas externos:

| Funcion | Tipo | Descripcion |
|---------|------|-------------|
| `report_usage_monitor_get_usage_data` | read | Obtiene estadisticas de uso precalculadas |
| `report_usage_monitor_get_monitor_stats` | read | Obtiene estadisticas completas de monitoreo |
| `report_usage_monitor_get_notification_history` | read | Obtiene historial de notificaciones con paginacion |
| `report_usage_monitor_set_usage_thresholds` | write | Actualiza umbrales de usuarios y disco |

Todas las respuestas GET incluyen el `hostname` del servidor.

### Soporte Multi-Base de Datos

Todas las consultas SQL son compatibles con:

- MySQL / MariaDB
- PostgreSQL
- Microsoft SQL Server
- Oracle

### Notificaciones

Notificaciones profesionales por correo electronico cuando se exceden los umbrales, incluyendo:

- Resumen del uso actual
- Tablas de datos historicos
- Informacion de la plataforma
- Recomendaciones accionables
- Enlaces directos al dashboard

## Configuracion

Navegue a _Administracion del sitio > Plugins > Reportes > Usage Monitor_ para configurar:

### Ajustes Principales

| Ajuste | Descripcion | Valor por defecto |
|--------|-------------|-------------------|
| Limite de Usuarios | Umbral maximo de usuarios diarios | 100 |
| Cuota de Disco | Cuota de disco en gigabytes | 10 |
| Email | Correo destinatario de notificaciones | - |

### Ajustes de Notificacion

| Ajuste | Descripcion | Valor por defecto |
|--------|-------------|-------------------|
| Nivel de Advertencia de Disco | Porcentaje que activa alertas de disco | 90% |
| Nivel de Advertencia de Usuarios | Porcentaje que activa alertas de usuarios | 90% |

### Requisitos del Sistema

El plugin puede usar opcionalmente el comando `du` para calculos precisos de uso de disco en sistemas Linux. Configure la ruta a `du` en _Administracion del sitio > Servidor > Rutas del sistema_.

## Tareas Programadas

| Tarea | Descripcion | Programacion |
|-------|-------------|--------------|
| `disk_usage` | Calcula uso de espacio en disco | Diario a las 02:00 |
| `last_users` | Calcula inicios de sesion recientes | Cada 4 horas |
| `users_daily` | Cuenta usuarios unicos diarios | Cada hora |
| `users_daily_90_days` | Calcula maximo de usuarios en 90 dias | Diario a las 03:00 |
| `notification_disk` | Envia alertas de uso de disco | Diario a las 08:00 |
| `notification_userlimit` | Envia alertas de limite de usuarios | Diario a las 08:00 |

## Arquitectura

### Estructura de Directorios

```
report/usage_monitor/
├── classes/
│   ├── external/           # Clases de API externa
│   │   ├── get_monitor_stats.php
│   │   ├── get_notification_history.php
│   │   ├── get_usage_data.php
│   │   └── set_usage_thresholds.php
│   ├── output/             # Clases renderable y renderer
│   │   ├── dashboard.php
│   │   └── renderer.php
│   ├── task/               # Tareas programadas
│   │   ├── disk_usage.php
│   │   ├── last_users.php
│   │   ├── notification_disk.php
│   │   ├── notification_userlimit.php
│   │   ├── users_daily.php
│   │   └── users_daily_90_days.php
│   └── observer.php        # Observador de eventos
├── db/
│   ├── access.php          # Capacidades
│   ├── events.php          # Suscripciones a eventos
│   ├── install.php         # Script de instalacion
│   ├── services.php        # Definicion de servicios externos
│   ├── tasks.php           # Definicion de tareas programadas
│   ├── uninstall.php       # Script de desinstalacion
│   └── upgrade.php         # Procedimientos de actualizacion
├── lang/
│   ├── en/                 # Cadenas en ingles
│   └── es/                 # Cadenas en espanol
├── templates/              # Plantillas Mustache
├── amd/                    # Modulos JavaScript AMD
├── index.php               # Pagina principal del dashboard
├── locallib.php            # Funciones de libreria
├── settings.php            # Configuracion de administrador
└── version.php             # Version del plugin
```

### Capacidades

| Capacidad | Descripcion |
|-----------|-------------|
| `report/usage_monitor:view` | Ver reportes de Usage Monitor |
| `report/usage_monitor:manage` | Gestionar configuracion de Usage Monitor |

## Validacion de Servidor

Este plugin valida automaticamente el hostname del servidor y solo se ejecuta en servidores autorizados por IngeWeb.

En servidores no autorizados, el plugin:

- Muestra un mensaje de error en el dashboard
- Omite todas las tareas programadas silenciosamente
- Retorna errores desde los endpoints de API
- Oculta opciones de configuracion

## Soporte

Para soporte tecnico, contacte a [IngeWeb](https://ingeweb.co/soporte).

## Licencia

Copyright 2025 Soporte IngeWeb <soporte@ingeweb.co>

Este programa es software libre: puede redistribuirlo y/o modificarlo bajo los terminos de la Licencia Publica General de GNU publicada por la Free Software Foundation, ya sea la version 3 de la Licencia, o (a su eleccion) cualquier version posterior.

Este programa se distribuye con la esperanza de que sea util, pero SIN NINGUNA GARANTIA; sin siquiera la garantia implicita de COMERCIABILIDAD o APTITUD PARA UN PROPOSITO PARTICULAR. Consulte la Licencia Publica General de GNU para mas detalles.

Deberia haber recibido una copia de la Licencia Publica General de GNU junto con este programa. Si no es asi, consulte <https://www.gnu.org/licenses/>.
