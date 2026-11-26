# Instalación y Configuración de Procesamiento de Video (FFmpeg) — INFONATEC

## 1. Evaluación técnica: FFmpeg en servidor vs. extracción en navegador

| Aspecto | FFmpeg en servidor (recomendado para producción) | Captura asistida en navegador (`<video>` + `<canvas>`) |
| :--- | :--- | :--- |
| **Compatibilidad de formatos** | Soporta MP4 (H.264/HEVC), MOV de iPhone y WebM sin depender del navegador del usuario. | Depende de los códecs instalados en cada navegador (por ejemplo, MOV/HEVC de iPhone suele fallar en Chrome/Edge de Windows). |
| **Memoria y rendimiento** | Usa subproceso externo acotado (`-threads 2`, timeout de 15 s, fast-seek `-ss` antes de `-i`), sin cargar el video en la memoria de PHP. | Decodifica fotogramas en el dispositivo del usuario; videos 4K largos pueden consumir bastante RAM en celulares de gama baja. |
| **Seguridad** | El servidor procesa el video privado directamente y valida la imagen resultante con `finfo` + `getimagesize`. | El servidor **siempre revalida** la imagen subida (`frame_image`) por MIME real, `getimagesize()`, dimensiones mínimas y peso máximo (5 MB). |

Este módulo soporta **ambos modos**:
1. **Con FFmpeg instalado:** el servidor extrae el fotograma JPEG directamente desde el video protegido (`origen_extraccion = 'ffmpeg'`).
2. **Sin FFmpeg (respaldo automático en navegador):** si FFmpeg no está instalado, la interfaz muestra un aviso claro, captura el fotograma del `<video>` en `<canvas>` y lo envía al servidor para validación estricta y almacenamiento (`origen_extraccion = 'navegador'`). Si se llama explícitamente `modo_extraccion=ffmpeg` sin FFmpeg instalado, el servidor responde HTTP `503` con un mensaje comprensible.

---

## 2. Límites y formatos admitidos

- **Formatos de video admitidos:** `.mp4` (`video/mp4`), `.mov` (`video/quicktime`), `.webm` (`video/webm`).
- **Validación real en servidor:** extensión, tamaño (`> 0` y `<= límite`), MIME real (`finfo_file`), firma binaria de cabecera (`ftyp` para MP4/MOV, EBML `\x1A\x45\xDF\xA3` para WebM) y validación de stream con `ffprobe` cuando está disponible.
- **Límite por defecto:** `35 MB` (`VIDEO_MAX_MB=35` en `.env`), acotado además por `upload_max_filesize` y `post_max_size` de `php.ini` (en XAMPP por defecto son `40M`).
- **Duración máxima:** `600` segundos (10 minutos).
- **Imágenes extraídas:** JPEG/PNG/WebP reales de hasta `5 MB`, guardadas en `public/uploads/recorridos/escenas/` con nombres aleatorios y bloqueo de ejecución de scripts vía `.htaccess`.
- **Videos originales:** guardados en `storage/recorridos_videos/` (fuera de `public/`, con `.htaccess` `Require all denied`) y transmitidos únicamente mediante endpoints autenticados/autorizados con streaming por rangos (`HTTP 206`) en bloques de 64 KB.

---

## 3. Instalar y configurar FFmpeg en Windows / XAMPP

### Opción A: Con `winget` (Windows 10/11)
1. Abre PowerShell y ejecuta:
   ```powershell
   winget install Gyan.FFmpeg
   ```
2. Reinicia Apache en XAMPP Control Panel para que tome el nuevo `PATH`, o indica la ruta exacta en `.env`.

### Opción B: Instalación manual en `C:\ffmpeg` (recomendada para XAMPP)
1. Descarga el paquete `ffmpeg-release-essentials.zip` desde `https://www.gyan.dev/ffmpeg/builds/`.
2. Extrae el contenido y coloca los ejecutables en:
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

## 5. Requisitos de Apache y migración de BD

1. Asegúrate de que Apache tenga `mod_rewrite` y `AllowOverride All` para que `.htaccess` bloquee `storage/` y la ejecución de scripts dentro de `public/uploads/recorridos/escenas/`.
2. Ejecuta la migración incremental (conserva datos existentes):
   ```powershell
   Get-Content database\migrations\20261126_recorridos_video.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4
   ```
3. Verifica el estado en el navegador o mediante `GET /api/recorridos/procesador/estado` (con sesión iniciada).
