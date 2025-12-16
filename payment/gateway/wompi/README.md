# Wompi Payment Gateway for Moodle (paygw_wompi)

Plugin de pasarela de pago Wompi para Moodle, diseñado para el mercado colombiano.

## Descripción

Este plugin permite a los sitios Moodle aceptar pagos a través de Wompi, la plataforma líder de pagos en Colombia. Soporta múltiples métodos de pago locales incluyendo tarjetas de crédito/débito, PSE, Nequi, Bancolombia, Daviplata y Puntos Colombia.

## Características

- **Múltiples métodos de pago:**
  - Tarjetas de Crédito/Débito (Visa, Mastercard, American Express)
  - Nequi (billetera móvil)
  - PSE (transferencias bancarias)
  - Transferencia Bancolombia
  - Corresponsal Bancolombia (pago en efectivo)
  - Daviplata
  - Puntos Colombia

- **Seguridad:**
  - Firma de integridad SHA256 para todas las transacciones
  - Verificación de webhooks con llave de eventos
  - Validación automática de llaves según ambiente

- **Funcionalidades:**
  - Soporte para ambientes Sandbox (pruebas) y Producción
  - Widget de checkout integrado de Wompi
  - Procesamiento de pagos asíncronos vía webhooks
  - Notificaciones automáticas a usuarios
  - Soporte multiidioma (Español e Inglés)
  - Cumplimiento GDPR

## Requisitos

- Moodle 4.1 o superior
- PHP 7.4 o superior
- Cuenta de comercio en Wompi Colombia
- Certificado SSL (HTTPS) en producción

## Instalación

1. Descarga el plugin y extráelo en `payment/gateway/wompi`
2. Accede a tu sitio Moodle como administrador
3. Navega a **Administración del sitio > Notificaciones** para completar la instalación
4. Configura el plugin en **Administración del sitio > Plugins > Pasarelas de pago > Wompi**

## Configuración

### Obtener credenciales de Wompi

1. Accede al [Dashboard de Wompi](https://comercios.wompi.co)
2. Ve a **Configuración > Llaves de API**
3. Copia las siguientes llaves:
   - **Llave Pública** (pub_test_* o pub_prod_*)
   - **Llave Privada** (prv_test_* o prv_prod_*)
   - **Llave de Integridad**
   - **Llave de Eventos** (opcional, para webhooks)

### Configurar en Moodle

1. Ve a **Administración del sitio > Plugins > Pasarelas de pago > Gestionar cuentas de pago**
2. Crea una nueva cuenta de pago o edita una existente
3. Habilita la pasarela Wompi
4. Ingresa las credenciales:
   - Selecciona el ambiente (Sandbox/Producción)
   - Llave Pública
   - Llave Privada
   - Llave de Integridad
   - Llave de Eventos (opcional)
5. Selecciona los métodos de pago a habilitar
6. Guarda los cambios

### Configurar Webhook (Recomendado)

Para procesar pagos asíncronos (PSE, Nequi, etc.):

1. En el Dashboard de Wompi, ve a **Configuración > Eventos**
2. Agrega la URL del webhook:
   ```
   https://tu-sitio-moodle.com/payment/gateway/wompi/webhook.php
   ```
3. Copia la **Llave de Eventos** y agrégala en la configuración del plugin

## Uso

### Para Administradores

1. Configura un método de inscripción con pago (ej: Inscripción con pago)
2. Asocia la cuenta de pago configurada con Wompi
3. Establece el precio en COP (pesos colombianos)

### Para Estudiantes

1. Accede al curso que requiere pago
2. Haz clic en el botón de pago
3. Selecciona Wompi como método de pago
4. Completa el pago en el widget de Wompi
5. Serás inscrito automáticamente al completar el pago

## Métodos de Pago Soportados

| Método | Tipo | Tiempo de confirmación |
|--------|------|------------------------|
| Tarjeta | Inmediato | Instantáneo |
| Nequi | Asíncrono | 1-5 minutos |
| PSE | Asíncrono | 1-30 minutos |
| Bancolombia Transfer | Asíncrono | 1-30 minutos |
| Bancolombia Collect | Asíncrono | Hasta 24 horas |
| Daviplata | Asíncrono | 1-5 minutos |
| Puntos Colombia | Inmediato | Instantáneo |

## Datos de Prueba (Sandbox)

### Tarjetas de prueba

| Número | Resultado |
|--------|-----------|
| 4242 4242 4242 4242 | Aprobada |
| 4111 1111 1111 1111 | Declinada |

- **Fecha de vencimiento:** Cualquier fecha futura
- **CVV:** Cualquier 3 dígitos
- **Nombre:** Cualquier nombre

### Nequi de prueba
- **Número de celular:** 3991111111 (Aprobado)
- **Número de celular:** 3992222222 (Declinado)

## Solución de Problemas

### El pago no se procesa

1. Verifica que las llaves estén correctas para el ambiente seleccionado
2. Confirma que el webhook está configurado correctamente
3. Revisa los logs de Moodle en **Administración del sitio > Servidor > Logs**

### Error de firma de integridad

1. Verifica que la Llave de Integridad sea correcta
2. Asegúrate de que no haya espacios adicionales en las llaves

### El usuario no se inscribe después del pago

1. Verifica que el webhook esté activo
2. Confirma que la URL del webhook sea accesible desde Internet
3. Revisa que la Llave de Eventos esté configurada

## Soporte

- **Email:** soporte@ingeweb.co
- **Documentación de Wompi:** [docs.wompi.co](https://docs.wompi.co)

## Licencia

Este plugin está licenciado bajo la GNU GPL v3 o posterior.

## Créditos

- **Autor:** Alonso Arias
- **Email:** soporte@ingeweb.co
- **Versión:** 1.0.0
- **Basado en:** paygw_stripe por Alex Morris

## Changelog

### 1.0.0 (2025-12-16)
- Versión inicial
- Soporte para todos los métodos de pago de Wompi Colombia
- Widget de checkout integrado
- Soporte de webhooks para pagos asíncronos
- Soporte multiidioma (ES/EN)
