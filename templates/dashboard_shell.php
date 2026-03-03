<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Pro | Tu Estudio Juridico</title>
    <link rel="stylesheet" href="/dashboard-page.css?v=2026030231">
    <link rel="stylesheet" href="/app-shell.css?v=2026022705">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<?php
    $themeEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
    $dashIsAdmin = ($themeEmail === 'gmcalderonlewin@gmail.com');
    $humanLeadAgo = static function ($dt): ?string {
        $raw = trim((string)$dt);
        if ($raw === '') return null;
        $ts = strtotime($raw);
        if (!$ts) return null;
        $diff = time() - $ts;
        if ($diff < 0) $diff = 0;
        if ($diff < 60) return 'hace menos de 1 min';
        if ($diff < 3600) {
            $m = (int)floor($diff / 60);
            return 'hace ' . $m . ' min';
        }
        if ($diff < 86400) {
            $h = (int)floor($diff / 3600);
            return 'hace ' . $h . ' h';
        }
        $d = (int)floor($diff / 86400);
        return 'hace ' . $d . ' día' . ($d === 1 ? '' : 's');
    };
    $jsonAttr = static function ($data): string {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return htmlspecialchars((string)($json === false ? '{}' : $json), ENT_QUOTES, 'UTF-8');
    };
    $crmBuckets = [
        'PENDIENTE' => [],
        'REABIERTO' => [],
        'CONTACTADO' => [],
        'GANADO' => [],
        'PERDIDO' => [],
        'CANCELADO' => [],
    ];
    $crmBucketTitles = [
        'PENDIENTE' => 'No contactado',
        'REABIERTO' => 'Reabierto',
        'CONTACTADO' => 'Contactado',
        'GANADO' => 'Clientes contratados',
        'PERDIDO' => 'No cerró',
        'CANCELADO' => 'Archivado',
    ];
    if (!empty($mis_casos)) {
        foreach ($mis_casos as $tmpLead) {
            $k = strtoupper(trim((string)($tmpLead['estado'] ?? 'PENDIENTE')));
            if ($k === 'CONTACTADO' && !empty($tmpLead['reabierto_at'])) {
                $k = 'REABIERTO';
            }
            if (!isset($crmBuckets[$k])) $k = 'PENDIENTE';
            $crmBuckets[$k][] = $tmpLead;
        }
    }
    $crmCounts = [];
    foreach ($crmBuckets as $k => $arr) $crmCounts[$k] = count($arr);
    $crmStateTagClass = [
        'PENDIENTE' => 'status-pendiente',
        'REABIERTO' => 'status-contactado',
        'CONTACTADO' => 'status-contactado',
        'GANADO' => 'status-ganado',
        'PERDIDO' => 'status-perdido',
        'CANCELADO' => 'status-cancelado',
    ];
    $resolveDashboardView = static function ($rawTab): array {
        $tab = strtolower(trim((string)$rawTab));
        if (in_array($tab, ['home', 'inicio'], true)) {
            return ['main' => 'home', 'sub' => 'home'];
        }
        if (in_array($tab, ['marketplace', 'crm', 'inbox', 'leads'], true)) {
            return ['main' => 'inbox', 'sub' => 'leads'];
        }
        if (in_array($tab, ['quote', 'builder', 'cotizador'], true)) {
            return ['main' => 'business', 'sub' => 'builder'];
        }
        if (in_array($tab, ['quotes', 'cotizaciones'], true)) {
            return ['main' => 'inbox', 'sub' => 'quotes'];
        }
        if (in_array($tab, ['services', 'catalog', 'catalogo', 'business', 'negocio'], true)) {
            return ['main' => 'business', 'sub' => 'catalog'];
        }
        if (in_array($tab, ['branding', 'brand', 'marca'], true)) {
            return ['main' => 'business', 'sub' => 'branding'];
        }
        if (in_array($tab, ['performance', 'rendimiento'], true)) {
            return ['main' => 'business', 'sub' => 'performance'];
        }
        if (in_array($tab, ['subscription', 'suscripcion', 'cuenta-plan'], true)) {
            return ['main' => 'perfil', 'sub' => 'subscription'];
        }
        if (in_array($tab, ['team', 'equipo', 'cuenta-team'], true)) {
            return ['main' => 'perfil', 'sub' => 'team'];
        }
        if (in_array($tab, ['perfil', 'cuenta', 'cuenta-perfil'], true)) {
            return ['main' => 'perfil', 'sub' => 'perfil'];
        }
        return ['main' => 'home', 'sub' => 'home'];
    };
    $dashView = $resolveDashboardView($initial_tab ?? 'crm');
    $dashMain = $dashView['main'];
    $dashSub = $dashView['sub'];
    $dashboardViewMap = [
        'home' => 'dashboard_views/home.php',
        'leads' => 'dashboard_views/leads.php',
        'quotes' => 'dashboard_views/quotes.php',
        'builder' => 'dashboard_views/builder.php',
        'catalog' => 'dashboard_views/catalog.php',
        'branding' => 'dashboard_views/branding.php',
        'performance' => 'dashboard_views/performance.php',
        'subscription' => 'dashboard_views/subscription.php',
        'team' => 'dashboard_views/team.php',
        'perfil' => 'dashboard_views/perfil.php',
    ];
    $dashboardViewTemplate = $dashboardViewMap[$dashSub] ?? 'dashboard_views/leads.php';
    $lawyerDisplayName = trim((string)($user['nombre'] ?? $user['name'] ?? $user['email'] ?? 'Abogado'));
    if ($lawyerDisplayName === '') $lawyerDisplayName = 'Abogado';
    $workspaceLabel = !empty($workspace_context['team_name'])
        ? trim((string)$workspace_context['team_name'])
        : 'Workspace individual';
    if ($workspaceLabel === '') $workspaceLabel = 'Workspace individual';
    $topbarMeta = [];
    $topbarMeta[] = !empty($workspace_context['team_id']) ? 'Team activo' : 'Modo individual';
    if (!empty($dashboard_metrics['pending_leads'])) {
        $topbarMeta[] = (int)$dashboard_metrics['pending_leads'] . ' leads pendientes';
    }
    if (!empty($dashboard_metrics['quotes_pending'])) {
        $topbarMeta[] = (int)$dashboard_metrics['quotes_pending'] . ' cotizaciones por mover';
    }
    $topbarMetaLabel = implode(' · ', array_slice($topbarMeta, 0, 2));
    $dashboardHeaderMap = [
        'home' => ['title' => 'Centro de comando', 'subtitle' => 'Prioriza el día, detecta riesgo y mueve el despacho sin perder foco.'],
        'leads' => ['title' => 'Bandeja de leads', 'subtitle' => 'Captura, seguimiento y cierre en una bandeja tipo inbox.'],
        'quotes' => ['title' => 'Bandeja de cotizaciones', 'subtitle' => 'Gestiona propuestas, seguimiento comercial y decisiones del cliente.'],
        'builder' => ['title' => 'Cotizador', 'subtitle' => 'Compone propuestas en pasos y sal con texto listo para enviar.'],
        'catalog' => ['title' => 'Catálogo', 'subtitle' => 'Ordena tus servicios, precios base y plantillas comerciales.'],
        'branding' => ['title' => 'Marca del estudio', 'subtitle' => 'Controla firma comercial, PDF y datos visibles al cliente desde el workspace.'],
        'performance' => ['title' => 'Rendimiento', 'subtitle' => 'Mide pipeline, cierres, actividad y salud comercial.'],
        'subscription' => ['title' => 'Plan', 'subtitle' => 'Estado del plan, beneficios y próximos pasos comerciales.'],
        'team' => ['title' => 'Team jurídico', 'subtitle' => 'Crea equipo, invita por email y prepara el workspace compartido del estudio.'],
        'perfil' => ['title' => 'Cuenta', 'subtitle' => 'Estado del perfil, plan y workspace profesional.'],
    ];
    $dashboardHeader = $dashboardHeaderMap[$dashSub] ?? $dashboardHeaderMap['home'];
    $appNavItems = [
        ['href' => '/dashboard/home', 'label' => 'Inicio', 'match' => ['home']],
        ['href' => '/dashboard/leads', 'label' => 'Leads', 'match' => ['leads']],
        ['href' => '/dashboard/cotizaciones', 'label' => 'Cotizaciones', 'match' => ['quotes']],
        ['href' => '/dashboard/cotizador', 'label' => 'Cotizador', 'match' => ['builder']],
        ['href' => '/dashboard/catalogo', 'label' => 'Catálogo', 'match' => ['catalog']],
        ['href' => '/dashboard/marca', 'label' => 'Marca (estudio)', 'match' => ['branding']],
        ['href' => '/dashboard/rendimiento', 'label' => 'Rendimiento', 'match' => ['performance']],
        ['href' => '/dashboard/cuenta', 'label' => 'Cuenta', 'match' => ['perfil', 'subscription', 'team']],
    ];
    $topbarPrimaryAction = null;
    switch ($dashSub) {
        case 'home':
            $topbarPrimaryAction = ['type' => 'link', 'href' => '/dashboard/cotizador', 'label' => 'Nueva cotización'];
            break;
        case 'leads':
            $topbarPrimaryAction = ['type' => 'button', 'label' => 'Nuevo prospecto', 'onclick' => 'abrirModalProspecto()'];
            break;
        case 'quotes':
            $topbarPrimaryAction = ['type' => 'link', 'href' => '/dashboard/cotizador', 'label' => 'Nueva cotización'];
            break;
        case 'builder':
            $topbarPrimaryAction = ['type' => 'link', 'href' => '/dashboard/cotizaciones', 'label' => 'Ver cotizaciones'];
            break;
        case 'catalog':
            $topbarPrimaryAction = ['type' => 'button', 'label' => 'Nuevo servicio', 'onclick' => "dashboardResetServiceForm(); window.scrollTo({ top: 0, behavior: 'smooth' });"];
            break;
        case 'branding':
            $topbarPrimaryAction = ['type' => 'link', 'href' => '/dashboard/cotizador', 'label' => 'Probar cotizador'];
            break;
        case 'performance':
            $topbarPrimaryAction = ['type' => 'link', 'href' => '/dashboard/leads', 'label' => 'Ver leads'];
            break;
        case 'subscription':
            $topbarPrimaryAction = ['type' => 'link', 'href' => (string)($subscription_state['cta_href'] ?? 'mailto:contacto@example.com'), 'label' => (string)($subscription_state['cta_label'] ?? 'Hablar con ventas')];
            break;
        case 'team':
            $topbarPrimaryAction = !empty($team_state['team'])
                ? ['type' => 'link', 'href' => '#teamInviteForm', 'label' => 'Invitar miembro']
                : ['type' => 'link', 'href' => '#teamCreateForm', 'label' => 'Crear team'];
            break;
        case 'perfil':
            $topbarPrimaryAction = ['type' => 'link', 'href' => '/completar-datos?modo=abogado', 'label' => 'Editar perfil'];
            break;
    }
    $mobileMoreActive = in_array($dashSub, ['catalog', 'branding', 'performance', 'subscription', 'team', 'perfil'], true);
