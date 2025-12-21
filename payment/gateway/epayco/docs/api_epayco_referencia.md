# ePayco API - Referencia Tecnica

> Documentacion tecnica de la API de ePayco para integracion con Moodle

**API Version:** Checkout.js v1 | **Pais:** Colombia | **Monedas:** COP, USD

---

## Tabla de Contenidos

1. [Endpoints](#endpoints)
2. [Autenticacion](#autenticacion)
3. [Checkout.js](#checkoutjs)
4. [Estados de Transaccion](#estados-de-transaccion)
5. [Firma de Seguridad](#firma-de-seguridad)
6. [Confirmacion vs Respuesta](#confirmacion-vs-respuesta)
7. [Parametros de Respuesta](#parametros-de-respuesta)
8. [Consulta de Transacciones](#consulta-de-transacciones)
9. [Codigos de Error](#codigos-de-error)
10. [Datos de Prueba](#datos-de-prueba)

---

## Endpoints

### URLs Base

| Ambiente | URL Checkout.js | Prefijo |
|----------|-----------------|---------|
| Sandbox | `https://checkout.epayco.co/checkout.js` | test |
| Produccion | `https://checkout.epayco.co/checkout.js` | prod |

### API REST

| Ambiente | URL Base |
|----------|----------|
| Sandbox | `https://secure.epayco.co/validation/v1/reference/` |
| Produccion | `https://secure.epayco.co/validation/v1/reference/` |

---

## Autenticacion

### Credenciales

| Credencial | Descripcion | Uso |
|------------|-------------|-----|
| P_CUST_ID_CLIENTE | ID del comercio | Todas las operaciones |
| P_KEY | Llave secreta | Firma de transacciones |
| PUBLIC_KEY | Llave publica | Checkout.js |
| PRIVATE_KEY | Llave privada | Consultas API |

### Configuracion de Ambiente

```javascript
// Sandbox (pruebas)
test: true

// Produccion
test: false
```

---

## Checkout.js

### Integracion Basica

```html
<script src="https://checkout.epayco.co/checkout.js"></script>

<script>
var handler = ePayco.checkout.configure({
    key: 'PUBLIC_KEY',
    test: true  // false para produccion
});

var data = {
    // Obligatorios
    name: "Curso de Moodle",
    description: "Inscripcion al curso",
    invoice: "EPY-123456",
    currency: "cop",
    amount: "50000",
    tax_base: "0",
    tax: "0",
    country: "CO",
    lang: "es",

    // Externos
    external: "true",

    // URLs de respuesta
    response: "https://moodle.com/payment/gateway/epayco/response.php",
    confirmation: "https://moodle.com/payment/gateway/epayco/confirmation.php",

    // Datos del cliente (opcionales)
    name_billing: "Juan Perez",
    address_billing: "Calle 123",
    type_doc_billing: "CC",
    mobilephone_billing: "3001234567",
    number_doc_billing: "1234567890",
    email_billing: "juan@email.com",

    // Extras
    extra1: "component",
    extra2: "paymentarea",
    extra3: "itemid"
};

handler.open(data);
</script>
```

### Parametros del Checkout

| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| name | string | Si | Nombre del producto/curso |
| description | string | Si | Descripcion del producto |
| invoice | string | Si | Referencia unica de factura |
| currency | string | Si | Moneda (cop, usd) |
| amount | string | Si | Monto total |
| tax_base | string | Si | Base gravable (0 si no aplica) |
| tax | string | Si | Valor del impuesto (0 si no aplica) |
| country | string | Si | Codigo de pais (CO) |
| lang | string | No | Idioma (es, en) |
| external | string | Si | "true" para modo externo |
| response | string | Si | URL de retorno del usuario |
| confirmation | string | Si | URL de confirmacion server-to-server |
| extra1-10 | string | No | Datos adicionales |

### Datos del Cliente

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| name_billing | string | Nombre completo |
| address_billing | string | Direccion |
| type_doc_billing | string | Tipo documento (CC, NIT, CE) |
| mobilephone_billing | string | Telefono movil |
| number_doc_billing | string | Numero de documento |
| email_billing | string | Email |

---

## Estados de Transaccion

### Codigos de Respuesta (x_cod_response)

| Codigo | Estado | Descripcion | Final |
|--------|--------|-------------|-------|
| 1 | Aceptada | Transaccion aprobada | Si |
| 2 | Rechazada | Transaccion rechazada | Si |
| 3 | Pendiente | Esperando confirmacion | No |
| 4 | Fallida | Error en el proceso | Si |
| 6 | Reversada | Transaccion revertida | Si |
| 7 | Retenida | Retenida para revision | No |
| 9 | Expirada | Tiempo de pago agotado | Si |
| 10 | Abandonada | Usuario abandono el checkout | Si |
| 11 | Cancelada | Cancelada por usuario | Si |
| 12 | Antifraude | Rechazada por sistema antifraude | Si |

### Flujo de Estados

```
PENDIENTE (3) ─────┬────> ACEPTADA (1) ────> (entrega)
                   │
                   ├────> RECHAZADA (2)
                   │
                   ├────> FALLIDA (4)
                   │
                   ├────> EXPIRADA (9)
                   │
                   └────> CANCELADA (11)

ACEPTADA (1) ──────────> REVERSADA (6)
```

---

## Firma de Seguridad

### Generacion de Firma (x_signature)

La firma se genera con SHA-256 usando el separador `^`:

```
signature = SHA256(p_cust_id_cliente ^ x_ref_payco ^ x_transaction_id ^ x_amount ^ x_currency_code ^ p_key)
```

### Ejemplo PHP

```php
public function generate_signature(array $data): string {
    $signaturestring = sprintf(
        '%s^%s^%s^%s^%s^%s',
        $this->p_cust_id_cliente,
        $data['x_ref_payco'],
        $data['x_transaction_id'],
        $data['x_amount'],
        $data['x_currency_code'],
        $this->p_key
    );
    return hash('sha256', $signaturestring);
}
```

### Verificacion de Firma

```php
public function verify_signature(array $data): bool {
    $receivedsignature = $data['x_signature'] ?? '';
    $calculatedsignature = $this->generate_signature($data);
    return hash_equals($calculatedsignature, $receivedsignature);
}
```

---

## Confirmacion vs Respuesta

### URL de Confirmacion (confirmation.php)

- **Tipo:** Server-to-Server (POST)
- **Momento:** Cuando la transaccion cambia de estado
- **Uso:** Procesar pagos asincronos (PSE, efectivo, etc.)
- **Confiabilidad:** Alta (no depende del navegador)

```php
// confirmation.php
$ref_payco = $_POST['x_ref_payco'] ?? $_GET['ref_payco'];
$x_cod_response = $_POST['x_cod_response'];
$x_signature = $_POST['x_signature'];

// Verificar firma
if (!$helper->verify_signature($_POST)) {
    http_response_code(400);
    exit('Invalid signature');
}

// Procesar segun estado
if ($x_cod_response == 1) {
    $helper->deliver_order(...);
}
```

### URL de Respuesta (response.php)

- **Tipo:** Redireccion del navegador (GET)
- **Momento:** Cuando el usuario termina en el checkout
- **Uso:** Mostrar resultado al usuario
- **Confiabilidad:** Baja (usuario puede cerrar navegador)

```php
// response.php
$ref_payco = $_GET['ref_payco'];

// Consultar estado real via API
$transaction = $helper->get_transaction_status($ref_payco);

// Mostrar resultado al usuario
if ($transaction['x_cod_response'] == 1) {
    // Mostrar exito
} else {
    // Mostrar error o pendiente
}
```

---

## Parametros de Respuesta

### Parametros POST de Confirmacion

| Parametro | Tipo | Descripcion |
|-----------|------|-------------|
| x_ref_payco | string | Referencia unica de ePayco |
| x_id_invoice | string | ID de factura (tu referencia) |
| x_amount | string | Monto de la transaccion |
| x_amount_country | string | Monto en moneda local |
| x_amount_ok | string | Monto neto |
| x_amount_base | string | Base gravable |
| x_tax | string | Impuesto |
| x_currency_code | string | Codigo de moneda |
| x_bank_name | string | Nombre del banco (PSE) |
| x_cardnumber | string | Ultimos 4 digitos |
| x_quotas | string | Numero de cuotas |
| x_response | string | Mensaje de respuesta |
| x_response_reason_text | string | Razon detallada |
| x_cod_response | string | Codigo de respuesta (1-12) |
| x_cod_transaction_state | string | Estado de transaccion |
| x_transaction_id | string | ID de transaccion |
| x_transaction_date | string | Fecha de transaccion |
| x_franchise | string | Franquicia (VISA, MC, etc.) |
| x_signature | string | Firma de validacion |
| x_customer_email | string | Email del cliente |
| x_customer_name | string | Nombre del cliente |
| x_customer_doctype | string | Tipo de documento |
| x_customer_document | string | Numero de documento |
| x_extra1-10 | string | Datos adicionales enviados |

---

## Consulta de Transacciones

### Consulta por Referencia

```
GET https://secure.epayco.co/validation/v1/reference/{ref_payco}
Authorization: Bearer {private_key}
```

### Ejemplo de Respuesta

```json
{
  "success": true,
  "title_response": "Consulta exitosa",
  "text_response": "Transaccion encontrada",
  "last_action": "2025-01-15 10:30:00",
  "data": {
    "x_ref_payco": "123456789",
    "x_id_invoice": "EPY-123456",
    "x_amount": "50000.00",
    "x_currency_code": "COP",
    "x_cod_response": 1,
    "x_response": "Aceptada",
    "x_transaction_id": "abc123def456",
    "x_franchise": "VISA",
    "x_customer_email": "cliente@email.com",
    "x_extra1": "component",
    "x_extra2": "paymentarea",
    "x_extra3": "itemid"
  }
}
```

### Implementacion PHP

```php
public function get_transaction_status(string $ref_payco): ?array {
    $url = "https://secure.epayco.co/validation/v1/reference/{$ref_payco}";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $this->private_key
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['success'] ? $data['data'] : null;
}
```

---

## Codigos de Error

### Errores de Tarjeta

| Codigo | Mensaje | Descripcion |
|--------|---------|-------------|
| 01 | Tarjeta invalida | Numero de tarjeta incorrecto |
| 04 | Tarjeta bloqueada | Tarjeta reportada |
| 05 | Fondos insuficientes | Sin saldo disponible |
| 14 | Tarjeta no valida | Tarjeta no reconocida |
| 43 | Tarjeta robada | Tarjeta reportada como robada |
| 51 | Fondos insuficientes | Cupo excedido |
| 54 | Tarjeta expirada | Fecha de vencimiento pasada |
| 57 | Transaccion no permitida | Restriccion en la tarjeta |
| 82 | CVV invalido | Codigo de seguridad incorrecto |

### Errores de PSE

| Codigo | Mensaje | Descripcion |
|--------|---------|-------------|
| 9994 | Pendiente | Esperando confirmacion del banco |
| 9995 | Rechazada | Banco rechazo la transaccion |
| 9996 | Error banco | Error de comunicacion |
| 9997 | Procesando | En proceso de validacion |

---

## Datos de Prueba

### Tarjetas de Prueba

| Franquicia | Numero | CVV | Vencimiento | Resultado |
|------------|--------|-----|-------------|-----------|
| Visa | `4575623182290326` | 123 | 12/25 | Aprobada |
| Visa | `4151611527583283` | 123 | 12/25 | Rechazada |
| Mastercard | `5170394490379427` | 123 | 12/25 | Aprobada |
| Amex | `373118856457642` | 1234 | 12/25 | Pendiente |
| Diners | `36032429319768` | 123 | 12/25 | Aprobada |

### PSE de Prueba

| Banco | Resultado |
|-------|-----------|
| Banco de prueba | Simula flujo completo |

---

## Implementacion en Moodle

### Flujo de Pago

```
1. Usuario selecciona curso
         ↓
2. pay.php configura Checkout.js
         ↓
3. Usuario completa pago en widget
         ↓
4a. confirmation.php recibe POST (asincrono)
    → Verifica firma
    → deliver_order() si x_cod_response == 1
         ↓
4b. response.php (redireccion usuario)
    → Consulta estado via API
    → Muestra resultado
         ↓
5. Usuario inscrito en curso
```

### Archivos Clave

| Archivo | Funcion |
|---------|---------|
| `pay.php` | Configura Checkout.js con datos del pago |
| `confirmation.php` | Recibe confirmacion server-to-server |
| `response.php` | Procesa retorno del usuario |
| `classes/epayco_helper.php` | Helper para API y verificacion |
| `classes/gateway.php` | Configuracion de la pasarela |

---

## Referencias

- [Documentacion Oficial ePayco](https://docs.epayco.com)
- [Panel de Comercio](https://dashboard.epayco.com)
- [Checkout.js](https://docs.epayco.com/docs/checkout)

---

*Documentacion tecnica paygw_epayco v1.0.0 | © 2025 ingeweb.co*
