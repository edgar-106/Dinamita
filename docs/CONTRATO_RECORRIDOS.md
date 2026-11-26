# Contrato de Integración — Recorridos Interactivos por Escenas (INFONATEC)

Versión del contrato: **`infonatec-tour-v1`**

Este documento define el contrato común entre:
- **Módulo de Recorridos por Video** (`feature/recorridos-video`): carga de videos protegidos, extracción de fotogramas planos, definición de fragmentos (`inicio_segundos`, `fin_segundos`) y reproducción controlada.
- **Módulo de Recorridos por Fotografías / Panorámicas** (compañero): carga de fotos planas o panorámicas 360° y creación/edición de puntos interactivos.

Ambos módulos comparten **el mismo esquema base** (`recorrido`, `recorrido_escena`, `recorrido_conexion`), **el mismo adaptador JS** (`public/js/recorrido-contrato.js`) y **el mismo visor** (`app/views/tour/escenas.php` + `public/js/recorrido-visor.js`).

---

## 1. Esquema de base de datos compartido

Definido en `database/migrations/20261126_recorridos_video.sql` (sin alterar `paseo_virtual` ni datos previos):

### Tablas compartidas
1. **`recorrido`**:
   - `id_recorrido`, `id_propiedad` (UNIQUE FK → `propiedad`), `titulo`, `descripcion`, `estado` (`BORRADOR` | `PUBLICADO` | `INACTIVO`), `escena_inicial_id`, `fecha_creacion`, `fecha_actualizacion`.
2. **`recorrido_escena`**:
   - `id_escena` (`id`), `id_recorrido`, `nombre`, `tipo_visual` (`imagen` | `panorama`), `imagen_url`, `video_id` (NULL cuando proviene de fotografía), `momento_segundos` (NULL en foto), `inicio_segundos` (opcional), `fin_segundos` (opcional, `> inicio_segundos`), `orden`, `origen_extraccion` (`ffmpeg` | `navegador` | `foto` | `manual`).
   - Las escenas extraídas desde video siempre producen `tipo_visual = 'imagen'` (imagen plana, nunca panorama 360°).
3. **`recorrido_conexion`**:
   - `id_conexion` (`id`), `id_recorrido`, `escena_origen_id`, `escena_destino_id` (`<> escena_origen_id`), `etiqueta`, `posicion_sistema` (`plano_xy` | `panorama_yaw_pitch`), `posicion_json`.

### Tabla exclusiva del módulo de video
4. **`recorrido_video`**:
   - `id_video`, `id_recorrido`, `id_propiedad`, `id_propietario`, `nombre_original`, `archivo_interno` (nombre aleatorio generado en servidor dentro de `storage/recorridos_videos/`), `mime_type`, `tamano_bytes`, `duracion_segundos`, `ancho`, `alto`, `estado`, `mensaje_error`, `fecha_subida`.

---

## 2. Propuesta de coordenadas para puntos interactivos (a acordar)

Para soportar tanto **imágenes planas** (`tipo_visual = 'imagen'`) como **panoramas 360°** (`tipo_visual = 'panorama'`) sin acoplar ambos visores, `posicion` es un objeto JSON discriminado por `sistema`:

1. **Escena plana (`tipo_visual = 'imagen'`):**
   ```json
   {
     "sistema": "plano_xy",
     "x": 64.5,
     "y": 52.0
   }
   ```
   - `x` e `y` en porcentajes `0..100` respecto al borde superior izquierdo de la imagen renderizada (independiente de la resolución o pantalla).

2. **Escena panorámica (`tipo_visual = 'panorama'`):**
   ```json
   {
     "sistema": "panorama_yaw_pitch",
     "yaw": -110.0,
     "pitch": -4.0
   }
   ```
   - `yaw` en grados (`-180..180`) y `pitch` en grados (`-90..90`) compatibles directamente con **Pannellum** (`public/tour/lib/pannellum.js`).

> **Nota de coordinación:** el editor de fotografías puede seguir usando `plano_xy` para fotos normales o `panorama_yaw_pitch` para panorámicas; el visor único (`recorrido-visor.js`) ya interpreta ambos sistemas según `scene.tipo_visual`. Si tu módulo requiere campos extra en `posicion` (ej. `icono`, `rotacion`), se pueden añadir al objeto JSON sin romper las columnas de BD.

---

## 3. Formato JSON del contrato (`window.InfonatecTourContract`)

El adaptador `public/js/recorrido-contrato.js` expone:
- `InfonatecTourContract.normalizeTour(payload)`
- `InfonatecTourContract.normalizeScene(scene)`
- `InfonatecTourContract.normalizeConnection(conn)`
- `InfonatecTourContract.validatePosition(pos, visualType)`
- `InfonatecTourContract.getSamplePayload()` (fixture de ejemplo mixto: escena `imagen` de video + escena `panorama` de foto)

