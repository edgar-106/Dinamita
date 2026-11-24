# INFONATEC Inmobiliaria

Plataforma inmobiliaria desarrollada en **PHP 8.0+ (recomendado 8.2+)** bajo el patrón **Modelo–Vista–Controlador (MVC)** con base de datos **MySQL / MariaDB (`utf8mb4`)**, recorridos virtuales **360° con Pannellum** y galería estática accesible, publicación de propiedades en **4 pasos** con subida real de fotografías, catálogo con filtros dinámicos, favoritos, citas, panel administrativo y asistente virtual con **IA**.

---

## Requisitos previos

- **PHP 8.0 o superior** (recomendado **8.2+**) con las extensiones:
  - `pdo_mysql` (conexión a MySQL/MariaDB)
  - `mbstring` (manejo UTF-8 y respuestas en streaming)
  - `fileinfo` (validación MIME real de fotografías subidas)
  - `curl` *(opcional, necesaria solo si se conecta con Gemini API externa)*
- **MySQL 8.0+ o MariaDB 10.4+** con soporte `utf8mb4` (incluido en XAMPP).
- **Apache** con `mod_rewrite` habilitado y `AllowOverride All` (incluido en XAMPP), o bien el servidor integrado de PHP para desarrollo local.

---

## Instalación y configuración paso a paso

### 1. Clonar el repositorio

Si utilizas **XAMPP en Windows**, clona el proyecto dentro de `C:\xampp\htdocs\Bienes raices`:

```bash
git clone https://github.com/edgar-106/Dinamita.git "Bienes raices"
cd "Bienes raices"
```

### 2. Configurar variables de entorno (`.env`)

Crea tu archivo `.env` local a partir de la plantilla `.env.example`:

- **En Windows (PowerShell):**
  ```powershell
  Copy-Item .env.example .env
  ```
- **En Linux / macOS / Git Bash:**
  ```bash
  cp .env.example .env
  ```

Edita `.env` con tus valores locales o de producción:

| Variable | Valor recomendado (XAMPP local) | Descripción |
| :--- | :--- | :--- |
| `APP_ENV` | `development` | Modo de ejecución (`development` o `production`). |
| `DB_HOST` | `localhost` | Servidor de MySQL / MariaDB. |
| `DB_NAME` | `sistema_inmobiliario` | Nombre de la base de datos. |
| `DB_USER` | `root` | Usuario de MySQL (en XAMPP local normalmente es `root`). |
| `DB_PASS` | *(vacío)* | Contraseña de MySQL (en XAMPP local suele quedar vacía). |
| `DB_CHARSET` | `utf8mb4` | Codificación de conexión a la base de datos. |
| `ADMIN_EMAIL` | `123@gmail.com` | Correo autorizado para acceder al panel `/admin`. |
| `AI_PROVIDER` | `gemini` o `local` | Motor del asistente (`gemini` con fallback automático a `local`). |
| `GEMINI_API_KEY` | *(opcional)* | Clave de Google Gemini. Si se deja vacía, funciona el motor local integrado. |
| `AI_MODEL` | `gemini-1.5-flash` | Modelo usado cuando `AI_PROVIDER=gemini` y hay clave configurada. |

> **Importante:** El archivo `.env` está excluido en `.gitignore` para no exponer credenciales. Nunca lo subas al repositorio.

### 3. Crear e importar la base de datos

#### Opción A — Usando phpMyAdmin (`http://localhost/phpmyadmin`)

1. Abre phpMyAdmin y entra a la pestaña **Importar**.
2. Importa primero **`database/sistema_inmobiliario_schema.sql`** (crea automáticamente la base `sistema_inmobiliario` en `utf8mb4` y las tablas principales).
3. Selecciona la base `sistema_inmobiliario` y vuelve a importar **`database/sistema_inmobiliario_seed.sql`** (crea las tablas adicionales `vehiculo`, `asignacion_transporte`, `favorito` y `solicitud_contacto`, además de catálogos, usuarios de prueba, 10 propiedades con galería completa, recorridos 360° y registros iniciales).

