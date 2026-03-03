<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programa profesional | Tu Estudio Juridico</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 880px; margin: 0 auto; padding: 0 14px 28px; }
        .stack { display:grid; gap:10px; margin-top:12px; }
        .card { border:1px solid var(--app-line); border-radius:16px; padding:12px; background:rgba(15,29,52,.62); }
        .card h1, .card h2 { margin:0; }
        .card p { margin:6px 0 0; color:var(--app-muted); font-size:12px; line-height:1.4; }
        .grid { display:grid; gap:8px; grid-template-columns:1fr 1fr; }
        .badge-row { display:flex; gap:6px; flex-wrap:wrap; }
        .badge { border:1px solid var(--app-line-strong); border-radius:999px; padding:4px 8px; font-size:10px; font-weight:700; }
        .form-grid { display:grid; gap:10px; margin-top:10px; }
        .form-grid label { display:grid; gap:6px; font-size:11px; font-weight:700; }
        .input { width:100%; border-radius:12px; border:1px solid var(--app-line); padding:10px 12px; background:rgba(8,17,31,.75); color:var(--app-ink); }
        .actions { display:grid; gap:8px; grid-template-columns:1fr; margin-top:8px; }
        .msg { border:1px solid var(--app-line); border-radius:12px; padding:10px; font-size:12px; }
        @media (max-width: 760px) { .grid { grid-template-columns:1fr; } }
    </style>
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<?php
    $proThemeEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
    $profilePct = (int)($perfil_completion_pct ?? 0);
    $profileChecklist = is_array($perfil_completion_checklist ?? null) ? $perfil_completion_checklist : [];
    $canEditLawyerProfile = !empty($can_edit_lawyer_profile);
    $hasLawyerRequest = !empty($has_lawyer_request);
    $hasLawyerAccess = !empty($has_lawyer_access);
    $pendingItems = [];
    $checklistLabels = [
        'whatsapp' => 'WhatsApp profesional',
        'materia' => 'Materia principal',
        'submaterias' => 'Submaterias o materia secundaria',
        'universidad' => 'Universidad',
        'experiencia' => 'Año de titulación / experiencia',
        'cobertura' => 'Cobertura geográfica',
        'sexo' => 'Sexo',
        'links' => 'Al menos un link público',
    ];
    foreach ($checklistLabels as $key => $label) {
        if (empty($profileChecklist[$key])) {
            $pendingItems[] = $label;
        }
    }
