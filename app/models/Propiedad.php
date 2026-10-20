<?php
/**
 * Modelo Propiedad
 * Gestiona el catálogo de inmuebles (casas, departamentos, terrenos)
 */

class Propiedad extends Model {

    /**
     * Catálogo base de propiedades con información completa
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
            'tag' => 'EXCLUSIVA',
            'price' => '$1,850,000 MXN',
            'raw_price' => 1850000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['450 m²'],
            'details' => ['450 m² de superficie', 'Uso residencial', 'Servicios subterráneos', 'Propiedad privada'],
            'operation_details' => ['Venta directa', 'Contado', 'Facilidades de pago'],
            'virtualTour' => null
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
            'tag' => 'VENTA',
            'price' => '$6,800,000 MXN',
            'raw_price' => 6800000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1400&q=90',
            'stats' => ['340 m²', '4 Rec', '5 Baños'],
            'details' => ['4 habitaciones', '5 baños', '340 m² de construcción', 'Alberca privada', 'Estacionamiento 3 autos'],
            'operation_details' => ['Venta', 'Efectivo', 'Crédito bancario', 'Financiamiento sujeto a condiciones'],
            'virtualTour' => [
                'type' => 'iframe',
                'url' => 'tour',
                'title' => 'Recorrido Interactivo 360° — Residencia Minimalista Las Cumbres',
                'subtitle' => 'Explora los espacios en 360°, interactúa con los puntos de navegación y conoce los acabados.',
                'buttonText' => 'VER RECORRIDO INTERACTIVO 360° ▶',
                'helpText' => '💡 Arrastra para mirar alrededor. Haz clic en las flechas para caminar de una sala a otra o en [i] para ver detalles.'
            ]
        ],
        3 => [
            'id' => 3,
            'title' => 'Penthouse Polanco Sky View',
            'type' => 'departamento',
            'type_label' => 'Departamento',
            'operation' => 'rentar',
            'operation_label' => 'Renta',
            'location' => 'CDMX',
            'full_location' => 'Ciudad de México',
            'tag' => 'RENTA',
            'price' => '$38,000 / mes',
            'raw_price' => 38000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1600607688969-a5bfcd646154?auto=format&fit=crop&w=1400&q=90',
            'stats' => ['160 m²', '3 Rec', '2.5 Baños'],
            'details' => ['160 m²', '3 habitaciones', '2.5 baños', 'Estacionamiento techado', 'Seguridad 24/7'],
            'operation_details' => ['Renta mensual', 'Depósito requerido', 'Póliza jurídica'],
            'virtualTour' => [
                'video' => 'casa 1.mp4',
                'title' => 'Recorrido Virtual — Penthouse Polanco Sky View',
                'subtitle' => 'Conoce los espacios interiores, acabados y vista panorámica de este exclusivo penthouse.'
            ]
        ],
        4 => [
            'id' => 4,
            'title' => 'Casa Bosques del Valle',
            'type' => 'casa',
            'type_label' => 'Casa',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'San Pedro, NL',
            'full_location' => 'San Pedro Garza García, NL',
            'tag' => 'EXCLUSIVA',
            'price' => '$12,500,000 MXN',
            'raw_price' => 12500000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['520 m²', '5 Rec', '6 Baños'],
            'details' => ['520 m² de construcción', '5 recámaras con vestidor', 'Jardín con asador', 'Acabados en mármol'],
            'operation_details' => ['Venta', 'Crédito hipotecario aceptado'],
            'virtualTour' => null
        ],
        5 => [
            'id' => 5,
            'title' => 'Loft Santa Fe',
            'type' => 'departamento',
            'type_label' => 'Departamento',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'Cuajimalpa, CDMX',
            'full_location' => 'Ciudad de México',
            'tag' => 'OPORTUNIDAD',
            'price' => '$3,950,000 MXN',
            'raw_price' => 3950000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['75 m²', '1 Rec', '1.5 Baños'],
            'details' => ['75 m² estilo industrial', '1 recámara abierta', 'Amenidades completas (Gym, alberca)'],
            'operation_details' => ['Venta', 'Apto para crédito'],
            'virtualTour' => null
        ],
        6 => [
            'id' => 6,
            'title' => 'Terreno Industrial Querétaro',
            'type' => 'terreno',
            'type_label' => 'Terreno',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'Querétaro, QRO',
            'full_location' => 'Querétaro, Querétaro',
            'tag' => 'VENTA',
            'price' => '$4,100,000 MXN',
            'raw_price' => 4100000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1531971589569-0d9370cbe1e5?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['1,200 m²'],
            'details' => ['1,200 m² de superficie regular', 'Uso industrial I-3', 'Plataforma nivelada con mecánica de suelo', 'Servicios de media y alta tensión'],
            'operation_details' => ['Venta directa', 'Escritura inmediata', 'Facilidades de pago y esquemas de crédito'],
            'virtualTour' => [
                'type' => 'iframe',
                'url' => 'tour/industrial',
                'title' => 'Recorrido Interactivo 360° — Terreno Industrial',
                'subtitle' => 'Explora la plataforma nivelada, subestación eléctrica, patio de maniobras y caseta de control.',
                'buttonText' => 'VER RECORRIDO INTERACTIVO 360° ▶',
                'helpText' => '💡 Arrastra para mirar en 360°. Haz clic en las flechas de navegación o en los puntos de información técnica.'
            ]
        ],
        7 => [
            'id' => 7,
            'title' => 'Departamento Condesa Garden',
            'type' => 'departamento',
            'type_label' => 'Departamento',
            'operation' => 'rentar',
            'operation_label' => 'Renta',
            'location' => 'Cuauhtémoc, CDMX',
            'full_location' => 'Ciudad de México',
            'tag' => 'RENTA',
            'price' => '$26,000 / mes',
            'raw_price' => 26000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['95 m²', '2 Rec', '2 Baños'],
            'details' => ['95 m² con balcón', 'Pet friendly', 'A 2 cuadras del parque México'],
            'operation_details' => ['Renta mensual', 'Depósito y fiador'],
            'virtualTour' => null
        ],
        8 => [
            'id' => 8,
            'title' => 'Villa Paraíso',
            'type' => 'casa',
            'type_label' => 'Casa',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'Cancún, QROO',
            'full_location' => 'Cancún, Quintana Roo',
            'tag' => 'EXCLUSIVA',
            'price' => '$8,200,000 MXN',
            'raw_price' => 8200000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['380 m²', '4 Rec', '4.5 Baños'],
            'details' => ['380 m² con muelle privado', 'Alberca infinity', 'Seguridad privada'],
            'operation_details' => ['Venta', 'Acepta créditos bancarios y extranjeros'],
            'virtualTour' => null
        ],
        9 => [
            'id' => 9,
            'title' => 'Estudio Roma Norte',
            'type' => 'departamento',
            'type_label' => 'Departamento',
            'operation' => 'rentar',
            'operation_label' => 'Renta',
            'location' => 'Roma Norte, CDMX',
            'full_location' => 'Ciudad de México',
            'tag' => 'RENTA',
            'price' => '$19,500 / mes',
            'raw_price' => 19500,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['50 m²', '1 Rec', '1 Baño'],
            'details' => ['Totalmente amueblado y equipado', 'Edificio con roof garden', 'Excelente ubicación'],
            'operation_details' => ['Renta', 'Contrato mínimo 1 año'],
            'virtualTour' => null
        ],
        10 => [
            'id' => 10,
            'title' => 'Macrolote Industrial Apodaca',
            'type' => 'terreno',
            'type_label' => 'Terreno',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'Apodaca, NL',
            'full_location' => 'Apodaca, Nuevo León',
            'tag' => 'INDUSTRIAL',
            'price' => '$12,500,000 MXN',
            'raw_price' => 12500000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1531971589569-0d9370cbe1e5?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['5,000 m²'],
            'details' => ['5,000 m² a 12,500 m²', 'Uso de suelo industrial pesado I-3', 'Acceso directo a autopista y libramiento', 'Subestación eléctrica (115 kV) y gas natural'],
            'operation_details' => ['Venta', 'Esquemas de financiamiento industrial', 'Escritura inmediata'],
            'virtualTour' => [
                'type' => 'iframe',
                'url' => 'tour/industrial',
                'title' => 'Recorrido Interactivo 360° — Terreno & Parque Industrial',
                'subtitle' => 'Explora la plataforma nivelada, subestación eléctrica, patio de maniobras y caseta de control.',
                'buttonText' => 'VER RECORRIDO INTERACTIVO 360° ▶',
                'helpText' => '💡 Arrastra para mirar en 360°. Haz clic en las flechas de navegación o en los puntos de información técnica.'
            ]
        ]
    ];

    /**
     * Retorna todas las propiedades (desde BD si existe tabla, o fallback a mock)
     */
    public function getAll(): array {
        // La base de datos tiene prioridad para que el catálogo pueda crecer
        // sin modificar código; el arreglo estático mantiene la demo operativa.
        if ($this->db !== null) {
            try {
                // La base usa nombres normalizados en español; aquí se adaptan
                // al contrato que ya consumen las vistas y la API del sitio.
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
                            SELECT ip.url_imagen
                            FROM imagen_propiedad ip
                            WHERE ip.id_propiedad = p.id_propiedad
                            ORDER BY ip.orden_visualizacion, ip.id_imagen
                            LIMIT 1
                        ), '') AS image,
                        COALESCE((
                            SELECT pv.url_3d
                            FROM paseo_virtual pv
                            WHERE pv.id_propiedad = p.id_propiedad
                            LIMIT 1
                        ), '') AS tour_url
                    FROM propiedad p
                    INNER JOIN categoria c ON c.id_categoria = p.id_categoria
                    INNER JOIN estado_propiedad e ON e.id_estado = p.id_estado
                    WHERE e.nombre = 'DISPONIBLE'
                    ORDER BY p.fecha_publicacion DESC, p.id_propiedad DESC"
                );
                $dbProps = array_map([$this, 'mapDatabaseProperty'], $stmt->fetchAll());
                if (!empty($dbProps)) {
                    return $dbProps;
                }
            } catch (Exception $e) {
                // Fallback a mock data
            }
        }
        return self::$mockProperties;
    }

    /** Convierte un registro del esquema inmobiliario al formato de la UI. */
    private function mapDatabaseProperty(array $property): array {
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

        $isRent = $property['operation'] === 'rentar';
        $details = array_values(array_filter([
            $property['descripcion'] ?: null,
            $property['superficie'] !== null ? number_format((float) $property['superficie'], 0) . ' m²' : null,
            $property['habitaciones'] !== null ? $property['habitaciones'] . ' habitaciones' : null,
            $property['banos'] !== null ? $property['banos'] . ' baños' : null,
        ]));

        return [
            'id' => (int) $property['id'],
            'title' => $property['title'],
            'type' => $property['type'],
            'type_label' => ucfirst(strtolower($property['type_label'])),
            'operation' => $property['operation'],
            'operation_label' => $property['operation_label'],
            'location' => $property['location'],
            'full_location' => $property['location'],
            'tag' => $property['tag'],
            'price' => '$' . number_format((float) $property['raw_price'], 0) . ($isRent ? ' / mes' : ' MXN'),
            'raw_price' => (float) $property['raw_price'],
            // La UI actual usa un video para las tarjetas; conservar el recurso
            // local evita tratar una fotografía como archivo multimedia.
            'video' => 'casa 1.mp4',
            'image' => $property['image'],
            'stats' => $stats,
            'details' => $details,
            'operation_details' => array_values(array_filter([
                $property['operation_label'],
                $property['situacion_legal'] ?: null,
            ])),
            'virtualTour' => $property['tour_url'] !== '' ? ['type' => 'iframe', 'url' => $property['tour_url']] : null,
        ];
    }

    /**
     * Retorna una propiedad por su ID
     */
    public function getById(int $id): ?array {
        $all = $this->getAll();

        // El catálogo de respaldo usa el ID como índice, pero PDO devuelve
        // arreglos con índices consecutivos. Buscar por el campo `id` permite
        // que la API responda correctamente en ambos casos.
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
        // "all" y null representan ausencia de filtro, no un tipo real.
        $properties = $this->getAll();
        return array_filter($properties, function($item) use ($type, $operation) {
            $matchType = ($type === null || $type === 'all' || $item['type'] === $type);
            $matchOp = ($operation === null || $operation === 'all' || $item['operation'] === $operation);
            return $matchType && $matchOp;
        });
    }

    /**
     * Obtiene el listado de propiedades para el selector de inicio
     */
    public function getFeatured(): array {
        return $this->getAll();
    }

    /** Registra una propiedad publicada desde el formulario de Vender. */
    public function create(array $data): array {
        if ($this->db === null) {
            return ['success' => false, 'message' => 'La base de datos no está disponible.'];
        }

        $title = trim($data['title'] ?? '');
        $type = strtoupper(trim($data['type'] ?? ''));
        $price = filter_var($data['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $location = trim($data['location'] ?? '');
        $ownerName = trim($data['owner_name'] ?? '');
        $ownerEmail = trim($data['owner_email'] ?? '');

        if ($title === '' || !in_array($type, ['CASA', 'DEPARTAMENTO', 'TERRENO'], true) || $price === false || $price < 0 || $location === '' || $ownerName === '' || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Completa los datos requeridos de la propiedad y del propietario.'];
        }

        try {
            // Permite que esta operación forme parte de una transacción mayor,
            // por ejemplo durante pruebas o flujos administrativos compuestos.
            $ownsTransaction = !$this->db->inTransaction();
            if ($ownsTransaction) {
                $this->db->beginTransaction();
            }

            $ownerQuery = $this->db->prepare('SELECT id_usuario FROM usuario WHERE correo = ?');
            $ownerQuery->execute([$ownerEmail]);
            $ownerId = $ownerQuery->fetchColumn();

            if (!$ownerId) {
                $nameParts = preg_split('/\s+/', $ownerName, 2);
                $usernameBase = strtolower((string) preg_replace('/[^a-z0-9]/', '', strstr($ownerEmail, '@', true)));
                $username = substr($usernameBase ?: 'propietario', 0, 52) . '_' . substr(bin2hex(random_bytes(4)), 0, 7);
                $ownerInsert = $this->db->prepare(
                    'INSERT INTO usuario (nombre, apellido_paterno, correo, telefono, usuario, contrasena) VALUES (?, ?, ?, ?, ?, ?)'
                );
                $ownerInsert->execute([
                    $nameParts[0],
                    $nameParts[1] ?? 'Sin apellido',
                    $ownerEmail,
                    trim($data['owner_phone'] ?? '') ?: null,
                    $username,
                    password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                ]);
                $ownerId = (int) $this->db->lastInsertId();
            }

            $categoryQuery = $this->db->prepare('SELECT id_categoria FROM categoria WHERE nombre = ?');
            $categoryQuery->execute([$type]);
            $categoryId = $categoryQuery->fetchColumn();
            $stateId = $this->db->query("SELECT id_estado FROM estado_propiedad WHERE nombre = 'DISPONIBLE'")->fetchColumn();

            if (!$categoryId || !$stateId) {
                throw new RuntimeException('Faltan los catálogos iniciales de la base de datos.');
            }

            $propertyInsert = $this->db->prepare(
                'INSERT INTO propiedad (id_propietario, id_categoria, id_estado, titulo, descripcion, ubicacion, precio, superficie, habitaciones, banos, operacion)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $propertyInsert->execute([
                $ownerId,
                $categoryId,
                $stateId,
                $title,
                trim($data['description'] ?? '') ?: null,
                $location,
                $price,
                filter_var($data['area'] ?? null, FILTER_VALIDATE_FLOAT) ?: null,
                filter_var($data['bedrooms'] ?? null, FILTER_VALIDATE_INT) ?: null,
                filter_var($data['bathrooms'] ?? null, FILTER_VALIDATE_INT) ?: null,
                strtoupper(trim($data['operation'] ?? 'VENTA')),
            ]);
            $propertyId = (int) $this->db->lastInsertId();
            if ($ownsTransaction) {
                $this->db->commit();
            }

            return ['success' => true, 'id' => $propertyId, 'message' => 'Propiedad registrada correctamente.'];
        } catch (Throwable $e) {
            if (($ownsTransaction ?? false) && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error al publicar propiedad: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No fue posible registrar la propiedad.'];
        }
    }
}
