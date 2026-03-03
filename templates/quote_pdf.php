<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización #<?= (int)($quote['id'] ?? 0) ?> | Tu Estudio Juridico</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            color: #111827;
            background: #eef2f7;
            font-family: "Segoe UI", Arial, sans-serif;
        }
        .wrap {
            max-width: 210mm;
            margin: 0 auto;
            padding: 18px 12px 24px;
        }
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .toolbar-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 0 12px;
            border-radius: 10px;
            border: 1px solid #d1d5db;
            background: #fff;
            color: #111827;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-primary {
            background: #111827;
            border-color: #111827;
            color: #fff;
        }
        .sheet {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            padding: 18px;
        }
        .header {
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            gap: 12px;
            align-items: start;
            padding-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        .eyebrow {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .12em;
            color: #6b7280;
            font-weight: 800;
        }
        .title {
            margin: 6px 0 4px;
            font-size: 24px;
            line-height: 1.05;
            letter-spacing: -.03em;
        }
        .subtitle {
            margin: 0;
            color: #4b5563;
            font-size: 12px;
            line-height: 1.35;
        }
        .brand-card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 12px;
            background: #fafafa;
            font-size: 12px;
            line-height: 1.35;
        }
        .brand-card strong {
            display: block;
            font-size: 15px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 8px;
            margin-top: 10px;
        }
        .meta-box {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 9px 10px;
            background: #fafafa;
        }
        .meta-box span {
            display: block;
            font-size: 9px;
            font-weight: 800;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: .08em;
        }
        .meta-box strong {
            display: block;
            margin-top: 4px;
            font-size: 13px;
            line-height: 1.25;
        }
        .section {
            margin-top: 10px;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .section h2 {
            margin: 0 0 6px;
            font-size: 13px;
        }
        .card {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 10px 12px;
            background: #fff;
        }
        .body-text {
            margin: 0;
            color: #374151;
            line-height: 1.4;
            white-space: pre-wrap;
            font-size: 12px;
        }
        .sheet-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.12fr) minmax(0, .88fr);
            gap: 10px;
            margin-top: 10px;
            align-items: start;
        }
        .totals {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 7px 0;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }
        .totals-table td:last-child {
            text-align: right;
            font-weight: 800;
        }
        .totals-table tr:last-child td {
            border-bottom: none;
            font-size: 15px;
        }
        .footer-note {
            margin-top: 10px;
            font-size: 10px;
            color: #6b7280;
            line-height: 1.35;
            white-space: pre-wrap;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .muted-line {
            color: #6b7280;
            font-size: 11px;
            line-height: 1.35;
        }
        .compact-stack {
            display: grid;
            gap: 10px;
        }
        @media (max-width: 860px) {
            .header, .meta-grid, .sheet-grid, .totals { grid-template-columns: 1fr; }
            .sheet { padding: 18px; }
        }
        @media print {
            body { background: #fff; }
            .wrap { max-width: none; margin: 0; padding: 0; }
            .toolbar { display: none !important; }
            .sheet {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
            }
            @page {
                size: auto;
                margin: 8mm;
            }
        }
    </style>
</head>
<body>
<?php
    $branding = (array)($branding_settings ?? []);
    $brandEnabled = !empty($branding['enabled']);
    $quoteId = (int)($quote['id'] ?? 0);
    $fmt = static fn($value) => '$' . number_format((float)$value, 0, ',', '.');
    $compactText = static function ($value, int $limit): string {
        $text = trim(preg_replace('/\s+/u', ' ', (string)$value));
        if ($text === '') {
            return '';
        }
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($text, 'UTF-8') <= $limit) {
                return $text;
            }
            return rtrim(mb_substr($text, 0, max(0, $limit - 1), 'UTF-8')) . '…';
        }
        if (strlen($text) <= $limit) {
            return $text;
        }
        return rtrim(substr($text, 0, max(0, $limit - 1))) . '...';
    };
    $serviceTitle = $compactText((string)($quote['servicio_nombre_resuelto'] ?? 'Cotización legal'), 82);
    $detailText = $compactText((string)($quote['detalle'] ?? 'Por definir.'), 540);
    $notIncludedText = $compactText((string)($quote['no_incluye'] ?? ''), 220);
    $paymentText = $compactText((string)($quote['condiciones_pago'] ?? 'Forma de pago por definir.'), 180);
    $notesText = $compactText((string)($quote['notas'] ?? ''), 160);
    $legalNoticeText = $compactText((string)($branding['legal_notice'] ?? ''), 160);
