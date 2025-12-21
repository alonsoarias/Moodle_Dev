# INFORME DE ANALISIS EXHAUSTIVO - EducamBot

**Fecha:** 2025-12-21
**Version analizada:** 2025012000 (3.0)

---

## 1. RESUMEN EJECUTIVO

El bot EducamBot tiene un problema critico de reconocimiento de lenguaje natural. El caso de prueba que mencionas es un ejemplo perfecto:

```
Usuario: "que asignaturas tengo inscritas?"
Bot: "Disculpa, no tengo una respuesta para eso."

Usuario: "que materias tengo matriculadas?"
Bot: "No estoy seguro de entender tu pregunta."
```

**Causa raiz:** Las palabras "asignaturas", "materias", "inscrito/a", "matriculado/a" NO estan incluidas en:
1. La lista de sinonimos de `text_normalizer.php`
2. Los keywords de los shortcuts en `shortcuts.json`
3. Los keywords de las reglas en `navigation.json`

---

## 2. ARQUITECTURA DEL BOT - FLUJO DE PROCESAMIENTO

```
Usuario escribe mensaje
        |
        v
+------------------+
|   service.php    |  <-- Recibe la peticion AJAX
+------------------+
        |
        v
+------------------+
|   external.php   |  <-- Procesa la llamada API
|   (get_response) |
+------------------+
        |
        v
+------------------+
|   engine.php     |  <-- Motor principal del bot
|   respond()      |
+------------------+
        |
        +-------> analyze_question()
        |               |
        |               +---> text_normalizer.analyze()
        |               +---> intent_detector.detect()
        |
        +-------> handle_special_intents() [greeting, farewell, thanks]
        |
        +-------> handle_follow_up() [si es pregunta de seguimiento]
        |
        +-------> get_filtered_rules() [obtiene reglas de BD]
        |
        +-------> score_rules() [puntua reglas vs pregunta]
        |               |
        |               +---> calculate_match_score()
        |                       |
        |                       +---> Coincidencia exacta (100 pts)
        |                       +---> Patron contiene (45 pts)
        |                       +---> Overlap de palabras (30 pts)
        |                       +---> Keywords match (25 pts)
        |                       +---> Sinonimos (20 pts)
        |                       +---> Levenshtein (15 pts)
        |
        +-------> build_response() o handle_no_match()
```

---

## 3. CODIGO HARDCODEADO IDENTIFICADO

### 3.1 text_normalizer.php (Lineas 42-139)

**PROBLEMA CRITICO:** Todas las estructuras de datos estan hardcodeadas como constantes PHP:

```php
// Linea 42-84: ABBREVIATIONS (hardcoded)
private const ABBREVIATIONS = [
    'q' => 'que',
    'xq' => 'porque',
    // ... 30+ entradas hardcodeadas
];

// Linea 86-97: STOPWORDS (hardcoded)
private const STOPWORDS = [
    'el', 'la', 'los', 'las', 'un', 'una',
    // ... hardcodeadas
];

// Linea 99-139: SYNONYMS (hardcoded) - **PROBLEMA PRINCIPAL**
private const SYNONYMS = [
    'curso' => ['curso', 'materia', 'asignatura', 'clase', 'modulo'],
    'cursos' => ['cursos', 'materias', 'asignaturas', 'clases', 'modulos'],
    // ...
];
```

**FALTA EN SYNONYMS:**
- "inscrito/a/os/as" -> no existe
- "matriculado/a/os/as" -> no existe
- "asignatura" esta como sinonimo de "curso" pero NO de "cursos"
- Falta mapeo inverso: cuando el usuario dice "asignaturas", no se resuelve

### 3.2 intent_detector.php (Lineas 75-219)

**PROBLEMA:** Patrones de intent hardcodeados:

```php
// Linea 75-156: INTENT_PATTERNS (hardcoded)
private const INTENT_PATTERNS = [
    self::INTENT_GREETING => [
        'patterns' => [
            '/^(hola|buenas?|buenos|hey|hi|hello|saludos|que tal)/ui',
            // regex hardcodeado
        ],
    ],
    // ...
];

// Linea 158-199: TOPIC_PATTERNS (hardcoded)
private const TOPIC_PATTERNS = [
    self::TOPIC_COURSE => [
        'keywords' => ['curso', 'cursos', 'materia', 'materias', 'asignatura'...],
        // FALTA: 'inscrito', 'inscrita', 'matriculado', 'matriculada'
    ],
];
```

