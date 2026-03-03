<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use App\Controllers\AdminController;
use App\Services\LeadService;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/Services/LeadService.php';
require __DIR__ . '/../src/Controllers/AdminController.php';

function envOrDefault($key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

// CONFIGURACIÓN DE SESIÓN PERSISTENTE (30 DÍAS)
ini_set('session.cookie_lifetime', 2592000);
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_secure', envOrDefault('SESSION_COOKIE_SECURE', '1'));
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', envOrDefault('SESSION_COOKIE_SAMESITE', 'Lax'));

if (function_exists('session_set_cookie_params')) {
    session_set_cookie_params([
        'lifetime' => 2592000,
        'path' => '/',
        'secure' => envOrDefault('SESSION_COOKIE_SECURE', '1') === '1',
        'httponly' => true,
        'samesite' => envOrDefault('SESSION_COOKIE_SAMESITE', 'Lax')
    ]);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$app = AppFactory::create();
$debugEnabled = envOrDefault('APP_DEBUG', '0') === '1';
$app->addErrorMiddleware($debugEnabled, $debugEnabled, $debugEnabled);
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('X-Frame-Options', 'SAMEORIGIN')
        ->withHeader('X-Content-Type-Options', 'nosniff')
        ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload')
        ->withHeader('Content-Security-Policy', "default-src 'self'; img-src 'self' data: https:; script-src 'self'; style-src 'self' 'unsafe-inline'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
});

$renderer = new PhpRenderer(__DIR__ . '/../templates');

$leadService = new LeadService(getDB());
$adminController = new AdminController($renderer, $leadService);

// CREDENCIALES GOOGLE
$googleClientId     = envOrDefault('GOOGLE_CLIENT_ID', '');
$googleClientSecret = envOrDefault('GOOGLE_CLIENT_SECRET', '');
$redirectUri        = envOrDefault('GOOGLE_REDIRECT_URI', '');

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

function getDB() {
    $dbHost = envOrDefault('DB_HOST', '');
    $dbName = envOrDefault('DB_NAME', '');
    $dbUser = envOrDefault('DB_USER', '');
    $dbPass = envOrDefault('DB_PASS', '');
    return new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function resendApiKey(): string {
    return trim((string)envOrDefault('RESEND_API_KEY', ''));
}

function resendFromEmail(): string {
    return trim((string)envOrDefault('RESEND_FROM_EMAIL', envOrDefault('MAIL_FROM', '')));
}

function resendFromName(): string {
    return trim((string)envOrDefault('RESEND_FROM_NAME', ''));
}

function resendAdminEmails(): array {
    $raw = trim((string)envOrDefault('ADMIN_ALERT_EMAILS', envOrDefault('ADMIN_ALERT_EMAIL', '')));
    if ($raw === '') {
        return [];
    }
    $emails = preg_split('/[\s,;]+/', $raw) ?: [];
    $valid = [];
    foreach ($emails as $email) {
        $email = strtolower(trim((string)$email));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $valid[] = $email;
        }
    }
    return array_values(array_unique($valid));
}

function resendIsConfigured(): bool {
    return resendApiKey() !== '' && resendFromEmail() !== '';
}

function sendResendEmail(array $payload): array {
    if (!resendIsConfigured()) {
        return ['ok' => false, 'error' => 'resend_not_configured'];
    }

    $to = $payload['to'] ?? [];
    if (!is_array($to)) {
        $to = [$to];
    }
    $to = array_values(array_filter(array_map(static function ($email) {
        $email = trim((string)$email);
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }, $to)));

    if (empty($to)) {
        return ['ok' => false, 'error' => 'missing_recipient'];
    }

    $fromEmail = trim((string)($payload['from_email'] ?? resendFromEmail()));
    $fromName = trim((string)($payload['from_name'] ?? resendFromName()));
    $from = $fromName !== '' ? ($fromName . ' <' . $fromEmail . '>') : $fromEmail;
    $body = [
        'from' => $from,
        'to' => $to,
        'subject' => trim((string)($payload['subject'] ?? 'Tu Estudio Juridico')),
    ];
    if (!empty($payload['html'])) {
        $body['html'] = (string)$payload['html'];
    }
    if (!empty($payload['text'])) {
        $body['text'] = (string)$payload['text'];
    }
    if (isset($payload['reply_to']) && filter_var((string)$payload['reply_to'], FILTER_VALIDATE_EMAIL)) {
        $body['reply_to'] = trim((string)$payload['reply_to']);
    }

    $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return ['ok' => false, 'error' => 'payload_encode_failed'];
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . resendApiKey(),
    ];

    $responseRaw = false;
    $httpCode = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $responseRaw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        if ($responseRaw === false) {
            return ['ok' => false, 'error' => $curlError !== '' ? $curlError : 'curl_failed'];
        }
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $json,
                'timeout' => 15,
                'ignore_errors' => true,
            ],
        ]);
        $responseRaw = @file_get_contents('https://api.resend.com/emails', false, $context);
        $statusLine = '';
        if (!empty($http_response_header[0])) {
            $statusLine = (string)$http_response_header[0];
            if (preg_match('/\s(\d{3})\s/', $statusLine, $m)) {
                $httpCode = (int)$m[1];
            }
        }
        if ($responseRaw === false) {
            return ['ok' => false, 'error' => $statusLine !== '' ? $statusLine : 'http_request_failed'];
        }
    }

    $decoded = json_decode((string)$responseRaw, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        return ['ok' => true, 'response' => $decoded, 'http_code' => $httpCode];
    }
    return [
        'ok' => false,
        'error' => is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : trim((string)$responseRaw),
        'http_code' => $httpCode,
    ];
}

function sendAdminEventEmail(string $subject, string $html, string $text = ''): array {
    $admins = resendAdminEmails();
    if (empty($admins)) {
        return ['ok' => false, 'error' => 'missing_admin_alert_emails'];
    }
    return sendResendEmail([
        'to' => $admins,
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
    ]);
}

function notifyProjectInterest(array $payload): array {
    $to = trim((string)envOrDefault('PROJECT_INTEREST_EMAIL', 'gmcalderonlewin@gmail.com'));
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'missing_project_interest_email'];
    }

    $nombre = trim((string)($payload['nombre'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $empresa = trim((string)($payload['empresa'] ?? ''));
    $interes = trim((string)($payload['interes'] ?? ''));
    $mensaje = trim((string)($payload['mensaje'] ?? ''));
    $host = trim((string)($payload['host'] ?? ''));

    $interesLabelMap = [
        'prueba' => 'Quiere una prueba',
        'implementar' => 'Quiere implementarlo en su estudio',
    ];
    $interesLabel = $interesLabelMap[$interes] ?? $interes;

    $subject = 'Nuevo interes en el proyecto: ' . ($interesLabel !== '' ? $interesLabel : 'Contacto');
    $html = '<p>Se registró un nuevo interes desde la landing pública.</p>'
        . '<ul>'
        . '<li><strong>Nombre:</strong> ' . htmlspecialchars($nombre !== '' ? $nombre : 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Email:</strong> ' . htmlspecialchars($email !== '' ? $email : 'Sin email', ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Empresa:</strong> ' . htmlspecialchars($empresa !== '' ? $empresa : 'No informada', ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Interés:</strong> ' . htmlspecialchars($interesLabel !== '' ? $interesLabel : 'No informado', ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Host:</strong> ' . htmlspecialchars($host !== '' ? $host : 'Desconocido', ENT_QUOTES, 'UTF-8') . '</li>'
        . '</ul>'
        . ($mensaje !== '' ? '<p><strong>Mensaje:</strong><br>' . nl2br(htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8')) . '</p>' : '');
    $text = "Se registró un nuevo interes desde la landing pública.\n\n"
        . 'Nombre: ' . ($nombre !== '' ? $nombre : 'Sin nombre') . "\n"
        . 'Email: ' . ($email !== '' ? $email : 'Sin email') . "\n"
        . 'Empresa: ' . ($empresa !== '' ? $empresa : 'No informada') . "\n"
        . 'Interés: ' . ($interesLabel !== '' ? $interesLabel : 'No informado') . "\n"
        . 'Host: ' . ($host !== '' ? $host : 'Desconocido') . "\n"
        . ($mensaje !== '' ? "\nMensaje:\n{$mensaje}\n" : '');

    return sendResendEmail([
        'to' => [$to],
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
        'reply_to' => $email,
    ]);
}

function notifyProfessionalAccessRequest(array $lawyer, array $overrides = []): array {
    if (!resendIsConfigured()) {
        return ['ok' => false, 'error' => 'resend_not_configured'];
    }

    $nombre = trim((string)($overrides['nombre'] ?? $lawyer['nombre'] ?? ''));
    $email = trim((string)($overrides['email'] ?? $lawyer['email'] ?? ''));
    $whatsapp = trim((string)($overrides['whatsapp'] ?? $lawyer['whatsapp'] ?? ''));
    $rut = trim((string)($overrides['rut_abogado'] ?? $lawyer['rut_abogado'] ?? ''));
    $slug = trim((string)($lawyer['slug'] ?? ''));
    $adminUrl = 'https://example.com/admin';

    $subject = 'Solicitud de activacion de perfil profesional';
    $html = '<p>Un abogado solicitó activación manual de perfil profesional en Tu Estudio Juridico.</p>'
        . '<ul>'
        . '<li><strong>Nombre:</strong> ' . htmlspecialchars($nombre !== '' ? $nombre : 'Sin nombre', ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Email:</strong> ' . htmlspecialchars($email !== '' ? $email : 'Sin email', ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>WhatsApp:</strong> ' . htmlspecialchars($whatsapp !== '' ? $whatsapp : 'Sin WhatsApp', ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>RUT abogado:</strong> ' . htmlspecialchars($rut !== '' ? $rut : 'Sin RUT', ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>ID:</strong> ' . (int)($lawyer['id'] ?? 0) . '</li>'
        . ($slug !== '' ? '<li><strong>Slug:</strong> ' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') . '</li>' : '')
        . '</ul>'
        . '<p>Revisión y activación manual: <a href="' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($adminUrl, ENT_QUOTES, 'UTF-8') . '</a></p>';
    $text = "Un abogado solicitó activación manual de perfil profesional en Tu Estudio Juridico.\n\n"
        . 'Nombre: ' . ($nombre !== '' ? $nombre : 'Sin nombre') . "\n"
        . 'Email: ' . ($email !== '' ? $email : 'Sin email') . "\n"
        . 'WhatsApp: ' . ($whatsapp !== '' ? $whatsapp : 'Sin WhatsApp') . "\n"
        . 'RUT abogado: ' . ($rut !== '' ? $rut : 'Sin RUT') . "\n"
        . 'ID: ' . (int)($lawyer['id'] ?? 0) . "\n"
        . ($slug !== '' ? 'Slug: ' . $slug . "\n" : '')
        . "\nRevisión y activación manual: {$adminUrl}\n";

    return sendAdminEventEmail($subject, $html, $text);
}

function notifyLawyerProfileApproved(array $lawyer, int $completionPercent, bool $published): array {
    $email = trim((string)($lawyer['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'missing_target_email'];
    }

    $nombre = trim((string)($lawyer['nombre'] ?? ''));
    $dashboardUrl = 'https://example.com/dashboard';
    $profileUrl = !empty($lawyer['slug']) ? ('https://example.com/' . ltrim((string)$lawyer['slug'], '/')) : null;
    $publishedText = $published
        ? 'Tu perfil profesional fue activado y publicado en Tu Estudio Juridico.'
        : 'Tu cuenta profesional fue activada, pero tu perfil sigue oculto hasta completar al menos el 80% del perfil.';
    $nextStepText = $published
        ? 'Ya puedes entrar a tu dashboard profesional para gestionar leads, cotizaciones y tu perfil.'
        : 'Completa tu perfil profesional para que podamos publicarlo en el directorio de abogados.';

    $html = '<p>Hola ' . htmlspecialchars($nombre !== '' ? $nombre : 'abogado/a', ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>' . htmlspecialchars($publishedText, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><strong>Completitud actual:</strong> ' . $completionPercent . '%</p>'
        . '<p>' . htmlspecialchars($nextStepText, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p><a href="' . htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8') . '">Ir al dashboard profesional</a></p>';
    if ($profileUrl !== null) {
        $html .= '<p><a href="' . htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') . '">Ver perfil público</a></p>';
    }

    $text = 'Hola ' . ($nombre !== '' ? $nombre : 'abogado/a') . ",\n\n"
        . $publishedText . "\n"
        . 'Completitud actual: ' . $completionPercent . "%\n"
        . $nextStepText . "\n\n"
        . 'Ir al dashboard profesional: ' . $dashboardUrl . "\n";
    if ($profileUrl !== null) {
        $text .= 'Ver perfil público: ' . $profileUrl . "\n";
    }

    return sendResendEmail([
        'to' => [$email],
        'subject' => 'Tu perfil profesional fue activado en Tu Estudio Juridico',
        'html' => $html,
        'text' => $text,
    ]);
}

function appCronSecret(): string {
    return trim((string)envOrDefault('APP_CRON_SECRET', envOrDefault('JOBS_SECRET', '')));
}

function hasValidCronSecret($provided): bool {
    $secret = appCronSecret();
    $provided = trim((string)$provided);
    return $secret !== '' && $provided !== '' && hash_equals($secret, $provided);
}

function quoteCollectionReminderContent(array $lawyer, array $quote, string $mode = 'manual'): array {
    $clientEmail = trim((string)($quote['client_email'] ?? ''));
    if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'missing_client_email'];
    }

    $clientName = trim((string)($quote['client_name'] ?? ''));
    $serviceName = trim((string)($quote['asunto'] ?? $quote['servicio_nombre_resuelto'] ?? 'Cotización legal'));
    $collectionState = strtoupper(trim((string)($quote['cobro_estado_resuelto'] ?? $quote['cobro_estado'] ?? 'SIN_GESTION')));
    $total = sanitizeMoneyAmount($quote['total'] ?? 0);
    $anticipo = sanitizeMoneyAmount($quote['anticipo'] ?? 0);
    $collected = sanitizeMoneyAmount($quote['cobrado_monto_resuelto'] ?? $quote['cobrado_monto'] ?? 0);
    $porCobrar = max(0, sanitizeMoneyAmount($quote['por_cobrar_monto'] ?? max(0, $total - $collected)));
    if ($porCobrar <= 0) {
        return ['ok' => false, 'error' => 'quote_already_paid'];
    }

    $focusLabel = 'pago pendiente';
    $focusAmount = $porCobrar;
    $headline = 'Te escribimos para recordar el pago pendiente asociado a tu cotización.';
    if ($collectionState === 'ANTICIPO') {
        $focusLabel = 'saldo pendiente';
        $focusAmount = $porCobrar;
        $headline = 'Ya registramos el anticipo y queda pendiente el saldo final de tu cotización.';
    } elseif ($collectionState === 'PENDIENTE' && $anticipo > 0) {
        $focusLabel = 'anticipo pendiente';
        $focusAmount = $anticipo;
        $headline = 'Tu cotización fue aceptada y falta confirmar el anticipo para iniciar el servicio.';
    }

    $branding = lawyerQuoteBrandingSettings($lawyer);
    $signatureName = trim((string)($branding['brand_name'] ?? $lawyer['quote_brand_name'] ?? $lawyer['razon_social'] ?? $lawyer['nombre'] ?? 'Tu Estudio Juridico'));
    $signatureEmail = trim((string)($branding['email'] ?? $lawyer['quote_brand_email'] ?? $lawyer['email'] ?? resendFromEmail()));
    $signaturePhone = trim((string)($branding['phone'] ?? $lawyer['quote_brand_phone'] ?? $lawyer['whatsapp'] ?? ''));
    $paymentLink = trim((string)($quote['payment_link'] ?? ''));
    $paymentConditions = trim((string)($quote['condiciones_pago'] ?? ''));
    $subjectPrefix = $mode === 'automatic' ? 'Recordatorio automático de pago' : 'Recordatorio de pago';
    $subject = $subjectPrefix . ' - ' . $serviceName;

    $html = '<p>Hola ' . htmlspecialchars($clientName !== '' ? $clientName : 'cliente', ENT_QUOTES, 'UTF-8') . ',</p>'
        . '<p>' . htmlspecialchars($headline, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<ul>'
        . '<li><strong>Servicio:</strong> ' . htmlspecialchars($serviceName, ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>' . htmlspecialchars(ucfirst($focusLabel), ENT_QUOTES, 'UTF-8') . ':</strong> ' . htmlspecialchars(formatClpAmount($focusAmount), ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Total cotización:</strong> ' . htmlspecialchars(formatClpAmount($total), ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Abonado:</strong> ' . htmlspecialchars(formatClpAmount($collected), ENT_QUOTES, 'UTF-8') . '</li>'
        . '<li><strong>Saldo pendiente:</strong> ' . htmlspecialchars(formatClpAmount($porCobrar), ENT_QUOTES, 'UTF-8') . '</li>'
        . '</ul>';
    if ($paymentConditions !== '') {
        $html .= '<p><strong>Forma de pago:</strong> ' . nl2br(htmlspecialchars($paymentConditions, ENT_QUOTES, 'UTF-8')) . '</p>';
    }
    if ($paymentLink !== '') {
        $html .= '<p><a href="' . htmlspecialchars($paymentLink, ENT_QUOTES, 'UTF-8') . '">Ver link de pago</a></p>';
    }
    $html .= '<p>Si ya realizaste el pago, responde este correo para actualizar el estado y continuar con el servicio.</p>'
        . '<p>Atentamente,<br>' . htmlspecialchars($signatureName, ENT_QUOTES, 'UTF-8');
    if ($signaturePhone !== '') {
        $html .= '<br>' . htmlspecialchars($signaturePhone, ENT_QUOTES, 'UTF-8');
    }
    if ($signatureEmail !== '' && filter_var($signatureEmail, FILTER_VALIDATE_EMAIL)) {
        $html .= '<br>' . htmlspecialchars($signatureEmail, ENT_QUOTES, 'UTF-8');
    }
    $html .= '</p>';

    $text = 'Hola ' . ($clientName !== '' ? $clientName : 'cliente') . ",\n\n"
        . $headline . "\n\n"
        . 'Servicio: ' . $serviceName . "\n"
        . ucfirst($focusLabel) . ': ' . formatClpAmount($focusAmount) . "\n"
        . 'Total cotización: ' . formatClpAmount($total) . "\n"
        . 'Abonado: ' . formatClpAmount($collected) . "\n"
        . 'Saldo pendiente: ' . formatClpAmount($porCobrar) . "\n";
    if ($paymentConditions !== '') {
        $text .= 'Forma de pago: ' . $paymentConditions . "\n";
    }
    if ($paymentLink !== '') {
        $text .= 'Link de pago: ' . $paymentLink . "\n";
    }
    $text .= "\nSi ya realizaste el pago, responde este correo para actualizar el estado.\n\n"
        . 'Atentamente,' . "\n"
        . $signatureName . "\n";
    if ($signaturePhone !== '') {
        $text .= $signaturePhone . "\n";
    }
    if ($signatureEmail !== '' && filter_var($signatureEmail, FILTER_VALIDATE_EMAIL)) {
        $text .= $signatureEmail . "\n";
    }

    return [
        'ok' => true,
        'subject' => $subject,
        'html' => $html,
        'text' => $text,
        'reply_to' => filter_var($signatureEmail, FILTER_VALIDATE_EMAIL) ? $signatureEmail : null,
    ];
}

function notifyQuoteCollectionReminder(array $lawyer, array $quote, string $mode = 'manual'): array {
    $content = quoteCollectionReminderContent($lawyer, $quote, $mode);
    if (empty($content['ok'])) {
        return $content;
    }
    return sendResendEmail([
        'to' => [trim((string)($quote['client_email'] ?? ''))],
        'subject' => (string)$content['subject'],
        'html' => (string)$content['html'],
        'text' => (string)$content['text'],
        'reply_to' => $content['reply_to'] ?? null,
    ]);
}

function getGoogleClient() {
    global $googleClientId, $googleClientSecret, $redirectUri;
    $client = new Google\Client();
    $client->setClientId($googleClientId);
    $client->setClientSecret($googleClientSecret);
    $client->setRedirectUri($redirectUri);
    $client->addScope("email");
    $client->addScope("profile");
    return $client;
}

function createSlug($text) {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
    return preg_replace('/\s+/', '-', trim($text));
}

function validarWhatsApp($numero) {
    $numero = preg_replace('/[^0-9]/', '', $numero);
    return (strlen($numero) === 9 && substr($numero, 0, 1) === '9') ? $numero : false;
}

function especialidadesClientePermitidas() {
    return [
        'Familia',
        'Laboral',
        'Civil',
        'Penal',
        'Inmobiliario',
        'Deudores'
    ];
}

function lawyerMateriasTaxonomia(): array {
    return [
        'Derecho Civil' => ['Inmigración en Chile', 'Herencias y Posesiones efectivas', 'Deudas y Embargos', 'Compra y Arriendo de Propiedades', 'Negligencia Médica', 'Problemas entre Vecinos', 'Pago de Honorarios', 'Recurso de Protección contra alza en Isapre', 'Mascotas', 'Otros Casos Civiles'],
        'Derecho Familiar' => ['Pensión Alimenticia', 'Divorcio', 'Tuición', 'Juicio o Reconocimiento de Paternidad', 'Régimen de Visitas', 'Violencia Intrafamiliar', 'Cambio de Nombre', 'Declaración de Interdicción', 'Adopciones', 'Otros Casos de Familia'],
        'Derecho Laboral' => ['Defensa de Derechos Laborales', 'Despido Injustificado', 'Licencia Médica', 'Accidentes Laborales', 'Acoso Sexual', 'Autodespido', 'Constitución y Asesoría de Sindicatos', 'Negociación Colectiva', 'Otros Casos Laborales'],
        'Derecho Penal' => ['Accidentes de Tránsito', 'Abuso Sexual y Violación', 'Robos y Hurtos', 'Manejo en Estado de Ebriedad', 'Injurias y Calumnias', 'Tráfico de Drogas', 'Estafas y Delitos económicos', 'Agresiones y Riñas', 'Homicidios', 'Discriminación y Delitos de odio', 'Amenazas y Extorsiones', 'Delitos Informáticos', 'Otros Casos Penales'],
        'Derecho Comercial' => ['Insolvencia y Quiebras', 'Marcas, Patentes y Propiedad Intelectual', 'Constitución de Sociedad', 'Redacción o Revisión de Contratos', 'Litigio y Arbitrajes', 'Inversión Extranjera', 'Importaciones, Exportaciones y Derecho Aduanero', 'Recursos Naturales y Medioambientales', 'Mercado de Capitales', 'Otros Casos Comerciales'],
        'Derecho Tributario' => ['Planificación Tributaria', 'Litigios Tributarios', 'Otros Casos Tributarios'],
        'Protección al Consumidor' => ['Inmobiliarias y Constructoras', 'Bancos, AFPs y Financieras', 'Aseguradoras', 'Transporte', 'Tiendas y Retail', 'Entretención y Turismo', 'Comercio Electrónico', 'Automóviles e Indumotoras', 'Salud', 'Educación', 'Servicios Básicos', 'Telecomunicaciones', 'Otros Casos Protección al Consumidor'],
        'Derechos Humanos' => ['Detención ilegal', 'Lesiones por agentes del Estado', 'Torturas', 'Violencia sexual', 'Homicidio', 'Discriminación', 'Otros casos DD.HH.'],
        'Otros Casos' => ['Otro Tipo de Caso'],
        // Compatibilidad legacy (no romper perfiles existentes)
        'Civil' => ['Otros Casos Civiles'],
        'Familia' => ['Otros Casos de Familia'],
        'Laboral' => ['Otros Casos Laborales'],
        'Penal' => ['Otros Casos Penales'],
        'Comercial' => ['Otros Casos Comerciales'],
        'Tributario' => ['Otros Casos Tributarios'],
        'Consumidor' => ['Otros Casos Protección al Consumidor'],
        'Inmobiliario' => ['Otros casos inmobiliarios'],
        'Otros' => ['Otro tipo de caso'],
    ];
}

function lawyerMateriasCanonicas(): array {
    return [
        'Derecho Civil',
        'Derecho Familiar',
        'Derecho Laboral',
        'Derecho Penal',
        'Derecho Comercial',
        'Derecho Tributario',
        'Protección al Consumidor',
        'Derechos Humanos',
        'Otros Casos',
    ];
}

function normalizeLawyerMateria(string $materia): string {
    $m = trim($materia);
    $map = [
        'Civil' => 'Derecho Civil',
        'Familia' => 'Derecho Familiar',
        'Laboral' => 'Derecho Laboral',
        'Penal' => 'Derecho Penal',
        'Comercial' => 'Derecho Comercial',
        'Tributario' => 'Derecho Tributario',
        'Consumidor' => 'Protección al Consumidor',
        'Otros' => 'Otros Casos',
    ];
    return $map[$m] ?? $m;
}

function materiaSlug(string $text): string {
    $slug = trim($text);
    if ($slug === '') {
        return '';
    }
    $slug = strtr($slug, [
        'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A',
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a',
        'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Ö' => 'O',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o',
        'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ñ' => 'N', 'ñ' => 'n', 'Ç' => 'C', 'ç' => 'c',
    ]);
    if (function_exists('iconv')) {
        $iconv = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        if ($iconv !== false && $iconv !== '') {
            $slug = (string)$iconv;
        }
    }
    $slug = strtolower($slug);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim((string)$slug, '-');
}

function normalizeInternalNextPath($candidate, string $fallback = '/explorar'): string {
    $next = trim((string)$candidate);
    if ($next === '' || !str_starts_with($next, '/') || str_starts_with($next, '//')) {
        return $fallback;
    }
    return $next;
}

function buildAuthGateContract(string $next = '/explorar'): array {
    $nextSafe = normalizeInternalNextPath($next, '/explorar');
    $isAuthenticated = !empty($_SESSION['user_id']);
    return [
        'state' => $isAuthenticated ? 'authenticated' : 'anonymous',
        'is_authenticated' => $isAuthenticated,
        'requires_auth_for_full_access' => true,
        'next' => $nextSafe,
        'login_href' => '/login-google?next=' . rawurlencode($nextSafe),
    ];
}

function buildLandingPreviewPayload(PDO $pdo): array {
    $preview = [];
    $materiaCounts = [];
    try {
        $sql = "SELECT id,nombre,slug,especialidad,submaterias,experiencia,universidad,regiones_servicio,cobertura_nacional,google_picture,foto_url,likes,vistas FROM abogados WHERE " . visibleLawyerWhereClause() . " ORDER BY COALESCE(destacado_hasta,'1970-01-01') DESC, likes DESC, vistas DESC, id DESC LIMIT 6";
        $preview = $pdo->query($sql)->fetchAll() ?: [];
        foreach ($preview as &$abg) {
            $abg['foto_final'] = resolveLawyerPhoto($abg, 120, false);
        }
        unset($abg);
        foreach ($pdo->query("SELECT especialidad, COUNT(*) c FROM abogados WHERE " . visibleLawyerWhereClause() . " AND TRIM(COALESCE(especialidad,'')) <> '' GROUP BY especialidad") as $row) {
            $k = trim((string)($row['especialidad'] ?? ''));
            if ($k === '') {
                continue;
            }
            $materiaCounts[$k] = (int)($row['c'] ?? 0);
        }
    } catch (Throwable $e) {
        $preview = [];
        $materiaCounts = [];
    }
    return [
        'preview_lawyers' => $preview,
        'materia_counts' => $materiaCounts,
    ];
}

function consumeSessionFlashMessage(): array {
    $mensaje = $_SESSION['mensaje'] ?? null;
    $tipo_mensaje = $_SESSION['tipo_mensaje'] ?? null;
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    return [
        'mensaje' => $mensaje,
        'tipo_mensaje' => $tipo_mensaje,
    ];
}

function ensureLawyerSecondaryProfileColumns() {
    try {
        if (!dbColumnExists('abogados', 'materia_secundaria')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN materia_secundaria VARCHAR(150) NULL");
        }
        if (!dbColumnExists('abogados', 'submaterias_secundarias')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN submaterias_secundarias TEXT NULL");
        }
        if (!dbColumnExists('abogados', 'ciudades_plaza')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN ciudades_plaza TEXT NULL");
        }
        if (!dbColumnExists('abogados', 'sexo')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN sexo VARCHAR(20) NULL");
        }
        if (!dbColumnExists('abogados', 'entrevista_presencial')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN entrevista_presencial TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!dbColumnExists('abogados', 'faq_personalizadas_json')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN faq_personalizadas_json TEXT NULL");
        }
        if (!dbColumnExists('abogados', 'color_marca')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN color_marca VARCHAR(24) NULL");
        }
        if (!dbColumnExists('abogados', 'audiencias_para_abogados_plaza')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN audiencias_para_abogados_plaza TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!dbColumnExists('abogados', 'universidad_postitulo')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN universidad_postitulo VARCHAR(190) NULL");
        }
        if (!dbColumnExists('abogados', 'universidad_diplomado')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN universidad_diplomado VARCHAR(190) NULL");
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLeadLifecycleColumns() {
    try {
        if (!dbColumnExists('contactos_revelados', 'retention_stage')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN retention_stage VARCHAR(20) NOT NULL DEFAULT 'activo'");
        }
        if (!dbColumnExists('contactos_revelados', 'activo_hasta')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN activo_hasta DATETIME NULL");
        }
        if (!dbColumnExists('contactos_revelados', 'papelera_desde')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN papelera_desde DATETIME NULL");
        }
        if (!dbColumnExists('contactos_revelados', 'papelera_hasta')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN papelera_hasta DATETIME NULL");
        }
        if (!dbColumnExists('contactos_revelados', 'respaldado_at')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN respaldado_at DATETIME NULL");
        }
        if (!dbColumnExists('contactos_revelados', 'estado_updated_at')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN estado_updated_at DATETIME NULL");
            try {
                getDB()->exec("UPDATE contactos_revelados SET estado_updated_at = COALESCE(fecha_cierre, fecha_revelado, created_at, NOW()) WHERE estado_updated_at IS NULL");
            } catch (Throwable $e2) {}
        }
        if (!dbColumnExists('contactos_revelados', 'abogado_vio_at')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN abogado_vio_at DATETIME NULL");
        }
        if (!dbColumnExists('contactos_revelados', 'reabierto_at')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN reabierto_at DATETIME NULL");
        }
        if (!dbColumnExists('contactos_revelados', 'reabierto_count')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN reabierto_count INT NOT NULL DEFAULT 0");
        }
        if (!dbColumnExists('contactos_revelados', 'equipo_id')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN equipo_id INT NULL DEFAULT NULL");
        }
        if (!dbColumnExists('contactos_revelados', 'assigned_abogado_id')) {
            getDB()->exec("ALTER TABLE contactos_revelados ADD COLUMN assigned_abogado_id INT NULL DEFAULT NULL");
            try {
                getDB()->exec("UPDATE contactos_revelados SET assigned_abogado_id = abogado_id WHERE assigned_abogado_id IS NULL AND abogado_id IS NOT NULL");
            } catch (Throwable $e2) {}
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function runLeadRetentionMaintenance(PDO $pdo): array {
    ensureLeadLifecycleColumns();
    $result = ['moved_to_papelera' => 0, 'purged' => 0];
    if (!dbColumnExists('contactos_revelados', 'retention_stage')) {
        return $result;
    }
    try {
        $stmt1 = $pdo->exec(
            "UPDATE contactos_revelados
             SET retention_stage = 'papelera',
                 papelera_desde = COALESCE(papelera_desde, NOW()),
                 papelera_hasta = COALESCE(papelera_hasta, DATE_ADD(NOW(), INTERVAL 30 DAY))
             WHERE retention_stage = 'activo'
               AND activo_hasta IS NOT NULL
               AND activo_hasta < NOW()"
        );
        $result['moved_to_papelera'] = max(0, (int)$stmt1);
    } catch (Throwable $e) {}
    try {
        $stmt2 = $pdo->exec(
            "DELETE FROM contactos_revelados
             WHERE retention_stage = 'papelera'
               AND papelera_hasta IS NOT NULL
               AND papelera_hasta < NOW()"
        );
        $result['purged'] = max(0, (int)$stmt2);
    } catch (Throwable $e) {}
    return $result;
}

function normalizarTexto($texto) {
    $texto = trim((string)$texto);
    return preg_replace('/\s+/u', ' ', $texto);
}

function maxLawyersPerCase() {
    return 1;
}

function normalizeLookupKey($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    if (function_exists('iconv')) {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', ' ', $value);
    return trim((string)$value);
}

function normalizeRegionName($region) {
    $region = trim((string)$region);
    $key = normalizeLookupKey($region);
    $aliases = [
        "lib gral bernardo o higgins" => "Libertador General Bernardo O'Higgins",
        "libertador general bernardo o higgins" => "Libertador General Bernardo O'Higgins",
        "aysen del general carlos ibanez del campo" => "Aysén",
        "aysen" => "Aysén"
    ];
    if (isset($aliases[$key])) {
        return $aliases[$key];
    }
    return $region;
}

function normalizeComunaRow($row) {
    if (!is_array($row)) {
        return null;
    }

    $get = function (array $keys) use ($row) {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return null;
    };

    $comuna = trim((string)($get(['comuna', 'commune', 'nombre', 'name']) ?? ''));
    if ($comuna === '') {
        return null;
    }

    $region = normalizeRegionName($get(['region', 'region_name', 'nombre_region', 'name_region']) ?? '');
    $provincia = trim((string)($get(['provincia', 'province', 'province_name', 'nombre_provincia']) ?? ''));
    $cut = trim((string)($get(['cut', 'codigo', 'code', 'id']) ?? ''));

    $lat = $get(['lat', 'latitude', 'latitud']);
    $lng = $get(['lng', 'lon', 'long', 'longitude', 'longitud']);

    return [
        'cut' => $cut,
        'comuna' => $comuna,
        'provincia' => $provincia,
        'region' => $region,
        'lat' => is_numeric($lat) ? (float)$lat : null,
        'lng' => is_numeric($lng) ? (float)$lng : null
    ];
}

function normalizeComunasPayload($payload) {
    if (is_array($payload) && isset($payload['data']) && is_array($payload['data'])) {
        $payload = $payload['data'];
    }
    if (!is_array($payload)) {
        return [];
    }

    $rows = [];
    foreach ($payload as $item) {
        $normalized = normalizeComunaRow(is_array($item) ? $item : []);
        if ($normalized === null) {
            continue;
        }
        $rows[] = $normalized;
    }

    $uniq = [];
    $seen = [];
    foreach ($rows as $row) {
        $key = $row['cut'] !== '' ? $row['cut'] : normalizeLookupKey($row['comuna']);
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $uniq[] = $row;
    }
    return $uniq;
}

function fetchBoostrComunas() {
    $url = 'https://api.boostr.cl/geography/communes.json';
    $raw = null;
    $status = 0;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Tu Estudio Juridico/1.0 (+https://example.com)'
            ],
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\nUser-Agent: Tu Estudio Juridico/1.0 (+https://example.com)\r\n",
                'timeout' => 4
            ]
        ]);
        $raw = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
    }

    if ($status !== 200 || !is_string($raw) || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    $normalized = normalizeComunasPayload($decoded);
    return !empty($normalized) ? $normalized : null;
}

function boostrComunasCachePath() {
    return __DIR__ . '/data/chile_comunas_boostr.json';
}

function comunasChileCatalog() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $boostrPath = boostrComunasCachePath();
    $fallbackPath = __DIR__ . '/data/chile_comunas.json';
    $attemptPath = __DIR__ . '/data/chile_comunas_boostr_last_attempt.txt';
    $now = time();
    $lastAttempt = is_file($attemptPath) ? (int)@file_get_contents($attemptPath) : 0;
    $attemptCooldown = 24 * 60 * 60;
    $isCacheOld = is_file($boostrPath) && ($now - (int)@filemtime($boostrPath)) > (7 * 24 * 60 * 60);
    $missingCache = !is_file($boostrPath);
    $shouldRefresh = ($missingCache || $isCacheOld) && (($now - $lastAttempt) > $attemptCooldown);

    if ($shouldRefresh) {
        @file_put_contents($attemptPath, (string)$now);
        $remoteRows = fetchBoostrComunas();
        if (!empty($remoteRows)) {
            @file_put_contents(
                $boostrPath,
                json_encode($remoteRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            );
        }
    }

    $sourcePath = is_file($boostrPath) ? $boostrPath : $fallbackPath;
    if (!is_file($sourcePath)) {
        $cache = [];
        return $cache;
    }

    $raw = @file_get_contents($sourcePath);
    $data = json_decode((string)$raw, true);
    if (!is_array($data)) {
        $cache = [];
        return $cache;
    }

    $normalized = normalizeComunasPayload($data);

    $cache = $normalized;
    return $cache;
}

function regionesChile() {
    $catalog = comunasChileCatalog();
    if (!empty($catalog)) {
        $regions = [];
        foreach ($catalog as $item) {
            $region = trim((string)($item['region'] ?? ''));
            if ($region !== '' && !in_array($region, $regions, true)) {
                $regions[] = $region;
            }
        }
        if (!empty($regions)) {
            return $regions;
        }
    }
    return [
        'Arica y Parinacota',
        'Tarapacá',
        'Antofagasta',
        'Atacama',
        'Coquimbo',
        'Valparaíso',
        "Libertador General Bernardo O'Higgins",
        'Maule',
        'Ñuble',
        'Biobío',
        'La Araucanía',
        'Los Ríos',
        'Los Lagos',
        'Aysén',
        'Magallanes y Antártica Chilena',
        'Metropolitana de Santiago'
    ];
}

function universidadesChile() {
    return [
        'Pontificia Universidad Católica de Chile',
        'Universidad de Chile',
        'Universidad de Concepción',
        'Pontificia Universidad Católica de Valparaíso',
        'Universidad de Santiago de Chile',
        'Universidad Austral de Chile',
        'Universidad Técnica Federico Santa María',
        'Universidad Andrés Bello',
        'Universidad de Talca',
        'Universidad de Valparaíso',
        'Universidad del Desarrollo',
        'Universidad Diego Portales',
        'Universidad de La Frontera',
        'Universidad de los Andes',
        'Universidad Católica del Norte',
        'Universidad Adolfo Ibáñez',
        'Universidad Autónoma de Chile',
        'Universidad del Bío-Bío',
        'Universidad de Tarapacá',
        'Universidad San Sebastián',
        'Universidad de La Serena',
        'Universidad Católica de la Santísima Concepción',
        'Universidad Católica de Temuco',
        "Universidad Bernardo O'Higgins",
        'Universidad Central de Chile',
        'Universidad Mayor',
        'Universidad de Antofagasta',
        'Universidad Católica del Maule',
        'Universidad Alberto Hurtado',
        'Universidad de Playa Ancha',
        'Universidad Arturo Prat',
        'Universidad Tecnológica Metropolitana',
        'Universidad Finis Terrae',
        'Universidad de Los Lagos',
        'Universidad de Magallanes',
        'Universidad de Atacama',
        'Universidad Santo Tomás',
        'Universidad de Las Américas',
        'Universidad Metropolitana de Ciencias de la Educación',
        "Universidad de O'Higgins",
        'Universidad Católica Silva Henríquez',
        'Universidad Gabriela Mistral',
        'Universidad de Aysén',
        'Universidad Tecnológica de Chile - INACAP',
        'Universidad Viña del Mar',
        'Universidad Adventista de Chile',
        'Universidad Academia de Humanismo Cristiano',
        'Universidad SEK',
        'Universidad del Alba',
        'Universidad UNIACC',
        'Universidad Miguel de Cervantes',
        'Universidad de Aconcagua',
        'Universidad Bolivariana',
        'Universidad La República',
        'Universidad Los Leones',
        'Universidad de Aconcagua',
        'Universidad de Artes, Ciencias y Comunicación (UNIACC)',
        'Universidad de Viña del Mar',
        'Universidad del Alba (UDALBA)',
        'Universidad Gabriela Mistral',
        'Universidad Academia de Humanismo Cristiano',
        'Universidad Adventista de Chile',
        'Universidad SEK',
        'Universidad Metropolitana de Ciencias de la Educación',
        'Universidad Católica Silva Henríquez',
        'Universidad de O’Higgins',
        'Universidad de Aysén',
        'Universidad Tecnológica de Chile INACAP',
        'Universidad de Los Andes',
        'Universidad Mayor',
        'Universidad Central de Chile',
        'Universidad del Desarrollo',
        'Universidad Diego Portales',
        'Universidad Finis Terrae',
        'Universidad Adolfo Ibáñez',
        'Universidad Andrés Bello',
        'Universidad Autónoma de Chile',
        'Universidad San Sebastián',
        'Universidad Alberto Hurtado',
        'Universidad de Tarapacá',
        'Universidad de Antofagasta',
        'Universidad de Atacama',
        'Universidad de La Serena',
        'Universidad de Valparaíso',
        'Universidad de Playa Ancha',
        'Universidad de Santiago de Chile',
        'Universidad Tecnológica Metropolitana',
        'Universidad Arturo Prat',
        'Universidad de Talca',
        'Universidad Católica del Maule',
        'Universidad del Bío-Bío',
        'Universidad de La Frontera',
        'Universidad Católica de Temuco',
        'Universidad Austral de Chile',
        'Universidad de Los Lagos',
        'Universidad de Magallanes',
        'Universidad Santo Tomás',
        'Pontificia Universidad Católica de Valparaíso',
        'Universidad Técnica Federico Santa María',
        'Universidad Católica del Norte',
        'Universidad Católica de la Santísima Concepción'
    ];
}

function comunasChileSugeridas() {
    $catalog = comunasChileCatalog();
    if (!empty($catalog)) {
        return array_values(array_unique(array_column($catalog, 'comuna')));
    }
    return [];
}

function buscarComunaEnCatalogo($comuna) {
    $lookup = normalizeLookupKey($comuna);
    if ($lookup === '') {
        return null;
    }

    foreach (comunasChileCatalog() as $item) {
        if (normalizeLookupKey($item['comuna'] ?? '') === $lookup) {
            return $item;
        }
    }
    return null;
}

function regionSearchTerms($region) {
    $region = trim((string)$region);
    if ($region === '') {
        return [];
    }

    $terms = [$region];
    $key = normalizeLookupKey($region);
    if ($key === "libertador general bernardo o higgins" || $key === "lib gral bernardo o higgins") {
        $terms[] = "Lib. Gral. Bernardo O'Higgins";
        $terms[] = "Libertador General Bernardo O'Higgins";
    }
    if ($key === 'aysen' || $key === 'aysen del general carlos ibanez del campo') {
        $terms[] = 'Aysén';
        $terms[] = 'Aysen';
        $terms[] = 'Aysén del General Carlos Ibáñez del Campo';
    }
    return array_values(array_unique($terms));
}

function parseCoordinate($value) {
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_numeric($value)) {
        return null;
    }
    return (float)$value;
}

function fetchRemoteJson($url, $timeoutSeconds = 4) {
    $url = trim((string)$url);
    if ($url === '') {
        return [null, 0];
    }

    $status = 0;
    $raw = null;
    $headers = "Accept: application/json\r\nUser-Agent: Tu Estudio Juridico/1.0 (+https://example.com)\r\n";

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Tu Estudio Juridico/1.0 (+https://example.com)'],
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => max(2, (int)$timeoutSeconds),
            CURLOPT_FOLLOWLOCATION => true
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => $headers,
                'timeout' => max(2, (int)$timeoutSeconds)
            ]
        ]);
        $raw = @file_get_contents($url, false, $context);
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
    }

    if ($status !== 200 || !is_string($raw) || trim($raw) === '') {
        return [null, $status];
    }

    $decoded = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [null, $status];
    }

    return [$decoded, $status];
}

function normalizeRut($value) {
    $value = strtoupper(trim((string)$value));
    $value = preg_replace('/[^0-9K]/', '', $value);
    if ($value === '' || strlen($value) < 2) {
        return null;
    }

    $body = substr($value, 0, -1);
    $dv = substr($value, -1);
    if (!ctype_digit($body)) {
        return null;
    }

    $body = ltrim($body, '0');
    if ($body === '') {
        $body = '0';
    }
    return $body . '-' . $dv;
}

function calcularDvRut($body) {
    if (!ctype_digit((string)$body)) {
        return null;
    }

    $sum = 0;
    $mul = 2;
    for ($i = strlen($body) - 1; $i >= 0; $i--) {
        $sum += ((int)$body[$i]) * $mul;
        $mul = ($mul === 7) ? 2 : $mul + 1;
    }
    $rest = 11 - ($sum % 11);
    if ($rest === 11) {
        return '0';
    }
    if ($rest === 10) {
        return 'K';
    }
    return (string)$rest;
}

function rutValidoLocal($rutNormalizado) {
    $rutNormalizado = normalizeRut($rutNormalizado);
    if ($rutNormalizado === null) {
        return false;
    }

    [$body, $dv] = explode('-', $rutNormalizado);
    $dvEsperado = calcularDvRut($body);
    return $dvEsperado !== null && strtoupper($dv) === $dvEsperado;
}

function validateRutWithBoostr($rutNormalizado) {
    $rutNormalizado = normalizeRut($rutNormalizado);
    if ($rutNormalizado === null) {
        return [false, false];
    }

    [$body, $dvIngresado] = explode('-', $rutNormalizado);
    [$json, $status] = fetchRemoteJson('https://api.boostr.cl/rut/dv/' . urlencode($body) . '.json', 3);
    if ($status !== 200 || !is_array($json)) {
        return [null, false];
    }

    $dvApi = null;
    foreach (['dv', 'digito_verificador', 'digit', 'result', 'value'] as $key) {
        if (isset($json[$key]) && is_scalar($json[$key])) {
            $dvApi = strtoupper(trim((string)$json[$key]));
            break;
        }
    }
    if ($dvApi === null && isset($json['data']) && is_array($json['data'])) {
        foreach (['dv', 'digito_verificador', 'digit', 'result', 'value'] as $key) {
            if (isset($json['data'][$key]) && is_scalar($json['data'][$key])) {
                $dvApi = strtoupper(trim((string)$json['data'][$key]));
                break;
            }
        }
    }
    if ($dvApi === null || $dvApi === '') {
        return [null, true];
    }

    return [($dvApi === strtoupper($dvIngresado)), true];
}

function lookupPostalCodeWithBoostr($commune, $street, $number) {
    $commune = trim((string)$commune);
    $street = trim((string)$street);
    $number = trim((string)$number);

    if ($commune === '' || $street === '' || $number === '') {
        return [null, false, null];
    }

    $query = http_build_query([
        'commune' => $commune,
        'street' => $street,
        'number' => $number
    ]);
    [$json, $status] = fetchRemoteJson('https://api.boostr.cl/postalcode.json?' . $query, 4);
    if ($status !== 200 || !is_array($json)) {
        return [null, false, 'service_unavailable'];
    }

    $postal = null;
    $candidates = [$json];
    if (isset($json['data']) && is_array($json['data'])) {
        $candidates[] = $json['data'];
    }
    foreach ($candidates as $candidate) {
        foreach (['postalcode', 'postal_code', 'codigo_postal', 'code', 'zip'] as $key) {
            if (isset($candidate[$key]) && is_scalar($candidate[$key])) {
                $postal = preg_replace('/[^0-9A-Za-z]/', '', (string)$candidate[$key]);
                break 2;
            }
        }
    }

    if ($postal === null || $postal === '') {
        return [null, true, 'not_found'];
    }

    return [$postal, true, null];
}

function clienteWeeklyLimitInfo(array $cliente, $cooldownDays = 7) {
    $days = max(1, (int)$cooldownDays);
    $cooldownSeconds = $days * 24 * 60 * 60;

    $hasActiveCase = trim((string)($cliente['descripcion_caso'] ?? '')) !== '';
    $lastConsultRaw = trim((string)($cliente['ultima_consulta_cliente_at'] ?? ''));
    // Fallback legacy para datos anteriores a la migración.
    if ($lastConsultRaw === '' && trim((string)($cliente['whatsapp'] ?? '')) !== '') {
        $lastConsultRaw = trim((string)($cliente['created_at'] ?? ''));
    }

    $lastConsultTs = ($lastConsultRaw !== '') ? strtotime($lastConsultRaw) : false;
    $hasPublishedBefore = ($lastConsultTs !== false);
    $nextAvailableTs = $hasPublishedBefore ? ($lastConsultTs + $cooldownSeconds) : null;

    $isBlocked = false;
    if (!$hasActiveCase && $hasPublishedBefore && $nextAvailableTs !== null) {
        $isBlocked = $nextAvailableTs > time();
    }

    return [
        'cooldown_days' => $days,
        'has_active_case' => $hasActiveCase,
        'has_published_before' => $hasPublishedBefore,
        'is_blocked' => $isBlocked,
        'next_available_at' => $nextAvailableTs ? date('Y-m-d H:i:s', $nextAvailableTs) : null,
        'next_available_label' => $nextAvailableTs ? date('d/m/Y H:i', $nextAvailableTs) : null
    ];
}

function improveGooglePictureUrl($url, $size = 640) {
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }

    if (strpos($url, 'googleusercontent.com') === false) {
        return $url;
    }

    if (preg_match('/=s\d+-c$/', $url)) {
        return preg_replace('/=s\d+-c$/', '=s' . (int)$size . '-c', $url);
    }
    if (preg_match('/=s\d+$/', $url)) {
        return preg_replace('/=s\d+$/', '=s' . (int)$size, $url);
    }
    return $url . '=s' . (int)$size . '-c';
}

function resolveLawyerPhoto(array $abogado, $size = 640, $preserveIdentity = false) {
    $googlePicture = trim((string)($abogado['google_picture'] ?? ''));
    if ($googlePicture !== '') {
        return improveGooglePictureUrl($googlePicture, $size);
    }

    $fotoUrl = trim((string)($abogado['foto_url'] ?? ''));
    if ($fotoUrl !== '' && strtolower($fotoUrl) !== 'default.jpg') {
        return $fotoUrl;
    }

    $name = $preserveIdentity ? trim((string)($abogado['nombre'] ?? 'Abogado')) : 'Abogado';
    if ($name === '') {
        $name = 'Abogado';
    }
    return "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=1b5c5a&color=fff&size=" . (int)$size;
}

function ensureCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function hasValidCsrfToken($token) {
    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function rolePermitido($role) {
    return in_array($role, ['abogado', 'cliente'], true) ? $role : null;
}

function userCanPublishCases(array $user) {
    if (array_key_exists('puede_publicar_casos', $user)) {
        return (int)($user['puede_publicar_casos'] ?? 0) === 1;
    }
    return ($user['rol'] ?? null) === 'cliente';
}

function userCanUseLawyerMode(array $user) {
    if (array_key_exists('abogado_habilitado', $user)) {
        return (int)($user['abogado_habilitado'] ?? 0) === 1;
    }
    return ($user['rol'] ?? null) === 'abogado';
}

function userHasLawyerRequest(array $user): bool {
    if (array_key_exists('solicito_habilitacion_abogado', $user)) {
        return (int)($user['solicito_habilitacion_abogado'] ?? 0) === 1;
    }
    return false;
}

function userCanEditLawyerProfile(array $user): bool {
    return userCanUseLawyerMode($user) || userHasLawyerRequest($user);
}

function userCanAccessLawyerDashboard(array $user): bool {
    return userCanUseLawyerMode($user);
}

function currentLawyerDashboardUser(): ?array {
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if (!$user || !userCanAccessLawyerDashboard((array)$user)) {
            return null;
        }
        return (array)$user;
    } catch (Throwable $e) {
        return null;
    }
}

function lawyerWorkspaceContext(PDO $pdo, array $lawyer): array {
    $teamState = lawyerTeamState($pdo, $lawyer);
    $team = (array)($teamState['team'] ?? []);
    $teamId = (int)($team['id'] ?? 0);
    return [
        'team_id' => $teamId > 0 ? $teamId : null,
        'team_state' => $teamState,
        'workspace_label' => $teamId > 0 ? trim((string)($team['nombre'] ?? 'Team jurídico')) : 'Workspace personal',
    ];
}

function leadWorkspaceScope(array $workspace, int $lawyerId, string $alias = ''): array {
    $p = $alias !== '' ? rtrim($alias, '.') . '.' : '';
    static $hasEquipoColumn = null;
    if ($hasEquipoColumn === null) {
        try {
            $hasEquipoColumn = dbColumnExists('contactos_revelados', 'equipo_id');
        } catch (Throwable $e) {
            $hasEquipoColumn = false;
        }
    }
    if (!empty($workspace['team_id']) && $hasEquipoColumn) {
        return [
            'sql' => $p . 'equipo_id = ?',
            'params' => [(int)$workspace['team_id']],
        ];
    }
    if ($hasEquipoColumn) {
        return [
            'sql' => $p . 'abogado_id = ? AND ' . $p . 'equipo_id IS NULL',
            'params' => [$lawyerId],
        ];
    }
    return [
        'sql' => $p . 'abogado_id = ?',
        'params' => [$lawyerId],
    ];
}

function compactWorkflowDeltaLabel(?int $targetTs, ?int $nowTs = null): ?string {
    if (!$targetTs) {
        return null;
    }
    $nowTs = $nowTs ?? time();
    $diff = $targetTs - $nowTs;
    $abs = abs($diff);
    if ($abs < 3600) {
        $units = max(1, (int)ceil($abs / 60));
        $label = $units . ' min';
    } elseif ($abs < 86400) {
        $units = max(1, (int)ceil($abs / 3600));
        $label = $units . ' h';
    } else {
        $units = max(1, (int)ceil($abs / 86400));
        $label = $units . ' d';
    }
    return $diff >= 0 ? 'vence en ' . $label : 'vencida hace ' . $label;
}

function workflowPriorityRank(string $priority): int {
    $priority = strtolower(trim($priority));
    return [
        'alta' => 1,
        'media' => 2,
        'baja' => 3,
        'cerrado' => 4,
    ][$priority] ?? 5;
}

function leadWorkflowSnapshot(array $lead, ?int $nowTs = null): array {
    $nowTs = $nowTs ?? time();
    $createdTs = strtotime((string)($lead['fecha_revelado'] ?? $lead['cliente_created_at'] ?? $lead['created_at'] ?? '')) ?: null;
    $updatedTs = strtotime((string)($lead['estado_updated_at'] ?? '')) ?: $createdTs;
    $state = strtoupper(trim((string)($lead['estado'] ?? 'PENDIENTE')));

    $snapshot = [
        'code' => 'idle',
        'title' => 'Sin tarea automática',
        'body' => 'No hay automatización pendiente para este lead.',
        'priority' => 'Baja',
        'task_active' => false,
        'due_ts' => null,
        'due_label' => null,
        'cta' => 'Abrir lead',
    ];

    if ($state === 'PENDIENTE' && $createdTs) {
        $dueTs = $createdTs + 7200;
        $snapshot = [
            'code' => 'lead_first_touch',
            'title' => $dueTs <= $nowTs ? 'Responder ahora' : 'Primer contacto en 2 horas',
            'body' => $dueTs <= $nowTs
                ? 'Lead nuevo sin respuesta. Riesgo directo de pérdida por velocidad.'
                : 'Haz el primer contacto antes de que el lead se enfríe.',
            'priority' => 'Alta',
            'task_active' => true,
            'due_ts' => $dueTs,
            'due_label' => compactWorkflowDeltaLabel($dueTs, $nowTs),
            'cta' => 'Contactar',
        ];
    } elseif ($state === 'CONTACTADO' && $updatedTs) {
        $dueTs = $updatedTs + 86400;
        $snapshot = [
            'code' => 'lead_followup',
            'title' => $dueTs <= $nowTs ? 'Seguimiento vencido' : 'Seguimiento en 24 horas',
            'body' => $dueTs <= $nowTs
                ? 'El lead fue movido, pero ya pide seguimiento comercial.'
                : 'Conviene insistir o mover a cotización antes de 24 horas.',
            'priority' => $dueTs <= $nowTs ? 'Alta' : 'Media',
            'task_active' => true,
            'due_ts' => $dueTs,
            'due_label' => compactWorkflowDeltaLabel($dueTs, $nowTs),
            'cta' => 'Seguir',
        ];
    } elseif ($state === 'GANADO' && $updatedTs) {
        $dueTs = $updatedTs + 86400;
        $snapshot = [
            'code' => 'lead_onboarding',
            'title' => 'Aterrizar servicio y cobro',
            'body' => 'Lead ganado. Conviene confirmar pago, alcance y siguiente hito operativo.',
            'priority' => 'Media',
            'task_active' => true,
            'due_ts' => $dueTs,
            'due_label' => compactWorkflowDeltaLabel($dueTs, $nowTs),
            'cta' => 'Ordenar cierre',
        ];
    } elseif (in_array($state, ['PERDIDO', 'CANCELADO'], true)) {
        $snapshot = [
            'code' => 'lead_closed',
            'title' => 'Lead fuera de operación',
            'body' => 'No requiere tarea automática salvo reapertura manual.',
            'priority' => 'Baja',
            'task_active' => false,
            'due_ts' => null,
            'due_label' => null,
            'cta' => 'Ver lead',
        ];
    }

    return $snapshot;
}

function quoteWorkflowSnapshot(array $quote, ?int $nowTs = null): array {
    $nowTs = $nowTs ?? time();
    $createdTs = strtotime((string)($quote['created_at'] ?? '')) ?: null;
    $updatedTs = strtotime((string)($quote['updated_at'] ?? '')) ?: $createdTs;
    $state = strtoupper(trim((string)($quote['estado'] ?? 'BORRADOR')));

    $snapshot = [
        'code' => 'idle',
        'title' => 'Sin tarea automática',
        'body' => 'No hay automatización pendiente para esta cotización.',
        'priority' => 'Baja',
        'task_active' => false,
        'due_ts' => null,
        'due_label' => null,
        'cta' => 'Abrir',
    ];

    if ($state === 'BORRADOR' && $updatedTs) {
        $dueTs = $updatedTs + 86400;
        $snapshot = [
            'code' => 'quote_send',
            'title' => $dueTs <= $nowTs ? 'Enviar propuesta ahora' : 'Enviar propuesta hoy',
            'body' => $dueTs <= $nowTs
                ? 'El borrador ya quedó atrasado. Conviene enviarlo antes de que se enfríe.'
                : 'La cotización está lista para salir. Empuja cierre hoy.',
            'priority' => 'Alta',
            'task_active' => true,
            'due_ts' => $dueTs,
            'due_label' => compactWorkflowDeltaLabel($dueTs, $nowTs),
            'cta' => 'Enviar',
        ];
    } elseif ($state === 'ENVIADA' && $updatedTs) {
        $dueTs = $updatedTs + 172800;
        $snapshot = [
            'code' => 'quote_followup',
            'title' => $dueTs <= $nowTs ? 'Seguimiento comercial vencido' : 'Seguimiento en 48 horas',
            'body' => $dueTs <= $nowTs
                ? 'La propuesta fue enviada y ya pide insistencia comercial.'
                : 'Haz seguimiento antes de 48 horas para no perder tracción.',
            'priority' => $dueTs <= $nowTs ? 'Alta' : 'Media',
            'task_active' => true,
            'due_ts' => $dueTs,
            'due_label' => compactWorkflowDeltaLabel($dueTs, $nowTs),
            'cta' => 'Seguir',
        ];
    } elseif ($state === 'ACEPTADA' && $updatedTs) {
        $dueTs = $updatedTs + 86400;
        $snapshot = [
            'code' => 'quote_convert',
            'title' => 'Convertir aceptación en trabajo',
            'body' => 'La propuesta ya fue aceptada. Coordina anticipo, servicio y arranque.',
            'priority' => 'Media',
            'task_active' => true,
            'due_ts' => $dueTs,
            'due_label' => compactWorkflowDeltaLabel($dueTs, $nowTs),
            'cta' => 'Coordinar',
        ];
    }

    return $snapshot;
}

function quoteCollectionWorkflowSnapshot(array $quote, ?int $nowTs = null): array {
    $nowTs = $nowTs ?? time();
    $state = strtoupper(trim((string)($quote['estado'] ?? 'BORRADOR')));
    $collectionState = strtoupper(trim((string)($quote['cobro_estado_resuelto'] ?? $quote['cobro_estado'] ?? 'SIN_GESTION')));
    $updatedTs = strtotime((string)($quote['cobro_updated_at'] ?? $quote['updated_at'] ?? $quote['created_at'] ?? '')) ?: null;
    $anticipo = sanitizeMoneyAmount($quote['anticipo'] ?? 0);
    $porCobrar = max(0, sanitizeMoneyAmount($quote['por_cobrar_monto'] ?? 0));

    $snapshot = [
        'code' => 'collection_idle',
        'title' => 'Cobranza sin mover',
        'body' => 'Todavía no hay una acción de caja asociada a esta cotización.',
        'priority' => 'Baja',
        'task_active' => false,
        'due_ts' => null,
        'due_label' => null,
        'cta' => 'Abrir',
    ];

    if ($state !== 'ACEPTADA') {
        return [
            'code' => 'collection_wait_acceptance',
            'title' => 'Cobranza se activa al aceptar',
            'body' => 'Primero hay que convertir la propuesta; después entra el flujo de anticipo y saldo.',
            'priority' => 'Baja',
            'task_active' => false,
            'due_ts' => null,
            'due_label' => null,
            'cta' => 'Esperar decisión',
        ];
    }

    if ($collectionState === 'PAGADA' || $porCobrar <= 0) {
        return [
            'code' => 'collection_paid',
            'title' => 'Caja cerrada',
            'body' => 'La cotización ya quedó cobrada por completo.',
            'priority' => 'Cerrado',
            'task_active' => false,
            'due_ts' => null,
            'due_label' => null,
            'cta' => 'Pagada',
        ];
    }

    if ($collectionState === 'ANTICIPO') {
        $dueTs = $updatedTs ? ($updatedTs + 86400) : null;
        return [
            'code' => 'collection_balance_due',
            'title' => 'Cobrar saldo',
            'body' => 'Ya entró anticipo. Falta bajar a caja el saldo restante de ' . formatClpAmount($porCobrar) . '.',
            'priority' => $dueTs && $dueTs <= $nowTs ? 'Alta' : 'Media',
            'task_active' => true,
            'due_ts' => $dueTs,
            'due_label' => compactWorkflowDeltaLabel($dueTs, $nowTs),
            'cta' => 'Cobrar saldo',
        ];
    }

    $dueTs = $updatedTs ? ($updatedTs + 86400) : null;
    return [
        'code' => 'collection_retainer_due',
        'title' => $anticipo > 0 ? 'Cobrar anticipo' : 'Cobrar propuesta aceptada',
        'body' => $anticipo > 0
            ? 'La propuesta ya fue aceptada. Conviene mover el anticipo de ' . formatClpAmount($anticipo) . ' para arrancar ordenadamente.'
            : 'La propuesta fue aceptada y todavía no baja a caja. Conviene mover cobro ahora.',
        'priority' => $dueTs && $dueTs <= $nowTs ? 'Alta' : 'Media',
        'task_active' => true,
        'due_ts' => $dueTs,
        'due_label' => compactWorkflowDeltaLabel($dueTs, $nowTs),
        'cta' => $anticipo > 0 ? 'Cobrar anticipo' : 'Cobrar ahora',
    ];
}

function sanitizeMoneyAmount($value): float {
    if (is_string($value)) {
        $value = trim($value);
        $value = str_replace(['$', ' '], '', $value);
        if (strpos($value, ',') !== false && strpos($value, '.') !== false) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (strpos($value, ',') !== false) {
            $value = str_replace(',', '.', $value);
        }
    }
    $n = is_numeric($value) ? (float)$value : 0.0;
    if (!is_finite($n)) {
        return 0.0;
    }
    return round(max(0, $n), 2);
}

function normalizeOptionalWhatsapp($numero): ?string {
    $numero = preg_replace('/[^0-9]/', '', (string)$numero);
    if ($numero === '') {
        return null;
    }
    if (strlen($numero) === 11 && str_starts_with($numero, '56')) {
        $numero = substr($numero, 2);
    }
    return validarWhatsApp($numero) ?: null;
}

function formatClpAmount($amount): string {
    return '$' . number_format((float)$amount, 0, ',', '.');
}

function lawyerQuoteBrandingSettings(array $lawyer): array {
    $email = strtolower(trim((string)($lawyer['email'] ?? '')));
    $isGabriel = $email === 'gmcalderonlewin@gmail.com';
    return [
        'enabled' => ((int)($lawyer['quote_branding_enabled'] ?? ($isGabriel ? 1 : 0))) === 1,
        'brand_name' => trim((string)($lawyer['quote_brand_name'] ?? ($isGabriel ? 'FLOCID' : ''))),
        'legal_name' => trim((string)($lawyer['quote_brand_legal_name'] ?? ($isGabriel ? 'Defensa y Asesoria Juridica SpA' : ''))),
        'rut' => trim((string)($lawyer['quote_brand_rut'] ?? ($isGabriel ? '78.312.211-1' : ''))),
        'phone' => trim((string)($lawyer['quote_brand_phone'] ?? ($isGabriel ? '+56 9 2936 5362 (oficina)' : ''))),
        'email' => trim((string)($lawyer['quote_brand_email'] ?? ($isGabriel ? 'notificaciones.dgfc@gmail.com' : ''))),
        'address' => trim((string)($lawyer['quote_brand_address'] ?? '')),
        'legal_notice' => trim((string)($lawyer['quote_brand_legal_notice'] ?? ($isGabriel ? '© Liberades.cl - 2026 - Todos los derechos reservados · Aviso legal' : ''))),
    ];
}

function lawyerSubscriptionState(array $lawyer): array {
    $email = strtolower(trim((string)($lawyer['email'] ?? '')));
    $plan = strtolower(trim((string)($lawyer['subscription_plan'] ?? '')));
    $status = strtolower(trim((string)($lawyer['subscription_status'] ?? '')));

    if ($plan === '') {
        $plan = userCanAccessLawyerDashboard($lawyer) ? 'pro_founder' : 'free';
    }
    if ($status === '') {
        $status = userCanAccessLawyerDashboard($lawyer) ? 'active' : 'inactive';
    }

    $renewsAt = trim((string)($lawyer['subscription_renews_at'] ?? ''));
    $trialEndsAt = trim((string)($lawyer['subscription_trial_ends_at'] ?? ''));
    $startedAt = trim((string)($lawyer['subscription_started_at'] ?? ''));
    $contactEmail = 'contacto@example.com';

    $planMap = [
        'free' => [
            'label' => 'Free',
            'badge' => 'Gratis',
            'price' => '$0',
            'headline' => 'Perfil público y acceso básico.',
            'status_note' => 'Plan sin automatizaciones ni módulos avanzados.',
            'cta_label' => 'Pasar a PRO',
            'cta_href' => 'mailto:' . $contactEmail . '?subject=' . rawurlencode('Quiero activar Tu Estudio Juridico PRO'),
            'tone' => 'free',
        ],
        'pro' => [
            'label' => 'PRO',
            'badge' => 'PRO',
            'price' => '$39.990/mes',
            'headline' => 'CRM, cotizador, branding y analytics.',
            'status_note' => 'Plan profesional mensual activo.',
            'cta_label' => 'Administrar plan',
            'cta_href' => 'mailto:' . $contactEmail . '?subject=' . rawurlencode('Gestion de plan Tu Estudio Juridico PRO'),
            'tone' => 'pro',
        ],
        'pro_founder' => [
            'label' => 'PRO Founder',
            'badge' => 'Founder',
            'price' => '$0 temporal',
            'headline' => 'Acceso completo mientras se valida el modelo comercial.',
            'status_note' => 'Plan fundador con acceso premium sin cobro activo.',
            'cta_label' => 'Hablar sobre plan',
            'cta_href' => 'mailto:' . $contactEmail . '?subject=' . rawurlencode('Consulta por plan Tu Estudio Juridico PRO Founder'),
            'tone' => 'founder',
        ],
        'team' => [
            'label' => 'Team',
            'badge' => 'Team',
            'price' => '$89.990/mes',
            'headline' => 'Colaboración, workspace compartido y control por equipo.',
            'status_note' => 'Plan orientado a estudios y equipos jurídicos.',
            'cta_label' => 'Escalar a Team',
            'cta_href' => 'mailto:' . $contactEmail . '?subject=' . rawurlencode('Quiero activar Tu Estudio Juridico Team'),
            'tone' => 'team',
        ],
    ];
    $current = $planMap[$plan] ?? $planMap['pro_founder'];

    $renewalLabel = 'Sin cobro activo';
    if ($renewsAt !== '') {
        $ts = strtotime($renewsAt);
        if ($ts) $renewalLabel = 'Renueva ' . date('d/m/Y', $ts);
    } elseif ($trialEndsAt !== '') {
        $ts = strtotime($trialEndsAt);
        if ($ts) $renewalLabel = 'Trial hasta ' . date('d/m/Y', $ts);
    } elseif ($status === 'active' && $plan === 'pro') {
        $renewalLabel = 'Plan activo';
    }

    $statusLabelMap = [
        'active' => 'Activa',
        'trialing' => 'En trial',
        'past_due' => 'Pago pendiente',
        'canceled' => 'Cancelada',
        'inactive' => 'Inactiva',
    ];

    return [
        'plan_key' => $plan,
        'plan_label' => $current['label'],
        'badge' => $current['badge'],
        'price_label' => $current['price'],
        'headline' => $current['headline'],
        'status' => $status,
        'status_label' => $statusLabelMap[$status] ?? 'Activa',
        'status_note' => $current['status_note'],
        'renewal_label' => $renewalLabel,
        'cta_label' => $current['cta_label'],
        'cta_href' => $current['cta_href'],
        'tone' => $current['tone'],
        'contact_email' => $contactEmail,
        'started_at' => $startedAt,
        'features' => [
            'Leads y CRM',
            'Cotizador y PDF',
            'Firma comercial',
            'Métricas operativas',
        ],
        'plans' => [
            [
                'key' => 'free',
                'label' => 'Free',
                'price' => '$0',
                'summary' => 'Perfil público y presencia básica.',
                'features' => ['Perfil público', 'Explorar', 'Acceso básico'],
            ],
            [
                'key' => 'pro',
                'label' => 'PRO',
                'price' => '$39.990/mes',
                'summary' => 'Operación comercial diaria para abogado individual.',
                'features' => ['Leads', 'Cotizaciones', 'PDF', 'Marca propia', 'Analytics'],
            ],
            [
                'key' => 'team',
                'label' => 'Team',
                'price' => '$89.990/mes',
                'summary' => 'Colaboración y workspace compartido para estudio jurídico.',
                'features' => ['Todo PRO', 'Equipo jurídico', 'Roles', 'Workspace compartido'],
            ],
        ],
    ];
}

function teamSlugify(string $value): string {
    $slug = strtolower(trim($value));
    if ($slug === '') return 'team';
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim((string)$slug, '-');
    return $slug !== '' ? $slug : 'team';
}

function ensureUniqueTeamSlug(PDO $pdo, string $baseSlug, int $ignoreTeamId = 0): string {
    $slug = $baseSlug !== '' ? $baseSlug : 'team';
    $candidate = $slug;
    $suffix = 2;
    while (true) {
        $sql = "SELECT id FROM abogado_equipos WHERE slug = ?";
        $params = [$candidate];
        if ($ignoreTeamId > 0) {
            $sql .= " AND id <> ?";
            $params[] = $ignoreTeamId;
        }
        $st = $pdo->prepare($sql . " LIMIT 1");
        $st->execute($params);
        if (!(int)$st->fetchColumn()) {
            return $candidate;
        }
        $candidate = $slug . '-' . $suffix;
        $suffix++;
    }
}

function syncLawyerTeamMembership(PDO $pdo, array $lawyer): void {
    $lawyerId = (int)($lawyer['id'] ?? 0);
    $email = strtolower(trim((string)($lawyer['email'] ?? '')));
    if ($lawyerId <= 0 || $email === '') return;
    try {
        $st = $pdo->prepare("
            SELECT id
            FROM abogado_equipo_miembros
            WHERE LOWER(email) = ?
              AND estado = 'pending'
            ORDER BY id ASC
        ");
        $st->execute([$email]);
        $rows = $st->fetchAll() ?: [];
        foreach ($rows as $row) {
            $pdo->prepare("
                UPDATE abogado_equipo_miembros
                SET abogado_id = ?, estado = 'active', joined_at = COALESCE(joined_at, NOW())
                WHERE id = ?
            ")->execute([$lawyerId, (int)($row['id'] ?? 0)]);
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function lawyerTeamState(PDO $pdo, array $lawyer): array {
    $lawyerId = (int)($lawyer['id'] ?? 0);
    $email = strtolower(trim((string)($lawyer['email'] ?? '')));
    $result = [
        'team' => null,
        'membership' => null,
        'members' => [],
        'pending_invites' => [],
        'can_manage' => false,
    ];
    if ($lawyerId <= 0 && $email === '') return $result;
    try {
        $stMembership = $pdo->prepare("
            SELECT tm.*, t.nombre AS team_name, t.slug AS team_slug, t.owner_abogado_id, t.activo AS team_active
            FROM abogado_equipo_miembros tm
            INNER JOIN abogado_equipos t ON t.id = tm.equipo_id
            WHERE (tm.abogado_id = ? OR LOWER(tm.email) = ?)
              AND t.activo = 1
            ORDER BY FIELD(tm.estado, 'active', 'pending', 'revoked'), tm.id ASC
            LIMIT 1
        ");
        $stMembership->execute([$lawyerId, $email]);
        $membership = $stMembership->fetch() ?: null;
        if (!$membership) {
            return $result;
        }
        $teamId = (int)($membership['equipo_id'] ?? 0);
        if ($teamId <= 0) return $result;
        $stTeam = $pdo->prepare("SELECT * FROM abogado_equipos WHERE id = ? LIMIT 1");
        $stTeam->execute([$teamId]);
        $team = $stTeam->fetch() ?: null;
        if (!$team) return $result;

        $result['team'] = $team;
        $result['membership'] = $membership;
        $result['can_manage'] = ((int)($team['owner_abogado_id'] ?? 0) === $lawyerId) || in_array((string)($membership['rol'] ?? ''), ['owner', 'admin'], true);

        $stMembers = $pdo->prepare("
            SELECT tm.*, a.nombre AS abogado_nombre, a.email AS abogado_email
            FROM abogado_equipo_miembros tm
            LEFT JOIN abogados a ON a.id = tm.abogado_id
            WHERE tm.equipo_id = ?
            ORDER BY FIELD(tm.estado, 'active', 'pending', 'revoked'), FIELD(tm.rol, 'owner', 'admin', 'member'), tm.created_at ASC
        ");
        $stMembers->execute([$teamId]);
        $allMembers = $stMembers->fetchAll() ?: [];
        foreach ($allMembers as $member) {
            if (($member['estado'] ?? 'pending') === 'active') {
                $result['members'][] = $member;
            } else {
                $result['pending_invites'][] = $member;
            }
        }
    } catch (Throwable $e) {
        return $result;
    }
    return $result;
}

function recordLawyerTeamActivity(PDO $pdo, ?int $teamId, int $actorLawyerId, string $action, string $targetType, int $targetId, string $title, ?string $meta = null): void {
    $teamId = (int)$teamId;
    if ($teamId <= 0 || $actorLawyerId <= 0 || $targetId <= 0) {
        return;
    }
    ensureLawyerTeamTables();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO abogado_equipo_actividad
                (equipo_id, actor_abogado_id, action_key, target_type, target_id, title, meta, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $teamId,
            $actorLawyerId,
            substr(trim($action), 0, 60),
            substr(trim($targetType), 0, 30),
            $targetId,
            substr(trim($title), 0, 190),
            $meta !== null && trim($meta) !== '' ? substr(trim($meta), 0, 255) : null,
        ]);
    } catch (Throwable $e) {
        // fail-open
    }
}

function quoteStatusMeta(string $estado): array {
    $estado = strtoupper(trim($estado));
    $map = [
        'BORRADOR' => ['label' => 'Borrador', 'class' => 'status-pendiente'],
        'ENVIADA' => ['label' => 'Enviada', 'class' => 'status-contactado'],
        'ACEPTADA' => ['label' => 'Aceptada', 'class' => 'status-ganado'],
        'RECHAZADA' => ['label' => 'Rechazada', 'class' => 'status-perdido'],
        'ANULADA' => ['label' => 'Anulada', 'class' => 'status-cancelado'],
    ];
    return $map[$estado] ?? $map['BORRADOR'];
}

function quoteCollectionMeta(string $estado): array {
    $estado = strtoupper(trim($estado));
    $map = [
        'SIN_GESTION' => ['label' => 'Sin gestión', 'class' => 'status-pendiente'],
        'PENDIENTE' => ['label' => 'Por cobrar', 'class' => 'status-contactado'],
        'ANTICIPO' => ['label' => 'Anticipo recibido', 'class' => 'status-ganado'],
        'PAGADA' => ['label' => 'Pagada', 'class' => 'status-ganado'],
    ];
    return $map[$estado] ?? $map['SIN_GESTION'];
}

function buildLawyerQuoteMessage(array $lawyer, array $quote): string {
    $lines = [];
    $quoteId = (int)($quote['id'] ?? 0);
    $quoteItems = [];
    $rawItems = $quote['quote_items_json'] ?? ($quote['quote_items'] ?? null);
    if (is_string($rawItems) && $rawItems !== '') {
        $decoded = json_decode($rawItems, true);
        if (is_array($decoded)) $quoteItems = $decoded;
    } elseif (is_array($rawItems)) {
        $quoteItems = $rawItems;
    }
    $lines[] = 'COTIZACION LEGAL' . ($quoteId > 0 ? ' #' . $quoteId : '');
    $lines[] = '';
    $lines[] = 'Cliente: ' . trim((string)($quote['client_name'] ?? 'Cliente'));
    $lines[] = 'Servicio: ' . trim((string)($quote['asunto'] ?? 'Servicio legal'));
    if (trim((string)($quote['materia'] ?? '')) !== '') {
        $lines[] = 'Materia: ' . trim((string)$quote['materia']);
    }
    if (trim((string)($quote['plazo_estimado'] ?? '')) !== '') {
        $lines[] = 'Plazo estimado: ' . trim((string)$quote['plazo_estimado']);
    }
    if (trim((string)($quote['vigencia'] ?? '')) !== '') {
        $lines[] = 'Vigencia: ' . trim((string)$quote['vigencia']);
    }
    $lines[] = '';
    if (!empty($quoteItems)) {
        $lines[] = 'SERVICIOS BASE';
        foreach ($quoteItems as $item) {
            $name = trim((string)($item['nombre'] ?? $item['name'] ?? 'Servicio'));
            $qty = max(1, (int)($item['qty'] ?? $item['cantidad'] ?? 1));
            $price = $item['precio_base'] ?? $item['price'] ?? null;
            $costs = $item['gastos_base'] ?? $item['costs'] ?? null;
            $line = '- ' . $name . ' x' . $qty;
            if ($price !== null || $costs !== null) {
                $line .= ' (' . formatClpAmount($price ?? 0) . ' + ' . formatClpAmount($costs ?? 0) . ')';
            }
            $lines[] = $line;
        }
        $lines[] = '';
    }
    $lines[] = 'DETALLE';
    $lines[] = trim((string)($quote['detalle'] ?? 'Por definir.'));
    if (trim((string)($quote['no_incluye'] ?? '')) !== '') {
        $lines[] = '';
        $lines[] = 'NO INCLUYE';
        $lines[] = trim((string)$quote['no_incluye']);
    }
    $lines[] = '';
    $lines[] = 'DETALLE ECONOMICO';
    $lines[] = '- Honorarios: ' . formatClpAmount($quote['honorarios'] ?? 0);
    $lines[] = '- Gastos: ' . formatClpAmount($quote['gastos'] ?? 0);
    $lines[] = '- Descuento: ' . formatClpAmount($quote['descuento'] ?? 0);
    $lines[] = '- Total: ' . formatClpAmount($quote['total'] ?? 0);
    $lines[] = '- Anticipo: ' . formatClpAmount($quote['anticipo'] ?? 0);
    $lines[] = '- Saldo: ' . formatClpAmount($quote['saldo'] ?? 0);
    if (trim((string)($quote['condiciones_pago'] ?? '')) !== '') {
        $lines[] = '- Forma de pago: ' . trim((string)$quote['condiciones_pago']);
    }
    if (trim((string)($quote['payment_link'] ?? '')) !== '') {
        $lines[] = '- Link de pago: ' . trim((string)$quote['payment_link']);
    }
    if (trim((string)($quote['notas'] ?? '')) !== '') {
        $lines[] = '';
        $lines[] = 'NOTAS';
        $lines[] = trim((string)$quote['notas']);
    }
    $lawyerName = trim((string)($lawyer['nombre'] ?? 'Abogado/a'));
    $lawyerEmail = trim((string)($lawyer['email'] ?? ''));
    $lawyerWhatsapp = trim((string)($lawyer['whatsapp'] ?? ''));
    $lines[] = '';
    $lines[] = 'CONTACTO';
    $lines[] = $lawyerName !== '' ? $lawyerName : 'Abogado/a';
    if ($lawyerWhatsapp !== '') {
        $lines[] = 'WhatsApp: +56 ' . $lawyerWhatsapp;
    }
    if ($lawyerEmail !== '') {
        $lines[] = 'Email: ' . $lawyerEmail;
    }
    $branding = lawyerQuoteBrandingSettings($lawyer);
    if (!empty($branding['enabled'])) {
        $lines[] = '';
        if ($branding['brand_name'] !== '') {
            $lines[] = $branding['brand_name'];
        }
        if ($branding['legal_name'] !== '') {
            $lines[] = $branding['legal_name'];
        }
        if ($branding['rut'] !== '') {
            $lines[] = 'RUT ' . $branding['rut'];
        }
        $contactLines = [];
        if ($branding['phone'] !== '') {
            $contactLines[] = '📞 ' . $branding['phone'];
        }
        if ($branding['email'] !== '') {
            $contactLines[] = '📧 ' . $branding['email'];
        }
        if ($branding['address'] !== '') {
            $contactLines[] = $branding['address'];
        }
        if (!empty($contactLines)) {
            $lines[] = '';
            $lines[] = 'Contacto';
            foreach ($contactLines as $contactLine) {
                $lines[] = $contactLine;
            }
        }
        if ($branding['legal_notice'] !== '') {
            $lines[] = '';
            $lines[] = $branding['legal_notice'];
        }
    }
    return implode("\n", $lines);
}

function lawyerProfileCompletionPercent(array $profile): int {
    $checks = [];
    $checks[] = trim((string)($profile['whatsapp'] ?? '')) !== '';
    $checks[] = trim((string)($profile['especialidad'] ?? '')) !== '';
    $checks[] = trim((string)($profile['slug'] ?? '')) !== '';
    $checks[] = trim((string)($profile['universidad'] ?? '')) !== '';
    $checks[] = trim((string)($profile['experiencia'] ?? '')) !== '' || !empty($profile['anio_titulacion']);
    $bio = trim((string)($profile['biografia'] ?? ''));
    $bioLen = function_exists('mb_strlen') ? mb_strlen($bio, 'UTF-8') : strlen($bio);
    $checks[] = $bioLen >= 1;
    $checks[] = !empty($profile['cobertura_nacional']) || trim((string)($profile['regiones_servicio'] ?? '')) !== '' || trim((string)($profile['comunas_servicio'] ?? '')) !== '';
    $checks[] = trim((string)($profile['sexo'] ?? '')) !== '';
    $checks[] = (trim((string)($profile['instagram'] ?? '')) !== '' || trim((string)($profile['tiktok'] ?? '')) !== '' || trim((string)($profile['web'] ?? '')) !== '' || trim((string)($profile['facebook'] ?? '')) !== '' || trim((string)($profile['linkedin'] ?? '')) !== '');
    $subOk = false;
    foreach (['submaterias','submaterias_secundarias'] as $k) {
        $v = $profile[$k] ?? null;
        if (is_string($v) && trim($v) !== '') {
            $d = json_decode($v, true);
            if (is_array($d) && count(array_filter(array_map('strval',$d))) > 0) { $subOk = true; break; }
        } elseif (is_array($v) && count($v) > 0) { $subOk = true; break; }
    }
    $checks[] = $subOk || trim((string)($profile['materia_secundaria'] ?? '')) !== '';
    $filled = 0; foreach ($checks as $c) if ($c) $filled++;
    return (int) round(($filled / max(1, count($checks))) * 100);
}

function lawyerProfileCompletionChecklist(array $profile): array {
    $bio = trim((string)($profile['biografia'] ?? ''));
    $bioLen = function_exists('mb_strlen') ? mb_strlen($bio, 'UTF-8') : strlen($bio);
    $hasSub = false;
    foreach (['submaterias','submaterias_secundarias'] as $k) {
        $v = $profile[$k] ?? null;
        if (is_string($v) && trim($v) !== '') {
            $d = json_decode($v, true);
            if (is_array($d) && count(array_filter(array_map('strval',$d))) > 0) { $hasSub = true; break; }
        }
    }
    return [
        'whatsapp' => trim((string)($profile['whatsapp'] ?? '')) !== '',
        'materia' => trim((string)($profile['especialidad'] ?? '')) !== '',
        'submaterias' => $hasSub || trim((string)($profile['materia_secundaria'] ?? '')) !== '',
        'universidad' => trim((string)($profile['universidad'] ?? '')) !== '',
        'experiencia' => (trim((string)($profile['experiencia'] ?? '')) !== '' || !empty($profile['anio_titulacion'])),
        'cobertura' => !empty($profile['cobertura_nacional']) || trim((string)($profile['regiones_servicio'] ?? '')) !== '' || trim((string)($profile['comunas_servicio'] ?? '')) !== '',
        'sexo' => trim((string)($profile['sexo'] ?? '')) !== '',
        'links' => (trim((string)($profile['instagram'] ?? '')) !== '' || trim((string)($profile['tiktok'] ?? '')) !== '' || trim((string)($profile['web'] ?? '')) !== '' || trim((string)($profile['facebook'] ?? '')) !== '' || trim((string)($profile['linkedin'] ?? '')) !== ''),
        'bio_opcional' => ($bioLen <= 300),
    ];
}

function preferredSessionRoleFromUser(array $user) {
    if (userCanUseLawyerMode($user) && !userCanPublishCases($user)) {
        return 'abogado';
    }
    return 'cliente';
}

function adminEmails() {
    // Admin por correo deshabilitado: usar solo sesión /admin-login.
    return [];
}

function userIsAdminByEmail(?array $user) {
    // Compatibilidad legacy: siempre false.
    return false;
}

function adminCredentials(): array {
    return [
        'username' => getenv('LAWYERS_ADMIN_USER') ?: 'admin',
        'password' => getenv('LAWYERS_ADMIN_PASS') ?: 'LawyersAdmin2026!',
    ];
}

function demoModeSnapshotPath(): string {
    return '/tmp/lawyers_demo_snapshot.json';
}

function demoModeFlagPath(): string {
    return '/tmp/lawyers_demo_mode.flag';
}

function isDemoModeActive(): bool {
    return is_file(demoModeFlagPath());
}

function dbTableExists(string $table): bool {
    static $cache = [];
    if (array_key_exists($table, $cache)) return $cache[$table];
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ");
        $stmt->execute([$table]);
        $cache[$table] = ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        $cache[$table] = false;
    }
    return $cache[$table];
}

function demoModeTrackedTables(): array {
    $candidates = [
        'abogados',
        'contactos_revelados',
        'abogado_likes',
        'abogado_recomendaciones',
        'abogado_views_unicas',
        'abogado_views_lawyer_unicas',
    ];
    return array_values(array_filter($candidates, fn($t) => dbTableExists($t)));
}

function demoModeSnapshotTables(PDO $pdo): array {
    $tables = demoModeTrackedTables();
    $snapshot = [
        'created_at' => date('c'),
        'tables' => [],
    ];
    foreach ($tables as $table) {
        try {
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll() ?: [];
            $snapshot['tables'][$table] = $rows;
        } catch (Throwable $e) {
            $snapshot['tables'][$table] = [];
        }
    }
    $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        throw new RuntimeException('No se pudo serializar snapshot demo.');
    }
    $w1 = @file_put_contents(demoModeSnapshotPath(), $json);
    if ($w1 === false) {
        throw new RuntimeException('No se pudo escribir snapshot demo en disco.');
    }
    $w2 = @file_put_contents(demoModeFlagPath(), (string)time());
    if ($w2 === false) {
        throw new RuntimeException('No se pudo escribir flag de modo demo.');
    }
    return ['tables' => $tables, 'rows' => array_sum(array_map('count', $snapshot['tables']))];
}

function demoModeRestoreSnapshot(PDO $pdo): array {
    $path = demoModeSnapshotPath();
    if (!is_file($path)) {
        throw new RuntimeException('No existe snapshot de modo demo.');
    }
    $raw = file_get_contents($path);
    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded) || !is_array($decoded['tables'] ?? null)) {
        throw new RuntimeException('Snapshot demo inválido.');
    }
    $tables = array_keys($decoded['tables']);
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach (array_reverse($tables) as $table) {
            if (!dbTableExists($table)) continue;
            $pdo->exec("DELETE FROM `$table`");
        }
        foreach ($tables as $table) {
            if (!dbTableExists($table)) continue;
            $rows = $decoded['tables'][$table] ?? [];
            if (!is_array($rows) || empty($rows)) continue;
            foreach ($rows as $row) {
                if (!is_array($row) || empty($row)) continue;
                $cols = array_keys($row);
                $placeholders = implode(',', array_fill(0, count($cols), '?'));
                $sql = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES ($placeholders)";
                $pdo->prepare($sql)->execute(array_values($row));
            }
        }
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
    @unlink(demoModeFlagPath());
    return ['tables' => count($tables)];
}

function demoModeSeed(PDO $pdo, int $lawyerCount = 160, int $clientCount = 80): array {
    ensureLawyerSecondaryProfileColumns();
    ensureLeadLifecycleColumns();
    ensureActivityColumns();
    ensureLawyerIdentityRegistryTables();
    ensureLawyerQuoteBrandingColumns();
    ensureUniqueLikesTable();
    try {
        if (dbTableExists('abogado_recomendaciones')) {
            // no-op, just ensure table exists if route was not hit yet
        }
    } catch (Throwable $e) {}

    $tax = lawyerMateriasTaxonomia();
    $materias = lawyerMateriasCanonicas();
    $univers = array_values(array_unique(universidadesChile()));
    $regiones = array_values(array_unique(regionesChile()));
    $comunas = comunasChileCatalog();
    $comunasByRegion = [];
    foreach ($comunas as $c) {
        $r = trim((string)($c['region'] ?? ''));
        $co = trim((string)($c['comuna'] ?? ''));
        if ($r !== '' && $co !== '') $comunasByRegion[$r][] = $co;
    }
    foreach ($comunasByRegion as $r => $list) {
        $comunasByRegion[$r] = array_values(array_unique($list));
    }

    $maleNames = ['Gabriel','Cristobal','Sebastian','Matias','Felipe','Diego','Tomas','Benjamin','Joaquin','Ignacio','Vicente','Martin'];
    $femaleNames = ['Antonia','Camila','Valentina','Catalina','Martina','Javiera','Fernanda','Josefa','Constanza','Daniela','Paula','Francisca'];
    $lastNames = ['Rojas','Gonzalez','Muñoz','Diaz','Pereira','Castro','Silva','Nuñez','Torres','Molina','Contreras','Araya','Valdes','Espinoza','Morales','Soto','Sepulveda','Vargas'];
    $brandColors = ['azul','verde','dorado','rosa','vino','grafito'];
    $medios = ['efectivo','transferencia','tarjeta_credito','tarjeta_debito','crypto'];

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach (array_reverse(demoModeTrackedTables()) as $t) {
            $pdo->exec("DELETE FROM `$t`");
        }
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    $abCols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM abogados") as $r) $abCols[] = $r['Field'];
    $abColsSet = array_flip($abCols);
    $insertAb = function(array $data) use ($pdo, $abColsSet) {
        $row = [];
        foreach ($data as $k => $v) if (isset($abColsSet[$k])) $row[$k] = $v;
        if (empty($row)) return null;
        $cols = array_keys($row);
        $sql = "INSERT INTO abogados (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
        $pdo->prepare($sql)->execute(array_values($row));
        return (int)$pdo->lastInsertId();
    };

    $lawyerIds = [];
    $clientIds = [];
    for ($i = 1; $i <= $lawyerCount; $i++) {
        $isMale = ($i % 2) === 1;
        $nombre = ($isMale ? $maleNames[array_rand($maleNames)] : $femaleNames[array_rand($femaleNames)]) . ' ' . $lastNames[array_rand($lastNames)] . ' Test';
        $email = sprintf('abogado%03d-test@example.com', $i);
        $materia = $materias[array_rand($materias)];
        $subs = $tax[$materia] ?? ['Otro Tipo de Caso'];
        shuffle($subs);
        $subsMain = array_slice($subs, 0, min(3, count($subs)));
        $materia2 = $materias[array_rand($materias)];
        if ($materia2 === $materia) $materia2 = $materias[(array_rand($materias)+1) % count($materias)];
        $subs2 = $tax[$materia2] ?? [];
        shuffle($subs2);
        $subs2Pick = array_slice($subs2, 0, min(2, count($subs2)));
        $region = $regiones[array_rand($regiones)];
        $regionComs = $comunasByRegion[$region] ?? ['Santiago'];
        shuffle($regionComs);
        $plaza = array_slice($regionComs, 0, min(3, count($regionComs)));
        $comunaPrincipal = $plaza[0] ?? 'Santiago';
        $anios = random_int(3, 25);
        $anioT = (int)date('Y') - $anios;
        $slug = createSlug($nombre) . '-' . $i;
        $bio = "Abogado {$nombre}, licenciado de " . $univers[array_rand($univers)] . ", con más de {$anios} años de experiencia en {$materia}. Atiende " . ($region === 'Metropolitana de Santiago' ? 'Santiago' : $region) . " y coordina atención directa según urgencia del caso.";
        $faq = [
            ['q' => '¿Qué conviene tener a mano antes de contactar?', 'a' => 'Fechas clave, documentos y tu objetivo principal del caso.'],
            ['q' => '¿Atiendes urgencias?', 'a' => 'Sí, según disponibilidad y etapa procesal del caso.'],
        ];
        $data = [
            'rol' => 'abogado',
            'activo' => 1,
            'nombre' => $nombre,
            'email' => $email,
            'slug' => $slug,
            'especialidad' => $materia,
            'materia_secundaria' => $materia2,
            'submaterias' => json_encode($subsMain, JSON_UNESCAPED_UNICODE),
            'submaterias_secundarias' => json_encode($subs2Pick, JSON_UNESCAPED_UNICODE),
            'whatsapp' => '9' . str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'universidad' => $univers[array_rand($univers)],
            'experiencia' => $anios . ' años',
            'anio_titulacion' => $anioT,
            'biografia' => mb_substr($bio, 0, 300),
            'regiones_servicio' => $region,
            'comunas_servicio' => implode(', ', array_slice($regionComs, 0, min(5, count($regionComs)))),
            'ciudad' => $comunaPrincipal,
            'ciudades_plaza' => implode(', ', $plaza),
            'cobertura_nacional' => random_int(0, 100) < 35 ? 1 : 0,
            'sexo' => $isMale ? 'hombre' : 'mujer',
            'entrevista_presencial' => random_int(0, 100) < 70 ? 1 : 0,
            'instagram' => 'https://instagram.com/' . str_replace('-', '', $slug),
            'tiktok' => 'https://www.tiktok.com/@' . str_replace('-', '', $slug),
            'facebook' => 'https://facebook.com/' . str_replace('-', '', $slug),
            'linkedin' => 'https://linkedin.com/in/' . str_replace('-', '', $slug),
            'web' => 'https://www.' . str_replace('-', '', $slug) . '.cl',
            'exhibir_medios_pago' => 1,
            'medios_pago_json' => json_encode(array_slice($medios, 0, random_int(2, 4)), JSON_UNESCAPED_UNICODE),
            'foto_url' => 'https://randomuser.me/api/portraits/' . ($isMale ? 'men' : 'women') . '/' . (($i % 90) + 1) . '.jpg',
            'google_picture' => 'https://randomuser.me/api/portraits/' . ($isMale ? 'men' : 'women') . '/' . (($i % 90) + 1) . '.jpg',
            'abogado_habilitado' => 1,
            'abogado_verificado' => random_int(0,100) < 55 ? 1 : 0,
            'estado_verificacion_abogado' => 'pendiente',
            'rut_validacion_manual' => null,
            'solicito_habilitacion_abogado' => 1,
            'faq_personalizadas_json' => json_encode($faq, JSON_UNESCAPED_UNICODE),
            'color_marca' => $brandColors[array_rand($brandColors)],
            'likes' => random_int(0, 60),
            'vistas' => random_int(20, 1800),
            'last_seen_at' => date('Y-m-d H:i:s', time() - random_int(0, 72) * 3600),
            'audiencias_para_abogados_plaza' => random_int(0,100) < 45 ? 1 : 0,
            'created_at' => date('Y-m-d H:i:s', time() - random_int(5, 240) * 86400),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        if ($data['abogado_verificado']) {
            $data['estado_verificacion_abogado'] = 'verificado';
            $data['rut_validacion_manual'] = 'si';
            $data['fecha_verificacion_abogado'] = date('Y-m-d H:i:s', time() - random_int(1, 120) * 86400);
        }
        if (isset($abColsSet['destacado_hasta']) && random_int(0,100) < 12) {
            $data['destacado_hasta'] = date('Y-m-d H:i:s', time() + random_int(24, 24*30) * 3600);
        }
        $id = $insertAb($data);
        if ($id) $lawyerIds[] = $id;
    }

    for ($i = 1; $i <= $clientCount; $i++) {
        $nombre = $femaleNames[array_rand($femaleNames)] . ' ' . $lastNames[array_rand($lastNames)] . ' Cliente Test';
        $id = $insertAb([
            'rol' => 'cliente',
            'activo' => 1,
            'nombre' => $nombre,
            'email' => sprintf('cliente%03d-test@example.com', $i),
            'whatsapp' => '9' . str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'created_at' => date('Y-m-d H:i:s', time() - random_int(1, 180) * 86400),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($id) $clientIds[] = $id;
    }

    if (dbTableExists('contactos_revelados') && !empty($lawyerIds) && !empty($clientIds)) {
        $crCols = [];
        foreach ($pdo->query("SHOW COLUMNS FROM contactos_revelados") as $r) $crCols[] = $r['Field'];
        $crSet = array_flip($crCols);
        $insertCr = function(array $data) use ($pdo, $crSet) {
            $row = [];
            foreach ($data as $k => $v) if (isset($crSet[$k])) $row[$k] = $v;
            if (empty($row)) return;
            $cols = array_keys($row);
            $sql = "INSERT INTO contactos_revelados (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";
            $pdo->prepare($sql)->execute(array_values($row));
        };
        $states = ['PENDIENTE','CONTACTADO','GANADO','PERDIDO','CANCELADO'];
        $leadCount = min(1200, max(240, (int)round($lawyerCount * 4.5)));
        for ($i = 1; $i <= $leadCount; $i++) {
            $abogadoId = $lawyerIds[array_rand($lawyerIds)];
            $clienteId = $clientIds[array_rand($clientIds)];
            $estado = $states[array_rand($states)];
            $seen = ($estado === 'PENDIENTE' && random_int(0,100) < 50) ? null : date('Y-m-d H:i:s', time() - random_int(0,14) * 86400);
            $fechaRev = date('Y-m-d H:i:s', time() - random_int(0, 40) * 86400);
            $estadoAt = date('Y-m-d H:i:s', strtotime($fechaRev) + random_int(300, 8*86400));
            $insertCr([
                'abogado_id' => $abogadoId,
                'cliente_id' => $clienteId,
                'medio_contacto' => ($i % 2 ? 'Perfil Público · WhatsApp' : 'Perfil Público · Llamada'),
                'estado' => $estado,
                'consulta' => 'Lead demo #' . $i . ' generado en modo de prueba para QA de panel y CRM.',
                'presupuesto' => in_array($estado, ['GANADO','CONTACTADO'], true) ? random_int(80000, 950000) : 0,
                'fecha_revelado' => $fechaRev,
                'fecha_cierre' => $estado === 'GANADO' ? date('Y-m-d H:i:s', strtotime($estadoAt) + random_int(3600, 10*86400)) : null,
                'retention_stage' => 'activo',
                'activo_hasta' => date('Y-m-d H:i:s', strtotime($fechaRev) + 30*86400),
                'estado_updated_at' => $estadoAt,
                'abogado_vio_at' => $seen,
                'created_at' => $fechaRev,
            ]);
        }
    }

    return ['lawyers' => count($lawyerIds), 'clients' => count($clientIds)];
}

function isAdminSessionAuthenticated(): bool {
    return !empty($_SESSION['admin_auth']) && ($_SESSION['admin_auth'] === true);
}

function requireAdminAuthOrRedirect(Response $response): Response {
    if (isAdminSessionAuthenticated()) return $response;
    return $response->withHeader('Location', '/admin-login')->withStatus(302);
}

function dbColumnExists($table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $cache[$key] = ((int)$stmt->fetchColumn()) > 0;
    } catch (Throwable $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function lawyerVerificationColumnsAvailable() {
    return dbColumnExists('abogados', 'abogado_verificado')
        && dbColumnExists('abogados', 'estado_verificacion_abogado')
        && dbColumnExists('abogados', 'solicito_habilitacion_abogado');
}

function visibleLawyerWhereClause($alias = '') {
    $p = $alias !== '' ? rtrim($alias, '.') . '.' : '';
    // Publicado primero, verificado después: la visibilidad depende de rol+activo.
    // El badge PJUD se controla por abogado_verificado, no la aparición en el listado.
    return $p . "rol = 'abogado' AND " . $p . "activo = 1";
}

function canShowLawyerVerificationStatus(array $user) {
    return ($user['rol'] ?? null) === 'abogado' && lawyerVerificationColumnsAvailable();
}

function ensureActivityColumns(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        if (!dbColumnExists('abogados', 'last_seen_at')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN last_seen_at DATETIME NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // Fail-open to avoid taking down the app on schema drift/permissions.
    }
}

function touchLawyerLastSeen(): void {
    if (empty($_SESSION['user_id'])) return;
    if (empty($_SESSION['rol']) || $_SESSION['rol'] !== 'abogado') return;
    ensureActivityColumns();
    if (!dbColumnExists('abogados', 'last_seen_at')) return;

    $now = time();
    $lastTouch = (int)($_SESSION['lawyer_last_seen_touch_ts'] ?? 0);
    if ($lastTouch > 0 && ($now - $lastTouch) < 300) {
        return; // throttle writes to max 1 update / 5 min per session
    }
    try {
        $pdo = getDB();
        $pdo->prepare("UPDATE abogados SET last_seen_at = NOW() WHERE id = ? AND rol = 'abogado'")
            ->execute([(int)$_SESSION['user_id']]);
        $_SESSION['lawyer_last_seen_touch_ts'] = $now;
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLawyerIdentityRegistryTables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = getDB();
        if (!dbColumnExists('abogados', 'abogado_public_id')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN abogado_public_id VARCHAR(16) NULL DEFAULT NULL");
        }
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS abogado_identidad_registro (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(190) NOT NULL,
                abogado_public_id VARCHAR(16) NOT NULL,
                seq_num INT NOT NULL,
                current_abogado_user_id INT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_email (email),
                UNIQUE KEY uniq_public_id (abogado_public_id),
                UNIQUE KEY uniq_seq_num (seq_num)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureUniqueLikesTable(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        getDB()->exec("
            CREATE TABLE IF NOT EXISTS abogado_likes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                abogado_id INT NOT NULL,
                cliente_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_cliente_abogado (cliente_id, abogado_id),
                KEY idx_abogado (abogado_id),
                KEY idx_cliente (cliente_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureUniqueViewsTable(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        getDB()->exec("
            CREATE TABLE IF NOT EXISTS abogado_views_unicas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                abogado_id INT NOT NULL,
                viewer_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_viewer_abogado (viewer_id, abogado_id),
                KEY idx_abogado (abogado_id),
                KEY idx_viewer (viewer_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        // fail-open
    }
}


function ensureUniqueRecommendationsTable(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = getDB();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS abogado_recomendaciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                abogado_id INT NOT NULL,
                cliente_id INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_cliente_abogado (cliente_id, abogado_id),
                KEY idx_abogado (abogado_id),
                KEY idx_cliente (cliente_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        if (!dbColumnExists('abogados', 'recomendaciones')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN recomendaciones INT NOT NULL DEFAULT 0");
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLawyerPaymentColumns(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = getDB();
        if (!dbColumnExists('abogados', 'exhibir_medios_pago')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN exhibir_medios_pago TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!dbColumnExists('abogados', 'medios_pago_json')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN medios_pago_json TEXT NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLawyerSubscriptionColumns(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = getDB();
        if (!dbColumnExists('abogados', 'subscription_plan')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN subscription_plan VARCHAR(40) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'subscription_status')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN subscription_status VARCHAR(20) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'subscription_started_at')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN subscription_started_at DATETIME NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'subscription_renews_at')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN subscription_renews_at DATETIME NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'subscription_trial_ends_at')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN subscription_trial_ends_at DATETIME NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLawyerSocialColumns(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = getDB();
        if (!dbColumnExists('abogados', 'facebook')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN facebook VARCHAR(255) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'linkedin')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN linkedin VARCHAR(255) NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLawyerQuoteBrandingColumns(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = getDB();
        if (!dbColumnExists('abogados', 'quote_branding_enabled')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN quote_branding_enabled TINYINT(1) NOT NULL DEFAULT 0");
        }
        if (!dbColumnExists('abogados', 'quote_brand_name')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN quote_brand_name VARCHAR(120) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'quote_brand_legal_name')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN quote_brand_legal_name VARCHAR(190) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'quote_brand_rut')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN quote_brand_rut VARCHAR(32) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'quote_brand_phone')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN quote_brand_phone VARCHAR(120) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'quote_brand_email')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN quote_brand_email VARCHAR(190) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'quote_brand_address')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN quote_brand_address VARCHAR(255) NULL DEFAULT NULL");
        }
        if (!dbColumnExists('abogados', 'quote_brand_legal_notice')) {
            $pdo->exec("ALTER TABLE abogados ADD COLUMN quote_brand_legal_notice TEXT NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLawyerServicesAndQuotesTables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = getDB();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS abogado_servicios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                abogado_id INT NOT NULL,
                equipo_id INT NULL,
                nombre VARCHAR(190) NOT NULL,
                materia VARCHAR(150) NULL,
                detalle TEXT NULL,
                plazo_estimado VARCHAR(120) NULL,
                precio_base DECIMAL(12,2) NOT NULL DEFAULT 0,
                gastos_base DECIMAL(12,2) NOT NULL DEFAULT 0,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_abogado_activo (abogado_id, activo),
                KEY idx_abogado (abogado_id),
                KEY idx_equipo_activo (equipo_id, activo),
                KEY idx_equipo (equipo_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS abogado_cotizaciones (
                id INT AUTO_INCREMENT PRIMARY KEY,
                abogado_id INT NOT NULL,
                equipo_id INT NULL,
                servicio_id INT NULL,
                cliente_id INT NULL,
                client_name VARCHAR(190) NOT NULL,
                client_whatsapp VARCHAR(32) NULL,
                client_email VARCHAR(190) NULL,
                asunto VARCHAR(190) NOT NULL,
                materia VARCHAR(150) NULL,
                detalle TEXT NULL,
                no_incluye TEXT NULL,
                plazo_estimado VARCHAR(120) NULL,
                vigencia VARCHAR(120) NULL,
                honorarios DECIMAL(12,2) NOT NULL DEFAULT 0,
                gastos DECIMAL(12,2) NOT NULL DEFAULT 0,
                descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
                total DECIMAL(12,2) NOT NULL DEFAULT 0,
                anticipo DECIMAL(12,2) NOT NULL DEFAULT 0,
                saldo DECIMAL(12,2) NOT NULL DEFAULT 0,
                condiciones_pago VARCHAR(255) NULL,
                payment_link VARCHAR(255) NULL,
                notas TEXT NULL,
                mensaje_texto MEDIUMTEXT NULL,
                estado VARCHAR(20) NOT NULL DEFAULT 'BORRADOR',
                cobro_estado VARCHAR(20) NOT NULL DEFAULT 'SIN_GESTION',
                cobrado_monto DECIMAL(12,2) NOT NULL DEFAULT 0,
                cobro_updated_at DATETIME NULL DEFAULT NULL,
                cobro_reminder_sent_at DATETIME NULL DEFAULT NULL,
                cobro_reminder_count INT NOT NULL DEFAULT 0,
                cobro_reminder_last_channel VARCHAR(20) NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_abogado_created (abogado_id, created_at),
                KEY idx_abogado_estado (abogado_id, estado),
                KEY idx_equipo_created (equipo_id, created_at),
                KEY idx_equipo_estado (equipo_id, estado),
                KEY idx_cliente (cliente_id),
                KEY idx_servicio (servicio_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        if (!dbColumnExists('abogado_servicios', 'equipo_id')) {
            $pdo->exec("ALTER TABLE abogado_servicios ADD COLUMN equipo_id INT NULL DEFAULT NULL AFTER abogado_id");
        }
        if (!dbColumnExists('abogado_cotizaciones', 'equipo_id')) {
            $pdo->exec("ALTER TABLE abogado_cotizaciones ADD COLUMN equipo_id INT NULL DEFAULT NULL AFTER abogado_id");
        }
        if (!dbColumnExists('abogado_cotizaciones', 'cobro_estado')) {
            $pdo->exec("ALTER TABLE abogado_cotizaciones ADD COLUMN cobro_estado VARCHAR(20) NOT NULL DEFAULT 'SIN_GESTION' AFTER estado");
        }
        if (!dbColumnExists('abogado_cotizaciones', 'cobrado_monto')) {
            $pdo->exec("ALTER TABLE abogado_cotizaciones ADD COLUMN cobrado_monto DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER cobro_estado");
        }
        if (!dbColumnExists('abogado_cotizaciones', 'cobro_updated_at')) {
            $pdo->exec("ALTER TABLE abogado_cotizaciones ADD COLUMN cobro_updated_at DATETIME NULL DEFAULT NULL AFTER cobrado_monto");
        }
        if (!dbColumnExists('abogado_cotizaciones', 'cobro_reminder_sent_at')) {
            $pdo->exec("ALTER TABLE abogado_cotizaciones ADD COLUMN cobro_reminder_sent_at DATETIME NULL DEFAULT NULL AFTER cobro_updated_at");
        }
        if (!dbColumnExists('abogado_cotizaciones', 'cobro_reminder_count')) {
            $pdo->exec("ALTER TABLE abogado_cotizaciones ADD COLUMN cobro_reminder_count INT NOT NULL DEFAULT 0 AFTER cobro_reminder_sent_at");
        }
        if (!dbColumnExists('abogado_cotizaciones', 'cobro_reminder_last_channel')) {
            $pdo->exec("ALTER TABLE abogado_cotizaciones ADD COLUMN cobro_reminder_last_channel VARCHAR(20) NULL DEFAULT NULL AFTER cobro_reminder_count");
        }
        if (!dbColumnExists('abogado_cotizaciones', 'quote_items_json')) {
            $pdo->exec("ALTER TABLE abogado_cotizaciones ADD COLUMN quote_items_json MEDIUMTEXT NULL AFTER notas");
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLawyerTeamTables(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo = getDB();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS abogado_equipos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                owner_abogado_id INT NOT NULL,
                nombre VARCHAR(160) NOT NULL,
                slug VARCHAR(180) NOT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_owner (owner_abogado_id),
                UNIQUE KEY uniq_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS abogado_equipo_miembros (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipo_id INT NOT NULL,
                abogado_id INT NULL DEFAULT NULL,
                email VARCHAR(190) NOT NULL,
                nombre_invitado VARCHAR(160) NULL DEFAULT NULL,
                rol VARCHAR(20) NOT NULL DEFAULT 'member',
                estado VARCHAR(20) NOT NULL DEFAULT 'pending',
                invited_by_abogado_id INT NOT NULL,
                joined_at DATETIME NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_team_email (equipo_id, email),
                UNIQUE KEY uniq_team_lawyer (equipo_id, abogado_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS abogado_equipo_actividad (
                id INT AUTO_INCREMENT PRIMARY KEY,
                equipo_id INT NOT NULL,
                actor_abogado_id INT NOT NULL,
                action_key VARCHAR(60) NOT NULL,
                target_type VARCHAR(30) NOT NULL,
                target_id INT NOT NULL,
                title VARCHAR(190) NOT NULL,
                meta VARCHAR(255) NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_team_created (equipo_id, created_at),
                KEY idx_target (target_type, target_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    } catch (Throwable $e) {
        // fail-open
    }
}

function ensureLawyerAdminReviewColumns() {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        if (!dbColumnExists('abogados', 'rut_validacion_manual')) {
            getDB()->exec("ALTER TABLE abogados ADD COLUMN rut_validacion_manual VARCHAR(20) NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // fail-open
    }
}

function assignOrGetLawyerPublicId(PDO $pdo, string $email, ?int $currentUserId = null): ?string {
    ensureLawyerIdentityRegistryTables();
    $email = strtolower(trim($email));
    if ($email === '') return null;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT abogado_public_id FROM abogado_identidad_registro WHERE email = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$email]);
        $existing = $stmt->fetchColumn();
        if ($existing) {
            $up = $pdo->prepare("UPDATE abogado_identidad_registro SET current_abogado_user_id = ?, last_seen_at = NOW() WHERE email = ?");
            $up->execute([$currentUserId, $email]);
            $pdo->commit();
            return (string)$existing;
        }

        $seq = (int)$pdo->query("SELECT COALESCE(MAX(seq_num), 0) + 1 FROM abogado_identidad_registro")->fetchColumn();
        $publicId = 'ID-' . str_pad((string)$seq, 9, '0', STR_PAD_LEFT);
        $ins = $pdo->prepare("INSERT INTO abogado_identidad_registro (email, abogado_public_id, seq_num, current_abogado_user_id, created_at, last_seen_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
        $ins->execute([$email, $publicId, $seq, $currentUserId]);
        $pdo->commit();
        return $publicId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        return null;
    }
}

function trackEvent($eventName, $payload = []) {
    if (!is_string($eventName) || $eventName === '') {
        return;
    }

    $event = [
        'timestamp' => gmdate('c'),
        'event' => $eventName,
        'path' => $_SERVER['REQUEST_URI'] ?? null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['rol'] ?? null,
        'payload' => is_array($payload) ? $payload : []
    ];

    $line = json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line !== false) {
        @file_put_contents('/tmp/lawyers_analytics.jsonl', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

function ensureWebMetricsTable(): void {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        getDB()->exec("
            CREATE TABLE IF NOT EXISTS web_metric_events (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                event_name VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                path VARCHAR(255) NULL,
                content_type VARCHAR(48) NULL,
                content_id INT NULL,
                content_slug VARCHAR(191) NULL,
                user_id INT NULL,
                role VARCHAR(32) NULL,
                session_hash CHAR(64) NULL,
                ip VARCHAR(64) NULL,
                ip_hash CHAR(64) NULL,
                user_agent VARCHAR(255) NULL,
                referer VARCHAR(255) NULL,
                traffic_class ENUM('human','bot','admin_test') NOT NULL DEFAULT 'human',
                is_bot TINYINT(1) NOT NULL DEFAULT 0,
                raw_counted TINYINT(1) NOT NULL DEFAULT 1,
                human_counted TINYINT(1) NOT NULL DEFAULT 1,
                source VARCHAR(32) NULL,
                payload_json TEXT NULL,
                dedupe_key CHAR(64) NOT NULL,
                UNIQUE KEY uniq_dedupe (dedupe_key),
                KEY idx_event_created (event_name, created_at),
                KEY idx_traffic_created (traffic_class, created_at),
                KEY idx_content (content_type, content_id, created_at),
                KEY idx_ip_class (ip, traffic_class, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        // fail-open
    }
}

function metricsEnvCsvList(string $key): array {
    $raw = trim((string)(getenv($key) ?: ''));
    if ($raw === '') return [];
    $parts = preg_split('/[,;\s]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return array_values(array_filter(array_map('trim', $parts), fn($x) => $x !== ''));
}

function metricsClientIp(): string {
    $candidates = [
        (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''),
        (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''),
        (string)($_SERVER['REMOTE_ADDR'] ?? ''),
    ];
    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if ($candidate === '') continue;
        if (str_contains($candidate, ',')) {
            $candidate = trim((string)explode(',', $candidate, 2)[0]);
        }
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }
    return '';
}

function metricsNormalizePath(string $path): string {
    $path = trim($path);
    if ($path === '') return '/';
    if (!str_starts_with($path, '/')) $path = '/' . $path;
    return substr($path, 0, 255);
}

function metricsPathLooksStatic(string $path): bool {
    return (bool)preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|webp|ico|woff2?|ttf|map)$/i', $path);
}

function metricsPathExcludedFromPublicStats(string $path): bool {
    $path = metricsNormalizePath($path);
    $prefixes = ['/admin', '/dashboard', '/panel', '/auth', '/login-google', '/logout'];
    foreach ($prefixes as $prefix) {
        if (str_starts_with($path, $prefix)) return true;
    }
    return false;
}

function metricsPublicPageVisitPathSql(string $column = 'path'): string {
    return implode(' AND ', [
        "$column NOT LIKE '/admin%'",
        "$column NOT LIKE '/dashboard%'",
        "$column NOT LIKE '/panel%'",
        "$column NOT LIKE '/auth%'",
        "$column NOT LIKE '/login-google%'",
        "$column NOT LIKE '/logout%'",
    ]);
}

function metricsUaLooksBot(string $ua): bool {
    $ua = strtolower(trim($ua));
    if ($ua === '') return false;
    $patterns = [
        'bot', 'spider', 'crawl', 'slurp', 'bingpreview', 'headless', 'lighthouse',
        'facebookexternalhit', 'whatsapp', 'telegrambot', 'discordbot', 'linkedinbot',
        'applebot', 'semrush', 'ahrefs', 'mj12bot', 'yandexbot', 'bytespider', 'duckduckbot',
        'google-extended', 'gptbot', 'claudebot', 'chatgpt-user'
    ];
    foreach ($patterns as $needle) {
        if (str_contains($ua, $needle)) return true;
    }
    return false;
}

function metricsIpInCidr(string $ip, string $cidr): bool {
    if ($ip === '' || $cidr === '') return false;
    if (!str_contains($cidr, '/')) return $ip === $cidr;
    [$subnet, $maskBits] = explode('/', $cidr, 2);
    $maskBits = (int)$maskBits;
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);
    if ($ipLong === false || $subnetLong === false || $maskBits < 0 || $maskBits > 32) return false;
    $mask = $maskBits === 0 ? 0 : (-1 << (32 - $maskBits));
    return (($ipLong & $mask) === ($subnetLong & $mask));
}

function metricsIsKnownBotIp(string $ip): bool {
    if ($ip === '') return false;
    $cidrs = metricsEnvCsvList('METRICS_BOT_CIDRS');
    foreach ($cidrs as $cidr) {
        if (metricsIpInCidr($ip, $cidr)) return true;
    }
    return false;
}

function metricsIsInternalIp(string $ip): bool {
    if ($ip === '') return false;
    $ips = metricsEnvCsvList('METRICS_INTERNAL_IPS');
    foreach ($ips as $cand) {
        if ($cand !== '' && metricsIpInCidr($ip, $cand)) return true;
    }
    return false;
}

function metricsTrafficClass(string $ip, string $ua): string {
    if (isAdminSessionAuthenticated() || metricsIsInternalIp($ip)) {
        return 'admin_test';
    }
    if (metricsIsKnownBotIp($ip) || metricsUaLooksBot($ua)) {
        return 'bot';
    }
    return 'human';
}

function metricsDoNotTrackEnabled(): bool {
    if (!empty($_COOKIE['do_not_track_metrics'])) return true;
    $dntHeader = trim((string)($_SERVER['HTTP_DNT'] ?? ''));
    return $dntHeader === '1';
}

function metricsDedupeWindow(string $eventName): int {
    $map = [
        'page_visit' => 600,
        'content_view' => 600,
        'like' => 31536000,
        'recommend' => 31536000,
        'share' => 3600,
        'interaction_lead' => 3600,
        'interaction' => 300,
    ];
    return (int)($map[$eventName] ?? 300);
}

function recordWebMetricEvent(string $eventName, array $context = [], array $opts = []): bool {
    $eventName = strtolower(trim($eventName));
    if (!preg_match('/^[a-z0-9_]{2,64}$/', $eventName)) {
        return false;
    }
    if (metricsDoNotTrackEnabled()) {
        return false;
    }

    ensureWebMetricsTable();
    $ip = metricsClientIp();
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    $path = metricsNormalizePath((string)($context['path'] ?? ($_SERVER['REQUEST_URI'] ?? '/')));
    $sessionId = session_id();
    $sessionHash = $sessionId !== '' ? hash('sha256', $sessionId) : null;
    $scopeKey = trim((string)($opts['dedupe_scope'] ?? ''));
    if ($scopeKey === '') {
        if (!empty($_SESSION['user_id'])) $scopeKey = 'u:' . (int)$_SESSION['user_id'];
        elseif ($sessionId !== '') $scopeKey = 's:' . $sessionId;
        elseif ($ip !== '' || $ua !== '') $scopeKey = 'ipua:' . $ip . '|' . substr($ua, 0, 180);
        else $scopeKey = 'anon';
    }
    $windowSec = max(1, (int)($opts['window_sec'] ?? metricsDedupeWindow($eventName)));
    $bucket = (int)floor(time() / $windowSec);
    $contentType = trim((string)($context['content_type'] ?? ''));
    $contentId = isset($context['content_id']) ? (int)$context['content_id'] : null;
    if ($contentId !== null && $contentId <= 0) $contentId = null;
    $contentSlug = trim((string)($context['content_slug'] ?? ''));
    $trafficClass = in_array(($context['traffic_class'] ?? ''), ['human', 'bot', 'admin_test'], true)
        ? (string)$context['traffic_class']
        : metricsTrafficClass($ip, $ua);

    $rawCounted = $trafficClass === 'admin_test' ? 0 : 1;
    $humanCounted = $trafficClass === 'human' ? 1 : 0;
    if (array_key_exists('count_raw', $opts)) $rawCounted = !empty($opts['count_raw']) ? 1 : 0;
    if (array_key_exists('count_human', $opts)) $humanCounted = !empty($opts['count_human']) ? 1 : 0;

    $dedupeKey = hash('sha256', implode('|', [
        $eventName,
        $trafficClass,
        $scopeKey,
        $path,
        $contentType,
        (string)($contentId ?? 0),
        $contentSlug,
        (string)$bucket
    ]));

    $payload = [];
    $rawPayload = is_array($context['payload'] ?? null) ? (array)$context['payload'] : [];
    foreach (array_slice($rawPayload, 0, 20, true) as $k => $v) {
        if (!is_scalar($v) && $v !== null) continue;
        $payload[(string)$k] = is_string($v) ? substr($v, 0, 220) : $v;
    }
    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($payloadJson)) $payloadJson = '{}';

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO web_metric_events (
                event_name, created_at, path, content_type, content_id, content_slug,
                user_id, role, session_hash, ip, ip_hash, user_agent, referer,
                traffic_class, is_bot, raw_counted, human_counted, source, payload_json, dedupe_key
            ) VALUES (
                ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");
        $stmt->execute([
            $eventName,
            $path,
            $contentType !== '' ? substr($contentType, 0, 48) : null,
            $contentId,
            $contentSlug !== '' ? substr($contentSlug, 0, 191) : null,
            !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
            !empty($_SESSION['rol']) ? substr((string)$_SESSION['rol'], 0, 32) : null,
            $sessionHash,
            $ip !== '' ? substr($ip, 0, 64) : null,
            $ip !== '' ? hash('sha256', $ip) : null,
            $ua !== '' ? substr($ua, 0, 255) : null,
            !empty($_SERVER['HTTP_REFERER']) ? substr((string)$_SERVER['HTTP_REFERER'], 0, 255) : null,
            $trafficClass,
            $trafficClass === 'bot' ? 1 : 0,
            $rawCounted,
            $humanCounted,
            substr((string)($context['source'] ?? 'server'), 0, 32),
            $payloadJson,
            $dedupeKey
        ]);
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

function trackRequestPageVisit(Request $request, Response $response): void {
    if (strtoupper($request->getMethod()) !== 'GET') return;
    $status = $response->getStatusCode();
    if ($status < 200 || $status >= 400) return;
    $path = metricsNormalizePath((string)$request->getUri()->getPath());
    if (metricsPathLooksStatic($path) || str_starts_with($path, '/api/') || metricsPathExcludedFromPublicStats($path)) return;
    $contentType = strtolower($response->getHeaderLine('Content-Type'));
    if ($contentType !== '' && !str_contains($contentType, 'text/html')) return;
    recordWebMetricEvent('page_visit', [
        'path' => $path,
        'source' => 'middleware'
    ], [
        'window_sec' => 600
    ]);
}

function metricsLikelySameOrigin(Request $request): bool {
    $host = strtolower((string)$request->getUri()->getHost());
    if ($host === '') $host = 'example.com';
    $allowedHosts = array_values(array_unique(array_filter([$host, 'example.com', 'www.example.com', '127.0.0.1', 'localhost'])));
    $origin = strtolower(trim((string)$request->getHeaderLine('Origin')));
    if ($origin !== '') {
        $originHost = parse_url($origin, PHP_URL_HOST);
        return is_string($originHost) && in_array(strtolower($originHost), $allowedHosts, true);
    }
    $referer = strtolower(trim((string)$request->getHeaderLine('Referer')));
    if ($referer !== '') {
        $refHost = parse_url($referer, PHP_URL_HOST);
        if (!is_string($refHost)) return false;
        return in_array(strtolower($refHost), $allowedHosts, true);
    }
    return false;
}

function analyticsAdminSnapshot(PDO $pdo, int $days = 30): array {
    ensureWebMetricsTable();
    $days = max(1, min(180, $days));
    $since = date('Y-m-d H:i:s', time() - ($days * 86400));
    $interactionsEvents = ['like', 'share', 'recommend', 'interaction', 'interaction_lead'];
    $in = implode(',', array_fill(0, count($interactionsEvents), '?'));
    $publicVisitPathFilter = metricsPublicPageVisitPathSql('path');
    $publicVisitPathFilterE = metricsPublicPageVisitPathSql('e.path');

    $sumMetric = function(string $eventName, string $column) use ($pdo, $since, $publicVisitPathFilter): int {
        $sql = "SELECT COALESCE(SUM($column),0) FROM web_metric_events WHERE created_at >= ? AND event_name = ?";
        if ($eventName === 'page_visit') {
            $sql .= " AND $publicVisitPathFilter";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$since, $eventName]);
        return (int)$stmt->fetchColumn();
    };
    $sumMetricIn = function(array $events, string $column) use ($pdo, $since, $in): int {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM($column),0) FROM web_metric_events WHERE created_at >= ? AND event_name IN ($in)");
        $stmt->execute(array_merge([$since], $events));
        return (int)$stmt->fetchColumn();
    };

    $topContentStmt = $pdo->prepare("
        SELECT
            e.content_id,
            MAX(COALESCE(a.nombre, CONCAT('Contenido #', e.content_id))) AS content_name,
            MAX(COALESCE(a.slug, e.content_slug)) AS content_slug,
            COALESCE(SUM(CASE WHEN e.event_name = 'content_view' THEN e.raw_counted ELSE 0 END),0) AS views_raw,
            COALESCE(SUM(CASE WHEN e.event_name = 'content_view' THEN e.human_counted ELSE 0 END),0) AS views_human,
            COALESCE(SUM(CASE WHEN e.event_name IN ($in) THEN e.raw_counted ELSE 0 END),0) AS interactions_raw,
            COALESCE(SUM(CASE WHEN e.event_name IN ($in) THEN e.human_counted ELSE 0 END),0) AS interactions_human
        FROM web_metric_events e
        LEFT JOIN abogados a ON a.id = e.content_id
        WHERE e.created_at >= ?
          AND $publicVisitPathFilterE
          AND e.content_type = 'abogado_profile'
          AND e.content_id IS NOT NULL
        GROUP BY e.content_id
        ORDER BY interactions_human DESC, views_human DESC, views_raw DESC
        LIMIT 10
    ");
    $topContentStmt->execute(array_merge($interactionsEvents, $interactionsEvents, [$since]));
    $topContents = $topContentStmt->fetchAll() ?: [];

    $latestStmt = $pdo->prepare("
        SELECT created_at, path, ip, traffic_class, user_id, user_agent
        FROM web_metric_events
        WHERE created_at >= ?
          AND event_name = 'page_visit'
          AND $publicVisitPathFilter
        ORDER BY created_at DESC, id DESC
        LIMIT 20
    ");
    $latestStmt->execute([$since]);
    $latestVisits = $latestStmt->fetchAll() ?: [];

    $ipTop = function(string $klass) use ($pdo, $since, $publicVisitPathFilter): array {
        $stmt = $pdo->prepare("
            SELECT ip, COUNT(*) AS hits
            FROM web_metric_events
            WHERE created_at >= ?
              AND event_name = 'page_visit'
              AND $publicVisitPathFilter
              AND traffic_class = ?
              AND ip IS NOT NULL AND ip <> ''
            GROUP BY ip
            ORDER BY hits DESC, MAX(created_at) DESC
            LIMIT 10
        ");
        $stmt->execute([$since, $klass]);
        return $stmt->fetchAll() ?: [];
    };

    $contentActivityStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT content_id)
        FROM web_metric_events
        WHERE created_at >= ?
          AND content_id IS NOT NULL
          AND event_name IN ($in)
    ");
    $contentActivityStmt->execute(array_merge([$since], $interactionsEvents));
    $contentWithActivity = (int)$contentActivityStmt->fetchColumn();

    return [
        'days' => $days,
        'since' => $since,
        'total_contents' => (int)$pdo->query("SELECT COUNT(*) FROM abogados WHERE " . visibleLawyerWhereClause())->fetchColumn(),
        'page_visits_raw' => $sumMetric('page_visit', 'raw_counted'),
        'page_visits_human' => $sumMetric('page_visit', 'human_counted'),
        'content_views_raw' => $sumMetric('content_view', 'raw_counted'),
        'content_views_human' => $sumMetric('content_view', 'human_counted'),
        'likes_raw' => $sumMetric('like', 'raw_counted'),
        'likes_human' => $sumMetric('like', 'human_counted'),
        'shares_raw' => $sumMetric('share', 'raw_counted'),
        'shares_human' => $sumMetric('share', 'human_counted'),
        'interactions_raw' => $sumMetricIn($interactionsEvents, 'raw_counted'),
        'interactions_human' => $sumMetricIn($interactionsEvents, 'human_counted'),
        'content_with_activity' => $contentWithActivity,
        'top_contents' => $topContents,
        'latest_visits' => $latestVisits,
        'top_ips_human' => $ipTop('human'),
        'top_ips_bot' => $ipTop('bot'),
    ];
}

$app->add(function (Request $request, $handler) {
    touchLawyerLastSeen();
    $response = $handler->handle($request);
    try { trackRequestPageVisit($request, $response); } catch (Throwable $e) {}
    return $response;
});

// ============================================================================
// RUTAS PRINCIPALES (ESPECÍFICAS PRIMERO)
// ============================================================================

// 1. HOME
$homeLandingHandler = function (Request $request, Response $response) use ($renderer) {
    $pdo = getDB();
    $profesionales = (int)$pdo->query("SELECT COUNT(*) FROM abogados WHERE rol = 'abogado'")->fetchColumn();
    $clientesBuscando = (int)$pdo->query("SELECT COUNT(*) FROM abogados WHERE rol = 'cliente'")->fetchColumn();
    $projectInterestOld = $_SESSION['project_interest_old'] ?? [];
    unset($_SESSION['project_interest_old']);
    $materiasInicio = [];
    foreach (lawyerMateriasCanonicas() as $mat) {
        $slug = materiaSlug($mat);
        if ($slug === '') {
            continue;
        }
        $materiasInicio[] = [
            'nombre' => $mat,
            'materia_href' => '/materias/' . $slug,
            'explorar_href' => '/explorar?especialidad=' . rawurlencode($mat),
        ];
    }
    return $renderer->render($response, 'home.php', [
        'regiones_chile' => regionesChile(),
        'comunas_sugeridas' => comunasChileSugeridas(),
        'stats_inicio' => [
            'profesionales' => $profesionales,
            'clientes' => $clientesBuscando,
        ],
        'materias_inicio' => $materiasInicio,
        'project_interest_old' => is_array($projectInterestOld) ? $projectInterestOld : [],
    ] + consumeSessionFlashMessage());
};

$app->post('/solicitar-proyecto', function (Request $request, Response $response) {
    $data = (array)($request->getParsedBody() ?? []);
    $nombre = trim((string)($data['nombre'] ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $empresa = trim((string)($data['empresa'] ?? ''));
    $interes = trim((string)($data['interes'] ?? ''));
    $mensaje = trim((string)($data['mensaje'] ?? ''));
    $website = trim((string)($data['website'] ?? ''));

    $_SESSION['project_interest_old'] = [
        'nombre' => $nombre,
        'email' => $email,
        'empresa' => $empresa,
        'interes' => $interes,
        'mensaje' => $mensaje,
    ];

    if ($website !== '') {
        $_SESSION['mensaje'] = '✅ Gracias. Recibimos tu interés y te contactaremos.';
        $_SESSION['tipo_mensaje'] = 'success';
        unset($_SESSION['project_interest_old']);
        return $response->withHeader('Location', '/#interes-proyecto')->withStatus(302);
    }

    if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($interes, ['prueba', 'implementar'], true)) {
        $_SESSION['mensaje'] = '⚠️ Completa nombre, email válido y el tipo de interés.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/#interes-proyecto')->withStatus(302);
    }

    $send = notifyProjectInterest([
        'nombre' => $nombre,
        'email' => $email,
        'empresa' => $empresa,
        'interes' => $interes,
        'mensaje' => $mensaje,
        'host' => (string)$request->getUri()->getHost(),
    ]);

    if (!($send['ok'] ?? false)) {
        $_SESSION['mensaje'] = resendIsConfigured()
            ? '⚠️ No pudimos enviar tu solicitud ahora. Intenta nuevamente.'
            : 'ℹ️ El formulario funciona, pero el correo está desactivado en este entorno.';
        $_SESSION['tipo_mensaje'] = resendIsConfigured() ? 'error' : 'info';
        return $response->withHeader('Location', '/#interes-proyecto')->withStatus(302);
    }

    unset($_SESSION['project_interest_old']);
    $_SESSION['mensaje'] = '✅ Gracias. Te contactaremos pronto para coordinar una prueba o implementación.';
    $_SESSION['tipo_mensaje'] = 'success';
    return $response->withHeader('Location', '/#interes-proyecto')->withStatus(302);
});

$app->get('/', $homeLandingHandler);

$app->get('/inicio', function (Request $request, Response $response) {
    return $response->withHeader('Location', '/')->withStatus(301);
});

$app->get('/nosotros', function (Request $request, Response $response) {
    return $response->withHeader('Location', '/inicio')->withStatus(302);
});

$app->get('/materias/{slug}', function (Request $request, Response $response, $args) use ($renderer) {
    $slug = trim((string)($args['slug'] ?? ''));
    $tax = lawyerMateriasTaxonomia();
    $canon = lawyerMateriasCanonicas();
    $slugMap = [];
    foreach ($canon as $mat) {
        $key = materiaSlug($mat);
        if ($key !== '') {
            $slugMap[$key] = $mat;
        }
        // Compatibilidad con slugs legacy (ej: protecci-n-al-consumidor)
        $legacy = strtolower($mat);
        $legacy = preg_replace('/[^a-z0-9]+/', '-', $legacy);
        $legacy = trim((string)$legacy, '-');
        if ($legacy !== '' && !isset($slugMap[$legacy])) {
            $slugMap[$legacy] = $mat;
        }
    }
    $materia = $slugMap[$slug] ?? null;
    if ($materia === null || !isset($tax[$materia])) {
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
    $canonicalSlug = materiaSlug($materia);
    if ($canonicalSlug !== '' && $slug !== $canonicalSlug) {
        return $response->withHeader('Location', '/materias/' . $canonicalSlug)->withStatus(301);
    }
    recordWebMetricEvent('content_view', [
        'path' => '/materias/' . ($canonicalSlug !== '' ? $canonicalSlug : $slug),
        'content_type' => 'materia',
        'content_slug' => ($canonicalSlug !== '' ? $canonicalSlug : $slug),
        'source' => 'route_materia'
    ], [
        'window_sec' => 600
    ]);
    $pdo = getDB();
    $totalMateria = 0;
    try {
        $variants = [$materia];
        $legacyBackMap = [
            'Derecho Civil' => 'Civil', 'Derecho Familiar' => 'Familia', 'Derecho Laboral' => 'Laboral',
            'Derecho Penal' => 'Penal', 'Derecho Comercial' => 'Comercial', 'Derecho Tributario' => 'Tributario',
            'Protección al Consumidor' => 'Consumidor', 'Otros Casos' => 'Otros',
        ];
        if (isset($legacyBackMap[$materia])) $variants[] = $legacyBackMap[$materia];
        $variants = array_values(array_unique($variants));
        $sql = "SELECT COUNT(*) FROM abogados WHERE " . visibleLawyerWhereClause() . " AND especialidad IN (" . implode(',', array_fill(0, count($variants), '?')) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($variants);
        $totalMateria = (int)$stmt->fetchColumn();
    } catch (Throwable $e) {}
    return $renderer->render($response, 'materia.php', [
        'materia_nombre' => $materia,
        'materia_slug' => $slug,
        'submaterias' => array_values(array_filter(array_map('strval', (array)($tax[$materia] ?? [])))),
        'total_materia' => $totalMateria,
    ]);
});

$app->get('/explorar', function (Request $request, Response $response) use ($renderer) {
    $pdo = getDB();
    $query = $request->getQueryParams();
    $currentUser = [];
    if (!empty($_SESSION['user_id'])) {
        try {
            $stCurrent = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
            $stCurrent->execute([(int)$_SESSION['user_id']]);
            $currentUser = $stCurrent->fetch() ?: [];
        } catch (Throwable $e) { $currentUser = []; }
    }
    $feedMode = 'cliente';
    if (!empty($currentUser) && userCanAccessLawyerDashboard((array)$currentUser)) {
        $feedMode = 'abogado';
    }
    $materiasCatalogo = lawyerMateriasCanonicas();
    $filtroActual = normalizeLawyerMateria(trim((string)($query['especialidad'] ?? '')));
    $region = trim((string)($query['region'] ?? ''));
    $sexoFiltro = trim((string)($query['sexo'] ?? ''));
    $comuna = trim((string)($query['comuna'] ?? $query['ciudad'] ?? ''));
    $lugar = $comuna;
    $experiencia = '';
    $latUsuario = null;
    $lngUsuario = null;
    $radioKm = 0;
    $modoDistancia = 'ordenar';

    $conditions = [visibleLawyerWhereClause()];
    $params = [];

    if ($filtroActual !== '') {
        $variants = [$filtroActual];
        $legacyBackMap = [
            'Derecho Civil' => 'Civil',
            'Derecho Familiar' => 'Familia',
            'Derecho Laboral' => 'Laboral',
            'Derecho Penal' => 'Penal',
            'Derecho Comercial' => 'Comercial',
            'Derecho Tributario' => 'Tributario',
            'Protección al Consumidor' => 'Consumidor',
            'Otros Casos' => 'Otros',
        ];
        if (isset($legacyBackMap[$filtroActual])) {
            $variants[] = $legacyBackMap[$filtroActual];
        }
        $variants = array_values(array_unique($variants));
        $conditions[] = 'especialidad IN (' . implode(',', array_fill(0, count($variants), '?')) . ')';
        foreach ($variants as $v) $params[] = $v;
    }
    if ($region !== '') {
        if ($region === 'Todo Chile') {
            $conditions[] = 'cobertura_nacional = 1';
        } else {
            $regionTerms = regionSearchTerms($region);
            $regionClauses = [];
            foreach ($regionTerms as $regionTerm) {
                $regionClauses[] = 'regiones_servicio LIKE ?';
                $params[] = '%' . $regionTerm . '%';
            }
            if (empty($regionClauses)) {
                $regionClauses[] = 'regiones_servicio LIKE ?';
                $params[] = '%' . $region . '%';
            }
            $conditions[] = '(cobertura_nacional = 1 OR ' . implode(' OR ', $regionClauses) . ')';
        }
    }
    if ($comuna !== '') {
        $comunaClauses = [];
        foreach (['ciudades_plaza', 'comunas_servicio', 'ciudad'] as $colCiudad) {
            if (dbColumnExists('abogados', $colCiudad)) {
                $comunaClauses[] = 'COALESCE(' . $colCiudad . ", '') LIKE ?";
                $params[] = '%' . $comuna . '%';
            }
        }
        if (!empty($comunaClauses)) {
            $conditions[] = '(' . implode(' OR ', $comunaClauses) . ')';
        }
    }
    if ($sexoFiltro !== '' && in_array($sexoFiltro, ['mujer', 'hombre', 'no_binario', 'prefiero_no_decir'], true) && dbColumnExists('abogados', 'sexo')) {
        $conditions[] = 'sexo = ?';
        $params[] = $sexoFiltro;
    }
    $where = implode(' AND ', $conditions);
    $distanceSql = "NULL AS distancia_km";
    $having = '';

    $sql = "SELECT *, $distanceSql
            FROM abogados
            WHERE $where
            $having
            ORDER BY
                (CASE WHEN destacado_hasta IS NOT NULL AND destacado_hasta >= NOW() THEN 1 ELSE 0 END) DESC,
                (distancia_km IS NULL) ASC,
                distancia_km ASC,
                (COALESCE(likes,0) * 3 + COALESCE(vistas,0)) DESC,
                created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $abogados = $stmt->fetchAll();
    foreach ($abogados as &$abogado) {
        $abogado['foto_publica'] = resolveLawyerPhoto($abogado, 640, false);
        if (isset($abogado['distancia_km']) && $abogado['distancia_km'] !== null) {
            $abogado['distancia_km'] = round((float)$abogado['distancia_km'], 1);
        }
        $abogado['online_24h'] = false;
        if (!empty($abogado['last_seen_at'])) {
            $seenTs = strtotime((string)$abogado['last_seen_at']);
            if ($seenTs !== false && $seenTs >= (time() - 86400)) {
                $abogado['online_24h'] = true;
            }
        }
    }
    unset($abogado);

    $filtersApplied = ($filtroActual !== '' || $region !== '' || $sexoFiltro !== '' || $comuna !== '');
    $noResultsFallback = false;
    $noResultsMessage = '';
    if (empty($abogados) && $filtersApplied) {
        $sqlFallback = "SELECT *, NULL AS distancia_km FROM abogados WHERE " . visibleLawyerWhereClause() . " ORDER BY (CASE WHEN destacado_hasta IS NOT NULL AND destacado_hasta >= NOW() THEN 1 ELSE 0 END) DESC, (COALESCE(likes,0) * 3 + COALESCE(vistas,0)) DESC, created_at DESC";
        $stFallback = $pdo->prepare($sqlFallback);
        $stFallback->execute();
        $abogados = $stFallback->fetchAll();
        foreach ($abogados as &$abogado) {
            $abogado['foto_publica'] = resolveLawyerPhoto($abogado, 640, false);
            $abogado['online_24h'] = false;
        }
        unset($abogado);
        $noResultsFallback = true;
        $noResultsMessage = 'No encontramos resultados con ese filtro. Te mostramos abogados disponibles para que explores opciones cercanas.';
    }

    $perPage = 15;
    $page = max(1, (int)($query['page'] ?? 1));
    $totalProfiles = count($abogados);
    $totalPages = max(1, (int)ceil($totalProfiles / $perPage));
    if ($page > $totalPages) { $page = $totalPages; }
    $offset = ($page - 1) * $perPage;
    $abogadosPage = array_slice($abogados, $offset, $perPage);

    $top_por_especialidad = [];
    if (!empty($abogadosPage)) {
        $destacadosCount = count($abogadosPage) >= 6 ? 6 : (count($abogadosPage) >= 4 ? 4 : count($abogadosPage));
        $top_por_especialidad = array_slice($abogadosPage, 0, $destacadosCount);
    }
    $statsExplorar = [
        'profesionales' => (int)$pdo->query("SELECT COUNT(*) FROM abogados WHERE rol='abogado'")->fetchColumn(),
        'materias' => (int)$pdo->query("SELECT COUNT(DISTINCT especialidad) FROM abogados WHERE rol='abogado' AND especialidad IS NOT NULL AND TRIM(especialidad) <> ''")->fetchColumn(),
        'clientes' => (int)$pdo->query("SELECT COUNT(*) FROM abogados WHERE rol='cliente'")->fetchColumn(),
    ];

    $quickSearches = [];
    if ($feedMode === 'cliente') {
        try {
            $sqlQuick = "SELECT id, nombre, especialidad, ciudad, ciudades_plaza, regiones_servicio, cobertura_nacional, likes, vistas FROM abogados WHERE " . visibleLawyerWhereClause() . " ORDER BY (CASE WHEN destacado_hasta IS NOT NULL AND destacado_hasta >= NOW() THEN 1 ELSE 0 END) DESC, (COALESCE(likes,0) * 3 + COALESCE(vistas,0)) DESC, id DESC LIMIT 80";
            $stQuick = $pdo->prepare($sqlQuick);
            $stQuick->execute();
            $rowsQuick = $stQuick->fetchAll() ?: [];
            $seenQuick = [];
            $labelByMateria = [
                'Derecho Penal' => 'Penalista',
                'Derecho Familiar' => 'Abogada/o de familia',
                'Derecho Laboral' => 'Laboral',
                'Derecho Civil' => 'Civil',
                'Derecho Comercial' => 'Comercial',
                'Derecho Tributario' => 'Tributario',
                'Protección al Consumidor' => 'Consumidor',
                'Derechos Humanos' => 'DD.HH.',
            ];
            foreach ($rowsQuick as $rq) {
                $materiaQuick = normalizeLawyerMateria((string)($rq['especialidad'] ?? ''));
                if ($materiaQuick === '') continue;
                $placeCity = '';
                foreach (['ciudades_plaza','ciudad'] as $colQ) {
                    $rawQ = trim((string)($rq[$colQ] ?? ''));
                    if ($rawQ === '') continue;
                    $partsQ = preg_split('/[,;|]+/', $rawQ) ?: [];
                    foreach ($partsQ as $pQ) {
                        $pQ = trim((string)$pQ);
                        if ($pQ !== '') { $placeCity = $pQ; break 2; }
                    }
                }
                $placeRegion = '';
                if ($placeCity === '') {
                    if ((int)($rq['cobertura_nacional'] ?? 0) === 1) {
                        $placeRegion = 'Todo Chile';
                    } else {
                        $regRaw = trim((string)($rq['regiones_servicio'] ?? ''));
                        if ($regRaw !== '') {
                            $partsR = preg_split('/[,;|]+/', $regRaw) ?: [];
                            foreach ($partsR as $pR) {
                                $pR = trim((string)$pR);
                                if ($pR !== '') { $placeRegion = $pR; break; }
                            }
                        }
                    }
                }
                $labelBase = $labelByMateria[$materiaQuick] ?? $materiaQuick;
                if ($placeCity !== '') {
                    $labelQuick = $labelBase . ' en ' . $placeCity;
                    $hrefQuick = '/explorar?' . http_build_query(['especialidad' => $materiaQuick, 'ciudad' => $placeCity]);
                } else if ($placeRegion !== '') {
                    $labelQuick = $labelBase . ($placeRegion === 'Todo Chile' ? ' en todo Chile' : ' en ' . $placeRegion);
                    $hrefQuick = '/explorar?' . http_build_query(['especialidad' => $materiaQuick, 'region' => $placeRegion]);
                } else {
                    continue;
                }
                $keyQuick = md5($hrefQuick . '|' . (function_exists('mb_strtolower') ? mb_strtolower($labelQuick, 'UTF-8') : strtolower($labelQuick)));
                if (isset($seenQuick[$keyQuick])) continue;
                $seenQuick[$keyQuick] = true;
                $quickSearches[] = ['label' => $labelQuick, 'href' => $hrefQuick];
                if (count($quickSearches) >= 6) break;
            }
        } catch (Throwable $e) {
            $quickSearches = [];
        }
    }

    $lawyerFeedLeads = ['pending' => 0, 'total' => 0, 'pending_new' => 0, 'pending_seen' => 0];
    if ($feedMode === 'abogado' && !empty($currentUser['id'])) {
        try {
            $feedWorkspace = lawyerWorkspaceContext($pdo, (array)$currentUser);
            $leadFeedScope = leadWorkspaceScope($feedWorkspace, (int)$currentUser['id']);
            $stLeadFeed = $pdo->prepare("SELECT UPPER(COALESCE(estado,'PENDIENTE')) AS estado_key, COUNT(*) AS total FROM contactos_revelados WHERE " . $leadFeedScope['sql'] . " GROUP BY UPPER(COALESCE(estado,'PENDIENTE'))");
            $stLeadFeed->execute($leadFeedScope['params']);
            foreach (($stLeadFeed->fetchAll() ?: []) as $lc) {
                $estadoKey = strtoupper(trim((string)($lc['estado_key'] ?? 'PENDIENTE')));
                $cnt = (int)($lc['total'] ?? 0);
                $lawyerFeedLeads['total'] += $cnt;
                if ($estadoKey === 'PENDIENTE') $lawyerFeedLeads['pending'] += $cnt;
            }
            if (dbColumnExists('contactos_revelados', 'abogado_vio_at')) {
                $stLeadPendingSplit = $pdo->prepare("SELECT SUM(CASE WHEN abogado_vio_at IS NULL THEN 1 ELSE 0 END) AS pending_new, SUM(CASE WHEN abogado_vio_at IS NOT NULL THEN 1 ELSE 0 END) AS pending_seen FROM contactos_revelados WHERE " . $leadFeedScope['sql'] . " AND UPPER(COALESCE(estado,'PENDIENTE')) = 'PENDIENTE'");
                $stLeadPendingSplit->execute($leadFeedScope['params']);
                $split = $stLeadPendingSplit->fetch() ?: [];
                $lawyerFeedLeads['pending_new'] = (int)($split['pending_new'] ?? 0);
                $lawyerFeedLeads['pending_seen'] = (int)($split['pending_seen'] ?? 0);
            } else {
                $lawyerFeedLeads['pending_new'] = (int)$lawyerFeedLeads['pending'];
                $lawyerFeedLeads['pending_seen'] = 0;
            }
        } catch (Throwable $e) {
            $lawyerFeedLeads = ['pending' => 0, 'total' => 0, 'pending_new' => 0, 'pending_seen' => 0];
        }
    }

return $renderer->render($response, 'feed.php', [
        'abogados' => $abogadosPage,
        'filtroActual' => $filtroActual !== '' ? $filtroActual : null,
        'lugar' => $lugar,
        'region' => $region,
        'sexo' => $sexoFiltro,
        'comuna' => $comuna,
        'experiencia' => $experiencia,
        'latUsuario' => $latUsuario,
        'lngUsuario' => $lngUsuario,
        'radioKm' => $radioKm,
        'modoDistancia' => $modoDistancia,
        'regiones_chile' => regionesChile(),
        'materias_catalogo' => $materiasCatalogo,
        'sexos_catalogo' => ['mujer','hombre','no_binario','prefiero_no_decir'],
        'comunas_sugeridas' => comunasChileSugeridas(),
        'top_por_especialidad' => $top_por_especialidad,
        'stats_explorar' => $statsExplorar,
        'quick_searches' => $quickSearches,
        'page' => $page,
        'per_page' => $perPage,
        'total_profiles' => $totalProfiles,
        'total_pages' => $totalPages,
        'current_user' => $currentUser,
        'feed_mode' => $feedMode,
        'lawyer_feed_leads' => $lawyerFeedLeads,
        'no_results_fallback' => $noResultsFallback ?? false,
        'no_results_message' => $noResultsMessage ?? '',
        'auth_gate' => buildAuthGateContract('/explorar'),
        'csrf_token' => ensureCsrfToken(),
    ]);
});

// ============================================================================
// RUTAS API
// ============================================================================

$app->post('/api/like/{id}', function (Request $request, Response $response, $args) {
    $id = (int)$args['id'];
    if (!isset($_SESSION['user_id'])) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'login_required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
    $pdo = getDB();

    try {
        $stmtViewer = $pdo->prepare("SELECT rol FROM abogados WHERE id = ? LIMIT 1");
        $stmtViewer->execute([(int)$_SESSION['user_id']]);
        $viewerRole = (string)($stmtViewer->fetchColumn() ?: '');
        if ($viewerRole !== 'cliente') {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'only_clients_can_like']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        ensureUniqueLikesTable();
        $stmtInsert = $pdo->prepare("INSERT IGNORE INTO abogado_likes (abogado_id, cliente_id, created_at) VALUES (?, ?, NOW())");
        $stmtInsert->execute([$id, (int)$_SESSION['user_id']]);
        $inserted = $stmtInsert->rowCount() > 0;

        if ($inserted) {
            $sql = "UPDATE abogados SET likes = likes + 1 WHERE id = ? AND rol = 'abogado'";
            $pdo->prepare($sql)->execute([$id]);
            recordWebMetricEvent('like', [
                'path' => '/api/like/' . $id,
                'content_type' => 'abogado_profile',
                'content_id' => $id,
                'source' => 'api_like'
            ], [
                'window_sec' => 31536000,
                'dedupe_scope' => 'like:u' . (int)$_SESSION['user_id'] . ':c' . $id
            ]);
        }

        $stmt = $pdo->prepare("SELECT likes FROM abogados WHERE id = ?");
        $stmt->execute([$id]);
        $likes = $stmt->fetchColumn() ?: 0;

        $response->getBody()->write(json_encode(['success' => true, 'likes' => $likes, 'already_liked' => !$inserted]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->post('/api/recommend/{id}', function (Request $request, Response $response, $args) {
    $id = (int)$args['id'];
    if (!isset($_SESSION['user_id'])) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'login_required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
    $pdo = getDB();

    try {
        $stmtViewer = $pdo->prepare("SELECT rol FROM abogados WHERE id = ? LIMIT 1");
        $stmtViewer->execute([(int)$_SESSION['user_id']]);
        $viewerRole = (string)($stmtViewer->fetchColumn() ?: '');
        if ($viewerRole !== 'cliente') {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'only_clients_can_recommend']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        ensureUniqueRecommendationsTable();
        $stmtInsert = $pdo->prepare("INSERT IGNORE INTO abogado_recomendaciones (abogado_id, cliente_id, created_at) VALUES (?, ?, NOW())");
        $stmtInsert->execute([$id, (int)$_SESSION['user_id']]);
        $inserted = $stmtInsert->rowCount() > 0;

        if ($inserted) {
            $pdo->prepare("UPDATE abogados SET recomendaciones = COALESCE(recomendaciones,0) + 1 WHERE id = ? AND rol='abogado'")->execute([$id]);
            recordWebMetricEvent('recommend', [
                'path' => '/api/recommend/' . $id,
                'content_type' => 'abogado_profile',
                'content_id' => $id,
                'source' => 'api_recommend'
            ], [
                'window_sec' => 31536000,
                'dedupe_scope' => 'recommend:u' . (int)$_SESSION['user_id'] . ':c' . $id
            ]);
        }

        $stmt = $pdo->prepare("SELECT recomendaciones FROM abogados WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $count = (int)($stmt->fetchColumn() ?: 0);

        $response->getBody()->write(json_encode(['success' => true, 'recomendaciones' => $count, 'already_recommended' => !$inserted]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Throwable $e) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => $e->getMessage()]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->post('/api/view/{id}', function (Request $request, Response $response, $args) {
    $id = (int)$args['id'];
    $pdo = getDB();

    try {
        if (!isset($_SESSION['user_id'])) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'login_required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $viewerId = (int)$_SESSION['user_id'];
        ensureUniqueViewsTable();

        $stmtViewer = $pdo->prepare("SELECT rol FROM abogados WHERE id = ? LIMIT 1");
        $stmtViewer->execute([$viewerId]);
        $viewerRole = (string)($stmtViewer->fetchColumn() ?: '');
        if ($viewerRole !== 'cliente') {
            $response->getBody()->write(json_encode(['success' => true, 'counted' => false, 'reason' => 'only_clients_counted']));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $stmtInsert = $pdo->prepare("INSERT IGNORE INTO abogado_views_unicas (abogado_id, viewer_id, created_at) VALUES (?, ?, NOW())");
        $stmtInsert->execute([$id, $viewerId]);
        $inserted = $stmtInsert->rowCount() > 0;

        if ($inserted) {
            $sql = "UPDATE abogados SET vistas = vistas + 1 WHERE id = ? AND rol = 'abogado'";
            $pdo->prepare($sql)->execute([$id]);
        }

        $stmt = $pdo->prepare("SELECT vistas FROM abogados WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $vistas = (int)($stmt->fetchColumn() ?: 0);
        $response->getBody()->write(json_encode(['success' => true, 'counted' => $inserted, 'vistas' => $vistas]));
        return $response->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
        $response->getBody()->write(json_encode(['success' => false]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
});

$app->post('/api/event', function (Request $request, Response $response) {
    if (!metricsLikelySameOrigin($request)) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'origin_not_allowed']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    $rateBucket = (string)floor(time() / 60);
    $rateKey = 'metrics_api_event_rate';
    $rateState = is_array($_SESSION[$rateKey] ?? null) ? (array)$_SESSION[$rateKey] : [];
    $currentRate = (int)($rateState[$rateBucket] ?? 0);
    if ($currentRate >= 80) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'rate_limited']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(429);
    }
    $rateState[$rateBucket] = $currentRate + 1;
    foreach (array_keys($rateState) as $b) {
        if ((int)$b < ((int)$rateBucket - 2)) unset($rateState[$b]);
    }
    $_SESSION[$rateKey] = $rateState;

    $rawBody = (string)$request->getBody();
    $json = json_decode($rawBody, true);
    $data = is_array($json) ? $json : (array)($request->getParsedBody() ?? []);
    $eventName = trim((string)($data['event'] ?? ''));
    $payload = $data['payload'] ?? [];

    $allowed = [
        'home_cta_clicked',
        'home_question_intent_submitted',
        'feed_primary_cta_clicked',
        'dashboard_primary_cta_clicked',
        'lead_weekly_limit_blocked_frontend',
        'lead_form_started',
        'lead_step_completed',
        'lead_validation_error',
        'lead_confirmation_opened',
        'lead_submitted_frontend',
        'lead_submit_failed_frontend',
        'location_filter_used',
        'profile_shared'
    ];

    if (!in_array($eventName, $allowed, true)) {
        $response->getBody()->write(json_encode(['success' => false, 'error' => 'invalid_event']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    if (!is_array($payload)) {
        $payload = [];
    }

    // Limita tamaño para evitar abuso de payload.
    $payload = array_slice($payload, 0, 10, true);
    foreach ($payload as $key => $value) {
        if (!is_scalar($value) && $value !== null) {
            unset($payload[$key]);
            continue;
        }
        $payload[$key] = is_string($value) ? substr($value, 0, 200) : $value;
    }

    $klass = metricsTrafficClass(metricsClientIp(), (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if ($klass !== 'human') {
        $response->getBody()->write(json_encode(['success' => true, 'ignored' => true]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    $metricName = $eventName === 'profile_shared' ? 'share' : 'interaction';
    $contentId = isset($payload['abogado_id']) ? (int)$payload['abogado_id'] : null;
    if ($contentId !== null && $contentId <= 0) $contentId = null;
    $contentSlug = trim((string)($payload['abogado_slug'] ?? ''));
    $trackPayload = $payload;
    $trackPayload['event_name'] = $eventName;
    recordWebMetricEvent($metricName, [
        'path' => metricsNormalizePath((string)$request->getUri()->getPath()),
        'content_type' => $contentId ? 'abogado_profile' : null,
        'content_id' => $contentId,
        'content_slug' => $contentSlug !== '' ? $contentSlug : null,
        'source' => 'api_event',
        'payload' => $trackPayload
    ], [
        'window_sec' => $metricName === 'share' ? 3600 : 300,
        'dedupe_scope' => 'ux:' . $eventName . ':sid:' . (session_id() ?: 'anon') . ':cid:' . ($contentId ?: 0)
    ]);

    trackEvent($eventName, $payload + ['source' => 'frontend']);
    $response->getBody()->write(json_encode(['success' => true]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/api/postalcode', function (Request $request, Response $response) {
    $q = $request->getQueryParams();
    $commune = normalizarTexto($q['commune'] ?? '');
    $street = normalizarTexto($q['street'] ?? '');
    $number = normalizarTexto($q['number'] ?? '');

    [$postal, $serviceReached, $errorCode] = lookupPostalCodeWithBoostr($commune, $street, $number);

    $payload = [
        'success' => $postal !== null,
        'postal_code' => $postal,
        'service_reached' => $serviceReached
    ];

    if ($postal === null) {
        $payload['error'] = $errorCode ?? 'not_found';
    }

    trackEvent('postalcode_lookup', [
        'commune' => $commune,
        'service_reached' => $serviceReached ? 1 : 0,
        'success' => $postal !== null ? 1 : 0,
        'error' => $errorCode
    ]);

    $response->getBody()->write(json_encode($payload));
    return $response->withHeader('Content-Type', 'application/json');
});

// ============================================================================
// RUTAS POST
// ============================================================================

$app->post('/guardar-abogado', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/completar-datos')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    $pdo = getDB();
    $stmtPerm = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
    $stmtPerm->execute([$_SESSION['user_id']]);
    $userPerm = $stmtPerm->fetch();
    if (!$userPerm || !userCanEditLawyerProfile((array)$userPerm)) {
        $_SESSION['mensaje'] = '⚠️ Debes solicitar habilitación de perfil abogado antes de editar tu perfil profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    if (userCanUseLawyerMode((array)$userPerm)) {
        $_SESSION['rol'] = 'abogado';
    }

    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        trackEvent('csrf_failed', ['route' => 'guardar-abogado']);
        $_SESSION['error'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $whatsapp = validarWhatsApp($data['whatsapp'] ?? '');
    if (!$whatsapp) {
        $_SESSION['error'] = 'El número de WhatsApp debe tener 9 dígitos y comenzar con 9.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $slug = createSlug($data['slug'] ?? '');
    if ($slug === '') {
        $slug = 'abogado-' . (int)$_SESSION['user_id'];
    }
    $web = !empty($data['web']) ? trim((string)$data['web']) : null;
    if ($web && !str_starts_with($web, 'http')) {
        $web = "https://" . $web;
    }
    $facebook = trim((string)($data['facebook'] ?? ''));
    $linkedin = trim((string)($data['linkedin'] ?? ''));
    if ($facebook !== '' && !str_starts_with($facebook, 'http')) {
        $facebook = 'https://' . ltrim($facebook, '/');
    }
    if ($linkedin !== '' && !str_starts_with($linkedin, 'http')) {
        $linkedin = 'https://' . ltrim($linkedin, '/');
    }
    $biografia = trim((string)($data['biografia'] ?? ''));
    $bioLen = function_exists('mb_strlen') ? mb_strlen($biografia, 'UTF-8') : strlen($biografia);
    if ($bioLen > 300) {
        $_SESSION['error'] = 'La sección "Sobre este perfil" es opcional y puede tener hasta 300 caracteres.';
        return $response->withHeader('Location', '/completar-datos?modo=abogado')->withStatus(302);
    }

    $coberturaNacional = !empty($data['cobertura_nacional']) ? 1 : 0;
    $materiasTaxonomia = lawyerMateriasTaxonomia();
    $materiaPrincipal = trim((string)($data['especialidad'] ?? ''));
    if (!array_key_exists($materiaPrincipal, $materiasTaxonomia)) {
        $_SESSION['error'] = 'Selecciona una materia principal válida.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }
    $submateriasRaw = trim((string)($data['submaterias_json'] ?? ''));
    $submaterias = [];
    if ($submateriasRaw !== '') {
        $decoded = json_decode($submateriasRaw, true);
        if (is_array($decoded)) {
            foreach ($decoded as $item) {
                $label = normalizarTexto((string)$item);
                if ($label !== '') {
                    $submaterias[] = $label;
                }
            }
        }
    }
    $submaterias = array_values(array_unique($submaterias));
    if (count($submaterias) > 3) {
        $_SESSION['error'] = 'Puedes seleccionar hasta 3 submaterias.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }
    foreach ($submaterias as $submateria) {
        if (!in_array($submateria, $materiasTaxonomia[$materiaPrincipal], true)) {
            $_SESSION['error'] = 'Hay submaterias no válidas para la materia seleccionada.';
            return $response->withHeader('Location', '/completar-datos')->withStatus(302);
        }
    }
    $materiaSecundaria = trim((string)($data['materia_secundaria'] ?? ''));
    if ($materiaSecundaria !== '' && !array_key_exists($materiaSecundaria, $materiasTaxonomia)) {
        $_SESSION['error'] = 'Selecciona una materia secundaria válida.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }
    if ($materiaSecundaria !== '' && $materiaSecundaria === $materiaPrincipal) {
        $_SESSION['error'] = 'La materia secundaria debe ser distinta de la principal.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }
    $submateriasSecRaw = trim((string)($data['submaterias_secundarias_json'] ?? ''));
    $submateriasSecundarias = [];
    if ($submateriasSecRaw !== '') {
        $decodedSec = json_decode($submateriasSecRaw, true);
        if (is_array($decodedSec)) {
            foreach ($decodedSec as $item) {
                $label = normalizarTexto((string)$item);
                if ($label !== '') $submateriasSecundarias[] = $label;
            }
        }
    }
    $submateriasSecundarias = array_values(array_unique($submateriasSecundarias));
    if (count($submateriasSecundarias) > 3) {
        $_SESSION['error'] = 'Puedes seleccionar hasta 3 submaterias en la materia secundaria.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }
    if ($materiaSecundaria === '') {
        $submateriasSecundarias = [];
    }
    foreach ($submateriasSecundarias as $submateria) {
        if ($materiaSecundaria === '' || !in_array($submateria, $materiasTaxonomia[$materiaSecundaria], true)) {
            $_SESSION['error'] = 'Hay submaterias no válidas para la materia secundaria.';
            return $response->withHeader('Location', '/completar-datos')->withStatus(302);
        }
    }
    $regionServicio = normalizarTexto($data['region_servicio'] ?? '');
    $comunasServicio = normalizarTexto($data['comunas_servicio'] ?? '');
    $ciudadesPlaza = normalizarTexto($data['ciudades_plaza'] ?? '');
    $sexoPerfil = trim((string)($data['sexo'] ?? ''));
    if ($sexoPerfil === '' || !in_array($sexoPerfil, ['mujer', 'hombre', 'no_binario', 'prefiero_no_decir'], true)) {
        $_SESSION['error'] = 'Selecciona tu sexo para publicar el perfil.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }
    $entrevistaPresencial = !empty($data['entrevista_presencial']) ? 1 : 0;
    $audienciasParaAbogadosPlaza = !empty($data['audiencias_para_abogados_plaza']) ? 1 : 0;
    $coloresMarcaPermitidos = ['azul', 'verde', 'dorado', 'rosa', 'vino', 'grafito'];
    $colorMarca = trim((string)($data['color_marca'] ?? ''));
    if ($colorMarca === '' || !in_array($colorMarca, $coloresMarcaPermitidos, true)) {
        $colorMarca = 'azul';
    }
    if ($coberturaNacional === 0 && $regionServicio === '' && $comunasServicio === '') {
        $_SESSION['error'] = 'Indica region/comunas de atencion o activa cobertura Todo Chile.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $servicioLat = parseCoordinate($data['servicio_lat'] ?? null);
    $servicioLng = parseCoordinate($data['servicio_lng'] ?? null);
    if ($servicioLat !== null && ($servicioLat < -90 || $servicioLat > 90)) {
        $servicioLat = null;
    }
    if ($servicioLng !== null && ($servicioLng < -180 || $servicioLng > 180)) {
        $servicioLng = null;
    }

    $anioTitulacion = (int)($data['anio_titulacion'] ?? 0);
    $experienciaInput = trim((string)($data['experiencia'] ?? ''));
    $currentYear = (int)date('Y');
    if ($anioTitulacion >= 1950 && $anioTitulacion <= $currentYear) {
        $aniosExp = max(0, $currentYear - $anioTitulacion);
        $experienciaInput = $aniosExp . ' años';
    }

    $allowedMediosPago = ['efectivo', 'transferencia', 'tarjeta_credito', 'tarjeta_debito', 'crypto'];
    $mediosPago = $data['medios_pago'] ?? [];
    if (!is_array($mediosPago)) {
        $mediosPago = [];
    }
    $mediosPago = array_values(array_unique(array_filter(array_map(static fn($v) => trim((string)$v), $mediosPago), static fn($v) => in_array($v, $allowedMediosPago, true))));
    $exhibirMediosPago = !empty($data['exhibir_medios_pago']) ? 1 : 0;
    if ($exhibirMediosPago === 1 && count($mediosPago) === 0) {
        $_SESSION['error'] = 'Si decides exhibir medios de pago, selecciona al menos uno.';
        return $response->withHeader('Location', '/completar-datos?modo=abogado')->withStatus(302);
    }

    $faqPreguntas = $data['faq_q'] ?? [];
    $faqRespuestas = $data['faq_a'] ?? [];
    if (!is_array($faqPreguntas)) $faqPreguntas = [];
    if (!is_array($faqRespuestas)) $faqRespuestas = [];
    $faqPersonalizadas = [];
    $maxFaq = min(4, max(count($faqPreguntas), count($faqRespuestas)));
    for ($i = 0; $i < $maxFaq; $i++) {
        $q = normalizarTexto($faqPreguntas[$i] ?? '');
        $a = normalizarTexto($faqRespuestas[$i] ?? '');
        if ($q === '' && $a === '') {
            continue;
        }
        if ($q === '' || $a === '') {
            $_SESSION['error'] = 'Cada pregunta frecuente personalizada debe tener pregunta y respuesta.';
            return $response->withHeader('Location', '/completar-datos?modo=abogado')->withStatus(302);
        }
        $qLen = function_exists('mb_strlen') ? mb_strlen($q, 'UTF-8') : strlen($q);
        $aLen = function_exists('mb_strlen') ? mb_strlen($a, 'UTF-8') : strlen($a);
        if ($qLen < 10 || $qLen > 140) {
            $_SESSION['error'] = 'Cada pregunta frecuente debe tener entre 10 y 140 caracteres.';
            return $response->withHeader('Location', '/completar-datos?modo=abogado')->withStatus(302);
        }
        if ($aLen < 20 || $aLen > 400) {
            $_SESSION['error'] = 'Cada respuesta frecuente debe tener entre 20 y 400 caracteres.';
            return $response->withHeader('Location', '/completar-datos?modo=abogado')->withStatus(302);
        }
        $faqPersonalizadas[] = ['q' => $q, 'a' => $a];
    }

    $updateFields = [
        'whatsapp = ?',
        'especialidad = ?',
        'submaterias = ?',
        'materia_secundaria = ?',
        'submaterias_secundarias = ?',
        'universidad = ?',
        'slug = ?',
        'experiencia = ?',
        'biografia = ?',
        'instagram = ?',
        'tiktok = ?',
        'web = ?',
        'cobertura_nacional = ?',
        'regiones_servicio = ?',
        'comunas_servicio = ?',
        'ciudades_plaza = ?',
        'sexo = ?',
        'color_marca = ?',
        'entrevista_presencial = ?',
        'audiencias_para_abogados_plaza = ?',
        'servicio_lat = ?',
        'servicio_lng = ?'
    ];
    $updateParams = [
        $whatsapp,
        $materiaPrincipal,
        (!empty($submaterias) ? json_encode($submaterias, JSON_UNESCAPED_UNICODE) : null),
        ($materiaSecundaria !== '' ? $materiaSecundaria : null),
        (!empty($submateriasSecundarias) ? json_encode($submateriasSecundarias, JSON_UNESCAPED_UNICODE) : null),
        ($data['universidad'] ?? null) ?: null,
        $slug,
        ($experienciaInput !== '' ? $experienciaInput : null), $biografia,
        ($data['instagram'] ?? null) ?: null, ($data['tiktok'] ?? null) ?: null, $web,
        $coberturaNacional, $regionServicio ?: null, $comunasServicio ?: null, ($ciudadesPlaza !== '' ? $ciudadesPlaza : null), $sexoPerfil, $colorMarca, $entrevistaPresencial, $audienciasParaAbogadosPlaza, $servicioLat, $servicioLng
    ];
    if (dbColumnExists('abogados', 'rut_abogado')) {
        $rutAbogado = normalizeRut($data['rut_abogado'] ?? '');
        if ($rutAbogado !== null && !rutValidoLocal($rutAbogado)) {
            $_SESSION['error'] = 'Ingresa un RUT de abogado válido.';
            return $response->withHeader('Location', '/completar-datos')->withStatus(302);
        }
        $updateFields[] = 'rut_abogado = ?';
        $updateParams[] = $rutAbogado;
    }
    if (dbColumnExists('abogados', 'anio_titulacion')) {
        $updateFields[] = 'anio_titulacion = ?';
        $updateParams[] = ($anioTitulacion >= 1950 && $anioTitulacion <= $currentYear) ? $anioTitulacion : null;
    }
    if (dbColumnExists('abogados', 'tiene_postitulo')) {
        $tienePostitulo = !empty($data['tiene_postitulo']) ? 1 : 0;
        $nombrePostitulo = normalizarTexto($data['nombre_postitulo'] ?? '');
        $universidadPostitulo = normalizarTexto($data['universidad_postitulo'] ?? '');
        $updateFields[] = 'tiene_postitulo = ?';
        $updateParams[] = $tienePostitulo;
        if (dbColumnExists('abogados', 'nombre_postitulo')) {
            $updateFields[] = 'nombre_postitulo = ?';
            $updateParams[] = ($tienePostitulo && $nombrePostitulo !== '') ? $nombrePostitulo : null;
        }
        if (dbColumnExists('abogados', 'universidad_postitulo')) {
            $updateFields[] = 'universidad_postitulo = ?';
            $updateParams[] = ($tienePostitulo && $universidadPostitulo !== '') ? $universidadPostitulo : null;
        }
    }
    if (dbColumnExists('abogados', 'tiene_diplomado')) {
        $tieneDiplomado = !empty($data['tiene_diplomado']) ? 1 : 0;
        $nombreDiplomado = normalizarTexto($data['nombre_diplomado'] ?? '');
        $universidadDiplomado = normalizarTexto($data['universidad_diplomado'] ?? '');
        $updateFields[] = 'tiene_diplomado = ?';
        $updateParams[] = $tieneDiplomado;
        if (dbColumnExists('abogados', 'nombre_diplomado')) {
            $updateFields[] = 'nombre_diplomado = ?';
            $updateParams[] = ($tieneDiplomado && $nombreDiplomado !== '') ? $nombreDiplomado : null;
        }
        if (dbColumnExists('abogados', 'universidad_diplomado')) {
            $updateFields[] = 'universidad_diplomado = ?';
            $updateParams[] = ($tieneDiplomado && $universidadDiplomado !== '') ? $universidadDiplomado : null;
        }
    }
    if (dbColumnExists('abogados', 'facebook')) {
        $updateFields[] = 'facebook = ?';
        $updateParams[] = ($facebook !== '' ? $facebook : null);
    }
    if (dbColumnExists('abogados', 'linkedin')) {
        $updateFields[] = 'linkedin = ?';
        $updateParams[] = ($linkedin !== '' ? $linkedin : null);
    }
    if (dbColumnExists('abogados', 'exhibir_medios_pago')) {
        $updateFields[] = 'exhibir_medios_pago = ?';
        $updateParams[] = $exhibirMediosPago;
    }
    if (dbColumnExists('abogados', 'medios_pago_json')) {
        $updateFields[] = 'medios_pago_json = ?';
        $updateParams[] = !empty($mediosPago) ? json_encode($mediosPago, JSON_UNESCAPED_UNICODE) : null;
    }
    if (dbColumnExists('abogados', 'faq_personalizadas_json')) {
        $updateFields[] = 'faq_personalizadas_json = ?';
        $updateParams[] = !empty($faqPersonalizadas) ? json_encode($faqPersonalizadas, JSON_UNESCAPED_UNICODE) : null;
    }
    if (dbColumnExists('abogados', 'solicito_habilitacion_abogado')) {
        $updateFields[] = 'solicito_habilitacion_abogado = 1';
    }
    if (dbColumnExists('abogados', 'estado_verificacion_abogado')) {
        $updateFields[] = "estado_verificacion_abogado = CASE
            WHEN COALESCE(abogado_verificado,0) = 1 THEN estado_verificacion_abogado
            ELSE 'pendiente'
        END";
    }
    if (dbColumnExists('abogados', 'fecha_solicitud_habilitacion_abogado')) {
        $updateFields[] = 'fecha_solicitud_habilitacion_abogado = COALESCE(fecha_solicitud_habilitacion_abogado, NOW())';
    }
    if (dbColumnExists('abogados', 'puede_publicar_casos')) {
        $updateFields[] = 'puede_publicar_casos = COALESCE(puede_publicar_casos, 1)';
    }
    $perfilTmp = [
        'whatsapp' => $whatsapp,
        'especialidad' => $materiaPrincipal,
        'slug' => $slug,
        'universidad' => ($data['universidad'] ?? null),
        'experiencia' => ($experienciaInput !== '' ? $experienciaInput : null),
        'anio_titulacion' => (($anioTitulacion >= 1950 && $anioTitulacion <= $currentYear) ? $anioTitulacion : null),
        'biografia' => $biografia,
        'cobertura_nacional' => $coberturaNacional,
        'regiones_servicio' => $regionServicio ?: null,
        'comunas_servicio' => $comunasServicio ?: null,
        'sexo' => $sexoPerfil,
        'instagram' => ($data['instagram'] ?? null),
        'tiktok' => ($data['tiktok'] ?? null),
        'web' => $web,
        'facebook' => ($facebook !== '' ? $facebook : null),
        'linkedin' => ($linkedin !== '' ? $linkedin : null),
        'submaterias' => !empty($submaterias) ? json_encode($submaterias, JSON_UNESCAPED_UNICODE) : null,
        'materia_secundaria' => ($materiaSecundaria !== '' ? $materiaSecundaria : null),
        'submaterias_secundarias' => !empty($submateriasSecundarias) ? json_encode($submateriasSecundarias, JSON_UNESCAPED_UNICODE) : null,
    ];
    $perfilCompletitudPct = lawyerProfileCompletionPercent($perfilTmp);
    if (dbColumnExists('abogados', 'perfil_completitud_pct')) {
        $updateFields[] = 'perfil_completitud_pct = ?';
        $updateParams[] = $perfilCompletitudPct;
    }
    if (dbColumnExists('abogados', 'activo')) {
        $updateFields[] = 'activo = ?';
        $updateParams[] = ($perfilCompletitudPct >= 80 ? 1 : 0);
    }
    if ($perfilCompletitudPct >= 80) {
        $updateFields[] = "rol = 'abogado'";
        if (dbColumnExists('abogados', 'abogado_habilitado')) {
            $updateFields[] = 'abogado_habilitado = 1';
        }
        if (dbColumnExists('abogados', 'estado_verificacion_abogado')) {
            $updateFields[] = "estado_verificacion_abogado = CASE WHEN COALESCE(abogado_verificado,0)=1 THEN 'verificado' ELSE 'pendiente' END";
        }
    }
    $updateParams[] = $_SESSION['user_id'];
    $sql = "UPDATE abogados SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $pdo->prepare($sql)->execute($updateParams);

    $_SESSION['slug'] = $slug;
    $yaPromovido = !empty($userPerm['abogado_habilitado']) || !empty($userPerm['abogado_verificado']) || (($userPerm['rol'] ?? '') === 'abogado');
    if ($perfilCompletitudPct >= 80) {
        $_SESSION['mensaje'] = '✅ Perfil guardado (' . $perfilCompletitudPct . '%). Tu perfil ya puede aparecer en el listado. El badge PJUD se activa cuando admin valide tu RUT manualmente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } else {
        $_SESSION['mensaje'] = '✅ Perfil guardado (' . $perfilCompletitudPct . '%). Completa al menos 80% para publicar tu perfil. La verificación PJUD se revisa después por admin.';
        $_SESSION['tipo_mensaje'] = 'info';
    }
    $nextAfterSave = $yaPromovido ? '/dashboard/cuenta' : '/acceso-profesional';
    return $response->withHeader('Location', $nextAfterSave)->withStatus(302);
});

$app->post('/guardar-cliente', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) {
        return $response->withStatus(403);
    }

    $_SESSION['mensaje'] = 'La publicación de consultas está desactivada. Usa el directorio para explorar abogados.';
    $_SESSION['tipo_mensaje'] = 'info';
    return $response->withHeader('Location', '/explorar')->withStatus(302);

    $data = (array)($request->getParsedBody() ?? []);
    $pdo = getDB();
    ensureLeadLifecycleColumns();
    $stmtPerm = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
    $stmtPerm->execute([$_SESSION['user_id']]);
    $userPerm = $stmtPerm->fetch();
    if (!$userPerm || !userCanPublishCases($userPerm)) {
        return $response->withStatus(403);
    }
    $_SESSION['rol'] = 'cliente';

    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        trackEvent('lead_submit_failed', ['reason' => 'csrf']);
        $_SESSION['error'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    // Honeypot para bots básicos.
    if (!empty(trim((string)($data['website'] ?? '')))) {
        trackEvent('lead_submit_failed', ['reason' => 'honeypot']);
        $_SESSION['error'] = '⚠️ No fue posible procesar la solicitud.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $whatsapp = validarWhatsApp($data['whatsapp'] ?? '');
    if (!$whatsapp) {
        trackEvent('lead_submit_failed', ['reason' => 'invalid_whatsapp']);
        $_SESSION['error'] = '⚠️ El número de WhatsApp debe tener 9 dígitos y comenzar con 9.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $especialidad = trim((string)($data['especialidad'] ?? ''));
    if (!in_array($especialidad, especialidadesClientePermitidas(), true)) {
        trackEvent('lead_submit_failed', ['reason' => 'invalid_specialty']);
        $_SESSION['error'] = '⚠️ Selecciona una materia legal válida.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $ciudad = normalizarTexto($data['ciudad'] ?? '');
    $largoCiudad = function_exists('mb_strlen')
        ? mb_strlen($ciudad, 'UTF-8')
        : strlen($ciudad);
    if ($largoCiudad < 2 || $largoCiudad > 80) {
        trackEvent('lead_submit_failed', ['reason' => 'invalid_location_length', 'length' => $largoCiudad]);
        $_SESSION['error'] = '⚠️ Indica el lugar del caso (2 a 80 caracteres).';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $esExtranjero = !empty($data['es_extranjero']) ? 1 : 0;
    $rutCliente = normalizeRut($data['rut_cliente'] ?? '');
    if ($esExtranjero !== 1) {
        if ($rutCliente === null || !rutValidoLocal($rutCliente)) {
            trackEvent('lead_submit_failed', ['reason' => 'invalid_rut_local']);
            $_SESSION['error'] = '⚠️ Ingresa un RUT chileno válido o marca "Soy extranjero".';
            return $response->withHeader('Location', '/completar-datos')->withStatus(302);
        }

        [$rutOkBoostr, $boostrReached] = validateRutWithBoostr($rutCliente);
        if ($rutOkBoostr === false) {
            trackEvent('lead_submit_failed', ['reason' => 'invalid_rut_boostr']);
            $_SESSION['error'] = '⚠️ El RUT no pasó validación.';
            return $response->withHeader('Location', '/completar-datos')->withStatus(302);
        }
        if (!$boostrReached) {
            trackEvent('rut_validation_fallback_local', ['rut' => $rutCliente]);
        }
    } else {
        $rutCliente = null;
    }

    $direccionCalle = normalizarTexto($data['direccion_calle'] ?? '');
    $direccionNumero = normalizarTexto($data['direccion_numero'] ?? '');
    $codigoPostal = strtoupper(trim((string)($data['codigo_postal'] ?? '')));
    $codigoPostal = preg_replace('/[^0-9A-Z]/', '', $codigoPostal);
    if (strlen($codigoPostal) > 12) {
        $codigoPostal = substr($codigoPostal, 0, 12);
    }

    if ($codigoPostal === '' && $direccionCalle !== '' && $direccionNumero !== '') {
        [$postalLookup, $postalServiceReached, $postalError] = lookupPostalCodeWithBoostr($ciudad, $direccionCalle, $direccionNumero);
        if ($postalLookup !== null) {
            $codigoPostal = $postalLookup;
        } else {
            trackEvent('postalcode_lookup_failed_on_submit', [
                'service_reached' => $postalServiceReached ? 1 : 0,
                'error' => $postalError
            ]);
        }
    }

    $descripcion = normalizarTexto($data['descripcion'] ?? '');
    $largoDescripcion = function_exists('mb_strlen')
        ? mb_strlen($descripcion, 'UTF-8')
        : strlen($descripcion);

    if ($largoDescripcion < 20 || $largoDescripcion > 2000) {
        trackEvent('lead_submit_failed', ['reason' => 'invalid_description_length', 'length' => $largoDescripcion]);
        $_SESSION['error'] = '⚠️ Describe tu caso entre 20 y 2000 caracteres.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $stmt = $pdo->prepare("SELECT rol, whatsapp, descripcion_caso, created_at, ultima_consulta_cliente_at FROM abogados WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        trackEvent('lead_submit_failed', ['reason' => 'role_mismatch']);
        return $response->withStatus(403);
    }

    $weeklyLimit = clienteWeeklyLimitInfo($user, 7);
    if (!empty($weeklyLimit['is_blocked'])) {
        trackEvent('lead_submit_failed', [
            'reason' => 'weekly_limit',
            'next_available_at' => $weeklyLimit['next_available_at']
        ]);
        $_SESSION['error'] = '⏳ Por política anti-spam solo puedes publicar 1 consulta nueva cada 7 días. Próxima ventana: '
            . ($weeklyLimit['next_available_label'] ?? 'en 7 días')
            . ' (hora servidor).';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $isNewConsult = !$weeklyLimit['has_active_case'];

    // Si el caso ya estaba publicado, preserva la fecha original para no \"subir\" artificialmente el ranking.
    $sql = "UPDATE abogados
            SET whatsapp = ?,
                especialidad = ?,
                ciudad = ?,
                rut_cliente = ?,
                es_extranjero = ?,
                direccion_calle = ?,
                direccion_numero = ?,
                codigo_postal = ?,
                descripcion_caso = ?,
                created_at = CASE WHEN descripcion_caso IS NULL THEN NOW() ELSE created_at END,
                ultima_consulta_cliente_at = CASE
                    WHEN descripcion_caso IS NULL THEN NOW()
                    ELSE COALESCE(ultima_consulta_cliente_at, created_at)
                END
            WHERE id = ?";
    $pdo->prepare($sql)->execute([
        $whatsapp,
        $especialidad,
        $ciudad,
        $rutCliente,
        $esExtranjero,
        $direccionCalle !== '' ? $direccionCalle : null,
        $direccionNumero !== '' ? $direccionNumero : null,
        $codigoPostal !== '' ? $codigoPostal : null,
        $descripcion,
        $_SESSION['user_id']
    ]);

    trackEvent('lead_submitted', [
        'specialty' => $especialidad,
        'location' => $ciudad,
        'is_foreign' => $esExtranjero,
        'has_rut' => $rutCliente !== null ? 1 : 0,
        'has_postalcode' => $codigoPostal !== '' ? 1 : 0,
        'description_length' => $largoDescripcion,
        'submission_mode' => $isNewConsult ? 'new' : 'update'
    ]);
    $_SESSION['mensaje'] = $isNewConsult
        ? '✅ Tu consulta fue publicada. Recuerda: solo puedes ingresar 1 consulta nueva cada 7 días.'
        : '✅ Tu consulta fue actualizada correctamente. Recuerda: solo puedes ingresar 1 consulta nueva cada 7 días.';
    $_SESSION['tipo_mensaje'] = 'success';

    return $response->withHeader('Location', '/completar-datos?modo=cliente')->withStatus(302);
});

$app->post('/actualizar-estado-caso', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard')->withStatus(302);
    }

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        trackEvent('csrf_failed', ['route' => 'actualizar-estado-caso']);
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga el panel.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }
    $clienteId = (int)($data['cliente_id'] ?? 0);
    $leadId = (int)($data['id_caso'] ?? 0);
    $nuevoEstado = trim((string)($data['estado'] ?? ''));
    $abogadoId = $_SESSION['user_id'];
    $pdo = getDB();
    ensureLeadLifecycleColumns();
    $stmtPerm = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
    $stmtPerm->execute([$abogadoId]);
    $abogadoUser = $stmtPerm->fetch();
    if (!$abogadoUser || !userCanAccessLawyerDashboard((array)$abogadoUser)) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';
    $workspace = lawyerWorkspaceContext($pdo, (array)$abogadoUser);
    $estadosPermitidos = ['PENDIENTE', 'CONTACTADO', 'GANADO', 'PERDIDO', 'CANCELADO'];
    if (($leadId <= 0 && $clienteId <= 0) || !in_array($nuevoEstado, $estadosPermitidos, true)) {
        $_SESSION['mensaje'] = '⚠️ Datos inválidos para actualizar estado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }
    $presupuesto = is_numeric($data['presupuesto'] ?? null) ? (float)$data['presupuesto'] : 0.0;
    if ($presupuesto < 0) {
        $presupuesto = 0.0;
    }
    if ($nuevoEstado === 'GANADO') {
        $_SESSION['mensaje'] = 'ℹ️ El cierre se realiza desde una cotización aceptada.';
        $_SESSION['tipo_mensaje'] = 'info';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }

    $estadosConCierre = ['GANADO', 'PERDIDO', 'CANCELADO'];
    $leadScope = leadWorkspaceScope($workspace, (int)$abogadoId, 'cr');
    $leadScopeSafe = $leadScope;
    if (!dbColumnExists('contactos_revelados', 'equipo_id')) {
        $leadScopeSafe = [
            'sql' => 'cr.abogado_id = ?',
            'params' => [(int)$abogadoId],
        ];
    }
    $leadRecord = null;
    $leadScopeForSelect = $leadScope;
    if (!dbColumnExists('contactos_revelados', 'equipo_id')) {
        $leadScopeForSelect = $leadScopeSafe;
    }
    try {
        if ($leadId > 0) {
            $stLead = $pdo->prepare("
                SELECT cr.id, cr.estado, cr.cliente_id, c.nombre AS cliente_nombre
                FROM contactos_revelados cr
                LEFT JOIN abogados c ON c.id = cr.cliente_id
                WHERE cr.id = ? AND " . $leadScopeForSelect['sql'] . "
                LIMIT 1
            ");
            $stLead->execute(array_merge([$leadId], $leadScopeForSelect['params']));
        } else {
            $stLead = $pdo->prepare("
                SELECT cr.id, cr.estado, cr.cliente_id, c.nombre AS cliente_nombre
                FROM contactos_revelados cr
                LEFT JOIN abogados c ON c.id = cr.cliente_id
                WHERE cr.cliente_id = ? AND " . $leadScopeForSelect['sql'] . "
                ORDER BY cr.id DESC
                LIMIT 1
            ");
            $stLead->execute(array_merge([$clienteId], $leadScopeForSelect['params']));
        }
        $leadRecord = $stLead->fetch() ?: null;
    } catch (Throwable $e) {
        if ($leadScopeForSelect !== $leadScopeSafe) {
            try {
                if ($leadId > 0) {
                    $stLead = $pdo->prepare("
                        SELECT cr.id, cr.estado, cr.cliente_id, c.nombre AS cliente_nombre
                        FROM contactos_revelados cr
                        LEFT JOIN abogados c ON c.id = cr.cliente_id
                        WHERE cr.id = ? AND " . $leadScopeSafe['sql'] . "
                        LIMIT 1
                    ");
                    $stLead->execute(array_merge([$leadId], $leadScopeSafe['params']));
                } else {
                    $stLead = $pdo->prepare("
                        SELECT cr.id, cr.estado, cr.cliente_id, c.nombre AS cliente_nombre
                        FROM contactos_revelados cr
                        LEFT JOIN abogados c ON c.id = cr.cliente_id
                        WHERE cr.cliente_id = ? AND " . $leadScopeSafe['sql'] . "
                        ORDER BY cr.id DESC
                        LIMIT 1
                    ");
                    $stLead->execute(array_merge([$clienteId], $leadScopeSafe['params']));
                }
                $leadRecord = $stLead->fetch() ?: null;
            } catch (Throwable $retryErr) {
                $leadRecord = null;
            }
        } else {
            $leadRecord = null;
        }
    }
    
    $prevState = strtoupper(trim((string)($leadRecord['estado'] ?? '')));
    $shouldMarkReopened = ($nuevoEstado === 'CONTACTADO' && in_array($prevState, ['PERDIDO', 'CANCELADO'], true));

    if ($leadId > 0) {
        if (in_array($nuevoEstado, $estadosConCierre, true)) {
            $sql = "UPDATE contactos_revelados
                    SET estado = ?, fecha_cierre = NOW(), estado_updated_at = NOW()
                    WHERE id = ? AND " . $leadScope['sql'];
        } else {
            $sql = "UPDATE contactos_revelados
                    SET estado = ?, fecha_cierre = NULL, estado_updated_at = NOW()
                    WHERE id = ? AND " . $leadScope['sql'];
        }
        try {
            $pdo->prepare($sql)->execute(array_merge([$nuevoEstado, $leadId], $leadScope['params']));
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '42S22') {
                $sqlFallback = str_replace('cr.', '', $sql);
                $pdo->prepare($sqlFallback)->execute(array_merge([$nuevoEstado, $leadId], $leadScopeSafe['params']));
            } else {
                throw $e;
            }
        }
        if ($nuevoEstado === 'GANADO' && $presupuesto > 0) {
            try {
                $pdo->prepare("UPDATE contactos_revelados SET presupuesto = ? WHERE id = ? AND " . $leadScope['sql'])
                    ->execute(array_merge([$presupuesto, $leadId], $leadScope['params']));
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '42S22') {
                    $pdo->prepare("UPDATE contactos_revelados SET presupuesto = ? WHERE id = ? AND " . $leadScopeSafe['sql'])
                        ->execute(array_merge([$presupuesto, $leadId], $leadScopeSafe['params']));
                } else {
                    throw $e;
                }
            }
        }
        if ($shouldMarkReopened && dbColumnExists('contactos_revelados', 'reabierto_at')) {
            $reopenSql = "UPDATE contactos_revelados
                          SET reabierto_at = NOW(),
                              reabierto_count = COALESCE(reabierto_count, 0) + 1
                          WHERE id = ? AND " . $leadScope['sql'];
            try {
                $pdo->prepare($reopenSql)->execute(array_merge([$leadId], $leadScope['params']));
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '42S22') {
                    $reopenSqlFallback = str_replace('cr.', '', $reopenSql);
                    $pdo->prepare($reopenSqlFallback)->execute(array_merge([$leadId], $leadScopeSafe['params']));
                } else {
                    throw $e;
                }
            }
        }
    } else {
        if (in_array($nuevoEstado, $estadosConCierre, true)) {
            $sql = "UPDATE contactos_revelados 
                    SET estado = ?, fecha_cierre = NOW(), estado_updated_at = NOW()
                    WHERE cliente_id = ? AND " . $leadScope['sql'];
        } else {
            $sql = "UPDATE contactos_revelados 
                    SET estado = ?, fecha_cierre = NULL, estado_updated_at = NOW()
                    WHERE cliente_id = ? AND " . $leadScope['sql'];
        }
        try {
            $pdo->prepare($sql)->execute(array_merge([$nuevoEstado, $clienteId], $leadScope['params']));
        } catch (PDOException $e) {
            if ((string)$e->getCode() === '42S22') {
                $sqlFallback = str_replace('cr.', '', $sql);
                $pdo->prepare($sqlFallback)->execute(array_merge([$nuevoEstado, $clienteId], $leadScopeSafe['params']));
            } else {
                throw $e;
            }
        }
        if ($nuevoEstado === 'GANADO' && $presupuesto > 0) {
            try {
                $pdo->prepare("UPDATE contactos_revelados SET presupuesto = ? WHERE cliente_id = ? AND " . $leadScope['sql'])
                    ->execute(array_merge([$presupuesto, $clienteId], $leadScope['params']));
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '42S22') {
                    $pdo->prepare("UPDATE contactos_revelados SET presupuesto = ? WHERE cliente_id = ? AND " . $leadScopeSafe['sql'])
                        ->execute(array_merge([$presupuesto, $clienteId], $leadScopeSafe['params']));
                } else {
                    throw $e;
                }
            }
        }
        if ($shouldMarkReopened && dbColumnExists('contactos_revelados', 'reabierto_at')) {
            $reopenSql = "UPDATE contactos_revelados
                          SET reabierto_at = NOW(),
                              reabierto_count = COALESCE(reabierto_count, 0) + 1
                          WHERE cliente_id = ? AND " . $leadScope['sql'];
            try {
                $pdo->prepare($reopenSql)->execute(array_merge([$clienteId], $leadScope['params']));
            } catch (PDOException $e) {
                if ((string)$e->getCode() === '42S22') {
                    $reopenSqlFallback = str_replace('cr.', '', $reopenSql);
                    $pdo->prepare($reopenSqlFallback)->execute(array_merge([$clienteId], $leadScopeSafe['params']));
                } else {
                    throw $e;
                }
            }
        }
    }

    if (!empty($workspace['team_id']) && !empty($leadRecord['id'])) {
        $clientName = trim((string)($leadRecord['cliente_nombre'] ?? 'Cliente'));
        $beforeState = strtoupper(trim((string)($leadRecord['estado'] ?? 'PENDIENTE')));
        $meta = $clientName !== '' ? $clientName : 'Lead';
        if ($beforeState !== $nuevoEstado) {
            $meta .= ' · ' . $beforeState . ' → ' . $nuevoEstado;
        }
        if ($nuevoEstado === 'GANADO' && $presupuesto > 0) {
            $meta .= ' · ' . formatClpAmount($presupuesto);
        }
        recordLawyerTeamActivity(
            $pdo,
            (int)$workspace['team_id'],
            (int)$abogadoId,
            'lead_status_changed',
            'lead',
            (int)$leadRecord['id'],
            'Lead marcado como ' . ucfirst(strtolower($nuevoEstado)),
            $meta
        );
    }

    $_SESSION['mensaje'] = '✅ Estado actualizado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    
    return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
});

$app->post('/actualizar-seguimiento', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard')->withStatus(302);
    }

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        trackEvent('csrf_failed', ['route' => 'actualizar-seguimiento']);
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga el panel.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }
    $clienteId = (int)($data['cliente_id'] ?? 0);
    $abogadoId = $_SESSION['user_id'];
    $pdo = getDB();
    $stmtPerm = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
    $stmtPerm->execute([$abogadoId]);
    $abogadoUser = $stmtPerm->fetch();
    if (!$abogadoUser || !userCanAccessLawyerDashboard((array)$abogadoUser)) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';
    $workspace = lawyerWorkspaceContext($pdo, (array)$abogadoUser);
    if ($clienteId <= 0) {
        $_SESSION['mensaje'] = '⚠️ Caso inválido.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }
    $presupuesto = is_numeric($data['presupuesto'] ?? null) ? (float)$data['presupuesto'] : 0.0;
    if ($presupuesto < 0) {
        $presupuesto = 0.0;
    }
    $seguimiento = substr(normalizarTexto($data['seguimiento'] ?? ''), 0, 5000);
    $consulta = substr(normalizarTexto($data['consulta'] ?? ''), 0, 5000);
    
    $leadScope = leadWorkspaceScope($workspace, (int)$abogadoId);
    $sql = "UPDATE contactos_revelados 
            SET presupuesto = ?, seguimiento = ?, consulta = ?
            WHERE cliente_id = ? AND " . $leadScope['sql'];
    
    $pdo->prepare($sql)->execute(array_merge([
        $presupuesto,
        $seguimiento,
        $consulta,
        $clienteId
    ], $leadScope['params']));

    $_SESSION['mensaje'] = '✅ Seguimiento actualizado.';
    $_SESSION['tipo_mensaje'] = 'success';
    
    return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
});

$app->post('/actualizar-monto-caso', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        trackEvent('csrf_failed', ['route' => 'actualizar-monto-caso']);
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga el panel.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }
    $leadId = (int)($data['id_caso'] ?? 0);
    $presupuesto = is_numeric($data['presupuesto'] ?? null) ? (float)$data['presupuesto'] : 0.0;
    if ($leadId <= 0 || $presupuesto <= 0) {
        $_SESSION['mensaje'] = '⚠️ Debes ingresar un monto válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }
    $pdo = getDB();
    $stmtPerm = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
    $stmtPerm->execute([$_SESSION['user_id']]);
    $abogadoUser = $stmtPerm->fetch();
    if (!$abogadoUser || !userCanAccessLawyerDashboard((array)$abogadoUser)) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $abogadoId = (int)$_SESSION['user_id'];
    $workspace = lawyerWorkspaceContext($pdo, (array)$abogadoUser);
    $leadScope = leadWorkspaceScope($workspace, $abogadoId);
    $stmt = $pdo->prepare("UPDATE contactos_revelados SET presupuesto = ? WHERE id = ? AND " . $leadScope['sql']);
    $stmt->execute(array_merge([$presupuesto, $leadId], $leadScope['params']));
    $_SESSION['mensaje'] = '✅ Monto actualizado.';
    $_SESSION['tipo_mensaje'] = 'success';
    return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
});

$app->post('/dashboard/leads/asignar', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard/leads')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga el panel.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard/leads')->withStatus(302);
    }
    $leadId = (int)($data['id_caso'] ?? 0);
    $assignedLawyerId = (int)($data['assigned_abogado_id'] ?? 0);
    if ($leadId <= 0 || $assignedLawyerId <= 0) {
        $_SESSION['mensaje'] = '⚠️ Debes seleccionar un lead y un responsable válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard/leads')->withStatus(302);
    }

    $pdo = getDB();
    $stmtPerm = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $stmtPerm->execute([(int)$_SESSION['user_id']]);
    $lawyerUser = $stmtPerm->fetch() ?: [];
    if (!$lawyerUser || !userCanAccessLawyerDashboard((array)$lawyerUser)) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }

    $workspace = lawyerWorkspaceContext($pdo, (array)$lawyerUser);
    if (empty($workspace['team_id'])) {
        $_SESSION['mensaje'] = 'ℹ️ La asignación de leads está disponible cuando trabajas dentro de un team.';
        $_SESSION['tipo_mensaje'] = 'info';
        return $response->withHeader('Location', '/dashboard/leads')->withStatus(302);
    }

    $stMember = $pdo->prepare("
        SELECT tm.*
        FROM abogado_equipo_miembros tm
        WHERE tm.equipo_id = ? AND tm.abogado_id = ? AND tm.estado = 'active'
        LIMIT 1
    ");
    $stMember->execute([(int)$workspace['team_id'], $assignedLawyerId]);
    $member = $stMember->fetch() ?: null;
    if (!$member) {
        $_SESSION['mensaje'] = '⚠️ El abogado seleccionado no pertenece al team activo.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard/leads')->withStatus(302);
    }

    $stLead = $pdo->prepare("
        SELECT cr.id, cr.assigned_abogado_id, c.nombre AS cliente_nombre, a.nombre AS assigned_nombre
        FROM contactos_revelados cr
        LEFT JOIN abogados c ON c.id = cr.cliente_id
        LEFT JOIN abogados a ON a.id = cr.assigned_abogado_id
        WHERE cr.id = ? AND cr.equipo_id = ?
        LIMIT 1
    ");
    $stLead->execute([$leadId, (int)$workspace['team_id']]);
    $leadRow = $stLead->fetch() ?: null;

    $pdo->prepare("UPDATE contactos_revelados SET assigned_abogado_id = ?, estado_updated_at = NOW() WHERE id = ? AND equipo_id = ?")
        ->execute([$assignedLawyerId, $leadId, (int)$workspace['team_id']]);

    if ($leadRow) {
        $previousName = trim((string)($leadRow['assigned_nombre'] ?? ''));
        $newName = trim((string)($member['abogado_nombre'] ?? $member['nombre_invitado'] ?? $member['email'] ?? 'Miembro'));
        recordLawyerTeamActivity(
            $pdo,
            (int)$workspace['team_id'],
            (int)$_SESSION['user_id'],
            'lead_reassigned',
            'lead',
            $leadId,
            'Lead reasignado',
            trim((string)($leadRow['cliente_nombre'] ?? 'Lead')) . ' · ' . ($previousName !== '' ? $previousName : 'Sin responsable') . ' → ' . $newName
        );
    }

    $_SESSION['mensaje'] = '✅ Lead reasignado dentro del team.';
    $_SESSION['tipo_mensaje'] = 'success';
    return $response->withHeader('Location', '/dashboard/leads')->withStatus(302);
});

$app->post('/agregar-prospecto-crm', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard')->withStatus(302);
    }

    $data = (array)($request->getParsedBody() ?? []);
    $pdo = getDB();
    $stmtPerm = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
    $stmtPerm->execute([$_SESSION['user_id']]);
    $abogadoUser = $stmtPerm->fetch();
    if (!$abogadoUser || !userCanAccessLawyerDashboard((array)$abogadoUser)) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';

    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        trackEvent('csrf_failed', ['route' => 'agregar-prospecto-crm']);
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }

    $whatsapp = validarWhatsApp($data['whatsapp'] ?? '');
    if (!$whatsapp) {
        $_SESSION['mensaje'] = '⚠️ El número de WhatsApp debe tener 9 dígitos y comenzar con 9.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
    }

    try {
        $pdo->beginTransaction();

        $nombre = normalizarTexto($data['nombre'] ?? '');
        if ($nombre === '') {
            $nombre = 'Prospecto CRM';
        }

        $slugBase = createSlug($nombre);
        if ($slugBase === '') {
            $slugBase = 'prospecto-crm';
        }

        $slug = $slugBase;
        $i = 1;
        $stmtSlug = $pdo->prepare("SELECT COUNT(*) FROM abogados WHERE slug = ?");
        while (true) {
            $stmtSlug->execute([$slug]);
            if ((int)$stmtSlug->fetchColumn() === 0) {
                break;
            }
            $slug = $slugBase . '-' . $i;
            $i++;
        }

        $sql = "INSERT INTO abogados (slug, nombre, whatsapp, especialidad, email, ciudad, rol, activo, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'cliente', 1, NOW())";
        $pdo->prepare($sql)->execute([
            $slug,
            $nombre,
            $whatsapp,
            normalizeLawyerMateria(trim((string)($data['especialidad'] ?? 'Derecho Civil'))) ?: 'Derecho Civil',
            trim((string)($data['email'] ?? '')) ?: null,
            normalizarTexto($data['ciudad'] ?? '') ?: null
        ]);

        $nuevoClienteId = $pdo->lastInsertId();

        $workspace = lawyerWorkspaceContext($pdo, (array)$abogadoUser);
        $sql = "INSERT INTO contactos_revelados
                (abogado_id, equipo_id, assigned_abogado_id, cliente_id, estado, medio_contacto, consulta, presupuesto, fecha_revelado, estado_updated_at)
                VALUES (?, ?, ?, ?, 'PENDIENTE', ?, ?, ?, NOW(), NOW())";
        $pdo->prepare($sql)->execute([
            $_SESSION['user_id'],
            !empty($workspace['team_id']) ? (int)$workspace['team_id'] : null,
            (int)$_SESSION['user_id'],
            $nuevoClienteId,
            trim((string)($data['medio_contacto'] ?? 'Ingreso Manual')) ?: 'Ingreso Manual',
            trim((string)($data['consulta'] ?? '')),
            $data['presupuesto'] ?? 0.00
        ]);

        $pdo->commit();

        trackEvent('crm_prospect_added', ['medio_contacto' => $data['medio_contacto'] ?? 'Ingreso Manual']);
        $_SESSION['mensaje'] = '✅ Prospecto agregado exitosamente a tu CRM.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        trackEvent('crm_prospect_failed', ['reason' => 'exception', 'message' => substr($e->getMessage(), 0, 120)]);
        $_SESSION['mensaje'] = '⚠️ No se pudo guardar el prospecto. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302);
});

// ============================================================================
// UPLOAD (UTILIDAD)
// ============================================================================


$app->get('/logout', function (Request $request, Response $response) {
    $_SESSION = [];
    $_SESSION['mensaje'] = '✅ Has cerrado sesión exitosamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    return $response->withHeader('Location', '/')->withStatus(302);
});

$app->get('/panel', function (Request $request, Response $response) use ($renderer) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/panel')->withStatus(302);
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: [];
    $email = strtolower(trim((string)($user['email'] ?? $_SESSION['email'] ?? '')));
    $isAdmin = isAdminSessionAuthenticated();
    if ($isAdmin) {
        return $response->withHeader('Location', '/admin')->withStatus(302);
    }
    $canAbogado = userCanUseLawyerMode($user);
    $requestedLawyer = userHasLawyerRequest($user);
    if ($requestedLawyer && !$canAbogado) {
        $pct = lawyerProfileCompletionPercent($user);
        $_SESSION['mensaje'] = '📝 Completa tu perfil profesional. Cuando llegues al 80%, un admin podrá aprobarlo y aparecerás en el listado (' . $pct . '% actual).';
        $_SESSION['tipo_mensaje'] = 'info';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    if ($canAbogado) {
        $pct = lawyerProfileCompletionPercent($user);
        if ($pct < 80) {
            $_SESSION['mensaje'] = '🛠️ Tu perfil profesional está activado pero oculto. Completa ' . (80 - $pct) . '% aprox. para aparecer en el listado.';
            $_SESSION['tipo_mensaje'] = 'info';
            return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
        }
    }
    $lawyerLeadCounts = [
        'interesados' => 0,
        'no_contactado' => 0,
        'contactado' => 0,
        'cerrados' => 0,
        'archivados' => 0,
    ];
    if ($canAbogado && !empty($user['id'])) {
        try {
            $panelWorkspace = lawyerWorkspaceContext($pdo, (array)$user);
            $leadCountScope = leadWorkspaceScope($panelWorkspace, (int)$user['id']);
            $stLeadCounts = $pdo->prepare("
                SELECT UPPER(COALESCE(estado,'PENDIENTE')) AS estado_key, COUNT(*) AS total
                FROM contactos_revelados
                WHERE " . $leadCountScope['sql'] . "
                GROUP BY UPPER(COALESCE(estado,'PENDIENTE'))
            ");
            $stLeadCounts->execute($leadCountScope['params']);
            $rowsLeadCounts = $stLeadCounts->fetchAll() ?: [];
            foreach ($rowsLeadCounts as $lc) {
                $estadoKey = strtoupper(trim((string)($lc['estado_key'] ?? 'PENDIENTE')));
                $cnt = (int)($lc['total'] ?? 0);
                $lawyerLeadCounts['interesados'] += $cnt;
                if ($estadoKey === 'PENDIENTE') $lawyerLeadCounts['no_contactado'] += $cnt;
                if ($estadoKey === 'CONTACTADO') $lawyerLeadCounts['contactado'] += $cnt;
                if ($estadoKey === 'GANADO') $lawyerLeadCounts['cerrados'] += $cnt;
                if (in_array($estadoKey, ['PERDIDO','CANCELADO'], true)) $lawyerLeadCounts['archivados'] += $cnt;
            }
        } catch (Throwable $e) {
            // fail-open: panel must still render
        }
    }
    return $renderer->render($response, 'panel.php', [
        'user' => $user,
        'can_cliente' => true,
        'can_abogado' => $canAbogado,
        'lawyer_status' => [
            'requested' => userHasLawyerRequest($user) || userCanUseLawyerMode($user),
            'verified' => !empty($user['abogado_verificado']),
        ],
        'lawyer_lead_counts' => $lawyerLeadCounts,
        'is_admin' => $isAdmin,
        'csrf_token' => ensureCsrfToken(),
    ]);
});

$app->get('/acceso-profesional', function (Request $request, Response $response) use ($renderer) {
    $user = null;
    $profilePct = 0;
    $profileChecklist = [];
    $canEditLawyerProfile = false;
    $hasLawyerRequest = false;
    $hasLawyerAccess = false;
    if (!empty($_SESSION['user_id'])) {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
        if ($user) {
            $canEditLawyerProfile = userCanEditLawyerProfile($user);
            $hasLawyerRequest = userHasLawyerRequest($user);
            $hasLawyerAccess = userCanUseLawyerMode($user);
            if ($canEditLawyerProfile) {
                $profilePct = lawyerProfileCompletionPercent($user);
                $profileChecklist = lawyerProfileCompletionChecklist($user);
            }
        }
    }
    $mensaje = $_SESSION['mensaje'] ?? null;
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    return $renderer->render($response, 'abogados.php', [
        'user' => $user,
        'mensaje' => $mensaje,
        'csrf_token' => ensureCsrfToken(),
        'perfil_completion_pct' => $profilePct,
        'perfil_completion_checklist' => $profileChecklist,
        'can_edit_lawyer_profile' => $canEditLawyerProfile,
        'has_lawyer_request' => $hasLawyerRequest,
        'has_lawyer_access' => $hasLawyerAccess,
    ]);
});

$app->get('/tarifario', function (Request $request, Response $response) use ($renderer) {
    $mensaje = $_SESSION['mensaje'] ?? null;
    $tipo = $_SESSION['tipo_mensaje'] ?? null;
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    return $renderer->render($response, 'tarifario.php', [
        'csrf_token' => ensureCsrfToken(),
        'is_admin' => isAdminSessionAuthenticated(),
        'mensaje' => $mensaje,
        'tipo_mensaje' => $tipo,
    ]);
});

$app->post('/tarifario/login', function (Request $request, Response $response) {
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Sesion expirada. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/tarifario')->withStatus(302);
    }
    $creds = adminCredentials();
    $u = trim((string)($data['username'] ?? ''));
    $p = (string)($data['password'] ?? '');
    if (!hash_equals((string)$creds['username'], $u) || !hash_equals((string)$creds['password'], $p)) {
        $_SESSION['mensaje'] = '⛔ Credenciales invalidas.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/tarifario')->withStatus(302);
    }
    session_regenerate_id(true);
    $_SESSION['admin_auth'] = true;
    $_SESSION['mensaje'] = '✅ Acceso habilitado para el cotizador.';
    $_SESSION['tipo_mensaje'] = 'success';
    return $response->withHeader('Location', '/tarifario')->withStatus(302);
});

$app->post('/tarifario/logout', function (Request $request, Response $response) {
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Sesion expirada. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/tarifario')->withStatus(302);
    }
    unset($_SESSION['admin_auth']);
    $_SESSION['mensaje'] = '✅ Sesion del cotizador cerrada.';
    $_SESSION['tipo_mensaje'] = 'success';
    return $response->withHeader('Location', '/tarifario')->withStatus(302);
});

$app->post('/solicitar-habilitacion-abogado', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/acceso-profesional')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/panel')->withStatus(302);
    }
    $pdo = getDB();
    try {
        $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: [];
        $pdo->prepare("UPDATE abogados SET solicito_habilitacion_abogado=1, fecha_solicitud_habilitacion_abogado=COALESCE(fecha_solicitud_habilitacion_abogado, NOW()) WHERE id=?")->execute([(int)$_SESSION['user_id']]);
        if (!empty($user)) {
            notifyProfessionalAccessRequest($user);
        }
    } catch (Throwable $e) {}
    $_SESSION['mensaje'] = '📝 Solicitud registrada. La activacion del perfil profesional es manual y se revisara por correo admin. Completa tu perfil al 80% para acelerar la aprobacion.';
    $_SESSION['tipo_mensaje'] = 'info';
    return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
});

$app->post('/aplicar-programa-profesional', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/acceso-profesional')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        $_SESSION['mensaje'] = '⚠️ Cuenta no encontrada.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $nombreLegal = trim((string)($data['nombre_legal_abogado'] ?? $data['nombre_abogado'] ?? ''));
    $whatsapp = validarWhatsApp($data['whatsapp'] ?? '');
    $rut = trim((string)($data['rut_abogado'] ?? ''));
    $sql = "UPDATE abogados SET nombre = COALESCE(NULLIF(?,''), nombre), whatsapp = COALESCE(?, whatsapp), rut_abogado = CASE WHEN ? <> '' THEN ? ELSE rut_abogado END, solicito_habilitacion_abogado = 1, fecha_solicitud_habilitacion_abogado = COALESCE(fecha_solicitud_habilitacion_abogado, NOW()) WHERE id = ?";
    $pdo->prepare($sql)->execute([$nombreLegal, $whatsapp ?: null, $rut, $rut, (int)$_SESSION['user_id']]);
    $user['nombre'] = $nombreLegal !== '' ? $nombreLegal : ($user['nombre'] ?? '');
    if ($whatsapp !== null) {
        $user['whatsapp'] = $whatsapp;
    }
    if ($rut !== '') {
        $user['rut_abogado'] = $rut;
    }
    try {
        notifyProfessionalAccessRequest($user, [
            'nombre' => $nombreLegal !== '' ? $nombreLegal : ($user['nombre'] ?? ''),
            'whatsapp' => $whatsapp ?: ($user['whatsapp'] ?? ''),
            'rut_abogado' => $rut !== '' ? $rut : ($user['rut_abogado'] ?? ''),
        ]);
    } catch (Throwable $e) {}
    $_SESSION['mensaje'] = '📩 Tu solicitud quedó registrada. La activacion del perfil profesional es manual y se notificó al correo admin para revision.';
    $_SESSION['tipo_mensaje'] = 'info';
    return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
});

$app->get('/dashboard', function (Request $request, Response $response) use ($renderer) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard/home')->withStatus(302);
    }
    $pdo = getDB();
    ensureLawyerSubscriptionColumns();
    ensureLawyerQuoteBrandingColumns();
    ensureLawyerServicesAndQuotesTables();
    ensureLawyerTeamTables();
    ensureLeadLifecycleColumns();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: [];
    if (!userCanAccessLawyerDashboard($user)) {
        $_SESSION['mensaje'] = '🔒 Tu cuenta no tiene acceso profesional activo.';
        $_SESSION['tipo_mensaje'] = 'info';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    syncLawyerTeamMembership($pdo, $user);
    $workspace = lawyerWorkspaceContext($pdo, $user);
    $mensaje = $_SESSION['mensaje'] ?? null;
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    if (!isset($request->getQueryParams()['tab'])) {
        return $response->withHeader('Location', '/dashboard/home')->withStatus(302);
    }
    $queryParams = (array)($request->getQueryParams() ?? []);
    $initialTab = (string)($queryParams['tab'] ?? 'home');
    $performanceRangeKey = strtolower(trim((string)($queryParams['range'] ?? 'month')));
    if (!in_array($performanceRangeKey, ['day', 'month', 'year'], true)) {
        $performanceRangeKey = 'month';
    }
    if ($performanceRangeKey === 'day') {
        $rangeStartTs = strtotime(date('Y-m-d 00:00:00'));
    } elseif ($performanceRangeKey === 'year') {
        $rangeStartTs = strtotime(date('Y-01-01 00:00:00'));
    } else {
        $rangeStartTs = strtotime(date('Y-m-01 00:00:00'));
    }
    $performanceRangeLabel = $performanceRangeKey === 'day'
        ? 'Hoy'
        : ($performanceRangeKey === 'year' ? 'Este año' : 'Este mes');
    if (!in_array($initialTab, ['home', 'inicio', 'marketplace', 'crm', 'perfil', 'performance', 'services', 'catalog', 'quote', 'builder', 'inbox', 'business', 'leads', 'quotes', 'branding', 'negocio', 'catalogo', 'marca', 'rendimiento', 'cotizaciones', 'cotizador', 'subscription', 'suscripcion', 'team', 'equipo', 'cuenta', 'cuenta-plan', 'cuenta-team', 'cuenta-perfil'], true)) $initialTab = 'home';
    if (in_array($initialTab, ['marketplace', 'crm', 'inbox', 'leads'], true)) {
        try {
            if (dbColumnExists('contactos_revelados', 'abogado_vio_at')) {
                $leadViewScope = leadWorkspaceScope($workspace, (int)$user['id']);
                $pdo->prepare("UPDATE contactos_revelados SET abogado_vio_at = COALESCE(abogado_vio_at, NOW()) WHERE " . $leadViewScope['sql'] . " AND UPPER(COALESCE(estado,'PENDIENTE')) = 'PENDIENTE'")
                    ->execute($leadViewScope['params']);
            }
        } catch (Throwable $e) {}
    }

    $misCasos = [];
    try {
        $leadScope = leadWorkspaceScope($workspace, (int)$user['id'], 'cr');
        $sqlCrm = "SELECT cr.*, c.nombre, c.whatsapp, c.email, c.especialidad, c.ciudad, c.created_at AS cliente_created_at,
                          owner.nombre AS lead_owner_name, owner.email AS lead_owner_email,
                          assignee.nombre AS assigned_abogado_nombre, assignee.email AS assigned_abogado_email
                   FROM contactos_revelados cr
                   LEFT JOIN abogados c ON c.id = cr.cliente_id
                   LEFT JOIN abogados owner ON owner.id = cr.abogado_id
                   LEFT JOIN abogados assignee ON assignee.id = COALESCE(cr.assigned_abogado_id, cr.abogado_id)
                   WHERE " . $leadScope['sql'] . "
                   ORDER BY COALESCE(cr.estado_updated_at, cr.fecha_revelado) DESC, cr.id DESC";
        $stCrm = $pdo->prepare($sqlCrm);
        $stCrm->execute($leadScope['params']);
        $misCasos = $stCrm->fetchAll() ?: [];
    } catch (Throwable $e) { $misCasos = []; }

    foreach ($misCasos as &$mc) {
        $estadoRaw = strtoupper(trim((string)($mc['estado'] ?? 'PENDIENTE')));
        $mc['estado'] = $estadoRaw !== '' ? $estadoRaw : 'PENDIENTE';
        switch ($mc['estado']) {
            case 'CONTACTADO':
                $mc['estado_ui'] = 'Contactado';
                $mc['pipeline_group'] = 'contactados';
                break;
            case 'GANADO':
                $mc['estado_ui'] = 'Cliente contratado';
                $mc['pipeline_group'] = 'cerrados';
                break;
            case 'PERDIDO':
                $mc['estado_ui'] = 'No cerró';
                $mc['pipeline_group'] = 'archivados';
                break;
            case 'CANCELADO':
                $mc['estado_ui'] = 'Archivado';
                $mc['pipeline_group'] = 'archivados';
                break;
            case 'PENDIENTE':
            default:
                $mc['estado_ui'] = 'No contactado';
                $mc['pipeline_group'] = 'no_contactados';
                break;
        }
    }
    unset($mc);

    $statsDash = [
        'total' => count($misCasos),
        'pendientes' => 0,
        'ganados' => 0,
        'presupuesto_total' => 0,
        'contactados' => 0,
        'archivados' => 0,
        'no_contactados' => 0,
    ];
    $performanceRangeStats = [
        'total' => 0,
        'pendientes' => 0,
        'ganados' => 0,
        'presupuesto_total' => 0,
        'contactados' => 0,
        'archivados' => 0,
        'no_contactados' => 0,
        'cold_leads' => 0,
        'followup_due' => 0,
        'response_samples' => [],
    ];
    foreach ($misCasos as $mc) {
        $st = strtoupper((string)($mc['estado'] ?? 'PENDIENTE'));
        if ($st === 'PENDIENTE') $statsDash['pendientes']++;
        if ($st === 'GANADO') $statsDash['ganados']++;
        if ($st === 'CONTACTADO') $statsDash['contactados']++;
        if ($st === 'PENDIENTE') $statsDash['no_contactados']++;
        if (in_array($st, ['PERDIDO','CANCELADO'], true)) $statsDash['archivados']++;
        $statsDash['presupuesto_total'] += (float)($mc['presupuesto'] ?? 0);
        $createdTs = strtotime((string)($mc['fecha_revelado'] ?? $mc['cliente_created_at'] ?? $mc['created_at'] ?? '')) ?: null;
        $updatedTs = strtotime((string)($mc['estado_updated_at'] ?? '')) ?: $createdTs;
        if ($createdTs && $createdTs >= $rangeStartTs) {
            $performanceRangeStats['total']++;
            if ($st === 'PENDIENTE') $performanceRangeStats['pendientes']++;
            if ($st === 'GANADO') $performanceRangeStats['ganados']++;
            if ($st === 'CONTACTADO') $performanceRangeStats['contactados']++;
            if ($st === 'PENDIENTE') $performanceRangeStats['no_contactados']++;
            if (in_array($st, ['PERDIDO','CANCELADO'], true)) $performanceRangeStats['archivados']++;
            $performanceRangeStats['presupuesto_total'] += (float)($mc['presupuesto'] ?? 0);
            if ($createdTs && $updatedTs && $updatedTs >= $createdTs && $st !== 'PENDIENTE') {
                $performanceRangeStats['response_samples'][] = max(0, ($updatedTs - $createdTs) / 60);
            }
        }
    }

    $casosPendientes = [];
    foreach ($misCasos as $rCasoTmp) {
        $stTmp = strtoupper(trim((string)($rCasoTmp['estado'] ?? 'PENDIENTE')));
        if (in_array($stTmp, ['PENDIENTE','CONTACTADO'], true)) { $casosPendientes[] = $rCasoTmp; }
    }

    $clientesParaCotizar = [];
    $clientesParaCotizarMap = [];
    foreach ($misCasos as $crmLead) {
        $clienteId = (int)($crmLead['cliente_id'] ?? 0);
        if ($clienteId <= 0 || isset($clientesParaCotizarMap[$clienteId])) {
            continue;
        }
        $clientesParaCotizarMap[$clienteId] = true;
        $clientesParaCotizar[] = [
            'id' => $clienteId,
            'nombre' => trim((string)($crmLead['nombre'] ?? 'Cliente')),
            'whatsapp' => trim((string)($crmLead['whatsapp'] ?? '')),
            'email' => trim((string)($crmLead['email'] ?? '')),
            'especialidad' => trim((string)($crmLead['especialidad'] ?? '')),
            'estado' => strtoupper(trim((string)($crmLead['estado'] ?? 'PENDIENTE'))),
        ];
    }

    $leadAssignees = [];
    if (!empty($workspace['team_state']['members']) && is_array($workspace['team_state']['members'])) {
        foreach ((array)$workspace['team_state']['members'] as $member) {
            $memberLawyerId = (int)($member['abogado_id'] ?? 0);
            if ($memberLawyerId <= 0) continue;
            $leadAssignees[] = [
                'id' => $memberLawyerId,
                'name' => trim((string)($member['abogado_nombre'] ?? $member['nombre_invitado'] ?? $member['email'] ?? 'Miembro')),
                'email' => trim((string)($member['abogado_email'] ?? $member['email'] ?? '')),
                'role' => trim((string)($member['rol'] ?? 'member')),
            ];
        }
    }

    $servicios = [];
    try {
        if (!empty($workspace['team_id'])) {
            $stServicios = $pdo->prepare("
                SELECT *
                FROM abogado_servicios
                WHERE equipo_id = ?
                ORDER BY activo DESC, updated_at DESC, id DESC
            ");
            $stServicios->execute([(int)$workspace['team_id']]);
        } else {
            $stServicios = $pdo->prepare("
                SELECT *
                FROM abogado_servicios
                WHERE abogado_id = ? AND equipo_id IS NULL
                ORDER BY activo DESC, updated_at DESC, id DESC
            ");
            $stServicios->execute([(int)$user['id']]);
        }
        $servicios = $stServicios->fetchAll() ?: [];
    } catch (Throwable $e) {
        $servicios = [];
    }

    $cotizaciones = [];
    try {
        if (!empty($workspace['team_id'])) {
            $stQuotes = $pdo->prepare("
                SELECT q.*, s.nombre AS servicio_nombre, s.materia AS servicio_materia
                FROM abogado_cotizaciones q
                LEFT JOIN abogado_servicios s ON s.id = q.servicio_id
                WHERE q.equipo_id = ?
                ORDER BY q.updated_at DESC, q.id DESC
                LIMIT 50
            ");
            $stQuotes->execute([(int)$workspace['team_id']]);
        } else {
            $stQuotes = $pdo->prepare("
                SELECT q.*, s.nombre AS servicio_nombre, s.materia AS servicio_materia
                FROM abogado_cotizaciones q
                LEFT JOIN abogado_servicios s ON s.id = q.servicio_id
                WHERE q.abogado_id = ? AND q.equipo_id IS NULL
                ORDER BY q.updated_at DESC, q.id DESC
                LIMIT 50
            ");
            $stQuotes->execute([(int)$user['id']]);
        }
        $cotizaciones = $stQuotes->fetchAll() ?: [];
    } catch (Throwable $e) {
        $cotizaciones = [];
    }

    foreach ($cotizaciones as &$quote) {
        $statusMeta = quoteStatusMeta((string)($quote['estado'] ?? 'BORRADOR'));
        $quote['estado_ui'] = $statusMeta['label'];
        $quote['estado_class'] = $statusMeta['class'];
        $quote['servicio_nombre_resuelto'] = trim((string)($quote['servicio_nombre'] ?? $quote['asunto'] ?? 'Cotizacion legal'));
        $quote['mensaje_texto'] = trim((string)($quote['mensaje_texto'] ?? ''));
        if ($quote['mensaje_texto'] === '') {
            $quote['mensaje_texto'] = buildLawyerQuoteMessage($user, $quote);
        }
        $quote['client_whatsapp_href'] = '';
        $quoteWhatsapp = normalizeOptionalWhatsapp($quote['client_whatsapp'] ?? null);
        if ($quoteWhatsapp) {
            $quote['client_whatsapp_href'] = 'https://wa.me/56' . rawurlencode($quoteWhatsapp) . '?text=' . rawurlencode((string)$quote['mensaje_texto']);
        }
        $quoteEmail = trim((string)($quote['client_email'] ?? ''));
        $quote['client_email_href'] = '';
        if ($quoteEmail !== '') {
            $quote['client_email_href'] = 'mailto:' . rawurlencode($quoteEmail)
                . '?subject=' . rawurlencode('Cotizacion legal - ' . $quote['servicio_nombre_resuelto'])
                . '&body=' . rawurlencode((string)$quote['mensaje_texto']);
        }
        $collectionStatus = strtoupper(trim((string)($quote['cobro_estado'] ?? 'SIN_GESTION')));
        if ($collectionStatus === '' || $collectionStatus === 'SIN_GESTION') {
            $collectionStatus = strtoupper(trim((string)($quote['estado'] ?? 'BORRADOR'))) === 'ACEPTADA' ? 'PENDIENTE' : 'SIN_GESTION';
        }
        $quote['cobro_estado_resuelto'] = $collectionStatus;
        $quote['cobrado_monto_resuelto'] = max(0, sanitizeMoneyAmount($quote['cobrado_monto'] ?? 0));
        $quote['por_cobrar_monto'] = max(0, round((float)($quote['total'] ?? 0) - (float)$quote['cobrado_monto_resuelto'], 2));
        $collectionMeta = quoteCollectionMeta($collectionStatus);
        $quote['cobro_estado_ui'] = $collectionMeta['label'];
        $quote['cobro_estado_class'] = $collectionMeta['class'];
        $quote['collection_workflow'] = quoteCollectionWorkflowSnapshot($quote, time());
        $quote['cobro_reminder_count_resuelto'] = max(0, (int)($quote['cobro_reminder_count'] ?? 0));
        $quote['cobro_reminder_sent_at_ts'] = strtotime((string)($quote['cobro_reminder_sent_at'] ?? '')) ?: null;
        $quote['collection_reminder_ready'] =
            !empty($quote['collection_workflow']['task_active'])
            && !empty($quote['collection_workflow']['due_ts'])
            && (int)($quote['collection_workflow']['due_ts'] ?? PHP_INT_MAX) <= time()
            && filter_var((string)($quote['client_email'] ?? ''), FILTER_VALIDATE_EMAIL)
            && (
                empty($quote['cobro_reminder_sent_at_ts'])
                || (int)$quote['cobro_reminder_sent_at_ts'] <= (time() - 86400)
            );
        $quote['collection_reminder_whatsapp_href'] = '';
        $quoteReminderWhatsapp = normalizeOptionalWhatsapp($quote['client_whatsapp'] ?? null);
        if ($quoteReminderWhatsapp) {
            $reminderContent = quoteCollectionReminderContent($user, $quote, 'manual');
            if (!empty($reminderContent['ok'])) {
                $quote['collection_reminder_whatsapp_href'] = 'https://wa.me/56' . rawurlencode($quoteReminderWhatsapp) . '?text=' . rawurlencode((string)($reminderContent['text'] ?? ''));
            }
        }
    }
    unset($quote);

    $dashboardMetrics = [
        'new_leads_today' => 0,
        'pending_leads' => (int)($statsDash['pendientes'] ?? 0),
        'quotes_pending' => 0,
        'quotes_sent' => 0,
        'quotes_accepted' => 0,
        'avg_response_minutes' => null,
        'monthly_revenue' => (float)($statsDash['presupuesto_total'] ?? 0),
        'pipeline_value' => 0.0,
        'stale_leads' => 0,
        'cold_leads' => 0,
        'followup_due' => 0,
        'lead_close_rate' => 0,
        'quote_acceptance_rate' => 0,
        'team_activity_today' => 0,
    ];
    $dashboardAlerts = [];
    $dashboardTasks = [];
    $dashboardActivity = [];
    $teamActivityFeed = [];
    $dashboardNotifications = [];
    $dashboardFinance = [
        'month_collected' => (float)($statsDash['presupuesto_total'] ?? 0),
        'pipeline_value' => 0.0,
        'accepted_quotes_total' => 0.0,
        'draft_quotes_total' => 0.0,
        'collections_pending_total' => 0.0,
        'retainer_pending_total' => 0.0,
    ];
    $dashboardPipeline = [
        'nuevos' => (int)($statsDash['pendientes'] ?? 0),
        'contactados' => (int)($statsDash['contactados'] ?? 0),
        'cotizaciones' => 0,
        'cerrados' => (int)($statsDash['ganados'] ?? 0),
        'archivados' => (int)($statsDash['archivados'] ?? 0),
    ];

    $nowTs = time();
    $todayStartTs = strtotime(date('Y-m-d 00:00:00'));
    $monthStartTs = strtotime(date('Y-m-01 00:00:00'));
    $responseSamples = [];
    $staleLeadCount = 0;
    $followupLeadCount = 0;
    $taskCandidates = [];

    foreach ($misCasos as &$lead) {
        $createdTs = strtotime((string)($lead['fecha_revelado'] ?? $lead['cliente_created_at'] ?? $lead['created_at'] ?? '')) ?: null;
        $updatedTs = strtotime((string)($lead['estado_updated_at'] ?? '')) ?: $createdTs;
        $state = strtoupper(trim((string)($lead['estado'] ?? 'PENDIENTE')));
        $lead['workflow'] = leadWorkflowSnapshot($lead, $nowTs);

        if ($createdTs && $createdTs >= $todayStartTs) {
            $dashboardMetrics['new_leads_today']++;
        }

        if ($createdTs && $updatedTs && $updatedTs >= $createdTs && $state !== 'PENDIENTE') {
            $responseSamples[] = max(0, ($updatedTs - $createdTs) / 60);
        }

        if ($state === 'PENDIENTE' && !empty($lead['workflow']['due_ts']) && (int)$lead['workflow']['due_ts'] <= $nowTs) {
            $staleLeadCount++;
            if ($createdTs && $createdTs >= $rangeStartTs) {
                $performanceRangeStats['cold_leads']++;
            }
        }

        if ($state === 'CONTACTADO' && !empty($lead['workflow']['due_ts']) && (int)$lead['workflow']['due_ts'] <= $nowTs) {
            $followupLeadCount++;
            if ($createdTs && $createdTs >= $rangeStartTs) {
                $performanceRangeStats['followup_due']++;
            }
        }

        if (!empty($lead['workflow']['task_active']) && !empty($lead['workflow']['due_ts'])) {
            $taskCandidates[] = [
                'type' => 'lead',
                'priority' => (string)$lead['workflow']['priority'],
                'priority_rank' => workflowPriorityRank((string)$lead['workflow']['priority']),
                'due_ts' => (int)$lead['workflow']['due_ts'],
                'title' => (string)$lead['workflow']['title'],
                'subtitle' => trim((string)($lead['nombre'] ?? 'Cliente')) . ' · ' . trim((string)($lead['especialidad'] ?? 'Sin materia')),
                'href' => '/dashboard/leads',
                'cta' => (string)($lead['workflow']['cta'] ?? 'Abrir'),
            ];
        }

        $dashboardActivity[] = [
            'ts' => $updatedTs ?: $createdTs ?: 0,
            'kind' => 'lead',
            'title' => 'Lead ' . strtolower((string)($lead['estado_ui'] ?? 'actualizado')),
            'meta' => trim((string)($lead['nombre'] ?? 'Cliente')) . ' · ' . trim((string)($lead['especialidad'] ?? 'Sin materia')),
            'href' => '/dashboard/leads',
        ];
    }
    unset($lead);

    $draftQuoteCount = 0;
    $sentQuoteFollowupCount = 0;
    $collectionReminderDueCount = 0;
    $performanceRangeQuotes = [
        'total' => 0,
        'sent' => 0,
        'accepted' => 0,
        'pipeline_value' => 0.0,
        'accepted_total' => 0.0,
        'collections_pending_total' => 0.0,
        'retainer_pending_total' => 0.0,
    ];
    foreach ($cotizaciones as &$quote) {
        $quoteCreatedTs = strtotime((string)($quote['created_at'] ?? '')) ?: null;
        $quoteUpdatedTs = strtotime((string)($quote['updated_at'] ?? '')) ?: $quoteCreatedTs;
        $quoteState = strtoupper(trim((string)($quote['estado'] ?? 'BORRADOR')));
        $quoteTotal = (float)($quote['total'] ?? 0);
        $quote['workflow'] = quoteWorkflowSnapshot($quote, $nowTs);

        if ($quoteState === 'BORRADOR') {
            $draftQuoteCount++;
            $dashboardFinance['draft_quotes_total'] += $quoteTotal;
        }
        if ($quoteState === 'ENVIADA') {
            $dashboardMetrics['quotes_sent']++;
            $dashboardPipeline['cotizaciones']++;
            $dashboardFinance['pipeline_value'] += $quoteTotal;
            if (!empty($quote['workflow']['due_ts']) && (int)$quote['workflow']['due_ts'] <= $nowTs) {
                $sentQuoteFollowupCount++;
            }
        }
        if ($quoteState === 'ACEPTADA') {
            $dashboardMetrics['quotes_accepted']++;
            $dashboardFinance['accepted_quotes_total'] += $quoteTotal;
            $dashboardFinance['collections_pending_total'] += max(0, (float)($quote['por_cobrar_monto'] ?? 0));
            if (strtoupper((string)($quote['cobro_estado_resuelto'] ?? 'PENDIENTE')) === 'PENDIENTE') {
                $dashboardFinance['retainer_pending_total'] += max(0, (float)($quote['anticipo'] ?? 0));
            }
            if ($quoteUpdatedTs && $quoteUpdatedTs >= $monthStartTs) {
                $dashboardMetrics['monthly_revenue'] += $quoteTotal;
            }
        }

        if ($quoteCreatedTs && $quoteCreatedTs >= $rangeStartTs) {
            $performanceRangeQuotes['total']++;
            if ($quoteState === 'ENVIADA') {
                $performanceRangeQuotes['sent']++;
                $performanceRangeQuotes['pipeline_value'] += $quoteTotal;
            }
            if ($quoteState === 'ACEPTADA') {
                $performanceRangeQuotes['accepted']++;
                $performanceRangeQuotes['accepted_total'] += $quoteTotal;
                $performanceRangeQuotes['collections_pending_total'] += max(0, (float)($quote['por_cobrar_monto'] ?? 0));
                if (strtoupper((string)($quote['cobro_estado_resuelto'] ?? 'PENDIENTE')) === 'PENDIENTE') {
                    $performanceRangeQuotes['retainer_pending_total'] += max(0, (float)($quote['anticipo'] ?? 0));
                }
            }
        }

        if (!empty($quote['workflow']['task_active']) && !empty($quote['workflow']['due_ts'])) {
            $taskCandidates[] = [
                'type' => 'quote',
                'priority' => (string)$quote['workflow']['priority'],
                'priority_rank' => workflowPriorityRank((string)$quote['workflow']['priority']),
                'due_ts' => (int)$quote['workflow']['due_ts'],
                'title' => (string)$quote['workflow']['title'],
                'subtitle' => trim((string)($quote['client_name'] ?? 'Cliente')) . ' · ' . trim((string)($quote['servicio_nombre_resuelto'] ?? 'Cotización')),
                'href' => '/dashboard/cotizaciones',
                'cta' => (string)($quote['workflow']['cta'] ?? 'Abrir'),
            ];
        }
        if (!empty($quote['collection_workflow']['task_active']) && !empty($quote['collection_workflow']['due_ts'])) {
            $collectionPriority = (string)($quote['collection_workflow']['priority'] ?? 'Media');
            $collectionPriorityRank = workflowPriorityRank($collectionPriority);
            if (strtoupper((string)($quote['cobro_estado_resuelto'] ?? 'SIN_GESTION')) === 'PENDIENTE') {
                $collectionPriorityRank = 0;
            }
            $taskCandidates[] = [
                'type' => 'collection',
                'priority' => $collectionPriority,
                'priority_rank' => $collectionPriorityRank,
                'due_ts' => (int)($quote['collection_workflow']['due_ts'] ?? PHP_INT_MAX),
                'title' => (string)($quote['collection_workflow']['title'] ?? 'Mover cobranza'),
                'subtitle' => trim((string)($quote['client_name'] ?? 'Cliente')) . ' · ' . trim((string)($quote['servicio_nombre_resuelto'] ?? 'Cotización')) . ' · ' . formatClpAmount($quote['por_cobrar_monto'] ?? 0),
                'href' => '/dashboard/cotizaciones',
                'cta' => (string)($quote['collection_workflow']['cta'] ?? 'Cobrar'),
            ];
        }
        if (!empty($quote['collection_reminder_ready'])) {
            $collectionReminderDueCount++;
        }

        $dashboardActivity[] = [
            'ts' => $quoteUpdatedTs ?: $quoteCreatedTs ?: 0,
            'kind' => 'quote',
            'title' => 'Cotización ' . strtolower((string)($quote['estado_ui'] ?? 'actualizada')),
            'meta' => trim((string)($quote['client_name'] ?? 'Cliente')) . ' · ' . trim((string)($quote['servicio_nombre_resuelto'] ?? 'Cotización')),
            'href' => '/dashboard/cotizaciones',
        ];
    }
    unset($quote);

    $dashboardMetrics['quotes_pending'] = $draftQuoteCount;
    $dashboardMetrics['pipeline_value'] = (float)$dashboardFinance['pipeline_value'];
    $dashboardMetrics['stale_leads'] = $staleLeadCount;
    $dashboardMetrics['followup_due'] = $sentQuoteFollowupCount;
    $dashboardMetrics['cold_leads'] = $staleLeadCount + $followupLeadCount;
    $dashboardMetrics['lead_close_rate'] = count($misCasos) > 0
        ? (int)round(((int)($statsDash['ganados'] ?? 0) / max(1, count($misCasos))) * 100)
        : 0;
    $dashboardMetrics['quote_acceptance_rate'] = count($cotizaciones) > 0
        ? (int)round(((int)$dashboardMetrics['quotes_accepted'] / max(1, count($cotizaciones))) * 100)
        : 0;
    if (!empty($responseSamples)) {
        $dashboardMetrics['avg_response_minutes'] = (int)round(array_sum($responseSamples) / count($responseSamples));
    }

    $performanceRangeMetrics = [
        'lead_total' => (int)($performanceRangeStats['total'] ?? 0),
        'lead_cold' => (int)($performanceRangeStats['cold_leads'] ?? 0),
        'followup_due' => (int)($performanceRangeStats['followup_due'] ?? 0),
        'lead_close_rate' => (int)round(
            ((int)($performanceRangeStats['ganados'] ?? 0) / max(1, (int)($performanceRangeStats['total'] ?? 0))) * 100
        ),
        'quote_acceptance_rate' => (int)round(
            ((int)($performanceRangeQuotes['accepted'] ?? 0) / max(1, (int)($performanceRangeQuotes['total'] ?? 0))) * 100
        ),
        'pipeline_value' => (float)($performanceRangeQuotes['pipeline_value'] ?? 0),
        'accepted_total' => (float)($performanceRangeQuotes['accepted_total'] ?? 0),
        'collections_pending_total' => (float)($performanceRangeQuotes['collections_pending_total'] ?? 0),
        'retainer_pending_total' => (float)($performanceRangeQuotes['retainer_pending_total'] ?? 0),
        'lead_no_contactado' => (int)($performanceRangeStats['pendientes'] ?? 0),
        'lead_contactado' => (int)($performanceRangeStats['contactados'] ?? 0),
        'lead_ganado' => (int)($performanceRangeStats['ganados'] ?? 0),
        'lead_perdido' => (int)($performanceRangeStats['archivados'] ?? 0),
        'lead_presupuesto_total' => (float)($performanceRangeStats['presupuesto_total'] ?? 0),
        'avg_response_minutes' => !empty($performanceRangeStats['response_samples'])
            ? (int)round(array_sum($performanceRangeStats['response_samples']) / count($performanceRangeStats['response_samples']))
            : null,
    ];

    if (!empty($workspace['team_id'])) {
        try {
            $stTeamActivity = $pdo->prepare("
                SELECT ta.*, actor.nombre AS actor_nombre
                FROM abogado_equipo_actividad ta
                LEFT JOIN abogados actor ON actor.id = ta.actor_abogado_id
                WHERE ta.equipo_id = ?
                ORDER BY ta.created_at DESC, ta.id DESC
                LIMIT 12
            ");
            $stTeamActivity->execute([(int)$workspace['team_id']]);
            $teamActivityFeed = $stTeamActivity->fetchAll() ?: [];
        } catch (Throwable $e) {
            $teamActivityFeed = [];
        }
        foreach ($teamActivityFeed as $activityRow) {
            $activityTs = strtotime((string)($activityRow['created_at'] ?? '')) ?: 0;
            if ($activityTs >= $todayStartTs) {
                $dashboardMetrics['team_activity_today']++;
            }
            $dashboardActivity[] = [
                'ts' => $activityTs,
                'kind' => 'team',
                'title' => trim((string)($activityRow['title'] ?? 'Actividad de team')),
                'meta' => trim((string)($activityRow['actor_nombre'] ?? 'Team')) . ' · ' . trim((string)($activityRow['meta'] ?? '')),
                'href' => in_array((string)($activityRow['target_type'] ?? ''), ['lead', 'quote'], true)
                    ? ((string)($activityRow['target_type'] ?? '') === 'lead' ? '/dashboard/leads' : '/dashboard/cotizaciones')
                    : '/dashboard/cuenta/team',
            ];
        }
    }

    usort($taskCandidates, static function (array $a, array $b): int {
        $priorityCompare = (int)($a['priority_rank'] ?? 5) <=> (int)($b['priority_rank'] ?? 5);
        if ($priorityCompare !== 0) {
            return $priorityCompare;
        }
        return (int)($a['due_ts'] ?? PHP_INT_MAX) <=> (int)($b['due_ts'] ?? PHP_INT_MAX);
    });
    foreach (array_slice($taskCandidates, 0, 6) as $taskCandidate) {
        $dashboardTasks[] = [
            'type' => (string)($taskCandidate['type'] ?? 'task'),
            'priority' => (string)($taskCandidate['priority'] ?? 'Baja'),
            'title' => (string)($taskCandidate['title'] ?? 'Tarea pendiente'),
            'subtitle' => (string)($taskCandidate['subtitle'] ?? ''),
            'href' => (string)($taskCandidate['href'] ?? '/dashboard'),
            'cta' => (string)($taskCandidate['cta'] ?? 'Abrir'),
        ];
    }

    if ($staleLeadCount > 0) {
        $dashboardAlerts[] = [
            'severity' => 'high',
            'title' => $staleLeadCount . ' lead' . ($staleLeadCount === 1 ? '' : 's') . ' sin respuesta hace más de 2 horas',
            'body' => 'Riesgo directo de pérdida por velocidad de respuesta.',
            'href' => '/dashboard/leads',
            'cta' => 'Ver leads',
        ];
    }
    if ($draftQuoteCount > 0) {
        $dashboardAlerts[] = [
            'severity' => 'medium',
            'title' => $draftQuoteCount . ' cotización' . ($draftQuoteCount === 1 ? '' : 'es') . ' en borrador',
            'body' => 'Tienes trabajo comercial listo para enviar y convertir.',
            'href' => '/dashboard/cotizaciones',
            'cta' => 'Abrir bandeja',
        ];
    }
    if ($sentQuoteFollowupCount > 0) {
        $dashboardAlerts[] = [
            'severity' => 'medium',
            'title' => $sentQuoteFollowupCount . ' cotización' . ($sentQuoteFollowupCount === 1 ? '' : 'es') . ' enviada' . ($sentQuoteFollowupCount === 1 ? '' : 's') . ' sin seguimiento',
            'body' => 'Seguimiento comercial pendiente hace más de 48 horas.',
            'href' => '/dashboard/cotizaciones',
            'cta' => 'Revisar',
        ];
    }
    if ($followupLeadCount > 0) {
        $dashboardAlerts[] = [
            'severity' => 'low',
            'title' => $followupLeadCount . ' lead' . ($followupLeadCount === 1 ? '' : 's') . ' contactado' . ($followupLeadCount === 1 ? '' : 's') . ' sin cierre',
            'body' => 'Conviene insistir o mover a cotización formal.',
            'href' => '/dashboard/leads',
            'cta' => 'Seguir',
        ];
    }
    if ((float)($dashboardFinance['retainer_pending_total'] ?? 0) > 0) {
        $dashboardAlerts[] = [
            'severity' => 'high',
            'title' => 'Anticipos pendientes por ' . formatClpAmount($dashboardFinance['retainer_pending_total'] ?? 0),
            'body' => 'Hay cotizaciones aceptadas que todavía no convierten en anticipo.',
            'href' => '/dashboard/cotizaciones',
            'cta' => 'Cobrar anticipo',
        ];
    }
    if ((float)($dashboardFinance['collections_pending_total'] ?? 0) > 0) {
        $dashboardAlerts[] = [
            'severity' => 'medium',
            'title' => 'Saldo por cobrar de ' . formatClpAmount($dashboardFinance['collections_pending_total'] ?? 0),
            'body' => 'Tienes negocio ganado que todavía no bajó a caja.',
            'href' => '/dashboard/cotizaciones',
            'cta' => 'Ver cobranza',
        ];
    }
    if ($collectionReminderDueCount > 0) {
        $dashboardAlerts[] = [
            'severity' => 'medium',
            'title' => $collectionReminderDueCount . ' cobro' . ($collectionReminderDueCount === 1 ? '' : 's') . ' listo' . ($collectionReminderDueCount === 1 ? '' : 's') . ' para recordatorio',
            'body' => 'Ya hay cotizaciones que piden insistencia de caja por email o WhatsApp.',
            'href' => '/dashboard/cotizaciones',
            'cta' => 'Recordar cobro',
        ];
    }
    if (!empty($workspace['team_id']) && $dashboardMetrics['team_activity_today'] > 0) {
        $dashboardAlerts[] = [
            'severity' => 'low',
            'title' => $dashboardMetrics['team_activity_today'] . ' movimiento' . ($dashboardMetrics['team_activity_today'] === 1 ? '' : 's') . ' del team hoy',
            'body' => 'Ya hay actividad distribuida en el workspace compartido.',
            'href' => '/dashboard/cuenta/team',
            'cta' => 'Ver team',
        ];
    }
    if (empty($dashboardAlerts)) {
        $dashboardAlerts[] = [
            'severity' => 'good',
            'title' => 'Sin alertas críticas por ahora',
            'body' => 'Tu bandeja está al día. Buen momento para avanzar cotizaciones o catálogo.',
            'href' => '/dashboard/cotizador',
            'cta' => 'Crear cotización',
        ];
    }

    usort($dashboardActivity, static function (array $a, array $b): int {
        return (int)($b['ts'] ?? 0) <=> (int)($a['ts'] ?? 0);
    });
    $dashboardActivity = array_slice($dashboardActivity, 0, 8);

    if (empty($dashboardTasks)) {
        $dashboardTasks[] = [
            'type' => 'focus',
            'priority' => 'Baja',
            'title' => 'Tu bandeja está limpia',
            'subtitle' => 'Aprovecha para actualizar servicios, marca o preparar nuevas cotizaciones.',
            'href' => '/dashboard/cotizador',
            'cta' => 'Ir al cotizador',
        ];
    }

    if ($dashboardMetrics['cold_leads'] > 0) {
        $dashboardNotifications[] = [
            'title' => 'Workflow comercial',
            'body' => $dashboardMetrics['cold_leads'] . ' lead' . ($dashboardMetrics['cold_leads'] === 1 ? '' : 's') . ' ya están fríos o piden seguimiento inmediato.',
            'status' => 'workflow',
        ];
    }
    if ((float)($dashboardFinance['collections_pending_total'] ?? 0) > 0) {
        $dashboardNotifications[] = [
            'title' => 'Caja en seguimiento',
            'body' => formatClpAmount($dashboardFinance['collections_pending_total'] ?? 0) . ' siguen pendientes de cobro y ' . formatClpAmount($dashboardFinance['retainer_pending_total'] ?? 0) . ' corresponden a anticipos.',
            'status' => 'collections',
        ];
    }
    if ($collectionReminderDueCount > 0) {
        $dashboardNotifications[] = [
            'title' => 'Recordatorios de cobro',
            'body' => $collectionReminderDueCount . ' cotización' . ($collectionReminderDueCount === 1 ? '' : 'es') . ' ya pide' . ($collectionReminderDueCount === 1 ? '' : 'n') . ' recordatorio de caja.',
            'status' => 'collections',
        ];
    }
    $dashboardNotifications[] = [
        'title' => 'Notificaciones procesales',
        'body' => 'Aún no hay integración procesal conectada. Esta zona quedará lista para hitos, audiencias y vencimientos.',
        'status' => 'coming_soon',
    ];

    return $renderer->render($response, 'dashboard_shell.php', [
        'mensaje' => $mensaje,
        'user' => $user,
        'stats' => $statsDash,
        'casos' => $casosPendientes,
        'mis_casos' => $misCasos,
        'clientes_para_cotizar' => $clientesParaCotizar,
        'lead_assignees' => $leadAssignees,
        'servicios' => $servicios,
        'cotizaciones' => $cotizaciones,
        'branding_settings' => lawyerQuoteBrandingSettings($user),
        'subscription_state' => lawyerSubscriptionState($user),
        'team_state' => lawyerTeamState($pdo, $user),
        'workspace_context' => $workspace,
        'materias_taxonomia' => lawyerMateriasTaxonomia(),
        'csrf_token' => ensureCsrfToken(),
        'initial_tab' => $initialTab,
        'dashboard_metrics' => $dashboardMetrics,
        'dashboard_alerts' => $dashboardAlerts,
        'dashboard_tasks' => $dashboardTasks,
        'dashboard_activity' => $dashboardActivity,
        'dashboard_pipeline' => $dashboardPipeline,
        'dashboard_finance' => $dashboardFinance,
        'dashboard_notifications' => $dashboardNotifications,
        'team_activity_feed' => $teamActivityFeed,
        'performance_range' => [
            'key' => $performanceRangeKey,
            'label' => $performanceRangeLabel,
            'start_ts' => $rangeStartTs,
            'metrics' => $performanceRangeMetrics,
        ],
    ]);
});
$app->get('/dashboard/home', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=home')->withStatus(302); });
$app->get('/dashboard/leads', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=leads')->withStatus(302); });
$app->get('/dashboard/crm', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard/leads')->withStatus(302); });
$app->get('/dashboard/cotizaciones', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302); });
$app->get('/dashboard/negocio', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard/catalogo')->withStatus(302); });
$app->get('/dashboard/servicios', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard/catalogo')->withStatus(302); });
$app->get('/dashboard/catalogo', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=catalog')->withStatus(302); });
$app->get('/dashboard/marca', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=branding')->withStatus(302); });
$app->get('/dashboard/rendimiento', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=performance')->withStatus(302); });
$app->get('/dashboard/cotizador', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=builder')->withStatus(302); });
$app->get('/dashboard/cuenta', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=cuenta')->withStatus(302); });
$app->get('/dashboard/cuenta/perfil', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=cuenta')->withStatus(302); });
$app->get('/dashboard/cuenta/plan', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=cuenta-plan')->withStatus(302); });
$app->get('/dashboard/cuenta/team', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard?tab=cuenta-team')->withStatus(302); });
$app->get('/dashboard/perfil', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard/cuenta')->withStatus(302); });
$app->get('/dashboard/suscripcion', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard/cuenta/plan')->withStatus(302); });
$app->get('/dashboard/team', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302); });
$app->get('/dashboard/equipo', function (Request $request, Response $response) { return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302); });

$app->post('/dashboard/team/create', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard/cuenta/team')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ No se pudo validar la sesión del formulario.';
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }
    $pdo = getDB();
    ensureLawyerTeamTables();
    ensureLeadLifecycleColumns();
    $st = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $st->execute([(int)$_SESSION['user_id']]);
    $user = $st->fetch() ?: [];
    if (!$user || !userCanAccessLawyerDashboard((array)$user)) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    syncLawyerTeamMembership($pdo, $user);
    $teamState = lawyerTeamState($pdo, $user);
    if (!empty($teamState['team'])) {
        $_SESSION['mensaje'] = 'ℹ️ Ya perteneces a un team jurídico.';
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }

    $teamName = trim((string)($data['team_name'] ?? ''));
    if ($teamName === '') {
        $_SESSION['mensaje'] = '⚠️ Debes indicar un nombre para el equipo.';
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }

    try {
        $pdo->beginTransaction();
        $slug = ensureUniqueTeamSlug($pdo, teamSlugify($teamName));
        $pdo->prepare("
            INSERT INTO abogado_equipos (owner_abogado_id, nombre, slug, activo, created_at, updated_at)
            VALUES (?, ?, ?, 1, NOW(), NOW())
        ")->execute([(int)$user['id'], $teamName, $slug]);
        $teamId = (int)$pdo->lastInsertId();
        $pdo->prepare("
            INSERT INTO abogado_equipo_miembros (equipo_id, abogado_id, email, nombre_invitado, rol, estado, invited_by_abogado_id, joined_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'owner', 'active', ?, NOW(), NOW(), NOW())
        ")->execute([
            $teamId,
            (int)$user['id'],
            strtolower(trim((string)($user['email'] ?? ''))),
            trim((string)($user['nombre'] ?? $user['email'] ?? 'Owner')),
            (int)$user['id'],
        ]);
        $pdo->prepare("UPDATE abogado_servicios SET equipo_id = ? WHERE abogado_id = ? AND equipo_id IS NULL")
            ->execute([$teamId, (int)$user['id']]);
        $pdo->prepare("UPDATE abogado_cotizaciones SET equipo_id = ? WHERE abogado_id = ? AND equipo_id IS NULL")
            ->execute([$teamId, (int)$user['id']]);
        $pdo->prepare("UPDATE contactos_revelados SET equipo_id = ?, assigned_abogado_id = COALESCE(assigned_abogado_id, abogado_id) WHERE abogado_id = ? AND equipo_id IS NULL")
            ->execute([$teamId, (int)$user['id']]);
        recordLawyerTeamActivity($pdo, $teamId, (int)$user['id'], 'team_created', 'team', $teamId, 'Team jurídico creado', $teamName);
        $pdo->commit();
        $_SESSION['mensaje'] = '✅ Team jurídico creado. Ya puedes invitar colaboradores.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['mensaje'] = '⚠️ No se pudo crear el team jurídico.';
    }
    return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
});

$app->post('/dashboard/team/invite', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard/cuenta/team')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ No se pudo validar la sesión del formulario.';
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }
    $pdo = getDB();
    ensureLawyerTeamTables();
    $st = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $st->execute([(int)$_SESSION['user_id']]);
    $user = $st->fetch() ?: [];
    if (!$user || !userCanAccessLawyerDashboard((array)$user)) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    syncLawyerTeamMembership($pdo, $user);
    $teamState = lawyerTeamState($pdo, $user);
    if (empty($teamState['team']) || empty($teamState['can_manage'])) {
        $_SESSION['mensaje'] = '🔒 No tienes permisos para invitar a este team.';
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }
    $inviteEmail = strtolower(trim((string)($data['invite_email'] ?? '')));
    $inviteName = trim((string)($data['invite_name'] ?? ''));
    $inviteRole = strtolower(trim((string)($data['invite_role'] ?? 'member')));
    if (!filter_var($inviteEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje'] = '⚠️ Debes ingresar un email válido para la invitación.';
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }
    if (!in_array($inviteRole, ['admin', 'member'], true)) {
        $inviteRole = 'member';
    }
    try {
        $matchedLawyerId = 0;
        $matchedLawyer = null;
        $stMatch = $pdo->prepare("SELECT * FROM abogados WHERE LOWER(email) = ? LIMIT 1");
        $stMatch->execute([$inviteEmail]);
        $matchedLawyer = $stMatch->fetch() ?: null;
        if ($matchedLawyer && userCanAccessLawyerDashboard((array)$matchedLawyer)) {
            $matchedLawyerId = (int)($matchedLawyer['id'] ?? 0);
        }

        $stExisting = $pdo->prepare("SELECT id FROM abogado_equipo_miembros WHERE equipo_id = ? AND LOWER(email) = ? LIMIT 1");
        $stExisting->execute([(int)$teamState['team']['id'], $inviteEmail]);
        $existingId = (int)($stExisting->fetchColumn() ?: 0);
        if ($existingId > 0) {
            $pdo->prepare("
                UPDATE abogado_equipo_miembros
                SET nombre_invitado = ?, rol = ?, abogado_id = NULLIF(?, 0),
                    estado = ?, joined_at = CASE WHEN ? = 'active' THEN COALESCE(joined_at, NOW()) ELSE joined_at END,
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([
                $inviteName !== '' ? $inviteName : null,
                $inviteRole,
                $matchedLawyerId,
                $matchedLawyerId > 0 ? 'active' : 'pending',
                $matchedLawyerId > 0 ? 'active' : 'pending',
                $existingId,
            ]);
        } else {
            $pdo->prepare("
                INSERT INTO abogado_equipo_miembros (equipo_id, abogado_id, email, nombre_invitado, rol, estado, invited_by_abogado_id, joined_at, created_at, updated_at)
                VALUES (?, NULLIF(?,0), ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ")->execute([
                (int)$teamState['team']['id'],
                $matchedLawyerId,
                $inviteEmail,
                $inviteName !== '' ? $inviteName : null,
                $inviteRole,
                $matchedLawyerId > 0 ? 'active' : 'pending',
                (int)$user['id'],
                $matchedLawyerId > 0 ? date('Y-m-d H:i:s') : null,
            ]);
        }
        recordLawyerTeamActivity(
            $pdo,
            (int)$teamState['team']['id'],
            (int)$user['id'],
            $matchedLawyerId > 0 ? 'team_member_added' : 'team_member_invited',
            'team_member',
            $existingId > 0 ? $existingId : (int)$pdo->lastInsertId(),
            $matchedLawyerId > 0 ? 'Miembro agregado al team' : 'Invitación guardada',
            ($inviteName !== '' ? $inviteName : $inviteEmail) . ' · ' . strtoupper($inviteRole)
        );
        $_SESSION['mensaje'] = $matchedLawyerId > 0
            ? '✅ Miembro agregado al team jurídico.'
            : '✅ Invitación guardada. Quedará activa cuando ese correo entre al panel profesional.';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo guardar la invitación.';
    }
    return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
});

$app->post('/dashboard/team/member/remove', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard/cuenta/team')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ No se pudo validar la sesión del formulario.';
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }
    $pdo = getDB();
    ensureLawyerTeamTables();
    $st = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $st->execute([(int)$_SESSION['user_id']]);
    $user = $st->fetch() ?: [];
    if (!$user || !userCanAccessLawyerDashboard((array)$user)) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    syncLawyerTeamMembership($pdo, $user);
    $teamState = lawyerTeamState($pdo, $user);
    if (empty($teamState['team']) || empty($teamState['can_manage'])) {
        $_SESSION['mensaje'] = '🔒 No tienes permisos para gestionar este team.';
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }
    $data = (array)$request->getParsedBody();
    $memberId = (int)($data['member_id'] ?? 0);
    if ($memberId <= 0) {
        return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
    }
    try {
        $stMember = $pdo->prepare("SELECT * FROM abogado_equipo_miembros WHERE id = ? AND equipo_id = ? LIMIT 1");
        $stMember->execute([$memberId, (int)$teamState['team']['id']]);
        $member = $stMember->fetch() ?: null;
        if ($member && strtolower((string)($member['rol'] ?? 'member')) !== 'owner') {
            $pdo->prepare("DELETE FROM abogado_equipo_miembros WHERE id = ? LIMIT 1")->execute([$memberId]);
            recordLawyerTeamActivity(
                $pdo,
                (int)$teamState['team']['id'],
                (int)$user['id'],
                'team_member_removed',
                'team_member',
                $memberId,
                'Miembro o invitación eliminada',
                trim((string)($member['nombre_invitado'] ?? $member['email'] ?? 'Miembro'))
            );
            $_SESSION['mensaje'] = '✅ Miembro o invitación eliminada del team.';
        }
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo actualizar el team.';
    }
    return $response->withHeader('Location', '/dashboard/cuenta/team')->withStatus(302);
});

$app->post('/dashboard/servicios/guardar', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=catalog')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=catalog')->withStatus(302);
    }

    ensureLawyerServicesAndQuotesTables();
    $nombre = normalizarTexto($data['nombre_servicio'] ?? $data['nombre'] ?? '');
    if ($nombre === '') {
        $_SESSION['mensaje'] = '⚠️ Debes ingresar un nombre de servicio.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=catalog')->withStatus(302);
    }

    $serviceId = (int)($data['service_id'] ?? 0);
    $materia = trim((string)($data['materia'] ?? ''));
    $materia = $materia !== '' ? normalizeLawyerMateria($materia) : null;
    $detalle = trim((string)($data['detalle'] ?? ''));
    $plazo = trim((string)($data['plazo_estimado'] ?? ''));
    $precioBase = sanitizeMoneyAmount($data['precio_base'] ?? 0);
    $gastosBase = sanitizeMoneyAmount($data['gastos_base'] ?? 0);
    $activo = !empty($data['activo']) ? 1 : 0;

    try {
        $pdo = getDB();
        $workspace = lawyerWorkspaceContext($pdo, $lawyerUser);
        if ($serviceId > 0) {
            if (!empty($workspace['team_id'])) {
                $stmt = $pdo->prepare("
                    UPDATE abogado_servicios
                    SET nombre = ?, materia = ?, detalle = ?, plazo_estimado = ?, precio_base = ?, gastos_base = ?, activo = ?, abogado_id = ?
                    WHERE id = ? AND equipo_id = ?
                ");
                $stmt->execute([$nombre, $materia ?: null, $detalle ?: null, $plazo ?: null, $precioBase, $gastosBase, $activo, (int)$lawyerUser['id'], $serviceId, (int)$workspace['team_id']]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE abogado_servicios
                    SET nombre = ?, materia = ?, detalle = ?, plazo_estimado = ?, precio_base = ?, gastos_base = ?, activo = ?
                    WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL
                ");
                $stmt->execute([$nombre, $materia ?: null, $detalle ?: null, $plazo ?: null, $precioBase, $gastosBase, $activo, $serviceId, (int)$lawyerUser['id']]);
            }
            $_SESSION['mensaje'] = '✅ Servicio actualizado.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO abogado_servicios
                    (abogado_id, equipo_id, nombre, materia, detalle, plazo_estimado, precio_base, gastos_base, activo, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([(int)$lawyerUser['id'], !empty($workspace['team_id']) ? (int)$workspace['team_id'] : null, $nombre, $materia ?: null, $detalle ?: null, $plazo ?: null, $precioBase, $gastosBase, $activo]);
            $_SESSION['mensaje'] = '✅ Servicio agregado al catalogo.';
        }
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo guardar el servicio.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=catalog')->withStatus(302);
});

$app->post('/dashboard/servicios/eliminar', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=catalog')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=catalog')->withStatus(302);
    }

    try {
        $pdo = getDB();
        $workspace = lawyerWorkspaceContext($pdo, $lawyerUser);
        if (!empty($workspace['team_id'])) {
            $pdo->prepare("DELETE FROM abogado_servicios WHERE id = ? AND equipo_id = ?")->execute([(int)($data['service_id'] ?? 0), (int)$workspace['team_id']]);
        } else {
            $pdo->prepare("DELETE FROM abogado_servicios WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL")->execute([(int)($data['service_id'] ?? 0), (int)$lawyerUser['id']]);
        }
        $_SESSION['mensaje'] = '✅ Servicio eliminado.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo eliminar el servicio.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=catalog')->withStatus(302);
});

$app->post('/dashboard/cotizaciones/marca', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=branding')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=branding')->withStatus(302);
    }

    ensureLawyerQuoteBrandingColumns();
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("
            UPDATE abogados
            SET quote_branding_enabled = ?,
                quote_brand_name = ?,
                quote_brand_legal_name = ?,
                quote_brand_rut = ?,
                quote_brand_phone = ?,
                quote_brand_email = ?,
                quote_brand_address = ?,
                quote_brand_legal_notice = ?
            WHERE id = ?
        ");
        $stmt->execute([
            !empty($data['quote_branding_enabled']) ? 1 : 0,
            trim((string)($data['quote_brand_name'] ?? '')) ?: null,
            trim((string)($data['quote_brand_legal_name'] ?? '')) ?: null,
            trim((string)($data['quote_brand_rut'] ?? '')) ?: null,
            trim((string)($data['quote_brand_phone'] ?? '')) ?: null,
            trim((string)($data['quote_brand_email'] ?? '')) ?: null,
            trim((string)($data['quote_brand_address'] ?? '')) ?: null,
            trim((string)($data['quote_brand_legal_notice'] ?? '')) ?: null,
            (int)$lawyerUser['id'],
        ]);
        $_SESSION['mensaje'] = '✅ Firma del cotizador actualizada.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo guardar la firma del cotizador.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=branding')->withStatus(302);
});

$app->post('/dashboard/cotizaciones/guardar', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=quotes')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    ensureLawyerServicesAndQuotesTables();

    $pdo = getDB();
    $workspace = lawyerWorkspaceContext($pdo, $lawyerUser);
    $serviceId = (int)($data['service_id'] ?? 0);
    $quoteId = (int)($data['quote_id'] ?? 0);
    $clientId = (int)($data['cliente_id'] ?? 0);
    $service = null;
    $client = null;

    if ($serviceId > 0) {
        if (!empty($workspace['team_id'])) {
            $st = $pdo->prepare("SELECT * FROM abogado_servicios WHERE id = ? AND equipo_id = ? LIMIT 1");
            $st->execute([$serviceId, (int)$workspace['team_id']]);
        } else {
            $st = $pdo->prepare("SELECT * FROM abogado_servicios WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL LIMIT 1");
            $st->execute([$serviceId, (int)$lawyerUser['id']]);
        }
        $service = $st->fetch() ?: null;
        if (!$service) {
            $_SESSION['mensaje'] = '⚠️ El servicio seleccionado ya no existe.';
            $_SESSION['tipo_mensaje'] = 'error';
            return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
        }
    }

    if ($clientId > 0) {
        $st = $pdo->prepare("SELECT id, nombre, whatsapp, email, especialidad FROM abogados WHERE id = ? LIMIT 1");
        $st->execute([$clientId]);
        $client = $st->fetch() ?: null;
    }

    $clientName = normalizarTexto($data['client_name'] ?? ($client['nombre'] ?? ''));
    if ($clientName === '') {
        $_SESSION['mensaje'] = '⚠️ Debes indicar el nombre del cliente para generar la cotización.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    $clientWhatsapp = normalizeOptionalWhatsapp($data['client_whatsapp'] ?? ($client['whatsapp'] ?? null));
    $clientEmail = trim((string)($data['client_email'] ?? ($client['email'] ?? '')));
    $asunto = normalizarTexto($data['asunto'] ?? ($service['nombre'] ?? 'Cotizacion legal'));
    if ($asunto === '') {
        $asunto = 'Cotizacion legal';
    }
    $materia = trim((string)($data['materia'] ?? ($service['materia'] ?? ($client['especialidad'] ?? ''))));
    $materia = $materia !== '' ? normalizeLawyerMateria($materia) : null;
    $detalle = trim((string)($data['detalle'] ?? ($service['detalle'] ?? '')));
    $noIncluye = trim((string)($data['no_incluye'] ?? ''));
    $plazo = trim((string)($data['plazo_estimado'] ?? ($service['plazo_estimado'] ?? '')));
    $vigencia = trim((string)($data['vigencia'] ?? ''));
    $honorarios = sanitizeMoneyAmount($data['honorarios'] ?? ($service['precio_base'] ?? 0));
    $gastos = sanitizeMoneyAmount($data['gastos'] ?? ($service['gastos_base'] ?? 0));
    $descuento = sanitizeMoneyAmount($data['descuento'] ?? 0);
    $total = max(0, round($honorarios + $gastos - $descuento, 2));
    $anticipo = min($total, sanitizeMoneyAmount($data['anticipo'] ?? 0));
    $saldo = max(0, round($total - $anticipo, 2));
    $condicionesPago = trim((string)($data['condiciones_pago'] ?? ''));
    $paymentLink = trim((string)($data['payment_link'] ?? ''));
    $notas = trim((string)($data['notas'] ?? ''));
    $estado = strtoupper(trim((string)($data['estado'] ?? 'BORRADOR')));
    if (!in_array($estado, ['BORRADOR', 'ENVIADA', 'ACEPTADA', 'RECHAZADA', 'ANULADA'], true)) {
        $estado = 'BORRADOR';
    }

    $quoteItemsJson = null;
    $quoteItems = [];
    $rawItems = trim((string)($data['quote_items_json'] ?? ''));
    if ($rawItems !== '') {
        $decodedItems = json_decode($rawItems, true);
        if (is_array($decodedItems)) {
            $quoteItems = $decodedItems;
        }
    }
    if (!empty($quoteItems)) {
        $normalizedItems = [];
        $sumHonorarios = 0.0;
        $sumGastos = 0.0;
        foreach ($quoteItems as $item) {
            $serviceIdItem = (int)($item['id'] ?? 0);
            $qty = max(1, (int)($item['qty'] ?? 1));
            if ($serviceIdItem <= 0) continue;
            if (!empty($workspace['team_id'])) {
                $st = $pdo->prepare("SELECT * FROM abogado_servicios WHERE id = ? AND equipo_id = ? LIMIT 1");
                $st->execute([$serviceIdItem, (int)$workspace['team_id']]);
            } else {
                $st = $pdo->prepare("SELECT * FROM abogado_servicios WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL LIMIT 1");
                $st->execute([$serviceIdItem, (int)$lawyerUser['id']]);
            }
            $srv = $st->fetch() ?: null;
            if (!$srv) continue;
            $precio = (float)($srv['precio_base'] ?? 0);
            $gasto = (float)($srv['gastos_base'] ?? 0);
            $sumHonorarios += $precio * $qty;
            $sumGastos += $gasto * $qty;
            $normalizedItems[] = [
                'id' => (int)$srv['id'],
                'nombre' => (string)($srv['nombre'] ?? 'Servicio'),
                'materia' => (string)($srv['materia'] ?? ''),
                'precio_base' => $precio,
                'gastos_base' => $gasto,
                'detalle' => (string)($srv['detalle'] ?? ''),
                'plazo_estimado' => (string)($srv['plazo_estimado'] ?? ''),
                'qty' => $qty,
            ];
        }
        if (!empty($normalizedItems)) {
            $quoteItemsJson = json_encode($normalizedItems, JSON_UNESCAPED_UNICODE);
            $honorarios = round($sumHonorarios, 2);
            $gastos = round($sumGastos, 2);
            if ($asunto === '' || stripos($asunto, 'Cotizacion') === 0) {
                $asunto = count($normalizedItems) === 1
                    ? (string)($normalizedItems[0]['nombre'] ?? $asunto)
                    : 'Cotización por ' . count($normalizedItems) . ' servicios';
            }
            if ($detalle === '') {
                $detalleLines = ["Servicios base:"];
                foreach ($normalizedItems as $ni) {
                    $detalleLines[] = '- ' . trim((string)$ni['nombre']) . ' x' . (int)$ni['qty'];
                }
                $detalle = implode("\n", $detalleLines);
            }
        }
    }

    $quotePayload = [
        'id' => $quoteId,
        'client_name' => $clientName,
        'asunto' => $asunto,
        'materia' => $materia,
        'detalle' => $detalle,
        'no_incluye' => $noIncluye,
        'plazo_estimado' => $plazo,
        'vigencia' => $vigencia,
        'honorarios' => $honorarios,
        'gastos' => $gastos,
        'descuento' => $descuento,
        'total' => $total,
        'anticipo' => $anticipo,
        'saldo' => $saldo,
        'condiciones_pago' => $condicionesPago,
        'payment_link' => $paymentLink,
        'notas' => $notas,
        'client_email' => $clientEmail,
        'client_whatsapp' => $clientWhatsapp,
        'quote_items_json' => $quoteItemsJson,
    ];
    $mensajeTexto = buildLawyerQuoteMessage($lawyerUser, $quotePayload);
    $savedQuoteId = $quoteId;
    $currentCollectionState = null;
    $currentCollectedAmount = 0.0;
    if ($quoteId > 0) {
        try {
            if (!empty($workspace['team_id'])) {
                $stExistingCollection = $pdo->prepare("SELECT cobro_estado, cobrado_monto FROM abogado_cotizaciones WHERE id = ? AND equipo_id = ? LIMIT 1");
                $stExistingCollection->execute([$quoteId, (int)$workspace['team_id']]);
            } else {
                $stExistingCollection = $pdo->prepare("SELECT cobro_estado, cobrado_monto FROM abogado_cotizaciones WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL LIMIT 1");
                $stExistingCollection->execute([$quoteId, (int)$lawyerUser['id']]);
            }
            $existingCollection = $stExistingCollection->fetch() ?: null;
            if ($existingCollection) {
                $currentCollectionState = strtoupper(trim((string)($existingCollection['cobro_estado'] ?? '')));
                $currentCollectedAmount = max(0, sanitizeMoneyAmount($existingCollection['cobrado_monto'] ?? 0));
            }
        } catch (Throwable $e) {
            $currentCollectionState = null;
            $currentCollectedAmount = 0.0;
        }
    }
    if ($currentCollectionState === null || $currentCollectionState === '') {
        $currentCollectionState = $estado === 'ACEPTADA' ? 'PENDIENTE' : 'SIN_GESTION';
    }
    if ($estado !== 'ACEPTADA') {
        $currentCollectionState = 'SIN_GESTION';
        $currentCollectedAmount = 0.0;
    }

    try {
        if ($quoteId > 0) {
            if (!empty($workspace['team_id'])) {
                $stmt = $pdo->prepare("
                    UPDATE abogado_cotizaciones
                    SET abogado_id = ?, equipo_id = ?, servicio_id = ?, cliente_id = ?, client_name = ?, client_whatsapp = ?, client_email = ?,
                        asunto = ?, materia = ?, detalle = ?, no_incluye = ?, plazo_estimado = ?, vigencia = ?,
                        honorarios = ?, gastos = ?, descuento = ?, total = ?, anticipo = ?, saldo = ?,
                        condiciones_pago = ?, payment_link = ?, notas = ?, quote_items_json = ?, mensaje_texto = ?, estado = ?, cobro_estado = ?, cobrado_monto = ?
                    WHERE id = ? AND equipo_id = ?
                ");
                $stmt->execute([
                    (int)$lawyerUser['id'],
                    (int)$workspace['team_id'],
                    $serviceId > 0 ? $serviceId : null,
                    $clientId > 0 ? $clientId : null,
                    $clientName,
                    $clientWhatsapp,
                    $clientEmail !== '' ? $clientEmail : null,
                    $asunto,
                    $materia,
                    $detalle !== '' ? $detalle : null,
                    $noIncluye !== '' ? $noIncluye : null,
                    $plazo !== '' ? $plazo : null,
                    $vigencia !== '' ? $vigencia : null,
                    $honorarios,
                    $gastos,
                    $descuento,
                    $total,
                    $anticipo,
                    $saldo,
                    $condicionesPago !== '' ? $condicionesPago : null,
                    $paymentLink !== '' ? $paymentLink : null,
                    $notas !== '' ? $notas : null,
                    $quoteItemsJson !== null ? $quoteItemsJson : null,
                    $mensajeTexto,
                    $estado,
                    $currentCollectionState,
                    min($total, $currentCollectedAmount),
                    $quoteId,
                    (int)$workspace['team_id'],
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE abogado_cotizaciones
                    SET servicio_id = ?, cliente_id = ?, client_name = ?, client_whatsapp = ?, client_email = ?,
                        asunto = ?, materia = ?, detalle = ?, no_incluye = ?, plazo_estimado = ?, vigencia = ?,
                        honorarios = ?, gastos = ?, descuento = ?, total = ?, anticipo = ?, saldo = ?,
                        condiciones_pago = ?, payment_link = ?, notas = ?, quote_items_json = ?, mensaje_texto = ?, estado = ?, cobro_estado = ?, cobrado_monto = ?
                    WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL
                ");
                $stmt->execute([
                    $serviceId > 0 ? $serviceId : null,
                    $clientId > 0 ? $clientId : null,
                    $clientName,
                    $clientWhatsapp,
                    $clientEmail !== '' ? $clientEmail : null,
                    $asunto,
                    $materia,
                    $detalle !== '' ? $detalle : null,
                    $noIncluye !== '' ? $noIncluye : null,
                    $plazo !== '' ? $plazo : null,
                    $vigencia !== '' ? $vigencia : null,
                    $honorarios,
                    $gastos,
                    $descuento,
                    $total,
                    $anticipo,
                    $saldo,
                    $condicionesPago !== '' ? $condicionesPago : null,
                    $paymentLink !== '' ? $paymentLink : null,
                    $notas !== '' ? $notas : null,
                    $quoteItemsJson !== null ? $quoteItemsJson : null,
                    $mensajeTexto,
                    $estado,
                    $currentCollectionState,
                    min($total, $currentCollectedAmount),
                    $quoteId,
                    (int)$lawyerUser['id'],
                ]);
            }
            $_SESSION['mensaje'] = '✅ Cotización actualizada.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO abogado_cotizaciones
                    (abogado_id, equipo_id, servicio_id, cliente_id, client_name, client_whatsapp, client_email, asunto, materia, detalle,
                     no_incluye, plazo_estimado, vigencia, honorarios, gastos, descuento, total, anticipo, saldo,
                     condiciones_pago, payment_link, notas, quote_items_json, mensaje_texto, estado, cobro_estado, cobrado_monto, cobro_updated_at, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                (int)$lawyerUser['id'],
                !empty($workspace['team_id']) ? (int)$workspace['team_id'] : null,
                $serviceId > 0 ? $serviceId : null,
                $clientId > 0 ? $clientId : null,
                $clientName,
                $clientWhatsapp,
                $clientEmail !== '' ? $clientEmail : null,
                $asunto,
                $materia,
                $detalle !== '' ? $detalle : null,
                $noIncluye !== '' ? $noIncluye : null,
                $plazo !== '' ? $plazo : null,
                $vigencia !== '' ? $vigencia : null,
                $honorarios,
                $gastos,
                $descuento,
                $total,
                $anticipo,
                $saldo,
                $condicionesPago !== '' ? $condicionesPago : null,
                $paymentLink !== '' ? $paymentLink : null,
                $notas !== '' ? $notas : null,
                $quoteItemsJson !== null ? $quoteItemsJson : null,
                $mensajeTexto,
                $estado,
                $estado === 'ACEPTADA' ? 'PENDIENTE' : 'SIN_GESTION',
                0,
                null,
            ]);
            $savedQuoteId = (int)$pdo->lastInsertId();
            $_SESSION['mensaje'] = '✅ Cotización generada.';
        }
        if (!empty($workspace['team_id']) && $savedQuoteId > 0) {
            recordLawyerTeamActivity(
                $pdo,
                (int)$workspace['team_id'],
                (int)$lawyerUser['id'],
                $quoteId > 0 ? 'quote_updated' : 'quote_created',
                'quote',
                $savedQuoteId,
                $quoteId > 0 ? 'Cotización actualizada' : 'Cotización creada',
                $clientName . ' · ' . $asunto . ' · ' . quoteStatusMeta($estado)['label']
            );
        }
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo guardar la cotización.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
});

$app->post('/dashboard/cotizaciones/estado', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=quotes')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    $estado = strtoupper(trim((string)($data['estado'] ?? 'BORRADOR')));
    if (!in_array($estado, ['BORRADOR', 'ENVIADA', 'ACEPTADA', 'RECHAZADA', 'ANULADA'], true)) {
        $_SESSION['mensaje'] = '⚠️ Estado de cotización inválido.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    try {
        $pdo = getDB();
        $workspace = lawyerWorkspaceContext($pdo, $lawyerUser);
        $existingQuote = null;
        if (!empty($workspace['team_id'])) {
            $stQuote = $pdo->prepare("SELECT id, client_name, asunto, estado FROM abogado_cotizaciones WHERE id = ? AND equipo_id = ? LIMIT 1");
            $stQuote->execute([(int)($data['quote_id'] ?? 0), (int)$workspace['team_id']]);
            $existingQuote = $stQuote->fetch() ?: null;
        } else {
            $stQuote = $pdo->prepare("SELECT id, client_name, asunto, estado FROM abogado_cotizaciones WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL LIMIT 1");
            $stQuote->execute([(int)($data['quote_id'] ?? 0), (int)$lawyerUser['id']]);
            $existingQuote = $stQuote->fetch() ?: null;
        }
        if (!empty($workspace['team_id'])) {
            $pdo->prepare("UPDATE abogado_cotizaciones SET estado = ?, abogado_id = ? WHERE id = ? AND equipo_id = ?")
                ->execute([$estado, (int)$lawyerUser['id'], (int)($data['quote_id'] ?? 0), (int)$workspace['team_id']]);
        } else {
            $pdo->prepare("UPDATE abogado_cotizaciones SET estado = ? WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL")
                ->execute([$estado, (int)($data['quote_id'] ?? 0), (int)$lawyerUser['id']]);
        }
        if (!empty($workspace['team_id']) && !empty($existingQuote['id'])) {
            recordLawyerTeamActivity(
                $pdo,
                (int)$workspace['team_id'],
                (int)$lawyerUser['id'],
                'quote_status_changed',
                'quote',
                (int)$existingQuote['id'],
                'Cotización marcada como ' . quoteStatusMeta($estado)['label'],
                trim((string)($existingQuote['client_name'] ?? 'Cliente')) . ' · ' . trim((string)($existingQuote['asunto'] ?? 'Cotización'))
            );
        }
        $_SESSION['mensaje'] = '✅ Estado de cotización actualizado.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo actualizar la cotización.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
});

$app->post('/dashboard/cotizaciones/eliminar', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=quotes')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    $quoteId = (int)($data['quote_id'] ?? 0);
    if ($quoteId <= 0) {
        $_SESSION['mensaje'] = '⚠️ Cotización inválida.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    try {
        $pdo = getDB();
        $workspace = lawyerWorkspaceContext($pdo, $lawyerUser);
        if (!empty($workspace['team_id'])) {
            $stQuote = $pdo->prepare("SELECT id, estado, client_name, asunto FROM abogado_cotizaciones WHERE id = ? AND equipo_id = ? LIMIT 1");
            $stQuote->execute([$quoteId, (int)$workspace['team_id']]);
        } else {
            $stQuote = $pdo->prepare("SELECT id, estado, client_name, asunto FROM abogado_cotizaciones WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL LIMIT 1");
            $stQuote->execute([$quoteId, (int)$lawyerUser['id']]);
        }
        $quote = $stQuote->fetch() ?: null;
        if (!$quote) {
            $_SESSION['mensaje'] = '⚠️ Cotización no encontrada.';
            $_SESSION['tipo_mensaje'] = 'error';
            return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
        }
        if (strtoupper(trim((string)($quote['estado'] ?? ''))) !== 'BORRADOR') {
            $_SESSION['mensaje'] = 'ℹ️ Solo se pueden borrar cotizaciones en borrador.';
            $_SESSION['tipo_mensaje'] = 'info';
            return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
        }

        if (!empty($workspace['team_id'])) {
            $pdo->prepare("DELETE FROM abogado_cotizaciones WHERE id = ? AND equipo_id = ?")->execute([$quoteId, (int)$workspace['team_id']]);
        } else {
            $pdo->prepare("DELETE FROM abogado_cotizaciones WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL")->execute([$quoteId, (int)$lawyerUser['id']]);
        }

        if (!empty($workspace['team_id'])) {
            recordLawyerTeamActivity(
                $pdo,
                (int)$workspace['team_id'],
                (int)$lawyerUser['id'],
                'quote_deleted',
                'quote',
                $quoteId,
                'Cotización eliminada',
                trim((string)($quote['client_name'] ?? 'Cliente')) . ' · ' . trim((string)($quote['asunto'] ?? 'Cotización'))
            );
        }

        $_SESSION['mensaje'] = '🗑️ Cotización borrada.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo borrar la cotización.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
});

$app->post('/dashboard/cotizaciones/cobro', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=quotes')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    $collectionAction = strtoupper(trim((string)($data['collection_action'] ?? '')));
    if (!in_array($collectionAction, ['PENDIENTE', 'ANTICIPO', 'PAGADA'], true)) {
        $_SESSION['mensaje'] = '⚠️ Acción de cobro inválida.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    try {
        $pdo = getDB();
        $workspace = lawyerWorkspaceContext($pdo, $lawyerUser);
        if (!empty($workspace['team_id'])) {
            $stQuote = $pdo->prepare("SELECT id, client_name, asunto, estado, total, anticipo FROM abogado_cotizaciones WHERE id = ? AND equipo_id = ? LIMIT 1");
            $stQuote->execute([(int)($data['quote_id'] ?? 0), (int)$workspace['team_id']]);
        } else {
            $stQuote = $pdo->prepare("SELECT id, client_name, asunto, estado, total, anticipo FROM abogado_cotizaciones WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL LIMIT 1");
            $stQuote->execute([(int)($data['quote_id'] ?? 0), (int)$lawyerUser['id']]);
        }
        $quote = $stQuote->fetch() ?: null;
        if (!$quote) {
            $_SESSION['mensaje'] = '⚠️ Cotización no encontrada.';
            $_SESSION['tipo_mensaje'] = 'error';
            return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
        }
        if (strtoupper(trim((string)($quote['estado'] ?? 'BORRADOR'))) !== 'ACEPTADA') {
            $_SESSION['mensaje'] = 'ℹ️ La cobranza rápida aplica a cotizaciones aceptadas.';
            $_SESSION['tipo_mensaje'] = 'info';
            return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
        }

        $collectedAmount = 0.0;
        if ($collectionAction === 'ANTICIPO') {
            $collectedAmount = sanitizeMoneyAmount($quote['anticipo'] ?? 0);
        } elseif ($collectionAction === 'PAGADA') {
            $collectedAmount = sanitizeMoneyAmount($quote['total'] ?? 0);
        }

        if (!empty($workspace['team_id'])) {
            $pdo->prepare("
                UPDATE abogado_cotizaciones
                SET cobro_estado = ?, cobrado_monto = ?, cobro_updated_at = NOW(), abogado_id = ?
                WHERE id = ? AND equipo_id = ?
            ")->execute([
                $collectionAction,
                $collectedAmount,
                (int)$lawyerUser['id'],
                (int)$quote['id'],
                (int)$workspace['team_id'],
            ]);
        } else {
            $pdo->prepare("
                UPDATE abogado_cotizaciones
                SET cobro_estado = ?, cobrado_monto = ?, cobro_updated_at = NOW()
                WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL
            ")->execute([
                $collectionAction,
                $collectedAmount,
                (int)$quote['id'],
                (int)$lawyerUser['id'],
            ]);
        }

        if (!empty($workspace['team_id'])) {
            recordLawyerTeamActivity(
                $pdo,
                (int)$workspace['team_id'],
                (int)$lawyerUser['id'],
                'quote_collection_updated',
                'quote',
                (int)$quote['id'],
                $collectionAction === 'PAGADA' ? 'Cotización marcada como pagada' : ($collectionAction === 'ANTICIPO' ? 'Anticipo recibido' : 'Cobranza pendiente'),
                trim((string)($quote['client_name'] ?? 'Cliente')) . ' · ' . trim((string)($quote['asunto'] ?? 'Cotización'))
            );
        }

        $_SESSION['mensaje'] = $collectionAction === 'PAGADA'
            ? '✅ Cotización marcada como pagada.'
            : ($collectionAction === 'ANTICIPO' ? '✅ Anticipo registrado.' : '✅ Cobranza marcada como pendiente.');
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo actualizar la cobranza.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
});

$app->post('/dashboard/cotizaciones/cobro-recordatorio', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=quotes')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga la página e inténtalo nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
    }

    try {
        $pdo = getDB();
        ensureLawyerServicesAndQuotesTables();
        $workspace = lawyerWorkspaceContext($pdo, $lawyerUser);
        if (!empty($workspace['team_id'])) {
            $stQuote = $pdo->prepare("
                SELECT id, client_name, client_whatsapp, client_email, asunto, estado, total, anticipo, saldo, condiciones_pago, payment_link,
                       cobro_estado, cobrado_monto, cobro_updated_at, cobro_reminder_sent_at, cobro_reminder_count
                FROM abogado_cotizaciones
                WHERE id = ? AND equipo_id = ?
                LIMIT 1
            ");
            $stQuote->execute([(int)($data['quote_id'] ?? 0), (int)$workspace['team_id']]);
        } else {
            $stQuote = $pdo->prepare("
                SELECT id, client_name, client_whatsapp, client_email, asunto, estado, total, anticipo, saldo, condiciones_pago, payment_link,
                       cobro_estado, cobrado_monto, cobro_updated_at, cobro_reminder_sent_at, cobro_reminder_count
                FROM abogado_cotizaciones
                WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL
                LIMIT 1
            ");
            $stQuote->execute([(int)($data['quote_id'] ?? 0), (int)$lawyerUser['id']]);
        }
        $quote = $stQuote->fetch() ?: null;
        if (!$quote) {
            $_SESSION['mensaje'] = '⚠️ Cotización no encontrada.';
            $_SESSION['tipo_mensaje'] = 'error';
            return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
        }

        $quote['cobro_estado_resuelto'] = strtoupper(trim((string)($quote['cobro_estado'] ?? 'SIN_GESTION')));
        if ($quote['cobro_estado_resuelto'] === '' || $quote['cobro_estado_resuelto'] === 'SIN_GESTION') {
            $quote['cobro_estado_resuelto'] = strtoupper(trim((string)($quote['estado'] ?? 'BORRADOR'))) === 'ACEPTADA' ? 'PENDIENTE' : 'SIN_GESTION';
        }
        $quote['cobrado_monto_resuelto'] = sanitizeMoneyAmount($quote['cobrado_monto'] ?? 0);
        $quote['por_cobrar_monto'] = max(0, sanitizeMoneyAmount($quote['total'] ?? 0) - (float)$quote['cobrado_monto_resuelto']);

        if (strtoupper(trim((string)($quote['estado'] ?? 'BORRADOR'))) !== 'ACEPTADA' || $quote['por_cobrar_monto'] <= 0) {
            $_SESSION['mensaje'] = 'ℹ️ El recordatorio de cobro aplica solo a cotizaciones aceptadas con monto pendiente.';
            $_SESSION['tipo_mensaje'] = 'info';
            return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
        }

        $reminderResult = notifyQuoteCollectionReminder($lawyerUser, $quote, 'manual');
        if (empty($reminderResult['ok'])) {
            $_SESSION['mensaje'] = '⚠️ No se pudo enviar el recordatorio de cobro.';
            $_SESSION['tipo_mensaje'] = 'error';
            return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
        }

        if (!empty($workspace['team_id'])) {
            $pdo->prepare("
                UPDATE abogado_cotizaciones
                SET cobro_reminder_sent_at = NOW(),
                    cobro_reminder_count = COALESCE(cobro_reminder_count, 0) + 1,
                    cobro_reminder_last_channel = 'EMAIL',
                    abogado_id = ?
                WHERE id = ? AND equipo_id = ?
            ")->execute([
                (int)$lawyerUser['id'],
                (int)$quote['id'],
                (int)$workspace['team_id'],
            ]);
            recordLawyerTeamActivity(
                $pdo,
                (int)$workspace['team_id'],
                (int)$lawyerUser['id'],
                'quote_collection_reminder_sent',
                'quote',
                (int)$quote['id'],
                'Recordatorio de cobro enviado',
                trim((string)($quote['client_name'] ?? 'Cliente')) . ' · ' . trim((string)($quote['asunto'] ?? 'Cotización'))
            );
        } else {
            $pdo->prepare("
                UPDATE abogado_cotizaciones
                SET cobro_reminder_sent_at = NOW(),
                    cobro_reminder_count = COALESCE(cobro_reminder_count, 0) + 1,
                    cobro_reminder_last_channel = 'EMAIL'
                WHERE id = ? AND abogado_id = ? AND equipo_id IS NULL
            ")->execute([
                (int)$quote['id'],
                (int)$lawyerUser['id'],
            ]);
        }

        $_SESSION['mensaje'] = '✅ Recordatorio de cobro enviado por email.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo enviar el recordatorio de cobro.';
        $_SESSION['tipo_mensaje'] = 'error';
    }

    return $response->withHeader('Location', '/dashboard?tab=quotes')->withStatus(302);
});

$app->get('/jobs/cobranza-recordatorios', function (Request $request, Response $response) {
    $secret = (string)($request->getQueryParams()['key'] ?? $request->getHeaderLine('X-Cron-Key'));
    if (!hasValidCronSecret($secret)) {
        $response->getBody()->write(json_encode(['ok' => false, 'error' => 'forbidden'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
    }

    ensureLawyerServicesAndQuotesTables();
    $summary = ['ok' => true, 'sent' => 0, 'failed' => 0];

    try {
        $pdo = getDB();
        $rows = $pdo->query("
            SELECT q.*, a.nombre, a.email, a.whatsapp, a.razon_social, a.quote_brand_name, a.quote_brand_email, a.quote_brand_phone
            FROM abogado_cotizaciones q
            INNER JOIN abogados a ON a.id = q.abogado_id
            WHERE q.estado = 'ACEPTADA'
              AND COALESCE(q.client_email, '') <> ''
              AND COALESCE(q.cobro_estado, 'SIN_GESTION') IN ('SIN_GESTION', 'PENDIENTE', 'ANTICIPO')
              AND COALESCE(q.cobro_updated_at, q.updated_at, q.created_at) <= DATE_SUB(NOW(), INTERVAL 1 DAY)
              AND (q.cobro_reminder_sent_at IS NULL OR q.cobro_reminder_sent_at <= DATE_SUB(NOW(), INTERVAL 1 DAY))
            ORDER BY COALESCE(q.cobro_updated_at, q.updated_at, q.created_at) ASC
            LIMIT 50
        ")->fetchAll() ?: [];

        foreach ($rows as $quote) {
            $quote['cobro_estado_resuelto'] = strtoupper(trim((string)($quote['cobro_estado'] ?? 'SIN_GESTION')));
            if ($quote['cobro_estado_resuelto'] === '' || $quote['cobro_estado_resuelto'] === 'SIN_GESTION') {
                $quote['cobro_estado_resuelto'] = 'PENDIENTE';
            }
            $quote['cobrado_monto_resuelto'] = sanitizeMoneyAmount($quote['cobrado_monto'] ?? 0);
            $quote['por_cobrar_monto'] = max(0, sanitizeMoneyAmount($quote['total'] ?? 0) - (float)$quote['cobrado_monto_resuelto']);
            if ($quote['por_cobrar_monto'] <= 0) {
                continue;
            }
            $result = notifyQuoteCollectionReminder($quote, $quote, 'automatic');
            if (!empty($result['ok'])) {
                $summary['sent']++;
                $pdo->prepare("
                    UPDATE abogado_cotizaciones
                    SET cobro_reminder_sent_at = NOW(),
                        cobro_reminder_count = COALESCE(cobro_reminder_count, 0) + 1,
                        cobro_reminder_last_channel = 'EMAIL'
                    WHERE id = ?
                ")->execute([(int)$quote['id']]);
                if (!empty($quote['equipo_id'])) {
                    recordLawyerTeamActivity(
                        $pdo,
                        (int)$quote['equipo_id'],
                        (int)$quote['abogado_id'],
                        'quote_collection_reminder_auto',
                        'quote',
                        (int)$quote['id'],
                        'Recordatorio automático de cobro enviado',
                        trim((string)($quote['client_name'] ?? 'Cliente')) . ' · ' . trim((string)($quote['asunto'] ?? 'Cotización'))
                    );
                }
            } else {
                $summary['failed']++;
            }
        }
    } catch (Throwable $e) {
        $summary = ['ok' => false, 'error' => 'job_failed'];
        $response->getBody()->write(json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $response->getBody()->write(json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/dashboard/cotizaciones/{id}/pdf', function (Request $request, Response $response, array $args) use ($renderer) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/dashboard?tab=quotes')->withStatus(302);
    }
    $lawyerUser = currentLawyerDashboardUser();
    if (!$lawyerUser) {
        $_SESSION['mensaje'] = '🔒 No tienes acceso al panel profesional.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $_SESSION['rol'] = 'abogado';

    ensureLawyerQuoteBrandingColumns();
    ensureLawyerServicesAndQuotesTables();

    $quoteId = (int)($args['id'] ?? 0);
    if ($quoteId <= 0) {
        $response->getBody()->write('Cotizacion no encontrada.');
        return $response->withStatus(404);
    }

    try {
        $pdo = getDB();
        $workspace = lawyerWorkspaceContext($pdo, $lawyerUser);
        if (!empty($workspace['team_id'])) {
            $stmt = $pdo->prepare("
                SELECT q.*, s.nombre AS servicio_nombre, s.materia AS servicio_materia
                FROM abogado_cotizaciones q
                LEFT JOIN abogado_servicios s ON s.id = q.servicio_id
                WHERE q.id = ? AND q.equipo_id = ?
                LIMIT 1
            ");
            $stmt->execute([$quoteId, (int)$workspace['team_id']]);
        } else {
            $stmt = $pdo->prepare("
                SELECT q.*, s.nombre AS servicio_nombre, s.materia AS servicio_materia
                FROM abogado_cotizaciones q
                LEFT JOIN abogado_servicios s ON s.id = q.servicio_id
                WHERE q.id = ? AND q.abogado_id = ? AND q.equipo_id IS NULL
                LIMIT 1
            ");
            $stmt->execute([$quoteId, (int)$lawyerUser['id']]);
        }
        $quote = $stmt->fetch() ?: null;
        if (!$quote) {
            $response->getBody()->write('Cotizacion no encontrada.');
            return $response->withStatus(404);
        }
        $statusMeta = quoteStatusMeta((string)($quote['estado'] ?? 'BORRADOR'));
        $quote['estado_ui'] = $statusMeta['label'];
        $quote['estado_class'] = $statusMeta['class'];
        $quote['servicio_nombre_resuelto'] = trim((string)($quote['servicio_nombre'] ?? $quote['asunto'] ?? 'Cotizacion legal'));
        $quote['mensaje_texto'] = trim((string)($quote['mensaje_texto'] ?? ''));
        if ($quote['mensaje_texto'] === '') {
            $quote['mensaje_texto'] = buildLawyerQuoteMessage($lawyerUser, $quote);
        }
        return $renderer->render($response, 'quote_pdf.php', [
            'user' => $lawyerUser,
            'quote' => $quote,
            'branding_settings' => lawyerQuoteBrandingSettings($lawyerUser),
            'generated_at' => date('d/m/Y H:i'),
        ]);
    } catch (Throwable $e) {
        $response->getBody()->write('No se pudo generar la vista PDF.');
        return $response->withStatus(500);
    }
});

$app->get('/admin-login', function (Request $request, Response $response) use ($renderer) {
    if (isAdminSessionAuthenticated()) {
        return $response->withHeader('Location', '/admin')->withStatus(302);
    }
    $mensaje = $_SESSION['mensaje'] ?? null;
    $tipo = $_SESSION['tipo_mensaje'] ?? null;
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);
    return $renderer->render($response, 'admin_login.php', [
        'csrf_token' => ensureCsrfToken(),
        'mensaje' => $mensaje,
        'tipo_mensaje' => $tipo,
    ]);
});

$app->post('/admin-login', function (Request $request, Response $response) {
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Sesión expirada. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin-login')->withStatus(302);
    }
    $creds = adminCredentials();
    $u = trim((string)($data['username'] ?? ''));
    $p = (string)($data['password'] ?? '');
    if (!hash_equals((string)$creds['username'], $u) || !hash_equals((string)$creds['password'], $p)) {
        $_SESSION['mensaje'] = '⛔ Credenciales inválidas.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin-login')->withStatus(302);
    }
    session_regenerate_id(true);
    $_SESSION['admin_auth'] = true;
    $_SESSION['mensaje'] = '✅ Acceso admin habilitado.';
    $_SESSION['tipo_mensaje'] = 'success';
    return $response->withHeader('Location', '/admin')->withStatus(302);
});

$app->get('/admin', [$adminController, 'showDashboard']);

$app->post('/admin/demo-mode', function (Request $request, Response $response) {
    if (!isAdminSessionAuthenticated()) {
        $_SESSION['mensaje'] = '⛔ Acceso admin no autorizado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin-login')->withStatus(302);
    }
    $_SESSION['mensaje'] = 'ℹ️ El modo de prueba fue deshabilitado. Usa la carga masiva de perfiles de prueba gestionada por admin.';
    $_SESSION['tipo_mensaje'] = 'info';

    return $response->withHeader('Location', '/admin')->withStatus(302);
});

$app->post('/admin/usuario/{id}/accion', function (Request $request, Response $response, array $args) {
    if (!isAdminSessionAuthenticated()) {
        $_SESSION['mensaje'] = '⛔ Acceso admin no autorizado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin-login')->withStatus(302);
    }
    $pdo = getDB();

    $id = (int)($args['id'] ?? 0);
    if ($id <= 0) {
        $_SESSION['mensaje'] = '⚠️ ID inválido.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin')->withStatus(302);
    }

    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Recarga /admin.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin')->withStatus(302);
    }

    $accion = trim((string)($data['accion'] ?? ''));
    $horas = max(1, min(24 * 365, (int)($data['horas'] ?? 168)));

    try {
        switch ($accion) {
            case 'aprobar_abogado':
            case 'hacer_abogado':
                $stUser = $pdo->prepare("SELECT * FROM abogados WHERE id=? LIMIT 1");
                $stUser->execute([$id]);
                $target = $stUser->fetch() ?: null;
                if (!$target) {
                    $_SESSION['mensaje'] = '⚠️ Cuenta no encontrada.';
                    $_SESSION['tipo_mensaje'] = 'error';
                    break;
                }
                $pctTarget = lawyerProfileCompletionPercent($target);
                $publicar = ($pctTarget >= 80) ? 1 : 0;
                $pdo->prepare("UPDATE abogados SET rol='abogado', activo=?, solicito_habilitacion_abogado=1, abogado_habilitado=1, estado_verificacion_abogado=CASE WHEN COALESCE(abogado_verificado,0)=1 THEN 'verificado' ELSE 'pendiente' END, fecha_solicitud_habilitacion_abogado=COALESCE(fecha_solicitud_habilitacion_abogado,NOW()) WHERE id=?")->execute([$publicar, $id]);
                $target['rol'] = 'abogado';
                $target['activo'] = $publicar;
                $target['abogado_habilitado'] = 1;
                try {
                    notifyLawyerProfileApproved($target, $pctTarget, $publicar === 1);
                } catch (Throwable $e) {}
                $_SESSION['mensaje'] = $publicar ? ('✅ Perfil publicado como abogado (' . $pctTarget . '%). Verificación PJUD pendiente hasta revisión manual.') : ('✅ Cuenta activada como abogado, pero permanece oculta hasta completar 80% (' . $pctTarget . '%).');
                $_SESSION['tipo_mensaje'] = 'success';
                break;
            case 'rechazar_abogado':
                $pdo->prepare("UPDATE abogados SET abogado_verificado=0, abogado_habilitado=0, estado_verificacion_abogado='rechazado' WHERE id=?")->execute([$id]);
                $_SESSION['mensaje'] = '🛑 Solicitud rechazada.';
                $_SESSION['tipo_mensaje'] = 'info';
                break;
            case 'hacer_cliente':
                $pdo->prepare("UPDATE abogados SET rol='cliente', abogado_habilitado=0, abogado_verificado=0, estado_verificacion_abogado=NULL, destacado_hasta=NULL WHERE id=?")->execute([$id]);
                $_SESSION['mensaje'] = '👤 Cuenta movida a clientes.';
                $_SESSION['tipo_mensaje'] = 'success';
                break;
            case 'destacar':
                $pdo->prepare("UPDATE abogados SET destacado_hasta = DATE_ADD(NOW(), INTERVAL ? HOUR) WHERE id=?")->execute([$horas, $id]);
                $_SESSION['mensaje'] = '⭐ Perfil destacado por ' . $horas . ' horas.';
                $_SESSION['tipo_mensaje'] = 'success';
                break;
            case 'quitar_destacado':
                $pdo->prepare("UPDATE abogados SET destacado_hasta = NULL WHERE id=?")->execute([$id]);
                $_SESSION['mensaje'] = '⭐ Destacado removido.';
                $_SESSION['tipo_mensaje'] = 'info';
                break;
            case 'rut_validado_si':
                if (dbColumnExists('abogados', 'rut_validacion_manual')) {
                    $pdo->prepare("UPDATE abogados SET rut_validacion_manual='si', abogado_verificado=1, estado_verificacion_abogado='verificado', fecha_verificacion_abogado=NOW() WHERE id=?")->execute([$id]);
                }
                $_SESSION['mensaje'] = '✅ RUT marcado como abogado validado manualmente.';
                $_SESSION['tipo_mensaje'] = 'success';
                break;
            case 'rut_validado_no':
                if (dbColumnExists('abogados', 'rut_validacion_manual')) {
                    $pdo->prepare("UPDATE abogados SET rut_validacion_manual='no', abogado_verificado=0, estado_verificacion_abogado='rechazado' WHERE id=?")->execute([$id]);
                }
                $_SESSION['mensaje'] = '🛑 RUT marcado como no-abogado (revisión manual).';
                $_SESSION['tipo_mensaje'] = 'info';
                break;
            case 'rut_validado_pendiente':
                if (dbColumnExists('abogados', 'rut_validacion_manual')) {
                    $pdo->prepare("UPDATE abogados SET rut_validacion_manual=NULL, abogado_verificado=0, estado_verificacion_abogado='pendiente' WHERE id=?")->execute([$id]);
                }
                $_SESSION['mensaje'] = '↩️ Validación manual de RUT reiniciada.';
                $_SESSION['tipo_mensaje'] = 'info';
                break;
            default:
                $_SESSION['mensaje'] = '⚠️ Acción desconocida.';
                $_SESSION['tipo_mensaje'] = 'error';
                break;
        }
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ Error al aplicar acción admin.';
        $_SESSION['tipo_mensaje'] = 'error';
    }
    return $response->withHeader('Location', '/admin')->withStatus(302);
});
$app->post('/admin/leads-mantenimiento', function (Request $request, Response $response) {
    if (!isAdminSessionAuthenticated()) {
        $_SESSION['mensaje'] = '⛔ Acceso admin no autorizado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin-login')->withStatus(302);
    }
    $pdo = getDB();
    $m1 = 0; $m2 = 0;
    try {
        $m1 = (int)$pdo->exec("UPDATE contactos_revelados SET retention_stage='papelera', papelera_desde=NOW(), papelera_hasta=DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE COALESCE(retention_stage,'activo')='activo' AND activo_hasta IS NOT NULL AND activo_hasta < NOW()");
        $m2 = (int)$pdo->exec("DELETE FROM contactos_revelados WHERE COALESCE(retention_stage,'activo')='papelera' AND papelera_hasta IS NOT NULL AND papelera_hasta < NOW()");
        $_SESSION['mensaje'] = 'Mantenimiento leads ejecutado: ' . $m1 . ' a papelera, ' . $m2 . ' eliminados.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = 'Error en mantenimiento de leads.';
        $_SESSION['tipo_mensaje'] = 'error';
    }
    return $response->withHeader('Location', '/admin')->withStatus(302);
});
$app->get('/admin/leads-export.csv', function (Request $request, Response $response) {
    if (!isAdminSessionAuthenticated()) {
        $_SESSION['mensaje'] = '⛔ Acceso admin no autorizado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin-login')->withStatus(302);
    }
    $pdo = getDB();
    $rows = $pdo->query("SELECT id, abogado_id, cliente_id, medio_contacto, estado, COALESCE(retention_stage,'activo') retention_stage, activo_hasta, papelera_desde, papelera_hasta, created_at FROM contactos_revelados ORDER BY id DESC LIMIT 5000")->fetchAll() ?: [];
    $fh = fopen('php://temp', 'r+');
    fputcsv($fh, ['id','abogado_id','cliente_id','medio_contacto','estado','retention_stage','activo_hasta','papelera_desde','papelera_hasta','created_at']);
    foreach ($rows as $r) { fputcsv($fh, [$r['id'] ?? '',$r['abogado_id'] ?? '',$r['cliente_id'] ?? '',$r['medio_contacto'] ?? '',$r['estado'] ?? '',$r['retention_stage'] ?? '',$r['activo_hasta'] ?? '',$r['papelera_desde'] ?? '',$r['papelera_hasta'] ?? '',$r['created_at'] ?? '']); }
    rewind($fh);
    $csv = stream_get_contents($fh) ?: '';
    fclose($fh);
    try { $pdo->exec("UPDATE contactos_revelados SET respaldado_at = NOW() WHERE respaldado_at IS NULL"); } catch (Throwable $e) {}
    $response->getBody()->write($csv);
    return $response->withHeader('Content-Type', 'text/csv; charset=UTF-8')->withHeader('Content-Disposition', 'attachment; filename="leads-export.csv"');
});
$app->post('/admin/lead/{id}/restaurar', function (Request $request, Response $response, array $args) {
    if (!isAdminSessionAuthenticated()) {
        $_SESSION['mensaje'] = '⛔ Acceso admin no autorizado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/admin-login')->withStatus(302);
    }
    $id = (int)($args['id'] ?? 0);
    if ($id > 0) {
        try {
            getDB()->prepare("UPDATE contactos_revelados SET retention_stage='activo', activo_hasta=DATE_ADD(NOW(), INTERVAL 30 DAY), papelera_desde=NULL, papelera_hasta=NULL WHERE id=?")->execute([$id]);
            $_SESSION['mensaje'] = 'Lead restaurado.';
            $_SESSION['tipo_mensaje'] = 'success';
        } catch (Throwable $e) {
            $_SESSION['mensaje'] = 'No se pudo restaurar el lead.';
            $_SESSION['tipo_mensaje'] = 'error';
        }
    }
    return $response->withHeader('Location', '/admin?lead_stage=papelera')->withStatus(302);
});

$app->map(['GET','POST'], '/upload', function (Request $request, Response $response) use ($renderer) {
    $uploadDir = '/tmp/lawyers_uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }

    $mensaje = null;
    $tipo = null;
    $savedPath = null;
    $preview = null;
    $previewPath = null;

    if (strtoupper($request->getMethod()) === 'POST') {
        $files = $request->getUploadedFiles();
        $file = $files['archivo'] ?? null;
        if (!$file) {
            $mensaje = 'No se recibió archivo.';
            $tipo = 'error';
        } elseif ($file->getError() !== UPLOAD_ERR_OK) {
            $mensaje = 'Error al subir archivo.';
            $tipo = 'error';
        } else {
            $size = (int)$file->getSize();
            if ($size <= 0 || $size > 5 * 1024 * 1024) {
                $mensaje = 'El archivo debe pesar entre 1 byte y 5 MB.';
                $tipo = 'error';
            } else {
                $clientName = (string)$file->getClientFilename();
                $safeBase = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($clientName));
                if ($safeBase === '' || $safeBase === '.' || $safeBase === '..') {
                    $safeBase = 'archivo.txt';
                }
                $target = $uploadDir . '/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $safeBase;
                $file->moveTo($target);
                $mensaje = 'Archivo subido correctamente.';
                $tipo = 'success';
                $savedPath = $target;
                trackEvent('file_uploaded', [
                    'name' => $safeBase,
                    'size' => $size,
                    'path' => $target
                ]);
            }
        }
    }

    $query = $request->getQueryParams();
    $previewReq = trim((string)($query['preview'] ?? ''));
    if ($previewReq !== '') {
        $real = realpath($previewReq);
        $realBase = realpath($uploadDir);
        if ($real && $realBase && str_starts_with($real, $realBase . DIRECTORY_SEPARATOR) && is_file($real)) {
            $ext = strtolower(pathinfo($real, PATHINFO_EXTENSION));
            if (in_array($ext, ['txt', 'log', 'md', 'json', 'csv'], true)) {
                $preview = @file_get_contents($real, false, null, 0, 50000);
                $previewPath = $real;
                if ($preview === false) {
                    $preview = null;
                    $mensaje = 'No se pudo leer el archivo para vista previa.';
                    $tipo = 'error';
                }
            } else {
                $mensaje = 'Vista previa disponible solo para archivos de texto.';
                $tipo = 'error';
            }
        } else {
            $mensaje = 'Ruta de preview inválida.';
            $tipo = 'error';
        }
    }

    $recentFiles = [];
    if (is_dir($uploadDir)) {
        $items = glob($uploadDir . '/*') ?: [];
        rsort($items);
        foreach (array_slice($items, 0, 20) as $path) {
            if (!is_file($path)) continue;
            $recentFiles[] = [
                'path' => $path,
                'name' => basename($path),
                'size' => filesize($path) ?: 0,
                'mtime' => @date('Y-m-d H:i:s', filemtime($path) ?: time())
            ];
        }
    }

    return $renderer->render($response, 'upload.php', [
        'mensaje' => $mensaje,
        'tipo' => $tipo,
        'saved_path' => $savedPath,
        'recent_files' => $recentFiles,
        'preview' => $preview,
        'preview_path' => $previewPath
    ]);
});


$app->get('/login-google', function (Request $request, Response $response) {
    try {
        $next = normalizeInternalNextPath($request->getQueryParams()['next'] ?? '/explorar', '/explorar');
        $_SESSION['login_next'] = $next;
        $client = getGoogleClient();
        $client->setAccessType('online');
        $client->setPrompt('select_account');
        $state = bin2hex(random_bytes(12));
        $_SESSION['google_oauth_state'] = $state;
        $client->setState($state);
        $authUrl = $client->createAuthUrl();
        return $response->withHeader('Location', $authUrl)->withStatus(302);
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No fue posible iniciar sesión con Google.';
        $_SESSION['tipo_mensaje'] = 'error';
        $response->getBody()->write('<h1>Error iniciando sesión con Google</h1>');
        return $response->withStatus(500);
    }
});

$app->get('/auth/google/callback', function (Request $request, Response $response) {
    $params = $request->getQueryParams();
    if (!empty($params['error'])) {
        $_SESSION['mensaje'] = '⚠️ Inicio de sesión cancelado o rechazado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }

    $code = trim((string)($params['code'] ?? ''));
    if ($code === '') {
        $_SESSION['mensaje'] = '⚠️ Google no devolvió un código de autenticación.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }

    $state = trim((string)($params['state'] ?? ''));
    if (!empty($_SESSION['google_oauth_state']) && !hash_equals((string)$_SESSION['google_oauth_state'], $state)) {
        $_SESSION['mensaje'] = '⚠️ No pudimos validar el inicio de sesión (state inválido).';
        $_SESSION['tipo_mensaje'] = 'error';
        unset($_SESSION['google_oauth_state']);
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
    unset($_SESSION['google_oauth_state']);

    try {
        $client = getGoogleClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (!is_array($token) || isset($token['error'])) {
            $_SESSION['mensaje'] = '⚠️ No fue posible completar el login con Google.';
            $_SESSION['tipo_mensaje'] = 'error';
            return $response->withHeader('Location', '/explorar')->withStatus(302);
        }
        $client->setAccessToken($token);
        $oauth2 = new Google\Service\Oauth2($client);
        $gUser = $oauth2->userinfo->get();

        $email = trim((string)($gUser->email ?? ''));
        if ($email === '') {
            $_SESSION['mensaje'] = '⚠️ Google no entregó un correo válido.';
            $_SESSION['tipo_mensaje'] = 'error';
            return $response->withHeader('Location', '/explorar')->withStatus(302);
        }
        $nombre = trim((string)($gUser->name ?? 'Usuario'));
        $googleId = trim((string)($gUser->id ?? ''));
        $googlePicture = trim((string)($gUser->picture ?? ''));

        $pdo = getDB();
        $st = $pdo->prepare('SELECT * FROM abogados WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $user = $st->fetch();

        $isNewRegistration = false;
        if ($user) {
            $pdo->prepare("UPDATE abogados SET nombre=?, google_id=?, google_picture=?, email=?, rol=COALESCE(NULLIF(rol,''),'cliente'), puede_publicar_casos=COALESCE(puede_publicar_casos,1) WHERE id=?")
                ->execute([$nombre, $googleId, $googlePicture, $email, (int)$user['id']]);
            $userId = (int)$user['id'];
            $slug = (string)($user['slug'] ?? '');
            $rol = (string)($user['rol'] ?? 'cliente');
        } else {
            $baseSlug = createSlug($nombre ?: 'usuario');
            if ($baseSlug === '') $baseSlug = 'usuario';
            $slug = $baseSlug;
            $i = 1;
            $check = $pdo->prepare('SELECT COUNT(*) FROM abogados WHERE slug = ?');
            while (true) {
                $check->execute([$slug]);
                if ((int)$check->fetchColumn() === 0) break;
                $i++;
                $slug = $baseSlug . '-' . $i;
            }
            $rol = 'cliente';
            $pdo->prepare("INSERT INTO abogados (google_id, slug, nombre, email, rol, google_picture, activo, puede_publicar_casos) VALUES (?, ?, ?, ?, 'cliente', ?, 1, 1)")
                ->execute([$googleId, $slug, $nombre, $email, $googlePicture]);
            $userId = (int)$pdo->lastInsertId();
            $isNewRegistration = true;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['email'] = $email;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['user_name'] = $nombre;
        $_SESSION['rol'] = $rol ?: 'cliente';

        if ($isNewRegistration && resendIsConfigured()) {
            try {
                $subject = 'Nuevo registro en Tu Estudio Juridico';
                $html = '<p>Se creó un nuevo usuario en Tu Estudio Juridico.</p>'
                    . '<p><strong>Nombre:</strong> ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>'
                    . '<strong>Rol inicial:</strong> cliente</p>';
                $text = "Se creó un nuevo usuario en Tu Estudio Juridico.\n\n"
                    . "Nombre: {$nombre}\n"
                    . "Email: {$email}\n"
                    . "Rol inicial: cliente\n";
                sendAdminEventEmail($subject, $html, $text);
            } catch (Throwable $e) {
                // fail-open
            }
        }

        $next = normalizeInternalNextPath($_SESSION['login_next'] ?? '/explorar', '/explorar');
        unset($_SESSION['login_next']);
        return $response->withHeader('Location', $next)->withStatus(302);
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ Error procesando login con Google.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
});


$app->get('/completar-contacto-basico', function (Request $request, Response $response) use ($renderer) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google')->withStatus(302);
    }
    $next = normalizeInternalNextPath($request->getQueryParams()['next'] ?? '/explorar', '/explorar');
    $pdo = getDB();
    $st = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $st->execute([(int)$_SESSION['user_id']]);
    $user = $st->fetch() ?: [];
    return $renderer->render($response, 'contacto_basico.php', [
        'user' => $user,
        'next' => $next,
        'csrf_token' => ensureCsrfToken(),
        'mensaje' => $_SESSION['mensaje'] ?? null,
        'tipo_mensaje' => $_SESSION['tipo_mensaje'] ?? null,
    ]);
});

$app->post('/guardar-contacto-basico', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/completar-contacto-basico')->withStatus(302);
    }
    $next = normalizeInternalNextPath($data['next'] ?? '/explorar', '/explorar');
    $nombre = trim((string)($data['nombre'] ?? ''));
    $rawWhatsapp = preg_replace('/\D+/', '', (string)($data['whatsapp'] ?? ''));
    if (str_starts_with((string)$rawWhatsapp, '56')) $rawWhatsapp = substr((string)$rawWhatsapp, 2);
    $whatsapp = validarWhatsApp($rawWhatsapp);
    $nombreLen = function_exists('mb_strlen') ? mb_strlen($nombre, 'UTF-8') : strlen($nombre);
    if ($nombreLen < 3 || !$whatsapp) {
        $_SESSION['mensaje'] = '⚠️ Debes completar nombre y WhatsApp válido (+569XXXXXXXX).';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/completar-contacto-basico?next=' . rawurlencode($next))->withStatus(302);
    }
    $pdo = getDB();
    $pdo->prepare("UPDATE abogados SET nombre = ?, whatsapp = ?, rol = 'cliente' WHERE id = ?")
        ->execute([$nombre, $whatsapp, (int)$_SESSION['user_id']]);
    $_SESSION['nombre'] = $nombre;
    $_SESSION['mensaje'] = '✅ Datos guardados. Ya puedes ver perfiles.';
    $_SESSION['tipo_mensaje'] = 'success';
    return $response->withHeader('Location', $next)->withStatus(302);
});


$app->get('/completar-datos', function (Request $request, Response $response) use ($renderer) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/completar-datos')->withStatus(302);
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: [];
    if (!$user) {
        $_SESSION['mensaje'] = '⚠️ Cuenta no encontrada.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/logout')->withStatus(302);
    }

    $modo = trim((string)(($request->getQueryParams()['modo'] ?? '')));
    if ($modo === '') {
        $isLawyer = userCanEditLawyerProfile($user);
        $modo = $isLawyer ? 'abogado' : 'cliente';
    }

    $mensaje = $_SESSION['mensaje'] ?? null;
    $tipoMensaje = $_SESSION['tipo_mensaje'] ?? null;
    unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

    if ($modo === 'abogado') {
        if (!userCanEditLawyerProfile($user)) {
            $_SESSION['mensaje'] = '🔒 Tu cuenta aún no tiene perfil profesional activo.';
            $_SESSION['tipo_mensaje'] = 'info';
            return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
        }
        $perfilPct = lawyerProfileCompletionPercent($user);
        $perfilChecklist = lawyerProfileCompletionChecklist($user);
        return $renderer->render($response, 'onboarding_abogado.php', [
            'user' => $user,
            'perfil_completion_pct' => $perfilPct,
            'perfil_completion_checklist' => $perfilChecklist,
            'csrf_token' => ensureCsrfToken(),
            'universidades_chile' => universidadesChile(),
            'materias_taxonomia' => lawyerMateriasTaxonomia(),
            'regiones_chile' => regionesChile(),
            'comunas_sugeridas' => comunasChileSugeridas(),
            'comunas_catalogo' => comunasChileCatalog(),
            'lawyer_verification_enabled' => true,
            'mensaje' => $mensaje,
            'tipo_mensaje' => $tipoMensaje,
        ]);
    }

    return $renderer->render($response, 'onboarding_cliente.php', [
        'user' => $user,
        'csrf_token' => ensureCsrfToken(),
        'comunas_sugeridas' => comunasChileSugeridas(),
        'mensaje' => $mensaje,
        'tipo_mensaje' => $tipoMensaje,
        'error' => null,
    ]);
});


$app->post('/perfil-contacto/{id_abogado}', function (Request $request, Response $response, array $args) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google')->withStatus(302);
    }
    $pdo = getDB();
    $data = (array)($request->getParsedBody() ?? []);
    $idAbogado = (int)($args['id_abogado'] ?? 0);
    $slug = trim((string)($data['slug'] ?? ''));
    if ($idAbogado <= 0) {
        $_SESSION['mensaje'] = '⚠️ Perfil inválido.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/' . rawurlencode($slug ?: 'explorar'))->withStatus(302);
    }

    $stViewer = $pdo->prepare("SELECT * FROM abogados WHERE id=? LIMIT 1");
    $stViewer->execute([(int)$_SESSION['user_id']]);
    $viewer = $stViewer->fetch() ?: null;
    $stLawyer = $pdo->prepare("SELECT * FROM abogados WHERE id=? LIMIT 1");
    $stLawyer->execute([$idAbogado]);
    $lawyer = $stLawyer->fetch() ?: null;
    if (!$viewer || !$lawyer || (($lawyer['rol'] ?? '') !== 'abogado')) {
        $_SESSION['mensaje'] = '⚠️ No se encontró el perfil del abogado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
    $targetPath = '/' . rawurlencode((string)($lawyer['slug'] ?? ($slug ?: 'explorar')));

    // Si quien mira es abogado, no genera lead. Solo registra visita entre abogados.
    $viewerIsLawyer = userCanAccessLawyerDashboard((array)$viewer);
    if ($viewerIsLawyer) {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS abogado_views_lawyer_unicas (id INT AUTO_INCREMENT PRIMARY KEY, abogado_id INT NOT NULL, viewer_abogado_id INT NOT NULL, created_at DATETIME NOT NULL, UNIQUE KEY uniq_pair (abogado_id, viewer_abogado_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            if (!dbColumnExists('abogados', 'vistas_abogados')) {
                $pdo->exec("ALTER TABLE abogados ADD COLUMN vistas_abogados INT NOT NULL DEFAULT 0");
            }
            $ins = $pdo->prepare("INSERT IGNORE INTO abogado_views_lawyer_unicas (abogado_id, viewer_abogado_id, created_at) VALUES (?, ?, NOW())");
            $ins->execute([(int)$lawyer['id'], (int)$viewer['id']]);
            if ($ins->rowCount() > 0) {
                $pdo->prepare("UPDATE abogados SET vistas_abogados = COALESCE(vistas_abogados,0)+1 WHERE id=?")->execute([(int)$lawyer['id']]);
            }
        } catch (Throwable $e) {}
        $_SESSION['mensaje'] = '👀 Visita entre abogados registrada. Las cuentas de abogado no generan leads desde este formulario.';
        $_SESSION['tipo_mensaje'] = 'info';
        return $response->withHeader('Location', $targetPath)->withStatus(302);
    }

    // Cliente: crea lead y desbloquea contacto (deduplicado)
    $nombreCliente = normalizarTexto($data['nombre_cliente'] ?? ($viewer['nombre'] ?? ''));
    $rawWs = preg_replace('/\D+/', '', (string)($data['whatsapp_cliente'] ?? ''));
    if (str_starts_with((string)$rawWs, '56')) $rawWs = substr((string)$rawWs, 2);
    $whatsapp = validarWhatsApp($rawWs);
    $detalleCaso = normalizarTexto($data['detalle_caso'] ?? '');
    $pref = trim((string)($data['preferencia_contacto'] ?? 'whatsapp'));
    if (!in_array($pref, ['whatsapp','llamada'], true)) $pref = 'whatsapp';
    $wantsContact = !empty($data['quiero_contacto_abogado']);
    $nameLen = function_exists('mb_strlen') ? mb_strlen($nombreCliente, 'UTF-8') : strlen($nombreCliente);
    if ($nameLen < 3 || !$whatsapp || !$wantsContact) {
        $_SESSION['mensaje'] = '⚠️ Completa nombre, WhatsApp válido y confirma que quieres conocer los datos para contactar al abogado.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', $targetPath)->withStatus(302);
    }

    $pdo->prepare("UPDATE abogados SET nombre = COALESCE(NULLIF(?,''), nombre), whatsapp = ? WHERE id = ?")
        ->execute([$nombreCliente, $whatsapp, (int)$viewer['id']]);

    $lawyerWorkspace = lawyerWorkspaceContext($pdo, (array)$lawyer);
    if (!empty($lawyerWorkspace['team_id'])) {
        $stLead = $pdo->prepare("SELECT id FROM contactos_revelados WHERE abogado_id = ? AND cliente_id = ? AND equipo_id = ? LIMIT 1");
        $stLead->execute([(int)$lawyer['id'], (int)$viewer['id'], (int)$lawyerWorkspace['team_id']]);
    } else {
        $stLead = $pdo->prepare("SELECT id FROM contactos_revelados WHERE abogado_id = ? AND cliente_id = ? AND equipo_id IS NULL LIMIT 1");
        $stLead->execute([(int)$lawyer['id'], (int)$viewer['id']]);
    }
    $leadId = (int)($stLead->fetchColumn() ?: 0);
    $medio = 'Perfil Público · ' . ($pref === 'llamada' ? 'Llamada' : 'WhatsApp');
    if ($leadId > 0) {
        $pdo->prepare("UPDATE contactos_revelados SET medio_contacto=?, consulta=COALESCE(NULLIF(?,''), consulta), retention_stage=COALESCE(retention_stage,'activo'), activo_hasta=COALESCE(activo_hasta, DATE_ADD(NOW(), INTERVAL 30 DAY)) WHERE id=?")
            ->execute([$medio, $detalleCaso !== '' ? $detalleCaso : null, $leadId]);
    } else {
        $pdo->prepare("INSERT INTO contactos_revelados (abogado_id, equipo_id, assigned_abogado_id, cliente_id, estado, medio_contacto, consulta, fecha_revelado, retention_stage, activo_hasta, estado_updated_at) VALUES (?, ?, ?, ?, 'PENDIENTE', ?, ?, NOW(), 'activo', DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())")
            ->execute([
                (int)$lawyer['id'],
                !empty($lawyerWorkspace['team_id']) ? (int)$lawyerWorkspace['team_id'] : null,
                (int)$lawyer['id'],
                (int)$viewer['id'],
                $medio,
                ($detalleCaso !== '' ? $detalleCaso : null)
            ]);
        $leadId = (int)$pdo->lastInsertId();
    }

    // Notificación transaccional al abogado por nuevo lead
    try {
        $to = trim((string)($lawyer['email'] ?? ''));
        if ($to !== '' && filter_var($to, FILTER_VALIDATE_EMAIL) && resendIsConfigured()) {
            $subject = 'Nuevo lead en Tu Estudio Juridico: quieren contactarte';
            $profileUrl = 'https://example.com/' . (string)($lawyer['slug'] ?? '');
            $bodyText = "Se generó un lead desde tu perfil en Tu Estudio Juridico.\n\n"
                . "Abogado: " . (string)($lawyer['nombre'] ?? '') . "\n"
                . "Perfil: " . $profileUrl . "\n\n"
                . "Cliente: " . $nombreCliente . "\n"
                . "Email cliente: " . (string)($viewer['email'] ?? '') . "\n"
                . "WhatsApp cliente: +56" . $whatsapp . "\n"
                . "Preferencia: " . ($pref === 'llamada' ? 'Llamada' : 'WhatsApp') . "\n"
                . "Medio: " . $medio . "\n";
            if ($detalleCaso !== '') {
                $bodyText .= "\nCaso:\n" . $detalleCaso . "\n";
            }
            $bodyHtml = '<p>Se generó un lead desde tu perfil en Tu Estudio Juridico.</p>'
                . '<p><strong>Abogado:</strong> ' . htmlspecialchars((string)($lawyer['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Perfil:</strong> <a href="' . htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') . '</a></p>'
                . '<p><strong>Cliente:</strong> ' . htmlspecialchars($nombreCliente, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Email cliente:</strong> ' . htmlspecialchars((string)($viewer['email'] ?? ''), ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>WhatsApp cliente:</strong> +56' . htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Preferencia:</strong> ' . htmlspecialchars(($pref === 'llamada' ? 'Llamada' : 'WhatsApp'), ENT_QUOTES, 'UTF-8') . '<br>'
                . '<strong>Medio:</strong> ' . htmlspecialchars($medio, ENT_QUOTES, 'UTF-8') . '</p>';
            if ($detalleCaso !== '') {
                $bodyHtml .= '<p><strong>Caso:</strong><br>' . nl2br(htmlspecialchars($detalleCaso, ENT_QUOTES, 'UTF-8')) . '</p>';
            }
            sendResendEmail([
                'to' => [$to],
                'subject' => $subject,
                'html' => $bodyHtml,
                'text' => $bodyText,
                'reply_to' => filter_var((string)($viewer['email'] ?? ''), FILTER_VALIDATE_EMAIL) ? (string)$viewer['email'] : null,
            ]);
        }
    } catch (Throwable $e) {}

    trackEvent('profile_contact_lead_created', [
        'abogado_id' => (int)$lawyer['id'],
        'cliente_id' => (int)$viewer['id'],
        'lead_id' => $leadId,
        'preferencia' => $pref,
    ]);
    recordWebMetricEvent('interaction_lead', [
        'path' => $targetPath,
        'content_type' => 'abogado_profile',
        'content_id' => (int)$lawyer['id'],
        'content_slug' => trim((string)($lawyer['slug'] ?? '')),
        'source' => 'perfil_contacto',
        'payload' => [
            'preferencia' => $pref,
            'lead_id' => $leadId
        ]
    ], [
        'window_sec' => 3600,
        'dedupe_scope' => 'lead:u' . (int)$viewer['id'] . ':a' . (int)$lawyer['id'] . ':h' . date('YmdH')
    ]);

    $_SESSION['mensaje'] = resendIsConfigured()
        ? '✅ Contacto desbloqueado. También notificamos al abogado por correo.'
        : '✅ Contacto desbloqueado.';
    $_SESSION['tipo_mensaje'] = 'success';
    return $response->withHeader('Location', $targetPath . '?contacto=1')->withStatus(302);
});


$app->post('/desactivar-perfil-profesional', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/explorar')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: [];
    if (!$user || !userCanAccessLawyerDashboard((array)$user)) {
        $_SESSION['mensaje'] = '🔒 No tienes un perfil profesional activo para desactivar.';
        $_SESSION['tipo_mensaje'] = 'info';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
    try {
        $pdo->prepare("UPDATE abogados SET activo = 0 WHERE id = ?")->execute([(int)$user['id']]);
        $_SESSION['mensaje'] = '⏸️ Tu perfil profesional fue desactivado temporalmente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo desactivar tu perfil por ahora.';
        $_SESSION['tipo_mensaje'] = 'error';
    }
    return $response->withHeader('Location', '/explorar')->withStatus(302);
});

$app->post('/reactivar-perfil-profesional', function (Request $request, Response $response) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/explorar')->withStatus(302);
    }
    $data = (array)($request->getParsedBody() ?? []);
    if (!hasValidCsrfToken($data['csrf_token'] ?? null)) {
        $_SESSION['mensaje'] = '⚠️ Tu sesión expiró. Intenta nuevamente.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: [];
    if (!$user || !userCanAccessLawyerDashboard((array)$user)) {
        $_SESSION['mensaje'] = '🔒 Tu cuenta no tiene acceso profesional para reactivar un perfil.';
        $_SESSION['tipo_mensaje'] = 'info';
        return $response->withHeader('Location', '/explorar')->withStatus(302);
    }
    try {
        $pdo->prepare("UPDATE abogados SET activo = 1 WHERE id = ?")->execute([(int)$user['id']]);
        $_SESSION['mensaje'] = '✅ Tu perfil profesional volvió a estar visible en el directorio.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (Throwable $e) {
        $_SESSION['mensaje'] = '⚠️ No se pudo reactivar tu perfil por ahora.';
        $_SESSION['tipo_mensaje'] = 'error';
    }
    return $response->withHeader('Location', '/explorar')->withStatus(302);
});


$app->get('/editar-perfil', function (Request $request, Response $response) {
    return $response->withHeader('Location', '/completar-datos?modo=abogado')->withStatus(302);
});

$app->get('/mi-tarjeta', function (Request $request, Response $response) use ($renderer) {
    if (empty($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/login-google?next=/mi-tarjeta')->withStatus(302);
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
    $stmt->execute([(int)$_SESSION['user_id']]);
    $abogado = $stmt->fetch() ?: [];
    if (!$abogado) {
        $_SESSION['mensaje'] = '⚠️ Cuenta no encontrada.';
        $_SESSION['tipo_mensaje'] = 'error';
        return $response->withHeader('Location', '/panel')->withStatus(302);
    }
    if (!userCanAccessLawyerDashboard((array)$abogado)) {
        $_SESSION['mensaje'] = '🔒 Tu cuenta no tiene perfil profesional activo.';
        $_SESSION['tipo_mensaje'] = 'info';
        return $response->withHeader('Location', '/acceso-profesional')->withStatus(302);
    }
    $abogado['foto_final'] = resolveLawyerPhoto($abogado, 320, false);
    return $renderer->render($response, 'card.php', [
        'abogado' => $abogado,
    ]);
});

// ============================================================================
// RUTA COMODÍN (ÚLTIMA)
// ============================================================================

$app->get('/{slug}', function (Request $request, Response $response, $args) use ($renderer) {
    $slug = $args['slug'];
    
    $rutasEspeciales = [
        'dashboard', 'panel', 'admin', 'completar-datos', 'explorar', 'login-google', 
        'auth', 'api', 'logout', 'bajar-caso', 'revelar-contacto',
        'editar-perfil', 'guardar-abogado', 'guardar-cliente',
        'actualizar-estado-caso', 'actualizar-seguimiento',
        'agregar-prospecto-crm', 'solicitar-habilitacion-abogado', 'usar-modo-cliente',
        'aplicar-programa-profesional', 'perfil-contacto', 'guardar-contacto-basico', 'upload',
        'acceso-profesional', 'completar-contacto-basico', 'admin-login', 'tarifario'
    ];
    
    if (in_array($slug, $rutasEspeciales)) {
        $response->getBody()->write("<h1>404 - Ruta no encontrada</h1>");
        return $response->withStatus(404);
    }
    
    try {
        $pdo = getDB();
    $sqlProfile = "SELECT * FROM abogados WHERE slug = ? AND rol = 'abogado'";
    $stmt = $pdo->prepare($sqlProfile);
        $stmt->execute([$slug]);
        $abogado = $stmt->fetch();

        if (!$abogado) {
            $response->getBody()->write("<h1>404 - Perfil no encontrado</h1>");
            return $response->withStatus(404);
        }

        $abogado['foto_final'] = resolveLawyerPhoto($abogado, 720, false);
        recordWebMetricEvent('content_view', [
            'path' => '/' . $slug,
            'content_type' => 'abogado_profile',
            'content_id' => (int)($abogado['id'] ?? 0),
            'content_slug' => $slug,
            'source' => 'route_profile'
        ], [
            'window_sec' => 600
        ]);

        $viewer = null;
        $contactUnlocked = false;
        if (!empty($_SESSION['user_id'])) {
            $stmtViewer = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
            $stmtViewer->execute([(int)$_SESSION['user_id']]);
            $viewer = $stmtViewer->fetch() ?: null;
            if ($viewer) {
                $viewerWhatsapp = trim((string)($viewer['whatsapp'] ?? ''));
                if ((int)$viewer['id'] === (int)$abogado['id']) {
                    $contactUnlocked = true;
                } else {
                    $stmtLead = $pdo->prepare("SELECT COUNT(*) FROM contactos_revelados WHERE abogado_id = ? AND cliente_id = ?");
                    $stmtLead->execute([(int)$abogado['id'], (int)$viewer['id']]);
                    $contactUnlocked = ((int)$stmtLead->fetchColumn()) > 0;
                }
            }
        }

        $mensaje = $_SESSION['mensaje'] ?? null;
        $tipo_mensaje = $_SESSION['tipo_mensaje'] ?? null;
        unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']);

        return $renderer->render($response, 'profile.php', [
            'abogado' => $abogado,
            'viewer' => $viewer,
            'contact_unlocked' => $contactUnlocked,
            'csrf_token' => ensureCsrfToken(),
            'mensaje' => $mensaje,
            'tipo_mensaje' => $tipo_mensaje
        ]);
    } catch (\PDOException $e) {
        $response->getBody()->write("<h1>Error del servidor</h1><p>" . $e->getMessage() . "</p>");
        return $response->withStatus(500);
    }
});

$app->run();
