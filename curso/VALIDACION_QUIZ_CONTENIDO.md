# Validacion de Contenido de Autoevaluaciones

Este documento compara el contenido fuente (quiz_data_1_5.php) con el contenido generado en el MBZ (questions.xml).

---

## CAPITULO 1: Desarrollo Neurologico Infantil

### Quiz 1 - Estructura en MBZ
- **Activity ID:** 9
- **Module ID:** 9
- **Context ID:** 108
- **Question Category ID:** 1001
- **Question Bank Entry IDs:** 1-10
- **usingcontextid en question_reference:** 108 (contexto del modulo)

### Preguntas Capitulo 1

| # | Pregunta | Respuesta Correcta | En MBZ |
|---|----------|-------------------|--------|
| 1 | Cual es el beneficio principal de la poda sinaptica? | Precision en las conexiones neuronales | SI - question_bank_entry id="1" |
| 2 | Cual es la caracteristica del desarrollo del hemisferio izquierdo? | Alargamiento secuencial | SI - question_bank_entry id="2" |
| 3 | Que estructuras cerebrales se ven afectadas por el estres temprano? | Neocortex, hipocampo y amigdala | SI - question_bank_entry id="3" |
| 4 | Cual es la funcion principal del Area de Broca? | Produccion del lenguaje | SI - question_bank_entry id="4" |
| 5 | A los 2 anos, la densidad sinaptica es: | 50% superior a adultos | SI - question_bank_entry id="5" |
| 6 | Existen diferencias de genero en el desarrollo del hipocampo y la amigdala: | Si, existen diferencias documentadas | SI - question_bank_entry id="6" |
| 7 | El Area de Wernicke corresponde a que area de Brodmann? | Area 22 | SI - question_bank_entry id="7" |
| 8 | A los 2 anos el movimiento voluntario se desarrolla gracias a: | Mielinizacion de la via piramidal | SI - question_bank_entry id="8" |
| 9 | Los picos de aceleracion del desarrollo cerebral finalizan aproximadamente a los: | 14 anos | SI - question_bank_entry id="9" |
| 10 | El giro angular tiene como funcion principal: | Asociar informacion visual y auditiva | SI - question_bank_entry id="10" |

**ESTADO CAPITULO 1:** RESTAURA CORRECTAMENTE

---

## CAPITULO 2: Funciones Emocionales

### Quiz 2 - Estructura en MBZ
- **Activity ID:** 16
- **Module ID:** 16
- **Context ID:** 115
- **Question Category ID:** 1002
- **Question Bank Entry IDs:** 11-20
- **usingcontextid en question_reference:** 115 (contexto del modulo)

### Preguntas Capitulo 2

| # | Pregunta | Respuesta Correcta | En MBZ |
|---|----------|-------------------|--------|
| 1 | Cual es la consecuencia de una lesion en el hemisferio izquierdo en un adulto? | Depresion y reacciones catastroficas | SI - question_bank_entry id="11" |
| 2 | Cual afirmacion describe correctamente el patron de maduracion cerebral? | El cerebro madura de atras (sensorial) hacia adelante (motor) | SI - question_bank_entry id="12" |
| 3 | Que dos mecanismos pueden causar depresion en ninos y adultos? | Hipoactivacion de regiones frontales o hiperactivacion del hemisferio derecho | SI - question_bank_entry id="13" |
| 4 | Que funcion tiene la region anterior del lobulo temporal en la percepcion de expresiones faciales? | Entiende y recuerda el caracter de dichas expresiones | SI - question_bank_entry id="14" |
| 5 | A que edad ocurre la virilizacion del cuerpo calloso? | 8 anos | SI - question_bank_entry id="15" |
| 6 | Cual es la caracteristica unica del sentido del olfato? | No va al talamo sino directamente a la corteza | SI - question_bank_entry id="16" |
| 7 | A que edad comienza la habilidad de demorar la recompensa segun Mischel? | 4 anos | SI - question_bank_entry id="17" |
| 8 | Los lobulos parietales son funcionales: | Antes del nacimiento | SI - question_bank_entry id="18" |
| 9 | El efecto de modulacion se refiere a: | El control del hemisferio derecho sobre el izquierdo | SI - question_bank_entry id="19" |
| 10 | Las funciones ejecutivas incluyen: | Procesos cognitivos, emocionales y de regulacion de respuestas conductuales | SI - question_bank_entry id="20" |

