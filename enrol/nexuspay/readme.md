# NexusPay Enrollment Plugin for Moodle

<div align="center">

![NexusPay Logo](https://nexuslabs.com.co/assets/nexuspay-banner.png)

[![Moodle Plugin](https://img.shields.io/badge/Moodle-4.1%2B-orange)](https://moodle.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/License-Proprietary-red)](LICENSE)
[![Version](https://img.shields.io/badge/Version-1.0.0-green)](https://nexuslabs.com.co)
[![Language](https://img.shields.io/badge/Languages-EN%20%7C%20ES-yellow)](README.md#languages)

**Premium Payment Enrollment Solution for Moodle LMS**

[English](#english) | [Español](#español) | [Installation](#installation) | [Support](#support)

</div>

---

## English

### 🎯 Overview

NexusPay is a premium enrollment plugin for Moodle LMS that provides a comprehensive payment-based course access management system. Designed with flexibility and scalability in mind, it offers seamless integration with Moodle's payment subsystem while providing advanced features for educational institutions worldwide.

### ✨ Key Features

#### 💳 **Payment Management**
- Seamless integration with Moodle's core payment subsystem
- Support for multiple payment gateways simultaneously
- Multi-currency support with focus on **USD** and **COP** (Colombian Peso)
- Automated payment processing and enrollment activation
- Comprehensive payment history and reporting

#### 🎓 **Enrollment Options**
- **Flexible enrollment periods**: Minutes, hours, days, weeks, months, or years
- **Free trial periods**: Configurable trial access for first-time students
- **Uninterrupted payments**: Automatic calculation for missed payment periods
- **Group enrollment keys**: Secure group-based access control
- **Bulk enrollment management**: Administrative tools for mass enrollments

#### 📧 **Notifications & Communication**
- Automated enrollment expiry notifications
- Customizable email templates
- Multi-language support (English & Spanish)
- Scheduled reminder system
- Payment confirmation messages

#### 🔒 **Security & Compliance**
- GDPR compliant with full privacy API implementation
- Secure payment data handling
- Role-based access control
- Audit logging capabilities
- PCI DSS compliance ready

#### 🚀 **Performance & Scalability**
- Optimized database queries with intelligent indexing
- Efficient caching mechanisms
- Asynchronous task processing
- Support for high-concurrency environments
- Minimal server resource footprint

### 📋 Requirements

#### System Requirements
- **Moodle**: 4.1 LTS or higher (tested up to 4.4)
- **PHP**: 8.0 or higher
- **Database**: 
  - MySQL 5.7+ / MariaDB 10.2.29+
  - PostgreSQL 12+
- **Web Server**: Apache 2.4+ / Nginx 1.18+
- **Memory**: Minimum 256MB PHP memory limit

#### Moodle Configuration
- Payment subsystem must be enabled
- At least one payment gateway configured
- Cron properly configured and running
- Email system configured for notifications

### 📦 Installation

#### Method 1: Manual Installation

1. **Purchase and download** the plugin from [NexusLabs](https://nexuslabs.com.co)

2. **Extract the plugin**
   ```bash
   unzip nexuspay_v1.0.0.zip
   ```

3. **Copy to Moodle directory**
   ```bash
   cp -r nexuspay /path/to/moodle/enrol/
   ```

4. **Set proper permissions**
   ```bash
   chown -R www-data:www-data /path/to/moodle/enrol/nexuspay
   chmod -R 755 /path/to/moodle/enrol/nexuspay
   ```

5. **Install via Moodle**
   - Login as administrator
   - Navigate to *Site administration → Notifications*
   - Follow the installation wizard

#### Method 2: Command Line Installation

```bash
# Navigate to Moodle root
cd /path/to/moodle

# Extract plugin directly
unzip /path/to/nexuspay_v1.0.0.zip -d enrol/

# Run Moodle upgrade
php admin/cli/upgrade.php

# Clear caches
php admin/cli/purge_caches.php
```

### ⚙️ Configuration

#### Global Settings

Navigate to: *Site administration → Plugins → Enrolments → NexusPay Enrollment*

##### Basic Configuration
- **Enable plugin**: Activate NexusPay enrollments globally
- **Default role**: Role assigned to users after payment (typically Student)
- **Default enrollment duration**: Set default access period
- **Currency**: Choose default currency (USD or COP)

##### Payment Settings
- **Payment account**: Select or create payment account
- **Default cost**: Set default course price
- **Free trial period**: Configure trial duration
- **Uninterrupted payments**: Enable continuous billing

##### Notification Settings
- **Expiry notifications**: Configure when to notify users
- **Notification threshold**: Days before expiry to send alerts
- **Email templates**: Customize notification messages

#### Course-Level Configuration

1. Navigate to your course
2. Go to *Participants → Enrollment methods*
3. Add method: *NexusPay Enrollment*
4. Configure course-specific settings:
   - Enrollment fee
   - Currency override
   - Enrollment period
   - Group settings
   - Start/End dates

### 📚 User Guide

#### For Administrators

##### Managing Enrollments
1. Access course → *Participants*
2. Click *Enrollment methods*
3. Configure NexusPay instance
4. Monitor via *Reports → Payments*

##### Bulk Operations
- Use *Manage NexusPay enrollments* for bulk actions
- Import/Export user lists
- Apply group assignments
- Set custom enrollment periods

##### Reports & Analytics
- Payment reports: *Site administration → Reports → Payments*
- Enrollment statistics: *Course → Reports → Enrollment*
- Export data in CSV/Excel format

#### For Teachers

##### Monitoring Students
- View enrollment status in Participants list
- Check payment status for pending enrollments
- Send manual reminders if needed
- Manage group assignments

##### Course Access Control
- Set enrollment keys for groups
- Configure start/end dates
- Enable/disable self-enrollment
- Set payment requirements

#### For Students

##### Enrollment Process
1. Browse to desired course
2. Click *Enroll me*
3. Review payment information
4. Select payment method
5. Complete payment
6. Access course immediately

##### Managing Subscription
- View enrollment status in Dashboard
- Receive expiry notifications via email
- Renew subscription before expiry
- Access payment history in Profile

### 🔧 Advanced Configuration

#### Scheduled Tasks

Configure in: *Site administration → Server → Scheduled tasks*

| Task | Default Schedule | Purpose |
|------|-----------------|---------|
| Sync enrollments | */10 * * * * | Process expired enrollments |
| Send notifications | */15 * * * * | Send expiry alerts |
| Clean up payments | 0 2 * * * | Remove orphaned records |

#### Custom Fields Mapping

```php
// Instance configuration fields
customint1   // Payment account ID
customint2   // Group key requirement (0/1/2)
customint3   // Allow new enrollments (0/1)
customint4   // Force payment dates (0/1/2/3)
customint5   // Uninterrupted payments (0/1)
customint6   // Free trial period (seconds)
customint7   // Number of periods
customint8   // Show duration (0/1)
customchar1  // Period type (minute/hour/day/week/month/year)
customtext1  // Default group ID
```

### 🚀 API Integration

#### Enrollment API

```php
// Get plugin instance
$plugin = enrol_get_plugin('nexuspay');

// Enroll user programmatically
$instance = $DB->get_record('enrol', [
    'courseid' => $courseid,
    'enrol' => 'nexuspay'
]);

$plugin->enrol_user(
    $instance,
    $userid,
    $roleid,
    $timestart,
    $timeend
);
```

#### Payment Callbacks

```php
// Implement payment callback
class local_yourplugin_payment_callback {
    public static function payment_successful($data) {
        // Handle successful payment
    }
    
    public static function payment_failed($data) {
        // Handle failed payment
    }
}
```

#### Event Observers

```php
// Listen for NexusPay events
$observers = [
    [
        'eventname' => '\enrol_nexuspay\event\user_enrolled',
        'callback' => 'local_yourplugin_observer::user_enrolled',
    ],
];
```

### 🐛 Troubleshooting

#### Common Issues

##### Payment not processing
- Verify payment gateway is properly configured
- Check payment account has valid credentials
- Ensure SSL certificate is valid
- Review payment gateway logs

##### Enrollment not activating
- Confirm cron is running: `php admin/cli/cron.php`
- Check scheduled tasks are enabled
- Verify user has completed payment
- Review enrollment date restrictions

##### Notifications not sending
- Check email configuration: *Site administration → Server → Email*
- Verify notification settings are enabled
- Confirm user email addresses are valid
- Check message output configuration

##### Performance issues
- Enable caching: *Site administration → Development → Caching*
- Optimize database: `php admin/cli/mysql_optimizer.php`
- Review slow query log
- Consider increasing PHP memory limit

#### Debug Mode

Enable detailed logging:

```php
// In config.php
$CFG->debug = 32767;  // DEBUG_DEVELOPER
$CFG->debugdisplay = 1;
$CFG->debugpageinfo = 1;

// NexusPay specific debugging
$CFG->nexuspay_debug = true;
```

#### Log Files

Check logs in:
- Moodle logs: *Site administration → Reports → Logs*
- Server logs: `/var/log/apache2/error.log`
- Payment logs: *Site administration → Reports → Payments → Logs*

### 📊 Performance Optimization

#### Database Optimization
```sql
-- Add custom indexes for large installations
CREATE INDEX idx_nexuspay_user_course 
ON mdl_enrol_nexuspay(userid, courseid);

CREATE INDEX idx_nexuspay_payment_time 
ON mdl_enrol_nexuspay(timecreated);
```

#### Caching Configuration
```php
// Enable Redis caching (config.php)
$CFG->session_handler_class = '\core\session\redis';
$CFG->session_redis_host = '127.0.0.1';
$CFG->session_redis_port = 6379;
$CFG->session_redis_database = 0;
```

### 🔒 Security Best Practices

1. **Regular Updates**: Keep NexusPay updated to the latest version
2. **SSL/TLS**: Always use HTTPS for payment pages
3. **Access Control**: Limit administrative capabilities
4. **Audit Logs**: Regularly review payment and enrollment logs
5. **Backup**: Maintain regular database backups
6. **Testing**: Use sandbox/test payment gateways for development

---

## Español

### 🎯 Descripción General

NexusPay es un plugin premium de matriculación para Moodle LMS que proporciona un sistema integral de gestión de acceso a cursos basado en pagos. Diseñado con flexibilidad y escalabilidad en mente, ofrece integración perfecta con el subsistema de pagos de Moodle mientras proporciona características avanzadas para instituciones educativas en todo el mundo.

### ✨ Características Principales

#### 💳 **Gestión de Pagos**
- Integración perfecta con el subsistema de pagos principal de Moodle
- Soporte para múltiples pasarelas de pago simultáneamente
- Soporte multi-moneda con enfoque en **USD** y **COP** (Peso Colombiano)
- Procesamiento automatizado de pagos y activación de matriculación
- Historial completo de pagos y reportes

#### 🎓 **Opciones de Matriculación**
- **Períodos flexibles**: Minutos, horas, días, semanas, meses o años
- **Períodos de prueba gratuitos**: Acceso de prueba configurable para nuevos estudiantes
- **Pagos ininterrumpidos**: Cálculo automático para períodos de pago perdidos
- **Claves de grupo**: Control de acceso seguro basado en grupos
- **Gestión masiva**: Herramientas administrativas para matriculaciones masivas

#### 📧 **Notificaciones y Comunicación**
- Notificaciones automáticas de vencimiento de matriculación
- Plantillas de correo personalizables
- Soporte multi-idioma (Inglés y Español)
- Sistema de recordatorios programados
- Mensajes de confirmación de pago

#### 🔒 **Seguridad y Cumplimiento**
- Cumple con GDPR con implementación completa de API de privacidad
- Manejo seguro de datos de pago
- Control de acceso basado en roles
- Capacidades de registro de auditoría
- Preparado para cumplimiento PCI DSS

#### 🚀 **Rendimiento y Escalabilidad**
- Consultas de base de datos optimizadas con indexación inteligente
- Mecanismos de caché eficientes
- Procesamiento asíncrono de tareas
- Soporte para entornos de alta concurrencia
- Huella mínima de recursos del servidor

### 📋 Requisitos

#### Requisitos del Sistema
- **Moodle**: 4.1 LTS o superior (probado hasta 4.4)
- **PHP**: 8.0 o superior
- **Base de Datos**: 
  - MySQL 5.7+ / MariaDB 10.2.29+
  - PostgreSQL 12+
- **Servidor Web**: Apache 2.4+ / Nginx 1.18+
- **Memoria**: Mínimo 256MB de límite de memoria PHP

#### Configuración de Moodle
- El subsistema de pagos debe estar habilitado
- Al menos una pasarela de pago configurada
- Cron configurado correctamente y en ejecución
- Sistema de correo configurado para notificaciones

### 📦 Instalación

#### Método 1: Instalación Manual

1. **Compre y descargue** el plugin desde [NexusLabs](https://nexuslabs.com.co)

2. **Extraiga el plugin**
   ```bash
   unzip nexuspay_v1.0.0.zip
   ```

3. **Copie al directorio de Moodle**
   ```bash
   cp -r nexuspay /ruta/a/moodle/enrol/
   ```

4. **Establezca los permisos adecuados**
   ```bash
   chown -R www-data:www-data /ruta/a/moodle/enrol/nexuspay
   chmod -R 755 /ruta/a/moodle/enrol/nexuspay
   ```

5. **Instale vía Moodle**
   - Inicie sesión como administrador
   - Navegue a *Administración del sitio → Notificaciones*
   - Siga el asistente de instalación

#### Método 2: Instalación por Línea de Comandos

```bash
# Navegue a la raíz de Moodle
cd /ruta/a/moodle

# Extraiga el plugin directamente
unzip /ruta/a/nexuspay_v1.0.0.zip -d enrol/

# Ejecute la actualización de Moodle
php admin/cli/upgrade.php

# Limpie cachés
php admin/cli/purge_caches.php
```

### ⚙️ Configuración

#### Configuración Global

Navegue a: *Administración del sitio → Plugins → Matriculaciones → Matriculación NexusPay*

##### Configuración Básica
- **Habilitar plugin**: Active las matriculaciones NexusPay globalmente
- **Rol predeterminado**: Rol asignado a usuarios después del pago (típicamente Estudiante)
- **Duración predeterminada**: Establezca el período de acceso predeterminado
- **Moneda**: Elija la moneda predeterminada (USD o COP)

##### Configuración de Pagos
- **Cuenta de pago**: Seleccione o cree cuenta de pago
- **Costo predeterminado**: Establezca el precio predeterminado del curso
- **Período de prueba gratuito**: Configure la duración de la prueba
- **Pagos ininterrumpidos**: Habilite facturación continua

##### Configuración de Notificaciones
- **Notificaciones de vencimiento**: Configure cuándo notificar a usuarios
- **Umbral de notificación**: Días antes del vencimiento para enviar alertas
- **Plantillas de correo**: Personalice mensajes de notificación

#### Configuración a Nivel de Curso

1. Navegue a su curso
2. Vaya a *Participantes → Métodos de matriculación*
3. Agregue método: *Matriculación NexusPay*
4. Configure ajustes específicos del curso:
   - Costo de matriculación
   - Moneda específica
   - Período de matriculación
   - Configuración de grupos
   - Fechas de inicio/fin

### 📚 Guía del Usuario

#### Para Administradores

##### Gestión de Matriculaciones
1. Acceda al curso → *Participantes*
2. Haga clic en *Métodos de matriculación*
3. Configure la instancia NexusPay
4. Monitoree vía *Reportes → Pagos*

##### Operaciones Masivas
- Use *Gestionar matriculaciones NexusPay* para acciones masivas
- Importe/Exporte listas de usuarios
- Aplique asignaciones de grupo
- Establezca períodos personalizados

##### Reportes y Analíticas
- Reportes de pago: *Administración del sitio → Reportes → Pagos*
- Estadísticas de matriculación: *Curso → Reportes → Matriculación*
- Exporte datos en formato CSV/Excel

#### Para Profesores

##### Monitoreo de Estudiantes
- Vea el estado de matriculación en lista de Participantes
- Verifique estado de pago para matriculaciones pendientes
- Envíe recordatorios manuales si es necesario
- Gestione asignaciones de grupo

##### Control de Acceso al Curso
- Establezca claves de matriculación para grupos
- Configure fechas de inicio/fin
- Habilite/deshabilite auto-matriculación
- Establezca requisitos de pago

#### Para Estudiantes

##### Proceso de Matriculación
1. Navegue al curso deseado
2. Haga clic en *Matricularme*
3. Revise la información de pago
4. Seleccione método de pago
5. Complete el pago
6. Acceda al curso inmediatamente

##### Gestión de Suscripción
- Vea el estado de matriculación en el Tablero
- Reciba notificaciones de vencimiento por correo
- Renueve la suscripción antes del vencimiento
- Acceda al historial de pagos en el Perfil

### 🔧 Configuración Avanzada

#### Tareas Programadas

Configure en: *Administración del sitio → Servidor → Tareas programadas*

| Tarea | Programación Predeterminada | Propósito |
|-------|----------------------------|-----------|
| Sincronizar matriculaciones | */10 * * * * | Procesar matriculaciones vencidas |
| Enviar notificaciones | */15 * * * * | Enviar alertas de vencimiento |
| Limpiar pagos | 0 2 * * * | Eliminar registros huérfanos |

#### Mapeo de Campos Personalizados

```php
// Campos de configuración de instancia
customint1   // ID de cuenta de pago
customint2   // Requisito de clave de grupo (0/1/2)
customint3   // Permitir nuevas matriculaciones (0/1)
customint4   // Forzar fechas de pago (0/1/2/3)
customint5   // Pagos ininterrumpidos (0/1)
customint6   // Período de prueba gratuito (segundos)
customint7   // Número de períodos
customint8   // Mostrar duración (0/1)
customchar1  // Tipo de período (minute/hour/day/week/month/year)
customtext1  // ID de grupo predeterminado
```

### 🚀 Integración API

#### API de Matriculación

```php
// Obtener instancia del plugin
$plugin = enrol_get_plugin('nexuspay');

// Matricular usuario programáticamente
$instance = $DB->get_record('enrol', [
    'courseid' => $courseid,
    'enrol' => 'nexuspay'
]);

$plugin->enrol_user(
    $instance,
    $userid,
    $roleid,
    $timestart,
    $timeend
);
```

#### Callbacks de Pago

```php
// Implementar callback de pago
class local_tuplugin_payment_callback {
    public static function payment_successful($data) {
        // Manejar pago exitoso
    }
    
    public static function payment_failed($data) {
        // Manejar pago fallido
    }
}
```

#### Observadores de Eventos

```php
// Escuchar eventos de NexusPay
$observers = [
    [
        'eventname' => '\enrol_nexuspay\event\user_enrolled',
        'callback' => 'local_tuplugin_observer::user_enrolled',
    ],
];
```

### 🐛 Solución de Problemas

#### Problemas Comunes

##### El pago no se procesa
- Verifique que la pasarela de pago esté configurada correctamente
- Compruebe que la cuenta de pago tenga credenciales válidas
- Asegúrese de que el certificado SSL sea válido
- Revise los registros de la pasarela de pago

##### La matriculación no se activa
- Confirme que cron está ejecutándose: `php admin/cli/cron.php`
- Verifique que las tareas programadas estén habilitadas
- Confirme que el usuario ha completado el pago
- Revise las restricciones de fecha de matriculación

##### Las notificaciones no se envían
- Verifique la configuración de correo: *Administración del sitio → Servidor → Correo*
- Confirme que la configuración de notificaciones esté habilitada
- Verifique que las direcciones de correo sean válidas
- Revise la configuración de salida de mensajes

##### Problemas de rendimiento
- Habilite el caché: *Administración del sitio → Desarrollo → Caché*
- Optimice la base de datos: `php admin/cli/mysql_optimizer.php`
- Revise el registro de consultas lentas
- Considere aumentar el límite de memoria PHP

#### Modo Debug

Habilite registro detallado:

```php
// En config.php
$CFG->debug = 32767;  // DEBUG_DEVELOPER
$CFG->debugdisplay = 1;
$CFG->debugpageinfo = 1;

// Debug específico de NexusPay
$CFG->nexuspay_debug = true;
```

#### Archivos de Registro

Verifique registros en:
- Registros de Moodle: *Administración del sitio → Reportes → Registros*
- Registros del servidor: `/var/log/apache2/error.log`
- Registros de pagos: *Administración del sitio → Reportes → Pagos → Registros*

### 📊 Optimización de Rendimiento

#### Optimización de Base de Datos
```sql
-- Agregue índices personalizados para instalaciones grandes
CREATE INDEX idx_nexuspay_user_course 
ON mdl_enrol_nexuspay(userid, courseid);

CREATE INDEX idx_nexuspay_payment_time 
ON mdl_enrol_nexuspay(timecreated);
```

#### Configuración de Caché
```php
// Habilitar caché Redis (config.php)
$CFG->session_handler_class = '\core\session\redis';
$CFG->session_redis_host = '127.0.0.1';
$CFG->session_redis_port = 6379;
$CFG->session_redis_database = 0;
```

### 🔒 Mejores Prácticas de Seguridad

1. **Actualizaciones Regulares**: Mantenga NexusPay actualizado a la última versión
2. **SSL/TLS**: Siempre use HTTPS para páginas de pago
3. **Control de Acceso**: Limite las capacidades administrativas
4. **Registros de Auditoría**: Revise regularmente los registros de pagos y matriculaciones
5. **Respaldos**: Mantenga respaldos regulares de la base de datos
6. **Pruebas**: Use pasarelas de pago sandbox/prueba para desarrollo

---

## 📞 Support / Soporte

### 🌐 NexusLabs Support Center

**Website**: [https://nexuslabs.com.co](https://nexuslabs.com.co)  
**Email**: support@nexuslabs.com.co  
**Phone**: Contact via website

#### Support Channels / Canales de Soporte

- **🎫 Ticket System**: Available for licensed users
- **📧 Email Support**: Response within 24-48 hours
- **📚 Documentation**: Comprehensive guides and tutorials
- **🎥 Video Tutorials**: Step-by-step visual guides
- **💬 Community Forum**: User community and discussions


### 🔄 Version History

| Version | Release Date | Highlights |
|---------|--------------|------------|
| 1.0.0 | January 2025 | Initial release with full feature set |
| 1.0.1 | *Planned* | Performance improvements |
| 1.1.0 | *Planned* | Additional payment gateway support |

### 📜 License

This is proprietary software developed and maintained by **NexusLabs Colombia**.

#### Terms of Use
- Single site installation per license
- Source code modifications allowed for licensee use
- Redistribution prohibited without written permission
- Support and updates included with active license

#### Copyright
© 2025 NexusLabs Colombia. All rights reserved.

**NexusLabs**  
Pamplona, Colombia  
[https://nexuslabs.com.co](https://nexuslabs.com.co)

---

<div align="center">

### 🌟 Get NexusPay Today / Obtenga NexusPay Hoy

[![Purchase Now](https://img.shields.io/badge/Purchase-NexusPay-success?style=for-the-badge)](https://nexuslabs.com.co/products/nexuspay)
[![Demo](https://img.shields.io/badge/Request-Demo-blue?style=for-the-badge)](https://nexuslabs.com.co/demo)
[![Support](https://img.shields.io/badge/Get-Support-orange?style=for-the-badge)](https://nexuslabs.com.co/support)

**Transform your Moodle payment experience with NexusPay**  
**Transforme su experiencia de pagos en Moodle con NexusPay**

</div>