?>
    <main class="wrap">
        <section class="toolbar">
            <div>
                <div class="eyebrow">Vista PDF</div>
                <div style="font-weight:800;">Cotización #<?= $quoteId ?></div>
            </div>
            <div class="toolbar-actions">
                <a class="btn" href="/dashboard?tab=quotes">Volver al dashboard</a>
                <button class="btn btn-primary" type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
            </div>
        </section>

        <article class="sheet">
            <header class="header">
                <div>
                    <div class="eyebrow">Cotización legal</div>
                    <h1 class="title"><?= htmlspecialchars($serviceTitle) ?></h1>
                    <p class="subtitle">
                        Documento generado el <?= htmlspecialchars((string)($generated_at ?? '')) ?>.
                        Estado actual: <?= htmlspecialchars((string)($quote['estado_ui'] ?? 'Borrador')) ?>.
                    </p>
                </div>
                <aside class="brand-card">
                    <?php if ($brandEnabled && !empty($branding['brand_name'])): ?>
                        <strong><?= htmlspecialchars((string)$branding['brand_name']) ?></strong>
                    <?php else: ?>
                        <strong><?= htmlspecialchars((string)($user['nombre'] ?? 'Abogado/a')) ?></strong>
                    <?php endif; ?>
                    <?php if ($brandEnabled && !empty($branding['legal_name'])): ?><div><?= htmlspecialchars((string)$branding['legal_name']) ?></div><?php endif; ?>
                    <?php if ($brandEnabled && !empty($branding['rut'])): ?><div>RUT <?= htmlspecialchars((string)$branding['rut']) ?></div><?php endif; ?>
                    <div style="margin-top:8px;">Contacto</div>
                    <?php if ($brandEnabled && !empty($branding['phone'])): ?><div><?= htmlspecialchars((string)$branding['phone']) ?></div><?php endif; ?>
                    <?php if ($brandEnabled && !empty($branding['email'])): ?><div><?= htmlspecialchars((string)$branding['email']) ?></div><?php endif; ?>
                    <?php if ($brandEnabled && !empty($branding['address'])): ?><div><?= htmlspecialchars((string)$branding['address']) ?></div><?php endif; ?>
                </aside>
            </header>

            <section class="meta-grid">
                <div class="meta-box">
                    <span>Cliente</span>
                    <strong><?= htmlspecialchars((string)($quote['client_name'] ?? 'Cliente')) ?></strong>
                </div>
                <div class="meta-box">
                    <span>Materia</span>
                    <strong><?= htmlspecialchars((string)($quote['materia'] ?? 'Por definir')) ?></strong>
                </div>
                <div class="meta-box">
                    <span>Plazo</span>
                    <strong><?= htmlspecialchars((string)($quote['plazo_estimado'] ?? 'Por definir')) ?></strong>
                </div>
                <div class="meta-box">
                    <span>Vigencia</span>
                    <strong><?= htmlspecialchars((string)($quote['vigencia'] ?? 'Por definir')) ?></strong>
                </div>
            </section>

            <section class="sheet-grid">
                <div class="compact-stack">
                    <section class="section" style="margin-top:0;">
                        <h2>Detalle de la propuesta</h2>
                        <div class="card">
                            <p class="body-text"><?= htmlspecialchars($detailText !== '' ? $detailText : 'Por definir.') ?></p>
                        </div>
                    </section>

                    <?php if ($notIncludedText !== ''): ?>
                        <section class="section">
                            <h2>No incluye</h2>
                            <div class="card">
                                <p class="body-text"><?= htmlspecialchars($notIncludedText) ?></p>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>

                <div class="totals">
                    <div class="card">
                        <h2 style="margin-top:0;">Condiciones</h2>
                        <p class="body-text"><?= htmlspecialchars($paymentText !== '' ? $paymentText : 'Forma de pago por definir.') ?></p>
                        <?php if (!empty($quote['payment_link'])): ?>
                            <div class="muted-line" style="margin-top:8px;">Pago online disponible</div>
                        <?php endif; ?>
                        <?php if ($notesText !== ''): ?>
                            <div style="margin-top:8px;">
                                <strong style="display:block; margin-bottom:4px; font-size:12px;">Notas</strong>
                                <p class="body-text"><?= htmlspecialchars($notesText) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card">
                        <h2 style="margin-top:0;">Resumen económico</h2>
                        <table class="totals-table">
                            <tr><td>Honorarios</td><td><?= $fmt($quote['honorarios'] ?? 0) ?></td></tr>
                            <tr><td>Gastos</td><td><?= $fmt($quote['gastos'] ?? 0) ?></td></tr>
                            <tr><td>Descuento</td><td><?= $fmt($quote['descuento'] ?? 0) ?></td></tr>
                            <tr><td>Anticipo</td><td><?= $fmt($quote['anticipo'] ?? 0) ?></td></tr>
                            <tr><td>Saldo</td><td><?= $fmt($quote['saldo'] ?? 0) ?></td></tr>
                            <tr><td>Total cotizado</td><td><?= $fmt($quote['total'] ?? 0) ?></td></tr>
                        </table>
                    </div>
                </div>
            </section>

            <?php if ($brandEnabled && $legalNoticeText !== ''): ?>
                <footer class="footer-note"><?= htmlspecialchars($legalNoticeText) ?></footer>
            <?php endif; ?>
        </article>
    </main>
</body>
</html>