**ESTADO CAPITULO 2:** ERROR AL RESTAURAR
- Error: `Invalid context id specified context::instance_by_id()`
- Warning: `Undefined property: stdClass::$originalqtype`

---

## COMPARATIVA ESTRUCTURAL

### Archivos del Quiz 1 (FUNCIONA)

```
activities/quiz_9/
├── quiz.xml          (activity id="9" moduleid="9" contextid="108")
├── module.xml        (module id="9" sectionid="2")
├── grades.xml        (grade_item id="9")
├── inforef.xml       (<fileref></fileref><question_categoryref></question_categoryref>)
├── roles.xml
└── comments.xml
```

### Archivos del Quiz 2 (FALLA)

```
activities/quiz_16/
├── quiz.xml          (activity id="16" moduleid="16" contextid="115")
├── module.xml        (module id="16" sectionid="3")
├── grades.xml        (grade_item id="16")
├── inforef.xml       (<fileref></fileref><question_categoryref></question_categoryref>)
├── roles.xml
└── comments.xml
```

### Questions.xml (ambos quizzes)

```xml
<!-- Capitulo 1 -->
<question_category id="1001">
  <name>Preguntas Capitulo 1</name>
  <contextid>50</contextid>           <!-- Contexto del curso -->
  <contextlevel>50</contextlevel>     <!-- CONTEXT_COURSE -->
  <contextinstanceid>1</contextinstanceid>
  <question_bank_entries>
    <question_bank_entry id="1">...</question_bank_entry>
    ...
    <question_bank_entry id="10">...</question_bank_entry>
  </question_bank_entries>
</question_category>

<!-- Capitulo 2 -->
<question_category id="1002">
  <name>Preguntas Capitulo 2</name>
  <contextid>50</contextid>           <!-- Contexto del curso -->
  <contextlevel>50</contextlevel>     <!-- CONTEXT_COURSE -->
  <contextinstanceid>1</contextinstanceid>
  <question_bank_entries>
    <question_bank_entry id="11">...</question_bank_entry>
    ...
    <question_bank_entry id="20">...</question_bank_entry>
  </question_bank_entries>
</question_category>
```

---

## ANALISIS DEL PROBLEMA

### Elementos Identicos entre Quiz 1 y Quiz 2:
1. Estructura XML del quiz.xml
2. Estructura XML del module.xml
3. Estructura del question_category (contextid=50, contextlevel=50)
4. Estructura del question_bank_entry
5. Estructura del question_instance con question_reference
6. El usingcontextid usa el contexto del modulo (108 vs 115)

### Hipotesis del Problema:

**El Quiz 1 restaura y el Quiz 2 no, a pesar de tener estructuras identicas.**

Posibles causas:
1. **Orden de restauracion:** Quiz 1 se restaura primero y establece contextos
2. **IDs de contexto:** El contexto 115 podria no existir durante la restauracion
3. **Referencias cruzadas:** Algun archivo de referencia falta o esta incorrecto
4. **question_categoryref vacio:** El inforef.xml de ambos quizzes tiene question_categoryref vacio

---

## CONTENIDO VERIFICADO

- Total preguntas Capitulo 1: 10 ✓
- Total preguntas Capitulo 2: 10 ✓
- Todas las preguntas tienen 4 opciones: ✓
- Todas las preguntas tienen 1 respuesta correcta: ✓
- Contenido coincide con fuente PHP: ✓

---

Fecha de generacion: 2026-01-17
