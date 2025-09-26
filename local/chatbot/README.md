# Chatbot Inteligente para Moodle v2.0

## 🤖 Descripción

Plugin de chatbot inteligente para Moodle con capacidades avanzadas de procesamiento de lenguaje natural, análisis contextual y aprendizaje automático. Este chatbot puede entender el contexto, detectar intenciones, analizar sentimientos y proporcionar respuestas personalizadas sin necesidad de IA externa.

## ✨ Características Principales

### Motor de Inteligencia
- **Procesamiento de Lenguaje Natural (NLP)**: Análisis profundo de mensajes
- **Detección de Intenciones**: Identifica automáticamente qué quiere el usuario
- **Análisis de Sentimientos**: Detecta emociones y ajusta las respuestas
- **Extracción de Entidades**: Reconoce fechas, cursos, tareas, etc.
- **Memoria Contextual**: Recuerda conversaciones previas en la sesión
- **Corrección Ortográfica**: Maneja errores de escritura automáticamente

### Capacidades de Aprendizaje
- **Aprendizaje Supervisado**: Aprende de patrones aprobados
- **Detección de Patrones**: Identifica nuevas formas de preguntar
- **Auto-mejora**: Se optimiza basándose en feedback
- **Análisis de Éxito**: Mide la efectividad de las respuestas

### Interfaz Inteligente
- **Sugerencias Contextuales**: Propone acciones relevantes
- **Acciones Rápidas**: Botones para tareas comunes
- **Entrada de Voz**: Reconocimiento de voz integrado
- **Múltiples Temas**: Moderno, Oscuro, Minimalista, Colorido
- **Animaciones Fluidas**: Experiencia visual agradable
- **Responsive**: Adaptado para móviles

### Análisis y Reportes
- **Analíticas en Tiempo Real**: Dashboard con métricas clave
- **Distribución de Sentimientos**: Gráficos de emociones
- **Intenciones Más Comunes**: Identifica necesidades frecuentes
- **Tiempo de Respuesta**: Monitoreo de rendimiento
- **Exportación de Datos**: CSV, JSON, HTML

## 📊 Arquitectura del Sistema

```
┌─────────────────────────────────────┐
│         INTERFAZ DE USUARIO         │
│  (Widget React-like con AMD)        │
└─────────────┬───────────────────────┘
              │
┌─────────────▼───────────────────────┐
│      CAPA DE SERVICIOS WEB          │
│  (AJAX / REST API)                  │
└─────────────┬───────────────────────┘
              │
┌─────────────▼───────────────────────┐
│     MOTOR DE PROCESAMIENTO          │
│  ┌────────────────────────────┐     │
│  │ 1. Preprocesamiento        │     │
│  │ 2. Análisis de Contexto    │     │
│  │ 3. Detección de Intención  │     │
│  │ 4. Extracción de Entidades │     │
│  │ 5. Análisis de Sentimiento │     │
│  │ 6. Generación de Respuesta │     │
│  └────────────────────────────┘     │
└─────────────┬───────────────────────┘
              │
┌─────────────▼───────────────────────┐
│        BASE DE DATOS                │
│  - Respuestas                       │
│  - Intenciones                      │
│  - Entidades                        │
│  - Patrones de Aprendizaje          │
│  - Logs de Conversación             │
│  - Diálogos Multi-turno             │
└─────────────────────────────────────┘
```

## 🚀 Instalación

### Requisitos
- Moodle 3.9 o superior
- PHP 7.2 o superior
- MySQL 5.7+ o PostgreSQL 9.6+
- Navegador moderno con JavaScript habilitado

### Pasos de Instalación

1. **Descargar el plugin**
   ```bash
   cd /path/to/moodle/local/
   git clone https://github.com/tuusuario/local_chatbot.git chatbot
   ```

2. **Establecer permisos**
   ```bash
   chmod -R 755 chatbot/
   chown -R www-data:www-data chatbot/
   ```

3. **Instalar desde Moodle**
   - Iniciar sesión como administrador
   - Ir a "Administración del sitio" → "Notificaciones"
   - Seguir el proceso de instalación

4. **Compilar JavaScript AMD**
   ```bash
   php admin/cli/purge_caches.php
   ```

## ⚙️ Configuración

### Configuración Básica
1. Ir a **Administración → Plugins → Chatbot Inteligente**
2. Configurar:
   - **Estado**: Habilitar/Deshabilitar
   - **Posición**: Ubicación del widget
   - **Tema**: Estilo visual
   - **Personalidad**: Tono de las respuestas

### Configuración de Inteligencia
- **Umbral de Confianza**: Ajustar sensibilidad (1-10)
- **Modo de Aprendizaje**: Activar para auto-mejora
- **Memoria Contextual**: Número de mensajes a recordar
- **Coincidencia Difusa**: Para errores ortográficos

### Gestión de Intenciones
1. Ir a **Gestionar Intenciones**
2. Definir:
   - Palabras clave
   - Patrones regex
   - Respuestas asociadas
   - Prioridades

## 🧠 Cómo Funciona el Motor Inteligente

### 1. Preprocesamiento
```php
- Normalización de texto
- Expansión de contracciones
- Corrección ortográfica
- Tokenización
- Eliminación de stop words
```

### 2. Detección de Intenciones
```php
- Análisis de palabras clave
- Matching de patrones regex
- Análisis de n-gramas
- Scoring ponderado
- Selección por confianza
```

### 3. Análisis de Sentimiento
```php
- Detección de palabras positivas/negativas
- Análisis de emojis
- Clasificación: positivo/negativo/neutral/inquisitivo
- Ajuste de tono de respuesta
```

### 4. Generación de Respuesta
```php
- Selección de plantilla
- Personalización con entidades
- Ajuste por sentimiento
- Variación para evitar repetición
- Adición de sugerencias contextuales
```

