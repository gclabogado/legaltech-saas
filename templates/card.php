<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi tarjeta | Tu Estudio Juridico</title>
    <style>
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 820px; margin: 0 auto; padding: 0 14px 28px; }
        .shell { margin-top: 12px; display:grid; gap:10px; }
        .hero { border:1px solid var(--app-line); border-radius:18px; overflow:hidden; background:
            radial-gradient(420px 180px at 90% 0%, rgba(92,142,214,.16), transparent 62%),
            radial-gradient(320px 180px at 0% 100%, rgba(216,194,165,.10), transparent 65%),
            rgba(15,29,52,.55); }
        .hero-cover { position:relative; aspect-ratio: 16/7; border-bottom:1px solid var(--app-line); background:
            radial-gradient(140px 100px at 20% 30%, rgba(216,194,165,.14), transparent 70%),
            radial-gradient(160px 110px at 80% 60%, rgba(92,142,214,.14), transparent 70%),
            #0b172b; }
        .hero-cover::after { content:""; position:absolute; inset:auto 0 0 0; height:55%; background:linear-gradient(180deg, transparent, rgba(8,17,31,.86)); }
        .hero-overlay { position:absolute; inset:auto 10px 10px 10px; display:flex; gap:12px; align-items:flex-end; z-index:2; flex-wrap:wrap; }
        .avatar-wrap { width:86px; height:86px; border-radius:20px; overflow:hidden; border:1px solid var(--app-line); background:rgba(255,255,255,.04); display:grid; place-items:center; }
        .avatar-wrap img { width:100%; height:100%; object-fit:cover; display:block; }
        .avatar-fallback { font-size:24px; font-weight:700; color:var(--app-brand-2); }
        .hero-copy { display:grid; gap:4px; }
        .pill { display:inline-flex; width:max-content; align-items:center; gap:6px; padding:4px 8px; border-radius:999px; font-size:10px; font-weight:700; border:1px solid var(--app-line-strong); background:rgba(8,17,31,.58); color:var(--app-brand-2); }
        .hero-title { margin:0; font-size:22px; line-height:.98; }
        .hero-sub { font-size:11px; color:var(--app-muted); }
        .hero-body { padding:10px; display:grid; gap:10px; }
        .info { border:1px solid var(--app-line); border-radius:14px; padding:10px; background:rgba(255,255,255,.03); }
        .info h2 { margin:0 0 6px; font-size:13px; }
        .info p, .muted { margin:0; font-size:11px; line-height:1.45; color:var(--app-muted); }
        .actions { display:flex; gap:8px; flex-wrap:wrap; }
        .public-link { font-size:12px; font-weight:700; word-break: break-all; }
    </style>
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<body class="theme-black">
    <nav class="nav">
        <div class="nav-inner">
            <a class="brand" href="/explorar">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/explorar">Explorar</a>
                <a class="active" href="/mi-tarjeta">Mi tarjeta</a>
            </div>
            <div class="nav-actions">
                <a class="btn btn-ghost" href="/panel">Mi panel</a>
                <a class="btn btn-primary" href="/logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <section class="shell">
            <section class="hero">
                <div class="hero-cover">
                    <div class="hero-overlay">
                        <div class="avatar-wrap">
                            <?php $fotoTarjeta = trim((string)($abogado['foto_final'] ?? '')); ?>
                            <?php $cardNameSeed = (string)($abogado['nombre'] ?? 'A'); $cardFirstChar = function_exists('mb_substr') ? mb_substr($cardNameSeed,0,1,'UTF-8') : substr($cardNameSeed,0,1); $avatarInitialCard = function_exists('mb_strtoupper') ? mb_strtoupper((string)$cardFirstChar,'UTF-8') : strtoupper((string)$cardFirstChar); ?>
                            <?php if ($fotoTarjeta !== ''): ?>
                                <img src="<?= htmlspecialchars($fotoTarjeta) ?>" alt="Avatar" onerror="this.style.display='none'; var fb=this.parentElement.querySelector('.avatar-fallback'); if(fb){fb.style.display='flex';}">
                                <div class="avatar-fallback" style="display:none;"><?= htmlspecialchars($avatarInitialCard) ?></div>
                            <?php else: ?>
                                <div class="avatar-fallback"><?= htmlspecialchars($avatarInitialCard) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="hero-copy">
                            <span class="pill"><?= htmlspecialchars($abogado['especialidad'] ?? 'Especialidad') ?></span>
                            <h1 class="hero-title">Mi tarjeta pública</h1>
                            <div class="hero-sub">
                                <?= !empty($abogado['universidad']) ? htmlspecialchars($abogado['universidad']) : 'Universidad no indicada' ?>
                                ·
                                <?= !empty($abogado['experiencia']) ? htmlspecialchars($abogado['experiencia']) : 'Experiencia comprobable' ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="hero-body">
                    <div class="info">
                        <h2>Enlace público</h2>
                        <div class="muted">Tu ficha visible en el directorio</div>
                        <div class="public-link" style="margin-top:6px;">example.com/<?= htmlspecialchars($abogado['slug']) ?></div>
                    </div>
                    <div class="actions">
                        <a class="btn btn-primary" href="/<?= htmlspecialchars($abogado['slug']) ?>">Ver ficha</a>
                        <button class="btn btn-ghost" onclick="copyLink('https://example.com/<?= htmlspecialchars($abogado['slug']) ?>')">Copiar link</button>
                        <a class="btn btn-ghost" href="/panel">Volver</a>
                    </div>
                </div>
            </section>
        </section>
    </main>

    <script>
        async function copyLink(link) {
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(link);
                    return;
                }
            } catch (_) {}
            const input = document.createElement('input');
            input.value = link;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        }
    </script>
</body>
</html>
