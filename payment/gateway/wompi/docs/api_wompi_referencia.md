# Wompi API - Referencia Tecnica

> Documentacion tecnica de la API de Wompi Colombia para integracion con Moodle

**API Version:** v1 | **Pais:** Colombia | **Moneda:** COP

---

## Tabla de Contenidos

1. [Endpoints](#endpoints)
2. [Autenticacion](#autenticacion)
3. [Estados de Transaccion](#estados-de-transaccion)
4. [Firma de Integridad](#firma-de-integridad)
5. [Webhooks](#webhooks)
6. [Metodos de Pago](#metodos-de-pago)
7. [Widget Checkout](#widget-checkout)
8. [Ejemplos de Respuesta](#ejemplos-de-respuesta)
9. [Codigos de Error](#codigos-de-error)
10. [Datos de Prueba](#datos-de-prueba)

---

## Endpoints

### URLs Base

| Ambiente | URL Base | Prefijo Llaves |
|----------|----------|----------------|
| Sandbox | `https://sandbox.wompi.co/v1` | `pub_test_`, `prv_test_` |
| Produccion | `https://production.wompi.co/v1` | `pub_prod_`, `prv_prod_` |

> **Nota:** Cada ambiente es completamente independiente. Las llaves de sandbox NO funcionan en produccion.

### Endpoints Principales

#### GET /merchants/{public_key}

Obtiene informacion del comercio y token de aceptacion.

```
GET https://sandbox.wompi.co/v1/merchants/{public_key}
```

**Respuesta:**
```json
{
  "data": {
    "id": 12345,
    "name": "Mi Comercio",
    "email": "comercio@email.com",
    "contact_name": "Juan Perez",
    "phone_number": "+573001234567",
    "active": true,
    "logo_url": null,
    "legal_name": "Mi Comercio SAS",
    "legal_id_type": "NIT",
    "legal_id": "900123456",
    "public_key": "pub_test_xxxxx",
    "accepted_currencies": ["COP"],
    "fraud_javascript_key": null,
    "fraud_groups": [],
    "accepted_payment_methods": [
      "CARD",
      "NEQUI",
      "PSE",
      "BANCOLOMBIA_TRANSFER",
      "BANCOLOMBIA_COLLECT"
    ],
    "payment_methods": [...],
    "presigned_acceptance": {
      "acceptance_token": "eyJhbGciOiJIUzI1NiJ9...",
      "permalink": "https://wompi.co/wp-content/...",
      "type": "END_USER_POLICY"
    }
  }
}
```

#### GET /transactions/{transaction_id}

Consulta el estado de una transaccion.

```
GET https://sandbox.wompi.co/v1/transactions/{transaction_id}
Authorization: Bearer {private_key}
```

**Respuesta:**
```json
{
  "data": {
    "id": "1234-5678-abcd-efgh",
    "created_at": "2025-01-15T10:30:00.000Z",
    "finalized_at": "2025-01-15T10:30:05.000Z",
    "amount_in_cents": 5000000,
    "reference": "MOODLE-12345",
    "customer_email": "estudiante@email.com",
    "currency": "COP",
    "payment_method_type": "CARD",
    "payment_method": {
      "type": "CARD",
      "extra": {
        "bin": "424242",
        "name": "VISA-4242",
        "brand": "VISA",
        "exp_year": "29",
        "exp_month": "12",
        "last_four": "4242",
        "card_holder": "JUAN PEREZ"
      },
      "installments": 1
    },
    "status": "APPROVED",
    "status_message": null,
    "billing_data": null,
    "shipping_address": null,
    "redirect_url": "https://moodle.com/payment/gateway/wompi/process.php",
    "payment_source_id": null,
    "payment_link_id": null,
    "customer_data": {
      "legal_id": "1234567890",
      "full_name": "Juan Perez",
      "phone_number": "+573001234567",
      "legal_id_type": "CC"
    },
    "merchant": {
      "name": "Mi Moodle",
      "legal_name": "Mi Moodle SAS",
      "contact_name": "Admin",
      "phone_number": "+573001234567",
      "logo_url": null,
      "legal_id_type": "NIT",
      "email": "admin@moodle.com",
      "legal_id": "900123456"
    }
  }
}
```

#### POST /transactions

Crea una nueva transaccion (solo API directa, no widget).

```
POST https://sandbox.wompi.co/v1/transactions
Authorization: Bearer {private_key}
Content-Type: application/json
```

---

## Autenticacion

### Tipos de Llaves

| Llave | Uso | Ejemplo |
|-------|-----|---------|
| Publica | Iniciar widget, consultar comercio | `pub_test_abc123...` |
| Privada | Consultar transacciones, crear pagos API | `prv_test_xyz789...` |
| Integridad | Firmar transacciones (widget) | `test_integrity_...` |
| Eventos | Verificar webhooks | `test_events_...` |

### Headers de Autorizacion

```
Authorization: Bearer {private_key}
```

---

## Estados de Transaccion

| Estado | Codigo | Descripcion | Final |
|--------|--------|-------------|-------|
| `APPROVED` | - | Transaccion aprobada | Si |
| `DECLINED` | - | Transaccion rechazada | Si |
| `PENDING` | - | Esperando confirmacion | No |
| `VOIDED` | - | Transaccion anulada | Si |
| `ERROR` | - | Error en procesamiento | Si |

### Flujo de Estados

```
PENDING ─────┬────> APPROVED ────> (entrega)
             │
             ├────> DECLINED
             │
             └────> ERROR

APPROVED ────────> VOIDED (anulacion)
```

---

## Firma de Integridad

### Generacion de Firma (Widget)

La firma se genera concatenando los valores y aplicando SHA256:

```
signature = SHA256(reference + amount_in_cents + currency + integrity_key)
```

**Ejemplo PHP:**
```php
$reference = "MOODLE-12345";
$amount_in_cents = 5000000; // $50,000 COP
$currency = "COP";
$integrity_key = "test_integrity_xxxxx";

$signature_string = $reference . $amount_in_cents . $currency . $integrity_key;
$signature = hash('sha256', $signature_string);
// Resultado: "a1b2c3d4e5f6..."
```

**Ejemplo en la implementacion Moodle:**
```php
public function generate_integrity_signature(string $reference, int $amountcents, string $currency): string {
    $signaturestring = $reference . $amountcents . $currency . $this->integritykey;
    return hash('sha256', $signaturestring);
}
```

---

## Webhooks

### Estructura del Evento

```json
{
  "event": "transaction.updated",
  "data": {
    "transaction": {
      "id": "1234-5678-abcd-efgh",
      "status": "APPROVED",
      "amount_in_cents": 5000000,
      "reference": "MOODLE-12345",
      "currency": "COP",
      "customer_email": "estudiante@email.com",
      "payment_method_type": "NEQUI",
      "created_at": "2025-01-15T10:30:00.000Z",
      "finalized_at": "2025-01-15T10:35:00.000Z"
    }
  },
  "sent_at": "2025-01-15T10:35:01.000Z",
  "timestamp": 1705315501,
  "signature": {
    "properties": [
      "transaction.id",
      "transaction.status",
      "transaction.amount_in_cents"
    ],
    "checksum": "a1b2c3d4e5f6..."
  },
  "environment": "test"
}
```

### Verificacion de Firma del Webhook

```php
public function verify_webhook_signature(array $eventdata, string $receivedsignature): bool {
    $properties = $eventdata['signature']['properties'];
    $timestamp = $eventdata['timestamp'];

    $signaturestring = '';
    foreach ($properties as $property) {
        $signaturestring .= $this->get_nested_value($eventdata['data'], $property);
    }
    $signaturestring .= $timestamp . $this->eventskey;

    $calculatedsignature = hash('sha256', $signaturestring);

    return hash_equals($calculatedsignature, $receivedsignature);
}
```

### Eventos Disponibles

| Evento | Descripcion |
|--------|-------------|
| `transaction.updated` | Estado de transaccion cambiado |
| `nequi_token.updated` | Token de Nequi actualizado |

---

## Metodos de Pago

### CARD (Tarjetas)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| type | string | `"CARD"` |
| token | string | Token de tarjeta tokenizada |
| installments | int | Numero de cuotas (1-36) |
| customer_email | string | Email del cliente |
| acceptance_token | string | Token de aceptacion de terminos |

### NEQUI

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| type | string | `"NEQUI"` |
| phone_number | string | Celular registrado en Nequi |

### PSE

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| type | string | `"PSE"` |
| user_type | int | 0=Persona, 1=Empresa |
| user_legal_id_type | string | CC, NIT, etc. |
| user_legal_id | string | Numero de documento |
| financial_institution_code | string | Codigo del banco |
| payment_description | string | Descripcion del pago |

### BANCOLOMBIA_TRANSFER

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| type | string | `"BANCOLOMBIA_TRANSFER"` |
| user_type | string | `"PERSON"` o `"COMPANY"` |
| user_legal_id_type | string | CC, NIT, etc. |
| user_legal_id | string | Numero de documento |
| payment_description | string | Descripcion del pago |
| sandbox_status | string | Solo sandbox: `"APPROVED"`, `"DECLINED"`, `"PENDING"` |

---

## Widget Checkout

### Inicializacion

```html
<script src="https://checkout.wompi.co/widget.js"></script>

<script>
var checkout = new WidgetCheckout({
    currency: 'COP',
    amountInCents: 5000000,
    reference: 'MOODLE-12345',
    publicKey: 'pub_test_xxxxx',
    signature: {
        integrity: 'a1b2c3d4e5f6...'
    },
    redirectUrl: 'https://moodle.com/payment/gateway/wompi/process.php',
    customerData: {
        email: 'estudiante@email.com',
        fullName: 'Juan Perez',
        phoneNumber: '+573001234567',
        phoneNumberPrefix: '+57',
        legalId: '1234567890',
        legalIdType: 'CC'
    }
});

checkout.open(function(result) {
    var transaction = result.transaction;
    console.log('Transaction ID:', transaction.id);
    console.log('Status:', transaction.status);
});
</script>
```

### Parametros del Widget

| Parametro | Tipo | Requerido | Descripcion |
|-----------|------|-----------|-------------|
| currency | string | Si | Moneda (COP) |
| amountInCents | int | Si | Monto en centavos |
| reference | string | Si | Referencia unica |
| publicKey | string | Si | Llave publica |
| signature.integrity | string | Si | Firma de integridad |
| redirectUrl | string | Si | URL de retorno |
| customerData | object | No | Datos del cliente |

---

## Ejemplos de Respuesta

### Transaccion Aprobada

```json
{
  "data": {
    "id": "1234-5678-abcd-efgh",
    "status": "APPROVED",
    "status_message": null,
    "amount_in_cents": 5000000,
    "reference": "MOODLE-12345",
    "currency": "COP",
    "payment_method_type": "CARD",
    "finalized_at": "2025-01-15T10:30:05.000Z"
  }
}
```

### Transaccion Rechazada

```json
{
  "data": {
    "id": "1234-5678-abcd-efgh",
    "status": "DECLINED",
    "status_message": "Fondos insuficientes",
    "amount_in_cents": 5000000,
    "reference": "MOODLE-12345"
  }
}
```

### Transaccion Pendiente (PSE/Nequi)

```json
{
  "data": {
    "id": "1234-5678-abcd-efgh",
    "status": "PENDING",
    "status_message": null,
    "amount_in_cents": 5000000,
    "reference": "MOODLE-12345",
    "payment_method_type": "NEQUI"
  }
}
```

---

## Codigos de Error

| Codigo | Mensaje | Descripcion |
|--------|---------|-------------|
| `INVALID_CARD` | Tarjeta invalida | Numero de tarjeta incorrecto |
| `INSUFFICIENT_FUNDS` | Fondos insuficientes | No hay saldo suficiente |
| `STOLEN_CARD` | Tarjeta robada | Tarjeta reportada como robada |
| `RESTRICTED_CARD` | Tarjeta restringida | Tarjeta bloqueada |
| `CONTACT_ENTITY` | Contactar entidad | Requiere contactar al banco |
| `EXPIRED_CARD` | Tarjeta expirada | Fecha de vencimiento pasada |
| `INVALID_CVV` | CVV invalido | Codigo de seguridad incorrecto |
| `INVALID_EXPIRY` | Vencimiento invalido | Fecha de vencimiento incorrecta |
| `TIMEOUT` | Tiempo agotado | Tiempo de respuesta excedido |
| `ANTIFRAUD_REJECTED` | Rechazado antifraude | Transaccion sospechosa |

---

## Datos de Prueba

### Tarjetas de Prueba

| Franquicia | Numero | CVV | Vencimiento | Resultado |
|------------|--------|-----|-------------|-----------|
| Visa | `4242424242424242` | 123 | 12/29 | APPROVED |
| Visa | `4111111111111111` | 123 | 12/29 | DECLINED |
| Visa | `4012888888881881` | 123 | 12/29 | PENDING |
| Mastercard | `5555555555554444` | 123 | 12/29 | APPROVED |
| Amex | `378282246310005` | 1234 | 12/29 | APPROVED |

### Nequi de Prueba

| Numero Celular | Resultado |
|----------------|-----------|
| `3991111111` | APPROVED |
| `3992222222` | DECLINED |
| `3993333333` | PENDING |

### PSE de Prueba

En sandbox, cualquier banco simula el flujo completo.

---

## Implementacion en Moodle

### Flujo de Pago

```
1. Usuario selecciona curso
         ↓
2. pay.php genera referencia y firma
         ↓
3. Widget Wompi se abre
         ↓
4. Usuario completa pago
         ↓
5a. Redireccion a process.php (pago inmediato)
    → Verifica estado con API
    → deliver_order() si APPROVED
         ↓
5b. Webhook a webhook.php (pago asincrono)
    → Verifica firma del evento
    → deliver_order() si APPROVED
         ↓
6. Usuario inscrito en curso
```

### Archivos Clave

| Archivo | Funcion |
|---------|---------|
| `pay.php` | Inicia el proceso de pago, genera referencia y firma |
| `process.php` | Procesa retorno del widget, verifica con API |
| `webhook.php` | Recibe eventos asincronos de Wompi |
| `classes/wompi_helper.php` | Helper para comunicacion con API |
| `classes/gateway.php` | Configuracion y metodos de la pasarela |

---

## Referencias

- [Documentacion Oficial Wompi](https://docs.wompi.co)
- [Dashboard Comercios](https://comercios.wompi.co)
- [Widget Checkout](https://docs.wompi.co/docs/widget-checkout)

---

*Documentacion tecnica paygw_wompi v1.0.0 | © 2025 ingeweb.co*
