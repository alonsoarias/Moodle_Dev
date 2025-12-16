# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere al [Versionamiento Semántico](https://semver.org/lang/es/).

## [1.0.1] - 2025-12-16

### Mejorado
- Documentacion HTML completa con navegacion horizontal sticky
- Logo de ingeweb.co integrado en header y footer
- Bandera de Colombia como SVG en lugar de emoji para mejor compatibilidad
- Seccion de contacto actualizada con informacion de ingeweb.co
- Plantillas de email configurables con placeholders documentados

### Corregido
- Visibilidad del texto en footer (contraste de colores)
- Estilos CSS para mejor legibilidad en alertas

---

## [1.0.0] - 2025-12-16

### Agregado

#### Métodos de Pago
- Soporte completo para **Tarjetas de Crédito/Débito** (Visa, Mastercard, American Express)
- Integración con **Nequi** (billetera móvil)
- Soporte para **PSE** (transferencias bancarias)
- Integración con **Bancolombia Transfer** (transferencia directa)
- Soporte para **Bancolombia Collect** (pago en corresponsales)
- Integración con **Daviplata** (billetera Davivienda)
- Soporte para **Puntos Colombia** (programa de fidelidad)

#### Funcionalidades Core
- Widget de checkout integrado de Wompi
- Soporte para ambientes **Sandbox** y **Producción**
- **Credenciales de prueba automáticas** en Sandbox
- Sistema de **webhooks** para pagos asíncronos
- **Firma de integridad SHA256** para todas las transacciones
- **Verificación de webhooks** con llave de eventos

#### Seguridad
- Validación automática de llaves según ambiente
- Verificación de firma en transacciones
- Cumplimiento **GDPR** con provider de privacidad
- Sin almacenamiento de datos de tarjetas

#### Base de Datos
- Tabla `paygw_wompi_transactions` para registro de transacciones
- Índices optimizados para búsquedas por referencia y transactionid

#### Internacionalización
- Soporte multiidioma completo
- Cadenas en **Español** (es)
- Cadenas en **Inglés** (en)

#### Notificaciones
- Sistema de mensajería integrado con Moodle
- Notificaciones de pago exitoso
- Notificaciones de pago fallido
- Notificaciones de pago pendiente

#### Documentación
- README.md completo con guía de instalación y uso
- Documentación HTML interactiva (`docs/guia-instalacion.html`)
- PHPDoc en todas las clases y métodos públicos

### Técnico

#### Arquitectura
- Clase `gateway` que extiende `\core_payment\gateway`
- Clase `wompi_helper` para interacción con API de Wompi
- Provider de privacidad para cumplimiento GDPR
- Sistema de callbacks para pagos inmediatos
- Sistema de webhooks para pagos asíncronos

#### Archivos Incluidos
```
paygw_wompi/
├── classes/
│   ├── gateway.php
│   ├── wompi_helper.php
│   └── privacy/provider.php
├── db/
│   ├── install.xml
│   └── messages.php
├── docs/
│   └── guia-instalacion.html
├── lang/
│   ├── en/paygw_wompi.php
│   └── es/paygw_wompi.php
├── pix/
│   └── icon.svg
├── cancelled.php
├── CHANGELOG.md
├── lib.php
├── pay.php
├── process.php
├── README.md
├── version.php
└── webhook.php
```

#### Compatibilidad
- Moodle 4.1 o superior
- PHP 7.4 o superior
- Requiere extensión cURL
- Requiere HTTPS en producción

---

## Guía de Actualización

### De versiones anteriores a 1.0.0

Esta es la primera versión estable del plugin. No hay pasos de migración requeridos.

### Notas generales de actualización

1. Siempre haz un **backup de tu base de datos** antes de actualizar
2. Prueba la actualización en un ambiente de **staging** primero
3. Revisa los logs después de la actualización
4. Verifica que los webhooks sigan funcionando correctamente

---

## Tipos de Cambios

- `Agregado` para nuevas funcionalidades
- `Cambiado` para cambios en funcionalidades existentes
- `Obsoleto` para funcionalidades que serán removidas próximamente
- `Removido` para funcionalidades removidas
- `Arreglado` para corrección de bugs
- `Seguridad` para vulnerabilidades corregidas

---

## Enlaces

- [Repositorio](https://github.com/ingeweb/moodle-paygw_wompi)
- [Documentación de Wompi](https://docs.wompi.co)
- [Soporte](mailto:soporte@ingeweb.co)

---

**Desarrollado por [ingeweb.co](https://ingeweb.co)**