?>
<body class="theme-black<?= $dashIsAdmin ? ' theme-admin' : ' theme-olive' ?>">
<?php if (isset($mensaje) && $mensaje !== null): ?>
    <div class="dash-message" data-autodismiss-ms="4000"><?= htmlspecialchars((string)$mensaje) ?></div>
<?php endif; ?>

<div class="dashboard-app-shell" id="dashboardRoot">
    <aside class="dashboard-sidebar">
        <a class="dashboard-brand" href="/dashboard/home">
            <span class="dashboard-brand-mark">L</span>
            <div>
                <strong>Tu Estudio Juridico Pro</strong>
                <span>Workspace profesional</span>
            </div>
        </a>

        <section class="dashboard-workspace-card">
            <div class="dashboard-workspace-row">
                <span class="dashboard-workspace-badge">Workspace</span>
                <?php if (!empty($subscription_state)): ?>
                    <span class="dashboard-workspace-plan tone-<?= htmlspecialchars((string)($subscription_state['tone'] ?? 'founder')) ?>">
                        <?= htmlspecialchars((string)($subscription_state['badge'] ?? 'Plan')) ?>
                    </span>
                <?php endif; ?>
            </div>
            <strong><?= htmlspecialchars($workspaceLabel) ?></strong>
            <p>
                <?= !empty($workspace_context['team_id'])
                    ? 'Operación compartida con catálogo, cotizaciones y leads coordinados.'
                    : 'Tu operación diaria concentrada en un panel único y más rápido de mover.' ?>
            </p>
            <div class="dashboard-workspace-stats">
                <div>
                    <span>Leads</span>
                    <strong><?= (int)($crmCounts['PENDIENTE'] ?? 0) ?></strong>
                </div>
                <div>
                    <span>Cotizaciones</span>
                    <strong><?= (int)($dashboard_metrics['quotes_pending'] ?? 0) ?></strong>
                </div>
            </div>
        </section>

        <div class="dashboard-sidebar-section">
            <div class="dashboard-sidebar-label">Workspace</div>
            <nav class="dashboard-sidebar-nav">
                <?php foreach ($appNavItems as $navItem): ?>
                    <?php $isActiveNav = in_array($dashSub, $navItem['match'], true); ?>
                    <a
                        href="<?= htmlspecialchars($navItem['href']) ?>"
                        class="dashboard-nav-link<?= $isActiveNav ? ' active' : '' ?>"
                        <?php if ($navItem['label'] === 'Inicio'): ?>id="tab-home"<?php endif; ?>
                        <?php if ($navItem['label'] === 'Leads' || $navItem['label'] === 'Cotizaciones'): ?>data-main="inbox"<?php endif; ?>
                        <?php if ($navItem['label'] === 'Leads'): ?>id="tab-inbox"<?php endif; ?>
                        <?php if (in_array($navItem['label'], ['Cotizador', 'Catálogo', 'Marca (estudio)', 'Rendimiento'], true)): ?>data-main="business"<?php endif; ?>
                        <?php if ($navItem['label'] === 'Cotizador'): ?>id="tab-business"<?php endif; ?>
                        <?php if ($navItem['label'] === 'Cuenta'): ?>data-main="perfil" id="tab-perfil"<?php endif; ?>>
                        <span><?= htmlspecialchars($navItem['label']) ?></span>
                        <?php if ($navItem['label'] === 'Leads' && (int)($crmCounts['PENDIENTE'] ?? 0) > 0): ?>
                            <small><?= (int)($crmCounts['PENDIENTE'] ?? 0) ?></small>
                        <?php elseif ($navItem['label'] === 'Cotizaciones' && !empty($dashboard_metrics['quotes_pending'])): ?>
                            <small><?= (int)($dashboard_metrics['quotes_pending'] ?? 0) ?></small>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div class="dashboard-sidebar-section dashboard-sidebar-meta">
            <div class="dashboard-sidebar-label">Accesos rápidos</div>
            <a class="dashboard-quick-link" href="/explorar">Explorar abogados</a>
            <a class="dashboard-quick-link" href="/mi-tarjeta">Ver mi ficha pública</a>
            <a class="dashboard-quick-link" href="/completar-datos?modo=abogado">Editar perfil profesional</a>
        </div>

        <div class="dashboard-sidebar-footer">
            <strong><?= htmlspecialchars($lawyerDisplayName) ?></strong>
            <span><?= htmlspecialchars((string)($_SESSION['email'] ?? '')) ?></span>
            <?php if (!empty($subscription_state)): ?>
                <a class="dashboard-plan-pill tone-<?= htmlspecialchars((string)($subscription_state['tone'] ?? 'founder')) ?>" href="/dashboard/cuenta/plan">
                    <?= htmlspecialchars((string)($subscription_state['badge'] ?? 'Plan')) ?> · <?= htmlspecialchars((string)($subscription_state['status_label'] ?? 'Activa')) ?>
                </a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="/logout">Salir</a>
        </div>
    </aside>

    <div class="dashboard-main-shell">
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-main">
                <div class="dashboard-topbar-kicker">Panel profesional</div>
                <h1><?= htmlspecialchars((string)$dashboardHeader['title']) ?></h1>
                <p><?= htmlspecialchars((string)$dashboardHeader['subtitle']) ?></p>
                <div class="dashboard-topbar-meta">
                    <span class="dashboard-topbar-chip"><?= htmlspecialchars($workspaceLabel) ?></span>
                    <?php if ($topbarMetaLabel !== ''): ?>
                        <span class="dashboard-topbar-chip is-soft"><?= htmlspecialchars($topbarMetaLabel) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="dashboard-topbar-actions">
                <?php if (!empty($subscription_state)): ?>
                    <a class="dashboard-topbar-plan tone-<?= htmlspecialchars((string)($subscription_state['tone'] ?? 'founder')) ?>" href="/dashboard/cuenta/plan">
                        <span><?= htmlspecialchars((string)($subscription_state['plan_label'] ?? 'Plan')) ?></span>
                        <small><?= htmlspecialchars((string)($subscription_state['renewal_label'] ?? '')) ?></small>
                    </a>
                <?php endif; ?>
                <?php if (!empty($topbarPrimaryAction)): ?>
                    <?php if (($topbarPrimaryAction['type'] ?? 'link') === 'button'): ?>
                        <button class="btn btn-primary" type="button" onclick="<?= htmlspecialchars((string)($topbarPrimaryAction['onclick'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($topbarPrimaryAction['label'] ?? 'Abrir')) ?></button>
                    <?php else: ?>
                        <a class="btn btn-primary" href="<?= htmlspecialchars((string)($topbarPrimaryAction['href'] ?? '/dashboard')) ?>"><?= htmlspecialchars((string)($topbarPrimaryAction['label'] ?? 'Abrir')) ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </header>

        <nav class="dashboard-mobile-nav">
            <a href="/dashboard/home" class="dashboard-mobile-link<?= $dashSub === 'home' ? ' active' : '' ?>">Inicio</a>
            <a href="/dashboard/leads" class="dashboard-mobile-link<?= $dashSub === 'leads' ? ' active' : '' ?>">Leads</a>
            <a href="/dashboard/cotizaciones" class="dashboard-mobile-link<?= $dashSub === 'quotes' ? ' active' : '' ?>">Cotizaciones</a>
            <a href="/dashboard/cotizador" class="dashboard-mobile-link<?= $dashSub === 'builder' ? ' active' : '' ?>">Cotizador</a>
            <details class="dashboard-mobile-more<?= $mobileMoreActive ? ' is-active' : '' ?>"<?= $mobileMoreActive ? ' open' : '' ?>>
                <summary class="dashboard-mobile-link">Más</summary>
                <div class="dashboard-mobile-more-menu">
                    <a href="/dashboard/catalogo">Catálogo</a>
                    <a href="/dashboard/marca">Marca (estudio)</a>
                    <a href="/dashboard/rendimiento">Rendimiento</a>
                    <a href="/dashboard/cuenta">Cuenta</a>
                    <a href="/explorar">Explorar abogados</a>
                </div>
            </details>
        </nav>

        <main class="dashboard-content">
            <?php require __DIR__ . '/' . $dashboardViewTemplate; ?>
        </main>
    </div>
</div>

<div
    id="dashboardPageConfig"
    data-initial-tab="<?= htmlspecialchars((string)($initial_tab ?? 'home')) ?>"
    data-pending-lead-count="<?= (int)($crmCounts['PENDIENTE'] ?? 0) ?>">
</div>
<script src="/dashboard-page.js?v=2026030230"></script>
</body>
</html>
