<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Completa tu contacto | Tu Estudio Juridico</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="stylesheet" href="/app-shell.css?v=202602231">
  <script src="/app-shell.js?v=202602231" defer></script>
  <style>
    .gate-wrap{max-width:620px;margin:0 auto;padding:18px 14px 28px;display:grid;gap:12px}
    .gate-card{border:1px solid var(--app-line);border-radius:16px;background:rgba(11,24,48,.88);padding:14px;display:grid;gap:12px;box-shadow:0 8px 18px rgba(0,0,0,.16)}
    .gate-card h1{margin:0;font-size:22px;line-height:1.1;color:#fff8ef;font-weight:800}
    .gate-card p{margin:0;color:#eef4ff;line-height:1.45;font-size:14px}
    .gate-form{display:grid;gap:10px}
    .gate-form label{display:grid;gap:6px;color:#f4efe7;font-weight:700;font-size:13px}
    .gate-form .input{background:#f4efe7 !important;color:#101827 !important;border:1px solid rgba(16,24,39,.14) !important;min-height:42px;font-size:14px}
    .gate-form .input::placeholder{color:#566274}
    .msg{border-radius:12px;padding:10px 12px;font-size:13px;border:1px solid var(--app-line)}
    .msg.success{background:rgba(34,197,94,.12);color:#d7ffe5;border-color:rgba(34,197,94,.28)}
    .msg.error{background:rgba(239,68,68,.12);color:#ffe2e2;border-color:rgba(239,68,68,.28)}
    .msg.info{background:rgba(59,130,246,.12);color:#dbeafe;border-color:rgba(59,130,246,.28)}
  </style>
</head>
<?php $e = strtolower(trim((string)($_SESSION['email'] ?? ''))); ?>
<body class="theme-black<?= ($e === 'gmcalderonlewin@gmail.com') ? ' theme-admin' : ' theme-client-lite' ?>">
  <main class="gate-wrap">
    <div class="gate-card">
      <h1>Completa tu contacto para ver perfiles</h1>
      <p>Antes de acceder a perfiles de abogados, necesitamos tu nombre y WhatsApp. Esto nos permite habilitar el contacto y registrar interacciones reales.</p>
      <?php if (!empty($mensaje)): ?>
        <div class="msg <?= htmlspecialchars((string)($tipo_mensaje ?? 'info')) ?>"><?= htmlspecialchars((string)$mensaje) ?></div>
      <?php endif; ?>
      <form class="gate-form" method="POST" action="/guardar-contacto-basico">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
        <input type="hidden" name="next" value="<?= htmlspecialchars((string)($next ?? '/explorar')) ?>">
        <label>
          Tu nombre
          <input class="input" type="text" name="nombre" required value="<?= htmlspecialchars((string)($user['nombre'] ?? '')) ?>" placeholder="Nombre y apellido">
        </label>
        <label>
          WhatsApp
          <input class="input" type="tel" name="whatsapp" required value="<?= !empty($user['whatsapp']) ? '+56' . htmlspecialchars((string)$user['whatsapp']) : '' ?>" placeholder="+56912345678">
        </label>
        <button class="btn btn-primary" type="submit">Guardar y continuar</button>
      </form>
    </div>
  </main>
</body>
</html>
