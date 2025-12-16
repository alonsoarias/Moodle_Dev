# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Versionado Semántico](https://semver.org/lang/es/).

## [1.0.0] - 2025-12-16

### Agregado
- Integración completa con PayU WebCheckout para Colombia
- Soporte para ambiente Sandbox y Producción
- Credenciales de prueba automáticas en Sandbox
- Validación de firmas MD5 según documentación oficial de PayU
- Página de redirección con diseño moderno
- Callback (URL de confirmación) para procesamiento automático de pagos
- Página de respuesta con mensajes de estado
- Soporte para múltiples métodos de pago colombianos:
  - Tarjetas de crédito (Visa, Mastercard, AMEX, Diners)
  - Tarjetas débito
  - PSE
  - Pagos en efectivo (Baloto, Efecty, Su Red)
- Sistema de notificaciones para usuarios
- Registro de transacciones en base de datos
- Cumplimiento con estándares de privacidad GDPR
- Documentación completa en español e inglés
- Documentación HTML con diseño ingeweb.co

### Seguridad
- Validación de firmas en todas las respuestas de PayU
- Protección contra transacciones duplicadas
- Sanitización de todos los parámetros de entrada

## [Unreleased]

### Planeado
- Soporte para tokenización de tarjetas
- Panel de administración con reportes de transacciones
- Integración con API de consultas de PayU
- Soporte para reembolsos automáticos

---

## Cómo actualizar

### De versiones anteriores

Si está actualizando desde una versión anterior del plugin:

1. Haga backup de su base de datos
2. Reemplace los archivos del plugin
3. Visite la página de administración de Moodle
4. Siga las instrucciones de actualización de la base de datos
5. Verifique la configuración del plugin

### Notas de compatibilidad

- **Moodle 4.1+**: Completamente compatible
- **PHP 7.4+**: Requerido
- **MySQL 5.7+ / PostgreSQL 12+**: Recomendado

---

Para reportar problemas o sugerir mejoras, visite:
https://github.com/ingeweb/paygw_payu/issues
