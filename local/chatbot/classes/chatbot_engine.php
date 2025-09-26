<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Intelligent Chatbot Engine
 *
 * @package    local_chatbot
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chatbot;

defined('MOODLE_INTERNAL') || die();

class chatbot_engine {
    
    private $userid;
    private $context;
    private $session_memory;
    private $intent_patterns;
    private $entity_extractor;
    
    /**
     * Constructor
     */
    public function __construct($userid) {
        global $DB;
        
        $this->userid = $userid;
        $this->load_session_memory();
        $this->initialize_patterns();
        $this->entity_extractor = new entity_extractor();
    }
    
    /**
     * Process message with intelligent analysis
     */
    public function process_message($message) {
        global $DB;
        
        // 1. Preprocessar mensaje
        $processed = $this->preprocess_message($message);
        
        // 2. Analizar contexto de la conversación
        $context = $this->analyze_context($processed);
        
        // 3. Detectar intención del usuario
        $intent = $this->detect_intent($processed, $context);
        
        // 4. Extraer entidades relevantes
        $entities = $this->entity_extractor->extract($processed);
        
        // 5. Analizar sentimiento
        $sentiment = $this->analyze_sentiment($processed);
        
        // 6. Recuperar información relevante
        $knowledge = $this->retrieve_knowledge($intent, $entities, $context);
        
        // 7. Generar respuesta inteligente
        $response = $this->generate_response($intent, $entities, $knowledge, $sentiment, $context);
        
        // 8. Actualizar memoria de sesión
        $this->update_session_memory($message, $response, $intent, $entities);
        
        // 9. Registrar interacción con metadatos
        $this->log_interaction($message, $response, $intent, $entities, $sentiment);
        
        return $response;
    }
    
    /**
     * Preprocess message for better understanding
     */
    private function preprocess_message($message) {
        // Normalizar texto
        $processed = mb_strtolower(trim($message));
        
        // Expandir contracciones comunes
        $contractions = [
            'q' => 'que',
            'xq' => 'por que',
            'pk' => 'por que',
            'tb' => 'también',
            'tmb' => 'también',
            'd' => 'de',
            'x' => 'por',
            'xa' => 'para',
            'xfa' => 'por favor',
            'pf' => 'por favor',
            'grax' => 'gracias',
            'thx' => 'gracias',
        ];
        
        foreach ($contractions as $short => $full) {
            $processed = preg_replace('/\b' . $short . '\b/i', $full, $processed);
        }
        
        // Corregir errores ortográficos comunes
        $corrections = $this->get_common_corrections();
        foreach ($corrections as $wrong => $correct) {
            $processed = str_replace($wrong, $correct, $processed);
        }
        
        // Eliminar caracteres especiales pero mantener espacios y puntuación básica
        $processed = preg_replace('/[^\p{L}\p{N}\s\.\,\?\!]/u', '', $processed);
        
        // Tokenización avanzada
        $tokens = $this->tokenize($processed);
        
        return [
            'original' => $message,
            'processed' => $processed,
            'tokens' => $tokens,
            'word_count' => count($tokens)
        ];
    }
    
    /**
     * Detect user intent using pattern matching and context
     */
    private function detect_intent($processed, $context) {
        $message = $processed['processed'];
        $tokens = $processed['tokens'];
        
        $scores = [];
        
        // Analizar cada patrón de intención
        foreach ($this->intent_patterns as $intent => $patterns) {
            $score = 0;
            $matches = 0;
            
            foreach ($patterns['keywords'] as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    $score += 2;
                    $matches++;
                }
            }
            
            // Verificar patrones regex
            if (isset($patterns['regex'])) {
                foreach ($patterns['regex'] as $regex) {
                    if (preg_match($regex, $message)) {
                        $score += 3;
                        $matches++;
                    }
                }
            }
            
            // Considerar n-gramas
            if (isset($patterns['ngrams'])) {
                $ngrams = $this->extract_ngrams($tokens, 2);
                foreach ($patterns['ngrams'] as $ngram) {
                    if (in_array($ngram, $ngrams)) {
                        $score += 2.5;
                        $matches++;
                    }
                }
            }
            
            // Boost basado en contexto previo
            if ($context['last_intent'] == $intent) {
                $score *= 1.2; // Continuidad de tema
            }
            
            // Penalización por ambigüedad
            if ($matches > 0) {
                $score = $score / (1 + log($processed['word_count']));
            }
            
            $scores[$intent] = $score;
        }
        
