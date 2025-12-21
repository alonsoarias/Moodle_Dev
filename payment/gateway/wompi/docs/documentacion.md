# Wompi Payment Gateway para Moodle

> Pasarela de pagos oficial de Bancolombia integrada con tu plataforma educativa

**Version:** 1.0.0 | **Moodle:** 4.1+ | **Pais:** Colombia | **Moneda:** COP

---

## Tabla de Contenidos

1. [Introduccion](#introduccion)
2. [Requisitos](#requisitos)
3. [Instalacion](#instalacion)
4. [Configuracion en Wompi](#configuracion-en-wompi)
5. [Configuracion en Moodle](#configuracion-en-moodle)
6. [Metodos de Pago](#metodos-de-pago)
7. [Webhook](#webhook)
8. [Guia para Administradores](#guia-para-administradores)
9. [Guia para Estudiantes](#guia-para-estudiantes)
10. [Pruebas Sandbox](#pruebas-sandbox)
11. [Seguridad](#seguridad)
12. [Solucion de Problemas](#solucion-de-problemas)
13. [API Reference](#api-reference)

---

## Introduccion

Plugin de pasarela de pago Wompi para Moodle, disenado especificamente para el mercado colombiano. Permite monetizar cursos aceptando multiples metodos de pago locales.

### Caracteristicas Principales

| Caracteristica | Descripcion |
|----------------|-------------|
| Multiples Metodos | Tarjetas, Nequi, PSE, Bancolombia, Daviplata, Puntos Colombia |
| Seguridad Bancaria | Firma SHA256, verificacion de webhooks, cumplimiento PCI DSS |
| Pagos Asincronos | Webhooks para PSE, Nequi y otros metodos que requieren confirmacion |
| Exclusivo Colombia | Optimizado para el mercado colombiano en COP |
| Ambiente Sandbox | Ambiente de pruebas completo para desarrollo |
| Notificaciones | Alertas automaticas por email sobre el estado de los pagos |

### Por que Wompi?

- **Respaldo de Bancolombia** - Plataforma oficial del banco mas grande de Colombia
- **Sin costos de integracion** - Solo pagas por transaccion exitosa
- **Metodos locales** - Nequi, PSE, Daviplata y mas billeteras colombianas
- **Widget moderno** - Experiencia de pago fluida y responsiva
- **Documentacion en espanol** - Soporte y documentacion en tu idioma

---

## Requisitos

### Requisitos Tecnicos

| Componente | Minimo | Recomendado | Notas |
|------------|--------|-------------|-------|
| Moodle | 4.1 | 4.3+ | Compatible con Payment API |
| PHP | 7.4 | 8.1+ | Extension cURL requerida |
| SSL/HTTPS | Produccion | Siempre activo | Obligatorio para pagos reales |
| cURL | Habilitada | Habilitada | Para comunicacion con API |
| JSON | Habilitada | Habilitada | Para procesar respuestas |

### Requisitos de Cuenta Wompi

- Cuenta de comercio verificada en [comercios.wompi.co](https://comercios.wompi.co)
- RUT o NIT de empresa colombiana
- Cuenta bancaria colombiana para recibir fondos
- Llaves de API (publica, privada, integridad, eventos)

> **Importante:** Wompi es exclusivo para Colombia. Solo acepta pagos en Pesos Colombianos (COP) y requiere una cuenta bancaria colombiana.

---

## Instalacion

### Metodo 1: Instalacion Manual

1. **Descarga el plugin** desde el repositorio oficial o contacta a soporte@ingeweb.co

2. **Extrae los archivos** en el directorio de pasarelas de pago:
   ```
   /tu-sitio-moodle/payment/gateway/wompi
   ```

3. **Verifica la estructura de archivos:**
   ```
   payment/gateway/wompi/
   ├── classes/
   │   ├── gateway.php
   │   ├── wompi_helper.php
   │   └── privacy/provider.php
   ├── db/
   │   ├── install.xml
   │   └── messages.php
   ├── docs/
   │   └── documentacion.md
   ├── lang/
   │   ├── en/paygw_wompi.php
   │   └── es/paygw_wompi.php
   ├── pix/icon.svg
   ├── pay.php
   ├── process.php
   ├── webhook.php
   └── version.php
   ```

4. **Ejecuta la instalacion en Moodle:**
   ```
   Administracion del sitio → Notificaciones
   ```

5. **Verifica la instalacion:**
   ```
   Administracion del sitio → Plugins → Pasarelas de pago → Gestionar pasarelas
   ```

### Metodo 2: Via Git

```bash
cd /ruta/a/moodle/payment/gateway
git clone https://github.com/ingeweb/moodle-paygw_wompi.git wompi
php /ruta/a/moodle/admin/cli/upgrade.php
```

---

## Configuracion en Wompi

### Paso 1: Crear cuenta en Wompi

1. Registrate en [comercios.wompi.co](https://comercios.wompi.co)
2. Completa la verificacion (RUT, camara de comercio, etc.)
3. Configura tu cuenta bancaria colombiana

### Paso 2: Obtener las Llaves de API

1. Accede al Dashboard de Wompi
2. Navega a **Configuracion → Llaves de API**
3. Copia las llaves:

| Llave | Formato Sandbox | Formato Produccion | Uso |
|-------|-----------------|-------------------|-----|
| Llave Publica | `pub_test_...` | `pub_prod_...` | Iniciar transacciones |
| Llave Privada | `prv_test_...` | `prv_prod_...` | Consultar transacciones |
| Llave de Integridad | `test_integrity_...` | `prod_integrity_...` | Firmar transacciones |
| Llave de Eventos | `test_events_...` | `prod_events_...` | Verificar webhooks |

> **Seguridad:** Nunca compartas tus llaves privadas o de integridad. No las incluyas en codigo fuente publico.

---

## Configuracion en Moodle

### Paso 1: Crear una Cuenta de Pago

1. Ve a: **Administracion del sitio → Plugins → Pasarelas de pago → Gestionar cuentas de pago**
2. Haz clic en "Crear cuenta de pago"
3. Asigna un nombre descriptivo: "Pagos Colombia - Wompi"
4. Habilita la pasarela Wompi

### Paso 2: Configurar las Credenciales

| Campo | Descripcion | Requerido |
|-------|-------------|-----------|
| Ambiente | Sandbox para pruebas, Produccion para pagos reales | Si |
| Llave Publica | Tu llave publica de Wompi | Si |
| Llave Privada | Tu llave privada de Wompi | Si |
| Llave de Integridad | Para generar firmas de transacciones | Si |
| Llave de Eventos | Para verificar webhooks | No |
| Metodos de Pago | Selecciona los metodos a ofrecer | Si |
| Recopilar datos | Pre-llenar email y nombre en checkout | No |

### Plantillas de Email

Placeholders disponibles:

| Placeholder | Descripcion | Ejemplo |
|-------------|-------------|---------|
| `{firstname}` | Nombre del usuario | Juan |
| `{fullname}` | Nombre completo | Juan Perez |
| `{amount}` | Monto con moneda | $50,000.00 COP |
| `{currency}` | Codigo de moneda | COP |
| `{orderid}` | ID de orden | WMP-123456 |

---

## Metodos de Pago

| Metodo | Tipo | Tiempo Confirmacion | Requiere Webhook |
|--------|------|---------------------|------------------|
| Tarjeta Credito/Debito | Inmediato | Instantaneo | No |
| Nequi | Asincrono | 1-5 minutos | **Si** |
| PSE | Asincrono | 1-30 minutos | **Si** |
| Bancolombia Transfer | Asincrono | 1-30 minutos | **Si** |
| Bancolombia Collect | Asincrono | Hasta 24 horas | **Si** |
| Daviplata | Asincrono | 1-5 minutos | **Si** |
| Puntos Colombia | Inmediato | Instantaneo | No |

> **Importante:** Los metodos asincronos requieren webhook configurado correctamente para inscribir automaticamente al usuario.

---

## Webhook

### Configurar el Webhook en Wompi

1. Accede a [comercios.wompi.co](https://comercios.wompi.co)
2. Navega a **Configuracion → Eventos**
3. Agrega la URL:
   ```
   https://tu-sitio-moodle.com/payment/gateway/wompi/webhook.php
   ```
4. Activa el evento `transaction.updated`
5. Copia la Llave de Eventos y agregala en Moodle

### Verificar el Webhook

1. Configura ambiente Sandbox
2. Realiza un pago de prueba con Nequi o PSE
3. Verifica que el usuario se inscriba automaticamente

---

## Guia para Administradores

### Configurar Pago en un Curso

1. Navega al curso
2. Ve a: **Administracion del curso → Usuarios → Metodos de inscripcion**
3. Agrega "Inscripcion con pago"
4. Configura:
   - **Cuenta de pago:** Selecciona la cuenta con Wompi
   - **Costo:** Ingresa el precio en COP (ej: 50000)
   - **Moneda:** COP

### Estados de Transaccion

| Estado | Descripcion | Accion |
|--------|-------------|--------|
| `PENDING` | Pago iniciado, esperando confirmacion | Esperar o verificar en Wompi |
| `APPROVED` | Pago aprobado exitosamente | Usuario inscrito automaticamente |
| `DELIVERED` | Pago procesado y curso entregado | Ninguna - completado |
| `DECLINED` | Pago rechazado por el banco | Usuario debe reintentar |
| `ERROR` | Error en el procesamiento | Revisar logs |
| `VOIDED` | Transaccion anulada | Ninguna |

---

## Guia para Estudiantes

### Como Pagar e Inscribirse

1. Accede al curso que deseas tomar
2. Haz clic en "Pagar con Wompi"
3. Selecciona tu metodo de pago
4. Completa la informacion requerida
5. Confirma el pago
6. Espera la confirmacion (instantanea para tarjetas, minutos para PSE/Nequi)

---

## Pruebas Sandbox

### Tarjetas de Prueba

| Numero | CVV | Vencimiento | Resultado |
|--------|-----|-------------|-----------|
| `4242 4242 4242 4242` | 123 | 12/29 | Aprobada |
| `4111 1111 1111 1111` | 123 | 12/29 | Declinada |
| `4012 8888 8888 1881` | 123 | 12/29 | Pendiente |

### Nequi de Prueba

| Numero Celular | Resultado |
|----------------|-----------|
| `3991111111` | Aprobado |
| `3992222222` | Declinado |

---

## Seguridad

### Firma de Integridad SHA256

```
signature = SHA256(reference + amount_in_cents + currency + integrity_key)
```

### Verificacion de Webhooks

```php
$signature_string = '';
foreach ($properties as $property) {
    $signature_string .= get_nested_value($event_data, $property);
}
$signature_string .= $timestamp . $events_key;
$calculated_signature = hash('sha256', $signature_string);
```

### Mejores Practicas

- Usa HTTPS siempre
- Protege tus llaves
- Configura el webhook
- Monitorea transacciones
- Actualiza el plugin regularmente

---

## Solucion de Problemas

### El pago no se procesa

1. Verifica que las llaves sean correctas para el ambiente
2. Confirma prefijos (`pub_test_` para Sandbox, `pub_prod_` para Produccion)
3. Revisa logs en Administracion → Reportes → Logs

### Error de firma de integridad

1. Verifica que la Llave de Integridad sea exacta
2. Confirma ambiente correcto
3. Regenera la llave si es necesario

### Usuario no se inscribe despues del pago

1. Verifica configuracion del webhook
2. Confirma URL accesible (HTTPS)
3. Revisa Llave de Eventos en plugin
4. Revisa tabla `paygw_wompi_transactions`

---

## API Reference

### Clase wompi_helper

```php
use paygw_wompi\wompi_helper;

// Crear instancia
$helper = new wompi_helper($publickey, $privatekey, $integritykey, $environment, $eventskey);

// Generar firma de integridad
$signature = $helper->generate_integrity_signature($reference, $amount_in_cents, $currency);

// Obtener transaccion de Wompi
$transaction = $helper->get_transaction($transaction_id);

// Verificar firma de webhook
$valid = $helper->verify_webhook_signature($event_data, $signature);

// Entregar orden (inscribir usuario)
$helper->deliver_order($component, $paymentarea, $itemid, $userid, $cost, $currency);
```

### Constantes de Estado

```php
wompi_helper::STATUS_APPROVED   // 'APPROVED'
wompi_helper::STATUS_DECLINED   // 'DECLINED'
wompi_helper::STATUS_PENDING    // 'PENDING'
wompi_helper::STATUS_VOIDED     // 'VOIDED'
wompi_helper::STATUS_ERROR      // 'ERROR'
```

---

## Soporte

- **Plugin:** soporte@ingeweb.co
- **Web:** [ingeweb.co](https://ingeweb.co)
- **Wompi:** [docs.wompi.co](https://docs.wompi.co)

---

*Plugin paygw_wompi v1.0.0 | © 2025 ingeweb.co | Licencia GNU GPL v3*
