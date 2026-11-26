<?php
/**
 * Modelo Propiedad
 * Gestiona el catálogo de inmuebles (casas, departamentos, terrenos),
 * galerías de imágenes, favoritos y publicación con carga real de fotografías.
 */

class Propiedad extends Model {

    private const MAX_PHOTOS = 10;
    private const MAX_PHOTO_BYTES = 5242880; // 5 MB
    private const ALLOWED_MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Catálogo de respaldo (solo usado si MySQL no está disponible)
     */
    private static array $mockProperties = [
        1 => [
            'id' => 1,
            'title' => 'Lote Montebello',
            'type' => 'terreno',
            'type_label' => 'Terreno',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'Mérida, YUC',
            'full_location' => 'Mérida, Yucatán',
            'tag' => 'DISPONIBLE',
            'price' => '$1,850,000 MXN',
            'raw_price' => 1850000,
            'area' => 450,
            'bedrooms' => null,
            'bathrooms' => null,
            'video' => null,
            'image' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=85',
            'gallery' => [
                ['url' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=85', 'alt' => 'Vista panorámica del Lote Montebello', 'caption' => 'Vista panorámica del Lote Montebello'],
                ['url' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=1200&q=85', 'alt' => 'Entorno arbolado y acceso principal', 'caption' => 'Entorno arbolado y acceso principal'],
                ['url' => 'https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=1200&q=85', 'alt' => 'Vegetación y zona residencial norte', 'caption' => 'Vegetación y zona residencial norte'],
                ['url' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=85', 'alt' => 'Vista general del polígono de terreno', 'caption' => 'Vista general del polígono de terreno'],
            ],
            'description' => 'Terreno residencial plano de 450 m² (15 m de frente por 30 m de fondo) ubicado en zona consolidada al norte de Mérida, con servicios subterráneos a pie de lote, alumbrado LED y vialidades pavimentadas.',
            'stats' => ['450 m²'],
            'details' => ['450 m² de superficie', 'Frente 15 m × Fondo 30 m', 'Servicios subterráneos a pie de lote', 'Zona residencial consolidada'],
            'operation_details' => ['Venta directa', 'Escritura pública inmediata', 'Contado o financiamiento'],
            'legal_details' => ['Propiedad privada con escritura pública', 'Uso de suelo residencial H2 verificado', 'Libre de gravamen ante RPP', 'Predial y servicios al corriente'],
            'payment_options' => [],
            'virtualTour' => null,
        ],
        2 => [
            'id' => 2,
            'title' => 'Residencia Las Cumbres',
            'type' => 'casa',
            'type_label' => 'Casa',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'Monterrey, NL',
            'full_location' => 'Monterrey, Nuevo León',
            'tag' => 'DISPONIBLE',
            'price' => '$6,800,000 MXN',
            'raw_price' => 6800000,
            'area' => 340,
            'bedrooms' => 4,
            'bathrooms' => 5,
            'video' => 'media/tour-propiedad.mp4',
            'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=90',
            'gallery' => [
                ['url' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=90', 'alt' => 'Fachada principal de Residencia Las Cumbres', 'caption' => 'Fachada principal contemporánea'],
                ['url' => 'tour/panoramas/sala.jpg', 'alt' => 'Sala principal de doble altura', 'caption' => 'Sala principal de doble altura'],
                ['url' => 'tour/panoramas/comedor.jpg', 'alt' => 'Comedor y cocina integral con isla de cuarzo', 'caption' => 'Comedor y cocina integral'],
                ['url' => 'tour/panoramas/recamara.jpg', 'alt' => 'Recámara principal con vestidor', 'caption' => 'Recámara principal'],
                ['url' => 'tour/panoramas/terraza.jpg', 'alt' => 'Terraza techada y jardín privado', 'caption' => 'Terraza techada y jardín'],
            ],
            'description' => 'Residencia contemporánea de dos niveles con 340 m² de construcción, sala a doble altura con ventanales térmicos, cocina integral con cubierta de cuarzo, 4 recámaras con baño propio, alberca privada, terraza techada y cochera para 3 autos.',
            'stats' => ['340 m²', '4 Rec', '5 Baños'],
            'details' => ['340 m² de construcción', '4 habitaciones con baño', '5 baños completos', 'Alberca privada y terraza techada', 'Cochera para 3 autos'],
            'operation_details' => ['Venta', 'Contado', 'Crédito bancario e Infonavit Cofinavit'],
            'legal_details' => ['Escritura pública vigente sin gravamen', 'Predial y agua al corriente', 'Apta para crédito hipotecario', 'Planos y licencia de construcción en regla'],
            'payment_options' => [],
            'virtualTour' => [
                'type' => 'iframe',
                'url' => 'tour',
                'video' => 'media/tour-propiedad.mp4',
                'title' => 'Recorrido Interactivo 360° — Residencia Minimalista Las Cumbres',
                'subtitle' => 'Explora los espacios en 360°, interactúa con los puntos de navegación y conoce los acabados.',
                'helpText' => '💡 Arrastra o usa las flechas para explorar en 360°. Usa el zoom (+) / (−) o los botones inferiores para cambiar de sala.',
                'estimatedDataMb' => 4.6,
            ],
        ],
    ];

    /**
     * Convierte una ruta relativa o absoluta a URL completa válida.
     */
    public static function resolveAssetUrl(?string $path): string {
        $path = trim((string) $path);
        if ($path === '') {
            return BASE_URL . 'img/property-fallback.svg';
        }
        if (preg_match('#^(https?://|data:)#i', $path)) {
            return $path;
        }
        return BASE_URL . ltrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Retorna todas las propiedades con su galería completa, info legal y tour.
     */
    public function getAll(): array {
        if ($this->db !== null) {
            try {
                $stmt = $this->db->query(
                    "SELECT
                        p.id_propiedad AS id,
                        p.titulo AS title,
                        p.descripcion,
                        p.ubicacion AS location,
                        p.precio AS raw_price,
                        p.superficie,
                        p.habitaciones,
                        p.banos,
                        p.situacion_legal,
                        LOWER(c.nombre) AS type,
                        c.nombre AS type_label,
                        CASE WHEN UPPER(p.operacion) = 'RENTA' THEN 'rentar' ELSE 'comprar' END AS operation,
                        CASE WHEN UPPER(p.operacion) = 'RENTA' THEN 'Renta' ELSE 'Venta' END AS operation_label,
                        e.nombre AS tag,
                        COALESCE((
                            SELECT pv.url_3d
                            FROM paseo_virtual pv
                            WHERE pv.id_propiedad = p.id_propiedad
                            LIMIT 1
                        ), '') AS tour_url,
                        COALESCE((
                            SELECT pv.descripcion
                            FROM paseo_virtual pv
                            WHERE pv.id_propiedad = p.id_propiedad
                            LIMIT 1
                        ), '') AS tour_desc
                    FROM propiedad p
                    INNER JOIN categoria c ON c.id_categoria = p.id_categoria
                    INNER JOIN estado_propiedad e ON e.id_estado = p.id_estado
                    WHERE e.nombre = 'DISPONIBLE' AND p.fecha_retiro IS NULL
                    ORDER BY p.fecha_publicacion DESC, p.id_propiedad DESC"
                );
                $rows = $stmt->fetchAll();

                if (!empty($rows)) {
                    $galleries = $this->fetchAllGalleries();
                    $sceneTours = class_exists('RecorridoVideo')
                        ? (new RecorridoVideo())->fetchPublishedByPropertyMap()
                        : [];
                    return array_map(
                        fn(array $row) => $this->mapDatabaseProperty(
                            $row,
                            $galleries[(int) $row['id']] ?? [],
                            $sceneTours[(int) $row['id']] ?? null
                        ),
                        $rows
                    );
                }
                return [];
            } catch (Exception $e) {
                error_log('Error consultando propiedades: ' . $e->getMessage());
            }
        }

        // Fallback con rutas resueltas
        return array_map(fn(array $item) => $this->normalizeMockProperty($item), self::$mockProperties);
    }

    /**
     * Obtiene todas las imágenes de `imagen_propiedad` agrupadas por id_propiedad.
     */
    private function fetchAllGalleries(): array {
        if ($this->db === null) {
            return [];
        }

        $grouped = [];
        $stmt = $this->db->query(
            "SELECT id_propiedad, url_imagen, descripcion
             FROM imagen_propiedad
             ORDER BY id_propiedad ASC, orden_visualizacion ASC, id_imagen ASC"
        );
        foreach ($stmt->fetchAll() as $img) {
            $propId = (int) $img['id_propiedad'];
            $caption = trim((string) ($img['descripcion'] ?? ''));
            $grouped[$propId][] = [
                'url'     => self::resolveAssetUrl($img['url_imagen']),
                'alt'     => $caption !== '' ? $caption : 'Fotografía de la propiedad',
                'caption' => $caption,
            ];
        }
        return $grouped;
    }

    /**
     * Normaliza una propiedad del catálogo de respaldo.
     */
    private function normalizeMockProperty(array $item): array {
        $gallery = [];
        foreach ($item['gallery'] ?? [] as $g) {
            $gallery[] = [
                'url'     => self::resolveAssetUrl($g['url'] ?? ''),
                'alt'     => $g['alt'] ?? $item['title'],
                'caption' => $g['caption'] ?? '',
            ];
        }
        if (empty($gallery)) {
            $gallery[] = [
                'url'     => self::resolveAssetUrl($item['image'] ?? ''),
                'alt'     => $item['title'],
                'caption' => $item['title'],
            ];
        }

        $item['image'] = $gallery[0]['url'];
        $item['gallery'] = $gallery;
        if (empty($item['payment_options'])) {
            $item['payment_options'] = $this->buildPaymentOptions($item['operation'], $item['type']);
        }
        if (!empty($item['virtualTour']['url'])) {
            $item['virtualTour'] = $this->buildVirtualTourPayload(
                (int) $item['id'],
                $item['title'],
                $item['virtualTour']['url'],
                $item['virtualTour']['subtitle'] ?? '',
                $item['video'] ?? null
            );
        }
        return $item;
    }

    /**
     * Convierte un registro del esquema inmobiliario al contrato enriquecido de la UI.
     */
    private function mapDatabaseProperty(array $property, array $gallery, ?array $sceneTour = null): array {
        $id = (int) $property['id'];
        $type = (string) $property['type'];
        $isRent = $property['operation'] === 'rentar';

        $stats = [];
        if ($property['superficie'] !== null) {
            $stats[] = number_format((float) $property['superficie'], 0) . ' m²';
        }
        if ($property['habitaciones'] !== null) {
            $stats[] = $property['habitaciones'] . ' Rec';
        }
        if ($property['banos'] !== null) {
            $stats[] = $property['banos'] . ' Baños';
        }

        $details = array_values(array_filter([
            $property['superficie'] !== null
                ? number_format((float) $property['superficie'], 0) . ($type === 'terreno' ? ' m² de superficie total' : ' m² de construcción')
                : null,
            $property['habitaciones'] !== null ? $property['habitaciones'] . ' habitaciones' : null,
            $property['banos'] !== null ? $property['banos'] . ' baños' : null,
            'Operación: ' . $property['operation_label'],
            'Ubicación: ' . $property['location'],
        ]));

        if (empty($gallery)) {
            $gallery = [[
                'url'     => BASE_URL . 'img/property-fallback.svg',
                'alt'     => $property['title'] . ' — imagen referencial',
                'caption' => $property['title'],
            ]];
        }

        $legalSummary = trim((string) ($property['situacion_legal'] ?? ''));
        $legalDetails = $this->buildLegalDetails($isRent, $type, $legalSummary);
        $paymentOptions = $this->buildPaymentOptions($property['operation'], $type);

        $videoPath = null;
        if (in_array($id, [2, 3], true)) {
            $videoPath = 'media/tour-propiedad.mp4';
        }

        $tourUrl = trim((string) ($property['tour_url'] ?? ''));
        $virtualTour = null;
        if ($tourUrl !== '') {
            $virtualTour = $this->buildVirtualTourPayload(
                $id,
                (string) $property['title'],
                $tourUrl,
                (string) ($property['tour_desc'] ?? ''),
                $videoPath
            );
        } elseif ($sceneTour !== null) {
            $virtualTour = [
                'type'        => 'iframe',
                'isSceneTour' => true,
                'url'         => 'recorrido/' . $id,
                'title'       => $sceneTour['titulo'],
                'subtitle'    => 'Explora los espacios por escenas y reproduce fragmentos de video.',
                'helpText'    => '💡 Haz clic en los puntos interactivos, usa las miniaturas o ← → para recorrer cada escena.',
            ];
        } elseif ($videoPath !== null) {
            $virtualTour = [
                'type'     => 'video',
                'video'    => $videoPath,
                'title'    => 'Recorrido en Video — ' . $property['title'],
                'subtitle' => 'Conoce los espacios interiores, distribución y acabados de este inmueble.',
            ];
        }

        return [
            'id'                => $id,
            'title'             => $property['title'],
            'type'              => $type,
            'type_label'        => ucfirst(strtolower($property['type_label'])),
            'operation'         => $property['operation'],
            'operation_label'   => $property['operation_label'],
            'location'          => $property['location'],
            'full_location'     => $property['location'],
            'tag'               => $property['tag'],
            'price'             => '$' . number_format((float) $property['raw_price'], 0) . ($isRent ? ' / mes' : ' MXN'),
            'raw_price'         => (float) $property['raw_price'],
            'area'              => $property['superficie'] !== null ? (float) $property['superficie'] : null,
            'bedrooms'          => $property['habitaciones'] !== null ? (int) $property['habitaciones'] : null,
            'bathrooms'         => $property['banos'] !== null ? (int) $property['banos'] : null,
            'description'       => trim((string) ($property['descripcion'] ?? '')),
            'video'             => $videoPath,
            'image'             => $gallery[0]['url'],
            'gallery'           => $gallery,
            'stats'             => $stats,
            'details'           => $details,
            'operation_details' => array_values(array_filter([
                $property['operation_label'] === 'Renta' ? 'Arrendamiento mensual' : 'Compra directa / Escrituración',
                $legalSummary !== '' ? $legalSummary : null,
            ])),
            'legal_details'     => $legalDetails,
            'payment_options'   => $paymentOptions,
            'virtualTour'       => $virtualTour,
            'sceneTour'         => $sceneTour,
        ];
    }

    /**
     * Construye detalles legales específicos según operación y tipo de inmueble.
     */
    private function buildLegalDetails(bool $isRent, string $type, string $legalSummary): array {
        $items = [];
        if ($legalSummary !== '') {
            $items[] = $legalSummary;
        }

        if ($isRent) {
            $items[] = 'Contrato de arrendamiento formal por 12 meses';
            $items[] = 'Depósito en garantía equivalente a 1 mes de renta';
            $items[] = 'Póliza jurídica o fiador con inmueble libre de gravamen';
            $items[] = 'Inventario fotográfico y entrega formal de llaves';
            return array_values(array_unique($items));
        }

        if ($type === 'terreno') {
            $items[] = 'Escritura pública inscrita ante el Registro Público de la Propiedad';
            $items[] = 'Certificado de libertad de gravamen vigente';
            $items[] = 'Uso de suelo y factibilidades verificadas';
            $items[] = 'Boleta predial y deslinde topográfico al corriente';
            return array_values(array_unique($items));
        }

        $items[] = 'Escritura pública inscrita y verificada ante RPP';
        $items[] = 'Certificado de libertad de gravamen al día';
        $items[] = 'Predial, agua y derechos municipales al corriente';
        $items[] = 'Avalúo comercial y documentación lista para notaría';
        return array_values(array_unique($items));
    }

    /**
     * Construye opciones de pago diferenciadas para venta, renta o terreno.
     */
    private function buildPaymentOptions(string $operation, string $type): array {
        if ($operation === 'rentar') {
            return [
                ['code' => 'SPEI', 'label' => 'Transferencia SPEI', 'style' => 'spei'],
                ['code' => 'DEPOSITO', 'label' => '1 mes depósito + renta', 'style' => 'cash'],
                ['code' => 'TARJETA', 'label' => 'Cargo con Tarjeta', 'style' => 'visa'],
                ['code' => 'POLIZA', 'label' => 'Póliza Jurídica', 'style' => 'infonavit'],
            ];
        }

        if ($type === 'terreno') {
            return [
                ['code' => 'CONTADO', 'label' => 'Pago de Contado', 'style' => 'cash'],
                ['code' => 'SPEI', 'label' => 'Transferencia SPEI', 'style' => 'spei'],
                ['code' => 'BANCARIO', 'label' => 'Crédito Terreno', 'style' => 'visa'],
                ['code' => 'PLAZOS', 'label' => 'Financiamiento Directo', 'style' => 'mastercard'],
            ];
        }

        return [
            ['code' => 'EFECTIVO', 'label' => 'Contado', 'style' => 'cash'],
            ['code' => 'SPEI', 'label' => 'Transferencia SPEI', 'style' => 'spei'],
            ['code' => 'BANCARIO', 'label' => 'Crédito Bancario', 'style' => 'visa'],
            ['code' => 'INFONAVIT', 'label' => 'INFONAVIT', 'style' => 'infonavit'],
            ['code' => 'FOVISSSTE', 'label' => 'FOVISSSTE', 'style' => 'fovissste'],
        ];
    }

    /**
     * Construye la metadata completa del recorrido 360° y su galería estática accesible.
     */
    private function buildVirtualTourPayload(int $id, string $title, string $tourUrl, string $tourDesc, ?string $videoPath): array {
        $isIndustrial = str_contains(strtolower($tourUrl), 'industrial');

        if ($isIndustrial) {
            $staticViews = [
                [
                    'title' => 'Vista Aérea (Master Plan)',
                    'image' => self::resolveAssetUrl('tour/panoramas/industrial/aerea.jpg'),
                    'alt' => 'Vista aérea del polígono industrial y vialidades principales',
                    'description' => 'Polígono regular de 5,000 a 12,500 m² con acceso directo al libramiento y corredor logístico.',
                ],
                [
                    'title' => 'Acceso y Caseta de Control',
                    'image' => self::resolveAssetUrl('tour/panoramas/industrial/acceso.jpg'),
                    'alt' => 'Acceso controlado 24/7 y báscula camionera de 80 toneladas',
                    'description' => 'Caseta blindada con control LPR CCTV, vialidad MR-45 de 22 cm y báscula de 80 toneladas.',
                ],
                [
                    'title' => 'Plataforma Nivelada (Lote)',
                    'image' => self::resolveAssetUrl('tour/panoramas/industrial/lote.jpg'),
                    'alt' => 'Plataforma industrial nivelada y compactada al 95% Proctor',
                    'description' => 'Capacidad portante de 6.5 a 8.0 ton/m² lista para cimentación de naves y racks de gran altura.',
                ],
                [
                    'title' => 'Subestación Eléctrica 115 kV',
                    'image' => self::resolveAssetUrl('tour/panoramas/industrial/subestacion.jpg'),
                    'alt' => 'Subestación eléctrica industrial de alta y media tensión',
                    'description' => 'Alimentación escalable hasta 20 MVA, red de gas natural industrial de 4 pulgadas y fibra óptica.',
                ],
                [
                    'title' => 'Patio Logístico y Andenes',
                    'image' => self::resolveAssetUrl('tour/panoramas/industrial/maniobras.jpg'),
                    'alt' => 'Patio de maniobras de 50 metros para tráileres full de 53 pies',
                    'description' => 'Amplio patio de maniobras proyectado para andenes hidráulicos de 30,000 lbs.',
                ],
            ];

            return [
                'type' => 'iframe',
                'url' => $tourUrl,
                'video' => $videoPath,
                'title' => $tourDesc !== '' ? $tourDesc : 'Recorrido Interactivo 360° — ' . $title,
                'subtitle' => 'Explora la plataforma nivelada, subestación eléctrica, patio de maniobras y caseta de control.',
                'helpText' => '💡 Arrastra o usa los controles direccionales, zoom (+)/(−) y flechas del teclado para recorrer el parque.',
                'estimatedDataMb' => 6.4,
                'staticViews' => $staticViews,
            ];
        }

        $staticViews = [
            [
                'title' => 'Sala Principal',
                'image' => self::resolveAssetUrl('tour/panoramas/sala.jpg'),
                'alt' => 'Sala principal de doble altura con ventanales térmicos',
                'description' => 'Doble altura con iluminación LED perimetral indirecta, ventanales duovent UV y piso porcelánico gran formato.',
            ],
            [
                'title' => 'Comedor y Cocina Integral',
                'image' => self::resolveAssetUrl('tour/panoramas/comedor.jpg'),
                'alt' => 'Comedor amplio conectado con cocina equipada con isla de cuarzo',
                'description' => 'Isla central en cuarzo blanco calacatta, parrilla de inducción y campana oculta.',
            ],
            [
                'title' => 'Recámara Principal',
                'image' => self::resolveAssetUrl('tour/panoramas/recamara.jpg'),
                'alt' => 'Master suite con iluminación natural y vestidor en madera de nogal',
                'description' => 'Suite para cama King Size con walk-in closet independiente y baño privado completo.',
            ],
            [
                'title' => 'Terraza y Jardín',
                'image' => self::resolveAssetUrl('tour/panoramas/terraza.jpg'),
                'alt' => 'Terraza techada exterior con área verde y preparación para asador',
                'description' => 'Área social exterior con piso cerámico antiderrapante y jardín privado iluminado.',
            ],
        ];

        return [
            'type' => 'iframe',
            'url' => $tourUrl,
            'video' => $videoPath,
            'title' => $tourDesc !== '' ? $tourDesc : 'Recorrido Interactivo 360° — ' . $title,
            'subtitle' => 'Explora los espacios en 360°, interactúa con los puntos informativos y conoce los acabados.',
            'helpText' => '💡 Arrastra o usa los controles direccionales, zoom (+)/(−) y flechas del teclado para navegar entre salas.',
            'estimatedDataMb' => 4.6,
            'staticViews' => $staticViews,
        ];
    }

    /**
     * Retorna una propiedad por su ID
     */
    public function getById(int $id): ?array {
        $all = $this->getAll();
        foreach ($all as $property) {
            if ((int) ($property['id'] ?? 0) === $id) {
                return $property;
            }
        }
        return null;
    }

    /**
     * Filtra propiedades por tipo y/o tipo de operación
     */
    public function filter(?string $type = null, ?string $operation = null): array {
        $properties = $this->getAll();
        return array_values(array_filter($properties, function ($item) use ($type, $operation) {
            $matchType = ($type === null || $type === '' || $type === 'all' || $item['type'] === $type);
            $matchOp = ($operation === null || $operation === '' || $operation === 'all' || $item['operation'] === $operation);
            return $matchType && $matchOp;
        }));
    }

    /**
     * Obtiene el listado de propiedades para la página de inicio
     */
    public function getFeatured(): array {
        return $this->getAll();
    }

    /**
     * Registra una propiedad publicada desde Vender, incluyendo validación y
     * almacenamiento real de fotografías en public/uploads/propiedades/.
     */
    public function create(array $data, array $files = []): array {
        $owner = Usuario::current();
        if (empty($owner['id'])) {
            return ['success' => false, 'message' => 'Inicia sesión o crea una cuenta para publicar.'];
        }
        if ($this->db === null) {
            return ['success' => false, 'message' => 'La base de datos no está disponible.'];
        }

        $title       = trim((string) ($data['title'] ?? ''));
        $type        = strtoupper(trim((string) ($data['type'] ?? '')));
        $operation   = strtoupper(trim((string) ($data['operation'] ?? 'VENTA')));
        $price       = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $location    = trim((string) ($data['location'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $area        = filter_var($data['area'] ?? null, FILTER_VALIDATE_FLOAT);
        $bedroomsRaw = trim((string) ($data['bedrooms'] ?? ''));
        $bathsRaw    = trim((string) ($data['bathrooms'] ?? ''));
        $parkingRaw  = trim((string) ($data['parking'] ?? ''));
        $landUse     = trim((string) ($data['land_use'] ?? ''));
        $services    = trim((string) ($data['services'] ?? ''));
        $ownerName   = trim((string) ($owner['name'] ?? '') . ' ' . (string) ($owner['last'] ?? ''));
        $ownerPhone  = trim((string) ($data['owner_phone'] ?? ($owner['phone'] ?? '')));
        $ownerEmail  = strtolower(trim((string) ($owner['email'] ?? '')));
        $aiGenerated = (string) ($data['ai_generated'] ?? '0') === '1';
        $aiApproved  = (string) ($data['ai_approved'] ?? '0') === '1';

        if (mb_strlen($title) < 5 || mb_strlen($title) > 180) {
            return ['success' => false, 'message' => 'El título debe tener entre 5 y 180 caracteres.'];
        }
        if (!in_array($type, ['CASA', 'DEPARTAMENTO', 'TERRENO'], true)) {
            return ['success' => false, 'message' => 'Selecciona un tipo de propiedad válido (Casa, Departamento o Terreno).'];
        }
        if (!in_array($operation, ['VENTA', 'RENTA'], true)) {
            return ['success' => false, 'message' => 'Selecciona una operación válida (Venta o Renta).'];
        }
        if ($price === false || $price <= 0) {
            return ['success' => false, 'message' => 'Ingresa un precio válido mayor a cero.'];
        }
        if ($location === '' || mb_strlen($location) < 4) {
            return ['success' => false, 'message' => 'Completa colonia, ciudad y estado de la propiedad.'];
        }
        if (mb_strlen($description) < 20) {
            return ['success' => false, 'message' => 'La descripción debe tener al menos 20 caracteres.'];
        }
        if ($aiGenerated && !$aiApproved) {
            return ['success' => false, 'message' => 'Debes revisar y aprobar explícitamente el borrador generado con IA antes de publicar.'];
        }
        if ($area === false || $area <= 0) {
            return ['success' => false, 'message' => 'Ingresa una superficie válida en m² mayor a cero.'];
        }

        $bedrooms = ($type !== 'TERRENO' && $bedroomsRaw !== '') ? filter_var($bedroomsRaw, FILTER_VALIDATE_INT) : null;
        $bathrooms = ($type !== 'TERRENO' && $bathsRaw !== '') ? filter_var($bathsRaw, FILTER_VALIDATE_INT) : null;
        $parking = ($type !== 'TERRENO' && $parkingRaw !== '') ? filter_var($parkingRaw, FILTER_VALIDATE_INT) : null;

        if ($type !== 'TERRENO') {
            if ($bedrooms === false || $bedrooms === null || $bedrooms < 0 || $bedrooms > 50) {
                return ['success' => false, 'message' => 'Indica el número de habitaciones (0 a 50).'];
            }
            if ($bathrooms === false || $bathrooms === null || $bathrooms < 0 || $bathrooms > 50) {
                return ['success' => false, 'message' => 'Indica el número de baños (0 a 50).'];
            }
            if ($parking !== null && ($parking === false || $parking < 0 || $parking > 50)) {
                return ['success' => false, 'message' => 'Indica un número válido de estacionamientos.'];
            }
        }

        $digitsOnly = preg_replace('/\D+/', '', $ownerPhone);
        if (mb_strlen($ownerName) < 3 || strlen((string) $digitsOnly) < 10 || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Completa nombre completo, teléfono (10 dígitos) y correo válido del propietario.'];
        }

        // Validar archivos de fotografía
        $validatedPhotos = $this->validateUploadedPhotos($files['photos'] ?? null);
        if (!$validatedPhotos['valid']) {
            return ['success' => false, 'message' => $validatedPhotos['message']];
        }
        $photoItems = $validatedPhotos['files'];
        $captions = is_array($data['photo_captions'] ?? null) ? $data['photo_captions'] : [];

        // Enriquecer descripción con características adicionales si aplican
        $extraNotes = [];
        if ($type !== 'TERRENO' && $parking !== null && $parking > 0) {
            $extraNotes[] = $parking . ' cajón(es) de estacionamiento';
        }
        if ($type === 'TERRENO' && $landUse !== '') {
            $extraNotes[] = 'Uso de suelo: ' . $landUse;
        }
        if ($type === 'TERRENO' && $services !== '') {
            $extraNotes[] = 'Servicios disponibles: ' . $services;
        }
        $fullDescription = $description . (!empty($extraNotes) ? "\n\nCaracterísticas adicionales: " . implode(' · ', $extraNotes) . '.' : '');

        $legalSummary = $type === 'TERRENO' && $landUse !== ''
            ? 'Uso de suelo ' . $landUse . ' · Documentación en validación por INFONATEC'
            : ($operation === 'RENTA'
                ? 'Arrendamiento formal · Validación documental con asesor INFONATEC'
                : 'Propiedad en proceso de validación documental y RPP con INFONATEC');

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/propiedades';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return ['success' => false, 'message' => 'No se pudo preparar el directorio de fotografías.'];
        }

        $movedPaths = [];

        try {
            $ownsTransaction = !$this->db->inTransaction();
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }

            // La identidad procede exclusivamente de la sesión autenticada.
            $ownerId = (int) $owner['id'];
            $ownerCheck = $this->db->prepare('SELECT id_usuario FROM usuario WHERE id_usuario = ? AND activo = 1');
            $ownerCheck->execute([$ownerId]);
            if (!$ownerCheck->fetchColumn()) {
                throw new RuntimeException('La cuenta no está activa.');
            }

            $categoryQuery = $this->db->prepare('SELECT id_categoria FROM categoria WHERE nombre = ? LIMIT 1');
            $categoryQuery->execute([$type]);
            $categoryId = $categoryQuery->fetchColumn();
            $stateId = $this->db->query("SELECT id_estado FROM estado_propiedad WHERE nombre = 'DISPONIBLE' LIMIT 1")->fetchColumn();

            if (!$categoryId || !$stateId) {
                throw new RuntimeException('Faltan catálogos iniciales en la base de datos.');
            }

            $propertyInsert = $this->db->prepare(
                'INSERT INTO propiedad (id_propietario, id_categoria, id_estado, titulo, descripcion, ubicacion, precio, superficie, habitaciones, banos, operacion, situacion_legal)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $propertyInsert->execute([
                (int) $ownerId,
                (int) $categoryId,
                (int) $stateId,
                $title,
                $fullDescription,
                $location,
                (float) $price,
                (float) $area,
                $bedrooms,
                $bathrooms,
                $operation,
                $legalSummary,
            ]);
            $propertyId = (int) $this->db->lastInsertId();

            $imgInsert = $this->db->prepare(
                'INSERT INTO imagen_propiedad (id_propiedad, url_imagen, descripcion, orden_visualizacion) VALUES (?, ?, ?, ?)'
            );

            foreach ($photoItems as $index => $photo) {
                $filename = 'prop_' . $propertyId . '_' . bin2hex(random_bytes(6)) . '.' . $photo['ext'];
                $destPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
                if (!move_uploaded_file($photo['tmp_name'], $destPath)) {
                    throw new RuntimeException('No se pudo guardar una de las fotografías en el servidor.');
                }
                $movedPaths[] = $destPath;

                $caption = trim((string) ($captions[$index] ?? ''));
                if ($caption === '') {
                    $caption = $title . ' — Fotografía ' . ($index + 1);
                }

                $imgInsert->execute([
                    $propertyId,
                    'uploads/propiedades/' . $filename,
                    mb_substr($caption, 0, 250),
                    $index + 1,
                ]);
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return [
                'success' => true,
                'id'      => $propertyId,
                'photos'  => count($movedPaths),
                'message' => 'Propiedad publicada correctamente con ' . count($movedPaths) . ' fotografía(s).',
            ];
        } catch (Throwable $e) {
            if (($ownsTransaction ?? false) && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            foreach ($movedPaths as $path) {
                if (is_file($path)) {
                    @unlink($path);
                }
            }
            error_log('Error al publicar propiedad: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No fue posible registrar la propiedad. Verifica los datos e inténtalo de nuevo.'];
        }
    }

    /**
     * Valida MIME real, dimensiones y tamaño de cada fotografía subida.
     */
    private function validateUploadedPhotos($rawPhotos): array {
        if (!is_array($rawPhotos) || empty($rawPhotos['name'])) {
            return ['valid' => false, 'message' => 'Agrega al menos 1 fotografía válida (JPG, PNG o WEBP) antes de publicar.'];
        }

        $names = is_array($rawPhotos['name']) ? $rawPhotos['name'] : [$rawPhotos['name']];
        $tmpNames = is_array($rawPhotos['tmp_name']) ? $rawPhotos['tmp_name'] : [$rawPhotos['tmp_name']];
        $errors = is_array($rawPhotos['error']) ? $rawPhotos['error'] : [$rawPhotos['error']];
        $sizes = is_array($rawPhotos['size']) ? $rawPhotos['size'] : [$rawPhotos['size']];

        $collected = [];
        $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;

        foreach ($names as $i => $origName) {
            $err = (int) ($errors[$i] ?? UPLOAD_ERR_NO_FILE);
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($err !== UPLOAD_ERR_OK) {
                if ($finfo) finfo_close($finfo);
                return ['valid' => false, 'message' => 'Error al subir la fotografía "' . basename((string) $origName) . '".'];
            }

            $size = (int) ($sizes[$i] ?? 0);
            if ($size <= 0 || $size > self::MAX_PHOTO_BYTES) {
                if ($finfo) finfo_close($finfo);
                return ['valid' => false, 'message' => 'La fotografía "' . basename((string) $origName) . '" excede el límite de 5 MB.'];
            }

            $tmp = (string) ($tmpNames[$i] ?? '');
            if ($tmp === '' || !is_uploaded_file($tmp)) {
                if ($finfo) finfo_close($finfo);
                return ['valid' => false, 'message' => 'Archivo de carga inválido para "' . basename((string) $origName) . '".'];
            }

            $mime = $finfo ? (string) finfo_file($finfo, $tmp) : (string) (mime_content_type($tmp) ?: '');
            if (!isset(self::ALLOWED_MIME_EXTENSIONS[$mime]) || @getimagesize($tmp) === false) {
                if ($finfo) finfo_close($finfo);
                return ['valid' => false, 'message' => 'Formato no permitido en "' . basename((string) $origName) . '". Usa solo JPG, PNG o WEBP.'];
            }

            $collected[] = [
                'tmp_name' => $tmp,
                'ext'      => self::ALLOWED_MIME_EXTENSIONS[$mime],
                'name'     => basename((string) $origName),
            ];
        }

        if ($finfo) {
            finfo_close($finfo);
        }

        if (empty($collected)) {
            return ['valid' => false, 'message' => 'Debes subir al menos 1 fotografía válida (JPG, PNG o WEBP).'];
        }
        if (count($collected) > self::MAX_PHOTOS) {
            return ['valid' => false, 'message' => 'Puedes subir como máximo ' . self::MAX_PHOTOS . ' fotografías por propiedad.'];
        }

        return ['valid' => true, 'files' => $collected];
    }

    /**
     * Obtiene los IDs de propiedades guardadas como favoritas por el usuario.
     */
    public function getUserFavoriteIds(int $userId): array {
        if ($this->db === null || $userId <= 0) {
            return [];
        }
        try {
            $stmt = $this->db->prepare('SELECT id_propiedad FROM favorito WHERE id_usuario = ? ORDER BY fecha_registro DESC');
            $stmt->execute([$userId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (Exception $e) {
            error_log('Error consultando favoritos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Alterna una propiedad en la lista de favoritos del usuario en BD.
     */
    public function toggleFavorite(int $userId, int $propertyId): array {
        if ($this->db === null || $userId <= 0 || $propertyId <= 0) {
            return ['success' => false, 'message' => 'No fue posible actualizar favoritos.'];
        }

        try {
            $exists = $this->db->prepare('SELECT 1 FROM favorito WHERE id_usuario = ? AND id_propiedad = ? LIMIT 1');
            $exists->execute([$userId, $propertyId]);
            if ($exists->fetchColumn()) {
                $del = $this->db->prepare('DELETE FROM favorito WHERE id_usuario = ? AND id_propiedad = ?');
                $del->execute([$userId, $propertyId]);
                $isFavorite = false;
            } else {
                $propCheck = $this->db->prepare('SELECT 1 FROM propiedad WHERE id_propiedad = ? LIMIT 1');
                $propCheck->execute([$propertyId]);
                if (!$propCheck->fetchColumn()) {
                    return ['success' => false, 'message' => 'Propiedad no encontrada.'];
                }
                $ins = $this->db->prepare('INSERT IGNORE INTO favorito (id_usuario, id_propiedad) VALUES (?, ?)');
                $ins->execute([$userId, $propertyId]);
                $isFavorite = true;
            }

            return [
                'success'     => true,
                'is_favorite' => $isFavorite,
                'favorites'   => $this->getUserFavoriteIds($userId),
            ];
        } catch (Exception $e) {
            error_log('Error al actualizar favorito: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No fue posible guardar el favorito.'];
        }
    }

    /**
     * Sincroniza favoritos guardados localmente al iniciar sesión o registrarse.
     */
    public function syncFavorites(int $userId, array $propertyIds): array {
        if ($this->db === null || $userId <= 0) {
            return $this->getUserFavoriteIds($userId);
        }

        try {
            $ins = $this->db->prepare(
                'INSERT IGNORE INTO favorito (id_usuario, id_propiedad)
                 SELECT ?, id_propiedad FROM propiedad WHERE id_propiedad = ?'
            );
            foreach ($propertyIds as $pid) {
                $pidInt = (int) $pid;
                if ($pidInt > 0) {
                    $ins->execute([$userId, $pidInt]);
                }
            }
        } catch (Exception $e) {
            error_log('Error sincronizando favoritos: ' . $e->getMessage());
        }

        return $this->getUserFavoriteIds($userId);
    }
}
