<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Tu Estudio Juridico</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 14px 28px; }
        .stack { display:grid; gap:10px; margin-top:12px; }
        .stats { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
        .stat { border:1px solid var(--app-line); border-radius:14px; background:rgba(15,29,52,.62); padding:12px; }
        .stat strong { display:block; font-size:20px; line-height:1; }
        .stat span { display:block; margin-top:4px; color:var(--app-muted); font-size:11px; }
        .card { border:1px solid var(--app-line); border-radius:14px; background:rgba(15,29,52,.58); padding:12px; }
        .card h2 { margin:0 0 8px; font-size:14px; }
        .lead-summary { margin-top:12px; display:flex; flex-wrap:wrap; gap:12px; align-items:center; }
        .lead-summary .lead-summary-stats { display:flex; gap:12px; flex-wrap:wrap; }
        .lead-summary .lead-summary-stats > div { min-width:120px; }
        .lead-summary strong { font-size:18px; display:block; }
        .lead-summary span { font-size:11px; color:var(--app-muted); }
        .lead-summary-bar { flex:1; height:10px; border-radius:999px; background:rgba(255,255,255,.06); overflow:hidden; display:flex; }
        .lead-summary-bar span { display:block; height:100%; transition:width .2s ease; }
        .lead-summary-bar-active { background:linear-gradient(90deg,#4ade80,#14b8a6); }
        .lead-summary-bar-papelera { background:linear-gradient(90deg,#f97316,#facc15); }
        .lead-health { margin-top:6px; display:flex; flex-wrap:wrap; gap:6px; }
        .lead-health span { background:rgba(255,255,255,.08); padding:2px 6px; border-radius:999px; font-size:11px; font-weight:600; }
        .lead-timeline { margin-top:6px; display:flex; flex-wrap:wrap; gap:6px; font-size:11px; }
        .lead-timeline span { background:rgba(255,255,255,.04); padding:2px 6px; border-radius:999px; color:var(--app-muted); }
        .lead-filter-row { margin-top:6px; }
        .admin-promoted-toggle { display:flex; justify-content:flex-end; margin:6px 0 4px; }
        .admin-promoted-toggle .btn { padding:6px 10px; font-size:11px; }
        .admin-promoted-panel[hidden] { display:none; }
        .table-wrap { overflow:auto; }
        table { width:100%; border-collapse:collapse; font-size:11px; }
        th, td { padding:8px 6px; border-bottom:1px solid rgba(255,255,255,.06); text-align:left; vertical-align:top; }
        th { color:var(--app-muted); font-weight:700; font-size:10px; text-transform:uppercase; letter-spacing:.04em; }
        .badge { display:inline-block; border:1px solid var(--app-line-strong); border-radius:999px; padding:2px 7px; font-size:10px; font-weight:700; }
        .actions-cell { display:grid; gap:6px; min-width:190px; }
        .actions-cell form { display:grid; gap:6px; }
        .tiny-row { display:flex; gap:6px; flex-wrap:wrap; }
        .tiny-row .btn, .actions-cell .btn { padding:6px 8px; font-size:10px; }
        .tiny-input { width:74px; padding:5px 6px; border-radius:8px; border:1px solid var(--app-line); background:rgba(255,255,255,.04); color:var(--app-ink); font-size:10px; }
        .mobile-list { display:none; gap:10px; }
        .mobile-item { border:1px solid var(--app-line); border-radius:12px; background:rgba(255,255,255,.02); padding:10px; display:grid; gap:8px; }
        .mobile-item .row { display:grid; gap:4px; }
        .mobile-item .k { font-size:10px; color:var(--app-muted); text-transform:uppercase; letter-spacing:.04em; }
        .mobile-item .v { font-size:13px; color:var(--app-ink); line-height:1.35; }
        .mobile-item .v.muted { color:var(--app-muted); font-size:12px; }
        .mobile-item .split { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
        .mobile-item .actions-cell { min-width:0; }
        .mobile-item .actions-cell .tiny-row { display:grid; grid-template-columns:1fr 1fr; gap:6px; }
        .mobile-item .actions-cell .tiny-row:last-child { grid-template-columns:78px 1fr 1fr; }
        .mobile-item .tiny-input { width:100%; }
        @media (max-width: 900px) { .stats { grid-template-columns:1fr 1fr; } }
        @media (max-width: 760px) {
            .table-wrap { display:none; }
            .mobile-list { display:grid; }
            .card { padding:10px; }
            .card h2 { font-size:15px; }
            .lead-summary { flex-direction:column; align-items:flex-start; }
        }
        @media (max-width: 560px) {
            .stats { grid-template-columns:1fr; }
            .mobile-item .split { grid-template-columns:1fr; }
            .mobile-item .actions-cell .tiny-row,
            .mobile-item .actions-cell .tiny-row:last-child { grid-template-columns:1fr; }
        }
    </style>
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
</head>
<body class="theme-black theme-admin">
    <nav class="nav">
        <div class="nav-inner">
            <a class="brand" href="/explorar">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/explorar">Explorar</a>
                <a href="/panel">Mi panel</a>
                <a class="active" href="/admin">Admin</a>
            </div>
            <div class="nav-actions">
                <a class="btn btn-ghost" href="/logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <section class="stack">
            <?php
                $postulaciones_incompletas = [];
                $postulaciones_listas = [];
                foreach (($postulaciones ?? []) as $pp) {
                    $pctTmp = function_exists('lawyerProfileCompletionPercent') ? lawyerProfileCompletionPercent((array)$pp) : 0;
                    if ($pctTmp >= 80) { $postulaciones_listas[] = $pp + ['_perfil_pct' => $pctTmp]; }
                    else { $postulaciones_incompletas[] = $pp + ['_perfil_pct' => $pctTmp]; }
                }
                $rut_pendientes = [];
                foreach (($abogados_promovidos ?? []) as $abx) {
                    $rvx = trim((string)($abx['rut_validacion_manual'] ?? ''));
                    if ($rvx === '') $rut_pendientes[] = $abx;
                }
                $formatLeadDate = function ($value) {
                    if (empty($value)) return '';
                    $ts = strtotime((string)$value);
                    return $ts ? date('Y-m-d H:i', $ts) : '';
                };
            ?>
            <?php if (!empty($_SESSION['mensaje'] ?? null)): ?>
            <section class="card">
                <div class="muted"><?= htmlspecialchars((string)($_SESSION['mensaje'] ?? '')) ?></div>
            </section>
            <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); endif; ?>
            <section class="app-toolbar">
                <div>
                    <div class="title">Dashboard administrador</div>
                    <div class="sub"><?= htmlspecialchars((string)($admin_user['email'] ?? '')) ?></div>
                </div>
                <span class="dot" aria-hidden="true"></span>
            </section>

            <section class="card">
                <h2>Leads: respaldo y retención</h2>
                <div class="tiny-row">
                    <a class="btn btn-primary" href="/admin/leads-export.csv">Descargar respaldo CSV</a>
                    <form method="POST" action="/admin/leads-mantenimiento">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                        <button type="submit" class="btn btn-ghost">Correr mantenimiento (30/30)</button>
                    </form>
                </div>
                <?php
                    $lead_counts = $lead_counts ?? [];
                    $lead_activos = (int)($lead_counts['leads_activos'] ?? 0);
                    $lead_papelera = (int)($lead_counts['leads_papelera'] ?? 0);
                    $lead_unseen = (int)($lead_counts['leads_sin_ver'] ?? 0);
                    $lead_total = $lead_activos + $lead_papelera;
                    $lead_total_for_bar = $lead_total > 0 ? $lead_total : 1;
                    $lead_active_pct = min(100, round(($lead_activos / $lead_total_for_bar) * 100, 1));
                    $lead_papelera_pct = min(100, round(($lead_papelera / $lead_total_for_bar) * 100, 1));
                ?>
                <div class="muted" style="margin-top:8px;">Leads activos 30 días, luego papelera 30 días y eliminación. Respáldalos antes de ejecutar mantenimiento.</div>
                <div class="lead-summary">
                    <div class="lead-summary-stats">
                        <div>
                            <strong><?= $lead_activos ?></strong>
                            <span>Activos</span>
                        </div>
                        <div>
                            <strong><?= $lead_papelera ?></strong>
                            <span>Papelera</span>
                        </div>
                        <div>
                            <strong><?= $lead_unseen ?></strong>
                            <span>Sin ver</span>
                        </div>
                    </div>
                    <div class="lead-summary-bar" aria-hidden="true">
                        <span class="lead-summary-bar-active" style="width:<?= $lead_active_pct ?>%"></span>
                        <span class="lead-summary-bar-papelera" style="width:<?= $lead_papelera_pct ?>%"></span>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>Previsualizar tema</h2>
                <div class="muted" style="margin-bottom:8px;">Simula cómo se ve la interfaz para cliente, abogado o admin sin cambiar datos.</div>
                <div class="tiny-row">
                    <select id="previewThemeSelect" class="tiny-input" style="width:auto; min-width:180px;">
                        <option value="admin">Admin (olivo)</option>
                        <option value="cliente">Cliente (lite)</option>
                        <option value="abogado">Abogado (azul)</option>
                    </select>
                    <button type="button" class="btn btn-ghost" id="previewThemeReset">Restaurar</button>
                </div>
            </section>

            <section class="stats">
                <div class="stat"><strong><?= (int)($stats['cuentas_total'] ?? 0) ?></strong><span>Cuentas</span></div>
                <div class="stat"><strong><?= (int)($stats['abogados_aprobados'] ?? 0) ?></strong><span>Abogados aprobados</span></div>
                <div class="stat"><strong><?= (int)($stats['postulaciones_pendientes'] ?? 0) ?></strong><span>Postulaciones pendientes</span></div>
                <div class="stat"><strong><?= (int)($stats['leads_total'] ?? 0) ?></strong><span>Leads</span></div>
            </section>

            <section class="card">
                <h2>Métricas web (últimos <?= (int)($analytics['days'] ?? 30) ?> días)</h2>
                <div class="stats" style="grid-template-columns:repeat(3,minmax(0,1fr));">
                    <div class="stat"><strong><?= (int)($analytics['total_contents'] ?? 0) ?></strong><span>Total contenidos</span></div>
                    <div class="stat"><strong><?= (int)($analytics['page_visits_human'] ?? 0) ?> / <?= (int)($analytics['page_visits_raw'] ?? 0) ?></strong><span>Visitas humanas / raw</span></div>
                    <div class="stat"><strong><?= (int)($analytics['content_views_human'] ?? 0) ?> / <?= (int)($analytics['content_views_raw'] ?? 0) ?></strong><span>Vistas contenido humanas / raw</span></div>
                    <div class="stat"><strong><?= (int)($analytics['likes_human'] ?? 0) ?> / <?= (int)($analytics['likes_raw'] ?? 0) ?></strong><span>Likes humanas / raw</span></div>
                    <div class="stat"><strong><?= (int)($analytics['shares_human'] ?? 0) ?> / <?= (int)($analytics['shares_raw'] ?? 0) ?></strong><span>Shares humanas / raw</span></div>
                    <div class="stat"><strong><?= (int)($analytics['interactions_human'] ?? 0) ?> / <?= (int)($analytics['interactions_raw'] ?? 0) ?></strong><span>Interacciones humanas / raw</span></div>
                    <div class="stat"><strong><?= (int)($analytics['content_with_activity'] ?? 0) ?></strong><span>Contenido con actividad</span></div>
                </div>
            </section>

            <section class="card">
                <h2>Top contenidos</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Contenido</th><th>Views H/R</th><th>Interacciones H/R</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($analytics['top_contents'] ?? [])): ?>
                            <tr><td colspan="4">Sin actividad registrada.</td></tr>
                        <?php else: foreach (($analytics['top_contents'] ?? []) as $tc): ?>
                            <tr>
                                <td>#<?= (int)($tc['content_id'] ?? 0) ?></td>
                                <td>
                                    <?= htmlspecialchars((string)($tc['content_name'] ?? 'Contenido')) ?>
                                    <?php if (!empty($tc['content_slug'])): ?>
                                        <div class="muted" style="font-size:10px;"><a href="/<?= htmlspecialchars((string)$tc['content_slug']) ?>">/<?= htmlspecialchars((string)$tc['content_slug']) ?></a></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int)($tc['views_human'] ?? 0) ?> / <?= (int)($tc['views_raw'] ?? 0) ?></td>
                                <td><?= (int)($tc['interactions_human'] ?? 0) ?> / <?= (int)($tc['interactions_raw'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <h2>Últimas visitas</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Fecha</th><th>Ruta</th><th>Clase</th><th>IP</th><th>User ID</th><th>User-Agent</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($analytics['latest_visits'] ?? [])): ?>
                            <tr><td colspan="6">Sin visitas registradas.</td></tr>
                        <?php else: foreach (($analytics['latest_visits'] ?? []) as $lv): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($lv['created_at'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($lv['path'] ?? '')) ?></td>
                                <td><span class="badge"><?= htmlspecialchars((string)($lv['traffic_class'] ?? '')) ?></span></td>
                                <td><?= htmlspecialchars((string)($lv['ip'] ?? '')) ?></td>
                                <td><?= (int)($lv['user_id'] ?? 0) ?></td>
                                <?php $ua = (string)($lv['user_agent'] ?? ''); $uaShort = function_exists('mb_substr') ? mb_substr($ua, 0, 90, 'UTF-8') : substr($ua, 0, 90); ?>
                                <td><?= htmlspecialchars((string)$uaShort) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <h2>Top IPs</h2>
                <div class="stats" style="grid-template-columns:1fr 1fr;">
                    <div class="stat">
                        <strong>Humanas</strong>
                        <?php if (empty($analytics['top_ips_human'] ?? [])): ?>
                            <div class="muted" style="margin-top:6px;">Sin registros.</div>
                        <?php else: ?>
                            <?php foreach (($analytics['top_ips_human'] ?? []) as $row): ?>
                                <div class="muted" style="margin-top:6px; font-size:12px;"><?= htmlspecialchars((string)($row['ip'] ?? '')) ?> · <?= (int)($row['hits'] ?? 0) ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="stat">
                        <strong>Bots</strong>
                        <?php if (empty($analytics['top_ips_bot'] ?? [])): ?>
                            <div class="muted" style="margin-top:6px;">Sin registros.</div>
                        <?php else: ?>
                            <?php foreach (($analytics['top_ips_bot'] ?? []) as $row): ?>
                                <div class="muted" style="margin-top:6px; font-size:12px;"><?= htmlspecialchars((string)($row['ip'] ?? '')) ?> · <?= (int)($row['hits'] ?? 0) ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>Embudo de revisión (abogados)</h2>
                <div class="tiny-row" style="margin-bottom:8px;">
                    <span class="badge" style="color:#ffd88a;border-color:rgba(255,214,102,.35);">Solicitudes listas (>=80%): <?= count($postulaciones_listas) ?></span>
                    <span class="badge">Solicitudes incompletas (&lt;80%): <?= count($postulaciones_incompletas) ?></span>
                    <span class="badge" style="color:#ffe7a3;border-color:rgba(255,193,7,.35);">RUT pendiente: <?= count($rut_pendientes) ?></span>
                    <span class="badge" style="color:#c4ffd9;border-color:rgba(52,199,89,.35);">Promovidos: <?= count($abogados_promovidos ?? []) ?></span>
                </div>
                <div class="tiny-row" style="margin-bottom:8px;">
                    <a class="btn <?= empty($review_filter) ? 'btn-primary' : 'btn-ghost' ?>" href="/admin">Todos</a>
                    <a class="btn <?= (($review_filter ?? '') === 'ready') ? 'btn-primary' : 'btn-ghost' ?>" href="/admin?review=ready">Listos >=80%</a>
                    <a class="btn <?= (($review_filter ?? '') === 'incomplete') ? 'btn-primary' : 'btn-ghost' ?>" href="/admin?review=incomplete">Incompletos <80%</a>
                    <a class="btn <?= (($review_filter ?? '') === 'rut_pending') ? 'btn-primary' : 'btn-ghost' ?>" href="/admin?review=rut_pending">RUT pendiente</a>
                    <a class="btn <?= (($review_filter ?? '') === 'published') ? 'btn-primary' : 'btn-ghost' ?>" href="/admin?review=published">Publicados</a>
                </div>
                <div class="mobile-list" style="display:grid;">
                    <div class="mobile-item">
                        <div class="row"><div class="k">Listas para aprobar (>=80)</div><div class="v">
                            <?php if (empty($postulaciones_listas)): ?>0<?php else: ?><?= count($postulaciones_listas) ?>
                            <span class="v muted" style="display:block;margin-top:4px;">
                                <?= htmlspecialchars(implode(' · ', array_map(fn($x) => (string)($x['nombre'] ?? ('#'.($x['id']??''))), array_slice($postulaciones_listas,0,3)))) ?><?php if (count($postulaciones_listas)>3): ?> (+<?= count($postulaciones_listas)-3 ?>)<?php endif; ?>
                            </span><?php endif; ?>
                        </div></div>
                        <div class="row"><div class="k">Incompletas (&lt;80)</div><div class="v">
                            <?php if (empty($postulaciones_incompletas)): ?>0<?php else: ?><?= count($postulaciones_incompletas) ?>
                            <span class="v muted" style="display:block;margin-top:4px;">
                                <?= htmlspecialchars(implode(' · ', array_map(fn($x) => (string)($x['nombre'] ?? ('#'.($x['id']??''))), array_slice($postulaciones_incompletas,0,3)))) ?><?php if (count($postulaciones_incompletas)>3): ?> (+<?= count($postulaciones_incompletas)-3 ?>)<?php endif; ?>
                            </span><?php endif; ?>
                        </div></div>
                        <div class="row"><div class="k">RUT pendiente</div><div class="v">
                            <?php if (empty($rut_pendientes)): ?>0<?php else: ?><?= count($rut_pendientes) ?>
                            <span class="v muted" style="display:block;margin-top:4px;">
                                <?= htmlspecialchars(implode(' · ', array_map(fn($x) => (string)($x['nombre'] ?? ('#'.($x['id']??''))), array_slice($rut_pendientes,0,3)))) ?><?php if (count($rut_pendientes)>3): ?> (+<?= count($rut_pendientes)-3 ?>)<?php endif; ?>
                            </span><?php endif; ?>
                        </div></div>
                    </div>
                </div>
            </section>

            <section class="card">
                <h2>Postulaciones profesionales (solicitudes)</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>RUT</th><th>WhatsApp</th><th>Materia</th><th>Perfil %</th><th>FAQ perfil</th><th>Mensaje</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php if (empty($postulaciones)): ?>
                            <tr><td colspan="11">Sin postulaciones.</td></tr>
                        <?php else: foreach ($postulaciones as $p): ?>
                            <?php
                                $faqPost = [];
                                if (!empty($p['faq_personalizadas_json'])) {
                                    $tmpFaq = json_decode((string)$p['faq_personalizadas_json'], true);
                                    if (is_array($tmpFaq)) {
                                        foreach ($tmpFaq as $fq) {
                                            if (!is_array($fq)) continue;
                                            $q = trim((string)($fq['q'] ?? ''));
                                            if ($q !== '') $faqPost[] = $q;
                                        }
                                    }
                                }
                            ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td><?= htmlspecialchars((string)$p['nombre']) ?></td>
                                <td><?= htmlspecialchars((string)$p['email']) ?></td>
                                <td><?= htmlspecialchars((string)($p['rut_abogado'] ?? '')) ?></td>
                                <td><?= !empty($p['whatsapp']) ? '+56 ' . htmlspecialchars((string)$p['whatsapp']) : '' ?></td>
                                <td><?= htmlspecialchars((string)($p['especialidad'] ?? '')) ?></td>
                                <td><?php $pctPost = function_exists('lawyerProfileCompletionPercent') ? lawyerProfileCompletionPercent((array)$p) : 0; ?><span class="badge"><?= (int)$pctPost ?>%</span> <?php if ($pctPost >= 80): ?><span class="badge" style="color:#ffd88a;border-color:rgba(255,214,102,.35);">Listo</span><?php else: ?><span class="badge">Incompleto</span><?php endif; ?></td>
                                <td style="max-width:220px; white-space:normal;">
                                    <?php if (!empty($faqPost)): ?>
                                        <?= htmlspecialchars(implode(' · ', array_slice($faqPost, 0, 2))) ?>
                                        <?php if (count($faqPost) > 2): ?> <span style="color:var(--app-muted)">+<?= count($faqPost) - 2 ?></span><?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--app-muted)">Usa FAQ genérica</span>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width:220px; white-space:normal;">
                                    <?php $msg = trim((string)($p['consulta'] ?? '')); ?>
                                    <?php if ($msg !== ''): ?>
                                        <?= htmlspecialchars(mb_substr($msg, 0, 80, 'UTF-8')) ?><?= mb_strlen($msg, 'UTF-8') > 80 ? '…' : '' ?>
                                    <?php else: ?>
                                        <span class="muted">Sin mensaje</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge"><?= htmlspecialchars((string)($p['estado_verificacion_abogado'] ?? 'pendiente')) ?></span></td>
                                <td><?= htmlspecialchars((string)($p['fecha_solicitud_habilitacion_abogado'] ?? '')) ?></td>
                                <td class="actions-cell">
                                    <form method="POST" action="/admin/usuario/<?= (int)$p['id'] ?>/accion">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                                        <div class="tiny-row">
                                            <button class="btn btn-primary" type="submit" name="accion" value="aprobar_abogado">Aprobar</button>
                                            <button class="btn btn-ghost" type="submit" name="accion" value="rechazar_abogado">Rechazar</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list">
                    <?php if (empty($postulaciones)): ?>
                        <div class="mobile-item"><div class="v">Sin postulaciones.</div></div>
                    <?php else: foreach ($postulaciones as $p): ?>
                        <?php
                            $faqPost = [];
                            if (!empty($p['faq_personalizadas_json'])) {
                                $tmpFaq = json_decode((string)$p['faq_personalizadas_json'], true);
                                if (is_array($tmpFaq)) {
                                    foreach ($tmpFaq as $fq) {
                                        if (!is_array($fq)) continue;
                                        $q = trim((string)($fq['q'] ?? ''));
                                        if ($q !== '') $faqPost[] = $q;
                                    }
                                }
                            }
                        ?>
                            <div class="mobile-item">
                                <div class="split">
                                    <div class="row"><div class="k">ID</div><div class="v">#<?= (int)$p['id'] ?></div></div>
                                    <div class="row"><div class="k">Estado</div><div class="v"><span class="badge"><?= htmlspecialchars((string)($p['estado_verificacion_abogado'] ?? 'pendiente')) ?></span></div></div>
                                </div>
                                <div class="row"><div class="k">Nombre</div><div class="v"><?= htmlspecialchars((string)$p['nombre']) ?></div></div>
                            <div class="row"><div class="k">Email</div><div class="v"><?= htmlspecialchars((string)$p['email']) ?></div></div>
                            <div class="split">
                                <div class="row"><div class="k">RUT</div><div class="v"><?= htmlspecialchars((string)($p['rut_abogado'] ?? '—')) ?></div></div>
                                <div class="row"><div class="k">WhatsApp</div><div class="v"><?= !empty($p['whatsapp']) ? '+56 ' . htmlspecialchars((string)$p['whatsapp']) : '—' ?></div></div>
                            </div>
                            <div class="row"><div class="k">Materia</div><div class="v"><?= htmlspecialchars((string)($p['especialidad'] ?? '—')) ?></div></div>
                            <div class="row"><div class="k">Perfil %</div><div class="v"><?php $pctPost = function_exists('lawyerProfileCompletionPercent') ? lawyerProfileCompletionPercent((array)$p) : 0; ?><?= (int)$pctPost ?>% · <?= $pctPost >= 80 ? 'Listo' : 'Incompleto' ?></div></div>
                                    <div class="row"><div class="k">FAQ</div><div class="v muted"><?php if (!empty($faqPost)): ?><?= htmlspecialchars(implode(' · ', array_slice($faqPost, 0, 2))) ?><?php if (count($faqPost) > 2): ?> (+<?= count($faqPost)-2 ?>)<?php endif; ?><?php else: ?>Usa FAQ genérica<?php endif; ?></div></div>
                                    <div class="row"><div class="k">Mensaje</div><div class="v muted">
                                        <?php $msg = trim((string)($p['consulta'] ?? '')); ?>
                                        <?php if ($msg !== ''): ?><?= htmlspecialchars(mb_substr($msg, 0, 120, 'UTF-8')) ?><?= mb_strlen($msg, 'UTF-8') > 120 ? '…' : '' ?><?php else: ?>Sin mensaje<?php endif; ?>
                                    </div></div>
                                    <div class="row"><div class="k">Fecha solicitud</div><div class="v muted"><?= htmlspecialchars((string)($p['fecha_solicitud_habilitacion_abogado'] ?? '')) ?></div></div>
                            <div class="actions-cell">
                                <form method="POST" action="/admin/usuario/<?= (int)$p['id'] ?>/accion">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                                    <div class="tiny-row">
                                        <button class="btn btn-primary" type="submit" name="accion" value="aprobar_abogado">Aprobar</button>
                                        <button class="btn btn-ghost" type="submit" name="accion" value="rechazar_abogado">Rechazar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </section>

            <div class="admin-promoted-toggle">
                <button type="button" class="btn btn-ghost" data-admin-promoted-toggle="promovidos">Mostrar abogados promovidos (publicados o en revisión PJUD)</button>
            </div>

            <section class="card admin-promoted-panel" data-admin-promoted-panel="promovidos" hidden>
                <h2>Abogados promovidos (publicados o en revisión PJUD)</h2>
                <div class="muted" style="margin-bottom:8px;">Perfiles ya promovidos por admin. Marca manualmente si el RUT fue validado como abogado (sí/no).</div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Nombre</th><th>Email</th><th>RUT</th><th>Materia</th><th>Perfil %</th><th>Vistas abogados</th><th>Estado RUT</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php if (empty($abogados_promovidos ?? [])): ?>
                            <tr><td colspan="9">Sin abogados promovidos.</td></tr>
                        <?php else: foreach (($abogados_promovidos ?? []) as $abp): ?>
                            <tr>
                                <td><?= (int)$abp['id'] ?></td>
                                <td><?= htmlspecialchars((string)($abp['nombre'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($abp['email'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($abp['rut_abogado'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($abp['especialidad'] ?? '')) ?></td>
                                <td><?php $pctAbp = function_exists('lawyerProfileCompletionPercent') ? lawyerProfileCompletionPercent((array)$abp) : 0; ?><span class="badge"><?= (int)$pctAbp ?>%</span></td>
                                <td><span class="badge"><?= (int)($abp['vistas_abogados'] ?? 0) ?></span></td>
                                <td>
                                    <?php $rv = trim((string)($abp['rut_validacion_manual'] ?? '')); ?>
                                    <?php if ((int)($abp['activo'] ?? 0) === 1): ?><span class="badge" style="color:#c4ffd9;border-color:rgba(52,199,89,.35);margin-right:4px;">Publicado</span><?php else: ?><span class="badge" style="margin-right:4px;">Oculto</span><?php endif; ?>
                                    <?php if ($rv === 'si'): ?>
                                        <span class="badge" style="color:#c4ffd9;border-color:rgba(52,199,89,.35);">Sí, es abogado</span>
                                    <?php elseif ($rv === 'no'): ?>
                                        <span class="badge" style="color:#ffd4dc;border-color:rgba(255,82,119,.35);">No es abogado</span>
                                    <?php else: ?>
                                        <span class="badge">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <form method="POST" action="/admin/usuario/<?= (int)$abp['id'] ?>/accion">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                                        <div class="tiny-row">
                                            <button class="btn btn-primary" type="submit" name="accion" value="rut_validado_si">RUT Sí</button>
                                            <button class="btn btn-ghost" type="submit" name="accion" value="rut_validado_no">RUT No</button>
                                        </div>
                                        <div class="tiny-row">
                                            <button class="btn btn-ghost" type="submit" name="accion" value="rut_validado_pendiente">Pendiente</button>
                                        <button class="btn btn-ghost" type="submit" name="accion" value="hacer_cliente">Revocar</button>
                                            <button class="btn btn-ghost" type="submit" name="accion" value="hacer_cliente">Revocar</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list">
                    <?php if (empty($abogados_promovidos ?? [])): ?>
                        <div class="mobile-item"><div class="v">Sin abogados promovidos.</div></div>
                    <?php else: foreach (($abogados_promovidos ?? []) as $abp): ?>
                        <?php $rv = trim((string)($abp['rut_validacion_manual'] ?? '')); ?>
                        <div class="mobile-item">
                            <div class="split">
                                <div class="row"><div class="k">ID</div><div class="v">#<?= (int)$abp['id'] ?></div></div>
                                <div class="row"><div class="k">Estado</div><div class="v">
                                    <?= ((int)($abp['activo'] ?? 0) === 1) ? 'Publicado' : 'Oculto' ?> · <?php if ($rv === 'si'): ?>RUT Sí<?php elseif ($rv === 'no'): ?>RUT No<?php else: ?>RUT pendiente<?php endif; ?>
                                </div></div>
                            </div>
                            <div class="row"><div class="k">Nombre</div><div class="v"><?= htmlspecialchars((string)($abp['nombre'] ?? '')) ?></div></div>
                            <div class="row"><div class="k">Email</div><div class="v"><?= htmlspecialchars((string)($abp['email'] ?? '')) ?></div></div>
                            <div class="split">
                                <div class="row"><div class="k">RUT</div><div class="v"><?= htmlspecialchars((string)($abp['rut_abogado'] ?? '—')) ?></div></div>
                                <div class="row"><div class="k">Materia</div><div class="v"><?= htmlspecialchars((string)($abp['especialidad'] ?? '—')) ?></div></div>
                            </div>
                            <div class="row"><div class="k">Perfil %</div><div class="v"><?php $pctAbp = function_exists('lawyerProfileCompletionPercent') ? lawyerProfileCompletionPercent((array)$abp) : 0; ?><?= (int)$pctAbp ?>%</div></div>
                            <div class="row"><div class="k">Vistas entre abogados</div><div class="v"><?= (int)($abp['vistas_abogados'] ?? 0) ?></div></div>
                            <div class="actions-cell">
                                <form method="POST" action="/admin/usuario/<?= (int)$abp['id'] ?>/accion">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                                    <div class="tiny-row">
                                        <button class="btn btn-primary" type="submit" name="accion" value="rut_validado_si">RUT Sí</button>
                                        <button class="btn btn-ghost" type="submit" name="accion" value="rut_validado_no">RUT No</button>
                                        <button class="btn btn-ghost" type="submit" name="accion" value="rut_validado_pendiente">Pendiente</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </section>

            <section class="card">
                <h2>Cuentas (últimas 50)</h2>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>ID público</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Materia</th><th>Submaterias</th><th>2da materia</th><th>Submaterias 2da</th><th>FAQ perfil</th><th>WhatsApp</th><th>Profesional</th><th>Verificado</th><th>Destacado</th><th>Alta</th><th>Superpoderes</th></tr></thead>
                        <tbody>
                        <?php foreach (($cuentas ?? []) as $c): ?>
                            <?php
                                $submateriasAdmin = [];
                                if (!empty($c['submaterias'])) {
                                    $tmp = json_decode((string)$c['submaterias'], true);
                                    if (is_array($tmp)) {
                                        $submateriasAdmin = array_values(array_filter(array_map('strval', $tmp)));
                                    }
                                }
                                $submateriasAdminSec = [];
                                if (!empty($c['submaterias_secundarias'])) {
                                    $tmpSec = json_decode((string)$c['submaterias_secundarias'], true);
                                    if (is_array($tmpSec)) {
                                        $submateriasAdminSec = array_values(array_filter(array_map('strval', $tmpSec)));
                                    }
                                }
                                $faqAdmin = [];
                                if (!empty($c['faq_personalizadas_json'])) {
                                    $tmpFaqAdmin = json_decode((string)$c['faq_personalizadas_json'], true);
                                    if (is_array($tmpFaqAdmin)) {
                                        foreach ($tmpFaqAdmin as $fq) {
                                            if (!is_array($fq)) continue;
                                            $q = trim((string)($fq['q'] ?? ''));
                                            if ($q !== '') $faqAdmin[] = $q;
                                        }
                                    }
                                }
                            ?>
                            <tr>
                                <td><?= (int)$c['id'] ?></td>
                                <td><?= !empty($c['abogado_public_id']) ? htmlspecialchars((string)$c['abogado_public_id']) : '—' ?></td>
                                <td><?= htmlspecialchars((string)$c['nombre']) ?></td>
                                <td><?= htmlspecialchars((string)$c['email']) ?></td>
                                <td><?= htmlspecialchars((string)$c['rol']) ?>
                                    <?php $cPct = function_exists('lawyerProfileCompletionPercent') ? lawyerProfileCompletionPercent((array)$c) : 0; $cRv = trim((string)($c['rut_validacion_manual'] ?? '')); ?>
                                    <?php if (($c['rol'] ?? '') === 'abogado'): ?>
                                        <div style="margin-top:4px; display:grid; gap:4px;">
                                            <span class="badge" style="width:max-content;"><?= (int)$cPct ?>% <?= $cPct >= 80 ? 'Listo' : 'Incompleto' ?></span>
                                            <span class="badge" style="width:max-content;"><?= ((int)($c['activo'] ?? 0) === 1) ? 'Publicado' : 'Oculto' ?></span>
                                            <?php if ($cRv === ''): ?><span class="badge" style="width:max-content;color:#ffe7a3;border-color:rgba(255,193,7,.35);">RUT pendiente</span><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars((string)($c['especialidad'] ?? '')) ?></td>
                                <td style="max-width:240px; white-space:normal;"><?= htmlspecialchars(implode(' · ', array_slice($submateriasAdmin, 0, 10))) ?></td>
                                <td><?= htmlspecialchars((string)($c['materia_secundaria'] ?? '')) ?></td>
                                <td style="max-width:240px; white-space:normal;"><?= htmlspecialchars(implode(' · ', array_slice($submateriasAdminSec, 0, 10))) ?></td>
                                <td style="max-width:220px; white-space:normal;">
                                    <?php if (!empty($faqAdmin)): ?>
                                        <?= htmlspecialchars(implode(' · ', array_slice($faqAdmin, 0, 2))) ?>
                                        <?php if (count($faqAdmin) > 2): ?> <span style="color:var(--app-muted)">+<?= count($faqAdmin) - 2 ?></span><?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:var(--app-muted)">Genérica</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= !empty($c['whatsapp']) ? '+56 ' . htmlspecialchars((string)$c['whatsapp']) : '' ?></td>
                                <td><?= !empty($c['solicito_habilitacion_abogado']) || !empty($c['abogado_habilitado']) ? 'Sí' : 'No' ?></td>
                                <td><?= !empty($c['abogado_verificado']) ? 'Sí' : 'No' ?></td>
                                <td><?= !empty($c['destacado_hasta']) ? htmlspecialchars((string)$c['destacado_hasta']) : '—' ?></td>
                                <td><?= htmlspecialchars((string)$c['created_at']) ?></td>
                                <td class="actions-cell">
                                    <form method="POST" action="/admin/usuario/<?= (int)$c['id'] ?>/accion">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                                        <div class="tiny-row">
                                            <button class="btn btn-ghost" type="submit" name="accion" value="hacer_cliente">Cliente</button>
                                            <button class="btn btn-ghost" type="submit" name="accion" value="hacer_abogado">Abogado</button>
                                        </div>
                                        <div class="tiny-row">
                                            <input class="tiny-input" type="number" min="1" max="720" name="horas_destacado" value="168">
                                            <button class="btn btn-primary" type="submit" name="accion" value="destacar">Destacar</button>
                                            <button class="btn btn-ghost" type="submit" name="accion" value="quitar_destacado">Quitar</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mobile-list">
                    <?php foreach (($cuentas ?? []) as $c): ?>
                        <?php
                            $submateriasAdmin = [];
                            if (!empty($c['submaterias'])) {
                                $tmp = json_decode((string)$c['submaterias'], true);
                                if (is_array($tmp)) $submateriasAdmin = array_values(array_filter(array_map('strval', $tmp)));
                            }
                            $submateriasAdminSec = [];
                            if (!empty($c['submaterias_secundarias'])) {
                                $tmpSec = json_decode((string)$c['submaterias_secundarias'], true);
                                if (is_array($tmpSec)) $submateriasAdminSec = array_values(array_filter(array_map('strval', $tmpSec)));
                            }
                            $faqAdmin = [];
                            if (!empty($c['faq_personalizadas_json'])) {
                                $tmpFaqAdmin = json_decode((string)$c['faq_personalizadas_json'], true);
                                if (is_array($tmpFaqAdmin)) {
                                    foreach ($tmpFaqAdmin as $fq) {
                                        if (!is_array($fq)) continue;
                                        $q = trim((string)($fq['q'] ?? ''));
                                        if ($q !== '') $faqAdmin[] = $q;
                                    }
                                }
                            }
                        ?>
                        <div class="mobile-item">
                            <div class="split">
                                <div class="row"><div class="k">ID</div><div class="v">#<?= (int)$c['id'] ?></div></div>
                                <div class="row"><div class="k">Rol / Estado</div><div class="v"><?= htmlspecialchars((string)$c['rol']) ?><?php if (($c['rol'] ?? '') === 'abogado'): ?><?php $cPct = function_exists('lawyerProfileCompletionPercent') ? lawyerProfileCompletionPercent((array)$c) : 0; $cRv = trim((string)($c['rut_validacion_manual'] ?? '')); ?> · <?= (int)$cPct ?>% · <?= ((int)($c['activo'] ?? 0) === 1) ? 'Publicado' : 'Oculto' ?><?= $cRv === '' ? ' · RUT pendiente' : '' ?><?php endif; ?></div></div>
                            </div>
                            <div class="row"><div class="k">Nombre</div><div class="v"><?= htmlspecialchars((string)$c['nombre']) ?></div></div>
                            <div class="row"><div class="k">Email</div><div class="v"><?= htmlspecialchars((string)$c['email']) ?></div></div>
                            <div class="row"><div class="k">Materia principal</div><div class="v"><?= htmlspecialchars((string)($c['especialidad'] ?? '—')) ?></div></div>
                            <?php if (!empty($submateriasAdmin)): ?><div class="row"><div class="k">Submaterias</div><div class="v muted"><?= htmlspecialchars(implode(' · ', array_slice($submateriasAdmin, 0, 5))) ?></div></div><?php endif; ?>
                            <?php if (!empty($c['materia_secundaria'])): ?><div class="row"><div class="k">2da materia</div><div class="v"><?= htmlspecialchars((string)$c['materia_secundaria']) ?></div></div><?php endif; ?>
                            <?php if (!empty($submateriasAdminSec)): ?><div class="row"><div class="k">Submaterias 2da</div><div class="v muted"><?= htmlspecialchars(implode(' · ', array_slice($submateriasAdminSec, 0, 5))) ?></div></div><?php endif; ?>
                            <div class="split">
                                <div class="row"><div class="k">Profesional</div><div class="v"><?= !empty($c['solicito_habilitacion_abogado']) || !empty($c['abogado_habilitado']) ? 'Sí' : 'No' ?></div></div>
                                <div class="row"><div class="k">Verificado</div><div class="v"><?= !empty($c['abogado_verificado']) ? 'Sí' : 'No' ?></div></div>
                            </div>
                            <div class="row"><div class="k">FAQ</div><div class="v muted"><?php if (!empty($faqAdmin)): ?><?= htmlspecialchars(implode(' · ', array_slice($faqAdmin, 0, 2))) ?><?php if (count($faqAdmin) > 2): ?> (+<?= count($faqAdmin)-2 ?>)<?php endif; ?><?php else: ?>Genérica<?php endif; ?></div></div>
                            <div class="actions-cell">
                                <form method="POST" action="/admin/usuario/<?= (int)$c['id'] ?>/accion">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                                    <div class="tiny-row">
                                        <button class="btn btn-ghost" type="submit" name="accion" value="hacer_cliente">Cliente</button>
                                        <button class="btn btn-ghost" type="submit" name="accion" value="hacer_abogado">Abogado</button>
                                    </div>
                                    <div class="tiny-row">
                                        <input class="tiny-input" type="number" min="1" max="720" name="horas" value="168">
                                        <button class="btn btn-primary" type="submit" name="accion" value="destacar">Destacar</button>
                                        <button class="btn btn-ghost" type="submit" name="accion" value="quitar_destacado">Quitar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="card">
                <h2>Últimos leads</h2>
                <div class="tiny-row" style="margin-bottom:8px;">
                    <a class="btn <?= empty($lead_stage) ? 'btn-primary' : 'btn-ghost' ?>" href="/admin">Todos</a>
                    <a class="btn <?= (($lead_stage ?? '') === 'activo') ? 'btn-primary' : 'btn-ghost' ?>" href="/admin?lead_stage=activo">Activos</a>
                    <a class="btn <?= (($lead_stage ?? '') === 'papelera') ? 'btn-primary' : 'btn-ghost' ?>" href="/admin?lead_stage=papelera">Papelera</a>
                </div>
                <?php
                    $lead_filter_active = ($lead_filter ?? '') === 'unseen';
                    $stage_query = (!empty($lead_stage) ? 'lead_stage=' . rawurlencode($lead_stage) : '');
                    $filter_unseen_url = '/admin?' . ($stage_query !== '' ? $stage_query . '&' : '') . 'lead_filter=unseen';
                    $filter_clear_url = '/admin' . ($stage_query !== '' ? '?' . $stage_query : '');
                ?>
                <div class="tiny-row lead-filter-row" style="margin-bottom:8px;">
                    <?php if ($lead_filter_active): ?>
                        <a class="btn btn-primary" href="<?= $filter_clear_url ?>">Mostrar todos los leads</a>
                    <?php else: ?>
                        <a class="btn btn-ghost" href="<?= $filter_unseen_url ?>">Mostrar solo leads nuevos/no vistos</a>
                    <?php endif; ?>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>ID</th><th>Fecha</th><th>Abogado</th><th>Cliente</th><th>WhatsApp cliente</th><th>Canal</th><th>Retención</th><th>Estado</th><th>Acción</th></tr></thead>
                        <tbody>
                        <?php if (empty($leads)): ?>
                            <tr><td colspan="9">Sin leads.</td></tr>
                        <?php else: foreach ($leads as $l): ?>
                            <?php
                                $leadBadges = [];
                                $leadStageActive = (($l['retention_stage'] ?? 'activo') !== 'papelera');
                                if (empty($l['abogado_vio_at']) && $leadStageActive) {
                                    $leadBadges[] = 'Sin ver';
                                }
                                if (empty($l['respaldado_at'])) {
                                    $leadBadges[] = 'Backup pendiente';
                                }
                                if (!empty($l['activo_hasta'])) {
                                    $leadBadges[] = 'Retención hasta ' . $formatLeadDate($l['activo_hasta']);
                                } elseif (!empty($l['papelera_hasta'])) {
                                    $leadBadges[] = 'Papelera hasta ' . $formatLeadDate($l['papelera_hasta']);
                                }
                                $timelineItems = [
                                    'Creado' => $l['fecha_revelado'],
                                    'Última' => $l['estado_updated_at'] ?? $l['fecha_cierre'] ?? '',
                                    'Caduca' => !empty($l['activo_hasta']) ? $l['activo_hasta'] : ($l['papelera_hasta'] ?? ''),
                                ];
                            ?>
                            <tr>
                                <td><?= (int)$l['id'] ?></td>
                                <td><?= htmlspecialchars((string)$l['fecha_revelado']) ?></td>
                                <td><?= htmlspecialchars((string)$l['abogado_nombre']) ?><br><span style="color:var(--app-muted)"><?= htmlspecialchars((string)$l['abogado_email']) ?></span></td>
                                <td><?= htmlspecialchars((string)$l['cliente_nombre']) ?></td>
                                <td><?= !empty($l['cliente_whatsapp']) ? '+56 ' . htmlspecialchars((string)$l['cliente_whatsapp']) : '' ?></td>
                                <td><?= htmlspecialchars((string)$l['medio_contacto']) ?></td>
                                <td>
                                    <span class="badge"><?= htmlspecialchars((string)($l['retention_stage'] ?? 'activo')) ?></span>
                                    <?php if (!empty($l['papelera_hasta'])): ?><div style="color:var(--app-muted); font-size:10px; margin-top:4px;">hasta <?= htmlspecialchars((string)$l['papelera_hasta']) ?></div><?php endif; ?>
                                    <?php if (!empty($l['activo_hasta'])): ?><div style="color:var(--app-muted); font-size:10px; margin-top:4px;">activo hasta <?= htmlspecialchars((string)$l['activo_hasta']) ?></div><?php endif; ?>
                                    <?php if (!empty($leadBadges)): ?>
                                        <div class="lead-health">
                                            <?php foreach ($leadBadges as $badge): ?>
                                                <span><?= htmlspecialchars((string)$badge) ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge"><?= htmlspecialchars((string)($l['estado'] ?? '')) ?></span>
                                    <div class="lead-timeline">
                                        <?php foreach ($timelineItems as $label => $value):
                                            $formatted = $formatLeadDate($value);
                                            if ($formatted === '') continue;
                                        ?>
                                            <span><?= htmlspecialchars($label . ': ' . $formatted) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (($l['retention_stage'] ?? '') === 'papelera'): ?>
                                    <form method="POST" action="/admin/lead/<?= (int)$l['id'] ?>/restaurar">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                                        <button class="btn btn-ghost" type="submit">Restaurar</button>
                                    </form>
                                    <?php else: ?>—<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </section>
    </main>
<script type="module" src="/js/admin-app.js"></script>
</body>
</html>
