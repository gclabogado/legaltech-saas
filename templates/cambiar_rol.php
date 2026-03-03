<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar perfil | Tu Estudio Juridico</title>
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
        .wrap { max-width: 640px; margin: 0 auto; padding: 32px 20px; }
        .nav { position: sticky; top: 0; z-index: 60; background: rgba(244, 239, 230, 0.9); backdrop-filter: blur(10px); border-bottom: 1px solid var(--line); }
        .nav-inner { max-width: 640px; margin: 0 auto; padding: 14px 20px; display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
        .brand { font-family: "Cormorant Garamond", serif; font-size: 26px; letter-spacing: 0.4px; }
        .nav-links { display: flex; gap: 10px; flex-wrap: wrap; font-size: 13px; }
        .nav-links a { padding: 6px 12px; border-radius: 999px; border: 1px solid transparent; }
        .nav-links a.active, .nav-links a:hover { border-color: var(--line); background: #fff; }
        .nav-actions { display: flex; gap: 10px; margin-left: auto; flex-wrap: wrap; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: var(--radius); padding: 18px; box-shadow: var(--shadow); animation: rise .5s ease; }
        .option { border: 1px solid var(--line); border-radius: 16px; padding: 16px; cursor: pointer; background: #fff; transition: transform .2s ease, border .2s ease; }
        .option:hover { transform: translateY(-2px); }
        .option.active { border-color: var(--accent-2); box-shadow: 0 16px 32px rgba(27, 92, 90, 0.18); }
        .muted { color: var(--muted); font-size: 12px; }
        .cta { display: inline-flex; align-items: center; justify-content: center; padding: 14px 20px; border-radius: 999px; font-weight: 700; border: 1px solid transparent; cursor: pointer; width: 100%; transition: transform .2s ease, box-shadow .2s ease; }
        .cta-primary { background: linear-gradient(135deg, #ff7a45 0%, #d84b1e 100%); color: #fff; box-shadow: 0 16px 32px rgba(216, 75, 30, 0.24); }
        .cta-primary:hover { transform: translateY(-1px); }
        .cta-ghost { background: #fff; border: 1px solid var(--line); }
        .stack { display: grid; gap: 12px; }
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
                <a class="active" href="/completar-datos">Mi panel</a>
            </div>
            <div class="nav-actions">
                <a class="cta cta-ghost" href="/completar-datos" style="width:auto; padding: 8px 14px;">Volver</a>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <section class="card" style="margin-top: 16px;">
            <h1 style="margin: 0 0 6px;">Hola, <?= htmlspecialchars(explode(' ', $user['nombre'])[0]) ?>.</h1>
            <p class="muted">Define como quieres usar Tu Estudio Juridico.</p>
        </section>

        <div class="stack" style="margin-top: 16px;">
            <div id="option-abogado" onclick="selectOption('abogado')" class="option <?= ($current_role === 'abogado') ? 'active' : '' ?>">
                <strong>Soy abogado</strong>
                <div class="muted">Recibe casos y gestiona tu CRM.</div>
                <?php if($current_role === 'abogado'): ?>
                    <div class="muted" style="margin-top:6px;">Actual</div>
                <?php endif; ?>
            </div>

            <div id="option-cliente" onclick="selectOption('cliente')" class="option <?= ($current_role === 'cliente') ? 'active' : '' ?>">
                <strong>Busco abogado</strong>
                <div class="muted">Publica tu caso y recibe contacto directo.</div>
                <?php if($current_role === 'cliente'): ?>
                    <div class="muted" style="margin-top:6px;">Actual</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="margin-top: 16px;">
            <form id="role-form" method="POST" action="/actualizar-rol" class="stack">
                <input type="hidden" name="nuevo_rol" id="nuevo_rol" value="<?= $current_role ?>">
                <button type="submit" id="confirm-btn" class="cta cta-primary">
                    <?php if($current_role): ?>
                        Confirmar como <?= $current_role === 'abogado' ? 'Abogado' : 'Cliente' ?>
                    <?php else: ?>
                        Selecciona una opcion
                    <?php endif; ?>
                </button>
                <a class="cta cta-ghost" href="/completar-datos">Completar perfil mas tarde</a>
            </form>
        </div>
    </main>

    <script>
        let selectedRole = '<?= $current_role ?>';

        function selectOption(role) {
            selectedRole = role;
            document.getElementById('option-abogado').classList.toggle('active', role === 'abogado');
            document.getElementById('option-cliente').classList.toggle('active', role === 'cliente');
            document.getElementById('nuevo_rol').value = role;

            const btn = document.getElementById('confirm-btn');
            btn.textContent = 'Confirmar como ' + (role === 'abogado' ? 'Abogado' : 'Cliente');
        }

        document.getElementById('role-form').addEventListener('submit', function(e) {
            if (!selectedRole) {
                e.preventDefault();
                alert('Por favor selecciona una opcion');
            }
        });
    </script>
</body>
</html>
