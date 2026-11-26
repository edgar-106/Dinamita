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

        // Preguntas sobre una propiedad concreta del catálogo (precio, ubicación, detalles...).
        $mentioned = $this->findPropertyMention($q, $properties);
        if ($mentioned !== null) {
            return $this->describeProperty($mentioned, $this->detectPropertyIntent($q));
        }

        // Recomendaciones filtradas por operación, tipo, zona y presupuesto.
        $recommendation = $this->recommendProperties($q, $properties);
        if ($recommendation !== null) {
            return $recommendation;
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

    /**
     * Devuelve la propiedad cuyo título aparece completo en la consulta.
     */
    private function hasFullTitleMention(string $q, array $properties): ?array {
        foreach ($properties as $property) {
            $title = $this->normalizeSearchText((string) ($property['title'] ?? ''));
            if ($title !== '' && str_contains($q, $title)) {
                return $property;
            }
        }
        return null;
    }

    /**
     * Detecta si el usuario pide recomendaciones u opciones.
     */
    private function hasRecommendationTrigger(string $q): bool {
        $triggers = [
            'recomienda', 'recomiendame', 'recomiendan', 'recomendar', 'recomendacion',
            'sugiere', 'sugiereme', 'sugerencia', 'busco', 'buscando', 'quiero',
            'necesito', 'me interesa', 'me gustaria', 'opciones', 'alternativas',
            'presupuesto', 'conviene', 'asesorame', 'ayudame a encontrar',
        ];
        foreach ($triggers as $trigger) {
            if (str_contains($q, $trigger)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Identifica la propiedad aludida en la pregunta del usuario.
     */
    private function findPropertyMention(string $q, array $properties): ?array {
        $fullTitle = $this->hasFullTitleMention($q, $properties);
        if ($fullTitle !== null) {
            return $fullTitle;
        }

        $typeWords = ['casa', 'residencia', 'terreno', 'lote', 'departamento', 'villa'];
        $locationWords = [
            'monterrey', 'apodaca', 'merida', 'queretaro', 'cancun', 'cdmx', 'polanco',
            'condesa', 'roma', 'santa', 'fe', 'cuajimalpa', 'cuauhtemoc', 'yucatan',
            'qroo', 'norte', 'valle',
        ];

        $tokenFrequency = [];
        $tokensByProperty = [];
        foreach ($properties as $index => $property) {
            $title = $this->normalizeSearchText((string) ($property['title'] ?? ''));
            $words = array_values(array_unique(array_filter(
                preg_split('/[^a-z0-9]+/', $title) ?: [],
                fn($word) => $word !== '' && mb_strlen($word) >= 4
            )));
            $tokensByProperty[$index] = $words;
            foreach ($words as $word) {
                $tokenFrequency[$word] = ($tokenFrequency[$word] ?? 0) + 1;
            }
        }

        $best = null;
        $bestScore = 0;
        $bestDistinctiveHits = 0;
        $tie = false;

        foreach ($properties as $index => $property) {
            $score = 0;
            $distinctiveHits = 0;
            foreach ($tokensByProperty[$index] ?? [] as $word) {
                if (!str_contains($q, $word)) {
                    continue;
                }
                if (in_array($word, $typeWords, true) || in_array($word, $locationWords, true)) {
                    $score += 40;
                } elseif (($tokenFrequency[$word] ?? 1) === 1) {
                    $score += 100;
                    $distinctiveHits++;
                } else {
                    $score += 25;
                }
            }

            $type = (string) ($property['type'] ?? '');
            if ($type === 'terreno' && (str_contains($q, 'terreno') || str_contains($q, 'lote') || str_contains($q, 'predio'))) {
                $score += 30;
            } elseif ($type === 'casa' && (str_contains($q, 'casa') || str_contains($q, 'residencia') || str_contains($q, 'villa'))) {
                $score += 30;
            } elseif ($type === 'departamento' && (str_contains($q, 'departamento') || str_contains($q, 'depto') || str_contains($q, 'depa') || str_contains($q, 'loft') || str_contains($q, 'penthouse') || str_contains($q, 'estudio'))) {
                $score += 30;
            }

            if ($score > $bestScore) {
                $best = $property;
                $bestScore = $score;
                $bestDistinctiveHits = $distinctiveHits;
                $tie = false;
            } elseif ($score > 0 && $score === $bestScore) {
                $tie = true;
            }
        }

        if ($best === null || $bestScore < 100 || $tie) {
            return null;
        }
        if ($bestDistinctiveHits === 0 && $this->hasRecommendationTrigger($q)) {
            return null;
        }

        return $best;
    }

    /**
     * Determina qué aspecto de la propiedad le interesa al usuario.
     */
    private function detectPropertyIntent(string $q): string {
        $intents = [
            'precio' => ['precio', 'cuesta', 'costo', 'cuanto', 'valor', 'vale', 'barato', 'caro', 'cotizacion'],
            'ubicacion' => ['donde', 'ubicacion', 'ubicad', 'zona', 'direccion', 'colonia', 'ciudad', 'llegar', 'mapa'],
            'disponibilidad' => ['disponible', 'sigue', 'vendida', 'vendido', 'ocupada', 'ocupado', 'rentada', 'rentado', 'estatus', 'todavia'],
            'recorrido' => ['recorrido', 'tour', '360', 'virtual', 'fotos', 'video', 'galeria', 'imagenes', 'ver'],
            'legal' => ['legal', 'escritura', 'gravamen', 'predial', 'document', 'papel', 'rpp', 'notaria', 'fideicomiso'],
            'pago' => ['pago', 'credito', 'financiamiento', 'infonavit', 'fovissste', 'mensualidad', 'enganche', 'banco', 'contado'],
            'renta' => ['renta', 'rentar', 'arrendar', 'alquiler', 'mensual', 'deposito'],
            'venta' => ['venta', 'comprar', 'compra', 'adquirir'],
            'detalles' => ['habitacion', 'recamara', 'bano', 'banos', 'metro', 'm2', 'superficie', 'amenidad', 'caracteristica', 'distribucion', 'terraza', 'alberca', 'cochera', 'estacionamiento'],
        ];

        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($q, $keyword)) {
                    return $intent;
                }
            }
        }

        return 'resumen';
    }

    /**
     * Construye una respuesta concreta sobre una propiedad del catálogo.
     */
    private function describeProperty(array $property, string $intent): array {
        $id = (int) ($property['id'] ?? 0);
        $title = (string) ($property['title'] ?? 'esta propiedad');
        $price = (string) ($property['price'] ?? '');
        $location = (string) ($property['full_location'] ?? $property['location'] ?? '');
        $typeLabel = (string) ($property['type_label'] ?? '');
        $operationLabel = (string) ($property['operation_label'] ?? '');
        $favorites = $this->currentFavoriteIds();
        $isFavorite = in_array($id, $favorites, true);
        $favoriteNote = $isFavorite ? "\n\n⭐ Ya la tienes guardada en **Favoritos**." : '';

        switch ($intent) {
            case 'precio':
                $reply = "**{$title}** tiene un precio de **{$price}**";
                $reply .= $property['operation'] === 'rentar'
                    ? " (renta mensual)."
                    : " (operación: {$operationLabel}).";
                if (!empty($property['payment_options'])) {
                    $reply .= "\n\n💳 Opciones de pago:\n" . $this->bulletList($property['payment_options']);
                }
                $reply .= "\n\n¿Quieres que revise si se ajusta a tu presupuesto o te muestre opciones similares?";
                break;

            case 'ubicacion':
                $reply = "**{$title}** se ubica en **{$location}**.";
                if (!empty($property['description'])) {
                    $reply .= "\n\n📍 " . $this->shortenText((string) $property['description'], 220);
                }
                $reply .= "\n\nPuedes abrir su ficha para ver la zona, la galería y agendar una visita.";
                break;

            case 'disponibilidad':
                $reply = "Sí, **{$title}** aparece como **disponible** en nuestro catálogo vigente ({$operationLabel}, {$typeLabel}).";
                $reply .= "\n\nSi quieres asegurarla, te recomiendo agendar una visita o contactar a un asesor desde su ficha.";
                break;

            case 'recorrido':
                if (!empty($property['virtualTour'])) {
                    $tourTitle = (string) ($property['virtualTour']['title'] ?? 'Recorrido virtual');
                    $reply = "¡Claro! **{$title}** cuenta con **{$tourTitle}**.";
                    if (!empty($property['virtualTour']['subtitle'])) {
                        $reply .= "\n\n🕶️ " . $property['virtualTour']['subtitle'];
                    }
                    $reply .= "\n\nAbre la ficha y pulsa **Recorrido 360°** para explorarlo.";
                } else {
                    $reply = "**{$title}** no tiene recorrido virtual publicado por ahora, pero su ficha incluye **galería fotográfica completa**.";
                    $reply .= "\n\n¿Quieres que te recomiende propiedades con recorrido 360°?";
                }
                break;

            case 'legal':
                $reply = "Situación legal de **{$title}**:\n" . $this->bulletList($property['legal_details'] ?? []);
                $reply .= "\n\nToda la documentación se revisa con notaría antes de cerrar la operación.";
                break;

            case 'pago':
                $reply = "Para **{$title}** ({$operationLabel}) estas son las opciones de pago:\n" . $this->bulletList($property['payment_options'] ?? []);
                $reply .= "\n\nPrecio: **{$price}**. Puedo ayudarte a comparar contra otras propiedades si me dices tu presupuesto.";
                break;

            case 'renta':
            case 'venta':
                $reply = "**{$title}** está en **{$operationLabel}** con un precio de **{$price}**.";
                $reply .= "\n\n• Tipo: {$typeLabel}\n• Ubicación: {$location}";
                if (!empty($property['details'])) {
                    $reply .= "\n• " . implode("\n• ", array_slice($property['details'], 0, 5));
                }
                $reply .= "\n\n¿Te gustaría conocer las condiciones legales o agendar una visita?";
                break;

            case 'detalles':
                $reply = "Estos son los detalles de **{$title}**:\n";
                if (!empty($property['stats'])) {
                    $reply .= '• ' . implode(' · ', $property['stats']) . "\n";
                }
                $reply .= '• Tipo: ' . $typeLabel . "\n• Operación: " . $operationLabel . "\n• Ubicación: " . $location;
                if (!empty($property['description'])) {
                    $reply .= "\n\n" . $this->shortenText((string) $property['description'], 260);
                }
                break;

            default:
                $reply = "**{$title}** es un(a) **{$typeLabel}** en **{$operationLabel}** por **{$price}**.";
                if (!empty($property['stats'])) {
                    $reply .= "\n\n📐 " . implode(' · ', $property['stats']);
                }
                $reply .= "\n📍 " . $location;
                if (!empty($property['description'])) {
                    $reply .= "\n\n" . $this->shortenText((string) $property['description'], 240);
                }
                $reply .= "\n\nPuedo darte el precio, la ubicación, los detalles legales, las formas de pago o el recorrido virtual. ¿Qué te interesa?";
                break;
        }

        return [
            'reply' => $reply . $favoriteNote,
            'related' => $id > 0 ? [$id] : [],
        ];
    }

    /**
     * Genera recomendaciones filtradas por operación, tipo, zona y presupuesto.
     */
    private function recommendProperties(string $q, array $properties): ?array {
        if ($this->hasFullTitleMention($q, $properties)) {
            return null;
        }
        if (!$this->hasRecommendationTrigger($q)) {
            return null;
        }

        $operation = null;
        if (preg_match('/\b(renta|rentar|alquilar|alquiler|arrendar|mensual)\b/', $q)) {
            $operation = 'rentar';
        } elseif (preg_match('/\b(comprar|compra|venta|adquirir|invertir)\b/', $q)) {
            $operation = 'comprar';
        }

        $type = null;
        if (preg_match('/\b(terreno|lote|predio|macrolote)s?\b/', $q)) {
            $type = 'terreno';
        } elseif (preg_match('/\b(casa|residencia|villa)s?\b/', $q)) {
            $type = 'casa';
        } elseif (preg_match('/\b(departamento|depto|depa|loft|penthouse|estudio)s?\b/', $q)) {
            $type = 'departamento';
        }

        $zones = [
            'san pedro', 'monterrey', 'apodaca', 'merida', 'yucatan', 'queretaro',
            'cancun', 'cdmx', 'ciudad de mexico', 'polanco', 'condesa', 'roma norte',
            'roma', 'santa fe', 'cuajimalpa', 'cuauhtemoc',
        ];
        $zoneLabels = [
            'san pedro' => 'San Pedro', 'monterrey' => 'Monterrey', 'apodaca' => 'Apodaca',
            'merida' => 'Mérida', 'yucatan' => 'Yucatán', 'queretaro' => 'Querétaro',
            'cancun' => 'Cancún', 'cdmx' => 'CDMX', 'ciudad de mexico' => 'CDMX',
            'polanco' => 'Polanco', 'condesa' => 'Condesa', 'roma norte' => 'Roma Norte',
            'roma' => 'Roma', 'santa fe' => 'Santa Fe', 'cuajimalpa' => 'Cuajimalpa',
            'cuauhtemoc' => 'Cuauhtémoc',
        ];
        $zoneHits = [];
        foreach ($zones as $zone) {
            if (str_contains($q, $zone)) {
                $zoneHits[] = $zone;
            }
        }

        $budget = $this->parseBudget($q);

        $featured = ($operation === null && $type === null && empty($zoneHits) && $budget === null);

        $matchesZone = function (array $property) use ($zoneHits): bool {
            if (empty($zoneHits)) {
                return true;
            }
            $haystack = $this->normalizeSearchText(
                ($property['location'] ?? '') . ' ' . ($property['full_location'] ?? '') . ' ' . ($property['title'] ?? '')
            );
            foreach ($zoneHits as $zone) {
                if (str_contains($haystack, $zone)) {
                    return true;
                }
            }
            return false;
        };

        $baseFilter = function (array $property) use ($operation, $type, $matchesZone): bool {
            if ($operation !== null && ($property['operation'] ?? '') !== $operation) {
                return false;
            }
            if ($type !== null && ($property['type'] ?? '') !== $type) {
                return false;
            }
            return $matchesZone($property);
        };

        $matchesBudget = function (array $property) use ($budget): bool {
            if ($budget === null) {
                return true;
            }
            $price = (float) ($property['raw_price'] ?? 0);
            if (isset($budget['min']) && $price < $budget['min']) {
                return false;
            }
            if (isset($budget['max']) && $price > $budget['max']) {
                return false;
            }
            return true;
        };

        $candidates = array_values(array_filter($properties, $baseFilter));
        $withinBudget = array_values(array_filter($candidates, $matchesBudget));

        $adjusted = null;
        $favorites = $this->currentFavoriteIds();
        if ($featured) {
            $withinBudget = $this->featuredProperties($properties, $favorites);
        } elseif (empty($withinBudget) && $budget !== null) {
            $adjusted = 'presupuesto';
            $withinBudget = $candidates;
        }
        if (!$featured && empty($withinBudget) && !empty($zoneHits)) {
            $adjusted = 'zona';
            $withinBudget = array_values(array_filter($properties, function (array $property) use ($operation, $type): bool {
                if ($operation !== null && ($property['operation'] ?? '') !== $operation) {
                    return false;
                }
                return $type === null || ($property['type'] ?? '') === $type;
            }));
        }
        if (empty($withinBudget)) {
            $adjusted = 'filtros';
            $withinBudget = $properties;
        }

        usort($withinBudget, function (array $a, array $b) use ($favorites, $adjusted, $budget): int {
            $aFav = in_array((int) ($a['id'] ?? 0), $favorites, true);
            $bFav = in_array((int) ($b['id'] ?? 0), $favorites, true);
            if ($aFav !== $bFav) {
                return $aFav ? -1 : 1;
            }

            if ($adjusted === 'presupuesto' && $budget !== null) {
                $distance = function (array $property) use ($budget): float {
                    $price = (float) ($property['raw_price'] ?? 0);
                    if (isset($budget['min']) && $price < $budget['min']) {
                        return $budget['min'] - $price;
                    }
                    if (isset($budget['max']) && $price > $budget['max']) {
                        return $price - $budget['max'];
                    }
                    return 0.0;
                };
                $diff = $distance($a) <=> $distance($b);
                if ($diff !== 0) {
                    return $diff;
                }
            }

            return ((float) ($a['raw_price'] ?? 0)) <=> ((float) ($b['raw_price'] ?? 0));
        });

        $top = array_slice($withinBudget, 0, 4);

        $criteria = [];
        if ($type !== null) {
            $criteria[] = $type === 'terreno' ? 'terrenos' : ($type === 'casa' ? 'casas' : 'departamentos');
        }
        if ($operation !== null) {
            $criteria[] = $operation === 'rentar' ? 'en renta' : 'en venta';
        }
        if (!empty($zoneHits)) {
            $criteria[] = 'en ' . ($zoneLabels[$zoneHits[0]] ?? mb_convert_case($zoneHits[0], MB_CASE_TITLE, 'UTF-8'));
        }
        if ($budget !== null && isset($budget['max'])) {
            $suffix = $operation === 'rentar' ? ' al mes' : '';
            if (isset($budget['min'])) {
                $criteria[] = 'entre $' . number_format((float) $budget['min'], 0) . ' y $' . number_format((float) $budget['max'], 0) . $suffix;
            } else {
                $criteria[] = 'hasta $' . number_format((float) $budget['max'], 0) . $suffix;
            }
        }

        $reply = "Claro 😊 ";
        if ($featured) {
            $reply .= "Estas son algunas opciones destacadas de nuestro catálogo:";
        } else {
            $reply .= empty($criteria)
                ? "Estas son las opciones que mejor se ajustan a lo que buscas:"
                : "Buscando **" . implode(' ', $criteria) . "** encontré estas opciones:";
        }

        if ($adjusted === 'presupuesto') {
            $reply .= "\n\n⚠️ No hay coincidencias exactas dentro de ese presupuesto, pero estas son las más cercanas:";
        } elseif ($adjusted === 'zona') {
            $reply .= "\n\n⚠️ No hay opciones en esa zona con esos filtros; te muestro alternativas equivalentes:";
        } elseif ($adjusted === 'filtros') {
            $reply .= "\n\n⚠️ No encontré coincidencias exactas; estas son las opciones disponibles:";
        }

        $reply .= "\n";
        foreach ($top as $property) {
            $reply .= "\n" . $this->propertySummaryLine($property, $favorites);
        }

        $favoriteResults = array_filter($top, fn($property) => in_array((int) ($property['id'] ?? 0), $favorites, true));
        if (!empty($favoriteResults)) {
            $reply .= "\n\n⭐ Incluí propiedades que ya tienes en **Favoritos** para que las compares primero.";
        }

        $pick = $top[0] ?? null;
        if ($pick !== null) {
            $reply .= "\n\n💡 **Mi recomendación:** **{$pick['title']}**";
            $reply .= " por **{$pick['price']}** en {$pick['full_location']}";
            if (!empty($pick['stats'])) {
                $reply .= ' (' . implode(' · ', array_slice($pick['stats'], 0, 3)) . ')';
            }
            $reply .= ". Es la que mejor equilibra precio y características de tu búsqueda.";
        }

        $reply .= "\n\n¿Quieres que te muestre la ficha completa, el recorrido 360° o que ajuste la búsqueda?";

        return [
            'reply' => $reply,
            'related' => array_map(fn($property) => (int) $property['id'], $top),
        ];
    }

    /**
     * Selección curada cuando el usuario pide recomendaciones sin filtros.
     */
    private function featuredProperties(array $properties, array $favoriteIds): array {
        $picked = [];
        $seen = [];

        foreach ($properties as $property) {
            $id = (int) ($property['id'] ?? 0);
            if (in_array($id, $favoriteIds, true) && !isset($seen[$id])) {
                $picked[] = $property;
                $seen[$id] = true;
            }
        }

        $cheapestByKey = [];
        foreach ($properties as $property) {
            $key = ($property['type'] ?? '') . '|' . ($property['operation'] ?? '');
            if (!isset($cheapestByKey[$key]) || ((float) ($property['raw_price'] ?? 0)) < ((float) ($cheapestByKey[$key]['raw_price'] ?? 0))) {
                $cheapestByKey[$key] = $property;
            }
        }

        foreach ($cheapestByKey as $property) {
            if (count($picked) >= 4) {
                break;
            }
            $id = (int) ($property['id'] ?? 0);
            if (!isset($seen[$id])) {
                $picked[] = $property;
                $seen[$id] = true;
            }
        }

        return array_slice($picked, 0, 4);
    }

    /**
     * Extrae un rango de presupuesto en pesos desde el texto del usuario.
     */
    private function parseBudget(string $q): ?array {
        $unitPattern = '(millones|millon|mdp|mill|mil|k)';

        if (preg_match('/(?:entre|de)\s+\$?([0-9][0-9.,]*)\s*' . $unitPattern . '?\s+(?:y|a|hasta)\s+\$?([0-9][0-9.,]*)\s*' . $unitPattern . '?/', $q, $range)) {
            $unitA = $range[2] ?? '';
            $unitB = $range[4] ?? '';
            if ($unitA === '' && $unitB !== '') {
                $unitA = $unitB;
            }
            if ($unitB === '' && $unitA !== '') {
                $unitB = $unitA;
            }
            $min = $this->parseAmount($range[1], $unitA);
            $max = $this->parseAmount($range[3], $unitB);
            if ($min !== null && $max !== null && $max >= $min) {
                return ['min' => $min, 'max' => $max];
            }
        }

        if (preg_match('/(?:hasta|menos de|maximo|no mas de|presupuesto de|presupuesto|tope)\s+\$?([0-9][0-9.,]*)\s*' . $unitPattern . '?/', $q, $maxMatch)) {
            $unit = $maxMatch[2] ?? '';
            $max = $this->parseAmount($maxMatch[1], $unit);
            if ($max !== null && ($unit !== '' || $max >= 10000)) {
                return ['max' => $max];
            }
        }

        if (preg_match('/\$?([0-9][0-9.,]*)\s*' . $unitPattern . '\b/', $q, $unitMatch)) {
            $max = $this->parseAmount($unitMatch[1], $unitMatch[2]);
            if ($max !== null) {
                return ['max' => $max];
            }
        }

        return null;
    }

    /**
     * Convierte una cifra con unidad (millones, mdp, mil, k) a pesos.
     */
    private function parseAmount(string $raw, string $unit = ''): ?float {
        $raw = trim(str_replace(['$', ' '], '', $raw));
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $raw)) {
            $value = (float) str_replace([',', '.'], '', $raw);
        } else {
            $value = (float) str_replace(',', '.', $raw);
        }

        $unit = $this->normalizeSearchText($unit);
        if (in_array($unit, ['millon', 'millones', 'mdp', 'mill'], true)) {
            $value *= 1000000;
        } elseif (in_array($unit, ['mil', 'k'], true)) {
            $value *= 1000;
        }

        return $value > 0 ? $value : null;
    }

    /**
     * Línea resumida de una propiedad para listados de recomendaciones.
     */
    private function propertySummaryLine(array $property, array $favoriteIds): string {
        $isFavorite = in_array((int) ($property['id'] ?? 0), $favoriteIds, true);
        $bullet = $isFavorite ? '• ⭐ ' : '• ';
        return "{$bullet}**{$property['title']}** ({$property['type_label']}, {$property['operation_label']}) — **{$property['price']}** — {$property['full_location']}";
    }

    /**
     * Convierte un arreglo en una lista con viñetas.
     */
    private function bulletList(array $items): string {
        $lines = [];
        foreach ($items as $item) {
            if (is_array($item)) {
                $item = $item['label'] ?? $item['title'] ?? $item['code'] ?? '';
            }
            $item = trim((string) $item);
            if ($item !== '') {
                $lines[] = $item;
            }
        }
        if (empty($lines)) {
            return '• Información disponible en la ficha del inmueble.';
        }
        return '• ' . implode("\n• ", $lines);
    }

    /**
     * Recorta un texto largo sin partir palabras.
     */
    private function shortenText(string $text, int $limit): string {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false) {
            $cut = mb_substr($cut, 0, $space);
        }
        return rtrim($cut, " ,.;:") . '…';
    }

    /**
     * IDs favoritos del usuario en sesión (vacío si no hay sesión).
     */
    private function currentFavoriteIds(): array {
        try {
            $user = Usuario::current();
            if (!$user || empty($user['id'])) {
                return [];
            }
            return (new Propiedad())->getUserFavoriteIds((int) $user['id']);
        } catch (Throwable $e) {
            error_log('Error consultando favoritos en AI: ' . $e->getMessage());
            return [];
        }
    }
}
