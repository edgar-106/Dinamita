# Instalación y Configuración de Procesamiento de Video (FFmpeg) — INFONATEC

## 1. Evaluación técnica: FFmpeg en servidor vs. extracción en navegador

| Aspecto | FFmpeg en servidor (recomendado para producción) | Captura asistida en navegador (`<video>` + `<canvas>`) |
| :--- | :--- | :--- |
| **Compatibilidad de formatos** | Soporta MP4 (H.264/HEVC), MOV de iPhone y WebM sin depender del navegador del usuario. | Depende de los códecs instalados en cada navegador (por ejemplo, MOV/HEVC de iPhone suele fallar en Chrome/Edge de Windows). |
| **Memoria y rendimiento** | Subproceso externo acotado (`proc_open` con arreglo de argumentos sin shell, `-threads 2`, timeout de 15 s y fast-seek `-ss` antes de `-i`), sin cargar videos en la memoria de PHP. | Decodifica fotogramas en el dispositivo del usuario; videos 4K largos pueden consumir bastante RAM en celulares de gama baja. |
| **Seguridad** | El servidor procesa el video privado directamente y revalida la imagen resultante con `finfo` + `getimagesize()`. | El servidor **siempre revalida** la imagen subida (`frame`) por tamaño (`<= 8 MB`), MIME real (`finfo`), `getimagesize()` (`>= 100×100`, `<= 64 MP`) y orientación EXIF. |

El módulo soporta **ambos modos**:
1. **Con FFmpeg instalado:** el servidor extrae el fotograma JPEG directamente desde el video privado y lo registra en `recorrido_archivo` (`origen_extraccion = 'ffmpeg'`).
2. **Sin FFmpeg (respaldo automático en navegador):** si FFmpeg no está instalado, la interfaz muestra *"Captura desde navegador activa"*, captura el fotograma del `<video>` en `<canvas>` y lo envía al servidor para validación estricta (`origen_extraccion = 'navegador'`). Si se solicita `modo_extraccion=ffmpeg` sin FFmpeg instalado, el servidor responde HTTP `503` con instrucciones claras y sin dejar archivos temporales.

---

## 2. Límites y formatos admitidos

- **Formatos de video admitidos:** `.mp4` (`video/mp4`), `.mov` (`video/quicktime`), `.webm` (`video/webm`).
- **Validación real en servidor:** error de subida PHP, tamaño (`1 byte .. límite efectivo`), MIME real (`finfo_file`), firma binaria de cabecera (`ftyp` para MP4/MOV, EBML `\x1A\x45\xDF\xA3` para WebM) y validación de stream con `ffprobe` si está instalado.
- **Límite por defecto:** `35 MB` (`VIDEO_MAX_MB=35` en `.env`), acotado además por `upload_max_filesize` y `post_max_size` de `php.ini` (en XAMPP local por defecto `40M`).
- **Cantidad por recorrido:** máximo `10` videos privados por propiedad y `60` archivos de imagen totales entre borrador y publicación en `recorrido_archivo` (`30` escenas activas máximo por recorrido).
- **Imágenes extraídas:** JPG, PNG o WEBP de hasta `8 MB` (entre `100×100` y `24000 px` por lado, máximo `64 MP`), guardadas con nombre aleatorio en `storage/recorridos/` y entregadas únicamente por `/recorridos/imagen/{id_archivo}`.
- **Videos originales:** guardados fuera de acceso público en `storage/recorridos_videos/` (bloqueado por `.htaccess` `Require all denied`) y entregados solo por streaming `HTTP 206 Range` en bloques de 64 KB previa autorización en servidor.

---

## 3. Instalar y configurar FFmpeg en Windows / XAMPP

### Opción A: Con `winget` (Windows 10/11)
1. Abre PowerShell y ejecuta:
   ```powershell
   winget install Gyan.FFmpeg
   ```
2. Reinicia Apache en XAMPP Control Panel para que tome el nuevo `PATH`, o configura la ruta exacta en `.env`.

### Opción B: Instalación manual en `C:\ffmpeg` (recomendada para XAMPP)
1. Descarga `ffmpeg-release-essentials.zip` desde `https://www.gyan.dev/ffmpeg/builds/`.
2. Extrae los ejecutables en:
   - `C:\ffmpeg\bin\ffmpeg.exe`
   - `C:\ffmpeg\bin\ffprobe.exe`
3. Verifica en consola:
   ```cmd
   C:\ffmpeg\bin\ffmpeg.exe -version
   C:\ffmpeg\bin\ffprobe.exe -version
   ```
4. *(Opcional)* Configura las rutas en tu archivo `.env`:
   ```env
   FFMPEG_PATH=C:\ffmpeg\bin\ffmpeg.exe
   FFPROBE_PATH=C:\ffmpeg\bin\ffprobe.exe
   VIDEO_MAX_MB=35
   ```
   *(El sistema busca automáticamente en `.env`, `PATH`, `C:\ffmpeg\bin\` y `C:\xampp\ffmpeg\bin\`.)*

---

## 4. Ajustar límites de subida en `php.ini` (si subes videos mayores a 35 MB)

En `C:\xampp\php\php.ini`:
```ini
upload_max_filesize = 64M
post_max_size = 70M
max_execution_time = 120
```
Luego reinicia Apache en XAMPP y actualiza `VIDEO_MAX_MB=60` en `.env`.

---

## 5. Orden de migraciones SQL (bases existentes)

Ejecutar en PowerShell desde la raíz del proyecto (conserva datos y recorridos 360° existentes):

```powershell
# 1. Solo si falta la columna propiedad.fecha_retiro:
Get-Content database\migrations\20261124_mis_propiedades.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4
# 2. Tablas base de recorridos compartidos (recorrido_foto y recorrido_archivo):
Get-Content database\migrations\20261126_recorridos_fotos.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4
# 3. Tabla de videos privados conectada al recorrido compartido:
Get-Content database\migrations\20261126_recorridos_video.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4
```
