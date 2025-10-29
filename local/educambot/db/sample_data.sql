-- Sample data for local_educambot plugin
-- This file can be used to populate the knowledge base with test data

-- Sample Rules
INSERT INTO {local_educambot_rule} (pattern, synonyms, keywords, response, roles, contexts, suggested, enabled, timecreated, timemodified)
VALUES
('¿Cómo accedo a la plataforma?
¿Cómo ingreso a Moodle?
¿Cómo entro al campus virtual?',
'ingresar
acceder
entrar
loguearse',
'acceso, login, ingresar, plataforma',
'<p>Para acceder a la plataforma, sigue estos pasos:</p>
<ol>
<li>Ve a la página principal de Moodle</li>
<li>Haz clic en "Iniciar sesión" en la esquina superior derecha</li>
<li>Ingresa tu nombre de usuario y contraseña</li>
<li>Haz clic en "Entrar"</li>
</ol>
<p>Si olvidaste tu contraseña, haz clic en "¿Olvidó su nombre de usuario o contraseña?"</p>',
NULL,
NULL,
1,
1,
UNIX_TIMESTAMP(),
UNIX_TIMESTAMP());

INSERT INTO {local_educambot_rule} (pattern, synonyms, keywords, response, roles, contexts, suggested, enabled, timecreated, timemodified)
VALUES
('¿Cómo subo una tarea?
¿Cómo envío mi tarea?
¿Dónde entrego las tareas?',
'enviar
subir
entregar
cargar',
'tarea, enviar, subir, entregar',
'<p>Para subir una tarea en Moodle:</p>
<ol>
<li>Accede al curso correspondiente</li>
<li>Busca la actividad de "Tarea" en la sección correspondiente</li>
<li>Haz clic en el nombre de la tarea</li>
<li>Haz clic en "Agregar entrega"</li>
<li>Arrastra tu archivo o haz clic en el ícono para seleccionarlo</li>
<li>Haz clic en "Guardar cambios"</li>
</ol>
<p><strong>Importante:</strong> Verifica que tu archivo se haya cargado correctamente antes de cerrar la página.</p>',
NULL,
NULL,
1,
1,
UNIX_TIMESTAMP(),
UNIX_TIMESTAMP());

INSERT INTO {local_educambot_rule} (pattern, synonyms, keywords, response, roles, contexts, suggested, enabled, timecreated, timemodified)
VALUES
('¿Cómo cambio mi contraseña?
¿Dónde modifico mi password?
¿Cómo actualizo mi clave?',
'modificar
actualizar
editar
cambiar',
'contraseña, password, clave, seguridad',
'<p>Para cambiar tu contraseña:</p>
<ol>
<li>Inicia sesión en Moodle</li>
<li>Haz clic en tu nombre en la esquina superior derecha</li>
<li>Selecciona "Preferencias" en el menú</li>
<li>Haz clic en "Cambiar contraseña"</li>
<li>Ingresa tu contraseña actual</li>
<li>Ingresa tu nueva contraseña dos veces</li>
<li>Haz clic en "Guardar cambios"</li>
</ol>
<p><strong>Recomendación:</strong> Usa una contraseña segura con al menos 8 caracteres, incluyendo mayúsculas, minúsculas y números.</p>',
NULL,
NULL,
0,
1,
UNIX_TIMESTAMP(),
UNIX_TIMESTAMP());

-- Sample Knowledge Base Entries

-- Create a topic first
INSERT INTO {local_educambot_topic} (name, description, parentid, sortorder, timecreated, timemodified)
VALUES
('Primeros Pasos', 'Información básica para comenzar a usar Moodle', NULL, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- Get the topic ID (this would need to be done programmatically)
-- For this example, assume topic ID = 1

INSERT INTO {local_educambot_knowledge} (title, summary, content, contentformat, type, externalurl, tags, enabled, createdby, updatedby, timecreated, timemodified)
VALUES
('Guía de inicio rápido',
'<p>Aprende los conceptos básicos de Moodle en pocos minutos</p>',
'<p>Moodle es una plataforma de aprendizaje en línea que te permite acceder a cursos, materiales de estudio, tareas y comunicarte con tus profesores y compañeros.</p>
<h3>Conceptos básicos:</h3>
<ul>
<li><strong>Curso:</strong> Espacio virtual donde se desarrolla el contenido académico</li>
<li><strong>Actividad:</strong> Tareas, foros, cuestionarios, etc.</li>
<li><strong>Recurso:</strong> Archivos, enlaces, páginas de contenido</li>
<li><strong>Calificaciones:</strong> Puedes ver tus notas en el libro de calificaciones</li>
</ul>
<p>Explora tu curso haciendo clic en las diferentes secciones y actividades.</p>',
1,
'guide',
NULL,
'moodle, inicio, tutorial, básico',
1,
NULL,
NULL,
UNIX_TIMESTAMP(),
UNIX_TIMESTAMP());

INSERT INTO {local_educambot_knowledge} (title, summary, content, contentformat, type, externalurl, tags, enabled, createdby, updatedby, timecreated, timemodified)
VALUES
('¿Cómo navegar por un curso?',
'<p>Aprende a moverte eficientemente dentro de tus cursos de Moodle</p>',
'<p>La navegación en Moodle es intuitiva una vez que conoces los elementos principales:</p>
<h3>Estructura de un curso:</h3>
<ul>
<li><strong>Panel lateral:</strong> Contiene bloques con información útil como calendario, eventos próximos, y navegación</li>
<li><strong>Área principal:</strong> Muestra el contenido del curso organizado por secciones o temas</li>
<li><strong>Menú superior:</strong> Te permite volver al inicio, ver tus cursos y acceder a tu perfil</li>
</ul>
<h3>Consejos de navegación:</h3>
<ol>
<li>Usa el índice de curso (si está disponible) para saltar directamente a una sección</li>
<li>El "breadcrumb" (ruta de navegación) te muestra dónde estás y te permite retroceder</li>
<li>Activa las notificaciones para estar al tanto de nuevas actividades</li>
</ol>',
1,
'faq',
NULL,
'navegación, curso, interfaz',
1,
NULL,
NULL,
UNIX_TIMESTAMP(),
UNIX_TIMESTAMP());

-- Link knowledge to topic (assuming topic ID = 1, knowledge IDs = 1 and 2)
-- INSERT INTO {local_educambot_kn_topic} (knowledgeid, topicid) VALUES (1, 1);
-- INSERT INTO {local_educambot_kn_topic} (knowledgeid, topicid) VALUES (2, 1);

-- Sample relation between knowledge entries
-- INSERT INTO {local_educambot_relation} (sourceid, targetid, relationtype) VALUES (1, 2, 'related');