        // Seleccionar intención con mayor puntuación
        arsort($scores);
        $top_intent = key($scores);
        $confidence = current($scores);
        
        // Si la confianza es baja, intentar inferir del contexto
        if ($confidence < 2) {
            $top_intent = $this->infer_from_context($processed, $context);
        }
        
        return [
            'intent' => $top_intent,
            'confidence' => $confidence,
            'alternatives' => array_slice($scores, 1, 2, true)
        ];
    }
    
    /**
     * Analyze conversation context
     */
    private function analyze_context($processed) {
        $context = [
            'last_intent' => null,
            'last_entities' => [],
            'conversation_length' => 0,
            'topic_continuity' => false,
            'user_frustration' => 0,
            'clarification_needed' => false
        ];
        
        if (!empty($this->session_memory)) {
            $last = end($this->session_memory);
            $context['last_intent'] = $last['intent'] ?? null;
            $context['last_entities'] = $last['entities'] ?? [];
            $context['conversation_length'] = count($this->session_memory);
            
            // Detectar continuidad de tema
            if (isset($last['tokens'])) {
                $common_tokens = array_intersect($processed['tokens'], $last['tokens']);
                $context['topic_continuity'] = count($common_tokens) > 1;
            }
            
            // Detectar frustración del usuario
            $context['user_frustration'] = $this->detect_frustration();
            
            // Detectar necesidad de clarificación
            $context['clarification_needed'] = $this->needs_clarification($processed);
        }
        
        return $context;
    }
    
    /**
     * Analyze sentiment of the message
     */
    private function analyze_sentiment($processed) {
        $message = $processed['processed'];
        
        // Palabras positivas
        $positive_words = [
            'gracias', 'excelente', 'perfecto', 'genial', 'bueno', 'bien',
            'fantástico', 'maravilloso', 'útil', 'claro', 'entiendo',
            'feliz', 'contento', 'satisfecho', 'mejor', 'increíble'
        ];
        
        // Palabras negativas
        $negative_words = [
            'problema', 'error', 'mal', 'no funciona', 'falla', 'difícil',
            'complicado', 'confuso', 'frustrante', 'molesto', 'terrible',
            'horrible', 'pésimo', 'no entiendo', 'no sirve', 'basura'
        ];
        
        // Palabras neutras/interrogativas
        $question_words = [
            'cómo', 'qué', 'cuándo', 'dónde', 'por qué', 'quién',
            'cuál', 'puedo', 'podría', 'debería'
        ];
        
        $positive_count = 0;
        $negative_count = 0;
        $question_count = 0;
        
        foreach ($positive_words as $word) {
            $positive_count += substr_count($message, $word);
        }
        
        foreach ($negative_words as $word) {
            $negative_count += substr_count($message, $word);
        }
        
        foreach ($question_words as $word) {
            $question_count += substr_count($message, $word);
        }
        
        // Detectar emociones con emojis y puntuación
        if (preg_match('/[😊😄😃😁🙂👍✨💪]|:\)|:D|\^\^/', $processed['original'])) {
            $positive_count += 2;
        }
        
        if (preg_match('/[😞😔😢😭😡🤬👎]|:\(|:\'|>:|/', $processed['original'])) {
            $negative_count += 2;
        }
        
        // Calcular sentimiento dominante
        $total = $positive_count + $negative_count + $question_count;
        
        if ($total == 0) {
            $sentiment = 'neutral';
            $score = 0;
        } else {
            $score = ($positive_count - $negative_count) / $total;
            
            if ($score > 0.3) {
                $sentiment = 'positive';
            } else if ($score < -0.3) {
                $sentiment = 'negative';
            } else if ($question_count > ($positive_count + $negative_count)) {
                $sentiment = 'inquisitive';
            } else {
                $sentiment = 'neutral';
            }
        }
        
        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => min(abs($score), 1.0)
        ];
    }
    
    /**
     * Generate intelligent response
     */
    private function generate_response($intent, $entities, $knowledge, $sentiment, $context) {
        global $DB;
        
        // Obtener plantillas de respuesta para la intención
        $templates = $this->get_response_templates($intent['intent']);
        
        // Seleccionar plantilla basada en contexto y sentimiento
        $template = $this->select_best_template($templates, $sentiment, $context);
        
        // Personalizar respuesta con entidades
        $response = $this->personalize_response($template, $entities);
        
        // Añadir elementos contextuales
        if ($context['clarification_needed']) {
            $response = $this->add_clarification_request($response, $intent);
        }
        
        // Ajustar tono según sentimiento
        $response = $this->adjust_tone($response, $sentiment);
        
        // Añadir sugerencias si es apropiado
        if ($intent['confidence'] < 3 || $context['user_frustration'] > 2) {
            $response = $this->add_suggestions($response, $intent);
        }
        
        // Variación para evitar repetición
        $response = $this->add_variation($response, $context);
        
        return $response;
    }
    
    /**
     * Initialize intent patterns
     */
    private function initialize_patterns() {
        $this->intent_patterns = [
            'greeting' => [
                'keywords' => ['hola', 'buenos días', 'buenas tardes', 'buenas noches', 'saludos', 'hey', 'qué tal'],
                'regex' => ['/^hola\b/i', '/buenos?\s+(días?|tardes?|noches?)/i'],
                'ngrams' => ['qué tal', 'cómo estás']
            ],
            'help_general' => [
                'keywords' => ['ayuda', 'ayudar', 'asistencia', 'necesito', 'problema', 'no sé'],
                'regex' => ['/necesito\s+ayuda/i', '/no\s+sé\s+cómo/i', '/puedes?\s+ayudar/i'],
                'ngrams' => ['necesito ayuda', 'no entiendo', 'no sé']
            ],
            'course_info' => [
                'keywords' => ['curso', 'materia', 'asignatura', 'clase', 'inscribir', 'matricular'],
                'regex' => ['/cursos?\s+(disponibles?|activos?)/i', '/mis\s+cursos?/i'],
                'ngrams' => ['mis cursos', 'qué curso', 'cuáles cursos']
            ],
            'assignment' => [
                'keywords' => ['tarea', 'trabajo', 'actividad', 'entrega', 'plazo', 'fecha límite', 'assignment'],
                'regex' => ['/tareas?\s+pendientes?/i', '/fecha\s+de\s+entrega/i'],
                'ngrams' => ['entregar tarea', 'fecha entrega', 'tareas pendientes']
            ],
            'grades' => [
                'keywords' => ['nota', 'calificación', 'puntaje', 'evaluación', 'resultado', 'promedio'],
                'regex' => ['/mis?\s+notas?/i', '/calificaci(ón|ones)/i'],
                'ngrams' => ['ver notas', 'mis calificaciones', 'qué nota']
            ],
            'technical' => [
                'keywords' => ['error', 'problema', 'no funciona', 'falla', 'bug', 'técnico', 'no puedo'],
                'regex' => ['/no\s+(funciona|puedo|carga)/i', '/error\s+\d+/i'],
                'ngrams' => ['no funciona', 'no puedo', 'me sale']
            ],
            'navigation' => [
                'keywords' => ['dónde', 'encontrar', 'buscar', 'ubicar', 'menú', 'sección', 'página'],
                'regex' => ['/dónde\s+(está|encuentro|queda)/i', '/cómo\s+llego/i'],
                'ngrams' => ['dónde está', 'cómo encontrar', 'en qué']
            ],
            'schedule' => [
                'keywords' => ['calendario', 'horario', 'fecha', 'cuándo', 'programación', 'agenda'],
                'regex' => ['/cuándo\s+(es|hay|tengo)/i', '/qué\s+día/i'],
                'ngrams' => ['qué día', 'cuándo es', 'a qué']
            ],
            'exam' => [
                'keywords' => ['examen', 'parcial', 'final', 'quiz', 'prueba', 'evaluación', 'test'],
                'regex' => ['/examen\s+(final|parcial)/i', '/cuándo\s+es\s+el\s+examen/i'],
                'ngrams' => ['examen final', 'parcial de', 'tengo examen']
            ],
            'resources' => [
                'keywords' => ['material', 'recurso', 'archivo', 'documento', 'pdf', 'video', 'presentación'],
                'regex' => ['/materiales?\s+del?\s+curso/i', '/dónde\s+está\s+el\s+pdf/i'],
                'ngrams' => ['material del', 'recursos de', 'descargar archivo']
            ],
            'forum' => [
                'keywords' => ['foro', 'discusión', 'debate', 'comentar', 'publicar', 'responder', 'post'],
                'regex' => ['/foros?\s+del?\s+curso/i', '/cómo\s+participar/i'],
                'ngrams' => ['participar foro', 'publicar en', 'foro del']
            ],
            'profile' => [
                'keywords' => ['perfil', 'usuario', 'cuenta', 'contraseña', 'password', 'datos', 'información personal'],
                'regex' => ['/mi\s+perfil/i', '/cambiar\s+contraseña/i'],
                'ngrams' => ['mi perfil', 'cambiar contraseña', 'mis datos']
            ],
            'communication' => [
                'keywords' => ['mensaje', 'correo', 'email', 'contactar', 'escribir', 'comunicar', 'chat'],
                'regex' => ['/enviar\s+mensaje/i', '/contactar\s+con/i'],
                'ngrams' => ['enviar mensaje', 'contactar profesor', 'escribir a']
            ],
            'gratitude' => [
                'keywords' => ['gracias', 'agradezco', 'perfecto', 'excelente', 'genial', 'entendido'],
                'regex' => ['/muchas?\s+gracias/i', '/te\s+agradezco/i'],
                'ngrams' => ['muchas gracias', 'está perfecto', 'muy bien']
            ],
            'farewell' => [
                'keywords' => ['adiós', 'chao', 'bye', 'hasta luego', 'nos vemos', 'terminar'],
                'regex' => ['/hasta\s+(luego|pronto|mañana)/i', '/me\s+voy/i'],
                'ngrams' => ['hasta luego', 'nos vemos', 'me voy']
            ]
        ];
    }
    
    /**
     * Get response templates for intent
     */
    private function get_response_templates($intent) {
        global $DB;
        
        // Primero buscar en base de datos
        $templates = $DB->get_records('local_chatbot_responses', 
            ['intent' => $intent, 'enabled' => 1], 
            'priority ASC'
        );
        
        // Si no hay en BD, usar plantillas por defecto
        if (empty($templates)) {
            $templates = $this->get_default_templates($intent);
        }
        
        return $templates;
    }
    
    /**
     * Get default templates
     */
    private function get_default_templates($intent) {
        $defaults = [
            'greeting' => [
                '¡Hola! 👋 Es un gusto poder ayudarte hoy. ¿En qué puedo asistirte?',
                '¡Bienvenido! Estoy aquí para ayudarte con lo que necesites sobre la plataforma.',
                'Hola, ¿cómo estás? Dime, ¿en qué te puedo colaborar?'
            ],
            'help_general' => [
                'Claro, estoy aquí para ayudarte. Puedo asistirte con:\n• Información sobre cursos\n• Tareas y actividades\n• Calificaciones\n• Navegación en la plataforma\n¿Sobre cuál tema específico necesitas ayuda?',
                'Por supuesto, te ayudaré con gusto. ¿Podrías ser más específico sobre lo que necesitas?',
                'Entiendo que necesitas ayuda. Cuéntame más detalles para poder asistirte mejor.'
            ],
            'course_info' => [
                'Para ver tus cursos, dirígete al panel principal y selecciona "Mis cursos". Allí encontrarás todos los cursos en los que estás inscrito.',
                'Los cursos están organizados en tu dashboard. Puedes acceder a ellos desde el menú principal o desde la página de inicio.',
                'Encontrarás la información de tus cursos en la sección "Mis cursos" del menú. Cada curso muestra su progreso y actividades pendientes.'
            ],
            'unknown' => [
                'Hmm, no estoy seguro de entender completamente tu pregunta. ¿Podrías reformularla o darme más detalles?',
                'Disculpa, no tengo información clara sobre eso. ¿Puedes ser más específico?',
                'Interesante pregunta. Para darte una mejor respuesta, ¿podrías proporcionar más contexto?'
            ]
        ];
        
        $templates = [];
        $responses = $defaults[$intent] ?? $defaults['unknown'];
        
        foreach ($responses as $i => $response) {
            $templates[] = (object)[
                'response' => $response,
                'priority' => $i + 1
            ];
        }
        
        return $templates;
    }
    
    /**
     * Extract n-grams from tokens
     */
    private function extract_ngrams($tokens, $n = 2) {
        $ngrams = [];
        $count = count($tokens);
        
        for ($i = 0; $i <= $count - $n; $i++) {
            $ngram = implode(' ', array_slice($tokens, $i, $n));
            $ngrams[] = $ngram;
        }
        
        return $ngrams;
    }
    
    /**
     * Tokenize text
     */
    private function tokenize($text) {
        // Eliminar puntuación y dividir por espacios
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $tokens = preg_split('/\s+/', $text);
        
        // Eliminar tokens vacíos y stop words básicas
        $stopwords = ['el', 'la', 'de', 'en', 'y', 'a', 'los', 'las', 'un', 'una', 'es', 'por'];
        $tokens = array_filter($tokens, function($token) use ($stopwords) {
            return !empty($token) && strlen($token) > 1 && !in_array($token, $stopwords);
        });
        
        return array_values($tokens);
    }
    
    /**
     * Load session memory
     */
    private function load_session_memory() {
        global $SESSION;
        
        if (!isset($SESSION->chatbot_memory)) {
            $SESSION->chatbot_memory = [];
        }
        
        $this->session_memory = &$SESSION->chatbot_memory;
        
        // Mantener solo las últimas 10 interacciones
        if (count($this->session_memory) > 10) {
            $this->session_memory = array_slice($this->session_memory, -10);
        }
    }
    
    /**
     * Update session memory
     */
    private function update_session_memory($message, $response, $intent, $entities) {
        $this->session_memory[] = [
            'message' => $message,
            'response' => $response,
            'intent' => $intent['intent'],
            'entities' => $entities,
            'timestamp' => time(),
            'tokens' => $this->tokenize($message)
        ];
    }
    
    /**
     * Log interaction
     */
    private function log_interaction($message, $response, $intent, $entities, $sentiment) {
        global $DB;
        
        $record = new \stdClass();
        $record->userid = $this->userid;
        $record->message = $message;
        $record->response = $response;
        $record->intent = $intent['intent'];
        $record->confidence = $intent['confidence'];
        $record->entities = json_encode($entities);
        $record->sentiment = $sentiment['sentiment'];
        $record->context = json_encode($this->context);
        $record->timecreated = time();
        
        $DB->insert_record('local_chatbot_logs', $record);
    }
    
    /**
     * Select best template based on context
     */
    private function select_best_template($templates, $sentiment, $context) {
        if (empty($templates)) {
            return "Lo siento, no tengo una respuesta para eso en este momento.";
        }
        
        // Si hay frustración del usuario, elegir respuesta más empática
        if ($context['user_frustration'] > 2) {
            // Buscar templates con palabras empáticas
            foreach ($templates as $template) {
                if (strpos($template->response, 'entiendo') !== false || 
                    strpos($template->response, 'lamento') !== false) {
                    return $template->response;
                }
            }
        }
        
        // Para sentimiento negativo, priorizar respuestas de solución
        if ($sentiment['sentiment'] == 'negative') {
            foreach ($templates as $template) {
                if (strpos($template->response, 'solución') !== false || 
                    strpos($template->response, 'ayudar') !== false) {
                    return $template->response;
                }
            }
        }
        
        // Evitar repetir la última respuesta
        if (!empty($this->session_memory)) {
            $last_response = end($this->session_memory)['response'];
            $templates = array_filter($templates, function($t) use ($last_response) {
                return $t->response !== $last_response;
            });
        }
        
        // Seleccionar aleatoriamente entre las mejores opciones
        $top_templates = array_slice($templates, 0, 3);
        $selected = $top_templates[array_rand($top_templates)];
        
        return $selected->response;
    }
    
    /**
     * Personalize response with entities
     */
    private function personalize_response($template, $entities) {
        $response = $template;
        
        // Reemplazar placeholders con entidades detectadas
        if (isset($entities['course_name'])) {
            $response = str_replace('{course}', $entities['course_name'], $response);
        }
        
        if (isset($entities['date'])) {
            $response = str_replace('{date}', $entities['date'], $response);
        }
        
        if (isset($entities['assignment'])) {
            $response = str_replace('{assignment}', $entities['assignment'], $response);
        }
        
        // Añadir nombre del usuario si está disponible
        global $USER;
        if ($USER->firstname) {
            $response = str_replace('{name}', $USER->firstname, $response);
        }
        
        return $response;
    }
    
    /**
     * Adjust tone based on sentiment
     */
    private function adjust_tone($response, $sentiment) {
        if ($sentiment['sentiment'] == 'negative') {
            // Añadir empatía
            $empathy = [
                "Entiendo tu frustración. ",
                "Lamento que tengas dificultades. ",
                "Comprendo la situación. "
            ];
            $response = $empathy[array_rand($empathy)] . $response;
        } else if ($sentiment['sentiment'] == 'positive') {
            // Añadir entusiasmo
            $enthusiasm = [
                "¡Excelente! ",
                "¡Genial! ",
                "¡Me alegra poder ayudarte! "
            ];
            if (rand(0, 1) == 1) {
                $response = $enthusiasm[array_rand($enthusiasm)] . $response;
            }
        }
        
        return $response;
    }
    
    /**
     * Add suggestions if confidence is low
     */
    private function add_suggestions($response, $intent) {
        if ($intent['confidence'] < 3) {
            $suggestions = "\n\n💡 También puedes preguntarme sobre:\n";
            $suggestions .= "• Tus cursos y actividades\n";
            $suggestions .= "• Calificaciones y evaluaciones\n";
            $suggestions .= "• Problemas técnicos\n";
            $suggestions .= "• Navegación en la plataforma";
            
            $response .= $suggestions;
        }
        
        return $response;
    }
    
    /**
     * Add variation to response
     */
    private function add_variation($response, $context) {
        // Añadir variaciones temporales
        $hour = date('H');
        
        if ($hour >= 5 && $hour < 12) {
            $time_greeting = "Buenos días";
        } else if ($hour >= 12 && $hour < 19) {
            $time_greeting = "Buenas tardes";
        } else {
            $time_greeting = "Buenas noches";
        }
        
        // Solo añadir saludo temporal ocasionalmente
        if ($context['conversation_length'] == 0 && rand(0, 2) == 0) {
            $response = $time_greeting . ". " . $response;
        }
        
        return $response;
    }
    
    /**
     * Detect user frustration
     */
    private function detect_frustration() {
        $frustration = 0;
        
        if (count($this->session_memory) >= 2) {
            $recent = array_slice($this->session_memory, -3);
            
            foreach ($recent as $interaction) {
                // Buscar palabras de frustración
                if (preg_match('/(no funciona|no sirve|no entiendo|mal|error|problema)/i', $interaction['message'])) {
                    $frustration++;
                }
                
                // Repetición de preguntas similares
                if (isset($interaction['intent']) && 
                    $interaction['intent'] == end($this->session_memory)['intent']) {
                    $frustration += 0.5;
                }
            }
        }
        
        return $frustration;
    }
    
    /**
     * Check if clarification is needed
     */
    private function needs_clarification($processed) {
        // Mensajes muy cortos o muy vagos
        if ($processed['word_count'] < 2) {
            return true;
        }
        
        // Mensajes con solo pronombres
        $vague_words = ['eso', 'esto', 'aquello', 'algo', 'cosa'];
        $vague_count = 0;
        
        foreach ($vague_words as $word) {
            if (in_array($word, $processed['tokens'])) {
                $vague_count++;
            }
        }
        
        return $vague_count > ($processed['word_count'] / 2);
    }
    
    /**
     * Add clarification request
     */
    private function add_clarification_request($response, $intent) {
        $clarifications = [
            "\n\n¿Podrías darme más detalles para poder ayudarte mejor?",
            "\n\n¿A qué te refieres específicamente?",
            "\n\nPara darte una respuesta más precisa, ¿podrías ser más específico?"
        ];
        
        return $response . $clarifications[array_rand($clarifications)];
    }
    
    /**
     * Infer intent from context
     */
    private function infer_from_context($processed, $context) {
        // Si es continuación de tema
        if ($context['topic_continuity'] && $context['last_intent']) {
            return $context['last_intent'];
        }
        
        // Si es muy corto, probablemente es afirmación/negación
        if ($processed['word_count'] == 1) {
            if (in_array($processed['tokens'][0], ['sí', 'si', 'ok', 'vale', 'bueno'])) {
                return 'confirmation';
            }
            if (in_array($processed['tokens'][0], ['no', 'nop', 'nah'])) {
                return 'negation';
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Get common spelling corrections
     */
    private function get_common_corrections() {
        return [
            'ola' => 'hola',
            'ke' => 'que',
            'kiero' => 'quiero',
            'aber' => 'a ver',
            'aver' => 'a ver',
            'haber' => 'a ver',
            'acer' => 'hacer',
            'aser' => 'hacer',
            'ai' => 'ahí',
            'hay' => 'ahí',
            'alla' => 'allá',
            'halla' => 'allá',
            'aya' => 'haya',
            'asia' => 'hacia',
            'acia' => 'hacia',
            'bio' => 'vio',
            'boi' => 'voy',
            'boy' => 'voy',
            'cituacion' => 'situación',
            'esamen' => 'examen',
            'escuela' => 'escuela',
            'matemeticas' => 'matemáticas',
            'ingles' => 'inglés',
            'español' => 'español',
            'fisika' => 'física',
            'quimica' => 'química',
            'biologia' => 'biología',
            'istoria' => 'historia',
            'hechar' => 'echar',
            'deveres' => 'deberes',
            'travajo' => 'trabajo',
            'nesecito' => 'necesito',
            'nesesito' => 'necesito',
            'resivir' => 'recibir',
            'recivir' => 'recibir',
            'saves' => 'sabes',
            'tambien' => 'también',
            'mañna' => 'mañana',
            'despues' => 'después',
            'dias' => 'días',
            'sabado' => 'sábado',
            'miercoles' => 'miércoles',
            'informacion' => 'información',
            'atencion' => 'atención',
            'porfavor' => 'por favor',
            'porfabor' => 'por favor',
            'profavor' => 'por favor',
            'grasias' => 'gracias',
            'grácias' => 'gracias'
        ];
    }
    
    /**
     * Retrieve relevant knowledge
     */
    private function retrieve_knowledge($intent, $entities, $context) {
        global $DB;
        
        $knowledge = [];
        
        // Recuperar información específica según la intención
        switch ($intent['intent']) {
            case 'course_info':
                if (isset($entities['course_name'])) {
                    // Buscar información del curso específico
                    $course = $DB->get_record('course', ['shortname' => $entities['course_name']]);
                    if ($course) {
                        $knowledge['course'] = $course;
                    }
                }
                break;
                
            case 'assignment':
                // Obtener tareas pendientes del usuario
                $sql = "SELECT a.name, a.duedate, cm.id as cmid
                        FROM {assign} a
                        JOIN {course_modules} cm ON cm.instance = a.id
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                        WHERE a.duedate > ? AND cm.visible = 1
                        ORDER BY a.duedate ASC
                        LIMIT 5";
                $assignments = $DB->get_records_sql($sql, [time()]);
                if ($assignments) {
                    $knowledge['assignments'] = $assignments;
                }
                break;
                
            case 'grades':
                // Información sobre calificaciones recientes
                $knowledge['grade_info'] = true;
                break;
        }
        
        return $knowledge;
    }
}

/**
 * Entity Extractor Class
 */
class entity_extractor {
    
    /**
     * Extract entities from processed message
     */
    public function extract($processed) {
        $entities = [];
        $message = $processed['processed'];
        
        // Extraer fechas
        $dates = $this->extract_dates($message);
        if (!empty($dates)) {
            $entities['dates'] = $dates;
        }
        
        // Extraer números
        $numbers = $this->extract_numbers($message);
        if (!empty($numbers)) {
            $entities['numbers'] = $numbers;
        }
        
        // Extraer nombres de cursos
        $courses = $this->extract_course_names($message);
        if (!empty($courses)) {
            $entities['courses'] = $courses;
        }
        
        // Extraer tiempo relativo
        $time_refs = $this->extract_time_references($message);
        if (!empty($time_refs)) {
            $entities['time_references'] = $time_refs;
        }
        
        // Extraer URLs
        if (preg_match_all('/https?:\/\/[^\s]+/', $processed['original'], $matches)) {
            $entities['urls'] = $matches[0];
        }
        
        // Extraer emails
        if (preg_match_all('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i', $message, $matches)) {
            $entities['emails'] = $matches[0];
        }
        
        return $entities;
    }
    
    /**
     * Extract dates from text
     */
    private function extract_dates($text) {
        $dates = [];
        
        // Patrones de fecha comunes
        $patterns = [
            '/\d{1,2}\/\d{1,2}\/\d{2,4}/',
            '/\d{1,2}-\d{1,2}-\d{2,4}/',
            '/\d{1,2}\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)/i',
            '/(lunes|martes|miércoles|jueves|viernes|sábado|domingo)/i',
            '/(hoy|mañana|ayer|pasado mañana)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $dates = array_merge($dates, $matches[0]);
            }
        }
        
        return array_unique($dates);
    }
    
    /**
     * Extract numbers
     */
    private function extract_numbers($text) {
        $numbers = [];
        
        if (preg_match_all('/\b\d+\b/', $text, $matches)) {
            $numbers = $matches[0];
        }
        
        // Números escritos
        $written = [
            'uno' => 1, 'dos' => 2, 'tres' => 3, 'cuatro' => 4, 'cinco' => 5,
            'seis' => 6, 'siete' => 7, 'ocho' => 8, 'nueve' => 9, 'diez' => 10,
            'primero' => 1, 'segundo' => 2, 'tercero' => 3
        ];
        
        foreach ($written as $word => $num) {
            if (strpos($text, $word) !== false) {
                $numbers[] = $num;
            }
        }
        
        return $numbers;
    }
    
    /**
     * Extract course names
     */
    private function extract_course_names($text) {
        global $DB;
        
        $courses = [];
        
        // Buscar nombres de cursos conocidos
        $known_courses = $DB->get_records('course', null, '', 'id, shortname, fullname');
        
        foreach ($known_courses as $course) {
            if (stripos($text, $course->shortname) !== false || 
                stripos($text, $course->fullname) !== false) {
                $courses[] = $course->shortname;
            }
        }
        
        // Patrones genéricos de cursos
        if (preg_match_all('/(matemáticas?|física|química|biología|historia|geografía|inglés|lengua|literatura)/i', $text, $matches)) {
            $courses = array_merge($courses, $matches[0]);
        }
        
        return array_unique($courses);
    }
    
    /**
     * Extract time references
     */
    private function extract_time_references($text) {
        $references = [];
        
        $patterns = [
            'esta semana' => 'current_week',
            'próxima semana' => 'next_week',
            'semana pasada' => 'last_week',
            'este mes' => 'current_month',
            'próximo mes' => 'next_month',
            'mes pasado' => 'last_month',
            'hoy' => 'today',
            'mañana' => 'tomorrow',
            'ayer' => 'yesterday',
            'ahora' => 'now',
            'después' => 'later',
            'antes' => 'before'
        ];
        
        foreach ($patterns as $pattern => $ref) {
            if (strpos($text, $pattern) !== false) {
                $references[] = $ref;
            }
        }
        
        return $references;
    }
}
