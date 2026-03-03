<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload | Tu Estudio Juridico</title>
    <style>
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 860px; margin: 0 auto; padding: 14px; }
        .stack { display:grid; gap:10px; }
        .card { border:1px solid var(--app-line); border-radius:16px; background:rgba(15,29,52,.62); padding:12px; }
        .card h1, .card h2 { margin:0; }
        .card p { margin:6px 0 0; color:var(--app-muted); font-size:12px; line-height:1.4; }
        .msg { border:1px solid var(--app-line); border-radius:12px; padding:10px; font-size:12px; }
        .msg.success { background: rgba(44, 191, 124, 0.08); }
        .msg.error { background: rgba(255, 88, 88, 0.08); }
        .file-input { width:100%; border:1px dashed var(--app-line-strong); border-radius:12px; padding:12px; background:rgba(255,255,255,.02); }
        table { width:100%; border-collapse:collapse; font-size:11px; }
        th, td { text-align:left; padding:8px 6px; border-bottom:1px solid rgba(255,255,255,.06); }
        th { color:var(--app-muted); text-transform:uppercase; font-size:10px; }
        pre.preview { margin:8px 0 0; max-height:420px; overflow:auto; background:rgba(8,17,31,.7); border:1px solid var(--app-line); border-radius:12px; padding:10px; font-size:11px; line-height:1.35; white-space:pre-wrap; word-break:break-word; }
    </style>
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
</head>
<body class="theme-black">
    <main class="wrap">
        <section class="stack">
            <section class="app-toolbar">
                <div>
                    <div class="title">Upload</div>
                    <div class="sub">Sube un archivo para revisarlo desde la VM</div>
                </div>
                <span class="dot" aria-hidden="true"></span>
            </section>

            <section class="card">
                <h1 style="font-size:16px;">Subir archivo</h1>
                <p>Útil para subir documentos como `777.txt` y luego contrastarlos con el estado actual del sitio. Límite: 5 MB.</p>
                <?php if (!empty($mensaje)): ?>
                    <div class="msg <?= htmlspecialchars((string)($tipo ?? '')) ?>" style="margin-top:10px;">
                        <?= htmlspecialchars((string)$mensaje) ?>
                        <?php if (!empty($saved_path)): ?>
                            <div style="margin-top:6px; color:var(--app-ink);">Ruta: <code><?= htmlspecialchars((string)$saved_path) ?></code></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data" style="margin-top:10px; display:grid; gap:10px;">
                    <input class="file-input" type="file" name="archivo" required>
                    <button class="btn btn-primary" type="submit">Subir archivo</button>
                </form>
            </section>

            <section class="card">
                <h2 style="font-size:14px;">Archivos recientes</h2>
                <?php if (empty($recent_files)): ?>
                    <p>No hay archivos subidos todavía.</p>
                <?php else: ?>
                    <div style="overflow:auto; margin-top:8px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tamaño</th>
                                    <th>Fecha</th>
                                    <th>Ruta</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_files as $f): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)$f['name']) ?></td>
                                        <td><?= number_format(((int)$f['size']) / 1024, 1, ',', '.') ?> KB</td>
                                        <td><?= htmlspecialchars((string)$f['mtime']) ?></td>
                                        <td><code><?= htmlspecialchars((string)$f['path']) ?></code></td>
                                        <td><a class="btn btn-ghost" style="padding:6px 10px; font-size:11px;" href="/upload?preview=<?= urlencode((string)$f['path']) ?>">Vista previa</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <?php if (!empty($preview_path)): ?>
            <section class="card">
                <h2 style="font-size:14px;">Vista previa</h2>
                <p><code><?= htmlspecialchars((string)$preview_path) ?></code></p>
                <pre class="preview"><?= htmlspecialchars((string)($preview ?? '')) ?></pre>
            </section>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
