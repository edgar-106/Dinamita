# INFONATEC Inmobiliaria

Aplicación inmobiliaria en PHP 8.2+ organizada con el patrón Modelo–Vista–Controlador (MVC) sobre MySQL/MariaDB.

## Estructura

```
public/                   # Única raíz web (DocumentRoot)
├── index.php             # Front Controller y registro de rutas
├── .htaccess             # Reglas de enrutamiento MVC
├── css/  js/  img/       # Recursos estáticos públicos
├── media/                # Videos de introducción y recorridos
├── tour/                 # Visor Pannellum 360° y panoramas
└── uploads/propiedades/  # Fotografías subidas al publicar inmuebles
app/
├── bootstrap.php         # Configuración, autoloader y cabeceras HTTP seguras
├── config/               # env.php, config.php, Database.php, ai_config.php
├── core/                 # Router, Controller, Model, Security (CSRF / rate-limit)
├── models/               # Propiedad, Usuario
└── views/                # home, explorar, vender, tour, admin y layouts
database/
├── sistema_inmobiliario_schema.sql
└── sistema_inmobiliario_seed.sql
scripts/
└── dev-router.php        # Router de desarrollo para php -S
```

## Configuración inicial

1. Copia `.env.example` a `.env` en la raíz del proyecto y define tus credenciales de MySQL (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) y, opcionalmente, `GEMINI_API_KEY`.
2. Importa `database/sistema_inmobiliario_schema.sql` y `database/sistema_inmobiliario_seed.sql` en MySQL con codificación `utf8mb4`.
3. En producción, configura Apache/Nginx con `public/` como `DocumentRoot`. El archivo `.htaccess` raíz bloquea además el acceso web directo a `.env`, `app/`, `database/` y `scripts/`.

## Rutas principales

- `/` — Portada con catálogo interactivo y filtros sin recarga (`AppController@index`)
- `/explorar` — Catálogo completo con filtros por operación, categoría, ubicación, precio y favoritos (`ExplorarController@index`)
- `/vender` — Formulario en 4 pasos con autosave, validación por paso, subida real de fotos y borrador IA con aprobación (`AppController@sell`)
- `/tour` y `/tour/industrial` — Recorridos 360° con controles de zoom, flechas, ayuda, aviso de datos y galería estática accesible
- `/api/propiedades`, `/api/propiedades/{id}` — Catálogo JSON con galerías completas, información legal y opciones de pago
- `/api/citas`, `/api/contacto`, `/api/favoritos/*` — Persistencia de citas, solicitudes de asesoría/interés y favoritos
- `/api/ai/chat`, `/api/ai/chat/stream`, `/api/ai/generate-description` — Asistente conversacional y redactor con IA
- `/admin/*` — Panel administrativo protegido por rol `ADMIN` y tokens CSRF
