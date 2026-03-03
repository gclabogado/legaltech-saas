<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Caso | Tu Estudio Juridico</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4efe6;
            --ink: #14121d;
            --muted: #5f5b68;
            --accent: #e25a2b;
            --accent-2: #0f6b5f;
            --card: #fffdf9;
            --line: #e4ddd3;
            --shadow: 0 24px 60px rgba(16, 12, 28, 0.12);
            --radius: 20px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Manrope", sans-serif;
            background:
                radial-gradient(circle at 15% -10%, #ffe5d7 0%, rgba(255, 229, 215, 0) 45%),
                radial-gradient(circle at 85% 0%, #d8f2ec 0%, rgba(216, 242, 236, 0) 42%),
                linear-gradient(transparent 94%, rgba(20, 18, 29, 0.03) 95%) 0 0 / 100% 32px,
                var(--bg);
            color: var(--ink);
        }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 980px; margin: 0 auto; padding: 0 20px 120px; }
        .nav { position: sticky; top: 0; z-index: 60; background: rgba(244, 239, 230, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .nav-inner { max-width: 980px; margin: 0 auto; padding: 14px 20px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .brand { font-family: "Cormorant Garamond", serif; font-size: 26px; letter-spacing: 0.4px; }
        .nav-links { display: flex; gap: 10px; flex-wrap: wrap; font-size: 13px; }
        .nav-links a { padding: 6px 12px; border-radius: 999px; border: 1px solid transparent; }
        .nav-links a.active, .nav-links a:hover { border-color: var(--line); background: #fff; }
        .nav-actions { display: flex; gap: 10px; margin-left: auto; flex-wrap: wrap; }
        .chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; border: 1px solid var(--line); background: #fff; }
        .grid { display: grid; gap: 16px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 18px; box-shadow: var(--shadow); animation: rise .5s ease; }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #fdf7f3; color: var(--accent); }
        .cta { display: inline-flex; align-items: center; justify-content: center; padding: 12px 18px; border-radius: 999px; font-weight: 700; border: 1px solid transparent; cursor: pointer; transition: transform .2s ease, box-shadow .2s ease; }
        .cta-primary { background: linear-gradient(135deg, #ff7a45 0%, #d84b1e 100%); color: #fff; box-shadow: 0 16px 32px rgba(216, 75, 30, 0.24); }
        .cta-primary:hover { transform: translateY(-1px); }
        .cta-ghost { background: #fff; border: 1px solid var(--line); }
        .muted { color: var(--muted); font-size: 12px; }
        .case { border: 1px dashed var(--line); border-radius: 16px; padding: 16px; background: #fff; }
        .footer-nav {
            position: fixed; bottom: 0; left: 0; right: 0; z-index: 60;
            background: #fff; border-top: 1px solid var(--line);
            display: grid; grid-template-columns: repeat(3, 1fr); text-align: center;
        }
        .footer-nav a { padding: 12px; font-size: 12px; font-weight: 600; }
        .active { color: var(--accent-2); }
        .avatar { width: 52px; height: 52px; border-radius: 16px; object-fit: cover; }
        @keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<body>
    <nav class="nav">
        <div class="nav-inner">
            <a class="brand" href="/">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/">Inicio</a>
                <a href="/explorar">Galeria</a>
                <a class="active" href="/bienvenida">Mi caso</a>
            </div>
            <div class="nav-actions">
                <a class="chip" href="/completar-datos">Editar</a>
                <a class="chip" href="/logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <section class="grid" style="margin-top: 18px;">
            <div class="card">
                <span class="badge">Solicitud activa</span>
                <h1 style="margin: 10px 0 6px;">Mi caso: <?= htmlspecialchars($cliente['especialidad'] ?? 'Sin definir') ?></h1>
                <p class="muted">Visible para abogados verificados.</p>
                <div class="case" style="margin-top: 12px;">
                    "<?= htmlspecialchars($cliente['descripcion_caso'] ?? 'No hay descripcion disponible.') ?>"
                </div>
                <div style="display:flex; gap:10px; flex-wrap: wrap; margin-top: 14px;">
                    <a class="cta cta-ghost" href="/completar-datos">Editar</a>
                    <a class="cta cta-ghost" href="/bajar-caso" onclick="return confirm('¿Seguro? Tu caso dejara de ser visible para abogados.')">Cerrar caso</a>
                </div>
            </div>

            <div class="card">
                <h2 style="margin: 0 0 6px;">Abogados interesados (<?= count($interesados) ?>)</h2>
                <?php if(empty($interesados)): ?>
                    <div class="case">
                        <p class="muted">Estamos notificando especialistas. Recibiras contactos aqui.</p>
                    </div>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach($interesados as $abg): ?>
                            <div class="case" style="display:flex; gap:12px; align-items:center;">
                                <img class="avatar" src="<?= $abg['google_picture'] ?: 'https://ui-avatars.com/api/?name='.urlencode($abg['nombre']).'&background=6d5efc&color=fff' ?>" alt="<?= htmlspecialchars($abg['nombre']) ?>">
                                <div style="flex:1;">
                                    <strong><?= htmlspecialchars($abg['nombre']) ?></strong>
                                    <div class="muted"><?= htmlspecialchars($abg['especialidad']) ?></div>
                                    <a class="muted" href="/<?= $abg['slug'] ?>">Ver perfil publico</a>
                                </div>
                                <a class="cta cta-primary" href="https://wa.me/56<?= $abg['whatsapp'] ?>?text=Hola%20<?= urlencode($abg['nombre']) ?>%2C%20vi%20tu%20perfil%20en%20Tu%20Estudio%20Juridico">WhatsApp</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <div class="footer-nav">
        <a href="/bienvenida" class="active">Mi caso</a>
        <a href="/completar-datos">Editar</a>
        <a href="/explorar">Explorar</a>
    </div>
</body>
</html>
