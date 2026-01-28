<?php
/**
 * Datos de quizzes - Capitulos 1 al 5
 * Extraidos de los PDFs/DOCX originales del curso
 * Formato: multichoice con una respuesta correcta
 */

$QUIZ_DATA = [
    // CAPITULO 1: Desarrollo Neurologico Infantil
    1 => [
        'name' => 'Autoevaluacion - Capitulo 1',
        'intro' => 'Test de autoevaluacion del Capitulo 1: Desarrollo Neurologico Infantil',
        'questions' => [
            [
                'text' => 'Cual es el beneficio principal de la poda sinaptica?',
                'answers' => [
                    ['text' => 'Precision en las conexiones neuronales', 'correct' => true],
                    ['text' => 'Mayor cantidad de sinapsis', 'correct' => false],
                    ['text' => 'Aumento del tamano cerebral', 'correct' => false],
                    ['text' => 'Reduccion del consumo de energia', 'correct' => false],
                ]
            ],
            [
                'text' => 'Cual es la caracteristica del desarrollo del hemisferio izquierdo?',
                'answers' => [
                    ['text' => 'Desarrollo global simultaneo', 'correct' => false],
                    ['text' => 'Alargamiento secuencial', 'correct' => true],
                    ['text' => 'Maduracion emocional temprana', 'correct' => false],
                    ['text' => 'Predominio en funciones espaciales', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que estructuras cerebrales se ven afectadas por el estres temprano?',
                'answers' => [
                    ['text' => 'Solo el cerebelo', 'correct' => false],
                    ['text' => 'Neocortex, hipocampo y amigdala', 'correct' => true],
                    ['text' => 'Unicamente el tronco encefalico', 'correct' => false],
                    ['text' => 'Solo la medula espinal', 'correct' => false],
                ]
            ],
            [
                'text' => 'Cual es la funcion principal del Area de Broca?',
                'answers' => [
                    ['text' => 'Comprension del lenguaje', 'correct' => false],
                    ['text' => 'Procesamiento visual', 'correct' => false],
                    ['text' => 'Control motor de extremidades', 'correct' => false],
                    ['text' => 'Produccion del lenguaje', 'correct' => true],
                ]
            ],
            [
                'text' => 'A los 2 anos, la densidad sinaptica es:',
                'answers' => [
                    ['text' => 'Igual que en adultos', 'correct' => false],
                    ['text' => '25% inferior a adultos', 'correct' => false],
                    ['text' => '50% superior a adultos', 'correct' => true],
                    ['text' => 'El doble que en adultos', 'correct' => false],
                ]
            ],
            [
                'text' => 'Existen diferencias de genero en el desarrollo del hipocampo y la amigdala:',
                'answers' => [
                    ['text' => 'No existen diferencias significativas', 'correct' => false],
                    ['text' => 'Solo en el hipocampo', 'correct' => false],
                    ['text' => 'Si, existen diferencias documentadas', 'correct' => true],
                    ['text' => 'Solo en la amigdala', 'correct' => false],
                ]
            ],
            [
                'text' => 'El Area de Wernicke corresponde a que area de Brodmann?',
                'answers' => [
                    ['text' => 'Area 4', 'correct' => false],
                    ['text' => 'Area 17', 'correct' => false],
                    ['text' => 'Area 22', 'correct' => true],
                    ['text' => 'Area 44', 'correct' => false],
                ]
            ],
            [
                'text' => 'A los 2 anos el movimiento voluntario se desarrolla gracias a:',
                'answers' => [
                    ['text' => 'Maduracion del cerebelo', 'correct' => false],
                    ['text' => 'Desarrollo del sistema limbico', 'correct' => false],
                    ['text' => 'Mielinizacion de la via piramidal', 'correct' => true],
                    ['text' => 'Crecimiento del hipocampo', 'correct' => false],
                ]
            ],
            [
                'text' => 'Los picos de aceleracion del desarrollo cerebral finalizan aproximadamente a los:',
                'answers' => [
                    ['text' => '14 anos', 'correct' => true],
                    ['text' => '6 anos', 'correct' => false],
                    ['text' => '21 anos', 'correct' => false],
                    ['text' => '3 anos', 'correct' => false],
                ]
            ],
            [
                'text' => 'El giro angular tiene como funcion principal:',
                'answers' => [
                    ['text' => 'Produccion del habla', 'correct' => false],
                    ['text' => 'Control motor fino', 'correct' => false],
                    ['text' => 'Asociar informacion visual y auditiva', 'correct' => true],
                    ['text' => 'Regulacion emocional', 'correct' => false],
                ]
            ],
        ]
    ],

    // CAPITULO 2: Funciones Emocionales
    2 => [
        'name' => 'Autoevaluacion - Capitulo 2',
        'intro' => 'Test de autoevaluacion del Capitulo 2: Funciones Emocionales',
        'questions' => [
            [
                'text' => 'Cual es la consecuencia de una lesion en el hemisferio izquierdo en un adulto?',
                'answers' => [
                    ['text' => 'Euforia o indiferencia emocional', 'correct' => false],
                    ['text' => 'Dificultad para reconocer expresiones faciales', 'correct' => false],
                    ['text' => 'Depresion y reacciones catastroficas', 'correct' => true],
                    ['text' => 'Perdida de la sensibilidad corporal', 'correct' => false],
                ]
            ],
            [
                'text' => 'Cual afirmacion describe correctamente el patron de maduracion cerebral?',
                'answers' => [
                    ['text' => 'La maduracion ocurre en el cuerpo calloso a los 4 anos', 'correct' => false],
                    ['text' => 'El cerebro madura de adelante hacia atras', 'correct' => false],
                    ['text' => 'Ambos hemisferios maduran simultaneamente', 'correct' => false],
                    ['text' => 'El cerebro madura de atras (sensorial) hacia adelante (motor)', 'correct' => true],
                ]
            ],
            [
                'text' => 'Que dos mecanismos pueden causar depresion en ninos y adultos?',
                'answers' => [
                    ['text' => 'Hiperactivacion del hemisferio izquierdo y baja actividad parietal', 'correct' => false],
                    ['text' => 'Hipoactivacion del lobulo temporal y lesion del cuerpo calloso', 'correct' => false],
                    ['text' => 'Hipoactivacion de regiones frontales o hiperactivacion del hemisferio derecho', 'correct' => true],
                    ['text' => 'Maduracion tardia del cerebro posterior', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que funcion tiene la region anterior del lobulo temporal en la percepcion de expresiones faciales?',
                'answers' => [
                    ['text' => 'Entiende y recuerda el caracter de dichas expresiones', 'correct' => true],
                    ['text' => 'Reconoce la expresion facial en el momento', 'correct' => false],
                    ['text' => 'Procesa la informacion sensorial del tacto', 'correct' => false],
                    ['text' => 'Controla los musculos faciales', 'correct' => false],
                ]
            ],
            [
                'text' => 'A que edad ocurre la virilizacion del cuerpo calloso?',
                'answers' => [
                    ['text' => '4 anos', 'correct' => false],
                    ['text' => '6 anos', 'correct' => false],
                    ['text' => '8 anos', 'correct' => true],
                    ['text' => '12 anos', 'correct' => false],
                ]
            ],
            [
                'text' => 'Cual es la caracteristica unica del sentido del olfato?',
                'answers' => [
                    ['text' => 'Es el ultimo sentido en desarrollarse', 'correct' => false],
                    ['text' => 'No va al talamo sino directamente a la corteza', 'correct' => true],
                    ['text' => 'Solo funciona en la edad adulta', 'correct' => false],
                    ['text' => 'Depende del lobulo frontal', 'correct' => false],
                ]
            ],
            [
                'text' => 'A que edad comienza la habilidad de demorar la recompensa segun Mischel?',
                'answers' => [
                    ['text' => '2 anos', 'correct' => false],
                    ['text' => '4 anos', 'correct' => true],
                    ['text' => '6 anos', 'correct' => false],
                    ['text' => '8 anos', 'correct' => false],
                ]
            ],
            [
                'text' => 'Los lobulos parietales son funcionales:',
                'answers' => [
                    ['text' => 'Despues del primer ano de vida', 'correct' => false],
                    ['text' => 'Antes del nacimiento', 'correct' => true],
                    ['text' => 'A partir de los 3 anos', 'correct' => false],
                    ['text' => 'En la adolescencia', 'correct' => false],
                ]
            ],
            [
                'text' => 'El efecto de modulacion se refiere a:',
                'answers' => [
                    ['text' => 'El control del hemisferio derecho sobre el izquierdo', 'correct' => true],
                    ['text' => 'La regulacion del sueno', 'correct' => false],
                    ['text' => 'El desarrollo del lenguaje', 'correct' => false],
                    ['text' => 'La maduracion del cerebelo', 'correct' => false],
                ]
            ],
            [
                'text' => 'Las funciones ejecutivas incluyen:',
                'answers' => [
                    ['text' => 'Solo procesos motores', 'correct' => false],
                    ['text' => 'Solo procesos sensoriales', 'correct' => false],
                    ['text' => 'Procesos cognitivos, emocionales y de regulacion de respuestas conductuales', 'correct' => true],
                    ['text' => 'Unicamente la memoria a largo plazo', 'correct' => false],
                ]
            ],
        ]
    ],

    // CAPITULO 3: Plasticidad Cerebral
    3 => [
        'name' => 'Autoevaluacion - Capitulo 3',
        'intro' => 'Test de autoevaluacion del Capitulo 3: Plasticidad Cerebral',
        'questions' => [
            [
                'text' => 'Que es la plasticidad cerebral?',
                'answers' => [
                    ['text' => 'La rigidez de las estructuras cerebrales', 'correct' => false],
                    ['text' => 'La capacidad del cerebro para reorganizar sus conexiones en respuesta a experiencias', 'correct' => true],
                    ['text' => 'El tamano fijo del cerebro', 'correct' => false],
                    ['text' => 'La cantidad de neuronas al nacer', 'correct' => false],
                ]
            ],
            [
                'text' => 'Cual es la diferencia entre periodo critico y periodo sensible?',
                'answers' => [
                    ['text' => 'No hay diferencia, son sinonimos', 'correct' => false],
                    ['text' => 'El periodo critico es mas largo', 'correct' => false],
                    ['text' => 'El periodo critico es mas estricto e irreversible, el sensible es mas flexible', 'correct' => true],
                    ['text' => 'El periodo sensible ocurre antes que el critico', 'correct' => false],
                ]
            ],
            [
                'text' => 'Hasta que edad aproximadamente se extiende la plasticidad en areas frontales?',
                'answers' => [
                    ['text' => '2 anos', 'correct' => false],
                    ['text' => '7 anos', 'correct' => false],
                    ['text' => '18 anos o mas (7000 dias)', 'correct' => true],
                    ['text' => '10 anos', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que es la potenciacion a largo plazo (PLP)?',
                'answers' => [
                    ['text' => 'La muerte de neuronas', 'correct' => false],
                    ['text' => 'El refuerzo sinaptico tras descargas neurales intensas', 'correct' => true],
                    ['text' => 'La reduccion de sinapsis', 'correct' => false],
                    ['text' => 'El crecimiento del craneo', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que establece el Principio de Kennard?',
                'answers' => [
                    ['text' => 'Las lesiones en adultos se recuperan mejor', 'correct' => false],
                    ['text' => 'La recuperacion funcional es mayor cuando las lesiones ocurren en edades tempranas', 'correct' => true],
                    ['text' => 'El cerebro no puede recuperarse de lesiones', 'correct' => false],
                    ['text' => 'Solo los ninos mayores de 10 anos pueden recuperarse', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que es la apoptosis?',
                'answers' => [
                    ['text' => 'La creacion de nuevas neuronas', 'correct' => false],
                    ['text' => 'La muerte celular programada para optimizar redes neuronales', 'correct' => true],
                    ['text' => 'El crecimiento de dendritas', 'correct' => false],
                    ['text' => 'La formacion de mielina', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que beneficio cognitivo confiere el bilinguismo?',
                'answers' => [
                    ['text' => 'Ninguno demostrado', 'correct' => false],
                    ['text' => 'Solo mejora la pronunciacion', 'correct' => false],
                    ['text' => 'Mejora el control inhibitorio y la flexibilidad cognitiva', 'correct' => true],
                    ['text' => 'Reduce la memoria', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que es la reserva cognitiva?',
                'answers' => [
                    ['text' => 'La cantidad de neuronas de reserva', 'correct' => false],
                    ['text' => 'Ventaja funcional de la estimulacion continua que protege contra deterioro', 'correct' => true],
                    ['text' => 'El espacio libre en el cerebro', 'correct' => false],
                    ['text' => 'La memoria a corto plazo', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que estrategia de estudio favorece mejor el aprendizaje duradero?',
                'answers' => [
                    ['text' => 'Estudiar muchas horas seguidas (teoria acumulativa)', 'correct' => false],
                    ['text' => 'Estimulos cortos y distribuidos de 20-25 minutos (teoria distributiva)', 'correct' => true],
                    ['text' => 'Estudiar solo antes del examen', 'correct' => false],
                    ['text' => 'No importa la estrategia', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que son las redes perineuronales (PNNs)?',
                'answers' => [
                    ['text' => 'Tipos de neuronas', 'correct' => false],
                    ['text' => 'Neurotransmisores', 'correct' => false],
                    ['text' => 'Estructuras de matriz extracelular cuya maduracion cierra el periodo critico', 'correct' => true],
                    ['text' => 'Vasos sanguineos del cerebro', 'correct' => false],
                ]
            ],
        ]
    ],

    // CAPITULO 4: Sinaptogenesis
    4 => [
        'name' => 'Autoevaluacion - Capitulo 4',
        'intro' => 'Test de autoevaluacion del Capitulo 4: Sinaptogenesis',
        'questions' => [
            [
                'text' => 'Que es la sinaptogenesis?',
                'answers' => [
                    ['text' => 'La muerte de sinapsis', 'correct' => false],
                    ['text' => 'El proceso de formacion de nuevas conexiones entre neuronas', 'correct' => true],
                    ['text' => 'La mielinizacion de axones', 'correct' => false],
                    ['text' => 'La division celular', 'correct' => false],
                ]
            ],
            [
                'text' => 'Hasta que edad aproximadamente se extiende la poda sinaptica?',
                'answers' => [
                    ['text' => '2 anos', 'correct' => false],
                    ['text' => '8 anos', 'correct' => false],
                    ['text' => '16 anos', 'correct' => true],
                    ['text' => '25 anos', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que significa desarrollo cerebral heterocronico?',
                'answers' => [
                    ['text' => 'Todas las regiones maduran al mismo tiempo', 'correct' => false],
                    ['text' => 'Distintas regiones maduran a ritmos diferentes', 'correct' => true],
                    ['text' => 'El cerebro no cambia con el tiempo', 'correct' => false],
                    ['text' => 'Solo el lobulo frontal se desarrolla', 'correct' => false],
                ]
            ],
            [
                'text' => 'A que edad aproximadamente se completa la maduracion de la corteza prefrontal?',
                'answers' => [
                    ['text' => '10 anos', 'correct' => false],
                    ['text' => '18 anos', 'correct' => false],
                    ['text' => 'Alrededor de los 30 anos', 'correct' => true],
                    ['text' => '5 anos', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que funcion tiene la microglia en la poda sinaptica?',
                'answers' => [
                    ['text' => 'Crea nuevas sinapsis', 'correct' => false],
                    ['text' => 'Participa en la eliminacion de sinapsis', 'correct' => true],
                    ['text' => 'Produce mielina', 'correct' => false],
                    ['text' => 'Transporta nutrientes', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que es la fuerza sinaptica?',
                'answers' => [
                    ['text' => 'El tamano de la sinapsis', 'correct' => false],
                    ['text' => 'El refuerzo y eficacia de las sinapsis existentes por practica', 'correct' => true],
                    ['text' => 'La velocidad de transmision', 'correct' => false],
                    ['text' => 'La cantidad de neurotransmisores', 'correct' => false],
                ]
            ],
            [
                'text' => 'Donde persiste la neurogenesis en la edad adulta?',
                'answers' => [
                    ['text' => 'En todo el cerebro', 'correct' => false],
                    ['text' => 'En el hipocampo y bulbo olfatorio', 'correct' => true],
                    ['text' => 'En la corteza motora', 'correct' => false],
                    ['text' => 'No hay neurogenesis adulta', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que receptor es crucial para la induccion de la potenciacion a largo plazo?',
                'answers' => [
                    ['text' => 'Receptor de dopamina', 'correct' => false],
                    ['text' => 'Receptor NMDA', 'correct' => true],
                    ['text' => 'Receptor de serotonina', 'correct' => false],
                    ['text' => 'Receptor GABA', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que es el eje HPA?',
                'answers' => [
                    ['text' => 'Un tipo de neurona', 'correct' => false],
                    ['text' => 'Sistema de regulacion del estres (Hipotalamo-Hipofisis-Adrenal)', 'correct' => true],
                    ['text' => 'Una region del cerebelo', 'correct' => false],
                    ['text' => 'Un neurotransmisor', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que factor de transcripcion es importante para la consolidacion del aprendizaje?',
                'answers' => [
                    ['text' => 'ADN polimerasa', 'correct' => false],
                    ['text' => 'CREB', 'correct' => true],
                    ['text' => 'Hemoglobina', 'correct' => false],
                    ['text' => 'Insulina', 'correct' => false],
                ]
            ],
        ]
    ],

    // CAPITULO 5: Periodo Sensoriomotor
    5 => [
        'name' => 'Autoevaluacion - Capitulo 5',
        'intro' => 'Test de autoevaluacion del Capitulo 5: Periodo Sensoriomotor',
        'questions' => [
            [
                'text' => 'Que es el periodo sensoriomotor segun Piaget?',
                'answers' => [
                    ['text' => 'La segunda etapa del desarrollo', 'correct' => false],
                    ['text' => 'La primera etapa del desarrollo cognitivo (0-24 meses)', 'correct' => true],
                    ['text' => 'Una etapa que ocurre en la adolescencia', 'correct' => false],
                    ['text' => 'El periodo de desarrollo del lenguaje', 'correct' => false],
                ]
            ],
            [
                'text' => 'Cual es el logro principal del periodo sensoriomotor?',
                'answers' => [
                    ['text' => 'El desarrollo del lenguaje', 'correct' => false],
                    ['text' => 'La permanencia del objeto', 'correct' => true],
                    ['text' => 'La capacidad de leer', 'correct' => false],
                    ['text' => 'El pensamiento abstracto', 'correct' => false],
                ]
            ],
            [
                'text' => 'A que edad comienza el control inhibitorio?',
                'answers' => [
                    ['text' => '6 meses', 'correct' => false],
                    ['text' => '12 meses', 'correct' => false],
                    ['text' => '18 meses', 'correct' => true],
                    ['text' => '36 meses', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que son las reacciones circulares terciarias?',
                'answers' => [
                    ['text' => 'Movimientos involuntarios del bebe', 'correct' => false],
                    ['text' => 'Experimentacion y curiosidad activa (12-18 meses)', 'correct' => true],
                    ['text' => 'Reflejos innatos', 'correct' => false],
                    ['text' => 'El pensamiento simbolico', 'correct' => false],
                ]
            ],
            [
                'text' => 'A que edad se percibe la memoria de trabajo?',
                'answers' => [
                    ['text' => 'Al nacer', 'correct' => false],
                    ['text' => 'A los 8 meses', 'correct' => true],
                    ['text' => 'A los 2 anos', 'correct' => false],
                    ['text' => 'A los 5 anos', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que es la Cisura de Rolando?',
                'answers' => [
                    ['text' => 'Un tipo de neurona', 'correct' => false],
                    ['text' => 'Linea de division entre cerebro sensitivo y area motora', 'correct' => true],
                    ['text' => 'Un neurotransmisor', 'correct' => false],
                    ['text' => 'Una estructura del cerebelo', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que son las neuronas de espejo?',
                'answers' => [
                    ['text' => 'Neuronas que reflejan luz', 'correct' => false],
                    ['text' => 'Neuronas que permiten la anticipacion de movimientos', 'correct' => true],
                    ['text' => 'Neuronas solo presentes en adultos', 'correct' => false],
                    ['text' => 'Neuronas del sistema visual', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que caracteriza al egocentrismo extremo del bebe?',
                'answers' => [
                    ['text' => 'Comprende todos los puntos de vista', 'correct' => false],
                    ['text' => 'No comprende el mundo mas alla de su punto de vista actual', 'correct' => true],
                    ['text' => 'Es muy sociable', 'correct' => false],
                    ['text' => 'Tiene pensamiento abstracto', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que teoria rige actualmente el desarrollo humano segun el material?',
                'answers' => [
                    ['text' => 'Solo la teoria de Piaget', 'correct' => false],
                    ['text' => 'Solo la teoria de Erikson', 'correct' => false],
                    ['text' => 'La teoria epigenetica', 'correct' => true],
                    ['text' => 'La teoria conductista', 'correct' => false],
                ]
            ],
            [
                'text' => 'Que conecta el Fasciculo Longitudinal Superior?',
                'answers' => [
                    ['text' => 'Los dos ojos', 'correct' => false],
                    ['text' => 'El lobulo frontal con areas parietales', 'correct' => true],
                    ['text' => 'El cerebro con la medula', 'correct' => false],
                    ['text' => 'Los dos oidos', 'correct' => false],
                ]
            ],
        ]
    ],
];