?>
<body class="theme-black<?= ($proThemeEmail === 'gmcalderonlewin@gmail.com') ? ' theme-admin' : ' theme-olive' ?>">
    <nav class="nav">
        <div class="nav-inner">
            <a class="brand" href="/explorar">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/explorar">Explorar</a>
                <a class="active" href="/acceso-profesional">Programa profesional</a>
            </div>
            <div class="nav-actions">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <a class="btn btn-ghost" href="/panel">Mi panel</a>
                    <a class="btn btn-ghost" href="/logout">Salir</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="/login-google?next=/acceso-profesional">Entra con Google</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <section class="stack">
            <?php if (!empty($mensaje ?? null)): ?>
                <div class="msg"><?= htmlspecialchars((string)$mensaje) ?></div>
            <?php endif; ?>

            <section class="app-toolbar">
                <div>
                    <div class="title">Programa profesional Tu Estudio Juridico</div>
                    <div class="sub">Un solo login con Google. Activación por aprobación.</div>
                </div>
                <span class="dot" aria-hidden="true"></span>
            </section>

            <section class="app-hero-card">
                <h1>Postula con tu misma cuenta Google</h1>
                <p>Este es tu centro único para postular, seguir el estado y completar el perfil profesional. La activación se hace manualmente.</p>
            </section>

            <section class="grid">
                <div class="card">
                    <h2 style="font-size:14px;">Flujo</h2>
                    <p>1) Entra con Google</p>
                    <p>2) Envía tu solicitud</p>
                    <p>3) Completa tu perfil hasta 80%</p>
                    <p>4) Activación manual por admin</p>
                </div>
                <div class="card">
                    <h2 style="font-size:14px;">Estado actual</h2>
                    <?php if (empty($user)): ?>
                        <p>No has iniciado sesión.</p>
                    <?php else: ?>
                        <div class="badge-row" style="margin-top:8px;">
                            <span class="badge">Cuenta Google activa</span>
                            <?php if ($hasLawyerAccess): ?>
                                <span class="badge">Acceso profesional activo</span>
                            <?php elseif ($hasLawyerRequest): ?>
                                <span class="badge">Pendiente revisión</span>
                            <?php else: ?>
                                <span class="badge">Sin postulación</span>
                            <?php endif; ?>
                            <?php if ($canEditLawyerProfile): ?>
                                <span class="badge">Perfil <?= $profilePct ?>%</span>
                            <?php endif; ?>
                        </div>
                        <p><?= htmlspecialchars((string)($user['email'] ?? '')) ?></p>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (empty($_SESSION['user_id'])): ?>
                <section class="card">
                    <h2 style="font-size:14px;">Paso 1: entra con Google</h2>
                    <p>Usa el mismo correo que quieres asociar al perfil de abogado.</p>
                    <div class="actions">
                        <a class="btn btn-primary" href="/login-google?next=/acceso-profesional">Entra con Google para postular</a>
                    </div>
                </section>
            <?php else: ?>
                <?php if ($hasLawyerAccess): ?>
                    <section class="card">
                        <h2 style="font-size:14px;">Acceso profesional activo</h2>
                        <p>Tu cuenta ya puede usar el dashboard profesional. Si quieres aparecer en el directorio, mantén el perfil sobre 80%.</p>
                        <div class="actions">
                            <a class="btn btn-primary" href="/dashboard">Ir a panel profesional</a>
                            <a class="btn btn-ghost" href="/completar-datos?modo=abogado">Editar perfil profesional</a>
                        </div>
                    </section>
                <?php elseif ($hasLawyerRequest): ?>
                    <section class="card">
                        <h2 style="font-size:14px;">Postulación enviada</h2>
                        <p>Tu solicitud está en revisión. Mientras tanto, completa el perfil para acelerar la aprobación y quedar listo para publicación.</p>
                        <div class="actions">
                            <a class="btn btn-primary" href="/completar-datos?modo=abogado">Completar perfil profesional</a>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="card">
                        <h2 style="font-size:14px;">Formulario de postulación</h2>
                        <p>Datos mínimos para iniciar revisión.</p>
                        <form method="POST" action="/aplicar-programa-profesional" class="form-grid">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                            <label>
                                Nombre completo del abogado
                                <input class="input" type="text" name="nombre_legal_abogado" required value="<?= htmlspecialchars((string)($user['nombre'] ?? '')) ?>" placeholder="Nombre y apellido">
                            </label>
                            <label>
                                WhatsApp (+569XXXXXXXX)
                                <input class="input" type="tel" name="whatsapp" required value="<?= !empty($user['whatsapp']) ? '+56' . htmlspecialchars((string)$user['whatsapp']) : '' ?>" placeholder="+56912345678">
                            </label>
                            <label>
                                RUT del abogado
                                <input class="input" type="text" name="rut_abogado" required value="<?= htmlspecialchars((string)($user['rut_abogado'] ?? '')) ?>" placeholder="12.345.678-9">
                            </label>
                            <button class="btn btn-primary" type="submit">Enviar postulación</button>
                        </form>
                    </section>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['user_id']) && $canEditLawyerProfile): ?>
                <section class="card">
                    <h2 style="font-size:14px;">Checklist del perfil profesional</h2>
                    <p>Progreso actual: <strong><?= $profilePct ?>%</strong>. Al llegar al 80% tu perfil queda listo para publicarse cuando admin lo apruebe.</p>
                    <div class="badge-row" style="margin-top:8px;">
                        <?php if (empty($pendingItems)): ?>
                            <span class="badge">Checklist completo</span>
                        <?php else: ?>
                            <?php foreach ($pendingItems as $item): ?>
                                <span class="badge"><?= htmlspecialchars($item) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="actions">
                        <a class="btn btn-primary" href="/completar-datos?modo=abogado">Abrir editor del perfil</a>
                        <?php if ($hasLawyerAccess): ?>
                            <a class="btn btn-ghost" href="/dashboard">Ir al dashboard</a>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
