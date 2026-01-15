# INTEB Chat - Módulo de Chat con IA para Moodle

[![Moodle](https://img.shields.io/badge/Moodle-4.1+-orange.svg)](https://moodle.org)
[![Licencia](https://img.shields.io/badge/Licencia-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Versión](https://img.shields.io/badge/Versión-3.6.1-green.svg)](https://github.com/alonsoarias/mod_intebchat)

**[English Version](README.md)**

INTEB Chat es un potente módulo de actividad para Moodle que integra las capacidades de IA de OpenAI directamente en tu Sistema de Gestión de Aprendizaje. Permite a estudiantes y profesores interactuar con asistentes de IA dentro de los cursos, soportando chat de texto, entrada/salida de voz y conversaciones en tiempo real.

---

## Tabla de Contenidos

- [Características](#características)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Configuración](#configuración)
- [Uso](#uso)
- [Capacidades](#capacidades)
- [Referencia de API](#referencia-de-api)
- [Esquema de Base de Datos](#esquema-de-base-de-datos)
- [Privacidad y Seguridad](#privacidad-y-seguridad)
- [Solución de Problemas](#solución-de-problemas)
- [Registro de Cambios](#registro-de-cambios)
- [Contribuir](#contribuir)
- [Licencia](#licencia)
- [Soporte](#soporte)

---

## Características

### 🤖 Integración con IA

#### API de Chat Completions
- Modelo de conversación sin estado usando modelos GPT de OpenAI
- Parámetros configurables: temperatura, top-p, penalizaciones de frecuencia/presencia
- Prompts de sistema personalizados y base de conocimiento ("fuente de verdad")
- Gestión automática del historial de conversación
- Modelo por defecto: GPT-4.1

#### API de Asistentes
- Asistentes de IA con estado y hilos persistentes
- Instrucciones y configuraciones específicas por asistente
- Seguimiento de ID de hilo para continuidad de conversación
- Soporte para asistentes personalizados de OpenAI

### 🎙️ Capacidades de Audio

#### Voz a Texto
- Entrada de voz usando OpenAI Whisper API
- Formatos soportados: MP3, MP4, MPEG, MPGA, M4A, WAV, WebM, OGG
- Tamaño máximo de archivo: 25MB
- Detección automática de idioma

#### Texto a Voz
- Respuestas de IA leídas en voz alta
- 11 opciones de voz: alloy, ash, ballad, coral, echo, fable, nova, onyx, sage, shimmer, verse
- Configurable a nivel global y por instancia

#### Modo de Conversación en Tiempo Real
- Audio bidireccional usando OpenAI Realtime API (WebRTC)
- Conversación natural con detección automática de habla
- Detección de Actividad de Voz del Servidor (VAD) para turnos automáticos
- Respuestas de baja latencia

### 💬 Características del Chat

#### Gestión de Conversaciones
- Crear, guardar y continuar conversaciones
- Títulos de conversación autogenerados
- Búsqueda de texto completo en títulos y mensajes
- Historial de conversación con paginación
- Limpiar y eliminar conversaciones

#### Respuestas en Streaming
- Visualización de respuestas en tiempo real usando Server-Sent Events (SSE)
- Visualización progresiva de tokens
- Salida sin búfer para mejor UX

#### Modo Sin Conexión
- Mensajes en cola cuando está sin conexión
- Reintento automático cuando se restaura la conexión
- Indicador visual de estado sin conexión

### 📊 Analíticas e Informes

#### Analíticas por Instancia
- Total de conversaciones, mensajes, tokens, usuarios
- Visualización de actividad diaria
- Ranking de usuarios principales
- Filtrado por período (día, semana, mes, todo)

#### Informes de Curso
- Desglose de uso por instancia
- Seguimiento de actividad de estudiantes
- Consumo de tokens por usuario
- Accesible desde la navegación de Informes del Curso

#### Informes del Sitio
- Estadísticas de uso a nivel de sitio
- Desgloses por curso
- Seguimiento a nivel de usuario
- Accesible desde Administración del Sitio

### 🎨 Interfaz de Usuario

#### Soporte de Temas
- Alternador de modo Oscuro/Claro
- Propiedades CSS personalizadas para temas
- Diseño responsivo
- Interfaz optimizada para móviles

#### Mascotas Animadas
Seis personajes interactivos para engagement visual:
- Asistente INTEB (por defecto)
- Robot
- Gato
- Búho
- Clippy
- Bombilla

### 🔒 Seguridad

#### Protección de API Key
- Encriptación AES-256-CBC para claves API almacenadas
- Encriptación automática en primer acceso
- Soporte de API key a nivel de instancia

#### Limitación de Velocidad
- Algoritmo de ventana deslizante
- Límites basados en usuario (por defecto: 60 req/min)
- Límites basados en IP (por defecto: 30 req/min)
- Cabeceras de respuesta X-RateLimit-*

#### Protección de Entrada
- Prevención de inyección de prompts
- Sanitización de entrada con longitud máxima (10,000 caracteres)
- Protección CSRF via claves de sesión

### 🔧 Administración

#### Gestión de Tokens
- Límites configurables por usuario por período
- Opciones de período: hora, día, semana, mes
- Seguimiento de uso en tiempo real
- Cuenta regresiva en vivo hasta reinicio

#### Retención de Conversaciones
- Limpieza automática de conversaciones antiguas
- Período de retención configurable (días)
- Tarea programada para limpieza

---

## Requisitos

| Requisito | Versión |
|-----------|---------|
| Moodle | 4.1 o superior |
| PHP | 7.4 o superior |
| Extensiones PHP | cURL, OpenSSL |
| API Key de OpenAI | Requerida |

---

## Instalación

### Método 1: Descarga Directa

1. Descarga la última versión desde [GitHub](https://github.com/alonsoarias/mod_intebchat/releases)
2. Extrae en `/mod/intebchat` en tu instalación de Moodle
3. Navega a **Administración del sitio → Notificaciones**
4. Sigue el asistente de instalación

### Método 2: Git Clone

```bash
cd /ruta/a/moodle/mod
git clone https://github.com/alonsoarias/mod_intebchat.git intebchat
```

Luego visita **Administración del sitio → Notificaciones** para completar la instalación.

---

## Configuración

### Configuración Global

Navega a **Administración del sitio → Plugins → Módulos de actividad → INTEB Chat**

#### Configuración General

| Configuración | Descripción | Por defecto |
|---------------|-------------|-------------|
| API Key | Tu clave API de OpenAI (almacenada encriptada) | Requerida |
| Tipo de API | API de Chat Completions o Asistentes | Chat |
| Restringir Uso | Requerir inicio de sesión | Habilitado |
| Habilitar Registro | Almacenar historial de conversación | Deshabilitado |
| Permitir Config. por Instancia | Permitir API keys por instancia | Deshabilitado |

#### Configuración de Audio

| Configuración | Descripción | Por defecto |
|---------------|-------------|-------------|
| Habilitar Audio | Permitir entrada/salida de audio globalmente | Deshabilitado |
| Voz por Defecto | Voz para texto a voz | alloy |

#### Límites de Tokens

| Configuración | Descripción | Por defecto |
|---------------|-------------|-------------|
| Habilitar Límite de Tokens | Restringir uso de tokens por usuario | Deshabilitado |
| Máx. Tokens por Usuario | Tokens máximos por período | 10,000 |
| Período de Límite | Período de reinicio (hora/día/semana/mes) | día |

#### Valores por Defecto de Chat API

| Configuración | Descripción | Por defecto |
|---------------|-------------|-------------|
| Prompt del Sistema | Instrucciones del sistema por defecto | - |
| Fuente de Verdad | Base de conocimiento para contexto | - |
| Modelo | Modelo por defecto | gpt-4.1 |
| Temperatura | Aleatoriedad (0-2) | 0.7 |
| Máx. Tokens | Tokens máximos de respuesta | 1024 |
| Top P | Muestreo de núcleo (0-1) | 1.0 |
| Penalización de Frecuencia | Penalización de frecuencia (-2 a 2) | 0 |
| Penalización de Presencia | Penalización de presencia (-2 a 2) | 0 |

#### Limitación de Velocidad

| Configuración | Descripción | Por defecto |
|---------------|-------------|-------------|
| Habilitar Limitación | Activar límites de solicitudes | Deshabilitado |
| Límite de Usuario | Solicitudes por minuto por usuario | 60 |
| Límite de IP | Solicitudes por minuto por IP | 30 |

#### Configuración de Retención

| Configuración | Descripción | Por defecto |
|---------------|-------------|-------------|
| Habilitar Retención | Auto-limpieza de conversaciones antiguas | Deshabilitado |
| Días de Retención | Días para mantener conversaciones | 30 |

### Configuración por Instancia

Al agregar INTEB Chat a un curso, puedes configurar:

| Configuración | Descripción |
|---------------|-------------|
| Nombre | Nombre de la actividad |
| Descripción | Descripción de la actividad |
| Mostrar Etiquetas | Mostrar etiquetas de nombre en chat |
| Mascota | Seleccionar personaje animado |
| Habilitar Audio | Habilitar audio para esta instancia |
| Modo de Audio | texto, audio, ambos, o conversacional |
| Voz | Voz específica de la instancia |
| Instrucciones | Instrucciones personalizadas del asistente |
| Nombre del Asistente | Nombre a mostrar del asistente |
| Persistir Conversaciones | Guardar conversaciones entre sesiones |

---

## Uso

### Para Estudiantes

1. Haz clic en la actividad INTEB Chat en tu curso
2. Escribe tu mensaje en el campo de entrada o usa entrada de voz
3. Presiona Enter o haz clic en Enviar
4. Ve la respuesta de la IA en el área de chat
5. Usa la barra lateral para gestionar conversaciones

### Para Profesores

1. Agrega la actividad INTEB Chat a tu curso
2. Configura los ajustes de instancia según sea necesario
3. Accede a las analíticas via los informes del curso
4. Monitorea el uso de estudiantes y consumo de tokens

### Para Administradores

1. Configura los ajustes globales
2. Establece límites de tokens si es necesario
3. Habilita limitación de velocidad para protección
4. Accede a informes a nivel de sitio
5. Configura políticas de retención

---

## Capacidades

| Capacidad | Descripción | Roles por Defecto |
|-----------|-------------|-------------------|
| `mod/intebchat:view` | Ver y usar el chat | Estudiante, Profesor, Gestor |
| `mod/intebchat:addinstance` | Agregar nuevas instancias | Profesor Editor, Gestor |
| `mod/intebchat:viewownconversations` | Ver historial propio de conversaciones | Estudiante, Profesor, Gestor |
| `mod/intebchat:viewstudentconversations` | Ver conversaciones de estudiantes | Profesor, Gestor |
| `mod/intebchat:viewallconversations` | Ver todas las conversaciones | Gestor |
| `mod/intebchat:viewanalytics` | Acceder al dashboard de analíticas | Profesor, Gestor |
| `mod/intebchat:viewsitereport` | Acceder a informes del sitio | Gestor |
| `mod/intebchat:managetokenlimits` | Gestionar límites de tokens | Gestor |

---

## Referencia de API

### Servicios Web

| Función | Descripción | Tipo |
|---------|-------------|------|
| `mod_intebchat_create_conversation` | Crear nueva conversación | write |
| `mod_intebchat_load_conversation` | Cargar mensajes de conversación | read |
| `mod_intebchat_clear_conversation` | Limpiar historial de conversación | write |
| `mod_intebchat_update_conversation_title` | Actualizar título de conversación | write |
| `mod_intebchat_get_assistants` | Listar asistentes disponibles | read |
| `mod_intebchat_save_realtime_message` | Guardar mensaje en tiempo real | write |
| `mod_intebchat_get_site_report` | Obtener estadísticas del sitio | read |
| `mod_intebchat_get_course_report` | Obtener estadísticas del curso | read |

### Endpoints Internos

| Endpoint | Descripción |
|----------|-------------|
| `/mod/intebchat/api/completion.php` | Completaciones de chat |
| `/mod/intebchat/api/completion_stream.php` | Completaciones en streaming |
| `/mod/intebchat/api/realtime_token.php` | Sesiones de API en tiempo real |

---

## Esquema de Base de Datos

### Tablas

#### `intebchat`
Tabla principal de instancias del módulo de actividad.

#### `intebchat_log`
Historial de mensajes y seguimiento de tokens.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | int | Clave primaria |
| instanceid | int | FK de instancia del módulo |
| userid | int | FK de usuario |
| conversationid | int | FK de conversación |
| usermessage | text | Mensaje del usuario |
| airesponse | text | Respuesta de la IA |
| prompttokens | int | Tokens de entrada |
| completiontokens | int | Tokens de salida |
| totaltokens | int | Tokens combinados |
| timecreated | int | Marca de tiempo |

#### `intebchat_conversations`
Gestión de conversaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | int | Clave primaria |
| instanceid | int | FK de instancia del módulo |
| userid | int | FK de usuario |
| title | varchar(255) | Título de la conversación |
| preview | varchar(255) | Vista previa del último mensaje |
| threadid | varchar(255) | ID de hilo de OpenAI |
| messagecount | int | Conteo de mensajes |
| timecreated | int | Marca de tiempo de creación |
| timemodified | int | Última modificación |

#### `intebchat_token_usage`
Seguimiento de límite de tokens por usuario.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | int | Clave primaria |
| userid | int | FK de usuario |
| tokensused | int | Tokens en período actual |
| periodstart | int | Marca de tiempo de inicio del período |
| periodtype | varchar(10) | Tipo de período |

---

## Privacidad y Seguridad

### Cumplimiento GDPR

El plugin implementa la API de privacidad de Moodle:
- Los datos del usuario pueden ser exportados
- Los datos de conversación marcados como eliminables
- Metadatos de privacidad declarados para todo el almacenamiento de datos

### Almacenamiento de Datos

| Tipo de Dato | Ubicación | Retención |
|--------------|-----------|-----------|
| Conversaciones | Base de datos de Moodle | Configurable |
| Mensajes | Base de datos de Moodle | Configurable |
| Uso de tokens | Base de datos de Moodle | Basado en período |
| Archivos de audio | Directorio temporal de Moodle | Basado en sesión |
| IDs de hilo | Servidores de OpenAI | Política de OpenAI |

### Medidas de Seguridad

- Claves API encriptadas en reposo (AES-256-CBC)
- Protección CSRF en todos los endpoints
- Sanitización y validación de entrada
- Limitación de velocidad para protección contra DoS
- Validación de clave de sesión

---

## Solución de Problemas

### Problemas Comunes

#### Error "API Key Inválida"
- Verifica que tu clave API de OpenAI sea correcta
- Comprueba si la clave tiene créditos suficientes
- Asegúrate de que la clave tenga acceso a los modelos requeridos

#### Audio No Funciona
- Habilita audio en la configuración global primero
- Habilita audio para la instancia específica
- Verifica los permisos del micrófono del navegador
- Verifica que HTTPS esté habilitado (requerido para WebRTC)

#### Límite de Tokens Alcanzado
- Espera a que el período se reinicie (mostrado en la UI)
- Contacta al administrador para aumentar el límite
- Considera actualizar a un límite más alto

#### Errores de Límite de Velocidad
- Espera unos segundos y reintenta
- Contacta al administrador si persiste

### Modo de Depuración

Habilita la depuración de Moodle para ver mensajes de error detallados:

```php
// En config.php
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;
```

---

## Registro de Cambios

Consulta [CHANGELOG.md](CHANGELOG.md) para un historial detallado de todos los cambios.

---

## Contribuir

¡Las contribuciones son bienvenidas! Por favor sigue estos pasos:

1. Haz fork del repositorio
2. Crea una rama de característica (`git checkout -b feature/caracteristica-increible`)
3. Haz commit de tus cambios (`git commit -m 'Agregar característica increíble'`)
4. Haz push a la rama (`git push origin feature/caracteristica-increible`)
5. Abre un Pull Request

### Estándares de Código

- Sigue las guías de codificación de Moodle
- Incluye comentarios PHPDoc
- Agrega cadenas de idioma para todo el texto
- Escribe pruebas para nuevas características

---

## Licencia

Este plugin está licenciado bajo la [Licencia Pública General de GNU v3](https://www.gnu.org/licenses/gpl-3.0.html).

---

## Soporte

### Autor

**Alonso Arias**
- Email: soporte@ingeweb.co
- Sitio web: [ingeweb.co](https://ingeweb.co)

### Recursos

- [Repositorio de GitHub](https://github.com/alonsoarias/mod_intebchat)
- [Rastreador de Issues](https://github.com/alonsoarias/mod_intebchat/issues)
- [Directorio de Plugins de Moodle](https://moodle.org/plugins/mod_intebchat)

### Obtener Ayuda

1. Revisa la sección de [Solución de Problemas](#solución-de-problemas)
2. Busca en los [Issues de GitHub](https://github.com/alonsoarias/mod_intebchat/issues) existentes
3. Crea un nuevo issue con información detallada

---

## Agradecimientos

- OpenAI por proporcionar las APIs de IA
- Comunidad de Moodle por la excelente plataforma LMS
- Todos los contribuidores y testers

---

*Hecho con ❤️ para la comunidad de Moodle*