> **Nota:** `database/initialize.sql` es un script complementario idempotente pensado únicamente si ya cuentas con un esquema previo sin haber ejecutado `database/sistema_inmobiliario_seed.sql`.

#### Opción B — Por línea de comandos

- **Windows (Símbolo del sistema / CMD con XAMPP):**
  ```cmd
  C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\sistema_inmobiliario_schema.sql
  C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\sistema_inmobiliario_seed.sql
  ```
- **Windows (PowerShell con XAMPP):**
  ```powershell
  Get-Content database\sistema_inmobiliario_schema.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4
  Get-Content database\sistema_inmobiliario_seed.sql -Raw | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4
  ```
- **Linux / macOS:**
  ```bash
  mysql -u root -p --default-character-set=utf8mb4 < database/sistema_inmobiliario_schema.sql
  mysql -u root -p --default-character-set=utf8mb4 < database/sistema_inmobiliario_seed.sql
  ```

### 4. Permisos de subida de imágenes

Verifica que el servidor web tenga permisos de escritura sobre el directorio **`public/uploads/propiedades/`**.
Este directorio acepta entre **1 y 10 fotografías** por propiedad publicada (formatos `JPG`, `PNG` o `WEBP` de hasta **5 MB** cada una) e incluye su propio `.htaccess` que impide el listado de archivos y la ejecución de scripts.

### 5. Iniciar la aplicación

#### Opción A — XAMPP (Apache + MySQL)

1. Inicia **Apache** y **MySQL** desde el Panel de Control de XAMPP.
2. Abre en tu navegador:
   - `http://localhost/Bienes%20raices/public/`
   - *(El archivo `index.php` raíz redirige automáticamente a `public/` en instalaciones XAMPP que apuntan a la carpeta del proyecto).*

#### Opción B — Servidor integrado de PHP (sin Apache)

Desde la raíz del proyecto ejecuta:

```bash
php -S localhost:8000 -t public scripts/dev-router.php
```

Y abre `http://localhost:8000/` en tu navegador.

#### Opción C — Despliegue en producción (Apache / Nginx)

1. Configura el **`DocumentRoot`** del servidor web apuntando directamente a la carpeta **`public/`**.
2. Establece **`APP_ENV=production`** en `.env` y habilita **HTTPS** (las cookies de sesión activan `secure` automáticamente cuando detectan HTTPS).
3. Asegúrate de mantener activo el `.htaccess` raíz, que bloquea el acceso HTTP directo a `.env*`, `app/`, `database/`, `scripts/`, `legacy/`, `composer.json` y `README.md`.

---

## Cuentas de acceso inicial (datos de prueba del seed)

Al importar `database/sistema_inmobiliario_seed.sql` se crean dos cuentas listas para pruebas:

| Rol | Correo | Contraseña | Acceso |
| :--- | :--- | :--- | :--- |
| **Administrador (`ADMIN`)** | `123@gmail.com` | `123` | Acceso único al panel `/admin` (debe coincidir con `ADMIN_EMAIL` en `.env`). |
| **Cliente (`CLIENTE`)** | `cliente@infonatec.com` | `123` | Portal público, favoritos, citas y publicación de propiedades. |

> **Advertencia de seguridad:** Cambia las contraseñas de estas cuentas y actualiza `ADMIN_EMAIL` en `.env` antes de publicar el sistema en producción.

---

## Rutas principales del sistema

Registradas en [`public/index.php`](public/index.php):

### Páginas públicas
- `GET /` y `GET /home` — Portada con catálogo destacado, buscador rápido y asistencia interactiva (`AppController@index`).
- `GET /explorar` — Catálogo completo con filtros por operación, categoría, ubicación, rango de precio y favoritos (`ExplorarController@index`).
- `GET /vender` — Formulario de publicación en 4 pasos con autoguardado, validación por paso, borrador de descripción con IA y carga real de fotos (`AppController@sell`).
- `POST /vender/publicar` — Procesamiento de alta de inmueble con CSRF y carga de archivos (`AppController@publish`).
- `GET /legal` — Información legal sobre compraventa, arrendamiento, auditoría registral y protección de datos (`AppController@legal`).
- `GET /tour` y `GET /tour/industrial` — Recorridos virtuales 360° con controles de zoom, flechas, ayuda, aviso de consumo de datos y galería estática accesible (`AppController@tour`, `AppController@tourIndustrial`).

