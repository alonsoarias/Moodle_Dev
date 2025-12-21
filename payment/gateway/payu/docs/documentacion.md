# PayU Latam Payment Gateway para Moodle

> Pasarela de pagos PayU Latam integrada con tu plataforma educativa

**Version:** 1.0.0 | **Moodle:** 4.1+ | **Pais:** Colombia | **Moneda:** COP

---

## Tabla de Contenidos

1. [Introduccion](#introduccion)
2. [Requisitos](#requisitos)
3. [Instalacion](#instalacion)
4. [Configuracion en PayU](#configuracion-en-payu)
5. [Configuracion en Moodle](#configuracion-en-moodle)
6. [Metodos de Pago](#metodos-de-pago)
7. [Pagina de Confirmacion](#pagina-de-confirmacion)
8. [Guia para Administradores](#guia-para-administradores)
9. [Guia para Estudiantes](#guia-para-estudiantes)
10. [Pruebas Sandbox](#pruebas-sandbox)
11. [Seguridad](#seguridad)
12. [Solucion de Problemas](#solucion-de-problemas)
13. [API Reference](#api-reference)

---

## Introduccion

Plugin de pasarela de pago PayU Latam para Moodle, disenado para el mercado colombiano y latinoamericano. Permite monetizar cursos usando WebCheckout de PayU.

### Caracteristicas Principales

| Caracteristica | Descripcion |
|----------------|-------------|
| WebCheckout | Formulario de pago seguro de PayU |
| Multiples Metodos | Tarjetas, PSE, Efectivo, Baloto |
| Firma MD5/SHA256 | Seguridad en transacciones |
| Confirmacion Server | Callback automatico de pagos |
| Notificaciones | Alertas por email de estado |
| Sandbox | Ambiente de pruebas completo |

### Por que PayU Latam?

- **Lider regional** - Presencia en toda Latinoamerica
- **Confiabilidad** - Anos de experiencia en pagos online
- **Multiples canales** - Tarjetas, transferencias, efectivo
- **Integracion robusta** - API y WebCheckout probados
- **Soporte local** - Atencion en espanol

---

## Requisitos

### Requisitos Tecnicos

| Componente | Minimo | Recomendado | Notas |
|------------|--------|-------------|-------|
| Moodle | 4.1 | 4.3+ | Compatible con Payment API |
| PHP | 8.0 | 8.1+ | Extension cURL requerida |
| SSL/HTTPS | Produccion | Siempre activo | Obligatorio para pagos |
| cURL | Habilitada | Habilitada | Comunicacion con API |

### Requisitos de Cuenta PayU

- Cuenta comercial en [payu.com](https://payu.com)
- Documentacion comercial verificada
- Credenciales de API (Merchant ID, Account ID, API Key, API Login)

---

## Instalacion

### Metodo 1: Instalacion Manual

1. **Descarga el plugin** desde el repositorio oficial

2. **Extrae los archivos** en:
   ```
   /tu-sitio-moodle/payment/gateway/payu
   ```

3. **Estructura de archivos:**
   ```
   payment/gateway/payu/
   ├── classes/
   │   ├── gateway.php
   │   ├── payu_helper.php
   │   └── privacy/provider.php
   ├── db/
   │   ├── install.xml
   │   └── messages.php
   ├── docs/
   │   └── documentacion.md
   ├── lang/
   │   ├── en/paygw_payu.php
   │   └── es/paygw_payu.php
   ├── pay.php
   ├── callback.php
   ├── return.php
   └── version.php
   ```

4. **Ejecuta la instalacion:**
   ```
   Administracion del sitio → Notificaciones
   ```

---

## Configuracion en PayU

### Paso 1: Crear Cuenta

1. Registrate en [payu.com](https://payu.com)
2. Completa la verificacion de documentos
3. Activa tu cuenta comercial

### Paso 2: Obtener Credenciales

Accede al panel administrativo de PayU y obtener:

| Credencial | Descripcion | Ubicacion |
|------------|-------------|-----------|
| Merchant ID | ID del comercio | Configuracion → Datos tecnicos |
| Account ID | ID de la cuenta | Configuracion → Datos tecnicos |
| API Key | Llave de API | Configuracion → Datos tecnicos |
| API Login | Login de API | Configuracion → Datos tecnicos |

### URLs de WebCheckout

| Ambiente | URL |
|----------|-----|
| Sandbox | `https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/` |
| Produccion | `https://checkout.payulatam.com/ppp-web-gateway-payu/` |

---

## Configuracion en Moodle

### Paso 1: Crear Cuenta de Pago

1. Ve a: **Administracion del sitio → Plugins → Pasarelas de pago → Gestionar cuentas**
2. Haz clic en "Crear cuenta de pago"
3. Nombre: "Pagos Colombia - PayU"
4. Habilita la pasarela PayU

### Paso 2: Configurar Credenciales

| Campo | Descripcion | Requerido |
|-------|-------------|-----------|
| Ambiente | Sandbox o Produccion | Si |
| Merchant ID | Tu ID de comercio | Si |
| Account ID | Tu ID de cuenta | Si |
| API Key | Tu llave de API | Si |
| API Login | Tu login de API | Si |
| Algoritmo Firma | MD5 o SHA256 | Si |

### Plantillas de Email

| Placeholder | Descripcion | Ejemplo |
|-------------|-------------|---------|
| `{firstname}` | Nombre del usuario | Juan |
| `{fullname}` | Nombre completo | Juan Perez |
| `{amount}` | Monto con moneda | $50,000 COP |
| `{orderid}` | ID de orden | PAYU-123456 |

---

## Metodos de Pago

### Metodos Soportados en Colombia

| Metodo | Tipo | Confirmacion |
|--------|------|--------------|
| Visa | Tarjeta | Inmediata |
| Mastercard | Tarjeta | Inmediata |
| American Express | Tarjeta | Inmediata |
| Diners | Tarjeta | Inmediata |
| PSE | Transferencia | 1-30 minutos |
| Baloto | Efectivo | Hasta 24h |
| Efecty | Efectivo | Hasta 24h |
| Su Red | Efectivo | Hasta 24h |
| Codensa | Tarjeta privada | Inmediata |

---

## Pagina de Confirmacion

### URL de Confirmacion (Callback)

```
https://tu-sitio.com/payment/gateway/payu/callback.php
```

Configura esta URL en tu panel de PayU como "URL de confirmacion".

### URL de Respuesta (Return)

```
https://tu-sitio.com/payment/gateway/payu/return.php
```

Configura esta URL como "URL de respuesta".

---

## Guia para Administradores

### Configurar Pago en un Curso

1. Navega al curso
2. Ve a: **Administracion del curso → Usuarios → Metodos de inscripcion**
3. Agrega "Inscripcion con pago"
4. Configura:
   - **Cuenta de pago:** Selecciona la cuenta con PayU
   - **Costo:** Ingresa el precio en COP
   - **Moneda:** COP

### Estados de Transaccion (state_pol)

| Codigo | Estado | Descripcion | Accion |
|--------|--------|-------------|--------|
| 4 | APPROVED | Transaccion aprobada | Inscribir usuario |
| 5 | EXPIRED | Transaccion expirada | Ninguna |
| 6 | DECLINED | Transaccion rechazada | Informar usuario |
| 7 | PENDING | Esperando confirmacion | Esperar |
| 104 | ERROR | Error en proceso | Revisar logs |

### Tareas Programadas

| Tarea | Frecuencia | Funcion |
|-------|------------|---------|
| Verificar pendientes | 15 minutos | Consulta estado en PayU |
| Limpiar expiradas | Diaria 3AM | Elimina transacciones antiguas |

---

## Guia para Estudiantes

### Como Pagar e Inscribirse

1. Accede al curso que deseas tomar
2. Haz clic en "Pagar con PayU"
3. Seras redirigido a la pagina de PayU
4. Selecciona tu metodo de pago
5. Completa los datos requeridos
6. Confirma el pago
7. Espera la confirmacion

### Tiempos de Confirmacion

- **Tarjeta:** Instantaneo
- **PSE:** 1-30 minutos
- **Efectivo:** Hasta 24 horas despues de pagar en punto fisico

---

## Pruebas Sandbox

### Credenciales de Prueba

Obtener credenciales de sandbox en el panel de PayU.

### Tarjetas de Prueba

| Franquicia | Numero | CVV | Vencimiento | Resultado |
|------------|--------|-----|-------------|-----------|
| Visa | `4111111111111111` | 123 | 12/25 | Aprobada |
| Visa | `4000000000000002` | 123 | 12/25 | Rechazada |
| Mastercard | `5500000000000004` | 123 | 12/25 | Aprobada |
| Amex | `378282246310005` | 1234 | 12/25 | Aprobada |

### Datos Adicionales de Prueba

| Dato | Valor |
|------|-------|
| Nombre | APPROVED / REJECTED |
| Email | test@test.com |
| Telefono | 3001234567 |
| Documento | 123456789 |

---

## Seguridad

### Firma de Transaccion (MD5)

Para envio al WebCheckout:
```
signature = MD5(ApiKey~MerchantId~referenceCode~TX_VALUE~currency)
```

### Firma de Respuesta

Para validar respuesta:
```
signature = MD5(ApiKey~MerchantId~referenceCode~new_value~currency~state_pol)
```

> **Importante:** El `new_value` debe formatearse con reglas especiales (ver API Reference).

### Mejores Practicas

- Usa HTTPS siempre
- No expongas API Key
- Valida firma en callback
- Verifica montos recibidos
- Usa algoritmo SHA256 si es posible

---

## Solucion de Problemas

### El pago no se confirma automaticamente

1. Verifica URL de confirmacion en panel PayU
2. Asegurate que el servidor acepta POST externos
3. Revisa logs de Moodle

### Error de firma invalida

1. Verifica API Key sin espacios
2. Confirma algoritmo correcto (MD5 o SHA256)
3. Verifica formato de valor (regla del segundo decimal)

### Usuario no se inscribe despues del pago

1. Ejecuta tarea "Verificar transacciones pendientes"
2. Revisa estado en panel PayU
3. Verifica configuracion del curso

---

## API Reference

### Clase payu_helper

```php
use paygw_payu\payu_helper;

// Crear instancia
$helper = new payu_helper($merchantid, $accountid, $apikey, $apilogin, $test_mode);

// Generar firma para envio
$signature = $helper->generate_signature($reference, $amount, $currency);

// Verificar firma de respuesta
$valid = $helper->verify_response_signature($response_data);

// Formatear valor para firma de respuesta
$formatted = $helper->format_value_for_signature(150000.00);

// Entregar orden
$helper->deliver_order($component, $paymentarea, $itemid, $userid, $cost, $currency);
```

### Codigos de Estado (state_pol)

```php
4   => 'APPROVED'   // Aprobada
5   => 'EXPIRED'    // Expirada
6   => 'DECLINED'   // Rechazada
7   => 'PENDING'    // Pendiente
104 => 'ERROR'      // Error
```

### Parametros del WebCheckout

| Parametro | Descripcion |
|-----------|-------------|
| merchantId | ID del comercio |
| accountId | ID de la cuenta |
| referenceCode | Referencia unica |
| amount | Monto de la transaccion |
| currency | Moneda (COP) |
| signature | Firma MD5/SHA256 |
| description | Descripcion del pago |
| buyerEmail | Email del comprador |
| responseUrl | URL de respuesta |
| confirmationUrl | URL de confirmacion |

---

## Soporte

- **Plugin:** soporte@ingeweb.co
- **Web:** [ingeweb.co](https://ingeweb.co)
- **PayU:** [developers.payulatam.com](https://developers.payulatam.com)

---

*Plugin paygw_payu v1.0.0 | © 2025 ingeweb.co | Licencia GNU GPL v3*
