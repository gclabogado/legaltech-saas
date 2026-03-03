<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\LeadService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;

class AdminController
{
    private PhpRenderer $renderer;
    private LeadService $leadService;

    public function __construct(PhpRenderer $renderer, LeadService $leadService)
    {
        $this->renderer = $renderer;
        $this->leadService = $leadService;
    }

    public function showDashboard(Request $request, Response $response): Response
    {
        if (!isAdminSessionAuthenticated()) {
            return $response->withHeader('Location', '/admin-login')->withStatus(302);
        }

        $pdo = $this->leadService->getPdo();
        $leadStage = trim((string)($request->getQueryParams()['lead_stage'] ?? ''));
        if (!in_array($leadStage, ['', 'activo', 'papelera'], true)) {
            $leadStage = '';
        }
        $leadFilter = trim((string)($request->getQueryParams()['lead_filter'] ?? ''));
        if (!in_array($leadFilter, ['', 'unseen'], true)) {
            $leadFilter = '';
        }
        $reviewFilter = trim((string)($request->getQueryParams()['review'] ?? ''));
        if (!in_array($reviewFilter, ['', 'ready', 'incomplete', 'rut_pending', 'published'], true)) {
            $reviewFilter = '';
        }

        $leadCounts = $this->leadService->fetchLeadCounts();
        $leads = $this->leadService->listLeads($leadStage, $leadFilter === 'unseen');

        $stats = [
            'cuentas_total' => (int)$pdo->query("SELECT COUNT(*) FROM abogados")->fetchColumn(),
            'abogados_aprobados' => (int)$pdo->query("SELECT COUNT(*) FROM abogados WHERE COALESCE(abogado_verificado,0)=1")->fetchColumn(),
            'postulaciones_pendientes' => (int)$pdo->query("SELECT COUNT(*) FROM abogados WHERE COALESCE(solicito_habilitacion_abogado,0)=1 AND COALESCE(abogado_verificado,0)=0")->fetchColumn(),
            'leads_total' => (int)$pdo->query("SELECT COUNT(*) FROM contactos_revelados")->fetchColumn(),
        ];

        $analytics = analyticsAdminSnapshot($pdo, 30);
        $postulaciones = $pdo->query("SELECT * FROM abogados WHERE COALESCE(solicito_habilitacion_abogado,0)=1 AND COALESCE(rol,'cliente') <> 'abogado' ORDER BY COALESCE(fecha_solicitud_habilitacion_abogado, created_at) DESC LIMIT 100")->fetchAll() ?: [];
        $abogadosPromovidos = $pdo->query("SELECT * FROM abogados WHERE rol='abogado' ORDER BY id DESC LIMIT 100")->fetchAll() ?: [];
        $cuentas = $pdo->query("SELECT * FROM abogados ORDER BY id DESC LIMIT 50")->fetchAll() ?: [];

        if ($reviewFilter !== '') {
            if (in_array($reviewFilter, ['ready', 'incomplete'], true)) {
                $postulaciones = array_values(array_filter($postulaciones, function ($p) use ($reviewFilter) {
                    $pct = lawyerProfileCompletionPercent((array)$p);
                    return $reviewFilter === 'ready' ? ($pct >= 80) : ($pct < 80);
                }));
            }
            if ($reviewFilter === 'rut_pending') {
                $abogadosPromovidos = array_values(array_filter($abogadosPromovidos, function ($a) {
                    return trim((string)($a['rut_validacion_manual'] ?? '')) === '';
                }));
                $cuentas = array_values(array_filter($cuentas, function ($c) {
                    return (($c['rol'] ?? '') === 'abogado') && trim((string)($c['rut_validacion_manual'] ?? '')) === '';
                }));
            }
            if ($reviewFilter === 'published') {
                $abogadosPromovidos = array_values(array_filter($abogadosPromovidos, function ($a) {
                    return (int)($a['activo'] ?? 0) === 1;
                }));
                $cuentas = array_values(array_filter($cuentas, function ($c) {
                    return (($c['rol'] ?? '') === 'abogado') && (int)($c['activo'] ?? 0) === 1;
                }));
            }
        }

        $adminUser = [];
        if (!empty($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT * FROM abogados WHERE id = ? LIMIT 1");
            $stmt->execute([(int)$_SESSION['user_id']]);
            $adminUser = $stmt->fetch() ?: [];
        }

        return $this->renderer->render($response, 'admin.php', [
            'admin_user' => $adminUser,
            'stats' => $stats,
            'postulaciones' => $postulaciones,
            'cuentas' => $cuentas,
            'abogados_promovidos' => $abogadosPromovidos,
            'leads' => $leads,
            'lead_stage' => $leadStage,
            'lead_filter' => $leadFilter,
            'lead_counts' => $leadCounts,
            'review_filter' => $reviewFilter,
            'demo_mode_active' => isDemoModeActive(),
            'demo_mode_has_snapshot' => is_file(demoModeSnapshotPath()),
            'analytics' => $analytics,
            'csrf_token' => ensureCsrfToken(),
        ]);
    }
}