### API de catálogo, contacto, citas y favoritos
- `GET /api/propiedades` y `GET /api/propiedades/{id}` — Catálogo JSON con galería completa, detalles legales y opciones de pago.
- `POST /api/citas` y `POST /citas` — Registro de citas con validación de disponibilidad y token CSRF.
- `POST /api/contacto` y `POST /contacto` — Solicitudes de interés o asesoría guardadas en `solicitud_contacto`.
- `GET /api/favoritos`, `POST /api/favoritos/toggle`, `POST /api/favoritos/sync` — Sincronización y gestión de favoritos por usuario.

### Asistente con Inteligencia Artificial
- `POST /api/ai/chat` — Respuesta JSON del asesor virtual.
- `POST /api/ai/chat/stream` — Respuesta en streaming (SSE) para el chat flotante.
- `POST /api/ai/generate-description` — Redacción asistida de descripciones para el paso de publicación.
- `GET /api/ai/status` — Estado y proveedor activo del módulo IA.

### Autenticación y panel administrativo
- `POST /auth/login`, `POST /auth/register`, `POST/GET /auth/logout` — Registro, inicio de sesión (con límite de intentos) y cierre de sesión.
- `GET /admin` — Panel principal administrativo con KPIs y actividad reciente.
- `GET /admin/reservas` y `POST /admin/reservas/{id}/estado` — Gestión de citas y visualización de solicitudes de contacto.
- `GET /admin/pagos` y `POST /admin/pagos/{id}/confirmar` — Validación y confirmación de pagos.
- `GET /admin/eventos`, `POST /admin/eventos/tour`, `POST /admin/eventos/tour/{id}/eliminar` — Alta y eliminación de paseos virtuales 360°.
- `GET /admin/logistica`, `POST /admin/logistica/vehiculo`, `POST /admin/logistica/vehiculo/{id}/estado`, `POST /admin/logistica/asignar` — Control de vehículos y asignación de traslados.

---

## Estructura del proyecto

