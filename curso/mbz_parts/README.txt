MBZ Huellas Invisibles v4.0
============================

Archivo: backup-huellas-invisibles-v4.mbz
Tamano total: ~159 MB
Partes: 7

Para reconstruir el archivo MBZ:
---------------------------------

En Linux/Mac:
  cat huellas-invisibles-v4.mbz.part_* > huellas-invisibles-v4.mbz

En Windows (PowerShell):
  Get-Content huellas-invisibles-v4.mbz.part_* -Encoding Byte -ReadCount 0 | Set-Content huellas-invisibles-v4.mbz -Encoding Byte

En Windows (CMD):
  copy /b huellas-invisibles-v4.mbz.part_aa+huellas-invisibles-v4.mbz.part_ab+huellas-invisibles-v4.mbz.part_ac+huellas-invisibles-v4.mbz.part_ad+huellas-invisibles-v4.mbz.part_ae+huellas-invisibles-v4.mbz.part_af+huellas-invisibles-v4.mbz.part_ag huellas-invisibles-v4.mbz

Verificar integridad (SHA256):
  Linux/Mac: sha256sum huellas-invisibles-v4.mbz
  Windows: certutil -hashfile huellas-invisibles-v4.mbz SHA256

Contenido del curso:
--------------------
- 12 secciones (Presentacion + 10 capitulos + Cierre)
- 64 actividades totales
- ~193 terminos de glosario
- 100 preguntas de autoevaluacion
- Encuesta de satisfaccion
- Certificado (requiere configuracion manual del template)

Compatible con: Moodle 4.4+
