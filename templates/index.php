<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;

require __DIR__ . '/../vendor/autoload.php';

function envOrDefault($key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

// --- CONFIGURACIÓN DE SESIÓN PERSISTENTE (30 DÍAS) ---
ini_set('session.cookie_lifetime', 2592000);
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_path', '/');
ini_set('session.cookie_secure', '1'); 
ini_set('session.cookie_httponly', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$renderer = new PhpRenderer(__DIR__ . '/../templates');

// --- CREDENCIALES ---
$googleClientId     = envOrDefault('GOOGLE_CLIENT_ID', '');
$googleClientSecret = envOrDefault('GOOGLE_CLIENT_SECRET', '');
$redirectUri        = envOrDefault('GOOGLE_REDIRECT_URI', '');

// --- FUNCIONES ---
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

// ==============================================================================
// RUTAS
// ==============================================================================

// 1. HOME (Redirección Inteligente)
$app->get('/', function (Request $request, Response $response) use ($renderer) {
    if (isset($_SESSION['user_id']) && isset($_SESSION['rol'])) {
        if ($_SESSION['rol'] === 'cliente') {
            return $response->withHeader('Location', '/completar-datos')->withStatus(302);
        }
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    }
    return $renderer->render($response, 'home.php');
});

// 2. NUEVA RUTA: Bajar/Cerrar Caso (Esto mata el error 404 de tu captura)
$app->get('/bajar-caso', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) return $response->withStatus(403);
    $pdo = getDB();
    // Limpiamos la solicitud del cliente para que desaparezca del marketplace
    $pdo->prepare("UPDATE abogados SET descripcion_caso = NULL, especialidad = NULL WHERE id = ?")
        ->execute([$_SESSION['user_id']]);
    return $response->withHeader('Location', '/completar-datos')->withStatus(302);
});
// 2. NUEVA RUTA: Bajar/Cerrar Caso (Esto evita el 404 del botón rojo)
$app->get('/bajar-caso', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) return $response->withStatus(403);
    $pdo = getDB();
    $pdo->prepare("UPDATE abogados SET descripcion_caso = NULL, especialidad = NULL WHERE id = ?")
        ->execute([$_SESSION['user_id']]);
    return $response->withHeader('Location', '/completar-datos')->withStatus(302);
});
// 2. LOGIN (Google)
$app->get('/login-google', function (Request $request, Response $response) {
    $role = $request->getQueryParams()['role'] ?? 'abogado'; 
    $client = getGoogleClient();
    $client->setState($role); 
    return $response->withHeader('Location', $client->createAuthUrl())->withStatus(302);
});

// 3. CALLBACK
$app->get('/auth/google/callback', function (Request $request, Response $response) {
    $code = $request->getQueryParams()['code'] ?? null;
    if (!$code) return $response->withHeader('Location', '/')->withStatus(302);

    try {
        $client = getGoogleClient();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        $client->setAccessToken($token);
        $googleUser = (new Google\Service\Oauth2($client))->userinfo->get();
        
        $email = filter_var($googleUser->email, FILTER_SANITIZE_EMAIL);
        $nombre = strip_tags($googleUser->name);
        $picture = filter_var($googleUser->picture, FILTER_SANITIZE_URL);
        $googleId = $googleUser->id;
        $intentRole = $request->getQueryParams()['state'] ?? null; 

        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM abogados WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (empty($user['whatsapp']) && $intentRole && $intentRole !== $user['rol']) {
                $pdo->prepare("UPDATE abogados SET rol = ? WHERE id = ?")->execute([$intentRole, $user['id']]);
                $user['rol'] = $intentRole;
            }
            $pdo->prepare("UPDATE abogados SET google_id = ?, google_picture = ? WHERE id = ?")->execute([$googleId, $picture, $user['id']]);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['rol'] = $user['rol'];
            
            if (!empty($user['whatsapp'])) {
                if ($user['rol'] == 'cliente') return $response->withHeader('Location', '/bienvenida')->withStatus(302);
                return $response->withHeader('Location', '/dashboard')->withStatus(302);
            }
        } else {
            $finalRole = $intentRole ?? 'abogado';
            $slug = createSlug($nombre);
            $baseSlug = $slug; $i = 1; 
            while ($pdo->query("SELECT count(*) FROM abogados WHERE slug = '$slug'")->fetchColumn() > 0) {
                $slug = $baseSlug . '-' . $i; $i++;
            }
            $sql = "INSERT INTO abogados (nombre, email, slug, google_id, google_picture, activo, rol, created_at) VALUES (?, ?, ?, ?, ?, 1, ?, NOW())";
            $pdo->prepare($sql)->execute([$nombre, $email, $slug, $googleId, $picture, $finalRole]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['rol'] = $finalRole;
        }
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    } catch (Exception $e) {
        $response->getBody()->write("Error Login: " . $e->getMessage());
        return $response->withStatus(500);
    }
});

