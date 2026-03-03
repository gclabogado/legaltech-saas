<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars((string)$materia_nombre) ?> | Tu Estudio Juridico</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg">
  <link rel="stylesheet" href="/app-shell.css?v=202602231">
  <style>
    *{box-sizing:border-box} a{text-decoration:none;color:inherit} .wrap{max-width:900px;margin:0 auto;padding:14px} .stack{display:grid;gap:10px} .card{border:1px solid var(--app-line);border-radius:14px;background:rgba(15,29,52,.58);padding:12px} .chips{display:flex;gap:8px;flex-wrap:wrap} .chipx{border:1px solid var(--app-line);border-radius:999px;padding:6px 10px;font-size:12px;background:rgba(255,255,255,.03)} .mutedx{color:var(--app-muted);font-size:12px;line-height:1.4} .grid2{display:grid;grid-template-columns:1fr 1fr;gap:8px}@media(max-width:760px){.grid2{grid-template-columns:1fr}}
  </style>
</head>
<?php $themeEmail = strtolower(trim((string)($_SESSION['email'] ?? ''))); $isAdminTheme = ($themeEmail === 'gmcalderonlewin@gmail.com'); $isClientTheme = !$isAdminTheme && !empty($_SESSION['user_id']) && (($_SESSION['rol'] ?? 'cliente') === 'cliente'); $bodyThemeClass = 'theme-black' . ($isAdminTheme ? ' theme-admin' : ' theme-olive'); ?>
<body class="<?= htmlspecialchars($bodyThemeClass) ?>">
<main class="wrap">
  <section class="stack">
    <section class="app-toolbar">
      <div><div class="title"><?= htmlspecialchars((string)$materia_nombre) ?></div><div class="sub">Submaterias frecuentes y acceso al directorio filtrado</div></div><span class="dot" aria-hidden="true"></span>
    </section>
    <section class="card">
      <div class="grid2">
        <div>
          <div class="mutedx">Abogados publicados en esta materia</div>
          <div style="font-weight:800;font-size:22px;margin-top:2px;"><?= number_format((int)($total_materia ?? 0),0,',','.') ?></div>
        </div>
        <div style="display:flex;align-items:end;justify-content:flex-end;gap:8px;flex-wrap:wrap;">
          <a class="btn btn-primary" href="/explorar?especialidad=<?= urlencode((string)$materia_nombre) ?>">Ver abogados de esta materia</a>
          <a class="btn btn-ghost" href="/explorar">Volver al directorio</a>
        </div>
      </div>
    </section>
    <section class="card">
      <h2 style="margin:0 0 8px;font-size:14px;">Submaterias frecuentes</h2>
      <div class="chips">
        <?php foreach (($submaterias ?? []) as $sub): ?>
          <span class="chipx"><?= htmlspecialchars((string)$sub) ?></span>
        <?php endforeach; ?>
      </div>
      <p class="mutedx" style="margin:10px 0 0;">Estos temas ayudan a los abogados a destacar su experiencia en el perfil. En el directorio público el filtro principal sigue siendo simple: materia + región.</p>
    </section>
    <section class="card">
      <h2 style="margin:0 0 8px;font-size:14px;">Cómo usar esta categoría</h2>
      <div class="mutedx">1. Revisa la materia y submaterias. 2. Ve al directorio filtrado. 3. Compara perfiles, universidad, experiencia, región y señales de confianza antes de contactar.</div>
    </section>
  </section>
</main>
</body>
</html>