### 3.3 engine.php (Lineas 232-340)

**PROBLEMA:** Respuestas de saludos, despedidas y agradecimientos hardcodeadas:

```php
// Linea 232-256: build_greeting_response()
$greetings = [
    "!{$timeGreeting}, {$userInfo['firstname']}! ?En que puedo ayudarte hoy?",
    "!Hola {$userInfo['firstname']}! Estoy aqui para ayudarte.",
    // ... HARDCODEADO EN ESPANOL
];

// Linea 265-282: build_farewell_response()
$farewells = [
    "!Hasta luego! Si necesitas algo mas, aqui estare.",
    // ... HARDCODEADO EN ESPANOL
];

// Linea 291-306: build_thanks_response()
$responses = [
    "!De nada! Me alegra haberte ayudado. ?Necesitas algo mas?",
    // ... HARDCODEADO EN ESPANOL
];

// Linea 322-339: build_empathetic_response()
$empathetic = [
    "Entiendo tu frustracion, {$userInfo['firstname']}. Dejame ayudarte con esto.",
    // ... HARDCODEADO EN ESPANOL
];

// Linea 707-711: handle_no_match()
$fallbackResponses = [
    "No estoy seguro de entender tu pregunta. ?Podrias reformularla?",
    "Hmm, no encontre informacion sobre eso. ?Puedes ser mas especifico?",
    "Disculpa, no tengo una respuesta para eso. ?Intentamos con otras palabras?",
    // ESTAS SON LAS RESPUESTAS QUE VE EL USUARIO CUANDO NO ENTIENDE
];
```

### 3.4 conversation_context.php (Lineas 494-532)

**PROBLEMA:** Sugerencias de seguimiento hardcodeadas:

```php
public function get_follow_up_suggestions(): array {
    $suggestions = [];

    switch ($this->state['topic']) {
        case 'assignments':
            $suggestions = [
                '?Cuando vence esta tarea?',
                '?Que otras tareas tengo pendientes?',
                // ... HARDCODEADO EN ESPANOL
            ];
            break;
        // ... mas casos hardcodeados
    }
}
```

### 3.5 navigation.json (Archivo completo)

**PROBLEMA:** Toda la base de conocimiento de navegacion esta en espanol hardcodeado:

```json
{
    "id": "courses_fallback",
    "pattern": "Mis cursos",
    "keywords": [
        "mis cursos", "ver cursos", "cursos inscritos", "cursos",
        "en que cursos estoy", "lista de cursos", "cursos matriculados"
    ],
    "response": "Puedes ver todos tus <strong>cursos</strong> en:..."
}
```

**FALTAN KEYWORDS CRITICOS:**
- "asignaturas inscritas"
- "materias matriculadas"
- "que asignaturas tengo"
- "que materias tengo"
- "mis asignaturas"
- "mis materias"

---

## 4. ANALISIS DEL PROBLEMA ESPECIFICO

### Pregunta del usuario: "que asignaturas tengo inscritas?"

**Paso 1 - Normalizacion (text_normalizer.php:164-183):**
```
Input:  "que asignaturas tengo inscritas?"
Output: "que asignaturas tengo inscritas"
```
- Se convierte a minusculas
- Se elimina el signo de pregunta
- NO se expande ninguna abreviacion

**Paso 2 - Analisis (text_normalizer.php:191-208):**
```
words: ["que", "asignaturas", "tengo", "inscritas"]
keywords: ["asignaturas", "tengo", "inscritas"] (sin stopwords)
```

**Paso 3 - Deteccion de intent (intent_detector.php:239-249):**
```
intent: INTENT_QUERY (detecta "que" al inicio)
topic: TOPIC_GENERAL (no encuentra match con ningun topic)
```

**PROBLEMA:** La palabra "asignaturas" NO esta en TOPIC_COURSE.keywords:
```php
self::TOPIC_COURSE => [
    'keywords' => ['curso', 'cursos', 'materia', 'materias', 'asignatura', 'clase',
                  'clases', 'modulo', 'contenido', 'tema', 'temas', 'unidad', 'leccion'],
],
```
- "asignatura" (singular) SI esta
- "asignaturas" (plural) NO esta
- "inscritas" NO esta en ningun lado

**Paso 4 - Scoring de reglas (engine.php:510-530):**

