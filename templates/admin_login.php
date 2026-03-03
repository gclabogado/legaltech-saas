<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | Tu Estudio Juridico</title>
  <link rel="stylesheet" href="/app-shell.css?v=202602231">
  <style>
    body{margin:0;background:#070b14;color:#f3f7ff;font-family:Arial,sans-serif}
    .wrap{max-width:520px;margin:0 auto;padding:24px 16px 84px}
    .card{background:rgba(15,15,19,.94);border:1px solid rgba(255,255,255,.10);border-radius:18px;padding:18px;display:grid;gap:12px}
    h1{margin:0;font-size:24px;font-weight:800}
    .muted{color:#b8c4d9;font-size:14px;line-height:1.4}
    label{display:grid;gap:6px;font-weight:700;font-size:14px}
    input{width:100%;padding:12px 14px;border-radius:12px;border:1px solid rgba(255,255,255,.16);background:#f6f7fb;color:#101521;font-size:15px}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:12px 16px;border-radius:12px;border:1px solid rgba(255,255,255,.1);font-weight:700;text-decoration:none;color:#fff;background:linear-gradient(135deg,#e6477d,#a61e4d);cursor:pointer}
    .btn-ghost{background:transparent;color:#d6e0f2}
    .row{display:flex;gap:10px;flex-wrap:wrap}
    .alert{padding:10px 12px;border-radius:12px;font-size:13px;font-weight:700}
    .alert-error{background:rgba(255,77,109,.14);color:#ffd2da;border:1px solid rgba(255,77,109,.25)}
    .alert-success{background:rgba(67,214,144,.14);color:#cffae6;border:1px solid rgba(67,214,144,.25)}
    .alert-info{background:rgba(92,142,214,.14);color:#d6e7ff;border:1px solid rgba(92,142,214,.25)}
  </style>
</head>
<body>
  <main class="wrap">
    <section class="card">
      <h1>Admin</h1>
      <div class="muted">Acceso administrativo separado del login Google. Usa usuario y clave para gestionar registros y activar perfiles de abogado.</div>
      <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= htmlspecialchars(($tipo_mensaje ?? 'info') === 'error' ? 'error' : (($tipo_mensaje ?? 'info') === 'success' ? 'success' : 'info')) ?>"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>
      <form method="post" action="/admin-login" class="card" style="padding:0;border:none;background:transparent;gap:10px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
        <label>Usuario
          <input type="text" name="username" autocomplete="username" required placeholder="admin">
        </label>
        <label>Clave
          <input type="password" name="password" autocomplete="current-password" required placeholder="••••••••">
        </label>
        <div class="row">
          <button class="btn" type="submit">Entrar al dashboard admin</button>
          <a class="btn btn-ghost" href="/">Volver</a>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
