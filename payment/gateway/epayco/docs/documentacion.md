# ePayco Payment Gateway para Moodle

> Pasarela de pagos ePayco integrada con tu plataforma educativa

**Version:** 1.0.0 | **Moodle:** 4.1+ | **Pais:** Colombia | **Monedas:** COP, USD

---

## Tabla de Contenidos

1. [Introduccion](#introduccion)
2. [Requisitos](#requisitos)
3. [Instalacion](#instalacion)
4. [Configuracion en ePayco](#configuracion-en-epayco)
5. [Configuracion en Moodle](#configuracion-en-moodle)
6. [Metodos de Pago](#metodos-de-pago)
7. [URLs de Confirmacion](#urls-de-confirmacion)
8. [Guia para Administradores](#guia-para-administradores)
9. [Guia para Estudiantes](#guia-para-estudiantes)
10. [Pruebas Sandbox](#pruebas-sandbox)
11. [Seguridad](#seguridad)
12. [Solucion de Problemas](#solucion-de-problemas)
13. [API Reference](#api-reference)

---

## Introduccion

Plugin de pasarela de pago ePayco para Moodle, disenado para el mercado colombiano y latinoamericano. Permite monetizar cursos aceptando multiples metodos de pago.

### Caracteristicas Principales

| Caracteristica | Descripcion |
|----------------|-------------|
| Multiples Metodos | Tarjetas, PSE, Efectivo (Baloto, Efecty), Daviplata, Nequi |
| Multimoneda | Soporte para COP y USD |
| Checkout.js | Widget de pago integrado y responsivo |
| Confirmacion Server-to-Server | Callback seguro para confirmacion de pagos |
| Notificaciones | Alertas automaticas por email |
| Ambiente Sandbox | Pruebas completas sin cargos reales |

### Por que ePayco?

- **Lider en Colombia** - Una de las pasarelas mas usadas en el pais
- **Multiples canales** - Tarjetas, transferencias, efectivo y billeteras
- **Facil integracion** - Checkout.js simplifica el proceso
- **Soporte multimoneda** - COP y USD para mercados internacionales
- **Documentacion completa** - Recursos en espanol

---

## Requisitos

### Requisitos Tecnicos

| Componente | Minimo | Recomendado | Notas |
|------------|--------|-------------|-------|
| Moodle | 4.1 | 4.3+ | Compatible con Payment API |
| PHP | 8.0 | 8.1+ | Extension cURL requerida |
| SSL/HTTPS | Produccion | Siempre activo | Obligatorio para pagos |
| cURL | Habilitada | Habilitada | Comunicacion con API |

### Requisitos de Cuenta ePayco

- Cuenta comercial en [epayco.com](https://epayco.com)
- Documentacion comercial verificada
- Credenciales de API (P_CUST_ID, P_KEY, PUBLIC_KEY, PRIVATE_KEY)

---

## Instalacion

### Metodo 1: Instalacion Manual

1. **Descarga el plugin** desde el repositorio oficial

2. **Extrae los archivos** en:
   ```
   /tu-sitio-moodle/payment/gateway/epayco
   ```

3. **Estructura de archivos:**
   ```
   payment/gateway/epayco/
   ├── classes/
   │   ├── gateway.php
   │   ├── epayco_helper.php
   │   └── privacy/provider.php
   ├── db/
   │   ├── install.xml
   │   └── messages.php
   ├── docs/
   │   └── documentacion.md
   ├── lang/
   │   ├── en/paygw_epayco.php
   │   └── es/paygw_epayco.php
   ├── pay.php
   ├── confirmation.php
   ├── response.php
   └── version.php
   ```

4. **Ejecuta la instalacion:**
   ```
   Administracion del sitio → Notificaciones
   ```

### Metodo 2: Via Git

```bash
cd /ruta/a/moodle/payment/gateway
git clone https://github.com/ingeweb/moodle-paygw_epayco.git epayco
php /ruta/a/moodle/admin/cli/upgrade.php
```

---

## Configuracion en ePayco

### Paso 1: Crear cuenta

1. Registrate en [epayco.com](https://epayco.com)
2. Completa la verificacion de documentos
3. Activa tu cuenta comercial

### Paso 2: Obtener Credenciales

1. Accede al panel de ePayco
2. Ve a **Configuracion → Integraciones → Llaves API**
3. Copia las credenciales:

| Credencial | Descripcion | Uso |
|------------|-------------|-----|
| P_CUST_ID_CLIENTE | ID del comercio | Identificacion |
| P_KEY | Llave secreta | Firma de transacciones |
| PUBLIC_KEY | Llave publica | Iniciar checkout |
| PRIVATE_KEY | Llave privada | Consultas API |

> **Seguridad:** Nunca compartas P_KEY o PRIVATE_KEY. No las publiques en codigo fuente.

---

## Configuracion en Moodle

### Paso 1: Crear Cuenta de Pago

1. Ve a: **Administracion del sitio → Plugins → Pasarelas de pago → Gestionar cuentas**
2. Haz clic en "Crear cuenta de pago"
3. Nombre: "Pagos Colombia - ePayco"
4. Habilita la pasarela ePayco

### Paso 2: Configurar Credenciales

| Campo | Descripcion | Requerido |
|-------|-------------|-----------|
| Ambiente | Pruebas o Produccion | Si |
| P_CUST_ID_CLIENTE | Tu ID de cliente | Si |
| P_KEY | Tu llave secreta | Si |
| PUBLIC_KEY | Tu llave publica | Si |
| PRIVATE_KEY | Tu llave privada | Si |
| Idioma | Espanol o Ingles | No |
| Recopilar datos | Pre-llenar datos del usuario | No |

### Plantillas de Email

| Placeholder | Descripcion | Ejemplo |
|-------------|-------------|---------|
| `{firstname}` | Nombre del usuario | Juan |
| `{fullname}` | Nombre completo | Juan Perez |
| `{amount}` | Monto con moneda | $50,000 COP |
| `{currency}` | Codigo de moneda | COP |
| `{orderid}` | ID de orden | EPY-123456 |

---

## Metodos de Pago

### Metodos Soportados

| Metodo | Tipo | Confirmacion | Notas |
|--------|------|--------------|-------|
| Tarjetas Credito | Inmediato | Instantanea | Visa, MC, Amex, Diners |
| Tarjetas Debito | Inmediato | Instantanea | 3D Secure |
| PSE | Asincrono | 1-30 minutos | Transferencia bancaria |
| Efectivo | Asincrono | Hasta 24h | Baloto, Efecty, Gana |
| Daviplata | Asincrono | 1-5 minutos | Billetera digital |
| Nequi | Asincrono | 1-5 minutos | Billetera digital |

---

## URLs de Confirmacion

Configura estas URLs en tu panel de ePayco:

### URL de Confirmacion (Server-to-Server)

```
https://tu-sitio.com/payment/gateway/epayco/confirmation.php
```

Esta URL recibe la confirmacion de ePayco de forma asincrona.

### URL de Respuesta (Retorno Usuario)

```
https://tu-sitio.com/payment/gateway/epayco/response.php
```

Esta URL es donde el usuario es redirigido despues del pago.

> **Importante:** Ambas URLs deben estar accesibles via HTTPS.

---

## Guia para Administradores

### Configurar Pago en un Curso

1. Navega al curso
2. Ve a: **Administracion del curso → Usuarios → Metodos de inscripcion**
3. Agrega "Inscripcion con pago"
4. Configura:
   - **Cuenta de pago:** Selecciona la cuenta con ePayco
   - **Costo:** Ingresa el precio
   - **Moneda:** COP o USD

### Estados de Transaccion

| Codigo | Estado | Descripcion | Accion |
|--------|--------|-------------|--------|
| 1 | Aprobada | Transaccion exitosa | Usuario inscrito |
| 2 | Rechazada | Transaccion rechazada | Reintentar |
| 3 | Pendiente | Esperando confirmacion | Esperar |
| 4 | Fallida | Error en proceso | Revisar logs |
| 6 | Reversada | Transaccion revertida | Verificar |
| 7 | Retenida | En revision antifraude | Esperar |
| 9 | Expirada | Tiempo agotado | Reintentar |
| 10 | Abandonada | Usuario abandono | Ninguna |
| 11 | Cancelada | Cancelada por usuario | Ninguna |
| 12 | Antifraude | Rechazada por fraude | Contactar soporte |

### Tareas Programadas

| Tarea | Frecuencia | Funcion |
|-------|------------|---------|
| Verificar pendientes | 15 minutos | Consulta estado en ePayco |
| Limpiar expiradas | Diaria 3AM | Elimina transacciones abandonadas |

---

## Guia para Estudiantes

### Como Pagar e Inscribirse

1. Accede al curso que deseas tomar
2. Haz clic en "Pagar con ePayco"
3. Se abre el widget de ePayco
4. Selecciona tu metodo de pago
5. Completa los datos requeridos
6. Confirma el pago
7. Espera la confirmacion

### Tiempos de Confirmacion

- **Tarjeta:** Instantaneo
- **PSE:** 1-30 minutos
- **Efectivo:** Hasta 24 horas despues de pagar
- **Nequi/Daviplata:** 1-5 minutos

---

## Pruebas Sandbox

### Credenciales de Prueba

Obtener tus propias credenciales de sandbox en el panel de ePayco.

### Tarjetas de Prueba

| Tipo | Numero | CVV | Vencimiento | Resultado |
|------|--------|-----|-------------|-----------|
| Visa | `4575623182290326` | 123 | 12/25 | Aprobada |
| Visa | `4151611527583283` | 123 | 12/25 | Rechazada |
| Amex | `373118856457642` | 123 | 12/25 | Pendiente |

### PSE de Prueba

En sandbox, selecciona "Banco de prueba" para simular el flujo.

---

## Seguridad

### Verificacion de Firma

Todas las transacciones se validan con firma SHA-256:

```
signature = SHA256(p_cust_id_cliente ^ x_ref_payco ^ x_transaction_id ^ x_amount ^ x_currency_code ^ p_key)
```

### Mejores Practicas

- Usa HTTPS siempre
- No expongas P_KEY ni PRIVATE_KEY
- Configura las URLs de confirmacion
- Monitorea transacciones regularmente
- Valida montos en el servidor

---

## Solucion de Problemas

### El pago no se confirma automaticamente

1. Verifica URL de confirmacion en panel ePayco
2. Asegurate que el servidor acepta POST externos
3. Revisa logs de Moodle

### Error de firma invalida

1. Verifica P_CUST_ID_CLIENTE y P_KEY
2. Confirma ambiente correcto (test/produccion)
3. Verifica que no haya espacios en credenciales

### Usuario no se inscribe despues del pago

1. Ejecuta tarea "Verificar transacciones pendientes"
2. Revisa estado en panel ePayco
3. Verifica configuracion del curso

---

## API Reference

### Clase epayco_helper

```php
use paygw_epayco\epayco_helper;

// Crear instancia
$helper = new epayco_helper($p_cust_id, $p_key, $public_key, $private_key, $test_mode);

// Verificar firma de respuesta
$valid = $helper->verify_signature($response_data);

// Obtener estado de transaccion
$status = $helper->get_transaction_status($ref_payco);

// Entregar orden
$helper->deliver_order($component, $paymentarea, $itemid, $userid, $cost, $currency);
```

### Codigos de Respuesta (x_cod_response)

```php
1  => 'Aprobada'
2  => 'Rechazada'
3  => 'Pendiente'
4  => 'Fallida'
6  => 'Reversada'
7  => 'Retenida'
9  => 'Expirada'
10 => 'Abandonada'
11 => 'Cancelada'
12 => 'Antifraude'
```

---

## Soporte

- **Plugin:** soporte@ingeweb.co
- **Web:** [ingeweb.co](https://ingeweb.co)
- **ePayco:** [docs.epayco.com](https://docs.epayco.com)

---

*Plugin paygw_epayco v1.0.0 | © 2025 ingeweb.co | Licencia GNU GPL v3*
