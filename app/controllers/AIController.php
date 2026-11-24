<?php
/**
 * Controlador de Inteligencia Artificial (AIController)
 * INFONATEC Inmobiliaria
 */

require_once __DIR__ . '/../config/ai_config.php';

class AIController extends Controller {

    private const CHAT_HISTORY_LIMIT = 12;

    /**
     * Endpoint para procesar mensajes del chat
     */
    public function chat(): void {
        $raw = file_get_contents('php://input');
        $input = json_decode((string) $raw, true);
        if (!is_array($input)) {
            $input = !empty($_POST) ? $_POST : [];
        }

        Security::requireCsrf($input);

        $userMessage = trim((string) ($input['message'] ?? ''));
        if ($userMessage === '') {
            $this->json(['error' => 'Mensaje vacío'], 400);
        }

        $propiedadModel = new Propiedad();
        $properties = $propiedadModel->getAll();

        $history = $this->getChatHistory();
        $history[] = ['role' => 'user', 'content' => $userMessage];
        $this->saveChatHistory($history);

        $apiKey = defined('AI_API_KEY') ? trim(AI_API_KEY) : '';
        if ($apiKey !== '' && AI_PROVIDER !== 'local') {
            $llmResponse = $this->callGeminiApi($userMessage, $properties, $apiKey, $history);
            if ($llmResponse !== null) {
                $this->saveChatHistory(array_merge($history, [
                    ['role' => 'model', 'content' => $llmResponse],
                ]));

                $this->json([
                    'status' => 'success',
                    'reply'  => $llmResponse,
                    'engine' => 'gemini',
                ]);
            }
        }

        $localResponse = $this->localExpertEngine($userMessage, $properties);
        $replyText = $localResponse['reply'];

        $this->saveChatHistory(array_merge($history, [
            ['role' => 'model', 'content' => $replyText],
        ]));

        $this->json([
            'status'  => 'success',
            'reply'   => $replyText,
            'engine'  => 'local_expert',
            'related' => $localResponse['related'] ?? [],
        ]);
    }

