<?php
/**
 * Configuración del Módulo de Inteligencia Artificial
 * INFONATEC Inmobiliaria
 */

// Proveedor predeterminado: 'gemini', 'openai' o 'local'
if (!defined('AI_PROVIDER')) {
    define('AI_PROVIDER', 'gemini');
}

// Clave de API para Gemini o proveedor configurado (puede configurarse aquí o como variable de entorno)
if (!defined('AI_API_KEY')) {
    define('AI_API_KEY', getenv('GEMINI_API_KEY') ?: (getenv('AI_API_KEY') ?: ''));
}

// Modelo a utilizar (Gemini 1.5 Flash es el más veloz y ligero)
if (!defined('AI_MODEL')) {
    define('AI_MODEL', 'gemini-1.5-flash');
}

// Tiempo de espera máximo en segundos para evitar que la interfaz se quede esperando
if (!defined('AI_TIMEOUT_SECONDS')) {
    define('AI_TIMEOUT_SECONDS', 6);
}

// Mensaje del sistema con la personalidad y rol del Asistente
if (!defined('AI_SYSTEM_PROMPT')) {
    define('AI_SYSTEM_PROMPT', 'Eres el Asistente Virtual Inteligente de "INFONATEC Inmobiliaria", una prestigiosa plataforma de bienes raíces en México. 
Tu labor es asesorar cordialmente a compradores, inquilinos y propietarios sobre las propiedades disponibles, requisitos de compra/renta, opciones de crédito (Infonavit, Bancario) y recorridos virtuales interactivos 3D.
Sé conciso, profesional, empático y utiliza formato claro con viñetas cuando sea pertinente.
Si el usuario pregunta por propiedades específicas, recomiéndale las opciones reales de nuestro catálogo con sus precios y características. Si la propiedad cuenta con Recorrido 3D, invítalo a conocerlo.
Habla en español de México.');
}
