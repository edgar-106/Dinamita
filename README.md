# INFONATEC

Aplicación inmobiliaria en PHP organizada con el patrón Modelo–Vista–Controlador (MVC).

## Estructura

```
public/                 # única raíz web (DocumentRoot)
├── index.php           # Front Controller
├── .htaccess           # rutas hacia el Front Controller
├── css/  js/  img/     # recursos públicos
└── tour/               # Pannellum y panoramas 360
app/
├── bootstrap.php       # configuración y carga de clases
├── config/  core/  models/
├── controllers/        # AppController, ExplorarController, ApiController
└── views/              # páginas, layouts y recorrido 360
scripts/                # utilidades de automatización y pruebas
legacy/                 # versión HTML anterior, no publicada
```

Los adaptadores `api/` anteriores también se conservaron bajo `legacy/api/`;
las peticiones activas se atienden exclusivamente mediante `ApiController`.

Configura Apache/XAMPP con `public/` como DocumentRoot (por ejemplo,
`C:/xampp/htdocs/Dinamita/public`). Así, `public/index.php` es el único punto
de acceso y `app/`, `scripts/` y `legacy/` no quedan expuestos en la web.

### Rutas principales

- `/` — `AppController@index`
- `/explorar` — `ExplorarController@index`
- `/vender` — `AppController@sell`
- `/tour` — `AppController@tour`
- `/api/propiedades` — `ApiController@properties`
- `/api/ai/chat` — `ApiController@chat`
- `/api/ai/generate-description` — `ApiController@generateDescription`

## Flujo de una petición

1. Apache expone únicamente `public/`. Las reglas de `public/.htaccess` envían
   las URLs que no son archivos reales a `public/index.php`.
2. El front controller carga `app/bootstrap.php`, que inicia sesión, define las
   URLs base y registra el autoloader de las clases internas.
3. `Router` compara método HTTP y ruta contra las rutas declaradas en
   `public/index.php`; después ejecuta `Controlador@acción`.
4. El controlador obtiene datos de un modelo y elige una vista. Las páginas
   normales reciben header + contenido + footer; el tour se renderiza solo.
5. El footer publica el catálogo en `window.INFONATEC_PROPERTIES`; `public/js/app.js`
   lo utiliza para fichas, recomendaciones del chat y recorridos sin duplicar
   información en el navegador.

## Datos y modos de respaldo

`Propiedad` consulta `propiedad` en la base `sistema_inmobiliario`. Si MySQL no está iniciado, la
tabla no existe o la conexión falla, utiliza el catálogo de demostración incluido
en el modelo. Esto permite navegar el sitio localmente, pero **no guarda altas ni
cambios de propiedades**.

El registro y el acceso requieren MySQL. Las contraseñas se guardan con hash y
la autenticación utiliza la sesión PHP. Las credenciales incorrectas se rechazan.
La publicación guarda propiedades y fotografías, las citas se registran en `cita`
y las solicitudes de contacto y asesoría aparecen en Administración → Reservas.

En una instalación nueva, importar primero `database/sistema_inmobiliario_schema.sql`
y después `database/initialize.sql`. El primer archivo reconstruye las tablas y
debe reservarse para bases nuevas. El segundo añade catálogos y las tablas de
contacto y transporte de forma repetible. El administrador necesita una cuenta
con contraseña generada mediante `password_hash` y un rol `ADMIN` o `ADMINISTRADOR`
asignado en `usuario_rol`.

## IA y recorrido virtual

- Las rutas de IA reciben JSON en `/api/ai/chat` y
  `/api/ai/generate-description`.
- Si existe `GEMINI_API_KEY` (o `AI_API_KEY`), `AIController` intenta Gemini
  con un tiempo máximo definido por `AI_TIMEOUT_SECONDS`.
- Si falta la clave, cURL no está disponible o Gemini falla, el sistema contesta
  con reglas locales basadas en el catálogo. El endpoint `/api/ai/status` indica
  cuál de los dos modos está activo.
- El tour 360° se sirve desde `/tour`. Sus panoramas están en
  `public/tour/panoramas/`; si se cambia el nombre o se elimina una imagen, el
  visor Pannellum no podrá cargar esa sala.

## Puntos que deben revisarse antes de producción

- Configurar credenciales de MySQL y la clave de IA mediante variables de
  entorno; no subir secretos al repositorio.
- Configurar el DocumentRoot del servidor exactamente en `public/`.
- Incorporar protección CSRF a las operaciones que modifican información.
- Mantener sincronizados los valores `type`, `operation` y `virtualTour` del
  catálogo; los filtros y los botones de recorrido dependen de esas claves.

## Pruebas locales rápidas

Con Apache/XAMPP, entra a `http://localhost/infonatec/public/`. Para probar sin
Apache se puede usar el router de desarrollo:

```powershell
C:\xampp\php\php.exe -S 127.0.0.1:8080 -t public scripts/dev-router.php
```

Después verifica `/`, `/vender`, `/legal`, `/tour`, `/api/propiedades` y
`/api/ai/status`. `scripts/dev-router.php` es sólo para desarrollo y no se usa
en Apache.