    /**
     * Endpoint para procesar mensajes del chat con respuesta en streaming (SSE)
     */
    public function streamChat(): void {
        $raw = file_get_contents('php://input');
        $input = json_decode((string) $raw, true);
        if (!is_array($input)) {
            $input = !empty($_POST) ? $_POST : [];
        }

        Security::requireCsrf($input);

        $userMessage = trim((string) ($input['message'] ?? ''));
        if ($userMessage === '') {
            $this->json(['error' => 'Mensaje vacío'], 400);
        }

        $propiedadModel = new Propiedad();
        $properties = $propiedadModel->getAll();

        $history = $this->getChatHistory();
        $history[] = ['role' => 'user', 'content' => $userMessage];

        $apiKey = defined('AI_API_KEY') ? trim(AI_API_KEY) : '';
        $reply = null;
        $engine = 'local_expert';
        $related = [];

        if ($apiKey !== '' && AI_PROVIDER !== 'local') {
            $reply = $this->callGeminiApi($userMessage, $properties, $apiKey, $history);
            if ($reply !== null) {
                $engine = 'gemini';
            }
        }
        if ($reply === null) {
            $localResponse = $this->localExpertEngine($userMessage, $properties);
            $reply = $localResponse['reply'];
            $engine = 'local_expert';
            $related = $localResponse['related'] ?? [];
        }

        $history[] = ['role' => 'model', 'content' => $reply];
        $this->saveChatHistory($history);

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        echo "retry: 3000\n\n";
        echo "event: meta\n";
        echo 'data: ' . json_encode(['engine' => $engine, 'related' => $related], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();

        // Dividir preservando espacios, saltos de línea y caracteres UTF-8.
        $chunks = mb_str_split($reply, 8, 'UTF-8');

        foreach ($chunks as $chunk) {
            echo "event: token\n";
            echo 'data: ' . json_encode(['token' => $chunk], JSON_UNESCAPED_UNICODE) . "\n\n";
            flush();
            usleep(12000);
        }

        echo "event: done\n";
        echo 'data: ' . json_encode(['engine' => $engine, 'related' => $related], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
        exit;
    }

    /**
     * Endpoint para redactar descripciones de inmuebles en Vender
     */
    public function generateDescription(): void {
        $raw = file_get_contents('php://input');
        $input = json_decode((string) $raw, true);
        if (!is_array($input)) {
            $input = !empty($_POST) ? $_POST : [];
        }

        Security::requireCsrf($input);

        $type      = trim((string) ($input['type'] ?? 'Propiedad'));
        $operation = trim((string) ($input['operation'] ?? 'Venta'));
        $title     = trim((string) ($input['title'] ?? ''));
        $price     = trim((string) ($input['price'] ?? ''));
        $location  = trim((string) ($input['location'] ?? ''));
        $city      = trim((string) ($input['city'] ?? ''));
        $state     = trim((string) ($input['state'] ?? ''));
        $rooms     = trim((string) ($input['bedrooms'] ?? ''));
        $baths     = trim((string) ($input['bathrooms'] ?? ''));
        $area      = trim((string) ($input['area'] ?? ''));
        $details   = trim((string) ($input['details'] ?? ''));

        $promptContext = "Tipo: {$type}, Operación: {$operation}, Título base: {$title}, Precio: {$price}, Ubicación: {$location}, {$city}, {$state}, Superficie: {$area} m², Recámaras: {$rooms}, Baños: {$baths}. Notas adicionales: {$details}.";

        $apiKey = defined('AI_API_KEY') ? trim(AI_API_KEY) : '';
        if ($apiKey !== '' && AI_PROVIDER !== 'local') {
            $prompt = "Actúa como un experto copywriter inmobiliario mexicano. Redacta una descripción comercial atractiva, clara y veraz para anunciar el siguiente inmueble en {$operation}:\n"
                    . $promptContext . "\n"
                    . "Incluye:\n"
                    . "1. Un título o frase de apertura de alto impacto.\n"
                    . "2. Un párrafo descriptivo destacando la ubicación, distribución y estilo de vida.\n"
                    . "3. Lista de características clave con viñetas (•).\n"
                    . "4. Invitación breve a agendar visita.\n"
                    . "Sé directo y no agregues preámbulos.";

            $llmResult = $this->callGeminiTextOnly($prompt, $apiKey);
            if (!empty($llmResult)) {
                $this->json([
                    'status'      => 'success',
                    'description' => $llmResult,
                    'engine'      => 'gemini',
                ]);
            }
        }

        $locParts = array_values(array_filter([$location, $city, $state], fn($v) => $v !== ''));
        $locStr = !empty($locParts) ? implode(', ', $locParts) : 'zona privilegiada';
        $typeLabel = ucfirst(strtolower($type !== '' ? $type : 'propiedad'));
        $opLabel = strtolower($operation) === 'renta' ? 'renta' : 'venta';

        $desc = ($title !== '' ? "{$title} — " : '') . "Excelente oportunidad en {$opLabel}. Presentamos este inmueble tipo {$typeLabel} ubicado en {$locStr}, diseñado para brindar confort, funcionalidad y alta plusvalía.\n\n";
        $desc .= "Sus espacios ofrecen iluminación natural, distribución práctica y entorno seguro cercano a vialidades principales, comercios y servicios.\n\n";
        $desc .= "Características destacadas:\n";
        if ($area !== '') {
            $desc .= "• {$area} m² de superficie\n";
        }
        if ($rooms !== '' && strtolower($type) !== 'terreno') {
            $desc .= "• {$rooms} habitaciones amplias y bien ventiladas\n";
        }
        if ($baths !== '' && strtolower($type) !== 'terreno') {
            $desc .= "• {$baths} baños completos\n";
        }
        if ($details !== '') {
            $desc .= "• " . str_replace("\n", "\n• ", $details) . "\n";
        }
        $desc .= "• Documentación verificable y acompañamiento asesor INFONATEC\n\n";
        $desc .= "Agenda una visita o solicita más información directamente desde la ficha.";

        $this->json([
            'status'      => 'success',
            'description' => $desc,
            'engine'      => 'local_template',
        ]);
    }

    /**
     * Estado del servicio de IA
     */
    public function status(): void {
        $apiKey = defined('AI_API_KEY') ? trim(AI_API_KEY) : '';
        $hasApiKey = $apiKey !== '';
        $this->json([
            'ready'               => true,
            'provider'            => $hasApiKey && AI_PROVIDER !== 'local' ? AI_PROVIDER : 'local_expert',
            'configured_provider' => AI_PROVIDER,
            'model'               => AI_MODEL,
            'has_key'             => $hasApiKey,
        ]);
    }

    private function getChatHistory(): array {
        $history = $_SESSION['ai_chat_history'] ?? [];
        if (!is_array($history)) {
            return [];
        }

        $clean = [];
        foreach ($history as $message) {
            if (!is_array($message)) {
                continue;
            }
            $role = in_array($message['role'] ?? '', ['assistant', 'model'], true) ? 'model' : 'user';
            $content = trim((string) ($message['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $clean[] = ['role' => $role, 'content' => $content];
        }

        return array_slice($clean, -self::CHAT_HISTORY_LIMIT);
    }

    private function saveChatHistory(array $history): void {
        $_SESSION['ai_chat_history'] = array_slice($history, -self::CHAT_HISTORY_LIMIT);
    }

    private function callGeminiApi(string $userMsg, array $properties, string $apiKey, array $history = []): ?string {
        $catalogSummary = "CATÁLOGO DE PROPIEDADES EN INFONATEC:\n";
        foreach ($properties as $p) {
            $tour = !empty($p['virtualTour']['url']) ? ' (Cuenta con Recorrido Virtual 360°)' : '';
            $catalogSummary .= "- ID {$p['id']}: '{$p['title']}' ({$p['type_label']} en {$p['full_location']}) - {$p['operation_label']} - Precio: {$p['price']}{$tour}. Detalles: " . implode(', ', $p['details'] ?? []) . ".\n";
        }

        $systemInstruction = AI_SYSTEM_PROMPT . "\n\n" . $catalogSummary;

        $contents = [];
        foreach ($history as $message) {
            $contents[] = [
                'role'  => $message['role'],
                'parts' => [['text' => $message['content']]],
            ];
        }

        $geminiContents = [];
        $lastRole = null;
        foreach ($contents as $content) {
            if ($content['role'] === $lastRole) {
                continue;
            }
            $geminiContents[] = $content;
            $lastRole = $content['role'];
        }

        if (empty($geminiContents) || $geminiContents[0]['role'] !== 'user') {
            $geminiContents = [[
                'role'  => 'user',
                'parts' => [['text' => $userMsg]],
            ]];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . AI_MODEL . ':generateContent?key=' . urlencode($apiKey);
        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'contents' => $geminiContents,
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 380,
            ],
        ];

        return $this->executeCurlPost($url, $payload);
    }

    private function callGeminiTextOnly(string $prompt, string $apiKey): ?string {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . AI_MODEL . ':generateContent?key=' . urlencode($apiKey);
        $payload = [
            'contents' => [[
                'role'  => 'user',
                'parts' => [['text' => $prompt]],
            ]],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 450,
            ],
        ];
        return $this->executeCurlPost($url, $payload);
    }

    private function executeCurlPost(string $url, array $payload): ?string {
        if (!function_exists('curl_init')) {
            return null;
        }

        $verifySsl = defined('APP_ENV') && APP_ENV === 'production';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, AI_TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);

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

    private function normalizeSearchText(string $text): string {
        $lower = mb_strtolower($text, 'UTF-8');
        return strtr($lower, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    private function localExpertEngine(string $query, array $properties): array {
        $q = $this->normalizeSearchText($query);

        if (preg_match('/^(hola|hey|saludos|buenas|buenos dias|buenas tardes|buenas noches)\b/', $q) && mb_strlen(trim($query)) <= 60) {
            return [
                'reply' => "¡Hola! 😊 Soy el asistente virtual de **INFONATEC**. Estoy aquí para ayudarte a encontrar el inmueble ideal o resolver dudas sobre compra, renta, recorridos 360° o créditos.\n\nCuéntame, ¿qué estás buscando hoy? Por ejemplo: casas en Monterrey, terrenos en Mérida o departamentos en CDMX.",
                'related' => [],
            ];
        }

        if (str_contains($q, 'gracias') || str_contains($q, 'thank')) {
            return [
                'reply' => "¡Con mucho gusto! 😊 Si necesitas revisar alguna ficha o agendar una visita, aquí estoy para apoyarte.",
                'related' => [],
            ];
        }

        if (str_contains($q, '3d') || str_contains($q, '360') || str_contains($q, 'recorrido') || str_contains($q, 'tour') || str_contains($q, 'virtual')) {
            if (str_contains($q, 'industrial') || str_contains($q, 'terreno')) {
                return [
                    'reply' => "¡Sí! Contamos con **Recorrido Virtual 360° Industrial** (Querétaro y Apodaca), donde puedes explorar la vista aérea, plataforma nivelada, subestación eléctrica 115 kV, patio de maniobras y caseta de control, además de consultar una galería estática de bajo consumo.",
                    'related' => [6, 10],
                ];
            }
            $tourProps = array_values(array_filter($properties, fn($p) => !empty($p['virtualTour']['url'])));
            $ids = array_map(fn($p) => (int) $p['id'], array_slice($tourProps, 0, 3));
            return [
                'reply' => "¡Sí! Contamos con recorridos virtuales 360° en **Residencia Las Cumbres** y en los **Terrenos Industriales**, incluyendo controles de zoom, flechas accesibles y galería estática alternativa.",
                'related' => !empty($ids) ? $ids : [2, 6],
            ];
        }

        if (str_contains($q, 'credito') || str_contains($q, 'infonavit') || str_contains($q, 'fovissste') || str_contains($q, 'banco') || str_contains($q, 'financiamiento')) {
            return [
                'reply' => "En **INFONATEC** trabajamos distintas opciones según el tipo de inmueble:\n• Compra residencial: Contado, transferencia SPEI, crédito bancario, INFONAVIT y FOVISSSTE.\n• Terrenos: Contado, crédito para terreno y esquemas de financiamiento.\n• Renta: Depósito en garantía, SPEI y póliza jurídica.\n\nPuedes abrir cualquier ficha sin registrarte para ver sus opciones específicas.",
                'related' => [],
            ];
        }

        if (str_contains($q, 'vender') || str_contains($q, 'publicar') || str_contains($q, 'anunciar')) {
            return [
                'reply' => "Para publicar tu inmueble, entra a **Vender** en el menú superior. Nuestro formulario en 4 pasos guarda tu borrador automáticamente, valida fotografías y te permite generar y aprobar una descripción comercial con IA.",
                'related' => [],
            ];
        }

        $locationTerms = [
            'monterrey', 'san pedro', 'apodaca', 'nl', 'nuevo leon',
            'merida', 'yucatan', 'queretaro', 'cancun',
            'cdmx', 'ciudad de mexico', 'polanco', 'condesa', 'roma', 'santa fe', 'cuajimalpa', 'cuauhtemoc',
        ];
        foreach ($locationTerms as $term) {
            if (str_contains($q, $term)) {
                $matches = array_filter($properties, function ($p) use ($term) {
                    $haystack = $this->normalizeSearchText(($p['location'] ?? '') . ' ' . ($p['full_location'] ?? '') . ' ' . ($p['title'] ?? ''));
                    return str_contains($haystack, $term);
                });
                if (!empty($matches)) {
                    $reply = "Encontramos estas opciones disponibles en esa zona:\n";
                    $ids = [];
                    foreach ($matches as $m) {
                        $reply .= "• **{$m['title']}** ({$m['type_label']}): {$m['price']} — {$m['full_location']}\n";
                        $ids[] = (int) $m['id'];
                    }
                    $reply .= "\nHaz clic en **Ver Ficha** para consultar galería, situación legal y agendar visita.";
                    return ['reply' => $reply, 'related' => array_slice($ids, 0, 4)];
                }
            }
        }

        if (str_contains($q, 'terreno') || str_contains($q, 'lote')) {
            $terrenos = array_filter($properties, fn($p) => $p['type'] === 'terreno');
            $reply = "Tenemos estas opciones en terrenos y lotes:\n";
            $ids = [];
            foreach ($terrenos as $t) {
                $reply .= "• **{$t['title']}** ({$t['location']}) — {$t['price']}\n";
                $ids[] = (int) $t['id'];
            }
            return ['reply' => $reply, 'related' => array_slice($ids, 0, 4)];
        }

        if (str_contains($q, 'casa') || str_contains($q, 'residencia') || str_contains($q, 'villa')) {
            $casas = array_filter($properties, fn($p) => $p['type'] === 'casa');
            $reply = "Aquí tienes nuestras casas disponibles:\n";
            $ids = [];
            foreach ($casas as $c) {
                $reply .= "• **{$c['title']}** ({$c['location']}) — {$c['price']}\n";
                $ids[] = (int) $c['id'];
            }
            return ['reply' => $reply, 'related' => array_slice($ids, 0, 4)];
        }

        if (str_contains($q, 'renta') || str_contains($q, 'rentar') || str_contains($q, 'departamento') || str_contains($q, 'loft') || str_contains($q, 'penthouse') || str_contains($q, 'estudio')) {
            $deptos = array_filter($properties, fn($p) => $p['operation'] === 'rentar' || $p['type'] === 'departamento');
            $reply = "Opciones en renta y departamentos disponibles:\n";
            $ids = [];
            foreach ($deptos as $d) {
                $reply .= "• **{$d['title']}** ({$d['location']}) — {$d['price']}\n";
                $ids[] = (int) $d['id'];
            }
            return ['reply' => $reply, 'related' => array_slice($ids, 0, 4)];
        }

        if (str_contains($q, 'cita') || str_contains($q, 'contacto') || str_contains($q, 'horario') || str_contains($q, 'asesor')) {
            return [
                'reply' => "Puedes agendar una cita directamente desde la ficha de cualquier inmueble con el botón **Agendar Cita** o solicitar atención en la sección **Asesor**, sin necesidad de registrarte previamente.",
                'related' => [],
            ];
        }

        return [
            'reply' => "Puedo ayudarte a encontrar el inmueble adecuado. Indícame si buscas **compra** o **renta**, la **ciudad/zona** y el tipo de propiedad (**casa, departamento o terreno**).\n\nTambién puedes explorar:\n• 🏡 Casas, departamentos y terrenos con galería completa.\n• 🕶️ Recorridos virtuales 360° y galería estática accesible.\n• 💳 Información legal y opciones de pago abiertas en cada ficha.",
            'related' => [],
        ];
    }
}
