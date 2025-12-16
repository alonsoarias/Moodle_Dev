# PayU Colombia Payment Gateway for Moodle

[![Moodle Plugin](https://img.shields.io/badge/Moodle-4.1+-orange.svg)](https://moodle.org)
[![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![PayU](https://img.shields.io/badge/PayU-Colombia-brightgreen.svg)](https://colombia.payu.com)

Plugin de pasarela de pago PayU para Moodle, diseñado específicamente para el mercado colombiano.

## Descripción

Este plugin permite a las instituciones educativas colombianas aceptar pagos a través de PayU, la pasarela de pago líder en América Latina. Soporta múltiples métodos de pago locales incluyendo:

- Tarjetas de crédito (Visa, Mastercard, American Express, Diners)
- Tarjetas débito
- PSE (Pagos Seguros en Línea)
- Efectivo (Baloto, Efecty, Su Red)
- Transferencias bancarias

## Requisitos

- Moodle 4.1 o superior
- PHP 7.4 o superior
- Cuenta de comercio en PayU Colombia
- Certificado SSL (HTTPS) en producción

## Instalación

### Método 1: Desde el repositorio

1. Descargue el plugin desde el repositorio
2. Extraiga el contenido en `/payment/gateway/payu/`
3. Visite la página de administración de Moodle para completar la instalación
4. Configure el plugin con sus credenciales de PayU

### Método 2: Via Git

```bash
cd /path/to/moodle/payment/gateway
git clone https://github.com/ingeweb/paygw_payu.git payu
```

## Configuración

### 1. Obtener credenciales de PayU

1. Ingrese a su cuenta de PayU en [https://merchants.payulatam.com](https://merchants.payulatam.com)
2. Navegue a **Configuración** > **Configuración técnica**
3. Copie los siguientes datos:
   - **Merchant ID**: Identificador de su comercio
   - **Account ID**: ID de cuenta para Colombia
   - **API Key**: Llave secreta para firmas
   - **API Login**: Usuario de API

### 2. Configurar URLs en PayU

Configure las siguientes URLs en el panel de PayU:

**URL de Confirmación:**
```
https://su-sitio.com/payment/gateway/payu/callback.php
```

**URL de Respuesta:**
```
https://su-sitio.com/payment/gateway/payu/return.php
```

### 3. Configurar en Moodle

1. Vaya a **Administración del sitio** > **Plugins** > **Pasarelas de pago** > **Gestionar pasarelas de pago**
2. Habilite "PayU Colombia"
3. Configure una cuenta de pago con las credenciales de PayU

## Ambientes

### Sandbox (Pruebas)

El plugin incluye credenciales de prueba automáticas para el ambiente sandbox:

- **Merchant ID**: 508029
- **Account ID**: 512321
- **API Key**: 4Vj8eK4rloUd272L48hsrarnUA

**Tarjetas de prueba:**

| Tipo | Número | CVV | Fecha |
|------|--------|-----|-------|
| Visa | 4111111111111111 | 123 | 12/25 |
| Mastercard | 5424000000000015 | 123 | 12/25 |
| AMEX | 370000000000002 | 1234 | 12/25 |

### Producción

Para pagos reales, seleccione "Producción" y configure sus credenciales reales de PayU.

## Flujo de Pago

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Moodle    │────>│    PayU     │────>│   Banco/    │
│  (pay.php)  │     │  Checkout   │     │   Tarjeta   │
└─────────────┘     └─────────────┘     └─────────────┘
                           │
                           ▼
┌─────────────┐     ┌─────────────┐
│  callback   │<────│  Respuesta  │
│   .php      │     │    PayU     │
└─────────────┘     └─────────────┘
       │
       ▼
┌─────────────┐
│  Entrega    │
│   Orden     │
└─────────────┘
```

## Validación de Firmas

El plugin implementa la validación de firmas MD5 según la documentación oficial de PayU:

**Firma de envío:**
```
MD5(apiKey~merchantId~referenceCode~amount~currency)
```

**Firma de confirmación:**
```
MD5(apiKey~merchantId~referenceCode~value~currency~state_pol)
```

## Estados de Transacción

| Código | Estado | Descripción |
|--------|--------|-------------|
| 4 | APPROVED | Pago aprobado |
| 5 | EXPIRED | Pago expirado |
| 6 | DECLINED | Pago rechazado |
| 7 | PENDING | Pago pendiente |
| 104 | ERROR | Error en el pago |

## Solución de Problemas

### El pago no se procesa

1. Verifique que las URLs de callback estén correctamente configuradas en PayU
2. Asegúrese de que su servidor permita conexiones desde PayU
3. Revise los logs de Moodle para errores

### Firma inválida

1. Verifique que el API Key sea correcto
2. Asegúrese de usar el ambiente correcto (sandbox/producción)
3. Revise el formato de los valores (decimales)

### El usuario no es inscrito

1. Verifique que el callback de PayU esté llegando correctamente
2. Revise la tabla `paygw_payu_transactions` para el estado de la transacción
3. Compruebe los logs de Moodle

## API Reference

### payu_helper

Clase principal para interactuar con PayU:

```php
use paygw_payu\payu_helper;

// Crear instancia desde configuración
$helper = payu_helper::from_config($config);

// Generar firma
$signature = $helper->generate_signature($reference, $amount, $currency);

// Verificar firma de respuesta
$valid = $helper->verify_signature($sign, $reference, $value, $currency, $state);

// Verificar estado
$approved = $helper->is_approved($state_pol);
```

## Privacidad

Este plugin cumple con GDPR y la Ley 1581 de Colombia sobre protección de datos personales:

- Solo se envían a PayU los datos mínimos necesarios (email, nombre)
- Los datos de transacción se almacenan localmente para auditoría
- Los usuarios pueden solicitar la eliminación de sus datos

## Soporte

- **Documentación**: Ver `docs/documentacion.html`
- **Issues**: [GitHub Issues](https://github.com/ingeweb/paygw_payu/issues)
- **Email**: soporte@ingeweb.co

## Créditos

Desarrollado por [ingeweb.co](https://ingeweb.co)

- **Autor**: Alonso Arias
- **Email**: soporte@ingeweb.co
- **Sitio web**: https://ingeweb.co

## Licencia

Este plugin está licenciado bajo la GNU General Public License v3.0.
Vea el archivo [LICENSE](LICENSE) para más detalles.

---

**PayU** es una marca registrada de PayU GPO S.A.
