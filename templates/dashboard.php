<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Pro | Tu Estudio Juridico</title>
    <link rel="stylesheet" href="/dashboard-page.css?v=2026030203">
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
?>
    <body class="theme-black<?= $dashIsAdmin ? ' theme-admin' : ' theme-olive' ?>">
    <?php if(isset($mensaje)): ?>
        <div class="dash-message" data-autodismiss-ms="4000"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <nav class="nav">
        <div class="nav-inner">
                <a class="brand" href="/explorar">Tu Estudio Juridico</a>
                <div class="nav-links">
                    <a class="active" href="/dashboard">Panel profesional</a>
                    <a href="/explorar">Explorar</a>
                    <a href="/completar-datos?modo=abogado">Mi perfil</a>
                </div>
            <div class="nav-actions">
                <a class="btn btn-ghost" href="/logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="wrap" id="dashboardRoot">
        <?php
            $nextCase = null;
            $crmBuckets = [
                'PENDIENTE' => [],
                'CONTACTADO' => [],
                'GANADO' => [],
                'PERDIDO' => [],
                'CANCELADO' => [],
            ];
            $crmBucketTitles = [
                'PENDIENTE' => 'No contactado',
                'CONTACTADO' => 'Contactado',
                'GANADO' => 'Clientes contratados',
                'PERDIDO' => 'No cerró',
                'CANCELADO' => 'Archivado',
            ];
            if (!empty($casos)) {
                foreach ($casos as $tmpCase) {
                    if (empty($tmpCase['revelado_por_mi'])) {
                        $nextCase = $tmpCase;
                        break;
                    }
                }
                if ($nextCase === null) {
                    $nextCase = $casos[0];
                }
            }
            if (!empty($mis_casos)) {
                foreach ($mis_casos as $tmpLead) {
                    $k = strtoupper(trim((string)($tmpLead['estado'] ?? 'PENDIENTE')));
                    if (!isset($crmBuckets[$k])) $k = 'PENDIENTE';
                    $crmBuckets[$k][] = $tmpLead;
                }
            }
            $crmCounts = [];
            foreach ($crmBuckets as $k => $arr) $crmCounts[$k] = count($arr);
            $crmStateTagClass = [
                'PENDIENTE' => 'status-pendiente',
                'CONTACTADO' => 'status-contactado',
                'GANADO' => 'status-ganado',
                'PERDIDO' => 'status-perdido',
                'CANCELADO' => 'status-cancelado',
            ];
            $resolveDashboardView = static function ($rawTab): array {
                $tab = strtolower(trim((string)$rawTab));
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
                if ($tab === 'perfil') {
                    return ['main' => 'perfil', 'sub' => 'perfil'];
                }
                return ['main' => 'inbox', 'sub' => 'leads'];
            };
            $dashView = $resolveDashboardView($initial_tab ?? 'crm');
            $dashMain = $dashView['main'];
            $dashSub = $dashView['sub'];
        ?>
        <section class="dash-hud">
            <div class="dash-hud-head">
                <div>
                    <h1 class="dash-hud-title">Panel profesional</h1>
                    <div class="dash-hud-sub">Gestiona leads, seguimiento y cierre desde un solo lugar</div>
                </div>
                <div class="dash-hud-actions">
                    <a class="btn btn-ghost" href="/mi-tarjeta">Ver mi ficha</a>
                    <button class="btn btn-ghost" type="button" onclick="copyLink(window.location.origin + '/<?= htmlspecialchars((string)($user['slug'] ?? '')) ?>')">Copiar link</button>
                    <a class="btn btn-primary" href="/completar-datos?modo=abogado">Editar perfil profesional</a>
                </div>
                </div>
                <div class="dash-tabs app-tabs" style="margin-top:0;">
                <a href="/dashboard/leads" id="tab-inbox" class="dash-tab app-tab<?= $dashMain === 'inbox' ? ' active' : '' ?>">Bandejas</a>
                <a href="/dashboard/negocio" id="tab-business" class="dash-tab app-tab<?= $dashMain === 'business' ? ' active' : '' ?>">Negocio</a>
                <a href="/dashboard/perfil" id="tab-perfil" class="dash-tab app-tab<?= $dashMain === 'perfil' ? ' active' : '' ?>">Mi perfil</a>
            </div>
        </section>

        <section id="view-performance" class="grid" style="margin-top: 16px; display: <?= $dashSub === 'performance' ? 'grid' : 'none' ?>;">
            <div class="dash-subtabs">
                <a href="/dashboard/catalogo" id="subtab-catalog-performance" class="dash-subtab<?= $dashSub === 'catalog' ? ' active' : '' ?>">Catálogo</a>
                <a href="/dashboard/cotizador" id="subtab-builder-performance" class="dash-subtab<?= $dashSub === 'builder' ? ' active' : '' ?>">Cotizador</a>
                <a href="/dashboard/marca" id="subtab-branding-performance" class="dash-subtab<?= $dashSub === 'branding' ? ' active' : '' ?>">Marca</a>
                <a href="/dashboard/rendimiento" id="subtab-performance" class="dash-subtab<?= $dashSub === 'performance' ? ' active' : '' ?>">Rendimiento</a>
            </div>
            <div class="crm-breadcrumb">Negocio<span class="sep">/</span>Rendimiento</div>
            <div class="dash-card">
            <h2 class="section-title">Resumen</h2>
            <p class="muted">Estado actual de tus leads.</p>
            <div class="dash-stats mt-10">
                <div class="dash-stat"><strong><?= (int)($stats['total'] ?? 0) ?></strong>Total casos</div>
                <div class="dash-stat"><strong><?= (int)($stats['pendientes'] ?? 0) ?></strong>Pendientes</div>
                <div class="dash-stat"><strong><?= (int)($stats['ganados'] ?? 0) ?></strong>Ganados</div>
                <div class="dash-stat"><strong>$<?= number_format((float)($stats['presupuesto_total'] ?? 0), 0, ',', '.') ?></strong>Presupuesto</div>
                <div class="dash-stat"><strong><?= (int)($user['vistas_abogados'] ?? 0) ?></strong>Vistas de abogados</div>
            </div>
            <div class="mini-chip-row mt-10">
                <span class="mini-chip app-badge badge-info">No contactado: <?= (int)($crmCounts['PENDIENTE'] ?? 0) ?></span>
                <span class="mini-chip app-badge badge-accent">Contactado: <?= (int)($crmCounts['CONTACTADO'] ?? 0) ?></span>
                <span class="mini-chip app-badge badge-success">Cerró: <?= (int)($crmCounts['GANADO'] ?? 0) ?></span>
                <span class="mini-chip app-badge badge-warn">No cerró: <?= (int)($crmCounts['PERDIDO'] ?? 0) ?></span>
                <span class="mini-chip app-badge">Archivado: <?= (int)($crmCounts['CANCELADO'] ?? 0) ?></span>
            </div>

        </section>

        </section>

        <section id="view-services" class="grid" style="margin-top: 16px; display: <?= in_array($dashSub, ['quotes', 'catalog', 'builder', 'branding'], true) ? 'grid' : 'none' ?>;">
            <div class="dash-subtabs" id="servicesInboxSubtabs"<?= $dashSub === 'quotes' ? '' : ' hidden' ?>>
                <a href="/dashboard/leads" id="subtab-leads-services" class="dash-subtab<?= $dashSub === 'leads' ? ' active' : '' ?>">Bandeja de leads</a>
                <a href="/dashboard/cotizaciones" id="subtab-quotes-services" class="dash-subtab<?= $dashSub === 'quotes' ? ' active' : '' ?>">Bandeja de cotizaciones</a>
            </div>
            <div class="dash-subtabs" id="servicesBusinessSubtabs"<?= in_array($dashSub, ['catalog', 'builder', 'branding'], true) ? '' : ' hidden' ?>>
                <a href="/dashboard/catalogo" id="subtab-catalog-services" class="dash-subtab<?= $dashSub === 'catalog' ? ' active' : '' ?>">Catálogo</a>
                <a href="/dashboard/cotizador" id="subtab-builder-services" class="dash-subtab<?= $dashSub === 'builder' ? ' active' : '' ?>">Cotizador</a>
                <a href="/dashboard/marca" id="subtab-branding-services" class="dash-subtab<?= $dashSub === 'branding' ? ' active' : '' ?>">Marca</a>
                <a href="/dashboard/rendimiento" id="subtab-performance-services" class="dash-subtab<?= $dashSub === 'performance' ? ' active' : '' ?>">Rendimiento</a>
            </div>
            <div class="crm-breadcrumb">
                <?php if ($dashSub === 'quotes'): ?>
                    Bandejas<span class="sep">/</span>Cotizaciones
                <?php elseif ($dashSub === 'builder'): ?>
                    Negocio<span class="sep">/</span>Cotizador
                <?php elseif ($dashSub === 'catalog'): ?>
                    Negocio<span class="sep">/</span>Catálogo
                <?php elseif ($dashSub === 'branding'): ?>
                    Negocio<span class="sep">/</span>Marca
                <?php else: ?>
                    Workspace<span class="sep">/</span>Operación diaria
                <?php endif; ?>
            </div>
            <div class="quote-layout">
                <div class="quote-column quote-pane" id="servicesCatalogPane"<?= $dashSub === 'catalog' ? '' : ' hidden' ?>>
                    <div class="dash-card">
                        <div class="quote-section-head">
                            <div>
                                <h2 class="section-title">Catálogo de servicios</h2>
                                <p class="muted">Crea servicios con precio base, gastos y detalle reutilizable para cotizar más rápido.</p>
                            </div>
                            <span class="dash-tag"><?= count((array)($servicios ?? [])) ?> servicio<?= count((array)($servicios ?? [])) === 1 ? '' : 's' ?></span>
                        </div>
                        <form id="serviceForm" action="/dashboard/servicios/guardar" method="POST" class="quote-form-grid">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <input type="hidden" name="service_id" id="service_id" value="">
                            <label class="quote-label">
                                Nombre del servicio
                                <input class="input" type="text" name="nombre_servicio" id="service_nombre" placeholder="Ej: Consulta penal urgente" required>
                            </label>
                            <div class="quote-fields-2">
                                <label class="quote-label">
                                    Materia
                                    <select class="input" name="materia" id="service_materia">
                                        <option value="">Selecciona una materia</option>
                                        <?php foreach (array_keys((array)($materias_taxonomia ?? [])) as $matDash): ?>
                                            <option value="<?= htmlspecialchars((string)$matDash) ?>"><?= htmlspecialchars((string)$matDash) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="quote-label">
                                    Plazo estimado
                                    <input class="input" type="text" name="plazo_estimado" id="service_plazo" placeholder="24 horas, 3 días hábiles">
                                </label>
                            </div>
                            <label class="quote-label">
                                Detalle del servicio
                                <textarea class="input quote-textarea" name="detalle" id="service_detalle" placeholder="Explica qué incluye este servicio y qué recibe el cliente."></textarea>
                            </label>
                            <div class="quote-fields-3">
                                <label class="quote-label">
                                    Honorarios base
                                    <input class="input" type="number" min="0" step="1000" name="precio_base" id="service_precio" value="0">
                                </label>
                                <label class="quote-label">
                                    Gastos base
                                    <input class="input" type="number" min="0" step="1000" name="gastos_base" id="service_gastos" value="0">
                                </label>
                                <label class="quote-label quote-checkbox">
                                    <span>Estado</span>
                                    <span class="quote-checkbox-row">
                                        <input type="checkbox" name="activo" id="service_activo" checked>
                                        Activo para cotizar
                                    </span>
                                </label>
                            </div>
                            <div class="dash-actions-3">
                                <button class="btn btn-primary" type="submit">Guardar servicio</button>
                                <button class="btn btn-ghost" type="button" onclick="dashboardResetServiceForm()">Nuevo servicio</button>
                                <a class="btn btn-ghost" href="/dashboard/catalogo">Abrir esta vista</a>
                            </div>
                        </form>
                    </div>

                    <div class="dash-card">
                        <div class="quote-section-head">
                            <div>
                                <h2 class="section-title">Servicios guardados</h2>
                                <p class="muted">Edita precios, detalle o desactiva servicios antes de cotizar.</p>
                            </div>
                        </div>
                        <?php if (empty($servicios)): ?>
                            <p class="muted">Aún no tienes servicios cargados. Crea el primero arriba y quedará disponible para cotizar.</p>
                        <?php else: ?>
                            <div class="quote-list">
                                <?php foreach ($servicios as $srv): ?>
                                    <article class="quote-item-card">
                                        <div class="quote-item-top">
                                            <div>
                                                <strong><?= htmlspecialchars((string)($srv['nombre'] ?? 'Servicio')) ?></strong>
                                                <div class="muted"><?= htmlspecialchars((string)($srv['materia'] ?? 'Sin materia')) ?> · <?= htmlspecialchars((string)($srv['plazo_estimado'] ?? 'Plazo a definir')) ?></div>
                                            </div>
                                            <span class="dash-tag <?= !empty($srv['activo']) ? 'status-ganado' : 'status-cancelado' ?>"><?= !empty($srv['activo']) ? 'Activo' : 'Inactivo' ?></span>
                                        </div>
                                        <div class="quote-amounts-inline">
                                            <span>Base: <strong><?= formatClpAmount($srv['precio_base'] ?? 0) ?></strong></span>
                                            <span>Gastos: <strong><?= formatClpAmount($srv['gastos_base'] ?? 0) ?></strong></span>
                                        </div>
                                        <?php if (!empty($srv['detalle'])): ?>
                                            <p class="quote-body"><?= nl2br(htmlspecialchars((string)$srv['detalle'])) ?></p>
                                        <?php endif; ?>
                                        <div class="dash-actions-3">
                                            <button
                                                type="button"
                                                class="btn btn-primary"
                                                onclick="dashboardEditService(this)"
                                                data-service="<?= $jsonAttr([
                                                    'id' => (int)($srv['id'] ?? 0),
                                                    'nombre' => (string)($srv['nombre'] ?? ''),
                                                    'materia' => (string)($srv['materia'] ?? ''),
                                                    'detalle' => (string)($srv['detalle'] ?? ''),
                                                    'plazo_estimado' => (string)($srv['plazo_estimado'] ?? ''),
                                                    'precio_base' => (string)($srv['precio_base'] ?? '0'),
                                                    'gastos_base' => (string)($srv['gastos_base'] ?? '0'),
                                                    'activo' => !empty($srv['activo']) ? 1 : 0,
                                                ]) ?>">Editar</button>
                                            <button
                                                type="button"
                                                class="btn btn-ghost"
                                                onclick="dashboardUseServiceForQuote(this)"
                                                data-service="<?= $jsonAttr([
                                                    'id' => (int)($srv['id'] ?? 0),
                                                    'nombre' => (string)($srv['nombre'] ?? ''),
                                                    'materia' => (string)($srv['materia'] ?? ''),
                                                    'detalle' => (string)($srv['detalle'] ?? ''),
                                                    'plazo_estimado' => (string)($srv['plazo_estimado'] ?? ''),
                                                    'precio_base' => (string)($srv['precio_base'] ?? '0'),
                                                    'gastos_base' => (string)($srv['gastos_base'] ?? '0'),
                                                ]) ?>">Usar en cotización</button>
                                            <form method="POST" action="/dashboard/servicios/eliminar" onsubmit="return confirm('¿Eliminar este servicio del catálogo?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                <input type="hidden" name="service_id" value="<?= (int)($srv['id'] ?? 0) ?>">
                                                <button class="btn btn-ghost" type="submit">Eliminar</button>
                                            </form>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="quote-column quote-pane" id="servicesQuoteBuilderPane"<?= $dashSub === 'builder' ? '' : ' hidden' ?>>
                    <div class="dash-card">
                        <div class="quote-section-head">
                            <div>
                                <h2 class="section-title">Generar cotización</h2>
                                <p class="muted">Selecciona un servicio, ajusta montos y deja listo el mensaje para enviar al cliente.</p>
                            </div>
                        </div>
                        <form id="quoteBuilderForm" action="/dashboard/cotizaciones/guardar" method="POST" class="quote-form-grid">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <input type="hidden" name="quote_id" id="quote_id" value="">
                            <div class="quote-stepper" id="quoteStepper">
                                <div class="quote-stepper-item is-active" data-step-indicator="1"><span>1</span><small>Cliente</small></div>
                                <div class="quote-stepper-item" data-step-indicator="2"><span>2</span><small>Propuesta</small></div>
                                <div class="quote-stepper-item" data-step-indicator="3"><span>3</span><small>Montos y cierre</small></div>
                            </div>

                            <section class="quote-step is-active" data-step="1">
                                <div class="quote-step-head">
                                    <h3>Paso 1: cliente y servicio</h3>
                                    <p class="muted">Primero selecciona el lead y el servicio base para arrancar la cotización.</p>
                                </div>
                                <div class="quote-fields-2">
                                    <label class="quote-label">
                                        Cliente del CRM
                                        <select class="input" name="cliente_id" id="quote_cliente_id">
                                            <option value="">Selecciona un lead existente</option>
                                            <?php foreach (($clientes_para_cotizar ?? []) as $cliDash): ?>
                                                <option
                                                    value="<?= (int)($cliDash['id'] ?? 0) ?>"
                                                    data-name="<?= htmlspecialchars((string)($cliDash['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-whatsapp="<?= htmlspecialchars((string)($cliDash['whatsapp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-email="<?= htmlspecialchars((string)($cliDash['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-matter="<?= htmlspecialchars((string)($cliDash['especialidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string)($cliDash['nombre'] ?? 'Cliente')) ?> · <?= htmlspecialchars((string)($cliDash['especialidad'] ?? 'Sin materia')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                    <label class="quote-label">
                                        Servicio
                                        <select class="input" name="service_id" id="quote_service_id">
                                            <option value="">Cotización personalizada</option>
                                            <?php foreach (($servicios ?? []) as $srv): ?>
                                                <option
                                                    value="<?= (int)($srv['id'] ?? 0) ?>"
                                                    data-name="<?= htmlspecialchars((string)($srv['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-matter="<?= htmlspecialchars((string)($srv['materia'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-detail="<?= htmlspecialchars((string)($srv['detalle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-term="<?= htmlspecialchars((string)($srv['plazo_estimado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-price="<?= htmlspecialchars((string)($srv['precio_base'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-costs="<?= htmlspecialchars((string)($srv['gastos_base'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string)($srv['nombre'] ?? 'Servicio')) ?> · <?= formatClpAmount($srv['precio_base'] ?? 0) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                                <div class="quote-fields-3">
                                    <label class="quote-label">
                                        Nombre cliente
                                        <input class="input" type="text" name="client_name" id="quote_client_name" placeholder="Nombre y apellido" required>
                                    </label>
                                    <label class="quote-label">
                                        WhatsApp cliente
                                        <input class="input" type="text" name="client_whatsapp" id="quote_client_whatsapp" placeholder="912345678">
                                    </label>
                                    <label class="quote-label">
                                        Email cliente
                                        <input class="input" type="email" name="client_email" id="quote_client_email" placeholder="cliente@correo.cl">
                                    </label>
                                </div>
                                <div class="quote-fields-2">
                                    <label class="quote-label">
                                        Asunto / servicio cotizado
                                        <input class="input" type="text" name="asunto" id="quote_asunto" placeholder="Ej: Defensa en audiencia de control" required>
                                    </label>
                                    <label class="quote-label">
                                        Materia
                                        <select class="input" name="materia" id="quote_materia">
                                            <option value="">Selecciona una materia</option>
                                            <?php foreach (array_keys((array)($materias_taxonomia ?? [])) as $matDash): ?>
                                                <option value="<?= htmlspecialchars((string)$matDash) ?>"><?= htmlspecialchars((string)$matDash) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </label>
                                </div>
                                <div class="quote-step-actions">
                                    <button class="btn btn-primary" type="button" data-step-next>Continuar</button>
                                </div>
                            </section>

                            <section class="quote-step" data-step="2" hidden>
                                <div class="quote-step-head">
                                    <h3>Paso 2: alcance de la propuesta</h3>
                                    <p class="muted">Aquí defines vigencia, plazo, detalle y exclusiones de la cotización.</p>
                                </div>
                                <div class="quote-fields-2">
                                    <label class="quote-label">
                                        Plazo estimado
                                        <input class="input" type="text" name="plazo_estimado" id="quote_plazo" placeholder="24 horas, 5 días hábiles">
                                    </label>
                                    <label class="quote-label">
                                        Vigencia
                                        <input class="input" type="text" name="vigencia" id="quote_vigencia" placeholder="7 días corridos">
                                    </label>
                                </div>
                                <label class="quote-label">
                                    Detalle de la propuesta
                                    <textarea class="input quote-textarea" name="detalle" id="quote_detalle" placeholder="Describe alcance, entregables, reuniones, escritos o gestiones incluidas."></textarea>
                                </label>
                                <label class="quote-label">
                                    No incluye
                                    <textarea class="input quote-textarea quote-textarea-sm" name="no_incluye" id="quote_no_incluye" placeholder="Ej: tasas judiciales, notarías, peritajes, apelaciones, audiencias extraordinarias."></textarea>
                                </label>
                                <div class="quote-fields-2">
                                    <label class="quote-label">
                                        Forma de pago
                                        <input class="input" type="text" name="condiciones_pago" id="quote_condiciones_pago" placeholder="Transferencia 50/50, 3 cuotas, contado">
                                    </label>
                                    <label class="quote-label">
                                        Link de pago
                                        <input class="input" type="url" name="payment_link" id="quote_payment_link" placeholder="https://...">
                                    </label>
                                </div>
                                <div class="quote-fields-2">
                                    <label class="quote-label">
                                        Estado
                                        <select class="input" name="estado" id="quote_estado">
                                            <option value="BORRADOR">Borrador</option>
                                            <option value="ENVIADA">Enviada</option>
                                            <option value="ACEPTADA">Aceptada</option>
                                            <option value="RECHAZADA">Rechazada</option>
                                            <option value="ANULADA">Anulada</option>
                                        </select>
                                    </label>
                                    <label class="quote-label">
                                        Notas internas o aclaraciones
                                        <input class="input" type="text" name="notas" id="quote_notas" placeholder="Documentos requeridos, agenda, urgencia, etc.">
                                    </label>
                                </div>
                                <div class="quote-step-actions">
                                    <button class="btn btn-ghost" type="button" data-step-prev>Volver</button>
                                    <button class="btn btn-primary" type="button" data-step-next>Continuar</button>
                                </div>
                            </section>

                            <section class="quote-step" data-step="3" hidden>
                                <div class="quote-step-head">
                                    <h3>Paso 3: montos y generación</h3>
                                    <p class="muted">Calcula total, anticipo y saldo. Desde aquí generas la cotización final.</p>
                                </div>
                                <div class="quote-fields-4">
                                    <label class="quote-label">
                                        Honorarios
                                        <input class="input quote-money" type="number" min="0" step="1000" name="honorarios" id="quote_honorarios" value="0">
                                    </label>
                                    <label class="quote-label">
                                        Gastos
                                        <input class="input quote-money" type="number" min="0" step="1000" name="gastos" id="quote_gastos" value="0">
                                    </label>
                                    <label class="quote-label">
                                        Descuento
                                        <input class="input quote-money" type="number" min="0" step="1000" name="descuento" id="quote_descuento" value="0">
                                    </label>
                                    <label class="quote-label">
                                        Anticipo
                                        <input class="input quote-money" type="number" min="0" step="1000" name="anticipo" id="quote_anticipo" value="0">
                                    </label>
                                </div>
                                <div class="quote-summary-grid">
                                    <div class="dash-stat quote-summary-box"><strong id="quote_total_view">$0</strong>Total</div>
                                    <div class="dash-stat quote-summary-box"><strong id="quote_anticipo_view">$0</strong>Anticipo</div>
                                    <div class="dash-stat quote-summary-box"><strong id="quote_saldo_view">$0</strong>Saldo</div>
                                </div>
                                <div class="quote-step-actions">
                                    <button class="btn btn-ghost" type="button" data-step-prev>Volver</button>
                                    <button class="btn btn-ghost" type="button" onclick="dashboardResetQuoteForm()">Nueva cotización</button>
                                    <button class="btn btn-primary" type="submit">Generar cotización</button>
                                </div>
                            </section>
                        </form>
                    </div>

                    <div class="dash-card">
                        <div class="quote-section-head">
                            <div>
                                <h2 class="section-title">Mensaje listo para enviar</h2>
                                <p class="muted">Puedes copiarlo o abrir WhatsApp/email con el contenido ya armado.</p>
                            </div>
                        </div>
                        <textarea id="quote_preview" class="input quote-preview" readonly></textarea>
                        <div class="dash-actions-3" style="margin-top:10px;">
                            <button class="btn btn-primary" type="button" id="quoteWhatsappBtn">Abrir WhatsApp</button>
                            <button class="btn btn-ghost" type="button" id="quoteEmailBtn">Abrir email</button>
                            <button class="btn btn-ghost" type="button" id="quotePreviewCopyBtn">Copiar texto</button>
                        </div>
                    </div>
                </div>

                <div class="quote-column quote-pane" id="servicesQuoteInboxPane"<?= $dashSub === 'quotes' ? '' : ' hidden' ?>>
                    <div class="dash-card">
                        <div class="quote-section-head">
                            <div>
                                <h2 class="section-title">Cotizaciones recientes</h2>
                                <p class="muted">Edita una propuesta, cambia su estado o vuelve a enviarla al cliente.</p>
                            </div>
                            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                <span class="dash-tag"><?= count((array)($cotizaciones ?? [])) ?> cotización<?= count((array)($cotizaciones ?? [])) === 1 ? '' : 'es' ?></span>
                                <a class="btn btn-ghost" href="/dashboard/cotizador">Nueva cotización</a>
                            </div>
                        </div>
                        <?php if (empty($cotizaciones)): ?>
                            <p class="muted">Todavía no hay cotizaciones guardadas.</p>
                        <?php else: ?>
                            <div class="quote-list">
                                <?php foreach ($cotizaciones as $cot): ?>
                                    <article class="quote-item-card">
                                        <div class="quote-item-top">
                                            <div>
                                                <strong><?= htmlspecialchars((string)($cot['servicio_nombre_resuelto'] ?? 'Cotización')) ?></strong>
                                                <div class="muted"><?= htmlspecialchars((string)($cot['client_name'] ?? 'Cliente')) ?> · <?= htmlspecialchars((string)($cot['materia'] ?? 'Sin materia')) ?></div>
                                            </div>
                                            <span class="dash-tag <?= htmlspecialchars((string)($cot['estado_class'] ?? 'status-pendiente')) ?>"><?= htmlspecialchars((string)($cot['estado_ui'] ?? 'Borrador')) ?></span>
                                        </div>
                                        <div class="quote-amounts-inline">
                                            <span>Total: <strong><?= formatClpAmount($cot['total'] ?? 0) ?></strong></span>
                                            <span>Anticipo: <strong><?= formatClpAmount($cot['anticipo'] ?? 0) ?></strong></span>
                                            <span>Saldo: <strong><?= formatClpAmount($cot['saldo'] ?? 0) ?></strong></span>
                                        </div>
                                        <?php if (!empty($cot['detalle'])): ?>
                                            <p class="quote-body"><?= nl2br(htmlspecialchars((string)$cot['detalle'])) ?></p>
                                        <?php endif; ?>
                                        <div class="dash-actions-3">
                                            <button
                                                type="button"
                                                class="btn btn-primary"
                                                onclick="dashboardEditQuote(this)"
                                                data-quote="<?= $jsonAttr([
                                                    'id' => (int)($cot['id'] ?? 0),
                                                    'service_id' => (int)($cot['servicio_id'] ?? 0),
                                                    'cliente_id' => (int)($cot['cliente_id'] ?? 0),
                                                    'client_name' => (string)($cot['client_name'] ?? ''),
                                                    'client_whatsapp' => (string)($cot['client_whatsapp'] ?? ''),
                                                    'client_email' => (string)($cot['client_email'] ?? ''),
                                                    'asunto' => (string)($cot['asunto'] ?? ''),
                                                    'materia' => (string)($cot['materia'] ?? ''),
                                                    'detalle' => (string)($cot['detalle'] ?? ''),
                                                    'no_incluye' => (string)($cot['no_incluye'] ?? ''),
                                                    'plazo_estimado' => (string)($cot['plazo_estimado'] ?? ''),
                                                    'vigencia' => (string)($cot['vigencia'] ?? ''),
                                                    'honorarios' => (string)($cot['honorarios'] ?? '0'),
                                                    'gastos' => (string)($cot['gastos'] ?? '0'),
                                                    'descuento' => (string)($cot['descuento'] ?? '0'),
                                                    'anticipo' => (string)($cot['anticipo'] ?? '0'),
                                                    'condiciones_pago' => (string)($cot['condiciones_pago'] ?? ''),
                                                    'payment_link' => (string)($cot['payment_link'] ?? ''),
                                                    'notas' => (string)($cot['notas'] ?? ''),
                                                    'estado' => (string)($cot['estado'] ?? 'BORRADOR'),
                                                ]) ?>">Editar</button>
                                            <a class="btn btn-ghost" href="/dashboard/cotizaciones/<?= (int)($cot['id'] ?? 0) ?>/pdf" target="_blank" rel="noopener">PDF</a>
                                            <?php if (!empty($cot['client_whatsapp_href'])): ?>
                                                <a class="btn btn-ghost" href="<?= htmlspecialchars((string)$cot['client_whatsapp_href']) ?>" target="_blank" rel="noopener">WhatsApp</a>
                                            <?php endif; ?>
                                            <?php if (!empty($cot['client_email_href'])): ?>
                                                <a class="btn btn-ghost" href="<?= htmlspecialchars((string)$cot['client_email_href']) ?>">Email</a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-ghost" onclick="copyTextFromButton(this)" data-copy-text="<?= htmlspecialchars((string)($cot['mensaje_texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Copiar</button>
                                        </div>
                                        <div class="tiny-row" style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                                            <?php foreach (['BORRADOR' => 'Borrador', 'ENVIADA' => 'Enviada', 'ACEPTADA' => 'Aceptada', 'RECHAZADA' => 'Rechazada', 'ANULADA' => 'Anulada'] as $estadoKey => $estadoLabel): ?>
                                                <?php if (($cot['estado'] ?? 'BORRADOR') === $estadoKey) continue; ?>
                                                <form method="POST" action="/dashboard/cotizaciones/estado">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <input type="hidden" name="quote_id" value="<?= (int)($cot['id'] ?? 0) ?>">
                                                    <button class="btn btn-ghost" name="estado" value="<?= htmlspecialchars($estadoKey) ?>" type="submit"><?= htmlspecialchars($estadoLabel) ?></button>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="quote-column quote-pane" id="servicesBrandingPane"<?= $dashSub === 'branding' ? '' : ' hidden' ?>>
                    <div class="dash-card">
                        <div class="quote-section-head">
                            <div>
                                <h2 class="section-title">Firma comercial</h2>
                                <p class="muted">Define la firma automática que aparece en mensajes, cotizaciones y PDF.</p>
                            </div>
                        </div>
                        <form action="/dashboard/cotizaciones/marca" method="POST" class="quote-form-grid" id="quoteBrandingForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            <label class="quote-label quote-checkbox">
                                <span>Estado</span>
                                <span class="quote-checkbox-row">
                                    <input type="checkbox" name="quote_branding_enabled" id="quote_branding_enabled" <?= !empty($branding_settings['enabled']) ? 'checked' : '' ?>>
                                    Activar firma automática para mis cotizaciones
                                </span>
                            </label>
                            <div class="quote-fields-2">
                                <label class="quote-label">
                                    Marca
                                    <input class="input" type="text" name="quote_brand_name" id="quote_brand_name" value="<?= htmlspecialchars((string)($branding_settings['brand_name'] ?? '')) ?>" placeholder="FLOCID">
                                </label>
                                <label class="quote-label">
                                    Razón social
                                    <input class="input" type="text" name="quote_brand_legal_name" id="quote_brand_legal_name" value="<?= htmlspecialchars((string)($branding_settings['legal_name'] ?? '')) ?>" placeholder="Defensa y Asesoría Jurídica SpA">
                                </label>
                            </div>
                            <div class="quote-fields-2">
                                <label class="quote-label">
                                    RUT
                                    <input class="input" type="text" name="quote_brand_rut" id="quote_brand_rut" value="<?= htmlspecialchars((string)($branding_settings['rut'] ?? '')) ?>" placeholder="78.312.211-1">
                                </label>
                                <label class="quote-label">
                                    Teléfono
                                    <input class="input" type="text" name="quote_brand_phone" id="quote_brand_phone" value="<?= htmlspecialchars((string)($branding_settings['phone'] ?? '')) ?>" placeholder="+56 9 ...">
                                </label>
                            </div>
                            <div class="quote-fields-2">
                                <label class="quote-label">
                                    Email de contacto
                                    <input class="input" type="email" name="quote_brand_email" id="quote_brand_email" value="<?= htmlspecialchars((string)($branding_settings['email'] ?? '')) ?>" placeholder="notificaciones@...">
                                </label>
                                <label class="quote-label">
                                    Dirección real
                                    <input class="input" type="text" name="quote_brand_address" id="quote_brand_address" value="<?= htmlspecialchars((string)($branding_settings['address'] ?? '')) ?>" placeholder="Ingresa dirección real">
                                </label>
                            </div>
                            <label class="quote-label">
                                Aviso legal
                                <textarea class="input quote-textarea-sm" name="quote_brand_legal_notice" id="quote_brand_legal_notice" placeholder="Aviso legal o pie corporativo"><?= htmlspecialchars((string)($branding_settings['legal_notice'] ?? '')) ?></textarea>
                            </label>
                            <div class="dash-actions-3">
                                <button class="btn btn-primary" type="submit">Guardar firma</button>
                            </div>
                        </form>
                    </div>

                    <div class="dash-card">
                        <div class="quote-section-head">
                            <div>
                                <h2 class="section-title">Vista previa de firma</h2>
                                <p class="muted">Así se verá al final de tus cotizaciones y mensajes listos para enviar.</p>
                            </div>
                        </div>
                        <div class="quote-brand-block">
                            <strong><?= htmlspecialchars((string)(($branding_settings['brand_name'] ?? '') !== '' ? $branding_settings['brand_name'] : 'Firma del cotizador')) ?></strong>
                            <?php if (!empty($branding_settings['legal_name'])): ?><div><?= htmlspecialchars((string)$branding_settings['legal_name']) ?></div><?php endif; ?>
                            <?php if (!empty($branding_settings['rut'])): ?><div>RUT <?= htmlspecialchars((string)$branding_settings['rut']) ?></div><?php endif; ?>
                            <div style="margin-top:8px;">Contacto</div>
                            <?php if (!empty($branding_settings['phone'])): ?><div>📞 <?= htmlspecialchars((string)$branding_settings['phone']) ?></div><?php endif; ?>
                            <?php if (!empty($branding_settings['email'])): ?><div>📧 <?= htmlspecialchars((string)$branding_settings['email']) ?></div><?php endif; ?>
                            <?php if (!empty($branding_settings['address'])): ?><div><?= htmlspecialchars((string)$branding_settings['address']) ?></div><?php else: ?><div class="muted">Falta completar la dirección real.</div><?php endif; ?>
                            <?php if (!empty($branding_settings['legal_notice'])): ?><div style="margin-top:8px;"><?= htmlspecialchars((string)$branding_settings['legal_notice']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="view-perfil" class="grid" style="margin-top: 16px; display: <?= $dashMain === 'perfil' ? 'grid' : 'none' ?>;">
            <div class="crm-breadcrumb">Gestión de leads<span class="sep">/</span>Mi perfil</div>
            <h2 class="section-title">Mi perfil público</h2>
            <div class="dash-card">
                <p class="muted">Gestiona tu tarjeta y datos públicos desde un lugar más simple.</p>
                <div class="flex-row-wrap mt-10">
                    <a class="btn btn-primary" href="/mi-tarjeta">Ver mi ficha</a>
                    <a class="btn btn-ghost" href="/completar-datos?modo=abogado">Editar perfil profesional</a>
                    <?php if(!empty($user['slug'])): ?>
                        <a class="btn btn-ghost" href="/<?= htmlspecialchars((string)$user['slug']) ?>" target="_blank" rel="noopener">Abrir perfil público</a>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section id="view-crm" class="grid" style="margin-top: 16px; display: <?= $dashSub === 'leads' ? 'grid' : 'none' ?>;">
            <div class="dash-subtabs">
                <a href="/dashboard/leads" id="subtab-leads-crm" class="dash-subtab<?= $dashSub === 'leads' ? ' active' : '' ?>">Bandeja de leads</a>
                <a href="/dashboard/cotizaciones" id="subtab-quotes-crm" class="dash-subtab<?= $dashSub === 'quotes' ? ' active' : '' ?>">Bandeja de cotizaciones</a>
            </div>
            <div class="crm-breadcrumb">Bandejas<span class="sep">/</span>Leads</div>
            <div class="crm-layout">
                <aside class="crm-sidebar">
                    <div class="crm-sidebar-title">Bandejas</div>
                    <button type="button" onclick="abrirModalProspecto()" class="btn btn-primary fullw" style="margin:8px 0 10px;">+ Nuevo prospecto</button>
                    <div class="muted" style="font-size:12px;margin-bottom:8px;">Gestiona todos tus leads desde estas bandejas.</div>
                    <div class="crm-filter-row" id="crmFilterRow">
                        <button type="button" class="crm-filter-btn active" data-filter="ALL"><span class="label">Todos</span><span class="count"><?= (int)array_sum($crmCounts) ?></span></button>
                        <button type="button" class="crm-filter-btn is-hot" data-filter="PENDIENTE"><span class="label">Nuevos</span><span class="count"><?= (int)($crmCounts['PENDIENTE'] ?? 0) ?></span></button>
                        <button type="button" class="crm-filter-btn" data-filter="CONTACTADO"><span class="label">Contactados</span><span class="count"><?= (int)($crmCounts['CONTACTADO'] ?? 0) ?></span></button>
                        <button type="button" class="crm-filter-btn" data-filter="GANADO"><span class="label">Cerrados</span><span class="count"><?= (int)($crmCounts['GANADO'] ?? 0) ?></span></button>
                        <button type="button" class="crm-filter-btn" data-filter="PERDIDO"><span class="label">No cerró</span><span class="count"><?= (int)($crmCounts['PERDIDO'] ?? 0) ?></span></button>
                        <button type="button" class="crm-filter-btn" data-filter="CANCELADO"><span class="label">Archivados</span><span class="count"><?= (int)($crmCounts['CANCELADO'] ?? 0) ?></span></button>
                    </div>
                </aside>
                <div class="crm-main">
            <?php if(empty($mis_casos)): ?>
                <div class="dash-card">
                    <p class="muted">Aún no tienes leads. Los leads llegan cuando clientes completan el formulario desde tu perfil.</p>
                </div>
            <?php else: ?>
                <?php foreach (['PENDIENTE','CONTACTADO','GANADO','PERDIDO','CANCELADO'] as $bucketKey): ?>
                    <?php $bucketItems = $crmBuckets[$bucketKey] ?? []; if (empty($bucketItems)) continue; ?>
                    <div class="crm-bucket" data-bucket="<?= htmlspecialchars($bucketKey) ?>">
                    <div class="dash-card" style="padding:10px 12px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap;">
                            <strong><?= htmlspecialchars($crmBucketTitles[$bucketKey] ?? $bucketKey) ?></strong>
                            <span class="dash-tag <?= htmlspecialchars($crmStateTagClass[$bucketKey] ?? '') ?>"><?= (int)count($bucketItems) ?> lead<?= count($bucketItems) === 1 ? '' : 's' ?></span>
                        </div>
                    </div>
                    <?php foreach($bucketItems as $m): ?>
                    <?php
                        $mStateKey = strtoupper((string)($m['estado'] ?? 'PENDIENTE'));
                        $mStateTagClass = $crmStateTagClass[$mStateKey] ?? 'status-pendiente';
                        $mStateShort = [
                            'PENDIENTE' => 'Nuevo',
                            'CONTACTADO' => 'Contactado',
                            'GANADO' => 'Cerró',
                            'PERDIDO' => 'No cerró',
                            'CANCELADO' => 'Archivado',
                        ][$mStateKey] ?? ($m['estado_ui'] ?? ($m['estado'] ?? 'PENDIENTE'));
                    ?>
                    <div class="dash-case" id="caso-<?= (int)$m['id'] ?>">
                        <div class="dash-case-head">
                            <strong><?= htmlspecialchars($m['nombre']) ?></strong>
                            <span class="dash-tag <?= htmlspecialchars($mStateTagClass) ?>"><?= htmlspecialchars((string)$mStateShort) ?></span>
                        </div>
                        <div class="muted"><?= htmlspecialchars($m['especialidad']) ?> · <?= htmlspecialchars($m['medio_contacto'] ?? 'Web') ?> · <?= !empty($m['ciudad']) ? htmlspecialchars($m['ciudad']) : 'Lugar no indicado' ?></div>
                        <?php if ($lastChangeLabel = $humanLeadAgo($m['estado_updated_at'] ?? null)): ?>
                            <div class="muted" style="margin-top:4px;">Último cambio: <?= htmlspecialchars($lastChangeLabel) ?></div>
                        <?php endif; ?>
                        <div class="muted" style="margin-top:6px;">+56 <?= htmlspecialchars($m['whatsapp']) ?> · <?= htmlspecialchars($m['email'] ?? 'Sin correo') ?></div>
                        <div class="muted" style="margin-top:6px; font-size:12px;">Mensaje del cliente</div>
                        <p class="case-body"><?= !empty($m['consulta']) ? htmlspecialchars((string)$m['consulta']) : 'Lead recibido desde el formulario del perfil.' ?></p>
                        <div class="dash-actions-3">
                            <a class="btn btn-primary" href="https://wa.me/56<?= htmlspecialchars((string)$m['whatsapp']) ?>?text=Hola%20<?= urlencode((string)($m['nombre'] ?? '')) ?>%2C%20te%20contacto%20desde%20Tu%20Estudio%20Juridico">WhatsApp</a>
                            <a class="btn btn-ghost" href="tel:+56<?= htmlspecialchars((string)$m['whatsapp']) ?>">Llamar</a>
                            <a class="btn btn-ghost" href="mailto:<?= htmlspecialchars((string)($m['email'] ?? '')) ?>">Email</a>
                            <button
                                type="button"
                                class="btn btn-ghost"
                                onclick="dashboardQuoteFromLead(this)"
                                data-lead="<?= $jsonAttr([
                                    'client_id' => (int)($m['cliente_id'] ?? 0),
                                    'name' => (string)($m['nombre'] ?? ''),
                                    'whatsapp' => (string)($m['whatsapp'] ?? ''),
                                    'email' => (string)($m['email'] ?? ''),
                                    'matter' => (string)($m['especialidad'] ?? ''),
                                    'notes' => (string)($m['consulta'] ?? ''),
                                ]) ?>">Cotizar</button>
                        </div>
                        <?php if ($mStateKey === 'GANADO'): ?>
                            <div class="lead-amount-row lead-amount-highlight">
                                <div class="muted"><strong>Cierre:</strong> Cerrado desde cotización aceptada.</div>
                                <div class="lead-secondary-links"><a href="/dashboard?tab=quotes">Ver cotizaciones</a></div>
                                <form method="POST" action="/actualizar-estado-caso" class="lead-amount-edit">
                                    <input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button class="btn btn-ghost" name="estado" value="CANCELADO" type="submit">Dejar sin efecto</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <div class="tiny-row" style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                            <?php if ($mStateKey === 'PENDIENTE'): ?>
                                <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-primary" name="estado" value="CONTACTADO" type="submit">Contactado</button></form>
                                <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-ghost" name="estado" value="CANCELADO" type="submit">Archivar</button></form>
                            <?php elseif ($mStateKey === 'CONTACTADO'): ?>
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    onclick="dashboardQuoteFromLead(this)"
                                    data-lead="<?= $jsonAttr([
                                        'client_id' => (int)($m['cliente_id'] ?? 0),
                                        'name' => (string)($m['nombre'] ?? ''),
                                        'whatsapp' => (string)($m['whatsapp'] ?? ''),
                                        'email' => (string)($m['email'] ?? ''),
                                        'matter' => (string)($m['especialidad'] ?? ''),
                                        'notes' => (string)($m['consulta'] ?? ''),
                                    ]) ?>">Crear cotización</button>
                                <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-ghost" name="estado" value="PERDIDO" type="submit">No cerró</button></form>
                                <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-ghost" name="estado" value="CANCELADO" type="submit">Archivar</button></form>
                            <?php elseif (in_array($mStateKey, ['PERDIDO','CANCELADO'], true)): ?>
                                <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-ghost" name="estado" value="CONTACTADO" type="submit">Reabrir</button></form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
                </div>
            </div>
        </section>
    </main>


    <div id="modalProspecto" class="dash-modal">
        <div class="dash-modal-card">
            <h3 style="margin-top:0;">Nuevo prospecto</h3>
            <p class="muted" style="margin:4px 0 10px;font-size:12px;">Ingresa un prospecto manual para gestionarlo igual que un lead del perfil (Nuevo → Contactado → Cotización → Cerrado).</p>
            <form action="/agregar-prospecto-crm" method="POST" class="grid mt-10" style="grid-template-columns:1fr;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                <label class="muted" style="font-size:12px;font-weight:700;">Nombre completo</label>
                <input type="text" name="nombre" placeholder="Nombre completo" required class="input">

                <label class="muted" style="font-size:12px;font-weight:700;">WhatsApp</label>
                <input type="tel" name="whatsapp" placeholder="912345678" required class="input" inputmode="numeric" pattern="9[0-9]{8}">

                <label class="muted" style="font-size:12px;font-weight:700;">Correo (opcional)</label>
                <input type="email" name="email" placeholder="correo@ejemplo.cl" class="input">

                <label class="muted" style="font-size:12px;font-weight:700;">Ciudad / comuna (opcional)</label>
                <input type="text" name="ciudad" placeholder="La Serena" class="input">

                <label class="muted" style="font-size:12px;font-weight:700;">Materia principal</label>
                <select name="especialidad" class="input">
                    <?php
                    $taxDash = (array)($materias_taxonomia ?? []);
                    $materiaKeys = array_keys($taxDash);
                    if (empty($materiaKeys)) { $materiaKeys = ['Derecho Civil','Derecho Familiar','Derecho Laboral','Derecho Penal']; }
                    foreach ($materiaKeys as $mk):
                    ?>
                        <option value="<?= htmlspecialchars($mk) ?>"><?= htmlspecialchars($mk) ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="muted" style="font-size:12px;font-weight:700;">Origen del lead</label>
                <select name="medio_contacto" class="input">
                    <option value="Referido">Referido</option>
                    <option value="Llamada">Llamada</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Instagram">Instagram</option>
                    <option value="TikTok">TikTok</option>
                    <option value="Web">Web</option>
                    <option value="Otro abogado">Otro abogado</option>
                </select>

                <label class="muted" style="font-size:12px;font-weight:700;">Mensaje del cliente (opcional)</label>
                <textarea name="consulta" rows="4" class="input" placeholder="Ej: Necesito orientación penal urgente, tengo audiencia mañana y quiero cotizar defensa."></textarea>

                <button type="submit" class="btn btn-primary fullw">Guardar en CRM</button>
                <button type="button" onclick="cerrarModalProspecto()" class="btn btn-ghost fullw">Cancelar</button>
            </form>
        </div>
    </div>

    <div
        id="dashboardPageConfig"
        data-initial-tab="<?= htmlspecialchars((string)($initial_tab ?? 'crm')) ?>"
        data-pending-lead-count="<?= (int)($crmCounts['PENDIENTE'] ?? 0) ?>">
    </div>
    <script src="/dashboard-page.js?v=2026030203"></script>
</body>
</html>
