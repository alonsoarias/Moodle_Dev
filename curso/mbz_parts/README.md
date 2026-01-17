# Partes del Archivo MBZ - Huellas Invisibles

Este directorio contiene el archivo de backup de Moodle dividido en partes para poder almacenarlo en el repositorio Git.

## Archivos

| Archivo | Tamaño | Descripción |
|---------|--------|-------------|
| `huellas-invisibles-part1.zip` | ~50 MB | Parte 1 de 4 |
| `huellas-invisibles-part2.zip` | ~50 MB | Parte 2 de 4 |
| `huellas-invisibles-part3.zip` | ~50 MB | Parte 3 de 4 |
| `huellas-invisibles-part4.zip` | ~11 MB | Parte 4 de 4 |

**Tamaño total:** ~161 MB

## Cómo Reconstruir el Archivo MBZ

### En Linux/Mac

```bash
# 1. Descomprimir todas las partes
unzip huellas-invisibles-part1.zip
unzip huellas-invisibles-part2.zip
unzip huellas-invisibles-part3.zip
unzip huellas-invisibles-part4.zip

# 2. Unir las partes en un solo archivo
cat huellas-invisibles.mbz.part* > backup-huellas-invisibles.mbz

# 3. Limpiar archivos temporales
rm huellas-invisibles.mbz.part*

# 4. Verificar el archivo
ls -la backup-huellas-invisibles.mbz
```

### En Windows (PowerShell)

```powershell
# 1. Descomprimir todas las partes
Expand-Archive -Path "huellas-invisibles-part1.zip" -DestinationPath "."
Expand-Archive -Path "huellas-invisibles-part2.zip" -DestinationPath "."
Expand-Archive -Path "huellas-invisibles-part3.zip" -DestinationPath "."
Expand-Archive -Path "huellas-invisibles-part4.zip" -DestinationPath "."

# 2. Unir las partes
Get-Content huellas-invisibles.mbz.part* -Encoding Byte -ReadCount 0 | Set-Content backup-huellas-invisibles.mbz -Encoding Byte

# 3. Limpiar archivos temporales
Remove-Item huellas-invisibles.mbz.part*
```

### En Windows (CMD)

```cmd
REM 1. Descomprimir con tu herramienta favorita (7-Zip, WinRAR, etc.)

REM 2. Unir las partes
copy /b huellas-invisibles.mbz.partaa+huellas-invisibles.mbz.partab+huellas-invisibles.mbz.partac+huellas-invisibles.mbz.partad backup-huellas-invisibles.mbz

REM 3. Eliminar partes
del huellas-invisibles.mbz.part*
```

## Script Automático (Linux/Mac)

También puedes usar este script para automatizar el proceso:

```bash
#!/bin/bash
# rebuild_mbz.sh

echo "Reconstruyendo archivo MBZ de Huellas Invisibles..."

# Descomprimir
for i in 1 2 3 4; do
    echo "Descomprimiendo parte $i..."
    unzip -o "huellas-invisibles-part$i.zip"
done

# Unir
echo "Uniendo partes..."
cat huellas-invisibles.mbz.part* > backup-huellas-invisibles.mbz

# Limpiar
echo "Limpiando archivos temporales..."
rm -f huellas-invisibles.mbz.part*

# Verificar
SIZE=$(ls -lh backup-huellas-invisibles.mbz | awk '{print $5}')
echo ""
echo "¡Completado!"
echo "Archivo: backup-huellas-invisibles.mbz"
echo "Tamaño: $SIZE"
```

## Verificación

El archivo reconstruido debe tener las siguientes características:

- **Nombre:** `backup-huellas-invisibles.mbz`
- **Tamaño aproximado:** 161 MB (168,956,757 bytes)
- **Formato:** ZIP (Moodle Backup)

## Restaurar en Moodle

Una vez reconstruido el archivo `.mbz`:

1. Inicia sesión en https://campus.rednadi.com como administrador
2. Ve a **Administración del sitio** → **Cursos** → **Restaurar curso**
3. Sube el archivo `backup-huellas-invisibles.mbz`
4. Sigue el asistente de restauración
5. El curso "Huellas Invisibles" se creará con todas sus actividades

## Contenido del Curso

- **12 secciones** (Presentación + 10 capítulos + Cierre)
- **54 actividades** (recursos y etiquetas)
- **54 archivos** (PDFs, DOCX, PNG)
