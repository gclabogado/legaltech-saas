<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nosotros | Tu Estudio Juridico</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <style>
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 960px; margin: 0 auto; padding: 14px 14px 28px; }
        .stack { display:grid; gap:10px; }
        .panel { border:1px solid var(--app-line); border-radius:16px; background:rgba(15,29,52,.62); padding:12px; }
        .panel h2 { margin:0; font-size:15px; }
        .panel p { margin:6px 0 0; color:var(--app-muted); font-size:13px; line-height:1.45; }
        .grid2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
        .mini { border:1px solid var(--app-line); border-radius:12px; padding:10px; background:rgba(255,255,255,.02); }
        .mini small { color:var(--app-muted); display:block; font-size:10px; }
        .mini strong { font-size:18px; line-height:1.05; display:block; margin-top:2px; }
        .steps { display:grid; gap:8px; }
        .step { border:1px solid var(--app-line); border-radius:12px; padding:10px; background:rgba(255,255,255,.02); }
        .step b { display:block; font-size:13px; }
        .step span { display:block; margin-top:4px; color:var(--app-muted); font-size:12px; line-height:1.35; }
        .cta-row { display:flex; gap:8px; flex-wrap:wrap; }
        @media (max-width: 760px){ .grid2 { grid-template-columns:1fr; } }
    </style>
</head>
<?php
    $themeEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
    $isAdminTheme = ($themeEmail === 'gmcalderonlewin@gmail.com');
    $isClientTheme = !$isAdminTheme && !empty($_SESSION['user_id']) && (($_SESSION['rol'] ?? 'cliente') === 'cliente');
    $bodyThemeClass = 'theme-black' . ($isAdminTheme ? ' theme-admin' : ' theme-client-lite');
?>
<body class="<?= htmlspecialchars($bodyThemeClass) ?>">
    <main class="wrap">
        <section class="stack">
            <section class="app-toolbar">
                <div>
                    <div class="title">Quiénes somos</div>
                    <div class="sub">Directorio legal para encontrar y contactar abogados en Chile</div>
                </div>
                <span class="dot" aria-hidden="true"></span>
            </section>

            <section class="panel">
                <h2>Qué hace Tu Estudio Juridico</h2>
                <p>Ayudamos a personas a encontrar abogados por materia y región de forma rápida. Nuestro foco es un directorio simple, perfiles claros y contacto directo con profesionales, usando una sola cuenta Google para clientes y una postulación separada para el programa profesional.</p>
                <div class="grid2" style="margin-top:10px;">
                    <div class="mini">
                        <small>Profesionales registrados</small>
                        <strong>+<?= number_format((int)($stats_nosotros['profesionales'] ?? 0), 0, ',', '.') ?></strong>
                    </div>
                    <div class="mini">
                        <small>Clientes que buscan o han buscado abogado</small>
                        <strong><?= number_format((int)($stats_nosotros['clientes'] ?? 0), 0, ',', '.') ?></strong>
                    </div>
                    <div class="mini">
                        <small>Abogados de materias distintas</small>
                        <strong><?= number_format((int)($stats_nosotros['materias'] ?? 0), 0, ',', '.') ?> materias</strong>
                    </div>
                    <div class="mini">
                        <small>Modelo</small>
                        <strong>Directorio + contacto</strong>
                    </div>
                </div>
            </section>

            <section class="panel">
                <h2>Cómo ayudamos a clientes</h2>
                <div class="steps" style="margin-top:8px;">
                    <div class="step"><b>1. Explora el directorio</b><span>Filtra por materia y región. Revisa perfiles, experiencia, universidad, submaterias y señales de confianza.</span></div>
                    <div class="step"><b>2. Desbloquea contacto con Google</b><span>Si quieres contactar, ingresas con Google y confirmas nombre + WhatsApp. No pedimos formularios largos.</span></div>
                    <div class="step"><b>3. Contacta directo al abogado</b><span>Accedes a sus datos y generas un lead real para el profesional, que puede responder por sus canales registrados.</span></div>
                </div>
            </section>


            <section class="panel">
                <h2>Materias y submaterias</h2>
                <p>Explora páginas por materia con submaterias frecuentes y acceso directo al directorio filtrado.</p>
                <div class="grid2" style="margin-top:10px;">
                    <a class="mini" href="/materias/derecho-civil"><small>Materia</small><strong>Derecho Civil</strong></a>
                    <a class="mini" href="/materias/derecho-familiar"><small>Materia</small><strong>Derecho Familiar</strong></a>
                    <a class="mini" href="/materias/derecho-laboral"><small>Materia</small><strong>Derecho Laboral</strong></a>
                    <a class="mini" href="/materias/derecho-penal"><small>Materia</small><strong>Derecho Penal</strong></a>
                    <a class="mini" href="/materias/derecho-comercial"><small>Materia</small><strong>Derecho Comercial</strong></a>
                    <a class="mini" href="/materias/derecho-tributario"><small>Materia</small><strong>Derecho Tributario</strong></a>
                    <a class="mini" href="/materias/proteccion-al-consumidor"><small>Materia</small><strong>Protección al Consumidor</strong></a>
                    <a class="mini" href="/materias/derechos-humanos"><small>Materia</small><strong>Derechos Humanos</strong></a>
                </div>
            </section>

            <section class="panel">
                <h2>Programa profesional (abogados)</h2>
                <p>Los abogados usan la misma cuenta Google, pero deben postular al programa profesional. Tu Estudio Juridico revisa la postulación y solo perfiles aprobados acceden al panel de leads y aparecen como profesionales activos en el directorio.</p>
                <div class="cta-row" style="margin-top:10px;">
                    <a class="btn btn-primary" href="/explorar">Explorar abogados</a>
                    <a class="btn btn-ghost" href="/acceso-profesional">Acceso profesional</a>
                    <a class="btn btn-ghost" href="/inicio">Cómo funciona</a>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
