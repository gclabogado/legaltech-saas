<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil abogado | Tu Estudio Juridico</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <meta name="description" content="Abogado especialista en <?= htmlspecialchars($abogado['especialidad']) ?>. Contacto directo.">
    <link rel="stylesheet" href="/profile-page.css?v=2026030210">
    <link rel="stylesheet" href="/app-shell.css?v=2026022705">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<?php
$profileThemeEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
$viewerIsLawyer = false;
$viewerIsOwner = false;
if (!empty($viewer) && is_array($viewer)) {
    $viewerIsLawyer = !empty($viewer['abogado_habilitado']) || !empty($viewer['abogado_verificado']) || (($viewer['rol'] ?? '') === 'abogado');
    $viewerIsOwner = ((int)($viewer['id'] ?? 0) === (int)($abogado['id'] ?? 0));
}
$ownerProfileActive = (int)($abogado['activo'] ?? 0) === 1;
?>
<body class="theme-black<?= ($profileThemeEmail === 'gmcalderonlewin@gmail.com') ? ' theme-admin' : ' theme-olive' ?>" style="--brand-accent: <?= htmlspecialchars($brandAccentHex) ?>; --brand-accent-soft: <?= htmlspecialchars($brandAccentBg) ?>; --brand-accent-bg: <?= htmlspecialchars($brandAccentBg) ?>; --brand-accent-ink: <?= htmlspecialchars($brandAccentInk) ?>;">
    <div class="profile-scroll-fx" aria-hidden="true"></div>
    <?php
    $submateriasPerfil = [];
    $submateriasSecPerfil = [];
    $universidadTop = false;
    if (!empty($abogado['submaterias'])) {
        $decodedSubmaterias = json_decode((string)$abogado['submaterias'], true);
        if (is_array($decodedSubmaterias)) {
            $submateriasPerfil = array_values(array_filter(array_map('strval', $decodedSubmaterias)));
        }
    }
    if (!empty($abogado['submaterias_secundarias'])) {
        $decodedSubmateriasSec = json_decode((string)$abogado['submaterias_secundarias'], true);
        if (is_array($decodedSubmateriasSec)) {
            $submateriasSecPerfil = array_values(array_filter(array_map('strval', $decodedSubmateriasSec)));
        }
    }
    $uniProfileRaw = trim((string)($abogado['universidad'] ?? ''));
    $uniProfile = function_exists('mb_strtolower') ? mb_strtolower($uniProfileRaw, 'UTF-8') : strtolower($uniProfileRaw);
    if ($uniProfile !== '') {
        $universidadTop = str_contains($uniProfile, 'universidad de chile')
            || str_contains($uniProfile, 'pontificia universidad catolica de chile')
            || str_contains($uniProfile, 'pontificia universidad católica de chile')
            || str_contains($uniProfile, 'puc chile')
            || str_contains($uniProfile, 'uc chile');
    }
    $mediosPagoPerfil = [];
    $brandColorKey = trim((string)($abogado['color_marca'] ?? 'azul'));
    $brandColorMap = [
        'azul' => 'rgba(92,142,214,.85)',
        'verde' => 'rgba(63,208,168,.9)',
        'dorado' => 'rgba(216,194,165,.92)',
        'rosa' => 'rgba(239,109,168,.9)',
        'vino' => 'rgba(180,91,122,.9)',
        'grafito' => 'rgba(127,142,163,.82)',
    ];
    $brandRingProfile = $brandColorMap[$brandColorKey] ?? $brandColorMap['azul'];
    $brandAccentMap = [
        'azul' => ['#6ea8ff', 'rgba(110,168,255,.20)', '#e8f2ff'],
        'verde' => ['#4fe0b6', 'rgba(79,224,182,.18)', '#dcfff4'],
        'dorado' => ['#e3c27a', 'rgba(227,194,122,.18)', '#fff3cf'],
        'rosa' => ['#f48fc1', 'rgba(244,143,193,.18)', '#ffe3f1'],
        'vino' => ['#d77c99', 'rgba(215,124,153,.18)', '#ffe2ec'],
        'grafito' => ['#9fb3cc', 'rgba(159,179,204,.16)', '#edf4ff'],
    ];
    [$brandAccentHex, $brandAccentBg, $brandAccentInk] = $brandAccentMap[$brandColorKey] ?? $brandAccentMap['azul'];
    if (!empty($abogado['medios_pago_json'])) {
        $tmpMedios = json_decode((string)$abogado['medios_pago_json'], true);
        if (is_array($tmpMedios)) {
            $mediosPagoPerfil = array_values(array_filter(array_map('strval', $tmpMedios)));
        }
    }
    $mediosPagoLabels = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'tarjeta_credito' => 'Tarjeta de crédito',
        'tarjeta_debito' => 'Tarjeta de débito',
        'crypto' => 'Crypto',
    ];
    $igUser = str_replace('@', '', trim((string)($abogado['instagram'] ?? '')));
    $ttUser = str_replace('@', '', trim((string)($abogado['tiktok'] ?? '')));
    $fbRaw = trim((string)($abogado['facebook'] ?? ''));
    $liRaw = trim((string)($abogado['linkedin'] ?? ''));
    $buildProfileSummary = static function(array $abg, array $subm): string {
        $nombre = trim((string)($abg['nombre'] ?? ''));
        $uni = trim((string)($abg['universidad'] ?? 'Universidad no informada'));
        $uniPhrase = preg_match('/^universidad\b/i', $uni) ? $uni : ('Universidad ' . $uni);
        $exp = trim((string)($abg['experiencia'] ?? 'experiencia informada'));
        $materia = trim((string)($abg['especialidad'] ?? 'Derecho'));
        $sub = trim((string)($subm[0] ?? ''));
        $txt = 'Abogado ' . ($nombre !== '' ? $nombre : 'disponible') . ', licenciado de ' . $uniPhrase . ', con más de ' . $exp . ' de experiencia se especializa en ' . $materia;
        if ($sub !== '') $txt .= ', específicamente en ' . $sub;
        $txt .= '.';
        return preg_replace('/\s+/u', ' ', trim($txt));
    };
    $resumenPerfil = $buildProfileSummary($abogado, $submateriasPerfil);
    $materiaPerfil = trim((string)($abogado['especialidad'] ?? 'Derecho'));
    $regionPerfil = trim((string)($abogado['regiones_servicio'] ?? 'tu región'));
    $plazaPerfil = trim((string)($abogado['ciudades_plaza'] ?? ''));
    $plazaPrincipal = '';
    if ($plazaPerfil !== '') {
        $partsPlaza = array_values(array_filter(array_map('trim', explode(',', $plazaPerfil))));
        $plazaPrincipal = (string)($partsPlaza[0] ?? '');
    }
    $lugarContenido = $plazaPrincipal !== '' ? $plazaPrincipal : ($regionPerfil !== '' ? $regionPerfil : 'tu zona');
    $materiaLower = function_exists('mb_strtolower') ? mb_strtolower($materiaPerfil, 'UTF-8') : strtolower($materiaPerfil);
    $serviciosConsultadosTitle = 'Servicios legales más consultados en ' . $lugarContenido;
    $serviciosConsultadosDesc = 'Contenido pensado para quien llega con urgencia y necesita decidir rápido. Atención local y coordinación en todo Chile.';
    $serviciosSugeridos = [];
    $submateriasElegidas = array_values(array_unique(array_filter(array_merge($submateriasPerfil, $submateriasSecPerfil))));
    foreach (array_slice($submateriasElegidas, 0, 8) as $smElegida) {
        $smLower = function_exists('mb_strtolower') ? mb_strtolower((string)$smElegida, 'UTF-8') : strtolower((string)$smElegida);
        $serviciosSugeridos[] = [
            'title' => $smElegida,
            'desc' => 'Atención y estrategia legal para casos relacionados con ' . $smLower . ', según antecedentes, urgencia y etapa del caso.'
        ];
    }
    if (empty($serviciosSugeridos)) {
        $serviciosSugeridos = [
            ['title' => 'Orientación y estrategia inicial', 'desc' => 'Revisión breve de antecedentes, urgencia y siguiente paso recomendado para ordenar el caso.'],
            ['title' => 'Revisión de documentos y antecedentes', 'desc' => 'Análisis de citaciones, demandas, contratos, resoluciones o comunicaciones relevantes.'],
            ['title' => 'Representación y gestión del caso', 'desc' => 'Definición de estrategia, presentación de escritos y coordinación de audiencias o trámites.'],
            ['title' => 'Seguimiento y coordinación', 'desc' => 'Actualizaciones por etapas, plazos clave y apoyo para tomar decisiones con más claridad.'],
        ];
    }
    $faqPerfilFallback = [
        ['q' => '¿Qué conviene tener a mano antes de contactar?', 'a' => 'Nombre completo, documentos relevantes, fechas clave, tribunal o institución involucrada y tu objetivo principal. Si no tienes todo, igual podemos orientarte.'],
        ['q' => '¿Atienden urgencias fuera de horario?', 'a' => 'La disponibilidad depende del profesional. Si es urgente, usa llamada o WhatsApp y describe brevemente la situación al primer contacto.'],
        ['q' => '¿Atienden en regiones y Santiago?', 'a' => 'Revisa la región de ejercicio y si presta servicios en todo Chile. Muchos perfiles coordinan atención remota además de atención local.'],
        ['q' => '¿Qué pasa después del primer contacto?', 'a' => 'Podrás coordinar por llamada o WhatsApp, compartir antecedentes y definir si avanzas con asesoría, representación o revisión de documentos.'],
    ];
    $faqPerfil = [];
    if (!empty($abogado['faq_personalizadas_json'])) {
        $tmpFaqCustom = json_decode((string)$abogado['faq_personalizadas_json'], true);
        if (is_array($tmpFaqCustom)) {
            foreach ($tmpFaqCustom as $rowFaq) {
                if (!is_array($rowFaq)) continue;
                $q = trim((string)($rowFaq['q'] ?? ''));
                $a = trim((string)($rowFaq['a'] ?? ''));
                if ($q !== '' && $a !== '') {
                    $faqPerfil[] = ['q' => $q, 'a' => $a];
                }
            }
        }
    }
    if (empty($faqPerfil)) {
        $faqPerfil = $faqPerfilFallback;
    } else {
        $faqPerfil = array_slice($faqPerfil, 0, 4);
    }
    ?>
    <?php if (!empty($mensaje ?? null)): ?>
        <div class="dash-message" data-autodismiss-ms="3500"><?= htmlspecialchars((string)$mensaje) ?></div>
    <?php endif; ?>
    <nav class="nav">
        <div class="nav-inner">
            <a href="/explorar" class="brand">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/explorar">Explorar</a>
            </div>
            <div class="nav-actions">
                <a class="btn btn-ghost" href="/explorar">Volver al directorio</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if ($viewerIsOwner && $viewerIsLawyer): ?>
                        <a class="btn btn-primary" href="/panel">Mi panel</a>
                    <?php endif; ?>
                    <a class="btn btn-ghost" href="/logout">Salir</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="/login-google">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <?php if ($viewerIsLawyer && $viewerIsOwner): ?>
            <section class="lawyer-update-banner" aria-label="Actualizar perfil profesional">
                <div class="txt"><strong><?= $ownerProfileActive ? 'Este es tu perfil público.' : 'Tu perfil está oculto del directorio.' ?></strong> <?= $ownerProfileActive ? 'Manténlo actualizado para recibir más interesados.' : 'Reactívalo con un clic para volver a aparecer en la lista de abogados.' ?>
                    <span class="profile-visibility-badge <?= $ownerProfileActive ? 'is-published' : 'is-hidden' ?>"><?= $ownerProfileActive ? 'Publicado' : 'Oculto' ?></span>
                </div>
                <div class="banner-actions">
                    <a class="btn-mini btn-mini-primary" href="/dashboard/home">Ir al dashboard</a>
                    <a class="btn-mini" href="/completar-datos?modo=abogado">Editar perfil</a>
                    <button type="button" class="btn-mini" id="copyProfileLinkBtn">Copiar link</button>
                    <?php if ($ownerProfileActive): ?>
                        <form method="POST" action="/desactivar-perfil-profesional" onsubmit="return confirm('¿Desactivar temporalmente tu perfil profesional?');" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                            <button type="submit" class="btn-mini btn-mini-danger" style="cursor:pointer;">Desactivar perfil</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/reactivar-perfil-profesional" style="margin:0;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                            <button type="submit" class="btn-mini btn-mini-success" style="cursor:pointer;">Reactivar perfil</button>
                        </form>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
        <section class="profile-shell profile-tone-shell">
            <section class="top-strip profile-top-lite">
                <section class="app-toolbar app-bar">
                    <div>
                        <div class="title">Perfil profesional</div>
                        <div class="sub">Contacto directo y datos públicos del abogado</div>
                    </div>
                    <span class="dot" aria-hidden="true"></span>
                </section>
                <?php if (!empty($abogado['ciudades_plaza'])): ?>
                    <div class="micro-note">📍 Local en <?= htmlspecialchars((string)$abogado['ciudades_plaza']) ?> · zonas donde generalmente tramita y tiene mayor presencia.</div>
                <?php endif; ?>
            </section>
            <section class="hero-card">
                <?php $fotoPerfil = trim((string)($abogado['foto_final'] ?? '')); ?>
                <div class="hero-media<?= $fotoPerfil === '' ? ' demo-photo' : '' ?>" style="--brand-ring: <?= htmlspecialchars($brandRingProfile) ?>">
                    <div class="hero-avatar-wrap">
                        <?php if ($fotoPerfil !== ''): ?>
                            <img src="<?= htmlspecialchars($fotoPerfil) ?>" alt="Foto de perfil" onerror="this.closest('.hero-media')?.classList.add('demo-photo'); this.remove()">
                        <?php else: ?>
                            <div class="hero-avatar-fallback"><?= htmlspecialchars(mb_strtoupper(mb_substr((string)($abogado['nombre'] ?? 'A'), 0, 1))) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="hero-badges">
                        <span class="hero-badge app-badge badge-info"><?= htmlspecialchars($abogado['especialidad'] ?? 'Especialidad') ?></span>
                        <span class="hero-badge app-badge <?= !empty($abogado['abogado_verificado']) ? 'badge-success' : 'badge-warn' ?>"><?= !empty($abogado['abogado_verificado']) ? '✅ Verificado PJUD' : '🟡 PJUD pendiente' ?></span>
                    </div>
                    <div class="hero-overlay">
                        <h1 id="displayName" class="hero-name"><?= $universidadTop ? '⭐ ' : '' ?><?= htmlspecialchars($abogado['nombre'] ?: ('Abogado/a de ' . ($abogado['especialidad'] ?? 'especialidad'))) ?></h1>
                        <div class="hero-sub">
                            <?= $universidadTop ? '⭐ ' : '' ?><?= !empty($abogado['universidad']) ? htmlspecialchars($abogado['universidad']) : 'Universidad no indicada' ?>
                            ·
                            <?= !empty($abogado['experiencia']) ? 'Abogado con ' . htmlspecialchars((string)$abogado['experiencia']) . ' de experiencia' : 'Experiencia comprobable' ?>
                        </div>
                    </div>
                </div>
                <div class="hero-body">
                    <div class="mini-chip-row">
                            <span class="mini-chip app-badge badge-info"><?= htmlspecialchars($abogado['especialidad'] ?? 'Especialidad') ?></span>
                        <?php foreach (array_slice($submateriasPerfil, 0, 3) as $subm): ?>
                            <span class="mini-chip app-badge"><?= htmlspecialchars($subm) ?></span>
                        <?php endforeach; ?>
                        <?php if (!empty($abogado['materia_secundaria'])): ?>
                            <span class="mini-chip app-badge badge-accent">➕ <?= htmlspecialchars((string)$abogado['materia_secundaria']) ?></span>
                        <?php endif; ?>
                        <span class="mini-chip app-badge badge-info"><?= !empty($abogado['cobertura_nacional']) ? 'Atiende todo Chile' : (!empty($abogado['regiones_servicio']) ? htmlspecialchars((string)$abogado['regiones_servicio']) : 'Cobertura local') ?></span>
                        <?php if (!empty($abogado['entrevista_presencial'])): ?><span class="mini-chip app-badge badge-success">🤝 Entrevista presencial</span><?php endif; ?>
                        <span class="mini-chip app-badge <?= !empty($abogado['abogado_verificado']) ? 'badge-success' : 'badge-warn' ?>"><?= !empty($abogado['abogado_verificado']) ? '✅ Verificado PJUD' : '🟡 PJUD en revisión' ?></span>
                        <?php if (!empty($abogado['tiene_postitulo'])): ?><span class="mini-chip app-badge badge-gold">🎓 Postítulo</span><?php endif; ?>
                        <?php if (!empty($abogado['tiene_diplomado'])): ?><span class="mini-chip app-badge badge-gold">📘 Diplomado</span><?php endif; ?>
                    </div>
                    <div class="hero-meta">
                        <div class="meta-box">
                            <strong><?= (int)($abogado['vistas'] ?? 0) ?></strong>
                            <span>Vistas</span>
                        </div>
                        <div class="meta-box">
                            <strong id="profileLikesCount"><?= (int)($abogado['likes'] ?? 0) ?></strong>
                            <span>Likes</span>
                        </div>
                        <div class="meta-box">
                            <strong id="profileRecommendationsCount"><?= (int)($abogado['recomendaciones'] ?? 0) ?></strong>
                            <span>Recomendaciones</span>
                        </div>
                        <?php if ($viewerIsOwner): ?>
                        <div class="meta-box">
                            <strong><?= (int)($abogado['vistas_abogados'] ?? 0) ?></strong>
                            <span>Vistas de abogados</span>
                        </div>
                        <?php endif; ?>
                        <div class="meta-box">
                            <strong><?php $antiguedad = floor((time() - strtotime($abogado['created_at'] ?? 'now')) / (30 * 24 * 60 * 60)); echo max(1, $antiguedad); ?>+</strong>
                            <span>Meses en Lawyers</span>
                        </div>
                    </div>

                    <div class="cta-stack">
                        <?php if (empty($_SESSION['user_id'])): ?>
                            <div class="login-required-note">Para contactar, regístrate con Google.</div>
                            <a class="btn btn-primary" href="/login-google?next=/<?= urlencode($abogado['slug']) ?>">Entra con Google para contactar</a>
                        <?php elseif (empty($contact_unlocked) && !$viewerIsLawyer): ?>
                            <div class="contact-panel">
                                <div class="hint">Con tu cuenta Google puedes desbloquear la tarjeta de contacto de este abogado (esto genera un lead y aviso al abogado).</div>
                                <button type="button" class="btn btn-primary mt-10" onclick="document.getElementById('recommendedLeadFormPanel')?.scrollIntoView({behavior:'smooth', block:'start'});">Cuéntanos tu caso</button>
                            </div>
                        <?php elseif ($viewerIsLawyer && empty($contact_unlocked)): ?>
                            <div class="contact-panel">
                                <div class="hint">Estás viendo este perfil con una cuenta de abogado. Puedes revisarlo, pero este formulario no genera leads entre abogados.</div>
                            </div>
                        <?php else: ?>
                            <button class="btn btn-primary" id="revealBtn">Contactar abogado</button>
                        <?php endif; ?>
                        <div class="quick-actions">
                            <a class="btn btn-ghost" href="/explorar">Volver al catálogo</a>
                            <button type="button" class="btn btn-ghost" id="shareProfileBtn">Compartir perfil</button>
                            <?php if(isset($_SESSION['user_id']) && $viewerIsOwner && $viewerIsLawyer): ?>
                                <a class="btn btn-ghost" href="/panel">Mi panel</a>
                            <?php endif; ?>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a class="btn btn-ghost" href="/logout">Salir</a>
                            <?php else: ?>
                                <a class="btn btn-ghost" href="/login-google">Login</a>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($_SESSION['user_id']) && !$viewerIsLawyer): ?>
                        <div class="quick-actions" style="margin-top:8px;">
                            <button type="button" class="btn btn-ghost" id="likeBtn">👍 Me interesa este perfil</button>
                            <button type="button" class="btn btn-ghost" id="recommendBtn">⭐ Recomendar</button>
                        </div>
                        <?php elseif (!empty($_SESSION['user_id']) && $viewerIsLawyer && !$viewerIsOwner): ?>
                        <div class="contact-panel"><div class="hint">Las acciones de like y recomendación están reservadas para cuentas cliente.</div></div>
                        <?php endif; ?>
                        <div id="contactBlock" class="contact-panel <?= !empty($contact_unlocked) && isset($_GET['contacto']) ? '' : 'hidden' ?>">
                            <div class="hint">Contacto directo del perfil</div>
                            <div style="font-weight:700; margin-top:4px;" id="realName"><?= htmlspecialchars($abogado['nombre']) ?></div>
                            <div class="meta" style="margin-top:4px;">+56 <?= htmlspecialchars($abogado['whatsapp']) ?></div>
                            <div class="contact-row">
                                <a class="btn btn-primary" href="https://wa.me/56<?= $abogado['whatsapp'] ?>?text=Hola%20<?= urlencode($abogado['nombre']) ?>%2C%20vi%20tu%20ficha%20en%20Tu%20Estudio%20Juridico">WhatsApp</a>
                                <a class="btn btn-ghost" href="tel:+56<?= $abogado['whatsapp'] ?>">Llamar</a>
                                <?php if (!empty($abogado['email'])): ?>
                                    <a class="btn btn-ghost" href="mailto:<?= htmlspecialchars($abogado['email']) ?>?subject=Consulta%20desde%20Tu%20Estudio%20Juridico">Email</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content-grid">
                <div class="panel summary-panel">
                    <h2>Resumen del perfil</h2>
                    <p><?= htmlspecialchars($resumenPerfil) ?></p>
                </div>

                <div class="panel">
                    <h2>Datos rápidos</h2>
                    <div class="meta-list">
                        <div class="meta-item">
                            <small>Especialidad</small>
                            <strong><?= htmlspecialchars($abogado['especialidad'] ?? 'No indicada') ?></strong>
                        </div>
                        <?php if (!empty($submateriasPerfil)): ?>
                        <div class="meta-item">
                            <small>Submaterias</small>
                            <strong><?= htmlspecialchars(implode(' · ', array_slice($submateriasPerfil, 0, 5))) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($abogado['materia_secundaria'])): ?>
                        <div class="meta-item">
                            <small>Materia secundaria</small>
                            <strong><?= htmlspecialchars((string)$abogado['materia_secundaria']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($submateriasSecPerfil)): ?>
                        <div class="meta-item">
                            <small>Submaterias (materia secundaria)</small>
                            <strong><?= htmlspecialchars(implode(' · ', array_slice($submateriasSecPerfil, 0, 3))) ?></strong>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <small>Universidad</small>
                            <strong><?= $universidadTop ? '⭐ ' : '' ?><?= !empty($abogado['universidad']) ? htmlspecialchars($abogado['universidad']) : 'No indicada' ?></strong>
                        </div>
                        <div class="meta-item">
                            <small>Experiencia</small>
                            <strong><?= !empty($abogado['experiencia']) ? htmlspecialchars($abogado['experiencia']) : 'Experiencia comprobable' ?></strong>
                        </div>
                        <?php if ($viewerIsOwner): ?>
                        <div class="meta-item">
                            <small>Vistas entre abogados (solo tú)</small>
                            <strong><?= (int)($abogado['vistas_abogados'] ?? 0) ?></strong>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <small>Región donde ejerce</small>
                            <strong><?= !empty($abogado['cobertura_nacional']) ? 'Todo Chile' : (!empty($abogado['regiones_servicio']) ? htmlspecialchars((string)$abogado['regiones_servicio']) : 'No indicada') ?></strong>
                        </div>
                        <?php if (!empty($abogado['entrevista_presencial'])): ?>
                        <div class="meta-item highlight-presencial">
                            <small>Atención presencial</small>
                            <strong>Disponible para entrevistas presenciales</strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($abogado['comunas_servicio'])): ?>
                        <div class="meta-item">
                            <small>Comunas (opcional)</small>
                            <strong><?= htmlspecialchars((string)$abogado['comunas_servicio']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($abogado['ciudades_plaza'])): ?>
                        <div class="meta-item">
                            <small>Ciudades donde generalmente tramita / donde es local</small>
                            <strong><?= htmlspecialchars((string)$abogado['ciudades_plaza']) ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if ($viewerIsLawyer && !empty($abogado['audiencias_para_abogados_plaza'])): ?>
                        <div class="meta-item" style="background:linear-gradient(180deg, rgba(47,35,69,.40), rgba(18,14,30,.35)); border-color:rgba(210,176,255,.22);">
                            <small>Apoyo a colegas</small>
                            <strong>Disponible para tomar audiencias para otros abogados en su ciudad/plaza</strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($abogado['tiene_postitulo'])): ?>
                        <div class="meta-item">
                            <small>Postítulo</small>
                            <strong><?= !empty($abogado['nombre_postitulo']) ? htmlspecialchars((string)$abogado['nombre_postitulo']) : 'Sí' ?></strong>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($abogado['tiene_diplomado'])): ?>
                        <div class="meta-item">
                            <small>Diplomado</small>
                            <strong><?= !empty($abogado['nombre_diplomado']) ? htmlspecialchars((string)$abogado['nombre_diplomado']) : 'Sí' ?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($abogado['instagram']) || !empty($abogado['tiktok']) || !empty($abogado['web']) || !empty($fbRaw) || !empty($liRaw)): ?>
                    <div class="panel">
                        <h2>Redes y links</h2>
                        <div class="socials">
                            <div class="social-icons">
                            <?php if ($igUser !== ''): ?>
                                <a class="icon-btn" href="instagram://user?username=<?= htmlspecialchars($igUser) ?>" data-fallback="https://instagram.com/<?= htmlspecialchars($igUser) ?>" target="_blank" rel="noopener" title="Instagram">📸 <span>Instagram</span></a>
                            <?php endif; ?>
                            <?php if ($ttUser !== ''): ?>
                                <a class="icon-btn" href="snssdk1233://user/profile/<?= htmlspecialchars($ttUser) ?>" data-fallback="https://tiktok.com/@<?= htmlspecialchars($ttUser) ?>" target="_blank" rel="noopener" title="TikTok">🎵 <span>TikTok</span></a>
                            <?php endif; ?>
                            <?php if (!empty($abogado['web'])): ?>
                                <a class="icon-btn" href="<?= htmlspecialchars($abogado['web']) ?>" target="_blank" rel="noopener" title="Sitio web">🌐 <span>Web</span></a>
                            <?php endif; ?>
                            <?php if ($fbRaw !== ''): ?>
                                <a class="icon-btn" href="fb://facewebmodal/f?href=<?= htmlspecialchars($fbRaw) ?>" data-fallback="<?= htmlspecialchars($fbRaw) ?>" target="_blank" rel="noopener" title="Facebook">📘 <span>Facebook</span></a>
                            <?php endif; ?>
                            <?php if ($liRaw !== ''): ?>
                                <a class="icon-btn" href="<?= htmlspecialchars($liRaw) ?>" target="_blank" rel="noopener" title="LinkedIn">💼 <span>LinkedIn</span></a>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($abogado['exhibir_medios_pago']) && !empty($mediosPagoPerfil)): ?>
                    <div class="panel">
                        <h2>Medios de pago</h2>
                        <div class="mini-chip-row">
                            <?php foreach ($mediosPagoPerfil as $mp): ?>
                                <?php if (!isset($mediosPagoLabels[$mp])) continue; ?>
                                <span class="mini-chip">💳 <?= htmlspecialchars($mediosPagoLabels[$mp]) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="panel" id="recommendedLeadFormPanel">
                    <h2>Formulario recomendado</h2>
                    <p>Ideal si quieres que revisemos mejor tu situación antes de contactarte. Usa un solo formulario con tu preferencia de contacto.</p>
                    <div class="contact-choice" style="margin-top:8px;">
                        <?php if (!empty($_SESSION['user_id']) && !$viewerIsLawyer && empty($contact_unlocked)): ?>
                        <div class="guide-item">
                            <strong>Cuéntanos tu caso</strong>
                            <p>Usa el formulario de <em>Hablemos hoy mismo sobre tu caso</em> para enviar tu mensaje. Se generará un lead con tus datos registrados y tu preferencia de contacto.</p>
                            <button type="button" class="btn btn-primary mt-10" onclick="document.getElementById('leadCaseFormBox')?.scrollIntoView({behavior:'smooth', block:'start'}); document.querySelector('#leadCaseFormBox textarea')?.focus();">Ir al formulario</button>
                        </div>
                        <?php elseif (!empty($_SESSION['user_id']) && $viewerIsLawyer && empty($contact_unlocked)): ?>
                        <div class="guide-item">
                            <strong>Vista entre abogados</strong>
                            <p>Como abogado puedes revisar este perfil, pero el formulario de leads está reservado para clientes. Tu visita suma al contador de vistas entre abogados.</p>
                        </div>
                        <?php elseif (empty($_SESSION['user_id'])): ?>
                        <div class="guide-item login-required-note">
                            <strong>Cuéntanos tu caso</strong>
                            <p>Para usar el formulario y contactar al abogado, primero debes registrarte con Google. Luego volverás automáticamente a este perfil.</p>
                            <a class="btn btn-primary mt-10" href="/login-google?next=/<?= urlencode($abogado['slug']) ?>">Google · Registrarme para contactar</a>
                        </div>
                        <?php else: ?>
                        <div class="guide-item">
                            <strong>Contacto desbloqueado</strong>
                            <p>Tu lead ya fue enviado. Puedes usar WhatsApp o llamada directamente y también compartir antecedentes por el canal que prefieras.</p>
                        </div>
                        <?php endif; ?>
                        <div class="row">
                            <div class="choice-card">
                                <strong>WhatsApp</strong>
                                <p>Úsalo si prefieres escribir ahora. Antes te pediremos un número de contacto.</p>
                                <?php if (!empty($contact_unlocked)): ?>
                                    <a class="btn btn-primary" href="https://wa.me/56<?= $abogado['whatsapp'] ?>?text=Hola%20<?= urlencode($abogado['nombre']) ?>%2C%20vi%20tu%20perfil%20en%20Tu%20Estudio%20Juridico%20y%20quiero%20contarte%20mi%20caso.">Ir a WhatsApp</a>
                                <?php elseif (!empty($_SESSION['user_id']) && !$viewerIsLawyer): ?>
                                    <button type="button" class="btn btn-primary" onclick="const box=document.getElementById('leadCaseFormBox'); const f=document.getElementById('leadCaseForm'); if(f){ const pref=f.querySelector('input[name=preferencia_contacto][value=whatsapp]'); if(pref) pref.checked=true; } box?.scrollIntoView({behavior:'smooth', block:'center'}); document.querySelector('#leadCaseForm textarea')?.focus();">Ir a WhatsApp</button>
                                <?php else: ?>
                                    <a class="btn btn-primary require-google-contact" href="/login-google?next=/<?= urlencode($abogado['slug']) ?>" data-requires-google="1">Google · Registrarme para WhatsApp</a>
                                <?php endif; ?>
                            </div>
                            <div class="choice-card">
                                <strong>Llamada</strong>
                                <p>Úsalo si necesitas hablar de inmediato. Antes te pediremos un número de contacto.</p>
                                <?php if (!empty($contact_unlocked)): ?>
                                    <a class="btn btn-ghost" href="tel:+56<?= $abogado['whatsapp'] ?>">Llamar ahora</a>
                                <?php elseif (!empty($_SESSION['user_id']) && !$viewerIsLawyer): ?>
                                    <button type="button" class="btn btn-ghost" onclick="const box=document.getElementById('leadCaseFormBox'); const f=document.getElementById('leadCaseForm'); if(f){ const pref=f.querySelector('input[name=preferencia_contacto][value=llamada]'); if(pref) pref.checked=true; } box?.scrollIntoView({behavior:'smooth', block:'center'}); document.querySelector('#leadCaseForm textarea')?.focus();">Llamar ahora</button>
                                <?php else: ?>
                                    <a class="btn btn-ghost require-google-contact" href="/login-google?next=/<?= urlencode($abogado['slug']) ?>" data-requires-google="1">Google · Registrarme para llamar</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <h2><?= htmlspecialchars($serviciosConsultadosTitle) ?></h2>
                    <p><?= htmlspecialchars($serviciosConsultadosDesc) ?></p>
                    <div class="guide-list" style="margin-top:8px;">
                        <?php foreach ($serviciosSugeridos as $srv): ?>
                            <div class="guide-item">
                                <strong><?= htmlspecialchars($srv['title']) ?></strong>
                                <p><?= htmlspecialchars($srv['desc']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="panel">
                    <h2>Preguntas frecuentes antes de contratar</h2>
                    <p>Respuestas breves para ayudarte a decidir con más tranquilidad. La estrategia final siempre depende de los antecedentes del caso.</p>
                    <div class="faq-list" style="margin-top:8px;">
                        <?php foreach ($faqPerfil as $faq): ?>
                            <div class="faq-item">
                                <h3><?= htmlspecialchars($faq['q']) ?></h3>
                                <p><?= htmlspecialchars($faq['a']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="panel" id="leadCaseFormBox">
                    <h2>Hablemos hoy mismo sobre tu caso</h2>
                    <p>Llena el formulario con tus datos, y el abogado recibirá tus datos y consulta, e intentará venderte sus servicios.</p>
                    <?php if (isset($_SESSION['user_id']) && empty($contact_unlocked) && !$viewerIsLawyer): ?>
                        <div class="lead-send-status" id="leadSendStatus"><span class="lead-send-dot" aria-hidden="true"></span><span>Preparando y enviando tu mensaje al abogado...</span></div>
                        <form method="POST" action="/perfil-contacto/<?= (int)$abogado['id'] ?>" class="lead-inline-form" id="leadCaseForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                            <input type="hidden" name="slug" value="<?= htmlspecialchars((string)$abogado['slug']) ?>">
                            <label style="display:grid; gap:6px; font-size:12px; font-weight:700;">
                                Tu nombre
                                <input class="input" type="text" name="nombre_cliente" required value="<?= htmlspecialchars((string)($viewer['nombre'] ?? '')) ?>" placeholder="Nombre y apellido">
                            </label>
                            <label style="display:grid; gap:6px; font-size:12px; font-weight:700;">
                                WhatsApp (+569XXXXXXXX)
                                <input class="input" type="tel" name="whatsapp_cliente" required value="<?= !empty($viewer['whatsapp']) ? '+56' . htmlspecialchars((string)$viewer['whatsapp']) : '' ?>" placeholder="+56912345678">
                            </label>
                            <label style="display:grid; gap:6px; font-size:12px; font-weight:700;">
                                Cuéntanos tu caso
                                <textarea class="input" name="detalle_caso" rows="5" placeholder="Describe brevemente tu situación, fechas clave y qué necesitas resolver."></textarea>
                            </label>
                            <label class="hint" style="display:flex; gap:8px; align-items:flex-start; font-size:12px; border:1px solid var(--app-line); border-radius:10px; padding:8px;">
                                <input type="checkbox" name="quiero_contacto_abogado" value="1" required style="margin-top:2px;">
                                <span>Quiero conocer los datos para contactar a este abogado.</span>
                            </label>
                            <div style="display:grid; gap:6px; font-size:12px;">
                                <strong style="font-size:12px;">Preferencia de contacto</strong>
                                <label class="hint" style="display:flex; gap:8px; align-items:center;"><input type="radio" name="preferencia_contacto" value="whatsapp" checked> WhatsApp</label>
                                <label class="hint" style="display:flex; gap:8px; align-items:center;"><input type="radio" name="preferencia_contacto" value="llamada"> Llamada</label>
                            </div>
                            <div class="contact-row">
                                <button class="btn btn-primary" type="submit">Enviar mensaje y desbloquear contacto</button>
                                <button class="btn btn-ghost" type="button" onclick="const pref=document.querySelector('#leadCaseForm input[name=preferencia_contacto][value=llamada]'); if(pref) pref.checked=true; document.querySelector('#leadCaseForm textarea')?.focus();">Prefiero llamada</button>
                            </div>
                        </form>
                    <?php elseif (!empty($contact_unlocked)): ?>
                        <div class="guide-item">
                            <strong>Contacto desbloqueado</strong>
                            <p>Tu lead ya fue enviado correctamente. Ahora puedes continuar por WhatsApp o llamada con el abogado.</p>
                        </div>
                        <div class="contact-row">
                            <a class="btn btn-primary" href="https://wa.me/56<?= $abogado['whatsapp'] ?>?text=Hola%20<?= urlencode($abogado['nombre']) ?>%2C%20vi%20tu%20perfil%20en%20Tu%20Estudio%20Juridico">WhatsApp</a>
                            <a class="btn btn-ghost" href="tel:+56<?= $abogado['whatsapp'] ?>">Llamar</a>
                        </div>
                    <?php elseif (!empty($_SESSION['user_id']) && $viewerIsLawyer): ?>
                        <div class="guide-item">
                            <strong>Vista entre abogados</strong>
                            <p>Los formularios de contacto generan leads solo para cuentas cliente. Como abogado, tu visita se registra en el contador de vistas entre abogados.</p>
                        </div>
                    <?php else: ?>
                        <div class="guide-item login-required-note">
                            <strong>Regístrate con Google para contactar</strong>
                            <p>Para enviar este formulario y generar el lead al abogado, primero debes registrarte con Google.</p>
                            <a class="btn btn-primary mt-10 require-google-contact" href="/login-google?next=/<?= urlencode($abogado['slug']) ?>" data-requires-google="1">Google · Registrarme para contactar</a>
                        </div>
                    <?php endif; ?>
                </div></div>

                <div class="panel">
                    <h2>Cómo seguir</h2>
                    <p>1) Revisa perfil y especialidad. 2) Abre contacto. 3) Escribe por WhatsApp con tu necesidad legal y contexto breve.</p>
                </div>
            </section>
        </section>
    </main>

    <div class="google-gate-modal" id="googleGateModal" aria-hidden="true">
        <div class="google-gate-card" role="dialog" aria-modal="true" aria-labelledby="googleGateTitle">
            <h3 id="googleGateTitle">Regístrate con Google para contactar</h3>
            <p>Para usar WhatsApp, llamada o enviar el formulario a este abogado, primero debes entrar con Google. Luego volverás automáticamente a este perfil.</p>
            <div class="google-gate-actions">
                <button type="button" class="btn btn-ghost" id="closeGoogleGateModal">Cerrar</button>
                <a class="btn btn-primary google-logo-inline" id="googleGateLoginLink" href="/login-google">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-1.5 3.9-5.5 3.9-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.2.8 3.9 1.5l2.7-2.6C16.9 3.3 14.7 2.4 12 2.4 6.7 2.4 2.4 6.7 2.4 12S6.7 21.6 12 21.6c6.9 0 9.1-4.8 9.1-7.3 0-.5 0-.9-.1-1.2H12z"/></svg>
                    <span>Entrar con Google</span>
                </a>
            </div>
        </div>
    </div>

    <div class="bottom-nav app-primary-nav profile-nav<?= isset($_SESSION['user_id']) ? ' profile-nav-logged' : ' profile-nav-guest' ?>">
        <a href="/explorar" class="active-nav">Explorar</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if ($viewerIsOwner && $viewerIsLawyer): ?>
                <a href="/panel">Mi panel</a>
            <?php else: ?>
                <span class="nav-spacer" aria-hidden="true"></span>
            <?php endif; ?>
            <a href="/logout" class="app-primary-cta-link">Salir</a>
        <?php else: ?>
            <a href="/login-google" class="app-primary-cta-link">Login</a>
        <?php endif; ?>
    </div>
    <div
        id="profilePageConfig"
        data-abogado-id="<?= (int)($abogado['id'] ?? 0) ?>"
        data-abogado-slug="<?= htmlspecialchars((string)($abogado['slug'] ?? '')) ?>"
    ></div>
    <script src="/profile-page.js?v=2026022601"></script>
</body>
</html>
