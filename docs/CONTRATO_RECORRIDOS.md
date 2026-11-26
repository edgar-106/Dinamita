# Contrato Unificado de Integración — Recorridos Interactivos (INFONATEC)

Versión del contrato: **`schema_version: 1`** (implementado en `RecorridoFoto`, `RecorridoVideo` y editor/visor compartido `/mis-propiedades/{id}/recorrido` · `/recorridos/{id}`).

Este documento describe la integración real entre:
- **Módulo de Recorridos por Fotografías / Panorámicas** (`RecorridoFoto`, `RecorridosController`, `app/views/recorridos/fotos.php`, `public/js/recorridos-fotos.js`): gestión de borradores/publicaciones con control optimista `version`, almacenamiento seguro de imágenes (`recorrido_archivo` en `storage/recorridos/`) y visor plano + panorámico horizontal (Pannellum).
- **Módulo de Recorridos por Video** (`RecorridoVideo`, `RecorridoVideoController`, `VideoProcessor`, `public/js/recorrido-video.js`): carga privada de videos (`recorrido_video` en `storage/recorridos_videos/`), extracción de fotogramas planos hacia el mismo almacén `recorrido_archivo` (`storage/recorridos/`), edición/validación de intervalos (`inicio_segundos`, `fin_segundos`) y reproducción por rangos (`HTTP 206`).

Ambas fuentes de escenas (fotografías normales, panorámicas horizontales y fotogramas de video) conviven dentro del **mismo recorrido**, usan el **mismo editor** y el **mismo visor**.

---

## 1. Esquema de base de datos unificado

Orden de migraciones incrementales para bases existentes:
1. `database/migrations/20261124_mis_propiedades.sql` (si falta `propiedad.fecha_retiro`).
2. `database/migrations/20261126_recorridos_fotos.sql`: crea `recorrido_foto` (`borrador`, `publicado`, `version`) y `recorrido_archivo` (imágenes de escenas en `storage/recorridos/`).
3. `database/migrations/20261126_recorridos_video.sql`: crea `recorrido_video` con FK hacia `recorrido_foto(id_recorrido) ON DELETE CASCADE`, `propiedad(id_propiedad) ON DELETE CASCADE` y `usuario(id_usuario)`.

No altera `paseo_virtual` ni sobrescribe los recorridos de demostración existentes.

---

## 2. Formato JSON unificado (`schema_version: 1`)

Cada documento (`borrador` y `publicado` en `recorrido_foto`) sigue exactamente la estructura:

```json
{
  "schema_version": 1,
  "escena_inicial_id": 101,
  "escenas": [
    {
      "id": 101,
      "nombre": "Sala",
      "tipo_visual": "imagen",
      "imagen_url": "/Bienes%20raices/public/recorridos/imagen/101",
      "ancho": 1280,
      "alto": 720,
      "angulo_horizontal": 180,
      "orden": 0,
      "video_id": 5,
      "inicio_segundos": 0.5,
      "fin_segundos": 3.5
    }
  ],
  "conexiones": [
    {
      "id": 1,
      "escena_origen_id": 101,
      "escena_destino_id": 102,
      "etiqueta": "Ir a cocina",
      "coordenadas": "imagen_normalizada",
      "x": 0.645,
      "y": 0.52
    }
  ]
}
```

### Reglas de validación en servidor (`RecorridoFoto::validate()`)
- **`id`**: debe existir en `recorrido_archivo` para el `id_recorrido` de la propiedad autorizada.
- **`tipo_visual`**: `'imagen'` o `'panorama'`. Si `video_id` no es nulo, el servidor **exige `tipo_visual = 'imagen'`** (las escenas de video son planas, no panoramas).
- **`video_id`**: `null` en fotografías o panorámicas; si tiene valor, debe corresponder a una fila `LISTO` en `recorrido_video` perteneciente al mismo `id_recorrido`.
- **`inicio_segundos` / `fin_segundos`**: opcionales y en segundos (`float >= 0`). No se admite `fin_segundos` sin `inicio_segundos`; si ambos existen, debe cumplirse `fin_segundos > inicio_segundos`; cuando el servidor conoce `duracion_segundos`, rechaza rangos fuera de la duración (`+ 0.5 s` de tolerancia).
- **Coordenadas de conexión**: `coordenadas: "imagen_normalizada"` con `x`, `y` en `[0, 1]`. Para `panorama`, el editor/visor convierte automáticamente desde/hacia `(yaw, pitch)` en Pannellum usando `angulo_horizontal` (`180`, `270`, `360`).