// 4. PANEL DE CONTROL / ONBOARDING (Ruta Maestra)
$app->get('/completar-datos', function (Request $request, Response $response) use ($renderer) {
    if (!isset($_SESSION['user_id'])) return $response->withHeader('Location', '/')->withStatus(302);
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Lógica específica para CLIENTES (Dashboard Unificado)
    if ($user['rol'] == 'cliente') {
        // Buscamos si hay abogados que ya revelaron su contacto
        $sqlInteresados = "SELECT a.nombre, a.google_picture, a.especialidad, a.whatsapp, a.slug 
                           FROM contactos_revelados cr 
                           JOIN abogados a ON cr.abogado_id = a.id 
                           WHERE cr.cliente_id = ? 
                           ORDER BY cr.fecha_revelado DESC";
        $stmtInt = $pdo->prepare($sqlInteresados);
        $stmtInt->execute([$user['id']]);
        $interesados = $stmtInt->fetchAll();

        // Enviamos todo al mismo archivo
        return $renderer->render($response, 'onboarding_cliente.php', [
            'user' => $user, 
            'interesados' => $interesados,
            'error' => $_SESSION['error'] ?? null
        ]);
    }
    
    // Si es ABOGADO, sigue su flujo normal
    return $renderer->render($response, 'onboarding_abogado.php', ['user' => $user]);
});

// 5. ACCIÓN: BAJAR CASO (Finalizar publicación)
$app->get('/bajar-caso', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) return $response->withStatus(403);
    
    $pdo = getDB();
    // Limpiamos la descripción y la materia para que deje de ser visible en el Marketplace
    $pdo->prepare("UPDATE abogados SET descripcion_caso = NULL, especialidad = NULL WHERE id = ?")
        ->execute([$_SESSION['user_id']]);
    
    return $response->withHeader('Location', '/completar-datos')->withStatus(302);
});
// 6. GUARDAR CLIENTE
$app->post('/guardar-cliente', function (Request $request, Response $response) {
    if (!isset($_SESSION['user_id'])) return $response->withStatus(403);
    $data = $request->getParsedBody();
    $pdo = getDB();

    $whatsapp = validarWhatsApp($data['whatsapp']);
    if (!$whatsapp) {
        $_SESSION['error'] = '⚠️ WhatsApp inválido.';
        return $response->withHeader('Location', '/completar-datos')->withStatus(302);
    }

    $pdo->prepare("UPDATE abogados SET whatsapp = ?, especialidad = ?, descripcion_caso = ?, created_at = NOW() WHERE id = ?")
        ->execute([$whatsapp, $data['especialidad'], $data['descripcion'], $_SESSION['user_id']]);

    return $response->withHeader('Location', '/bienvenida')->withStatus(302);
});

// 7. DASHBOARD ABOGADO
$app->get('/dashboard', function (Request $request, Response $response) use ($renderer) {
    if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'abogado') return $response->withHeader('Location', '/')->withStatus(302);
    $pdo = getDB();
    $idAbogado = $_SESSION['user_id'];
    
    $sql = "SELECT a.id, a.nombre, a.whatsapp, a.email, a.especialidad, a.descripcion_caso, a.created_at,
            (SELECT COUNT(*) FROM contactos_revelados WHERE cliente_id = a.id) as cupos_usados,
            (SELECT COUNT(*) FROM contactos_revelados WHERE cliente_id = a.id AND abogado_id = ?) as revelado_por_mi
            FROM abogados a
            WHERE a.rol = 'cliente' AND a.descripcion_caso IS NOT NULL 
            AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAbogado, $idAbogado]);
    
    $sqlMisCasos = "SELECT a.id, a.nombre, a.whatsapp, a.email, a.especialidad, cr.estado FROM contactos_revelados cr JOIN abogados a ON cr.cliente_id = a.id WHERE cr.abogado_id = ? ORDER BY cr.fecha_revelado DESC";
    $stmtCRM = $pdo->prepare($sqlMisCasos);
    $stmtCRM->execute([$idAbogado]);
    
    return $renderer->render($response, 'dashboard.php', ['casos' => $stmt->fetchAll(), 'mis_casos' => $stmtCRM->fetchAll()]);
});

// 8. BIENVENIDA CLIENTE
$app->get('/bienvenida', function (Request $request, Response $response) use ($renderer) {
    if (!isset($_SESSION['user_id'])) return $response->withHeader('Location', '/')->withStatus(302);
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cliente = $stmt->fetch();

    $sql = "SELECT a.nombre, a.slug, a.google_picture, a.especialidad, a.whatsapp FROM contactos_revelados cr JOIN abogados a ON cr.abogado_id = a.id WHERE cr.cliente_id = ?";
    $stmtInt = $pdo->prepare($sql);
    $stmtInt->execute([$_SESSION['user_id']]);
    
    return $renderer->render($response, 'bienvenida.php', ['cliente' => $cliente, 'interesados' => $stmtInt->fetchAll()]);
});

$app->get('/logout', function (Request $request, Response $response) {
    session_destroy();
    return $response->withHeader('Location', '/')->withStatus(302);
});

// 9. PERFIL PÚBLICO (Fallback)
$app->get('/{slug}', function (Request $request, Response $response, $args) use ($renderer) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM abogados WHERE slug = ? AND rol = 'abogado'");
    $stmt->execute([$args['slug']]);
    $abogado = $stmt->fetch();
    if (!$abogado) { $response->getBody()->write("<h1>404 - Perfil no encontrado</h1>"); return $response->withStatus(404); }
    return $renderer->render($response, 'profile.php', ['abogado' => $abogado]);
});

$app->run();