Para la regla "courses_fallback" con keywords ["mis cursos", "ver cursos", "cursos inscritos"...]:
- Exact match: 0 (no coincide "que asignaturas tengo inscritas" con "Mis cursos")
- Pattern contains: 0 (no coincide)
- Word overlap: ~10 (solo "que" podria coincidir en algun patron)
- Keyword match: 0 ("asignaturas inscritas" no esta en keywords)
- Synonym match: ~20 SI el sinonimo funcionara, pero...

**PROBLEMA EN SINONIMOS (text_normalizer.php:99-139):**
```php
'curso' => ['curso', 'materia', 'asignatura', 'clase', 'modulo'],
'cursos' => ['cursos', 'materias', 'asignaturas', 'clases', 'modulos'],
```

El sistema busca si "asignaturas" es sinonimo de las keywords de la regla.
Las keywords de la regla son: "mis cursos", "ver cursos", "cursos inscritos"...

Cuando se hace `are_synonyms("asignaturas", "cursos")`:
1. Busca "asignaturas" en SYNONYMS -> No existe como clave
2. Busca en arrays de valores -> Encuentra en 'cursos' => [..., 'asignaturas', ...]
3. Devuelve ['cursos', 'materias', 'asignaturas', 'clases', 'modulos']
4. Verifica si "cursos" esta en ese array -> SI

**PERO** el problema es que las keywords son frases completas ("mis cursos", "cursos inscritos"), no palabras sueltas.

La funcion `calculate_match_score` en linea 571-586:
```php
foreach ($keywords as $keyword) {
    $normalizedKeyword = $this->normalizer->normalize($keyword);
    if (!empty($normalizedKeyword)) {
        // Direct match.
        if (mb_strpos($question, $normalizedKeyword) !== false) {
            $score += self::SCORE_WEIGHTS['keyword_match'];  // 25 pts
        }
        // Synonym match.
        $keywordResult = $this->normalizer->contains_keywords($question, [$normalizedKeyword], true);
        if ($keywordResult['found'] && mb_strpos($question, $normalizedKeyword) === false) {
            $score += self::SCORE_WEIGHTS['synonym_match'];  // 20 pts
        }
    }
}
```

El problema es que busca "mis cursos" en "que asignaturas tengo inscritas":
- `mb_strpos("que asignaturas tengo inscritas", "mis cursos")` = FALSE
- No hace match palabra por palabra

---

## 5. LISTA COMPLETA DE CODIGO HARDCODEADO

| Archivo | Lineas | Tipo | Descripcion |
|---------|--------|------|-------------|
| text_normalizer.php | 42-84 | const ABBREVIATIONS | Abreviaciones espanol hardcodeado |
| text_normalizer.php | 86-97 | const STOPWORDS | Stopwords espanol hardcodeado |
| text_normalizer.php | 99-139 | const SYNONYMS | Sinonimos incompletos hardcodeado |
| intent_detector.php | 75-156 | const INTENT_PATTERNS | Patrones regex hardcodeados |
| intent_detector.php | 158-199 | const TOPIC_PATTERNS | Topics y keywords hardcodeados |
| intent_detector.php | 201-219 | const SENTIMENT_PATTERNS | Sentimientos hardcodeados |
| intent_detector.php | 418-420 | $urgentKeywords | Keywords urgencia hardcodeados |
| engine.php | 232-256 | build_greeting_response() | Saludos hardcodeados |
| engine.php | 265-282 | build_farewell_response() | Despedidas hardcodeadas |
| engine.php | 291-306 | build_thanks_response() | Agradecimientos hardcodeados |
| engine.php | 322-339 | build_empathetic_response() | Respuestas empaticas hardcodeadas |
| engine.php | 334-338 | options[] | Opciones de respuesta hardcodeadas |
| engine.php | 707-711 | $fallbackResponses | Respuestas fallback hardcodeadas |
| engine.php | 797-822 | get_topic_suggestions() | Sugerencias por topic hardcodeadas |
| engine.php | 829-834 | get_quick_start_options() | Opciones inicio hardcodeadas |
| conversation_context.php | 265-268 | $prompts | Prompts de seguimiento hardcodeados |
| conversation_context.php | 326 | $shortResponses | Respuestas cortas hardcodeadas |
| conversation_context.php | 360-369 | $pronounMap | Mapeo pronombres hardcodeado |
| conversation_context.php | 494-532 | get_follow_up_suggestions() | Sugerencias hardcodeadas |
| navigation.json | Todo | JSON | Toda la base de conocimiento |
| shortcuts.json | Todo | JSON | Todos los shortcuts |

