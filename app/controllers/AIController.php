<?php
/**
 * Controlador de Inteligencia Artificial (AIController)
 * INFONATEC Inmobiliaria
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../config/ai_config.php';
require_once __DIR__ . '/../models/Propiedad.php';

class AIController extends Controller {

    /**
     * Endpoint para procesar mensajes del chat
     */
    public function chat(): void {
        // Leer cuerpo JSON o POST
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            $input = !empty($_POST) ? $_POST : [];
        }
        $userMessage = trim($input['message'] ?? '');

        if (empty($userMessage)) {
            $this->json(['error' => 'Mensaje vacío'], 400);
            return;
        }

        // Obtener catálogo actual de propiedades
        $propiedadModel = new Propiedad();
        $properties = $propiedadModel->getAll();

        // 1. Si hay clave configurada, intentar llamar al LLM
        $apiKey = defined('AI_API_KEY') ? trim(AI_API_KEY) : '';
        if (!empty($apiKey) && AI_PROVIDER !== 'local') {
            $llmResponse = $this->callGeminiApi($userMessage, $properties, $apiKey);
            if ($llmResponse !== null) {
                $this->json([
                    'status' => 'success',
                    'reply'  => $llmResponse,
                    'engine' => 'gemini'
                ]);
                return;
            }
        }

        // 2. Fallback Inteligente Local (alta velocidad, cero fallos, funciona 100% offline)
        $localResponse = $this->localExpertEngine($userMessage, $properties);
        $this->json([
            'status' => 'success',
            'reply'  => $localResponse['reply'],
            'engine' => 'local_expert',
            'related' => $localResponse['related'] ?? []
        ]);
    }

    /**
     * Endpoint para redactar descripciones de inmuebles en Vender
     */
    public function generateDescription(): void {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            $input = !empty($_POST) ? $_POST : [];
        }
        
        $type      = trim($input['type'] ?? 'Propiedad');
        $operation = trim($input['operation'] ?? 'Venta');
        $title     = trim($input['title'] ?? '');
        $price     = trim($input['price'] ?? '');
        $location  = trim($input['location'] ?? '');
        $city      = trim($input['city'] ?? '');
        $state     = trim($input['state'] ?? '');
        $rooms     = trim($input['bedrooms'] ?? '');
        $baths     = trim($input['bathrooms'] ?? '');
        $details   = trim($input['details'] ?? '');

        $promptContext = "Tipo: {$type}, Operación: {$operation}, Título base: {$title}, Precio: {$price}, Ubicación: {$location}, {$city}, {$state}. Recámaras: {$rooms}, Baños: {$baths}. Amenidades extra: {$details}.";

        $apiKey = defined('AI_API_KEY') ? trim(AI_API_KEY) : '';
        if (!empty($apiKey) && AI_PROVIDER !== 'local') {
            $prompt = "Actúa como un experto copywriter inmobiliario mexicano. Redacta una descripción comercial muy atractiva, persuasiva y profesional para anunciar el siguiente inmueble en venta/renta:\n"
                    . $promptContext . "\n"
                    . "La descripción debe incluir:\n"
                    . "1. Un título de impacto en una línea.\n"
                    . "2. Un párrafo introductorio cautivador destacando la ubicación y estilo de vida.\n"
                    . "3. Lista de amenidades y características clave con viñetas elegantes (•).\n"
                    . "4. Un llamado a la acción invitando a agendar visita.\n"
                    . "Sé directo y no añadas introducciones como 'aquí tienes tu descripción'.";

            $llmResult = $this->callGeminiTextOnly($prompt, $apiKey);
            if (!empty($llmResult)) {
                $this->json([
                    'status' => 'success',
                    'description' => $llmResult,
                    'engine' => 'gemini'
                ]);
                return;
            }
        }

        // Fallback local para redacción
        $locStr = !empty($location) ? $location : 'zona privilegiada';
        if (!empty($city)) $locStr .= ", {$city}";
        
        $desc = "¡Extraordinaria oportunidad de {$operation}! Presentamos esta magnífica propiedad ({$type}) ubicada en {$locStr}, diseñada para ofrecer máxima comodidad, plusvalía y estilo de vida.\n\n";
        $desc .= "Cuenta con excelente iluminación natural, espacios optimizados y acabados de primera calidad pensados para tu bienestar y el de tu familia.\n\n";
        $desc .= "Características destacadas:\n";
        if (!empty($rooms)) $desc .= "• {$rooms} amplias recámaras con excelente ventilación\n";
        if (!empty($baths)) $desc .= "• {$baths} baños completos con diseño moderno\n";
        if (!empty($details)) $desc .= "• " . str_replace("\n", "\n• ", $details) . "\n";
        $desc .= "• Ubicación estratégica cerca de vías principales, centros de servicios y comercio\n";
        $desc .= "• Seguridad y alta rentabilidad garantizada\n\n";
        $desc .= "¡No dejes pasar esta oportunidad! Agenda una cita hoy mismo con uno de nuestros asesores certificados INFONATEC.";

        $this->json([
            'status' => 'success',
            'description' => $desc,
            'engine' => 'local_template'
        ]);
    }

    /**
     * Estado del servicio de IA
     */
    public function status(): void {
        $apiKey = defined('AI_API_KEY') ? trim(AI_API_KEY) : '';
        $this->json([
            'ready'    => true,
            'provider' => AI_PROVIDER,
            'model'    => AI_MODEL,
            'has_key'  => !empty($apiKey)
        ]);
    }

    /**
     * Llamada a Google Gemini API
     */
    private function callGeminiApi(string $userMsg, array $properties, string $apiKey): ?string {
        $catalogSummary = "CATÁLOGO DE PROPIEDADES EN INFONATEC:\n";
        foreach ($properties as $p) {
            $tour = !empty($p['virtualTour']) ? ' (Cuenta con Recorrido Virtual 3D)' : '';
            $catalogSummary .= "- ID {$p['id']}: '{$p['title']}' ({$p['type_label']} en {$p['full_location']}) - {$p['operation_label']} - Precio: {$p['price']}{$tour}. Detalles: " . implode(', ', $p['details'] ?? []) . ".\n";
        }

        $systemInstruction = AI_SYSTEM_PROMPT . "\n\n" . $catalogSummary;

        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . AI_MODEL . ":generateContent?key=" . urlencode($apiKey);

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userMsg]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 380
            ]
        ];

        return $this->executeCurlPost($url, $payload);
    }

    /**
     * Llamada simple de texto para Gemini
     */
    private function callGeminiTextOnly(string $prompt, string $apiKey): ?string {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/" . AI_MODEL . ":generateContent?key=" . urlencode($apiKey);
        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [['text' => $prompt]]
                ]
            ],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 450
            ]
        ];
        return $this->executeCurlPost($url, $payload);
    }

    /**
     * Ejecutar petición cURL con timeout estricto
     */
    private function executeCurlPost(string $url, array $payload): ?string {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, AI_TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Compatibilidad con entornos locales XAMPP

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $data = json_decode($response, true);
            if (!empty($data['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($data['candidates'][0]['content']['parts'][0]['text']);
            }
        }

        return null;
    }

    /**
     * Motor Experto Local: Responde inteligentemente sin consumir API ni retrasar la web
     */
    private function localExpertEngine(string $query, array $properties): array {
        $q = mb_strtolower($query, 'UTF-8');
        $related = [];

        // 1. Detección de Recorrido 3D
        if (str_contains($q, '3d') || str_contains($q, 'recorrido') || str_contains($q, 'tour') || str_contains($q, 'virtual')) {
            $tourProps = array_filter($properties, fn($p) => !empty($p['virtualTour']));
            $first = reset($tourProps) ?: ($properties[2] ?? null);
            $title = $first ? $first['title'] : 'Residencia Las Cumbres';
            $id = $first ? $first['id'] : 2;
            return [
                'reply' => "¡Sí! Contamos con recorridos virtuales interactivos 3D. Por ejemplo, en **{$title}** puedes explorar el interior en 360° haciendo clic en la tarjeta o seleccionando 'Ver Recorrido 3D'.",
                'related' => [$id]
            ];
        }

        // 2. Consulta de Créditos / Infonavit / Financiamiento
        if (str_contains($q, 'credito') || str_contains($q, 'crédito') || str_contains($q, 'infonavit') || str_contains($q, 'banco') || str_contains($q, 'bancario') || str_contains($q, 'financiamiento')) {
            return [
                'reply' => "En **INFONATEC** aceptamos diversas modalidades de pago:\n• Crédito Infonavit y Fovissste.\n• Créditos bancarios (BBVA, Santander, Banorte, Scotiabank).\n• Pago de contado o financiamiento directo sujeto a evaluación.\n\nPuedes solicitar una asesoría personalizada sin costo en el botón 'Solicitar Asesoría' de la página principal.",
                'related' => []
            ];
        }

        // 3. Vender o Publicar
        if (str_contains($q, 'vender') || str_contains($q, 'publicar') || str_contains($q, 'anunciar') || str_contains($q, 'comision') || str_contains($q, 'comisión')) {
            return [
                'reply' => "Para vender o rentar tu propiedad con nosotros, visita nuestra sección **Vender** en el menú superior. Contamos con un formulario guiado e inteligencia artificial para redactar el anuncio con máxima atracción a compradores.",
                'related' => []
            ];
        }

        // 4. Búsqueda por Ubicación
        $locations = [
            'monterrey' => ['Monterrey, NL', 'San Pedro, NL'],
            'mida'     => ['Mérida, YUC'],
            'merida'    => ['Mérida, YUC'],
            'yucatan'   => ['Mérida, YUC'],
            'cdmx'      => ['CDMX'],
            'mexico'    => ['CDMX'],
            'polanco'   => ['CDMX'],
            'cancun'    => ['Cancún, Q.Roo'],
            'cancún'    => ['Cancún, Q.Roo'],
            'san pedro' => ['San Pedro, NL']
        ];

        foreach ($locations as $cityKey => $matchedLocs) {
            if (str_contains($q, $cityKey)) {
                $matches = array_filter($properties, function($p) use ($matchedLocs) {
                    foreach ($matchedLocs as $l) {
                        if (str_contains($p['location'], $l) || str_contains($p['full_location'], $l)) return true;
                    }
                    return false;
                });

                if (!empty($matches)) {
                    $reply = "Encontramos las siguientes opciones disponibles en esa zona:\n";
                    $ids = [];
                    foreach ($matches as $m) {
                        $reply .= "• **{$m['title']}** ({$m['type_label']}): {$m['price']} — {$m['full_location']}\n";
                        $ids[] = $m['id'];
                    }
                    $reply .= "\nHaz clic en 'Ver detalles' sobre cualquiera de ellas para conocer la información completa.";
                    return ['reply' => $reply, 'related' => $ids];
                }
            }
        }

        // 5. Búsqueda por tipo de propiedad
        if (str_contains($q, 'terreno') || str_contains($q, 'lote')) {
            $terrenos = array_filter($properties, fn($p) => $p['type'] === 'terreno');
            $reply = "Tenemos excelentes terrenos y lotes residenciales:\n";
            $ids = [];
            foreach ($terrenos as $t) {
                $reply .= "• **{$t['title']}** en {$t['location']} por {$t['price']}\n";
                $ids[] = $t['id'];
            }
            return ['reply' => $reply, 'related' => $ids];
        }

        if (str_contains($q, 'casa') || str_contains($q, 'residencia')) {
            $casas = array_filter($properties, fn($p) => $p['type'] === 'casa');
            $reply = "Aquí tienes algunas de nuestras mejores casas en catálogo:\n";
            $ids = [];
            foreach ($casas as $c) {
                $reply .= "• **{$c['title']}** ({$c['location']}) — {$c['price']}\n";
                $ids[] = $c['id'];
            }
            $reply .= "\n¿Te gustaría ver alguna en específico o agendar un recorrido?";
            return ['reply' => $reply, 'related' => $ids];
        }

        if (str_contains($q, 'renta') || str_contains($q, 'rentar') || str_contains($q, 'departamento') || str_contains($q, 'penthouse')) {
            $deptos = array_filter($properties, fn($p) => $p['operation'] === 'rentar' || $p['type'] === 'departamento');
            $reply = "Tenemos opciones en renta y departamentos exclusivos:\n";
            $ids = [];
            foreach ($deptos as $d) {
                $reply .= "• **{$d['title']}** ({$d['location']}) — {$d['price']}\n";
                $ids[] = $d['id'];
            }
            return ['reply' => $reply, 'related' => $ids];
        }

        // 6. Citas / Contacto
        if (str_contains($q, 'cita') || str_contains($q, 'contacto') || str_contains($q, 'horario') || str_contains($q, 'asesor')) {
            return [
                'reply' => "¡Con gusto! Puedes agendar una cita directamente desde la ficha de cualquier propiedad seleccionando 'Agendar Cita', o mediante la sección 'Asesoría' en la página de inicio.",
                'related' => []
            ];
        }

        // Respuesta cordial por defecto
        return [
            'reply' => "¡Hola! Con gusto puedo ayudarte. Puedes consultarme sobre:\n• 🏡 Casas o departamentos disponibles (ej. *'casas en Monterrey'* o *'rentas en CDMX'*).\n• 📐 Terrenos y lotes para inversión.\n• 🕶️ Recorridos virtuales 3D.\n• 💳 Opciones de crédito y financiamiento.\n\n¿Qué tipo de propiedad estás buscando hoy?",
            'related' => []
        ];
    }
}
