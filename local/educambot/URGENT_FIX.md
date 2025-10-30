# 🚨 SOLUCIÓN URGENTE - Bot No Responde

**Fecha:** 2025-10-30
**Versión Afectada:** v2.1.1, v2.1.2
**Síntoma:** Bot responde "No encontré una respuesta. Avisaré al equipo administrador." a TODAS las preguntas

---

## ⚡ CAUSA DEL PROBLEMA

El **seed de preguntas comunes NO se ejecutó** en tu instalación. Esto significa que la base de datos NO tiene las 9 preguntas comunes preconfiguradas, por lo que el bot no tiene respuestas que dar.

**¿Por qué pasó esto?**
- Si actualizaste de v2.1.1 (2025103004) a v2.1.2 (2025103005), el bloque de upgrade para v2025103004 no se ejecutó (porque ya lo tenías)
- El seed solo estaba configurado para ejecutarse en el bloque de v2025103004

---

## ✅ SOLUCIÓN INMEDIATA (3 Opciones)

### **Opción 1: CLI Script (RECOMENDADO - MÁS RÁPIDO)**

Ejecuta este comando desde el directorio raíz de Moodle:

```bash
php local/educambot/cli/seed_common_questions.php
```

**Salida esperada:**
```
========================================
EDUCAM BOT - SEED COMMON QUESTIONS
========================================

Executing seed...

✅ SEED COMPLETED SUCCESSFULLY

Results:
  - Created: 9 new rules
  - Updated: 0 existing rules
  - Total: 9 rules processed

Purging rules cache...
✅ Cache purged
```

---

### **Opción 2: Forzar Upgrade de Moodle**

1. Edita `local/educambot/version.php`
2. Cambia temporalmente la versión:
   ```php
   $plugin->version = 2025103003;  // Temporalmente más baja
   ```
3. Visita: **Site Administration → Notifications**
4. Moodle ejecutará el upgrade
5. Regresa la versión a:
   ```php
   $plugin->version = 2025103005;  // Versión correcta
   ```
6. Vuelve a visitar: **Site Administration → Notifications**

---

### **Opción 3: Código PHP Directo (Desde Cualquier Página PHP de Moodle)**

Crea un archivo temporal `fix_bot.php` en la raíz de Moodle:

```php
<?php
require_once('config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

require_once($CFG->dirroot . '/local/educambot/classes/local/setup/common_questions_seed.php');

echo '<h2>Ejecutando Seed...</h2>';

$result = \local_educambot\local\setup\common_questions_seed::seed();

echo '<p><strong>✅ Completado!</strong></p>';
echo '<ul>';
echo '<li>Creadas: ' . $result['created'] . '</li>';
echo '<li>Actualizadas: ' . $result['updated'] . '</li>';
echo '<li>Total: ' . $result['total'] . '</li>';
echo '</ul>';

// Purge cache
\cache::make('local_educambot', 'rules')->purge();
echo '<p>✅ Cache purgado</p>';

echo '<p><strong>Ahora prueba el bot con: "¿Cómo enviar un trabajo?"</strong></p>';
```

Luego visita: `https://tu-moodle.com/fix_bot.php`

**⚠️ IMPORTANTE:** Elimina `fix_bot.php` después de ejecutarlo.

---

## 🔍 VERIFICAR QUE FUNCIONÓ

### **Paso 1: Diagnóstico Rápido**

```bash
php local/educambot/cli/diagnose_bot.php
```

**Busca estas líneas:**
```
2. RULES TABLE
   Table exists: ✅ YES
   Total rules: 9
   Enabled rules: 9
   ✅ Rules found

5. TEST QUESTION
   Testing: '¿Cómo enviar un trabajo?'

   Response found: ✅ YES
   Confidence: 0.85
   Rule ID: 1
```

---

### **Paso 2: Prueba Manual**

Ve al chatbot de Educam Bot y prueba estas preguntas:

```
✅ ¿Cómo enviar un trabajo?
✅ cómo subir una tarea
✅ enviar assignment
✅ como ver mis notas
✅ ver calificaciones
✅ check grades
```

**Esperado:** El bot debe dar respuestas detalladas con instrucciones paso a paso.

---

## 🛠️ SI AÚN NO FUNCIONA

### **1. Verifica la Base de Datos Directamente**

```sql
SELECT COUNT(*) FROM mdl_local_educambot_rule WHERE enabled = 1;
```

**Esperado:** 9 o más reglas

Si el resultado es 0:
```sql
-- Ver si la tabla existe
SHOW TABLES LIKE 'mdl_local_educambot_rule';

-- Ver si hay reglas deshabilitadas
SELECT COUNT(*) FROM mdl_local_educambot_rule;
```

---

### **2. Purga Todos los Caches**

Visita: **Site Administration → Development → Purge all caches**

O por CLI:
```bash
php admin/cli/purge_caches.php
```

---

### **3. Revisa Errores de PHP**

Habilita debugging en `config.php`:

```php
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;
```

Luego prueba el bot y revisa los logs.

---

### **4. Verifica Permisos de Archivos**

```bash
ls -la local/educambot/classes/local/setup/common_questions_seed.php
```

Debe ser legible por el usuario del servidor web.

---

## 📋 RESUMEN DE ARCHIVOS IMPORTANTES

| Archivo | Propósito |
|---------|-----------|
| `cli/seed_common_questions.php` | Ejecuta el seed manualmente |
| `cli/diagnose_bot.php` | Diagnostica problemas del bot |
| `classes/local/setup/common_questions_seed.php` | Contiene las 9 preguntas comunes |
| `db/upgrade.php` | Se ejecuta automáticamente al actualizar versión |

---

## ⚙️ PRÓXIMA ACTUALIZACIÓN

La siguiente actualización (ya pusheada) incluye:

1. ✅ `db/upgrade.php` corregido para ejecutar seed en v2025103005
2. ✅ CLI scripts de diagnóstico y seed manual
3. ✅ Verificación inteligente: solo ejecuta seed si no hay reglas

**Para aplicar la corrección:**

```bash
cd /path/to/moodle
git pull origin claude/fix-common-questions-matching-011CUe6J3GL6nEWXPyCrsTv2

# Luego ejecuta UNO de estos:
# Opción A: Via Moodle UI
# Visita: Site Administration → Notifications

# Opción B: Via CLI
php local/educambot/cli/seed_common_questions.php
```

---

## 📞 CONTACTO

Si después de seguir estos pasos el bot SIGUE sin responder:

1. Ejecuta el diagnóstico completo:
   ```bash
   php local/educambot/cli/diagnose_bot.php > diagnostic_report.txt
   ```

2. Envía el archivo `diagnostic_report.txt`

3. Incluye la salida de:
   ```bash
   php -v
   grep "version" local/educambot/version.php
   ```

---

**Última actualización:** 2025-10-30
**Versión del fix:** v2.1.2 hotfix 1