### Escena normalizada
```json
{
  "id": 101,
  "id_recorrido": 1,
  "nombre": "Sala",
  "tipo_visual": "imagen",
  "imagen_url": "http://localhost/Bienes%20raices/public/uploads/recorridos/escenas/esc_1_xxxx.jpg",
  "video_id": 10,
  "momento_segundos": 3.5,
  "inicio_segundos": 1.0,
  "fin_segundos": 8.5,
  "orden": 1,
  "origen_extraccion": "ffmpeg",
  "video_fragmento_url": "http://localhost/Bienes%20raices/public/api/recorridos/escenas/101/video"
}
```

### Conexión normalizada
```json
{
  "id": 1,
  "escena_origen_id": 101,
  "escena_destino_id": 102,
  "etiqueta": "Pasar a Cocina",
  "posicion": {
    "sistema": "plano_xy",
    "x": 64.5,
    "y": 52.0
  }
}
```

---

## 4. Documentación de endpoints

Todos los endpoints `POST` requieren sesión autenticada (propietario real verificado en servidor contra `propiedad.id_propietario`) y token CSRF válido (`X-CSRF-Token` o `_csrf`).

| Método | Ruta | Quién accede | Recibe | Devuelve |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/mis-propiedades/{id}/recorrido-video` | Propietario | — | Vista HTML del estudio de video y escenas |
| `GET` | `/api/recorridos/procesador/estado` | Autenticado | — | `{ success, procesador: { ffmpeg_available, max_video_mb, ... } }` |
| `GET` | `/api/mis-propiedades/{id}/recorrido-video` | Propietario | — | `{ success, contract, propiedad, recorrido, videos, escenas, conexiones, procesador }` |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/videos` | Propietario + CSRF | `multipart/form-data`: `video` (MP4/MOV/WebM), opcional `duracion_segundos`, `ancho`, `alto` | `200 { success, video }` / `413` / `422` / `403` / `404` |
| `GET` | `/api/mis-propiedades/{id}/recorrido-video/videos/{videoId}/stream` | Propietario | Cabecera opcional `Range: bytes=...` | Stream binario `200`/`206` con `Accept-Ranges: bytes` |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/videos/{videoId}/eliminar` | Propietario + CSRF | — | `{ success, message }` (conserva imágenes ya extraídas con `video_id = NULL`) |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/escenas` | Propietario + CSRF | `video_id`, `nombre`, `momento_segundos`, opcional `inicio_segundos`, `fin_segundos`, `modo_extraccion` (`auto`/`ffmpeg`/`navegador`), opcional `frame_image` | `200 { success, escena }` / `422` / `503` (si falta FFmpeg y no hay captura) |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/escenas/{escenaId}/actualizar` | Propietario + CSRF | `nombre`, `inicio_segundos`, `fin_segundos`, `orden`, opcional `momento_segundos` / `frame_image` | `200 { success, escena }` |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/escenas/{escenaId}/eliminar` | Propietario + CSRF | — | `{ success, message }` |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/conexiones` | Propietario + CSRF | `escena_origen_id`, `escena_destino_id`, `etiqueta`, `posicion` (`{sistema, x, y}` o `{sistema, yaw, pitch}`) | `200 { success, conexion }` |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/conexiones/{conexionId}/eliminar` | Propietario + CSRF | — | `{ success, message }` |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/estado` | Propietario + CSRF | `estado` (`BORRADOR` \| `PUBLICADO`), opcional `escena_inicial_id` | `200 { success, recorrido }` |
| `GET` | `/recorrido/{id}` | Público (si publicado) o Dueño | `?embed=1` opcional | Visor HTML interactivo por escenas |
| `GET` | `/api/recorridos/{id}` | Público (si publicado) o Dueño | — | `{ success, contract, propiedad, recorrido, escenas, conexiones }` |
| `GET` | `/api/recorridos/escenas/{escenaId}/video` | Público (si publicado) o Dueño | Cabecera `Range: bytes=...` | Stream `200`/`206` del video fuente de la escena |

---

## 5. División de archivos al integrar con el módulo de fotografías

- **Propios de video (no tocar en módulo de fotos):**
  - `app/core/VideoProcessor.php`, `app/models/RecorridoVideo.php`, `app/controllers/RecorridoVideoController.php`
  - `app/views/cuenta/recorrido_video.php`, `public/js/recorrido-video.js`, `public/css/recorrido-video.css`
  - `storage/recorridos_videos/`, `storage/recorridos_tmp/`
- **Compartidos (coordinar cambios):**
  - `database/migrations/20261126_recorridos_video.sql` (`recorrido`, `recorrido_escena`, `recorrido_conexion`)
  - `public/js/recorrido-contrato.js`
  - `app/views/tour/escenas.php` y `public/js/recorrido-visor.js`
  - `app/models/Propiedad.php` (`sceneTour` / `virtualTour`), `public/index.php`, `public/js/app.js`
  - `app/views/cuenta/propiedades.php`, `app/views/cuenta/editar.php`
