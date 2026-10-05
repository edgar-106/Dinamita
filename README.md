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
