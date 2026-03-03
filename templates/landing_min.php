<?php
$canonicalUrl = trim((string)($canonical_url ?? 'https://example.com/'));
if ($canonicalUrl === '') {
    $canonicalUrl = 'https://example.com/';
}
$ogUrl = trim((string)($og_url ?? $canonicalUrl));
if ($ogUrl === '') {
    $ogUrl = $canonicalUrl;
}
$authGate = (isset($auth_gate) && is_array($auth_gate)) ? $auth_gate : [];
$previewLoginHref = trim((string)($authGate['login_href'] ?? '/login-google?next=%2Fexplorar'));
if ($previewLoginHref === '') {
    $previewLoginHref = '/login-google?next=%2Fexplorar';
}
$previewNext = trim((string)($authGate['next'] ?? '/explorar'));
if ($previewNext === '') {
    $previewNext = '/explorar';
}
$authState = trim((string)($authGate['state'] ?? 'anonymous'));
if ($authState === '') {
    $authState = 'anonymous';
}
$isPreviewMode = !empty($is_preview_mode);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Estudio Juridico | Ingresa con Google</title>
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta name="description" content="Encuentra abogados en Chile y conecta en minutos. Ingresa con Google para explorar perfiles y solicitar contacto en Tu Estudio Juridico.">
    <meta property="og:title" content="Tu Estudio Juridico | Ingresa con Google">
    <meta property="og:description" content="Encuentra abogados en Chile y conecta en minutos. Ingresa con Google para explorar perfiles y solicitar contacto en Tu Estudio Juridico.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($ogUrl) ?>">
    <meta property="og:image" content="https://example.com/og-lawyers-1200x630.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Tu Estudio Juridico | Ingresa con Google">
    <meta name="twitter:description" content="Encuentra abogados en Chile y conecta en minutos. Ingresa con Google para explorar perfiles y solicitar contacto en Tu Estudio Juridico.">
    <meta name="twitter:image" content="https://example.com/og-lawyers-1200x630.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <script src="/app-shell.js?v=202602231" defer></script>
    <style>
        .landing-min-wrap,
        .landing-min-card,
        .landing-min-card *{
            font-family:"Inter","Segoe UI","SF Pro Text","Helvetica Neue",Arial,sans-serif;
        }
        .landing-min-card a,
        .landing-min-card a:visited{
            color:inherit;
        }
        .landing-min-wrap{max-width:920px;margin:0 auto;padding:20px 14px 120px;}
        .landing-min-card{
            margin-top:22px;
            border:1px solid rgba(168,196,255,.16);
            border-radius:20px;
            background:linear-gradient(180deg,rgba(17,34,63,.92),rgba(13,24,45,.92));
            box-shadow:0 14px 28px rgba(0,0,0,.28), 0 0 0 1px rgba(113,155,242,.04) inset;
            padding:20px;
            display:grid;
            gap:15px;
        }
        .landing-min-kicker{font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#e2edff;opacity:.98}
        .landing-min-title{margin:0;font-size:30px;line-height:1.05;font-weight:900;letter-spacing:-.02em;color:#f5f0e8}
        .landing-min-sub{margin:0;color:#e6efff;font-size:15px;line-height:1.55;font-weight:500}
        .landing-min-points{display:grid;gap:9px}
        .landing-min-point{border:1px solid rgba(173,200,255,.14);background:rgba(255,255,255,.03);border-radius:12px;padding:11px 12px;color:#f0f4ff;font-size:14px}
        .landing-min-actions{display:grid;gap:8px;grid-template-columns:1fr}
        .landing-min-btn,
        .landing-min-btn:visited{
            min-height:54px;border-radius:14px;border:1px solid rgba(255,255,255,.16);
            display:flex;align-items:center;justify-content:center;gap:8px;
            font-weight:800;text-decoration:none;font-size:15px;
        }
        .landing-min-btn.google{
            background:linear-gradient(180deg,#24324b,#1a2437);
            color:#f7f7f7;
            border-color:rgba(92,154,255,.28);
            box-shadow:0 4px 10px rgba(66,133,244,.06);
        }
        .landing-min-btn.explore{
            background:linear-gradient(180deg,#2f6bd1,#2557ac);
            color:#f7fbff;
            border-color:rgba(134,178,255,.5);
            text-transform:uppercase;
            letter-spacing:.04em;
            box-shadow:0 6px 14px rgba(32,89,185,.25);
        }
        .google-mini-badge{width:16px;height:16px;display:block}
        .landing-min-cta-note{
            margin:-2px 2px 2px;
            font-size:12px;
            line-height:1.35;
            color:#d7e6ff;
            font-weight:600;
            text-align:center;
        }
        .landing-min-foot{font-size:12px;color:#c9d5ec}
        .landing-services{margin-top:12px;display:grid;gap:10px}
        .landing-services-head{display:grid;gap:4px}
        .landing-services-head h2{margin:0;font-size:18px;line-height:1.1;color:#f5f0e8;font-weight:900}
        .landing-services-head p{margin:0;font-size:12px;color:#edf3ff;line-height:1.4}
        .landing-services-grid{display:grid;gap:10px;grid-template-columns:repeat(2,minmax(0,1fr));}
        .service-card,.service-card:visited{border:1px solid rgba(173,200,255,.26);background:rgba(255,255,255,.055);border-radius:12px;padding:10px;display:grid;gap:8px;text-decoration:none;color:#f4f8ff}
        .service-card:hover{border-color:rgba(152,194,255,.5);background:rgba(120,170,255,.09)}
        .service-card h3{margin:0;font-size:14px;line-height:1.15;color:#fff4e8;font-weight:900;letter-spacing:-.01em}
        .service-sublist{display:grid;gap:4px}
        .service-subitem{font-size:11px;line-height:1.3;color:#eff5ff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:700}
        .service-availability{font-size:11px;font-weight:700;color:#e7f2ff;opacity:.98}
        .service-cta,.service-cta:visited{margin-top:auto;display:inline-flex;align-items:center;justify-content:center;min-height:34px;border-radius:9px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.03);color:#f4f8ff;font-size:12px;font-weight:800;text-decoration:none}
        .service-cta:hover{border-color:rgba(120,170,255,.32);background:rgba(120,170,255,.08)}

        .landing-flash{border:1px solid rgba(255,255,255,.14);border-radius:12px;padding:10px 12px;font-size:13px;font-weight:700;}
        .landing-flash.success{background:rgba(52,199,89,.12);color:#dcffe7;border-color:rgba(52,199,89,.32);}
        .landing-flash.error{background:rgba(255,82,119,.10);color:#ffd9e1;border-color:rgba(255,82,119,.28);}
        .landing-flash.info{background:rgba(66,133,244,.10);color:#dbe7ff;border-color:rgba(66,133,244,.28);}
        
        .landing-min-preview{display:grid;gap:10px}
        .preview-strip{
            display:grid;
            grid-auto-flow:column;
            grid-auto-columns:minmax(228px, 228px);
            gap:12px;
            overflow-x:auto;
            padding:4px 6px 10px;
            scroll-padding-inline:6px;
            scroll-snap-type:x mandatory;
            overscroll-behavior-x:contain;
            scrollbar-width:thin;
            scrollbar-color:rgba(120,170,255,.35) rgba(255,255,255,.03);
        }
        .preview-strip::-webkit-scrollbar{height:8px}
        .preview-strip::-webkit-scrollbar-track{background:rgba(255,255,255,.03);border-radius:999px}
        .preview-strip::-webkit-scrollbar-thumb{background:rgba(120,170,255,.35);border-radius:999px}
        .preview-teaser,
        .preview-teaser:visited{
            scroll-snap-align:start;
            display:grid;
            grid-template-rows:92px auto auto auto;
            gap:7px;
            padding:11px;
            border-radius:12px;
            border:1px solid rgba(176,205,255,.12);
            background:linear-gradient(180deg,rgba(255,255,255,.035),rgba(255,255,255,.022));
            color:#eef4ff;
            text-decoration:none;
            min-height:224px;
            box-shadow:0 5px 12px rgba(0,0,0,.14);
        }
        .preview-teaser:hover{
            border-color:rgba(144,186,255,.46);
            background:linear-gradient(180deg,rgba(120,170,255,.07),rgba(120,170,255,.04));
            transform:translateY(-1px);
        }
        .preview-photo-shell{
            position:relative;
            border-radius:9px;
            overflow:hidden;
            border:1px solid rgba(176,205,255,.12);
            background:linear-gradient(180deg,rgba(255,255,255,.05),rgba(255,255,255,.025));
        }
        .preview-avatar{
            width:100%;
            height:92px;
            object-fit:cover;
            filter:saturate(.78) brightness(.86) contrast(.98);
            transform:scale(1.01);
            opacity:.9;
        }
        .preview-photo-shell::after{
            content:"";
            position:absolute;inset:0;
            background:linear-gradient(180deg,rgba(9,14,24,.10),rgba(9,14,24,.44));
        }
        .preview-meta{font-size:12.5px;color:#eef3ff;line-height:1.34}
        .preview-meta strong{display:block;color:#f5f0e8;font-size:14px;font-weight:800;margin-bottom:3px}
        .preview-meta > div{
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
        }
        .preview-sub{
            font-size:12px;
            color:#e0e9fb;
            line-height:1.32;
            min-height:31px;
            display:-webkit-box;
            -webkit-line-clamp:2;
            -webkit-box-orient:vertical;
            overflow:hidden;
        }
        .preview-go{
            margin-top:auto;
            font-size:11.5px;
            font-weight:800;
            color:#eaf2ff;
            border:1px dashed rgba(92,154,255,.28);
            background:rgba(92,154,255,.08);
            padding:7px 8px;
            border-radius:8px;
            text-align:center;
        }
        .preview-note{font-size:12px;color:#e0eafc}
        .preview-gate-row{
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            align-items:center;
            justify-content:space-between;
            border:1px solid rgba(173,200,255,.16);
            background:rgba(255,255,255,.03);
            border-radius:10px;
            padding:8px 10px;
            font-size:12px;
            color:#e2ecff;
        }
        .preview-gate-link,
        .preview-gate-link:visited{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            min-height:30px;
            border-radius:8px;
            border:1px solid rgba(92,154,255,.32);
            background:rgba(92,154,255,.13);
            color:#f1f7ff;
            font-size:12px;
            font-weight:800;
            text-decoration:none;
            padding:0 10px;
            white-space:nowrap;
        }
        .preview-dots{
            display:flex;
            justify-content:center;
            gap:6px;
            align-items:center;
            margin-top:2px;
        }
        .preview-dot{
            width:7px;height:7px;border-radius:50%;
            border:none;padding:0;cursor:pointer;
            background:rgba(232,240,255,.22);
            box-shadow:inset 0 0 0 1px rgba(255,255,255,.07);
            transition:transform .15s ease, background-color .15s ease;
        }
        .preview-dot.is-active{
            background:#7fb0ff;
            transform:scale(1.22);
        }

        @media (max-width:640px){
            .landing-min-title{font-size:26px}
            .landing-min-actions{grid-template-columns:1fr}
            .landing-min-card{padding:16px}
            .landing-min-sub{font-size:14px;line-height:1.45}
            .landing-min-point{font-size:13px;padding:10px}
            .preview-note{font-size:11px}
            .preview-strip{grid-auto-columns:minmax(205px,205px);gap:10px;padding:2px 4px 8px;scroll-padding-inline:4px}
            .preview-teaser{
                grid-template-rows:70px auto auto auto;
                min-height:210px;
                padding:8px;
                gap:5px;
            }
            .preview-avatar{height:70px}
            .preview-meta strong{font-size:13px}
            .preview-meta{font-size:11.5px}
            .preview-sub{font-size:11px;min-height:28px}
            .preview-go{font-size:10.5px;padding:6px 7px}
            .preview-gate-row{font-size:11px}
            .preview-dots{display:none}
            .landing-services-grid{grid-template-columns:1fr}
            .service-card{padding:9px}
            .service-card h3{font-size:13px}
            .service-subitem{font-size:10.5px}
        }
    </style>
</head>
<body
    class="theme-black theme-olive"
    data-auth-state="<?= htmlspecialchars($authState) ?>"
    data-preview-mode="<?= $isPreviewMode ? '1' : '0' ?>"
    data-auth-next="<?= htmlspecialchars($previewNext) ?>"
>
    <div class="landing-min-wrap">
        <div class="landing-min-card">
            <div class="landing-min-kicker">Tu Estudio Juridico</div>
            <?php if (!empty($mensaje ?? null)): ?>
                <?php $flashType = in_array(($tipo_mensaje ?? 'info'), ['success','error','info'], true) ? $tipo_mensaje : 'info'; ?>
                <div class="landing-flash <?= htmlspecialchars((string)$flashType) ?>"><?= htmlspecialchars((string)$mensaje) ?></div>
            <?php endif; ?>
            <h1 class="landing-min-title">Ingresa con Google y explora abogados</h1>
            <p class="landing-min-sub">Busca abogados por materia y ciudad, compara perfiles y contacta directamente al que te interese.</p>

            <div class="landing-min-points">
                <div class="landing-min-point">1. Ingresa con Google y explora abogados por materia o ciudad.</div>
                <div class="landing-min-point">2. Contáctalo directamente.</div>
            </div>

            <div class="landing-min-actions">
                <a class="landing-min-btn explore" href="/explorar">EXPLORAR ABOGADOS</a>
                <a class="landing-min-btn google" href="<?= htmlspecialchars($previewLoginHref) ?>">
                    <svg class="google-mini-badge" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.2-1.4 3.5-5.5 3.5-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.1.8 3.8 1.5l2.6-2.5C16.8 2.6 14.6 1.8 12 1.8 6.4 1.8 1.9 6.3 1.9 12S6.4 22.2 12 22.2c6.9 0 9.6-4.8 9.6-7.3 0-.5-.1-.9-.1-1.2H12z"/>
                        <path fill="#4285F4" d="M23.1 12.2c0-.7-.1-1.2-.2-1.8H12v3.7h6.3c-.3 1.4-1.1 2.6-2.3 3.4l3.5 2.7c2-1.9 3.6-4.7 3.6-8z"/>
                        <path fill="#FBBC05" d="M5.8 14.3c-.2-.6-.4-1.4-.4-2.1s.1-1.5.4-2.1L2.2 7.3C1.5 8.8 1.1 10.4 1.1 12s.4 3.2 1.1 4.7l3.6-2.4z"/>
                        <path fill="#34A853" d="M12 22.2c2.8 0 5.1-.9 6.8-2.5l-3.5-2.7c-.9.6-2.1 1-3.4 1-2.6 0-4.8-1.8-5.6-4.2l-3.6 2.4c1.7 3.5 5.3 6 9.3 6z"/>
                    </svg>
                    Entrar con Google
                </a>
                <div class="landing-min-cta-note">Puedes explorar y abrir perfiles sin login. Para revelar contacto y enviar lead, inicia sesión con Google.</div>
            </div>

            <?php if (!empty($preview_lawyers)): ?>
                <div id="preview-directorio" class="landing-min-preview">
                    <div class="preview-note">Vista previa del directorio. Para ver perfiles completos, inicia sesión con Google.</div>
                    <div class="preview-strip">
                    <?php foreach (array_values($preview_lawyers) as $idx => $abg): ?>
                        <?php
                            $subs = [];
                            if (!empty($abg['submaterias'])) {
                                $tmp = json_decode((string)$abg['submaterias'], true);
                                if (is_array($tmp)) { $subs = array_values(array_filter(array_map('trim', $tmp))); }
                            }
                            $subPreview = implode(' · ', array_slice($subs, 0, 2));
                            $exp = trim((string)($abg['experiencia'] ?? ''));
                            $uni = trim((string)($abg['universidad'] ?? ''));
                            $previewName = trim((string)($abg['nombre'] ?? ''));
                            $previewSpec = trim((string)($abg['especialidad'] ?? ''));
                            $previewImgAlt = $previewName !== ''
                                ? ('Vista previa del perfil de ' . $previewName . ($previewSpec !== '' ? ', ' . $previewSpec : ''))
                                : 'Vista previa de perfil de abogado';
                            $isFirstPreview = $idx === 0;
                        ?>
                        <a class="preview-teaser" href="<?= htmlspecialchars($previewLoginHref) ?>">
                            <div class="preview-photo-shell">
                                <img
                                    class="preview-avatar"
                                    src="<?= htmlspecialchars((string)($abg['foto_final'] ?? '')) ?>"
                                    alt="<?= htmlspecialchars($previewImgAlt) ?>"
                                    loading="<?= $isFirstPreview ? 'eager' : 'lazy' ?>"
                                    decoding="async"
                                    fetchpriority="<?= $isFirstPreview ? 'high' : 'low' ?>"
                                    width="320"
                                    height="184"
                                >
                            </div>
                            <div class="preview-meta">
                                <strong><?= htmlspecialchars((string)($abg['especialidad'] ?? 'Abogado')) ?></strong>
                                <?php if ($subPreview !== ''): ?>
                                    <div><?= htmlspecialchars($subPreview) ?></div>
                                <?php else: ?>
                                    <div>Submaterias y experiencia en el perfil</div>
                                <?php endif; ?>
                            </div>
                            <div class="preview-sub">
                                <?= $exp !== '' ? htmlspecialchars($exp) : 'Experiencia visible en el perfil' ?>
                                <?php if ($uni !== ''): ?> · <?= htmlspecialchars($uni) ?><?php endif; ?>
                            </div>
                            <div class="preview-go">Ver perfil completo</div>
                        </a>
                    <?php endforeach; ?>
                    </div>
                    <?php if (count($preview_lawyers) > 1): ?>
                        <div class="preview-dots" aria-label="Páginas del carrusel">
                            <?php foreach (array_values($preview_lawyers) as $idx => $_): ?>
                                <button type="button" class="preview-dot<?= $idx === 0 ? ' is-active' : '' ?>" data-index="<?= (int)$idx ?>" aria-label="Ir a tarjeta <?= (int)($idx+1) ?>"></button>
                            <?php endforeach; unset($_); ?>
                        </div>
                    <?php endif; ?>
                    <div class="preview-gate-row">
                        <span>Para revelar contacto y enviar lead al abogado, inicia sesión con Google.</span>
                        <a class="preview-gate-link" href="<?= htmlspecialchars($previewLoginHref) ?>">Entrar con Google</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php
                $tax = (isset($materias_taxonomia) && is_array($materias_taxonomia)) ? $materias_taxonomia : [];
                $materiaCounts = (isset($materia_counts) && is_array($materia_counts)) ? $materia_counts : [];
                $legacyAliases = ['Civil','Familia','Laboral','Penal','Comercial','Tributario','Consumidor','Inmobiliario','Otros'];
                $serviceCards = [];
                foreach ($tax as $materia => $subs) {
                    if (!is_string($materia) || in_array($materia, $legacyAliases, true)) { continue; }
                    if (!is_array($subs) || empty($subs)) { continue; }
                    $subsClean = array_values(array_filter(array_map(static function($v){
                        return is_string($v) ? trim($v) : '';
                    }, $subs)));
                    if (!$subsClean) { continue; }
                    $serviceCards[] = [
                        'materia' => $materia,
                        'subs' => array_slice($subsClean, 0, 3),
                        'count' => (int)($materiaCounts[$materia] ?? 0),
                    ];
                    if (count($serviceCards) >= 8) { break; }
                }
            ?>
            <?php if (!empty($serviceCards)): ?>
                <section class="landing-services" aria-label="Nuestros servicios">
                    <div class="landing-services-head">
                        <h2>Nuestros servicios</h2>
                        <p>Busca abogados por materia y submateria en Chile.</p>
                    </div>
                    <div class="landing-services-grid">
                        <?php foreach ($serviceCards as $card): ?>
                            <a class="service-card" href="<?= htmlspecialchars($previewLoginHref) ?>">
                                <h3><?= htmlspecialchars((string)$card['materia']) ?></h3>
                                <div class="service-sublist">
                                    <?php foreach ($card['subs'] as $sub): ?>
                                        <span class="service-subitem"><?= htmlspecialchars((string)$sub) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php if ((int)($card['count'] ?? 0) > 0): ?>
                                    <div class="service-availability">
                                        <?= (int)$card['count'] ?> abogado<?= (int)$card['count'] === 1 ? '' : 's' ?> disponible<?= (int)$card['count'] === 1 ? '' : 's' ?>
                                    </div>
                                <?php endif; ?>
                                <span class="service-cta">Ver abogados</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="landing-min-foot">Explorar y ver perfiles es libre. Para revelar contacto y enviar lead, el acceso es con Google (sesión hasta 30 días).</div>
        </div>
    </div>
    <script>
    window.lawyersAuthGate = <?= json_encode($authGate, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    (function(){
        var strip = document.querySelector('.preview-strip');
        if(!strip) return;
        var dots = Array.prototype.slice.call(document.querySelectorAll('.preview-dot'));
        var items = Array.prototype.slice.call(strip.querySelectorAll('.preview-teaser'));
        if (!items.length) return;

        function cardLeft(el) {
            return Math.max(0, el.offsetLeft - 6);
        }
        function activeIndexFromScroll() {
            var left = strip.scrollLeft;
            var closest = 0;
            var best = Infinity;
            items.forEach(function(el, idx){
                var d = Math.abs(cardLeft(el) - left);
                if (d < best) { best = d; closest = idx; }
            });
            return closest;
        }
        function syncDots(idx){
            dots.forEach(function(dot, i){ dot.classList.toggle('is-active', i === idx); });
        }
        function goToIndex(idx, behavior){
            if (!items[idx]) return;
            strip.scrollTo({ left: cardLeft(items[idx]), behavior: behavior || 'smooth' });
        }
        goToIndex(0, 'auto');
        syncDots(0);
        dots.forEach(function(dot){
            dot.addEventListener('click', function(){
                var idx = parseInt(dot.getAttribute('data-index') || '0', 10) || 0;
                goToIndex(idx, 'smooth');
                syncDots(idx);
            });
        });
        strip.addEventListener('scroll', function(){ syncDots(activeIndexFromScroll()); }, { passive:true });
        var resizeTimer = null;
        window.addEventListener('resize', function(){
            if (resizeTimer) {
                clearTimeout(resizeTimer);
            }
            resizeTimer = setTimeout(function(){
                goToIndex(activeIndexFromScroll(), 'auto');
            }, 120);
        });

        // Auto-scroll only on desktop and with motion enabled.
        var mq = window.matchMedia && window.matchMedia('(min-width: 900px)');
        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
        if (!mq || !mq.matches || items.length < 2 || (reducedMotion && reducedMotion.matches)) return;
        var paused = false;
        var idx = 0;
        function tick(){
            if (paused) return;
            idx = (activeIndexFromScroll() + 1) % items.length;
            goToIndex(idx, 'auto');
            syncDots(idx);
        }
        var timer = setInterval(tick, 3400);
        function setPaused(v){ paused = v; }
        strip.addEventListener('mouseenter', function(){ setPaused(true); });
        strip.addEventListener('mouseleave', function(){ setPaused(false); });
        strip.addEventListener('focusin', function(){ setPaused(true); });
        strip.addEventListener('focusout', function(){ setPaused(false); });
        document.addEventListener('visibilitychange', function(){ setPaused(document.hidden); });
        window.addEventListener('beforeunload', function(){ clearInterval(timer); });
    })();
    </script>
</body>
</html>