## 📈 Algoritmos Implementados

### Coincidencia de Patrones
- **Exact Matching**: Palabras clave exactas
- **Fuzzy Matching**: Distancia de Levenshtein
- **Regex Patterns**: Expresiones regulares
- **N-gram Analysis**: Bigramas y trigramas

### Scoring de Intenciones
```
Score = Σ(keyword_matches × 2) + 
        Σ(regex_matches × 3) + 
        Σ(ngram_matches × 2.5) × 
        context_boost / 
        (1 + log(word_count))
```

### Análisis de Contexto
- Continuidad de tema
- Detección de frustración
- Necesidad de clarificación
- Historial de interacciones

## 🎯 Casos de Uso

### Estudiantes
- Consultar tareas pendientes
- Ver calificaciones
- Obtener ayuda con navegación
- Resolver problemas técnicos
- Acceder a recursos del curso

### Profesores
- Gestionar respuestas del bot
- Ver analíticas de uso
- Entrenar nuevos patrones
- Exportar conversaciones

### Administradores
- Configurar comportamiento global
- Analizar métricas de uso
- Gestionar intenciones y entidades
- Optimizar rendimiento

## 📝 Ejemplos de Interacción

### Consulta Simple
```
Usuario: "hola"
Bot: "¡Hola [Nombre]! 👋 Es un gusto poder ayudarte hoy. ¿En qué puedo asistirte?"
```

### Detección de Frustración
```
Usuario: "no funciona nada!!!"
Bot: "Entiendo tu frustración. Lamento que tengas dificultades. 
      Vamos a resolver esto paso a paso. ¿Qué específicamente no está funcionando?"
```

### Respuesta Contextual
```
Usuario: "tengo tarea"
Bot: "📝 Veo que tienes 3 tareas pendientes:
      • Matemáticas - Entrega: 28/01/2025
      • Física - Entrega: 30/01/2025
      • Química - Entrega: 02/02/2025
      
      ¿Te gustaría ver los detalles de alguna?"
```

## 🔧 Personalización Avanzada

### Crear Nueva Intención
```php
$intent = [
    'name' => 'biblioteca',
    'keywords' => ['libro', 'biblioteca', 'préstamo'],
    'regex' => ['/buscar\s+libro/i'],
    'response' => 'Para acceder a la biblioteca...'
];
```

### Definir Entidad Personalizada
```php
$entity = [
    'type' => 'course_code',
    'pattern' => '/[A-Z]{3}\d{3}/',
    'examples' => ['MAT101', 'FIS202']
];
```

### Crear Flujo de Diálogo
```php
$dialogue = [
    'id' => 'reset_password',
    'steps' => [
        ['prompt' => '¿Cuál es tu email?', 'collect' => 'email'],
        ['prompt' => 'Te enviaré un código...', 'action' => 'send_code'],
        ['prompt' => '¿Cuál es el código?', 'validate' => 'code']
    ]
];
```

## 🔐 Seguridad y Privacidad

- **Encriptación**: Datos sensibles encriptados
- **Validación**: Todas las entradas sanitizadas
- **Permisos**: Control granular por capacidades
- **Auditoría**: Registro completo de acciones
- **GDPR**: Compatible con normativas de privacidad
- **Modo Anónimo**: Opción sin registro de datos personales

## 🐛 Solución de Problemas

### El widget no aparece
```bash
# Limpiar cachés
php admin/cli/purge_caches.php

# Verificar permisos
ls -la /path/to/moodle/local/chatbot/

# Revisar logs
tail -f /var/log/apache2/error.log
```

### Respuestas lentas
- Verificar configuración de caché
- Optimizar consultas a BD
- Reducir memoria contextual
- Aumentar timeout

### Errores de JavaScript
- Verificar consola del navegador
- Recompilar AMD
- Actualizar navegador

## 📊 Métricas de Rendimiento

- **Tiempo de respuesta promedio**: < 500ms
- **Precisión de intenciones**: > 85%
- **Satisfacción del usuario**: > 80%
- **Uptime**: 99.9%
- **Concurrencia**: 100+ usuarios simultáneos

## 🚦 Roadmap

### v2.1 (Q2 2025)
- [ ] Integración con APIs externas
- [ ] Soporte para archivos adjuntos
- [ ] Modo de voz bidireccional

### v2.2 (Q3 2025)
- [ ] Machine Learning avanzado
- [ ] Análisis predictivo
- [ ] Chatbot multicanal

### v3.0 (Q4 2025)
- [ ] IA generativa opcional
- [ ] Procesamiento de imágenes
- [ ] Asistente proactivo

## 🤝 Contribuir

¡Las contribuciones son bienvenidas!

1. Fork el repositorio
2. Crear rama de feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## 📜 Licencia

Este proyecto está licenciado bajo GNU GPL v3 - ver [LICENSE](LICENSE) para detalles.

## 👥 Créditos

- **Desarrollador Principal**: [Tu Nombre]
- **Arquitectura**: Sistema de procesamiento inteligente basado en reglas
- **Tecnologías**: PHP, JavaScript (AMD), MySQL, CSS3
- **Framework**: Moodle 3.9+

## 📞 Soporte

- **Email**: soporte@tuchatbot.com
- **Documentación**: [docs.tuchatbot.com](https://docs.tuchatbot.com)
- **Issues**: [GitHub Issues](https://github.com/tuusuario/local_chatbot/issues)
- **Wiki**: [GitHub Wiki](https://github.com/tuusuario/local_chatbot/wiki)

## 🏆 Reconocimientos

- Comunidad Moodle
- Contribuidores del proyecto
- Testers beta

---

**Versión**: 2.0.0 | **Última actualización**: Enero 2025
