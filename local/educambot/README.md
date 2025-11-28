# Nexo Bot - Fase 1

## Descripción

Plugin de chatbot básico para Moodle. Esta es la **Fase 1** del desarrollo, que incluye funcionalidad básica con un sistema de reglas simple.

## Características de Fase 1

- ✅ Sistema de reglas básico
- ✅ Matching simple (exacto + palabras clave)
- ✅ Endpoint AJAX para preguntas
- ✅ Panel de administración para gestionar reglas
- ✅ Permisos: `local/educambot:use` y `local/educambot:manage`

## Instalación

1. El plugin está ubicado en: `/local/educambot`
2. Acceder a `Administración del sitio > Notificaciones` para instalar/actualizar
3. La base de datos se creará automáticamente

## Uso

### Para Administradores

1. Ir a: `Administración del sitio > Plugins locales > Nexo Bot > Gestionar Reglas`
2. Hacer clic en "Agregar Regla"
3. Completar:
   - **Patrón de Pregunta**: La pregunta principal (ej: "¿Cómo me inscribo?")
   - **Palabras Clave**: Palabras adicionales (una por línea, ej: inscribir, matricular)
   - **Respuesta**: La respuesta que dará el bot
   - **Habilitado**: Marcar para activar la regla
4. Guardar

### Probar el Bot (vía AJAX)

Usar la consola del navegador o herramientas como Postman:

```javascript
// En la consola del navegador en cualquier página de Moodle:
fetch('/local/educambot/service.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'sesskey=' + M.cfg.sesskey + '&question=como me inscribo'
})
.then(r => r.json())
.then(data => console.log(data));
```

Respuesta esperada:
```json
{
    "success": true,
    "response": "Para inscribirte...",
    "ruleid": 1,
    "confidence": 0.85
}
```

## Estructura de Base de Datos

### Tabla: `local_educambot_rule`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | ID único |
| pattern | TEXT | Patrón de pregunta |
| keywords | TEXT | Palabras clave (una por línea) |
| response | TEXT | Respuesta |
| enabled | INT | 0/1 habilitado |
| timecreated | INT | Timestamp creación |
| timemodified | INT | Timestamp modificación |

## Algoritmo de Matching

El motor calcula un puntaje para cada regla:

1. **Coincidencia exacta**: +100 puntos
2. **Patrón contenido en pregunta**: +50 puntos
3. **Pregunta contenida en patrón**: +40 puntos
4. **Palabras en común**: hasta +30 puntos
5. **Palabras clave coinciden**: +20 puntos por keyword

La regla con mayor puntaje gana (si score > 0).

## Próximas Fases

- **Fase 2**: Widget de interfaz visual
- **Fase 3**: Sistema de logging
- **Fase 4**: NLP mejorado con similitud semántica
- **Fase 5**: Base de conocimiento estructurado
- **Fase 6**: Personalización y contexto
- **Fase 7**: NLP avanzado y aprendizaje

## Soporte

Para reportar problemas o sugerencias, contactar al equipo de desarrollo.
