<?php
/**
 * ecg-proxy.php
 * Proxy seguro para la API de DeepSeek
 * Coloca este archivo en: /var/www/html/ecg/ (o en innovalab.cl/ecg/)
 *
 * CONFIGURACIÓN:
 *   1. Edita la línea con tu API key de DeepSeek
 *   2. Ajusta ALLOWED_ORIGINS con tu dominio de Moodle
 *   3. Sube el archivo al VPS
 */

// ═══════════════════════════════════════
//  ① CONFIGURA AQUÍ TU API KEY
// ═══════════════════════════════════════
define('DEEPSEEK_API_KEY', 'sk-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'); // ← Pega tu key aquí

// ② Dominios permitidos (tu Moodle y tu VPS)
define('ALLOWED_ORIGINS', [
    'https://asenfovalle.cl',
    'https://innovalab.cl',
    'http://localhost',
    'http://127.0.0.1',
    'null'  // para pruebas locales desde archivo
]);

define('DEEPSEEK_API_URL', 'https://api.deepseek.com/chat/completions');
define('DEEPSEEK_MODEL',   'deepseek-chat');
define('MAX_TOKENS',       900);
define('TIMEOUT_SECS',     30);

// ═══════════════════════════════════════
//  CORS — permite peticiones desde Moodle
// ═══════════════════════════════════════
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, ALLOWED_ORIGINS, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: " . ALLOWED_ORIGINS[0]);
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Responder a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// ═══════════════════════════════════════
//  LEER DATOS DEL MÓDULO ECG
// ═══════════════════════════════════════
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!$body || !isset($body['datos_ecg'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos ECG no recibidos']);
    exit;
}

$datos = $body['datos_ecg'];

// Validar campos obligatorios
$required = ['cuadrados_rr', 'ritmo', 'qrs_morfologia', 'st_estado'];
foreach ($required as $campo) {
    if (!isset($datos[$campo])) {
        http_response_code(400);
        echo json_encode(['error' => "Campo faltante: $campo"]);
        exit;
    }
}

// Sanitizar entradas
$cuadrados = (int) $datos['cuadrados_rr'];
$ritmo     = substr(strip_tags($datos['ritmo']),            0, 80);
$qrs       = substr(strip_tags($datos['qrs_morfologia']),   0, 80);
$st        = substr(strip_tags($datos['st_estado']),        0, 80);
$ondaT     = substr(strip_tags($datos['onda_t']     ?? ''), 0, 80);
$qt        = substr(strip_tags($datos['qt_estado']  ?? ''), 0, 80);
$contexto  = substr(strip_tags($datos['contexto']   ?? ''), 0, 200);

// Validación básica
if ($cuadrados < 1 || $cuadrados > 20) {
    http_response_code(400);
    echo json_encode(['error' => 'Cuadrados R-R fuera de rango (1–20)']);
    exit;
}

// ═══════════════════════════════════════
//  CONSTRUIR PROMPT PARA DEEPSEEK
// ═══════════════════════════════════════
$prompt = <<<PROMPT
Eres un instructor experto en electrocardiografía para profesionales de salud. 
Un alumno te entrega los datos medidos de un ECG y necesitas:
1. Aplicar el método F·R·E·C completo con razonamiento clínico
2. Mostrar el cálculo de FC paso a paso con la fórmula 300 ÷ N
3. Interpretar cada hallazgo clínicamente
4. Indicar si hay urgencia y qué acción tomar

DATOS MEDIDOS POR EL ALUMNO:
- Cuadrados grandes entre 2 picos R consecutivos: {$cuadrados}
- Ritmo observado: {$ritmo}
- Morfología QRS: {$qrs}
- Segmento ST: {$st}
- Onda T: {$ondaT}
- Intervalo QT: {$qt}
- Contexto clínico adicional: {$contexto}

Responde ÚNICAMENTE con este JSON (sin texto adicional, sin markdown):
{
  "fc_calculada": número entero (resultado de 300 ÷ {$cuadrados}),
  "fc_clasificacion": "Normal" | "Bradicardia" | "Taquicardia",
  "calculo_paso_a_paso": [
    "Paso 1: texto explicando qué se midió",
    "Paso 2: 300 ÷ {$cuadrados} cuadrados grandes = X lpm",
    "Paso 3: interpretación del valor"
  ],
  "frec_bloque": {
    "letra": "F",
    "titulo": "Frecuencia Cardíaca",
    "resultado": texto con el valor y clasificación,
    "interpretacion": texto clínico breve (1-2 frases)
  },
  "ritmo_bloque": {
    "letra": "R",
    "titulo": "Ritmo y Ondas",
    "resultado": texto resumiendo el ritmo observado,
    "p_onda": evaluación de la onda P según el ritmo ingresado,
    "pr_intervalo": evaluación deducida del PR,
    "qrs_evaluacion": evaluación del QRS ingresado,
    "interpretacion": texto clínico breve (1-2 frases)
  },
  "eje_bloque": {
    "letra": "E",
    "titulo": "Eje Eléctrico",
    "resultado": "Requiere derivaciones DI y aVF para confirmar",
    "interpretacion": "Con los datos disponibles no es posible calcular el eje con precisión. Revisar DI y aVF en el trazado completo."
  },
  "cambios_bloque": {
    "letra": "C",
    "titulo": "Cambios: ST · T · QT",
    "st_resultado": evaluación del ST ingresado,
    "t_resultado": evaluación de la onda T ingresada,
    "qt_resultado": evaluación del QT ingresado,
    "interpretacion": texto clínico relevante (1-2 frases),
    "urgente": true o false
  },
  "conclusion_clinica": texto de 2-3 frases con la conclusión F·R·E·C completa,
  "accion_sugerida": texto con la acción clínica sugerida basada en los hallazgos,
  "perla_clinica": un tip clínico breve y memorable relacionado con el caso,
  "urgencia_nivel": "INMEDIATA" | "PRIORITARIA" | "DIFERIDA" | "NINGUNA"
}
PROMPT;

// ═══════════════════════════════════════
//  LLAMAR A LA API DE DEEPSEEK
// ═══════════════════════════════════════
$payload = json_encode([
    'model'       => DEEPSEEK_MODEL,
    'max_tokens'  => MAX_TOKENS,
    'temperature' => 0.3,
    'messages'    => [
        [
            'role'    => 'system',
            'content' => 'Eres un experto en electrocardiografía clínica. Siempre respondes en JSON válido y en español, sin texto adicional fuera del JSON.'
        ],
        [
            'role'    => 'user',
            'content' => $prompt
        ]
    ]
]);

$ch = curl_init(DEEPSEEK_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_TIMEOUT        => TIMEOUT_SECS,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DEEPSEEK_API_KEY,
    ],
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response  = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_err  = curl_error($ch);
curl_close($ch);

// ═══════════════════════════════════════
//  MANEJAR RESPUESTA
// ═══════════════════════════════════════
if ($curl_err) {
    http_response_code(502);
    echo json_encode(['error' => 'Error de conexión con la API: ' . $curl_err]);
    exit;
}

if ($http_code !== 200) {
    $err_data = json_decode($response, true);
    $err_msg  = $err_data['error']['message'] ?? "Error HTTP $http_code";
    http_response_code(502);
    echo json_encode(['error' => $err_msg]);
    exit;
}

$api_data = json_decode($response, true);
$raw_text = $api_data['choices'][0]['message']['content'] ?? '';

// Limpiar posible markdown extra que DeepSeek pueda agregar
$clean_text = preg_replace('/^```json\s*/i', '', trim($raw_text));
$clean_text = preg_replace('/\s*```$/i', '', $clean_text);

// Verificar que sea JSON válido antes de reenviar
$parsed = json_decode($clean_text, true);
if ($parsed === null) {
    http_response_code(502);
    echo json_encode(['error' => 'La IA retornó una respuesta no parseable. Intenta nuevamente.']);
    exit;
}

// Agregar metadata útil para el módulo
$parsed['_meta'] = [
    'modelo'     => DEEPSEEK_MODEL,
    'timestamp'  => date('c'),
    'cuadrados'  => $cuadrados,
    'fc_formula' => "300 ÷ {$cuadrados} = " . round(300 / $cuadrados)
];

echo json_encode($parsed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
