# Addi Payment Gateway para Moodle

> Compra Ahora, Paga Despues (BNPL) integrado con tu plataforma educativa

**Version:** 1.0.0 | **Moodle:** 4.1+ | **Pais:** Colombia | **Moneda:** COP

---

## Tabla de Contenidos

1. [Introduccion](#introduccion)
2. [Requisitos](#requisitos)
3. [Instalacion](#instalacion)
4. [Configuracion en Addi](#configuracion-en-addi)
5. [Configuracion en Moodle](#configuracion-en-moodle)
6. [Flujo de Pago BNPL](#flujo-de-pago-bnpl)
7. [Webhook](#webhook)
8. [Guia para Administradores](#guia-para-administradores)
9. [Guia para Estudiantes](#guia-para-estudiantes)
10. [Pruebas Sandbox](#pruebas-sandbox)
11. [Seguridad](#seguridad)
12. [Solucion de Problemas](#solucion-de-problemas)
13. [API Reference](#api-reference)

---

## Introduccion

Plugin de pasarela de pago Addi para Moodle. Addi es un servicio colombiano de "Buy Now, Pay Later" (BNPL) que permite a los estudiantes dividir el pago de sus cursos en cuotas.

### Que es BNPL?

**Buy Now, Pay Later** (Compra Ahora, Paga Despues) es un modelo de financiamiento que permite:
- Acceder inmediatamente al curso
- Pagar en cuotas flexibles
- No requiere tarjeta de credito tradicional
- Aprobacion rapida (minutos)

### Caracteristicas Principales

| Caracteristica | Descripcion |
|----------------|-------------|
| Financiamiento | Cuotas flexibles sin tarjeta de credito |
| Aprobacion Rapida | Verificacion de credito en minutos |
| Sin Costos Ocultos | Transparencia en tasas e intereses |
| OAuth2 | Autenticacion segura via API |
| Webhooks | Notificaciones asincronas de estado |
| Sandbox | Ambiente de pruebas completo |

### Por que Addi?

- **Accesibilidad** - Estudiantes sin tarjeta pueden acceder a cursos
- **Mayor conversion** - Reduce abandono por falta de pago inmediato
- **Pago seguro** - Addi asume el riesgo de credito
- **Integracion simple** - API REST moderna con OAuth2

---

## Requisitos

### Requisitos Tecnicos

| Componente | Minimo | Recomendado | Notas |
|------------|--------|-------------|-------|
| Moodle | 4.1 | 4.3+ | Compatible con Payment API |
| PHP | 8.0 | 8.1+ | Extension cURL requerida |
| SSL/HTTPS | Obligatorio | Siempre activo | Requerido por Addi |
| cURL | Habilitada | Habilitada | Comunicacion OAuth2/API |

### Requisitos de Cuenta Addi

- Contrato de aliado comercial con Addi
- Credenciales de API (Client ID, Client Secret)
- Ally Slug (identificador del comercio)

### Limites de Monto

| Tipo | Valor | Configurable |
|------|-------|--------------|
| Minimo | $50,000 COP | Si |
| Maximo | $5,000,000 COP | Si |

> **Nota:** Los limites pueden variar segun el contrato con Addi.

---

## Instalacion

### Metodo 1: Instalacion Manual

1. **Descarga el plugin** desde el repositorio oficial

2. **Extrae los archivos** en:
   ```
   /tu-sitio-moodle/payment/gateway/addi
   ```

3. **Estructura de archivos:**
   ```
   payment/gateway/addi/
   ├── classes/
   │   ├── gateway.php
   │   ├── addi_helper.php
   │   └── privacy/provider.php
   ├── db/
   │   ├── install.xml
   │   └── messages.php
   ├── docs/
   │   └── documentacion.md
   ├── lang/
   │   ├── en/paygw_addi.php
   │   └── es/paygw_addi.php
   ├── pay.php
   ├── webhook.php
   ├── response.php
   └── version.php
   ```

4. **Ejecuta la instalacion:**
   ```
   Administracion del sitio → Notificaciones
   ```

---

## Configuracion en Addi

### Paso 1: Obtener Contrato de Aliado

1. Contacta al equipo comercial de Addi en [co.addi.com](https://co.addi.com)
2. Completa el proceso de onboarding
3. Firma el contrato de aliado comercial

### Paso 2: Recibir Credenciales

Una vez aprobado, recibiras:

| Credencial | Descripcion | Uso |
|------------|-------------|-----|
| Client ID | Identificador de la aplicacion | OAuth2 |
| Client Secret | Secreto de la aplicacion | OAuth2 |
| Ally Slug | Identificador unico del comercio | API |

### Ambientes

| Ambiente | URL Base | Uso |
|----------|----------|-----|
| Sandbox | `https://api.addi-staging.com` | Pruebas |
| Produccion | `https://api.addi.com` | Pagos reales |

---

## Configuracion en Moodle

### Paso 1: Crear Cuenta de Pago

1. Ve a: **Administracion del sitio → Plugins → Pasarelas de pago → Gestionar cuentas**
2. Haz clic en "Crear cuenta de pago"
3. Nombre: "Financiamiento Addi"
4. Habilita la pasarela Addi

### Paso 2: Configurar Credenciales

| Campo | Descripcion | Requerido |
|-------|-------------|-----------|
| Ambiente | Sandbox o Produccion | Si |
| Client ID | Tu Client ID de Addi | Si |
| Client Secret | Tu Client Secret de Addi | Si |
| Ally Slug | Tu identificador de aliado | Si |
| Monto Minimo | Monto minimo permitido | No |
| Monto Maximo | Monto maximo permitido | No |

### Plantillas de Email

| Placeholder | Descripcion | Ejemplo |
|-------------|-------------|---------|
| `{firstname}` | Nombre del usuario | Juan |
| `{fullname}` | Nombre completo | Juan Perez |
| `{amount}` | Monto con moneda | $500,000 COP |
| `{orderid}` | ID de orden | ADDI-123456 |

---

## Flujo de Pago BNPL

### Proceso de Solicitud

1. **Inicio:** Usuario selecciona "Pagar con Addi"
2. **Redireccion:** Usuario va al portal de Addi
3. **Verificacion:** Addi evalua credito del usuario
4. **Aprobacion:** Usuario acepta condiciones de financiamiento
5. **Confirmacion:** Addi notifica via webhook
6. **Entrega:** Usuario accede al curso inmediatamente

### Estados del Proceso

| Estado | Descripcion | Accion |
|--------|-------------|--------|
| PENDING | Solicitud iniciada | Esperar |
| APPROVED | Credito aprobado | Inscribir usuario |
| REJECTED | Credito rechazado | Informar usuario |
| CANCELLED | Solicitud cancelada | Ninguna |
| EXPIRED | Tiempo agotado | Reintentar |
| DECLINED | Rechazado por Addi | Informar usuario |

---

## Webhook

### Configurar Webhook

1. Addi configurara la URL de tu webhook durante el onboarding
2. URL del webhook:
   ```
   https://tu-sitio.com/payment/gateway/addi/webhook.php
   ```

### Eventos del Webhook

| Evento | Descripcion |
|--------|-------------|
| application.approved | Solicitud de credito aprobada |
| application.rejected | Solicitud de credito rechazada |
| application.cancelled | Solicitud cancelada por usuario |
| application.expired | Solicitud expirada |

### Verificacion JWT

Addi firma los webhooks con JWT. El plugin verifica automaticamente la firma.

---

## Guia para Administradores

### Configurar Pago en un Curso

1. Navega al curso
2. Ve a: **Administracion del curso → Usuarios → Metodos de inscripcion**
3. Agrega "Inscripcion con pago"
4. Configura:
   - **Cuenta de pago:** Selecciona la cuenta con Addi
   - **Costo:** Ingresa el precio (minimo $50,000 COP)
   - **Moneda:** COP

### Consideraciones de Precio

- **Minimo:** $50,000 COP (o el configurado)
- **Maximo:** $5,000,000 COP (o el configurado)
- Cursos fuera del rango no mostraran opcion Addi

### Monitorear Solicitudes

| Ubicacion | Informacion |
|-----------|-------------|
| Reportes de Pagos | Estado de transacciones |
| Logs del sistema | Errores y eventos |
| Tabla `paygw_addi_transactions` | Detalle de solicitudes |

---

## Guia para Estudiantes

### Como Financiar tu Curso

1. **Selecciona el curso** que deseas tomar
2. **Haz clic en "Pagar con Addi"**
3. **Seras redirigido** al portal de Addi
4. **Completa tus datos** personales y financieros
5. **Espera la evaluacion** de credito (1-5 minutos)
6. **Si eres aprobado:**
   - Acepta las condiciones del financiamiento
   - Selecciona el plan de cuotas
   - Confirma el credito
7. **Accede a tu curso** inmediatamente

### Requisitos para Estudiantes

- Documento de identidad colombiano (CC, CE)
- Telefono celular colombiano
- Historial crediticio (Addi lo verifica)
- Mayoria de edad

### Planes de Cuotas

Addi ofrece diferentes planes segun el monto:
- 2-4 cuotas para montos bajos
- Hasta 12 cuotas para montos altos
- Las condiciones varian segun evaluacion crediticia

---

## Pruebas Sandbox

### Ambiente de Sandbox

El ambiente de sandbox permite probar la integracion sin procesar creditos reales.

### Datos de Prueba

Addi proporcionara datos de prueba especificos durante el onboarding:
- Usuarios de prueba con diferentes resultados
- Documentos de identidad de prueba
- Escenarios de aprobacion/rechazo

### Escenarios de Prueba

| Escenario | Resultado Esperado |
|-----------|-------------------|
| Usuario apto | Solicitud aprobada |
| Usuario no apto | Solicitud rechazada |
| Usuario cancela | Solicitud cancelada |
| Timeout | Solicitud expirada |

---

## Seguridad

### Autenticacion OAuth2

```
1. Solicitar access_token:
   POST /oauth/token
   grant_type=client_credentials
   client_id={client_id}
   client_secret={client_secret}

2. Usar token en peticiones:
   Authorization: Bearer {access_token}
```

### Verificacion de Webhooks

Los webhooks estan firmados con JWT. El plugin verifica:
- Firma del token
- Expiracion del token
- Emisor del token (Addi)

### Mejores Practicas

- Almacena Client Secret de forma segura
- Usa HTTPS siempre
- Valida todos los webhooks
- Monitorea transacciones sospechosas

---

## Solucion de Problemas

### El boton de Addi no aparece

1. Verifica que el monto este dentro de los limites
2. Confirma que la cuenta de pago tenga Addi habilitado
3. Revisa que la moneda sea COP

### Error de autenticacion OAuth2

1. Verifica Client ID y Client Secret
2. Confirma el ambiente correcto (sandbox/produccion)
3. Revisa logs de Moodle

### Usuario no se inscribe despues de aprobacion

1. Verifica configuracion del webhook
2. Revisa que la URL sea HTTPS
3. Consulta logs para errores de entrega
4. Ejecuta tarea de verificacion de pendientes

### Solicitud siempre rechazada

En sandbox, verifica que estes usando datos de prueba correctos.
En produccion, el rechazo depende de la evaluacion de Addi.

---

## API Reference

### Clase addi_helper

```php
use paygw_addi\addi_helper;

// Crear instancia
$helper = new addi_helper($client_id, $client_secret, $ally_slug, $environment);

// Obtener token OAuth2
$token = $helper->get_access_token();

// Crear solicitud de credito
$application = $helper->create_application($orderdata);

// Verificar estado de solicitud
$status = $helper->get_application_status($application_id);

// Entregar orden
$helper->deliver_order($component, $paymentarea, $itemid, $userid, $cost, $currency);
```

### Constantes de Estado

```php
addi_helper::STATUS_APPROVED    // 'APPROVED'
addi_helper::STATUS_REJECTED    // 'REJECTED'
addi_helper::STATUS_PENDING     // 'PENDING'
addi_helper::STATUS_CANCELLED   // 'CANCELLED'
addi_helper::STATUS_EXPIRED     // 'EXPIRED'
addi_helper::STATUS_DECLINED    // 'DECLINED'
```

### Endpoints de API

| Endpoint | Metodo | Descripcion |
|----------|--------|-------------|
| `/oauth/token` | POST | Obtener access token |
| `/online-applications` | POST | Crear solicitud |
| `/online-applications/{id}` | GET | Consultar estado |

---

## Soporte

- **Plugin:** soporte@ingeweb.co
- **Web:** [ingeweb.co](https://ingeweb.co)
- **Addi:** [co.addi.com](https://co.addi.com)

---

*Plugin paygw_addi v1.0.0 | © 2025 ingeweb.co | Licencia GNU GPL v3*
