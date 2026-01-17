# Curso Huellas Invisibles - Backup MBZ

## Descripción del Curso

**Nombre completo:** Huellas Invisibles: Neurociencia del Desarrollo Infantil
**Nombre corto:** huellas-invisibles
**Sitio:** https://campus.rednadi.com
**Directora:** Jaqui Esquitino

## Estructura del Curso

El curso está compuesto por **12 secciones**:

### Sección 0: Presentación del Curso
- Título, Presentación y Programa del Curso
- Imagen del Curso

### Capítulos 1-10: Contenido Principal

| Capítulo | Tema | Materiales |
|----------|------|------------|
| 1 | Desarrollo Neurológico Infantil | Libro PDF, Documento de estudio, Glosario, Bibliografía, Test, Material complementario |
| 2 | Funciones Emocionales | Libro PDF, Documento complementario, Bibliografía, Glosario, Test |
| 3 | Plasticidad Cerebral | Libro PDF, Documento de estudio, Glosario, Bibliografía, Test |
| 4 | Sinaptogénesis | Libro PDF, Documento de estudio, Glosario, Bibliografía, Test |
| 5 | Período Sensoriomotor | Libro PDF, Documento complementario, Glosario, Bibliografía, Test |
| 6 | Estrés y Apego | Libro PDF, Documento de estudio, Glosario, Bibliografía, Test |
| 7 | Teoría Epigenética | Libro PDF, Documento de estudio, Glosario, Bibliografía, Test |
| 8 | Reflejo de Apnea | Libro PDF, Documento de estudio, Glosario, Bibliografía, Test |
| 9 | Socialización en la Primera Infancia | Libro PDF, Documento de estudio, Glosario, Bibliografía, Test |
| 10 | La Atención | Libro PDF, Documento de estudio, Glosario, Bibliografía, Test |

### Sección 11: Cierre del Curso
- Encuesta de Opinión

## Contenido del Backup MBZ

El archivo `.mbz` generado contiene:

- **54 actividades** (mod_resource y mod_label)
- **12 secciones** de curso
- **54 archivos binarios** (PDFs, DOCX, PNG)
- **Tamaño total:** ~161 MB

## Cómo Generar el Archivo MBZ

### Requisitos

- PHP 7.4 o superior
- Extensión ZIP de PHP habilitada
- Los archivos del curso deben estar en `mbz_generator/curso_content/curso/Huellas invisibles/`

### Instrucciones

1. Asegúrese de que los archivos del curso estén extraídos:
   ```bash
   cd /path/to/Moodle_Dev
   git archive aa84bc31cdf1eaaf410f1798456979b9fbcd77ae -- curso/ | tar -x -C mbz_generator/curso_content/
   ```

2. Ejecute el generador:
   ```bash
   cd mbz_generator
   php generate_mbz.php
   ```

3. El archivo se generará en:
   ```
   mbz_generator/backup_output/backup-huellas-invisibles-YYYYMMDD.mbz
   ```

## Cómo Restaurar en Moodle

1. Inicie sesión como administrador en https://campus.rednadi.com
2. Vaya a **Administración del sitio** → **Cursos** → **Restaurar curso**
3. Suba el archivo `backup-huellas-invisibles-YYYYMMDD.mbz`
4. Siga el asistente de restauración
5. Seleccione crear un curso nuevo o restaurar sobre uno existente

## Especificaciones Técnicas

- **Versión de Moodle compatible:** 4.4+
- **Formato del curso:** Topics (Temas)
- **Formato de backup:** Moodle 2.x (moodle2)
- **Codificación:** UTF-8

## Archivos Incluidos

```
curso/
├── README.md                           # Este archivo
├── generate_mbz.php                    # Script generador del MBZ
└── backup-huellas-invisibles-*.mbz     # Archivo de backup (si se genera localmente)
```

## Notas Importantes

- El archivo `.mbz` es demasiado grande para incluirse en el repositorio Git (>100MB)
- Use el script `generate_mbz.php` para regenerar el backup cuando sea necesario
- Los archivos fuente del curso están en el commit `aa84bc31cdf1eaaf410f1798456979b9fbcd77ae`

## Soporte

Para soporte técnico, contacte a:
- Sitio web: https://www.rednadi.com
- Moodle: https://campus.rednadi.com
