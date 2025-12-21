# PayU Latam API - Referencia Tecnica

> Documentacion tecnica de la API de PayU Latam para integracion con Moodle

**API Version:** WebCheckout | **Pais:** Colombia | **Moneda:** COP

---

## Tabla de Contenidos

1. [Endpoints](#endpoints)
2. [Credenciales](#credenciales)
3. [WebCheckout](#webcheckout)
4. [Firma de Transaccion](#firma-de-transaccion)
5. [Estados de Transaccion](#estados-de-transaccion)
6. [Pagina de Confirmacion](#pagina-de-confirmacion)
7. [Pagina de Respuesta](#pagina-de-respuesta)
8. [Consulta de Transacciones](#consulta-de-transacciones)
9. [Metodos de Pago](#metodos-de-pago)
10. [Datos de Prueba](#datos-de-prueba)

---

## Endpoints

### URLs de WebCheckout

| Ambiente | URL |
|----------|-----|
| Sandbox | `https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/` |
| Produccion | `https://checkout.payulatam.com/ppp-web-gateway-payu/` |

### URLs de API (Queries/Reports)

| Ambiente | URL |
|----------|-----|
| Sandbox | `https://sandbox.api.payulatam.com/reports-api/4.0/service.cgi` |
| Produccion | `https://api.payulatam.com/reports-api/4.0/service.cgi` |

### URLs de API (Payments)

| Ambiente | URL |
|----------|-----|
| Sandbox | `https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi` |
| Produccion | `https://api.payulatam.com/payments-api/4.0/service.cgi` |

---

## Credenciales

### Datos Requeridos

| Credencial | Descripcion | Uso |
|------------|-------------|-----|
| Merchant ID | ID del comercio | Todas las operaciones |
| Account ID | ID de la cuenta (por pais) | WebCheckout y API |
| API Key | Llave secreta | Firma de transacciones |
| API Login | Usuario de API | Consultas API |

### Ubicacion en Panel PayU

1. Accede a [merchants.payulatam.com](https://merchants.payulatam.com)
2. Ve a **Configuracion → Datos tecnicos**
3. Copia las credenciales

---

## WebCheckout

### Formulario de Pago

```html
<form method="POST" action="https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/">
    <!-- Credenciales -->
    <input type="hidden" name="merchantId" value="508029">
    <input type="hidden" name="accountId" value="512321">

    <!-- Datos de transaccion -->
    <input type="hidden" name="referenceCode" value="MOODLE-12345">
    <input type="hidden" name="amount" value="50000">
    <input type="hidden" name="currency" value="COP">
    <input type="hidden" name="signature" value="a1b2c3d4e5f6...">

    <!-- Descripcion -->
    <input type="hidden" name="description" value="Inscripcion curso Moodle">

    <!-- URLs -->
    <input type="hidden" name="responseUrl" value="https://moodle.com/payment/gateway/payu/return.php">
    <input type="hidden" name="confirmationUrl" value="https://moodle.com/payment/gateway/payu/callback.php">

    <!-- Datos del comprador -->
    <input type="hidden" name="buyerEmail" value="estudiante@email.com">
    <input type="hidden" name="buyerFullName" value="Juan Perez">
    <input type="hidden" name="telephone" value="3001234567">

    <!-- Configuracion -->
    <input type="hidden" name="test" value="1">
    <input type="hidden" name="lng" value="es">

    <button type="submit">Pagar con PayU</button>
</form>
```

### Parametros del Formulario

| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| merchantId | string | Si | ID del comercio |
| accountId | string | Si | ID de la cuenta |
| referenceCode | string | Si | Referencia unica (max 255) |
| amount | decimal | Si | Monto de la transaccion |
| currency | string | Si | Codigo de moneda (COP) |
| signature | string | Si | Firma MD5 o SHA256 |
| description | string | Si | Descripcion del pago |
| responseUrl | string | Si | URL de respuesta (GET) |
| confirmationUrl | string | Si | URL de confirmacion (POST) |
| buyerEmail | string | Si | Email del comprador |
| buyerFullName | string | No | Nombre completo |
| telephone | string | No | Telefono |
| test | int | No | 1=Sandbox, 0=Produccion |
| lng | string | No | Idioma (es, en, pt) |
| tax | decimal | No | Valor del IVA |
| taxReturnBase | decimal | No | Base de devolucion IVA |
| extra1-3 | string | No | Datos adicionales |

---

## Firma de Transaccion

### Algoritmo MD5 (Por defecto)

```
signature = MD5(ApiKey~MerchantId~referenceCode~amount~currency)
```

### Algoritmo SHA256

```
signature = SHA256(ApiKey~MerchantId~referenceCode~amount~currency)
```

### Ejemplo PHP

```php
public function generate_signature(string $reference, float $amount, string $currency): string {
    $signaturestring = sprintf(
        '%s~%s~%s~%s~%s',
        $this->apikey,
        $this->merchantid,
        $reference,
        $amount,
        $currency
    );

    // MD5
    return md5($signaturestring);

    // O SHA256
    // return hash('sha256', $signaturestring);
}
```

### Ejemplo de Generacion

```
ApiKey: 4Vj8eK4rloUd272L48hsrarnUA
MerchantId: 508029
referenceCode: MOODLE-12345
amount: 50000
currency: COP

Cadena: 4Vj8eK4rloUd272L48hsrarnUA~508029~MOODLE-12345~50000~COP
MD5: 7ee7cf808ce6a39b17481c54f2c57acc
```

---

## Estados de Transaccion

### Codigos de Estado (state_pol)

| Codigo | Estado | Descripcion | Final |
|--------|--------|-------------|-------|
| 4 | APPROVED | Transaccion aprobada | Si |
| 5 | EXPIRED | Transaccion expirada | Si |
| 6 | DECLINED | Transaccion rechazada | Si |
| 7 | PENDING | Pendiente de confirmacion | No |
| 104 | ERROR | Error en el proceso | Si |

### Flujo de Estados

```
PENDING (7) ─────┬────> APPROVED (4) ────> (entrega)
                 │
                 ├────> DECLINED (6)
                 │
                 ├────> EXPIRED (5)
                 │
                 └────> ERROR (104)
```

### Codigos de Respuesta (response_code_pol)

| Codigo | Descripcion |
|--------|-------------|
| 1 | Transaccion aprobada |
| 4 | Transaccion rechazada por antifraude |
| 5 | Transaccion rechazada por banco |
| 6 | Fondos insuficientes |
| 7 | Tarjeta invalida |
| 9 | Tarjeta expirada |
| 10 | Tarjeta restringida |
| 12 | Fecha expiracion invalida |
| 13 | Repita transaccion |
| 14 | Transaccion invalida |
| 17 | CVV invalido |
| 22 | Tarjeta no autenticada 3DS |
| 23 | Transaccion pendiente |
| 9994 | Pendiente (PSE/efectivo) |
| 9995 | Pendiente certificacion |
| 9996 | No autorizada |
| 9997 | Procesando |

---

## Pagina de Confirmacion

### URL de Confirmacion

```
POST https://tu-sitio.com/payment/gateway/payu/callback.php
```

### Parametros Recibidos

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| reference_sale | string | Referencia de venta (tu referenceCode) |
| reference_pol | string | Referencia de PayU |
| state_pol | int | Estado de la transaccion (4,5,6,7,104) |
| response_code_pol | int | Codigo de respuesta |
| response_message_pol | string | Mensaje de respuesta |
| merchant_id | int | ID del comercio |
| transaction_id | string | ID de transaccion |
| value | decimal | Monto de la transaccion |
| currency | string | Moneda |
| sign | string | Firma de validacion |
| payment_method | int | Metodo de pago |
| payment_method_name | string | Nombre del metodo |
| buyer_email | string | Email del comprador |
| transaction_date | datetime | Fecha de transaccion |
| extra1-3 | string | Datos adicionales |

### Verificacion de Firma

**IMPORTANTE:** La firma de respuesta tiene una regla especial para el valor:

```php
public function format_value_for_signature(float $value): string {
    $rounded = round($value, 2);
    $decimals = $rounded - floor($rounded);

    // Obtener segundo decimal
    $seconddecimal = (int)(($decimals * 100) % 10);

    // Si el segundo decimal es 0, usar 1 decimal
    if ($seconddecimal == 0) {
        return number_format($rounded, 1, '.', '');
    }

    // Si no, usar 2 decimales
    return number_format($rounded, 2, '.', '');
}
```

### Ejemplos de Formateo

| Valor Original | Valor Formateado |
|----------------|-----------------|
| 150000.00 | 150000.0 |
| 150000.10 | 150000.1 |
| 150000.15 | 150000.15 |
| 36019.50 | 36019.5 |
| 36019.55 | 36019.55 |

### Firma de Respuesta

```
sign = MD5(ApiKey~MerchantId~reference_sale~new_value~currency~state_pol)
```

### Implementacion PHP

```php
public function verify_response_signature(array $data): bool {
    $value = $this->format_value_for_signature((float)$data['value']);

    $signaturestring = sprintf(
        '%s~%s~%s~%s~%s~%s',
        $this->apikey,
        $data['merchant_id'],
        $data['reference_sale'],
        $value,
        $data['currency'],
        $data['state_pol']
    );

    $calculated = md5($signaturestring);
    return hash_equals($calculated, $data['sign']);
}
```

---

## Pagina de Respuesta

### URL de Respuesta

```
GET https://tu-sitio.com/payment/gateway/payu/return.php
```

### Parametros Recibidos (GET)

| Parametro | Descripcion |
|-----------|-------------|
| referenceCode | Referencia de la transaccion |
| TX_VALUE | Monto de la transaccion |
| currency | Moneda |
| transactionState | Estado (4,6,7,104) |
| signature | Firma |
| polResponseCode | Codigo de respuesta |
| message | Mensaje |

### Uso de la Pagina de Respuesta

Esta pagina se usa para mostrar el resultado al usuario. **No** es confiable para procesar el pago - usar siempre la pagina de confirmacion.

---

## Consulta de Transacciones

### API de Reportes

```
POST https://api.payulatam.com/reports-api/4.0/service.cgi
Content-Type: application/json
```

### Request: Consulta por Referencia

```json
{
    "test": false,
    "language": "es",
    "command": "ORDER_DETAIL_BY_REFERENCE_CODE",
    "merchant": {
        "apiLogin": "pRRXKOl8ikMmt9u",
        "apiKey": "4Vj8eK4rloUd272L48hsrarnUA"
    },
    "details": {
        "referenceCode": "MOODLE-12345"
    }
}
```

### Response

```json
{
    "code": "SUCCESS",
    "result": {
        "payload": [
            {
                "id": 12345678,
                "accountId": 512321,
                "status": "CAPTURED",
                "referenceCode": "MOODLE-12345",
                "description": "Inscripcion curso",
                "additionalValues": {
                    "TX_VALUE": {
                        "value": 50000.00,
                        "currency": "COP"
                    }
                },
                "buyer": {
                    "emailAddress": "estudiante@email.com",
                    "fullName": "Juan Perez"
                },
                "transactions": [
                    {
                        "id": "abc123def456",
                        "transactionResponse": {
                            "state": "APPROVED",
                            "responseCode": "APPROVED"
                        },
                        "paymentMethod": "VISA",
                        "paymentCountry": "CO"
                    }
                ]
            }
        ]
    }
}
```

---

## Metodos de Pago

### Colombia (CO)

| Codigo | Metodo | Tipo |
|--------|--------|------|
| 2 | VISA | Tarjeta |
| 3 | VISA_DEBIT | Tarjeta Debito |
| 22 | MASTERCARD | Tarjeta |
| 23 | MASTERCARD_DEBIT | Tarjeta Debito |
| 24 | AMEX | Tarjeta |
| 25 | DINERS | Tarjeta |
| 35 | PSE | Transferencia |
| 36 | BALOTO | Efectivo |
| 39 | EFECTY | Efectivo |
| 40 | SU_RED | Efectivo |
| 42 | CODENSA | Tarjeta Privada |

---

## Datos de Prueba

### Credenciales Sandbox

| Dato | Valor |
|------|-------|
| Merchant ID | 508029 |
| Account ID (Colombia) | 512321 |
| API Key | 4Vj8eK4rloUd272L48hsrarnUA |
| API Login | pRRXKOl8ikMmt9u |

### Tarjetas de Prueba

| Franquicia | Numero | CVV | Vencimiento | Nombre | Resultado |
|------------|--------|-----|-------------|--------|-----------|
| Visa | `4111111111111111` | 123 | 12/25 | APPROVED | Aprobada |
| Visa | `4000000000000002` | 123 | 12/25 | REJECTED | Rechazada |
| Mastercard | `5500000000000004` | 123 | 12/25 | APPROVED | Aprobada |
| Amex | `378282246310005` | 1234 | 12/25 | APPROVED | Aprobada |
| Diners | `36032429319768` | 123 | 12/25 | APPROVED | Aprobada |

### Datos Adicionales

| Campo | Valor |
|-------|-------|
| Email | test@test.com |
| Telefono | 3001234567 |
| Documento | 123456789 |
| Direccion | Calle 123 |
| Ciudad | Bogota |

---

## Implementacion en Moodle

### Flujo de Pago

```
1. Usuario selecciona curso
         ↓
2. pay.php genera formulario con firma
         ↓
3. Usuario redirigido a WebCheckout PayU
         ↓
4. Usuario completa pago
         ↓
5a. callback.php recibe POST (confirmacion)
    → Verifica firma
    → deliver_order() si state_pol == 4
         ↓
5b. return.php (redireccion usuario)
    → Muestra resultado
         ↓
6. Usuario inscrito en curso
```

### Archivos Clave

| Archivo | Funcion |
|---------|---------|
| `pay.php` | Genera formulario con firma |
| `callback.php` | Recibe confirmacion server-to-server |
| `return.php` | Procesa retorno del usuario |
| `classes/payu_helper.php` | Helper para firmas y API |
| `classes/gateway.php` | Configuracion de la pasarela |

---

## Referencias

- [Documentacion Oficial PayU](https://developers.payulatam.com)
- [Panel de Comercios](https://merchants.payulatam.com)
- [Sandbox](https://sandbox.payulatam.com)

---

*Documentacion tecnica paygw_payu v1.0.0 | © 2025 ingeweb.co*
