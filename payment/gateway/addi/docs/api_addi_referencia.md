# Addi API - Referencia Tecnica

> Documentacion tecnica de la API de Addi BNPL para integracion con Moodle

**API Version:** v1 | **Autenticacion:** OAuth2 | **Pais:** Colombia | **Moneda:** COP

---

## Tabla de Contenidos

1. [Endpoints](#endpoints)
2. [Autenticacion OAuth2](#autenticacion-oauth2)
3. [Crear Solicitud de Credito](#crear-solicitud-de-credito)
4. [Estados de Solicitud](#estados-de-solicitud)
5. [Webhooks](#webhooks)
6. [Consultar Estado](#consultar-estado)
7. [Ejemplos de Respuesta](#ejemplos-de-respuesta)
8. [Codigos de Error](#codigos-de-error)
9. [Implementacion en Moodle](#implementacion-en-moodle)

---

## Endpoints

### URLs Base

| Ambiente | URL Base | Uso |
|----------|----------|-----|
| Sandbox | `https://api.addi-staging.com` | Pruebas |
| Produccion | `https://api.addi.com` | Pagos reales |

### Endpoints Principales

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/oauth/token` | POST | Obtener access token |
| `/allies/{ally_slug}/online-applications` | POST | Crear solicitud |
| `/allies/{ally_slug}/online-applications/{id}` | GET | Consultar estado |

---

## Autenticacion OAuth2

### Flujo Client Credentials

Addi usa OAuth2 con grant type `client_credentials`.

### Solicitar Access Token

```
POST /oauth/token
Content-Type: application/x-www-form-urlencoded
```

**Body:**
```
grant_type=client_credentials
client_id={client_id}
client_secret={client_secret}
audience=https://api.addi.com
```

**Respuesta:**
```json
{
  "access_token": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "token_type": "Bearer",
  "expires_in": 86400
}
```

### Usar Access Token

```
Authorization: Bearer {access_token}
```

### Implementacion PHP

```php
public function get_access_token(): string {
    $url = $this->get_base_url() . '/oauth/token';

    $postdata = http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $this->clientid,
        'client_secret' => $this->clientsecret,
        'audience' => 'https://api.addi.com'
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'];
}
```

---

## Crear Solicitud de Credito

### Endpoint

```
POST /allies/{ally_slug}/online-applications
Authorization: Bearer {access_token}
Content-Type: application/json
```

### Request Body

```json
{
  "orderId": "MOODLE-12345",
  "totalAmount": 500000,
  "currency": "COP",
  "shippingAmount": 0,
  "totalTaxesAmount": 0,
  "items": [
    {
      "sku": "COURSE-123",
      "name": "Curso de Programacion",
      "quantity": 1,
      "unitPrice": 500000,
      "category": "EDUCATION"
    }
  ],
  "client": {
    "idType": "CC",
    "idNumber": "1234567890",
    "firstName": "Juan",
    "lastName": "Perez",
    "email": "juan@email.com",
    "cellphone": "+573001234567",
    "cellphoneCountryCode": "+57"
  },
  "billingAddress": {
    "lineOne": "Calle 123 #45-67",
    "city": "Bogota",
    "country": "CO"
  },
  "shippingAddress": {
    "lineOne": "Calle 123 #45-67",
    "city": "Bogota",
    "country": "CO"
  },
  "allyUrlRedirection": {
    "callbackUrl": "https://moodle.com/payment/gateway/addi/webhook.php",
    "redirectionUrl": "https://moodle.com/payment/gateway/addi/response.php"
  },
  "geoLocation": {
    "latitude": 4.6097,
    "longitude": -74.0817
  }
}
```

### Parametros del Request

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| orderId | string | Si | Referencia unica de orden |
| totalAmount | number | Si | Monto total en pesos |
| currency | string | Si | Moneda (COP) |
| shippingAmount | number | Si | Costo de envio (0 para digital) |
| totalTaxesAmount | number | Si | Total impuestos |
| items | array | Si | Lista de items |
| client | object | Si | Datos del cliente |
| billingAddress | object | Si | Direccion de facturacion |
| shippingAddress | object | Si | Direccion de envio |
| allyUrlRedirection | object | Si | URLs de callback y redireccion |
| geoLocation | object | No | Ubicacion geografica |

### Estructura de Items

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| sku | string | Si | Codigo del producto |
| name | string | Si | Nombre del producto |
| quantity | number | Si | Cantidad |
| unitPrice | number | Si | Precio unitario |
| category | string | Si | Categoria (EDUCATION) |

### Estructura de Client

| Campo | Tipo | Requerido | Descripcion |
|-------|------|-----------|-------------|
| idType | string | Si | Tipo documento (CC, CE, NIT) |
| idNumber | string | Si | Numero de documento |
| firstName | string | Si | Nombre |
| lastName | string | Si | Apellido |
| email | string | Si | Email |
| cellphone | string | Si | Telefono con codigo pais |
| cellphoneCountryCode | string | Si | Codigo de pais (+57) |

### Respuesta Exitosa

```json
{
  "applicationId": "app_abc123def456",
  "orderId": "MOODLE-12345",
  "status": "PENDING",
  "redirectionUrl": "https://checkout.addi.com/application/app_abc123def456",
  "createdAt": "2025-01-15T10:30:00Z"
}
```

---

## Estados de Solicitud

### Estados Posibles

| Estado | Descripcion | Final | Accion |
|--------|-------------|-------|--------|
| `PENDING` | Solicitud iniciada | No | Esperar |
| `APPROVED` | Credito aprobado | Si | Entregar curso |
| `REJECTED` | Credito rechazado | Si | Informar usuario |
| `CANCELLED` | Cancelada por usuario | Si | Ninguna |
| `EXPIRED` | Tiempo agotado | Si | Reintentar |
| `DECLINED` | Rechazada por politica | Si | Informar usuario |

### Flujo de Estados

```
PENDING ──────┬────> APPROVED ────> (entrega curso)
              │
              ├────> REJECTED
              │
              ├────> CANCELLED
              │
              ├────> EXPIRED
              │
              └────> DECLINED
```

---

## Webhooks

### Estructura del Evento

```json
{
  "event": "application.approved",
  "applicationId": "app_abc123def456",
  "orderId": "MOODLE-12345",
  "status": "APPROVED",
  "timestamp": "2025-01-15T10:35:00Z",
  "data": {
    "totalAmount": 500000,
    "currency": "COP",
    "approvedAmount": 500000,
    "installments": 4,
    "monthlyPayment": 125000
  }
}
```

### Eventos Disponibles

| Evento | Descripcion |
|--------|-------------|
| `application.approved` | Solicitud aprobada |
| `application.rejected` | Solicitud rechazada |
| `application.cancelled` | Solicitud cancelada |
| `application.expired` | Solicitud expirada |
| `application.declined` | Solicitud declinada |

### Verificacion de Firma JWT

Los webhooks estan firmados con JWT. Verificar:

```php
public function verify_webhook_jwt(string $token): bool {
    // Decodificar JWT (header.payload.signature)
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    // Verificar firma con clave publica de Addi
    // Verificar expiracion
    // Verificar issuer

    return true;
}
```

### Headers del Webhook

| Header | Descripcion |
|--------|-------------|
| `X-Addi-Signature` | JWT con la firma del evento |
| `Content-Type` | `application/json` |

---

## Consultar Estado

### Endpoint

```
GET /allies/{ally_slug}/online-applications/{application_id}
Authorization: Bearer {access_token}
```

### Respuesta

```json
{
  "applicationId": "app_abc123def456",
  "orderId": "MOODLE-12345",
  "status": "APPROVED",
  "totalAmount": 500000,
  "currency": "COP",
  "client": {
    "firstName": "Juan",
    "lastName": "Perez",
    "email": "juan@email.com"
  },
  "approvalDetails": {
    "approvedAmount": 500000,
    "installments": 4,
    "monthlyPayment": 125000,
    "interestRate": 0.019
  },
  "createdAt": "2025-01-15T10:30:00Z",
  "updatedAt": "2025-01-15T10:35:00Z"
}
```

---

## Ejemplos de Respuesta

### Solicitud Aprobada

```json
{
  "applicationId": "app_abc123def456",
  "orderId": "MOODLE-12345",
  "status": "APPROVED",
  "approvalDetails": {
    "approvedAmount": 500000,
    "installments": 4,
    "monthlyPayment": 132500,
    "totalInterest": 30000,
    "annualRate": 0.228
  }
}
```

### Solicitud Rechazada

```json
{
  "applicationId": "app_abc123def456",
  "orderId": "MOODLE-12345",
  "status": "REJECTED",
  "rejectionReason": "INSUFFICIENT_CREDIT_SCORE",
  "message": "La solicitud no cumple con los criterios de aprobacion"
}
```

### Solicitud Pendiente

```json
{
  "applicationId": "app_abc123def456",
  "orderId": "MOODLE-12345",
  "status": "PENDING",
  "redirectionUrl": "https://checkout.addi.com/application/app_abc123def456",
  "expiresAt": "2025-01-15T11:30:00Z"
}
```

---

## Codigos de Error

### Errores de API

| Codigo | Mensaje | Descripcion |
|--------|---------|-------------|
| 400 | Bad Request | Datos invalidos en el request |
| 401 | Unauthorized | Token invalido o expirado |
| 403 | Forbidden | Sin permisos para el recurso |
| 404 | Not Found | Solicitud no encontrada |
| 422 | Unprocessable Entity | Datos no procesables |
| 429 | Too Many Requests | Rate limit excedido |
| 500 | Internal Server Error | Error del servidor |

### Errores de Validacion

| Campo | Error | Descripcion |
|-------|-------|-------------|
| totalAmount | BELOW_MINIMUM | Monto menor al minimo |
| totalAmount | ABOVE_MAXIMUM | Monto mayor al maximo |
| client.idNumber | INVALID_FORMAT | Formato de documento invalido |
| client.email | INVALID_EMAIL | Email invalido |
| orderId | DUPLICATE | Orden ya existe |

### Razones de Rechazo

| Razon | Descripcion |
|-------|-------------|
| `INSUFFICIENT_CREDIT_SCORE` | Score crediticio insuficiente |
| `ALREADY_HAS_ACTIVE_CREDIT` | Ya tiene credito activo |
| `IDENTITY_VERIFICATION_FAILED` | Verificacion de identidad fallida |
| `AGE_RESTRICTION` | No cumple requisito de edad |
| `FRAUD_DETECTION` | Detectado como posible fraude |

---

## Implementacion en Moodle

### Flujo de Pago

```
1. Usuario selecciona curso
         ↓
2. pay.php solicita access_token OAuth2
         ↓
3. pay.php crea solicitud en Addi API
         ↓
4. Usuario es redirigido a checkout.addi.com
         ↓
5. Usuario completa proceso en Addi
         ↓
6a. Webhook a webhook.php (asincrono)
    → Verifica JWT
    → deliver_order() si APPROVED
         ↓
6b. Redireccion a response.php
    → Consulta estado via API
    → Muestra resultado
         ↓
7. Usuario inscrito en curso
```

### Archivos Clave

| Archivo | Funcion |
|---------|---------|
| `pay.php` | Crea solicitud y redirige a Addi |
| `webhook.php` | Recibe eventos asincronos |
| `response.php` | Procesa retorno del usuario |
| `classes/addi_helper.php` | Helper para OAuth2 y API |
| `classes/gateway.php` | Configuracion de la pasarela |

### Implementacion del Helper

```php
class addi_helper {
    private $clientid;
    private $clientsecret;
    private $allyslug;
    private $environment;
    private $accesstoken;

    public function __construct($clientid, $clientsecret, $allyslug, $environment) {
        $this->clientid = $clientid;
        $this->clientsecret = $clientsecret;
        $this->allyslug = $allyslug;
        $this->environment = $environment;
    }

    public function get_base_url(): string {
        return $this->environment === 'production'
            ? 'https://api.addi.com'
            : 'https://api.addi-staging.com';
    }

    public function create_application(array $orderdata): array {
        $token = $this->get_access_token();
        $url = $this->get_base_url() . "/allies/{$this->allyslug}/online-applications";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderdata));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
```

---

## Referencias

- [Documentacion Oficial Addi](https://docs.addi.com)
- [Portal de Aliados](https://allies.addi.com)
- [OAuth 2.0 RFC](https://tools.ietf.org/html/rfc6749)

---

*Documentacion tecnica paygw_addi v1.0.0 | © 2025 ingeweb.co*