---

## 6. RECOMENDACIONES DE MEJORA

### 6.1 Solucion Inmediata - Agregar Keywords Faltantes

**text_normalizer.php - Agregar sinonimos:**
```php
'inscrito' => ['inscrito', 'inscrita', 'inscritos', 'inscritas', 'matriculado', 'matriculada', 'matriculados', 'matriculadas', 'registrado', 'registrada'],
'asignatura' => ['asignatura', 'asignaturas', 'materia', 'materias', 'curso', 'cursos', 'clase', 'clases'],
```

**navigation.json - Agregar keywords a courses_fallback:**
```json
"keywords": [
    "mis cursos", "ver cursos", "cursos inscritos", "cursos",
    "en que cursos estoy", "lista de cursos", "cursos matriculados",
    "mis asignaturas", "asignaturas inscritas", "que asignaturas tengo",
    "mis materias", "materias matriculadas", "que materias tengo",
    "materias inscritas", "asignaturas matriculadas"
]
```

### 6.2 Solucion a Mediano Plazo - Externalizar Configuracion

1. **Crear tabla `local_educambot_synonym`:**
```sql
CREATE TABLE {local_educambot_synonym} (
    id BIGINT PRIMARY KEY,
    word VARCHAR(100),
    synonyms TEXT,
    lang VARCHAR(10),
    enabled TINYINT DEFAULT 1
);
```

2. **Crear tabla `local_educambot_abbreviation`:**
```sql
CREATE TABLE {local_educambot_abbreviation} (
    id BIGINT PRIMARY KEY,
    abbreviation VARCHAR(20),
    expansion VARCHAR(100),
    lang VARCHAR(10)
);
```

3. **Mover strings a archivos de idioma:**
```php
// lang/es/local_educambot.php
$string['greeting_morning'] = '!Buenos dias, {$a}! ?En que puedo ayudarte hoy?';
$string['greeting_afternoon'] = '!Buenas tardes, {$a}! ?En que puedo ayudarte?';
$string['farewell_1'] = '!Hasta luego! Si necesitas algo mas, aqui estare.';
$string['fallback_1'] = 'No estoy seguro de entender tu pregunta. ?Podrias reformularla?';
```

### 6.3 Solucion a Largo Plazo - Mejora del Algoritmo

1. **Implementar matching por palabras individuales:**
```php
// En lugar de buscar "mis cursos" en "que asignaturas tengo"
// Buscar cada palabra: "asignaturas" -> sinonimo de "cursos"
```

2. **Implementar stemming/lemmatizacion:**
```php
// "inscritas" -> "inscrit" (raiz)
// "matriculadas" -> "matricul" (raiz)
// Comparar raices en lugar de palabras completas
```

3. **Agregar fuzzy matching mejorado:**
```php
// Usar n-grams para detectar similitudes parciales
// "asignaturas" vs "asignatura" -> 95% similar
```

---

## 7. ARCHIVOS QUE REQUIEREN MODIFICACION

1. **text_normalizer.php** - Externalizar SYNONYMS, ABBREVIATIONS, STOPWORDS
2. **intent_detector.php** - Externalizar INTENT_PATTERNS, TOPIC_PATTERNS
3. **engine.php** - Mover strings a archivos de idioma
4. **conversation_context.php** - Mover strings a archivos de idioma
5. **navigation.json** - Agregar keywords faltantes
6. **shortcuts.json** - Agregar keywords faltantes
7. **lang/es/local_educambot.php** - Agregar todas las strings

---

## 8. CONCLUSIONES

El bot tiene una arquitectura solida pero sufre de:

1. **Vocabulario limitado:** Los sinonimos y keywords no cubren todas las formas en que los usuarios hacen preguntas
2. **Codigo hardcodeado:** Todo el contenido en espanol esta embebido en el codigo PHP
3. **Matching poco flexible:** El sistema busca frases completas en lugar de palabras individuales
4. **Sin capacidad de aprendizaje:** El sistema no puede aprender de preguntas no reconocidas

**Prioridad de correccion:**
1. URGENTE: Agregar keywords y sinonimos faltantes en navigation.json y text_normalizer.php
2. IMPORTANTE: Externalizar strings a archivos de idioma
3. DESEABLE: Mejorar algoritmo de matching con stemming y n-grams
