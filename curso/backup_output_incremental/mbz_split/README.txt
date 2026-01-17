HUELLAS INVISIBLES - Curso Completo MBZ
========================================

Este directorio contiene el archivo MBZ dividido en partes
para poder descargarlo desde el repositorio.

Archivo original: backup-huellas-fase11.mbz (158 MB)
Partes: 4 archivos de ~50MB cada uno

PARA RECONSTRUIR EL ARCHIVO MBZ:
--------------------------------

En Linux/Mac:
  cat huellas-completo.mbz.part_* > huellas-completo.mbz

En Windows (PowerShell):
  Get-Content huellas-completo.mbz.part_* -Encoding Byte -ReadCount 0 | Set-Content huellas-completo.mbz -Encoding Byte

En Windows (CMD):
  copy /b huellas-completo.mbz.part_aa+huellas-completo.mbz.part_ab+huellas-completo.mbz.part_ac+huellas-completo.mbz.part_ad huellas-completo.mbz

CONTENIDO DEL CURSO:
--------------------
- Presentacion del Curso
- Capitulo 1: Desarrollo Neurologico Infantil
- Capitulo 2: Funciones Emocionales
- Capitulo 3: Plasticidad Cerebral
- Capitulo 4: Sinaptogenesis
- Capitulo 5: Periodo Sensoriomotor
- Capitulo 6: Estres y Apego
- Capitulo 7: Teoria Epigenetica
- Capitulo 8: Reflejo de Apnea
- Capitulo 9: Socializacion en la Primera Infancia
- Capitulo 10: La Atencion
- Cierre del Curso (Intro + Encuesta + Certificado)

Total: 75 actividades, 12 secciones

NOTA: Los quizzes estan vacios. Importar preguntas desde
los archivos GIFT en: curso/backup_output_incremental/*.gift.txt
