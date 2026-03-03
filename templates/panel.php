<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi panel | Tu Estudio Juridico</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        .app-grid-2 { display:grid; gap:10px; grid-template-columns:1fr !important; }
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 0 14px 28px; }
        .shell { margin-top: 12px; display: grid; gap: 10px; }
        .panel-actions { display:grid; gap:8px; }
        .panel-actions .btn, .panel-actions form { width:100%; }
        .panel-actions form { display:grid; }
        .panel-meta { display:grid; gap:8px; grid-template-columns:1fr; }
        .mini-note { border:1px solid rgba(121,168,255,.18); border-radius:14px; padding:10px; background:rgba(121,168,255,.06); }
        .mini-note strong { display:block; font-size:12px; margin-bottom:4px; }
        .mini-note p { margin:0; color:var(--app-muted); font-size:11px; line-height:1.4; }
        .flow-row { display:grid; gap:8px; grid-template-columns:1fr; }
        .flow-step { border:1px solid var(--app-line); border-radius:12px; padding:8px; background:rgba(255,255,255,.02); }
        .flow-step small { display:block; color:var(--app-muted); font-size:10px; }
        .flow-step strong { font-size:12px; line-height:1.15; }
        .lawyer-banner { border:1px solid rgba(247,205,92,.28); border-radius:14px; padding:12px; background:linear-gradient(180deg, rgba(247,205,92,.10), rgba(247,205,92,.04)); display:grid; gap:10px; }
        .lawyer-banner .head { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
        .lawyer-banner .head strong { font-size:13px; }
        .lawyer-banner .sub { color: var(--app-muted); font-size:12px; line-height:1.35; }
        .lawyer-stats { display:grid; gap:8px; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .lawyer-stat { border:1px solid rgba(255,255,255,.08); border-radius:12px; padding:8px; background:rgba(255,255,255,.02); }
        .lawyer-stat small { display:block; color:var(--app-muted); font-size:10px; margin-bottom:2px; }
        .lawyer-stat strong { font-size:15px; line-height:1; }
        .lawyer-tools { display:grid; gap:8px; grid-template-columns: 1fr; }
        .lawyer-tools .btn, .lawyer-tools form { width:100%; }
        .lawyer-tools form { display:grid; }
        .badge-ok, .badge-warn, .badge-off { display:inline-flex; align-items:center; gap:6px; border-radius:999px; padding:5px 10px; font-size:11px; font-weight:700; }
        .badge-ok { background:rgba(34,197,94,.12); border:1px solid rgba(34,197,94,.28); color:#d8ffe8; }
        .badge-warn { background:rgba(250,204,21,.10); border:1px solid rgba(250,204,21,.28); color:#fff2bf; }
        .badge-off { background:rgba(248,113,113,.10); border:1px solid rgba(248,113,113,.25); color:#ffd9d9; }
        .future-grid { display:grid; gap:8px; grid-template-columns: 1fr; }
        .future-card { border:1px dashed rgba(255,255,255,.16); border-radius:12px; padding:10px; background:rgba(255,255,255,.015); }
        .future-card strong { display:block; font-size:12px; margin-bottom:4px; }
        .future-card small { color: var(--app-muted); line-height:1.35; }
        @media (max-width: 820px) {
            .panel-meta { grid-template-columns:1fr; }
            .flow-row { grid-template-columns:1fr; }
            .lawyer-tools, .future-grid { grid-template-columns:1fr; }
            .lawyer-stats { grid-template-columns: repeat(2,minmax(0,1fr)); }
        }

        /* Panel UI pass: unified + simplified */
        body { padding-bottom: 88px; }
        .wrap { max-width: 1080px; }
        .shell { gap: 10px; }
        .app-toolbar, .app-hero-card, .app-list-card, .lawyer-banner, .mini-note, .flow-step, .future-card {
            background: rgba(13,16,22,.92) !important;
            border: 1px solid rgba(255,255,255,.08) !important;
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(0,0,0,.12);
        }
        .app-hero-card h1 { font-size: 18px; margin-bottom: 4px; }
        .app-hero-card p { color: var(--app-muted); font-size: 13px; line-height: 1.45; }
        .lawyer-banner { gap: 10px; }
        .lawyer-banner .head strong { font-size: 14px; }
        .lawyer-banner .sub { font-size: 12px; line-height: 1.35; }
        .lawyer-stat { background: rgba(255,255,255,.02); }
        .lawyer-stat strong { font-size: 16px; }
        .app-list-card .body h2 { font-size: 15px; margin-bottom: 4px; }
        .app-list-card .body p { color: var(--app-muted); font-size: 12px; line-height: 1.4; }
        .mini-note p { font-size: 12px; }
        .flow-step { padding: 10px; }
        .flow-step strong { font-size: 12px; }
        .bottom-nav { min-height: 56px; }
        .bottom-nav a { min-height: 38px; font-size: 12px; }
        @media (max-width: 820px) {
            .wrap { padding: 0 10px 16px; }
            .lawyer-stats { grid-template-columns: 1fr 1fr; }
            .lawyer-tools { gap: 7px; }
        }


        /* Solid lawyer-flow surfaces (no blur / no glass) */
        .app-toolbar, .app-hero-card, .app-list-card, .lawyer-banner, .mini-note, .flow-step, .future-card {
            background:#111722 !important;
            border-color:#253246 !important;
            box-shadow:none !important;
        }
        .lawyer-banner { background:#151b24 !important; }
        .lawyer-stat { background:#161d28 !important; border-color:#2a3648 !important; }
        .mini-note { background:#141c28 !important; border-color:#29405d !important; }
        .flow-step { background:#121924 !important; }
        .future-card { background:#10161f !important; border-style:solid !important; }
        .nav, .nav-inner { backdrop-filter:none !important; }
        .nav { background:#0e141d !important; border-bottom:1px solid #243143 !important; }

        /* LinkedIn-inspired readability override */
        body {
            color: #1f2937 !important;
        }
        .app-toolbar, .app-hero-card, .app-list-card, .lawyer-banner, .mini-note, .flow-step, .future-card, .lawyer-stat {
            background: #ffffff !important;
            border-color: rgba(0,0,0,.12) !important;
            box-shadow: 0 1px 2px rgba(0,0,0,.08), 0 10px 24px rgba(15,23,42,.06) !important;
            color: #1f2937 !important;
        }
        .app-hero-card p,
        .lawyer-banner .sub,
        .mini-note p,
        .flow-step small,
        .future-card small {
            color: #5f6b7a !important;
        }
        .app-hero-card h1,
        .app-list-card .body h2,
        .app-list-card .body p,
        .app-list-card .head,
        .app-segment,
        .app-toolbar .title,
        .app-toolbar .sub {
            color: #1f2937 !important;
        }
        .app-list-card .body p,
        .app-toolbar .sub {
            color: #5f6b7a !important;
        }
        .app-segment {
            background: #e8f3ff !important;
            border-color: rgba(10,102,194,.24) !important;
        }
        .badge-ok { background:#e8f7ee !important; border-color: rgba(15,107,58,.25) !important; color:#0f6b3a !important; }
        .badge-warn { background:#fff7e8 !important; border-color: rgba(138,86,0,.25) !important; color:#8a5600 !important; }
        .badge-off { background:#ffebeb !important; border-color: rgba(176,30,30,.24) !important; color:#8f1f1f !important; }
        .nav { background: rgba(255,255,255,.96) !important; border-bottom:1px solid rgba(0,0,0,.12) !important; }

    </style>
    <link rel="stylesheet" href="/app-shell.css?v=2026022705">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<?php
    $themeEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
    $panelIsAdmin = ($themeEmail === 'gmcalderonlewin@gmail.com');
    $panelTheme = 'theme-black' . ($panelIsAdmin ? ' theme-admin' : ' theme-olive');
    $isLawyerPanel = !empty($can_abogado);
    $profileSlug = trim((string)($user['slug'] ?? ''));
    $publicProfilePath = $profileSlug !== '' ? ('/' . ltrim($profileSlug, '/')) : '';
    $rutState = strtolower(trim((string)($user['rut_validacion_manual'] ?? '')));
    $pjudBadge = !empty($lawyer_status['verified'])
        ? ['class' => 'badge-ok', 'text' => '✅ Verificado por admin (PJUD)']
        : (($rutState === 'no')
            ? ['class' => 'badge-off', 'text' => '❌ No verificado por admin']
            : ['class' => 'badge-warn', 'text' => '🟡 Pendiente revisión admin/PJUD']);
    $leadCounts = is_array($lawyer_lead_counts ?? null) ? $lawyer_lead_counts : [
        'interesados' => 0, 'no_contactado' => 0, 'contactado' => 0, 'cerrados' => 0, 'archivados' => 0
    ];
?>
<body class="<?= htmlspecialchars($panelTheme) ?>">
    <nav class="nav">
        <div class="nav-inner">
            <a class="brand" href="/explorar">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/explorar">Explorar</a>
                <a class="active" href="/panel">Mi panel</a>
            </div>
            <div class="nav-actions">
                <a class="btn btn-ghost" href="/logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <section class="shell app-stack">
            <section class="app-toolbar">
                <div>
                    <div class="title"><?= $isLawyerPanel ? 'Panel profesional' : 'Mi panel' ?></div>
                    <div class="sub"><?= $isLawyerPanel ? 'Edita tu perfil, revisa interesados y entra a tu CRM desde aquí.' : 'Explora abogados. El panel profesional está separado.' ?></div>
                </div>
                <span class="dot" aria-hidden="true"></span>
            </section>

            <section class="app-hero-card">
                <?php if ($isLawyerPanel): ?>
                    <h1>Panel profesional activo</h1>
                    <p>Edita tu perfil, comparte tu link y revisa interesados. La verificación PJUD se muestra aquí cuando admin la marque.</p>
                <?php else: ?>
                    <h1>Tu cuenta sirve para buscar abogado</h1>
                    <p>Filtra por materia y ubicación, abre fichas y contacta directo. Si eres abogado, postulas al programa profesional con este mismo correo.</p>
                <?php endif; ?>
            </section>

            <?php if ($isLawyerPanel): ?>
            <section class="lawyer-banner">
                <div class="head">
                    <strong>Perfil profesional</strong>
                    <span class="<?= htmlspecialchars($pjudBadge['class']) ?>"><?= htmlspecialchars($pjudBadge['text']) ?></span>
                </div>
                <div class="sub">Función 1: editar tu perfil profesional. También puedes compartir tu link y gestionar interesados en tu panel profesional.</div>
                <div class="lawyer-stats">
                    <div class="lawyer-stat"><small>Clientes interesados</small><strong><?= (int)$leadCounts['interesados'] ?></strong></div>
                    <div class="lawyer-stat"><small>Contactados</small><strong><?= (int)$leadCounts['contactado'] ?></strong></div>
                    <div class="lawyer-stat"><small>Cerrados</small><strong><?= (int)$leadCounts['cerrados'] ?></strong></div>
                    <div class="lawyer-stat"><small>Archivados / No cerró</small><strong><?= (int)$leadCounts['archivados'] ?></strong></div>
                </div>
                <div class="lawyer-tools">
                    <a class="btn btn-primary" href="/completar-datos?modo=abogado">Editar perfil profesional</a>
                    <a class="btn btn-ghost" href="/dashboard">Ver leads y CRM</a>
                    <?php if ($publicProfilePath !== ''): ?>
                        <a class="btn btn-ghost" href="<?= htmlspecialchars($publicProfilePath) ?>">Ver mi perfil</a>
                        <button type="button" class="btn btn-ghost" id="copyProfileLinkBtn" data-link="<?= htmlspecialchars($publicProfilePath) ?>">Copiar link de mi perfil</button>
                    <?php else: ?>
                        <a class="btn btn-ghost" href="/mi-tarjeta">Ver mi perfil</a>
                        <button type="button" class="btn btn-ghost" disabled>Link aún no disponible</button>
                    <?php endif; ?>
                    <a class="btn btn-ghost" href="/dashboard/leads">Ver leads</a>
                    <a class="btn btn-ghost" href="/dashboard/crm">CRM (contactados y archivados)</a>
                </div>
            </section>
            <?php endif; ?>

            <section class="app-grid-2">
                <article class="app-list-card">
                    <div class="head">
                        <span class="app-segment">Cliente</span>
                        <span class="app-segment">Modo visor</span>
                    </div>
                    <div class="body">
                        <h2>Explorar abogados</h2>
                        <p>Busca por materia, región o comuna. Abre fichas y compara perfiles.</p>
                        <div class="panel-actions">
                            <a class="btn btn-primary" href="/explorar">Explorar abogados</a>
                            <?php if (!empty($can_cliente)): ?>
                                <a class="btn btn-ghost" href="/inicio">Cómo funciona</a>
                            <?php else: ?>
                                <form method="POST" action="/usar-modo-cliente">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn btn-ghost">Activar modo visor</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>

                <article class="app-list-card">
                    <div class="head">
                        <span class="app-segment">Profesional</span>
                        <?php if (!empty($lawyer_status['verified'])): ?>
                            <span class="app-segment">Verificado</span>
                        <?php elseif (!empty($lawyer_status['requested'])): ?>
                            <span class="app-segment">Pendiente</span>
                        <?php else: ?>
                            <span class="app-segment">No habilitado</span>
                        <?php endif; ?>
                    </div>
                    <div class="body">
                        <h2>Programa profesional</h2>
                        <p>Tu cuenta sirve para cliente y perfil profesional. Desde aquí gestionas la publicación y tus leads.</p>
                        <div class="panel-actions">
                            <?php if (!empty($can_abogado)): ?>
                                <a class="btn btn-primary" href="/dashboard">Ir a panel abogado</a>
                                <a class="btn btn-ghost" href="/completar-datos?modo=abogado">Editar perfil profesional</a>
                            <?php else: ?>
                                <form method="POST" action="/solicitar-habilitacion-abogado">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                    <button type="submit" class="btn btn-primary">Solicitar acceso profesional</button>
                                </form>
                                <a class="btn btn-ghost" href="/acceso-profesional">Ver acceso profesional</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </section>

            <?php if ($isLawyerPanel): ?>
            <section class="future-grid">
                <div class="future-card">
                    <strong>Herramienta futura #1</strong>
                    <small>Espacio reservado para automatizaciones de seguimiento, recordatorios o plantillas de respuesta.</small>
                </div>
                <div class="future-card">
                    <strong>Herramienta futura #2</strong>
                    <small>Espacio reservado para analítica de conversión, recomendaciones o campañas de visibilidad.</small>
                </div>
            </section>
            <?php endif; ?>

            <section class="panel-meta">
                <div class="mini-note">
                    <strong>Flujo cliente (simple)</strong>
                    <p>Entra con Google, explora abogados por materia y ubicación, abre perfiles y contacta directamente.</p>
                </div>
                <div class="mini-note">
                    <strong>Flujo abogado (aprobación)</strong>
                    <p>Aplicas con tu correo Google. Si aceptamos tu solicitud, ese correo queda habilitado como abogado en Tu Estudio Juridico.</p>
                </div>
                <?php if (!empty($is_admin)): ?>
                <div class="mini-note">
                    <strong>Administrador</strong>
                    <p>Tu correo tiene acceso admin. Revisa cuentas, postulaciones y leads.</p>
                    <div style="margin-top:8px;">
                        <a class="btn btn-primary" href="/admin">Abrir dashboard admin</a>
                    </div>
                </div>
                <?php endif; ?>
            </section>

            <section class="flow-row">
                <div class="flow-step"><small>Paso 1</small><strong>Filtra por materia</strong></div>
                <div class="flow-step"><small>Paso 2</small><strong>Filtra por ubicación</strong></div>
                <div class="flow-step"><small>Paso 3</small><strong>Abre y contacta perfil</strong></div>
            </section>
        </section>
    </main>
    <div class="bottom-nav app-primary-nav">
        <a href="/explorar">Explorar</a>
        <a href="/panel" class="app-primary-cta-link">Mi panel</a>
    </div>
    <?php if ($isLawyerPanel): ?>
    <script>
        (function () {
            var btn = document.getElementById('copyProfileLinkBtn');
            if (!btn) return;
            btn.addEventListener('click', function () {
                var raw = btn.getAttribute('data-link') || '';
                if (!raw) return;
                var full = raw.startsWith('http') ? raw : (window.location.origin + raw);
                function done(label) {
                    var original = btn.textContent;
                    btn.textContent = label;
                    setTimeout(function(){ btn.textContent = original; }, 1400);
                }
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(full).then(function(){ done('Link copiado'); }).catch(function(){ done('No se pudo copiar'); });
                } else {
                    var ta = document.createElement('textarea');
                    ta.value = full;
                    document.body.appendChild(ta);
                    ta.select();
                    try { document.execCommand('copy'); done('Link copiado'); } catch(e) { done('No se pudo copiar'); }
                    document.body.removeChild(ta);
                }
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>
