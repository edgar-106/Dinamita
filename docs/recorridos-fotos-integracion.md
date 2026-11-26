# Recorridos por fotografías

El propietario accede desde **Mis propiedades → Crear o editar recorrido con fotos**. Puede agregar hasta 30 imágenes, nombrar espacios, elegir el inicio, colocar puntos y guardar o publicar. Las imágenes normales se muestran completas; las panorámicas horizontales se desplazan con la barra, gestos táctiles o las flechas del teclado cuando el visor tiene foco. No se simula una esfera 360° con una foto que no lo es.

Cada punto conecta una escena de origen con otra de destino. Las conexiones son direccionales; para volver se agrega otro punto o se usa la lista de espacios. Antes de publicar, todas las escenas deben ser alcanzables desde el inicio. Un recorrido de una sola foto también es válido.

## Instalación

1. Actualizar el código y respaldar la base existente.
2. Si la base todavía no tiene `propiedad.fecha_retiro`, aplicar primero la migración de Mis propiedades del 24 de noviembre.
3. Importar `database/migrations/20261126_recorridos_fotos.sql`. Es idempotente y crea dos tablas sin reemplazar los datos existentes. El esquema y SQL completo actualizados también las incluyen para instalaciones nuevas.
4. PHP requiere PDO MySQL y Fileinfo. El directorio `storage/recorridos` debe permitir escritura al usuario de PHP. Sus archivos se entregan por un controlador autorizado, nunca mediante una URL directa del almacenamiento.
5. Configurar `upload_max_filesize` y `post_max_size` para aceptar al menos 8 MB más la envoltura del formulario (por ejemplo 10M y 12M). XAMPP local ya tiene 40M/40M. No se usa FFmpeg ni software de imágenes adicional.

Se aceptan JPG, PNG y WEBP de hasta 8 MB, al menos 100 × 100 píxeles, hasta 64 megapíxeles y 24000 píxeles por dimensión. El servidor comprueba el tipo real y las dimensiones. Una panorámica debe ser al menos 1.5 veces más ancha que alta.

## Persistencia y privacidad

`recorrido_foto` conserva el borrador y la última versión publicada como documentos JSON independientes. `version` controla conflictos de guardado entre pestañas; una petición desactualizada recibe HTTP 409 sin sobrescribir cambios. Cargar imágenes no publica el recorrido ni cambia su versión. Las cargas sin guardar permanecen privadas.

`recorrido_archivo` registra las imágenes de cada recorrido con un nombre aleatorio dentro de `storage/recorridos`. Las tablas tienen claves foráneas al inmueble y recorrido. La carga y edición validan la sesión y titularidad del inmueble; conocer un id o un correo no permite administrarlo. Los POST exigen el token CSRF existente.

Los visitantes solo acceden al documento publicado y a las imágenes referenciadas en él, mientras la propiedad esté DISPONIBLE y no retirada. Ocultar revoca el visor público y esas imágenes sin borrar el borrador. Guardar un borrador no modifica la versión pública. Los recorridos anteriores de demostración se conservan; un recorrido de fotos publicado tiene prioridad en la ficha del inmueble.

Se permiten hasta 60 archivos físicos por recorrido para conservar simultáneamente imágenes de borrador y publicación. **Eliminar imágenes sin usar** elimina solamente archivos que no estén referenciados por ninguna de esas dos versiones. Hay que guardar antes los cambios de todas las pestañas. Quitar una escena del borrador elimina sus conexiones del borrador; no altera la publicación anterior.

## Contrato compartido propuesto para el módulo de video

El editor/visor es reutilizable y no contiene reconocimiento automático de habitaciones. El documento tiene `schema_version: 1`:

```json
{
  "schema_version": 1,
  "escena_inicial_id": 101,
  "escenas": [
    {
      "id": 101,
      "nombre": "Sala",
      "tipo_visual": "imagen",
      "imagen_url": "/infonatec/public/recorridos/imagen/101",
      "ancho": 1920,
      "alto": 1080,
      "orden": 0,
      "video_id": null,
      "inicio_segundos": null,
      "fin_segundos": null
    }
  ],
  "conexiones": []
}
```

Una conexión incluye `id` (asignado al guardar), `escena_origen_id`, `escena_destino_id`, `etiqueta`, `coordenadas: "imagen_normalizada"`, `x` e `y`. `x` e `y` van de 0 a 1, con origen en la esquina superior izquierda de la imagen completa, incluyendo las partes desplazadas de una panorámica. Se calculan respecto al rectángulo de la imagen, sin incluir márgenes. La posición no cambia al redimensionar el visor. Las escenas usan `tipo_visual: imagen` o `panorama`.

