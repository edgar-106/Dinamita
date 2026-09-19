<?php
/**
 * Modelo Propiedad
 * Gestiona el catálogo de inmuebles (casas, departamentos, terrenos)
 */

require_once __DIR__ . '/../core/Model.php';

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
                'url' => 'tour/index.html',
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
            'title' => 'Terreno Campestre El Manantial',
            'type' => 'terreno',
            'type_label' => 'Terreno',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'Arteaga, COAH',
            'full_location' => 'Arteaga, Coahuila',
            'tag' => 'NUEVO',
            'price' => '$980,000 MXN',
            'raw_price' => 980000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1448375240586-882707db888b?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['1,000 m²'],
            'details' => ['1,000 m² campestre', 'Vistas a la sierra', 'Luz y agua a pie de lote'],
            'operation_details' => ['Venta de contado', 'Financiamiento directo a 24 meses'],
            'virtualTour' => null
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
            'title' => 'Terreno Comercial Periférico',
            'type' => 'terreno',
            'type_label' => 'Terreno',
            'operation' => 'comprar',
            'operation_label' => 'Venta',
            'location' => 'Querétaro, QRO',
            'full_location' => 'Santiago de Querétaro, QRO',
            'tag' => 'COMERCIAL',
            'price' => '$5,400,000 MXN',
            'raw_price' => 5400000,
            'video' => 'casa 1.mp4',
            'image' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=1200&q=80',
            'stats' => ['800 m²'],
            'details' => ['800 m² sobre avenida principal', 'Uso de suelo mixto/comercial', 'Todos los servicios'],
            'operation_details' => ['Venta', 'Escritura inmediata'],
            'virtualTour' => null
        ]
    ];

    /**
     * Retorna todas las propiedades (desde BD si existe tabla, o fallback a mock)
     */
    public function getAll(): array {
        if ($this->db !== null) {
            try {
                $stmt = $this->db->query("SELECT * FROM propiedades ORDER BY id ASC");
                $dbProps = $stmt->fetchAll();
                if (!empty($dbProps)) {
                    return $dbProps;
                }
            } catch (Exception $e) {
                // Fallback a mock data
            }
        }
        return self::$mockProperties;
    }

    /**
     * Retorna una propiedad por su ID
     */
    public function getById(int $id): ?array {
        $all = $this->getAll();
        return $all[$id] ?? null;
    }

    /**
     * Filtra propiedades por tipo y/o tipo de operación
     */
    public function filter(?string $type = null, ?string $operation = null): array {
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
}
