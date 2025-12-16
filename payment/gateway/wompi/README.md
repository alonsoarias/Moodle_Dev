# Wompi Payment Gateway for Moodle

[![Moodle Plugin](https://img.shields.io/badge/Moodle-4.1+-orange.svg)](https://moodle.org)
[![PHP Version](https://img.shields.io/badge/PHP-7.4+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL%20v3-green.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Version](https://img.shields.io/badge/Version-1.0.0-brightgreen.svg)](CHANGELOG.md)

**Plugin de pasarela de pago Wompi para Moodle, diseñado específicamente para el mercado colombiano.**

**Desarrollado por [ingeweb.co](https://ingeweb.co)**

---

## Tabla de Contenidos

- [Descripción](#descripción)
- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Métodos de Pago Soportados](#métodos-de-pago-soportados)
- [Uso](#uso)
- [Datos de Prueba (Sandbox)](#datos-de-prueba-sandbox)
- [Seguridad](#seguridad)
- [Arquitectura del Plugin](#arquitectura-del-plugin)
- [Solución de Problemas](#solución-de-problemas)
- [API Reference](#api-reference)
- [Soporte](#soporte)
- [Contribuir](#contribuir)
- [Licencia](#licencia)
- [Créditos](#créditos)

---

## Descripción

Este plugin permite a los sitios Moodle aceptar pagos a través de **Wompi**, la plataforma líder de pagos en Colombia, subsidiaria de Bancolombia. Soporta múltiples métodos de pago locales adaptados al mercado colombiano, permitiendo a instituciones educativas monetizar sus cursos de manera sencilla y segura.

### ¿Por qué Wompi?

- **Líder en Colombia**: Plataforma respaldada por Bancolombia
- **Múltiples métodos de pago**: Tarjetas, billeteras digitales, transferencias bancarias
- **Sin costos de integración**: Solo pagas por transacción exitosa
- **Seguridad bancaria**: Cumple con estándares PCI DSS
- **Soporte local**: Atención en español para el mercado colombiano

---

## Características

### Métodos de Pago

| Método | Descripción | Tipo |
|--------|-------------|------|
| 💳 **Tarjetas** | Visa, Mastercard, American Express | Inmediato |
| 📱 **Nequi** | Billetera móvil líder en Colombia | Asíncrono |
| 🏦 **PSE** | Transferencias bancarias desde cualquier banco | Asíncrono |
| 🔄 **Bancolombia Transfer** | Transferencia directa desde Bancolombia | Asíncrono |
| 🏪 **Bancolombia Collect** | Pago en efectivo en corresponsales | Asíncrono |
| 📲 **Daviplata** | Billetera digital de Davivienda | Asíncrono |
| 🎁 **Puntos Colombia** | Programa de puntos de fidelidad | Inmediato |

### Seguridad

- **Firma de integridad SHA256** para todas las transacciones
- **Verificación de webhooks** con llave de eventos dedicada
- **Validación automática de llaves** según el ambiente (sandbox/producción)
- **Cumplimiento GDPR** para protección de datos personales
- **Sin almacenamiento de datos de tarjetas** en tu servidor

### Funcionalidades

- ✅ Soporte para ambientes **Sandbox** (pruebas) y **Producción**
- ✅ **Widget de checkout integrado** de Wompi
- ✅ **Procesamiento de pagos asíncronos** vía webhooks
- ✅ **Notificaciones automáticas** a usuarios por email
- ✅ **Soporte multiidioma** (Español e Inglés)
- ✅ **Registro completo de transacciones** en base de datos
- ✅ **Inscripción automática** al curso tras pago exitoso

---

## Requisitos

### Sistema

| Componente | Requisito Mínimo | Recomendado |
|------------|------------------|-------------|
| Moodle | 4.1+ | 4.3 o superior |
| PHP | 7.4+ | 8.1 o superior |
| SSL/HTTPS | Requerido en producción | Siempre activo |
| cURL Extension | Habilitada | Habilitada |

### Cuenta Wompi

- Cuenta de comercio verificada en [Wompi Colombia](https://comercios.wompi.co)
- Llaves de API (pública, privada, integridad)
- Webhook configurado para eventos de transacción

---

## Instalación

### Método 1: Instalación Manual

1. **Descarga el plugin** desde el repositorio oficial o [ingeweb.co](https://ingeweb.co)

2. **Extrae los archivos** en el directorio de pasarelas de pago:
   ```bash
   /tu-sitio-moodle/payment/gateway/wompi
   ```

3. **Verifica la estructura de archivos**:
   ```
   payment/gateway/wompi/
   ├── classes/
   │   ├── gateway.php
   │   ├── wompi_helper.php
   │   └── privacy/
   │       └── provider.php
   ├── db/
   │   ├── install.xml
   │   └── messages.php
   ├── docs/
   │   └── guia-instalacion.html
   ├── lang/
   │   ├── en/
   │   │   └── paygw_wompi.php
   │   └── es/
   │       └── paygw_wompi.php
   ├── pix/
   │   └── icon.svg
   ├── cancelled.php
   ├── CHANGELOG.md
   ├── lib.php
   ├── pay.php
   ├── process.php
   ├── README.md
   ├── version.php
   └── webhook.php
   ```

4. **Accede a Moodle como administrador** y navega a:
   ```
   Administración del sitio → Notificaciones
   ```

5. **Completa la instalación** siguiendo las instrucciones de Moodle

### Método 2: Via Git

```bash
# Navegar al directorio de pasarelas de pago
cd /ruta/a/moodle/payment/gateway

# Clonar el repositorio
git clone https://github.com/ingeweb/moodle-paygw_wompi.git wompi

# Ejecutar actualización de Moodle
php /ruta/a/moodle/admin/cli/upgrade.php
```

### Método 3: Via Composer

```bash
composer require ingeweb/moodle-paygw_wompi
```

---

## Configuración

### 1. Obtener Credenciales de Wompi

1. Accede al [Dashboard de Wompi](https://comercios.wompi.co)
2. Ve a **Configuración → Llaves de API**
3. Copia las siguientes llaves:

| Llave | Formato Sandbox | Formato Producción | Uso |
|-------|-----------------|-------------------|-----|
| Llave Pública | `pub_test_*` | `pub_prod_*` | Iniciar transacciones |
| Llave Privada | `prv_test_*` | `prv_prod_*` | Consultar transacciones |
| Llave de Integridad | Cadena alfanumérica | Cadena alfanumérica | Firmar transacciones |
| Llave de Eventos | Cadena alfanumérica | Cadena alfanumérica | Verificar webhooks |

### 2. Configurar en Moodle

1. Ve a **Administración del sitio → Plugins → Pasarelas de pago → Gestionar cuentas de pago**
2. Crea una nueva cuenta de pago o edita una existente
3. Habilita la pasarela **Wompi**
4. Ingresa las credenciales:
   - Selecciona el ambiente (Sandbox/Producción)
   - Llave Pública
   - Llave Privada
   - Llave de Integridad
   - Llave de Eventos (opcional pero recomendado)
5. Selecciona los métodos de pago a habilitar
6. Guarda los cambios

### 3. Configurar Webhook (Recomendado)

El webhook es **esencial** para procesar pagos asíncronos (PSE, Nequi, Bancolombia, etc.):

1. En el Dashboard de Wompi, ve a **Configuración → Eventos**
2. Agrega la URL del webhook:
   ```
   https://tu-sitio-moodle.com/payment/gateway/wompi/webhook.php
   ```
3. Activa el evento `transaction.updated`
4. Copia la **Llave de Eventos** y agrégala en la configuración del plugin en Moodle

---

## Métodos de Pago Soportados

### Tiempos de Confirmación

| Método | Tipo | Tiempo de Confirmación |
|--------|------|------------------------|
| Tarjeta de Crédito/Débito | Inmediato | Instantáneo |
| Nequi | Asíncrono | 1-5 minutos |
| PSE | Asíncrono | 1-30 minutos |
| Bancolombia Transfer | Asíncrono | 1-30 minutos |
| Bancolombia Collect | Asíncrono | Hasta 24 horas |
| Daviplata | Asíncrono | 1-5 minutos |
| Puntos Colombia | Inmediato | Instantáneo |

### Nota sobre pagos asíncronos

Los métodos de pago asíncronos requieren que el webhook esté correctamente configurado. Sin el webhook, los usuarios que paguen con estos métodos no serán inscritos automáticamente al curso.

---

## Uso

### Para Administradores

1. **Configura un método de inscripción con pago** en el curso:
   - Ve al curso → Participantes → Métodos de inscripción
   - Agrega "Inscripción con pago"
   - Selecciona la cuenta de pago con Wompi
   - Establece el precio en COP (pesos colombianos)

2. **Monitorea transacciones**:
   - En Moodle: Administración del sitio → Reportes → Pagos
   - En Wompi: Dashboard → Transacciones

### Para Estudiantes

1. Accede al curso que requiere pago
2. Haz clic en el botón de pago
3. Selecciona "Wompi" como método de pago
4. Completa el pago en el widget de Wompi
5. Serás inscrito automáticamente al completar el pago

---

## Datos de Prueba (Sandbox)

### Tarjetas de Prueba

| Número | Resultado |
|--------|-----------|
| `4242 4242 4242 4242` | ✅ Aprobada |
| `4111 1111 1111 1111` | ❌ Declinada |
| `4012 8888 8888 1881` | ⏳ Pendiente |

- **Fecha de vencimiento**: Cualquier fecha futura
- **CVV**: Cualquier 3 dígitos
- **Nombre**: Cualquier nombre

### Nequi de Prueba

| Número de Celular | Resultado |
|-------------------|-----------|
| `3991111111` | ✅ Aprobado |
| `3992222222` | ❌ Declinado |

### PSE de Prueba

En modo Sandbox, selecciona cualquier banco de la lista. El sistema simulará el flujo completo de pago.

---

## Seguridad

### Firma de Integridad

Todas las transacciones son firmadas usando SHA256:

```
signature = SHA256(reference + amount_in_cents + currency + integrity_key)
```

### Verificación de Webhooks

Los webhooks son verificados usando la llave de eventos:

```php
$signature_string = '';
foreach ($properties as $property) {
    $signature_string .= get_nested_value($event_data, $property);
}
$signature_string .= $timestamp . $events_key;
$calculated_signature = hash('sha256', $signature_string);
```

### Mejores Prácticas

1. **Nunca compartas** tus llaves privadas
2. **Usa HTTPS** siempre en producción
3. **Configura el webhook** para pagos asíncronos
4. **Prueba en Sandbox** antes de ir a producción
5. **Revisa los logs** regularmente

---

## Arquitectura del Plugin

### Estructura de Archivos

```
paygw_wompi/
├── classes/
│   ├── gateway.php          # Clase principal del gateway
│   ├── wompi_helper.php     # Helper para interactuar con la API
│   └── privacy/
│       └── provider.php     # Proveedor GDPR
├── db/
│   ├── install.xml          # Definición de tablas
│   └── messages.php         # Proveedores de mensajes
├── lang/
│   ├── en/paygw_wompi.php   # Cadenas en inglés
│   └── es/paygw_wompi.php   # Cadenas en español
├── pix/
│   └── icon.svg             # Icono del plugin
├── pay.php                  # Página de inicio de pago
├── process.php              # Procesador de callbacks
├── webhook.php              # Receptor de webhooks
├── cancelled.php            # Página de cancelación
├── lib.php                  # Funciones de librería
└── version.php              # Información de versión
```

### Base de Datos

El plugin crea la tabla `paygw_wompi_transactions`:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | ID autoincremental |
| userid | BIGINT | ID del usuario |
| transactionid | VARCHAR(255) | ID de transacción Wompi |
| reference | VARCHAR(255) | Referencia única |
| component | VARCHAR(100) | Componente de pago |
| paymentarea | VARCHAR(50) | Área de pago |
| itemid | BIGINT | ID del ítem |
| amount | BIGINT | Monto en centavos |
| currency | VARCHAR(3) | Moneda (COP) |
| status | VARCHAR(20) | Estado de la transacción |
| paymentmethod | VARCHAR(50) | Método de pago usado |
| timecreated | BIGINT | Timestamp de creación |
| timemodified | BIGINT | Timestamp de modificación |

### Flujo de Pago

```
1. Usuario inicia pago (pay.php)
   ↓
2. Se genera referencia y firma
   ↓
3. Widget de Wompi se abre
   ↓
4. Usuario completa pago
   ↓
5a. Pago inmediato: Callback a process.php
5b. Pago asíncrono: Webhook a webhook.php
   ↓
6. Verificación de transacción con API de Wompi
   ↓
7. Si aprobado: Inscripción al curso
   ↓
8. Notificación al usuario
```

---

## Solución de Problemas

### El pago no se procesa

1. Verifica que las llaves estén correctas para el ambiente seleccionado
2. Confirma que el prefijo coincida (`pub_test_` para Sandbox, `pub_prod_` para Producción)
3. Revisa los logs de Moodle
4. Verifica conexión a la API de Wompi

### Error de firma de integridad

1. Verifica que la Llave de Integridad sea correcta
2. Asegúrate de que no haya espacios adicionales en las llaves
3. Confirma que usas la llave del ambiente correcto

### El usuario no se inscribe después del pago

1. Verifica que el webhook esté activo
2. Confirma que la URL del webhook sea accesible (HTTPS)
3. Revisa que la Llave de Eventos esté configurada
4. Para pagos inmediatos, verifica la redirección

### El widget de Wompi no carga

1. Verifica que tu sitio use HTTPS
2. Revisa la consola del navegador por errores
3. Asegúrate de que no haya bloqueadores de scripts
4. Confirma que la Llave Pública sea válida

---

## API Reference

### Clase `wompi_helper`

```php
// Crear instancia
$helper = new wompi_helper($publickey, $privatekey, $integritykey, $environment, $eventskey);

// Generar firma de integridad
$signature = $helper->generate_integrity_signature($reference, $amount_in_cents, $currency);

// Obtener transacción
$transaction = $helper->get_transaction($transaction_id);

// Verificar firma de webhook
$valid = $helper->verify_webhook_signature($event_data, $signature);

// Entregar orden (inscribir usuario)
$helper->deliver_order($component, $paymentarea, $itemid, $userid, $cost, $currency);
```

### Clase `gateway`

```php
// Obtener lista de monedas soportadas
$currencies = gateway::get_supported_currencies(); // ['COP']

// Obtener URL de la API
$url = gateway::get_api_url('sandbox'); // https://sandbox.wompi.co/v1

// Obtener URL del checkout
$url = gateway::get_checkout_url(); // https://checkout.wompi.co
```

---

## Soporte

### Documentación

- **Guía completa**: Ver archivo `docs/guia-instalacion.html`
- **Documentación de Wompi**: [docs.wompi.co](https://docs.wompi.co)

### Contacto

- **Email**: soporte@ingeweb.co
- **Web**: [ingeweb.co](https://ingeweb.co)

### Reportar Bugs

Si encuentras un bug, por favor reportalo incluyendo:
- Versión de Moodle
- Versión de PHP
- Pasos para reproducir el problema
- Logs relevantes (sin incluir llaves o datos sensibles)

---

## Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Haz fork del repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Haz commit de tus cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crea un Pull Request

### Estándares de Código

- Sigue los [estándares de codificación de Moodle](https://docs.moodle.org/dev/Coding_style)
- Incluye PHPDoc en todas las funciones públicas
- Escribe tests para nuevas funcionalidades
- Mantén el soporte multiidioma

---

## Licencia

Este plugin está licenciado bajo la GNU GPL v3 o posterior.

```
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

Ver [LICENSE](https://www.gnu.org/licenses/gpl-3.0.html) para más detalles.

---

## Créditos

- **Desarrollado por**: [ingeweb.co](https://ingeweb.co)
- **Autor**: Alonso Arias
- **Email**: soporte@ingeweb.co
- **Versión**: 1.0.0

### Agradecimientos

- Equipo de Wompi por su excelente documentación de API
- Comunidad Moodle por los estándares y guías de desarrollo
- Todos los beta testers que ayudaron a mejorar el plugin

---

**Wompi es una marca registrada de Bancolombia S.A.**