Actualmente el identificador de escena coincide con el identificador del archivo de imagen cargado. El servidor calcula la URL, dimensiones y orden: no confía en esos valores enviados por el navegador. Las conexiones solo aceptan escenas del mismo recorrido. Se permiten hasta 150 conexiones.

Los campos de procedencia de video están **reservados**. Este módulo deja esos campos en null y rechaza `video_id` no nulo; todavía no reproduce fragmentos. Para integrar video, acordar primero:

- Subir cada fotograma extraído al mismo almacén de imágenes para recibir un id de escena válido.
- Validar que `video_id` pertenezca al mismo propietario y recorrido, y que el intervalo esté dentro de la duración del video.
- Extender el validador de `RecorridoFoto` para conservar esa procedencia, y agregar reproducción autorizada del fragmento en el visor. No aceptar URLs arbitrarias del cliente.
- Conservar las coordenadas normalizadas, los documentos de borrador/publicación y el control de versión. No crear un visor ni unas tablas de conexiones incompatibles.

Esta es una propuesta implementada del lado de fotografías, pendiente de acordar con la compañera que desarrolla video; no supone que ambos módulos ya estén integrados.

## Rutas

| Método y ruta | Función |
| --- | --- |
| GET `/mis-propiedades/{id}/recorrido` | Editor privado, con documento inicial en `#ptBoot`. |
| POST `/mis-propiedades/{id}/recorrido/foto` | FormData con `foto`; devuelve `success` y `scene`. |
| POST `/mis-propiedades/{id}/recorrido/guardar` | JSON `{version, accion, graph}`; acción `guardar`, `publicar` u `ocultar`. Devuelve documento normalizado, nueva versión y estado de publicación. |
| POST `/mis-propiedades/{id}/recorrido/limpiar` | JSON `{version}`; elimina imágenes no referenciadas, devuelve `deleted`. |
| GET `/recorridos/{id}` | Visor público de la última publicación. |
| GET `/recorridos/{id}?preview=1` | Vista previa del borrador guardado; solo su propietario. |
| GET `/recorridos/imagen/{id_archivo}` | Entrega autorizada de una imagen con MIME verificado y sin caché pública. |

Todas las rutas son relativas a `BASE_URL`. Para los POST enviar `X-CSRF-Token`. Un error devuelve `{success:false,message}` con HTTP 400, 401, 404, 409, 500 o 503. El propietario obtiene 404 al intentar operar sobre anuncios ajenos. Las imágenes se pueden explorar directamente desde la lista, incluso si la conexión de regreso no existe.

## Pruebas

`python scripts/test_recorridos_fotos.py` ejecuta pruebas HTTP con dos usuarios e imágenes desechables. Requiere el servidor local y la configuración MySQL de XAMPP utilizada por el proyecto. El script limpia sus usuarios, propiedades y archivos al terminar; no debe ejecutarse contra datos de producción. `--keep` conserva los fixtures para inspección visual; `--cleanup` los elimina después.

Los JPEG de celular se interpretan según su orientación EXIF cuando la extensión PHP EXIF está disponible (habilitada en este XAMPP). Las dimensiones guardadas coinciden con la orientación visual del navegador. Las fotos con relación horizontal de al menos 2.5:1 se proponen automáticamente como panorámicas; se puede cambiar el tipo en el editor.

## Visor panorámico con giro

Las escenas de tipo panorama ahora se abren con Pannellum (biblioteca local ya incluida en el proyecto), con arrastre, zoom y pantalla completa. La alternativa Ver fotografía completa conserva la visualización plana. El campo opcional angulo_horizontal acepta 180, 270 o 360, y vale 180 si no existe en documentos anteriores. El propietario declara la cobertura real de la toma; elegir 360 no genera habitaciones o superficies ausentes ni convierte una foto común en una captura esférica.

El visor aproxima una proyección equirectangular parcial: vaov=min(180, angulo_horizontal*alto/ancho). Las coordenadas de los puntos siguen siendo normalizadas sobre la imagen completa: yaw=(x-0.5)*angulo_horizontal y pitch=(0.5-y)*vaov. El editor transforma clics del visor a esas coordenadas; arrastrar la cámara no coloca puntos. Se deben revisar las posiciones después de cambiar la cobertura angular. Una panorámica de celular que use otra proyección puede presentar distorsión; no se realiza reconstrucción 3D.

Para compatibilidad WebGL, las imágenes grandes generan una textura temporal con un máximo de 4096 píxeles por lado en el navegador. El archivo original no se modifica. El visor se libera al cambiar de escena y, si falla, ofrece la fotografía plana.