```text
.
├── .env.example                       # Plantilla de variables de entorno
├── .gitignore                         # Excluye .env, archivos locales y subidas de prueba
├── .htaccess                          # Bloquea acceso HTTP directo a app/, database/, scripts/ y .env
├── composer.json                      # Autoload PSR/classmap y requisito PHP >= 8.0
├── index.php                          # Redirección de compatibilidad hacia public/
├── README.md                          # Documentación e instrucciones del sistema
├── Reporte de pruebas INFONATEC.txt   # Registro de pruebas funcionales
├── Reporte del proyecto INFONATEC.docx# Documentación del proyecto
├── app/
│   ├── bootstrap.php                  # Carga inicial, autoloader MVC y cabeceras de seguridad
│   ├── config/
│   │   ├── Database.php               # Conexión PDO MySQL/MariaDB en utf8mb4
│   │   ├── ai_config.php              # Configuración del proveedor IA (gemini / local)
│   │   ├── config.php                 # Rutas base, sesión segura e inicialización de entorno
│   │   └── env.php                    # Cargador de variables desde .env
│   ├── controllers/
│   │   ├── AIController.php           # Chat IA (JSON / SSE) y generador de descripciones
│   │   ├── AdminController.php        # Dashboard, reservas, pagos, tours y logística
│   │   ├── ApiController.php          # API de propiedades, citas, contacto y favoritos
│   │   ├── AppController.php          # Portada, vender, publicar, legal y tours 360°
│   │   ├── AuthController.php         # Login, registro y logout con protección CSRF
│   │   ├── ContactController.php      # Compatibilidad para /contacto y /citas con CSRF
│   │   └── ExplorarController.php     # Catálogo y filtros de propiedades
│   ├── core/
│   │   ├── Controller.php             # Renderizado de vistas, respuestas JSON y redirecciones
│   │   ├── Model.php                  # Clase base con conexión PDO compartida
│   │   ├── Router.php                 # Enrutador GET/POST con soporte de parámetros
│   │   └── Security.php               # Cabeceras HTTP, CSRF y limitador de intentos de login
│   ├── models/
│   │   ├── Propiedad.php              # Catálogo, filtros, galerías, favoritos y alta con fotos
│   │   └── Usuario.php                # Registro, autenticación y verificación de rol ADMIN
│   └── views/
│       ├── admin/                     # index.php, reservas.php, pagos.php, eventos.php, logistica.php
│       ├── explorar/                  # index.php
│       ├── home/                      # index.php
│       ├── layouts/                   # header.php, footer.php
│       ├── legal/                     # index.php
│       ├── tour/                      # index.php, industrial.php
│       └── vender/                    # index.php
├── database/
│   ├── sistema_inmobiliario_schema.sql# Esquema principal de tablas
│   ├── sistema_inmobiliario_seed.sql  # Tablas adicionales, catálogos y datos iniciales
│   └── initialize.sql                 # Script complementario idempotente
├── public/                            # Única raíz web pública (DocumentRoot)
│   ├── .htaccess                      # Reescritura MVC hacia index.php y DirectorySlash Off
│   ├── index.php                      # Front Controller y tabla de rutas
│   ├── css/                           # Hojas de estilo
│   ├── img/                           # Imágenes e íconos (incluye property-fallback.svg)
│   ├── js/                            # Lógica cliente, filtros, favoritos, chat y 4 pasos
│   ├── media/                         # intro-infonatec.mp4 y tour-propiedad.mp4
│   ├── tour/                          # Librería Pannellum 360° y panoramas
│   └── uploads/propiedades/           # Destino de fotografías subidas (.htaccess protector)
└── scripts/
    └── dev-router.php                 # Router para desarrollo local con php -S
```

---

## Verificación rápida y solución de problemas

### Comprobaciones recomendadas tras instalar

1. **Sintaxis PHP:**
   ```powershell
   Get-ChildItem -Path app,public,scripts,index.php -Filter *.php -Recurse | ForEach-Object { php -l $_.FullName }
   ```
2. **Páginas públicas:** Abre `/`, `/explorar`, `/vender`, `/legal`, `/tour`, `/tour/industrial` y `/api/propiedades/2` y confirma respuesta HTTP `200`.
3. **Protección de secretos en Apache:** Al solicitar `http://localhost/Bienes%20raices/.env` debe responder HTTP `403`.

### Solución a problemas comunes

- **Error de conexión con la base de datos:** Verifica que MySQL esté iniciado en XAMPP, que la base `sistema_inmobiliario` exista y que `DB_USER` / `DB_PASS` en `.env` coincidan con tu servidor (en XAMPP local: `DB_USER=root` y `DB_PASS=` vacío).
- **Error 404 al entrar a `/explorar`, `/vender` o `/legal`:** En Apache confirma que `mod_rewrite` esté habilitado y que la configuración del host tenga `AllowOverride All` para que se aplique `public/.htaccess`.
- **Error `403` (`csrf_invalid`) en formularios o peticiones POST:** Asegúrate de abrir el sitio siempre bajo el mismo host (`localhost` o `127.0.0.1` sin mezclar ambos) para que la cookie de sesión y el token CSRF coincidan; si la sesión expiró (30 minutos de inactividad), recarga la página.
- **Caracteres o acentos corruptos en el catálogo:** Reimporta `database/sistema_inmobiliario_seed.sql` indicando `--default-character-set=utf8mb4`.
- **Error al subir fotografías en `/vender`:** Confirma que `public/uploads/propiedades/` tenga permisos de escritura, que cada imagen sea `JPG`, `PNG` o `WEBP` de máximo `5 MB` (hasta 10 por propiedad) y revisa `upload_max_filesize` / `post_max_size` en `php.ini`.