---

## 3. Documentación de endpoints del módulo de video

Todas las rutas mutables (`POST`) exigen sesión autenticada, validación estricta de titularidad en BD (`propiedad.id_propietario === usuario_sesion.id`) y token CSRF válido (`X-CSRF-Token` o `_csrf`).

| Método | Ruta | Quién accede | Recibe | Devuelve |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/mis-propiedades/{id}/recorrido` | Propietario (`401`/`404`) | — | Editor unificado (fotos, panorámicas y estudio de video) |
| `GET` | `/mis-propiedades/{id}/recorrido-video` | Propietario | — | Redirección de compatibilidad a `/mis-propiedades/{id}/recorrido#video` |
| `GET` | `/api/recorridos/procesador/estado` | Autenticado (`401`) | — | `{ success, procesador: { ffmpeg_available, ffprobe_available, max_video_mb, ... } }` |
| `GET` | `/api/mis-propiedades/{id}/recorrido-video/videos` | Propietario (`401`/`404`) | — | `{ success, procesador, videos: [...] }` |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/videos` | Propietario + CSRF | `multipart/form-data`: `video` (MP4/MOV/WebM), opcional `duracion_segundos`, `ancho`, `alto` | `201 { success, video }` / `400` / `403` / `404` / `413` |
| `GET` | `/api/mis-propiedades/{id}/recorrido-video/videos/{videoId}/stream` | Propietario (`401`/`404`) | Cabecera opcional `Range: bytes=...` | Stream `200`/`206` con `Cache-Control: private, no-store` |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/videos/{videoId}/eliminar` | Propietario + CSRF | JSON o POST vacío con CSRF | `200 { success }` / `409` si alguna escena en `borrador` o `publicado` usa el video |
| `POST` | `/api/mis-propiedades/{id}/recorrido-video/escenas` | Propietario + CSRF | `video_id`, `nombre`, `momento_segundos`, opcional `inicio_segundos`, `fin_segundos`, `duracion_segundos`, `modo_extraccion` (`auto`/`ffmpeg`/`navegador`), `frame` (JPG/PNG/WEBP) | `201 { success, origen_extraccion, scene }` / `400` / `422` / `503` (si `ffmpeg` no está instalado y no hay `frame`) |
| `POST` | `/mis-propiedades/{id}/recorrido/guardar` | Propietario + CSRF | JSON `{ version, accion, graph }` (`guardar`/`publicar`/`ocultar`) | `200 { success, version, graph, published }` / `409` conflicto de versión |
| `GET` | `/recorridos/{id}` | Público (si publicado y disponible) o Dueño (`?preview=1`) | `?preview=1` opcional | Visor unificado plano/panorámico + reproductor de fragmentos |
| `GET` | `/recorrido/{id}` | Público / Dueño | `?preview=1` opcional | Redirección de compatibilidad a `/recorridos/{id}` |
| `GET` | `/api/recorridos/{id}/escenas/{escenaId}/video` | Público (si publicado y disponible) o Dueño (`?preview=1`) | `Range: bytes=...` | Stream `200`/`206` del video fuente autorizado para esa escena (`Cache-Control: private, no-store`) |

---

## 4. Seguridad implementada

- **Titularidad en servidor:** nunca se confía en `id_usuario`, `id_propietario` ni correos enviados por el navegador; todo se comprueba contra `Usuario::current()` y `propiedad.id_propietario`.
- **Videos originales privados:** almacenados con nombre aleatorio en `storage/recorridos_videos/` (protegido por `.htaccess` + `storage/.htaccess` `Require all denied`). Ni siquiera las escenas publicadas exponen rutas directas a los archivos.
- **Almacenamiento de fotogramas:** las imágenes extraídas se validan nuevamente en servidor (peso `<= 8 MB`, MIME real con `finfo`, dimensiones `getimagesize()`, entre `100×100` y `64 MP`) y se guardan en `storage/recorridos/` (servidas únicamente vía `/recorridos/imagen/{id_archivo}` cuando pertenecen al borrador del dueño o a una publicación activa).
- **Streaming por rangos sin cargar el video en memoria PHP:** `VideoProcessor::streamVideoRange()` libera el bloqueo de sesión (`session_write_close()`) y lee bloques de `64 KB` soportando `HTTP 206 Partial Content`.
- **Limpieza de temporales:** si FFmpeg o la validación falla durante la extracción, cualquier archivo temporal en `storage/recorridos_tmp/` o destino parcial en `storage/recorridos/` se elimina en el bloque `finally`/`catch`.
