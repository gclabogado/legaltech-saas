<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotizador | Tu Estudio Juridico</title>
    <link rel="canonical" href="https://example.com/tarifario">
    <meta name="description" content="Cotizador protegido de Tu Estudio Juridico para preparar y enviar cotizaciones legales a clientes.">
    <meta property="og:title" content="Cotizador | Tu Estudio Juridico">
    <meta property="og:description" content="Cotizador protegido para preparar y compartir propuestas legales.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://example.com/tarifario">
    <meta property="og:image" content="https://example.com/og-lawyers-1200x630.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Cotizador | Tu Estudio Juridico">
    <meta name="twitter:description" content="Cotizador protegido para preparar y compartir propuestas legales.">
    <meta name="twitter:image" content="https://example.com/og-lawyers-1200x630.png">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <style>
        * { box-sizing: border-box; }
        a { color: inherit; text-decoration: none; }
        .wrap { max-width: 1180px; margin: 0 auto; padding: 0 14px 28px; }
        .stack { display:grid; gap:10px; margin-top:12px; }
        .card { border:1px solid var(--app-line); border-radius:16px; padding:12px; background:rgba(15,29,52,.62); }
        .card h1, .card h2, .card h3, .card p { margin:0; }
        .card p { margin-top:6px; color:var(--app-muted); font-size:12px; line-height:1.45; }
        .auth-wrap { max-width: 520px; margin: 28px auto 0; }
        .form-grid { display:grid; gap:10px; }
        .field-grid { display:grid; gap:10px; grid-template-columns:repeat(2, minmax(0, 1fr)); }
        .field-grid-3 { display:grid; gap:10px; grid-template-columns:repeat(3, minmax(0, 1fr)); }
        .layout { display:grid; gap:10px; grid-template-columns:minmax(0, 1.2fr) minmax(320px, .8fr); align-items:start; }
        .kpis { display:grid; gap:10px; grid-template-columns:repeat(3, minmax(0, 1fr)); }
        .kpi { border:1px solid var(--app-line); border-radius:14px; padding:12px; background:rgba(255,255,255,.04); }
        .kpi-label { font-size:11px; text-transform:uppercase; letter-spacing:.08em; color:#c8d8ff; font-weight:800; }
        .kpi-value { margin-top:6px; font-size:28px; font-weight:900; letter-spacing:-.04em; }
        .subtle-box { border:1px dashed rgba(121,168,255,.26); border-radius:14px; padding:12px; background:rgba(9,17,33,.48); }
        .section-title-mini { font-size:14px; font-weight:800; }
        .helper { display:block; margin-top:4px; color:var(--app-muted); font-size:11px; line-height:1.4; }
        label { display:grid; gap:6px; font-size:12px; font-weight:700; }
        .input, select, textarea {
            width:100%;
            border-radius:12px;
            border:1px solid var(--app-line);
            padding:10px 12px;
            background:rgba(8,17,31,.78);
            color:var(--app-ink);
        }
        textarea { min-height:110px; resize:vertical; }
        .preview {
            min-height:420px;
            white-space:pre-wrap;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            line-height:1.45;
        }
        .actions { display:grid; gap:8px; grid-template-columns:repeat(auto-fit, minmax(160px, 1fr)); margin-top:10px; }
        .row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .pill {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:6px 10px;
            border-radius:999px;
            border:1px solid var(--app-line-strong);
            background:rgba(121,168,255,.10);
            font-size:11px;
            font-weight:800;
        }
        .list { display:grid; gap:8px; margin-top:10px; }
        .list-item { border:1px solid var(--app-line); border-radius:12px; padding:10px; background:rgba(255,255,255,.03); }
        .alert { padding:10px 12px; border-radius:12px; font-size:13px; font-weight:700; }
        .alert-error { background:rgba(255,77,109,.14); color:#ffd2da; border:1px solid rgba(255,77,109,.25); }
        .alert-success { background:rgba(67,214,144,.14); color:#cffae6; border:1px solid rgba(67,214,144,.25); }
        .alert-info { background:rgba(92,142,214,.14); color:#d6e7ff; border:1px solid rgba(92,142,214,.25); }
        .footer { margin-top: 4px; color: var(--app-muted); font-size: 11px; text-align:center; }
        @media (max-width: 980px) {
            .layout, .field-grid, .field-grid-3, .kpis { grid-template-columns:1fr; }
        }
    </style>
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<?php
    $themeEmail = strtolower(trim((string)($_SESSION['email'] ?? '')));
    $bodyThemeClass = 'theme-black' . (($themeEmail === 'gmcalderonlewin@gmail.com') ? ' theme-admin' : ' theme-olive');
?>
<body class="<?= htmlspecialchars($bodyThemeClass) ?>">
    <nav class="nav">
        <div class="nav-inner">
            <a class="brand" href="/explorar">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/explorar">Explorar</a>
                <a class="active" href="/tarifario">Cotizador</a>
                <?php if (!empty($is_admin)): ?>
                    <a href="/admin">Admin</a>
                <?php endif; ?>
            </div>
            <div class="nav-actions">
                <?php if (!empty($is_admin)): ?>
                    <form method="post" action="/tarifario/logout" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                        <button class="btn btn-ghost" type="submit">Cerrar acceso</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-primary" href="#tarifario-login">Entrar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= htmlspecialchars(($tipo_mensaje ?? 'info') === 'error' ? 'error' : (($tipo_mensaje ?? 'info') === 'success' ? 'success' : 'info')) ?>" style="margin-top:12px;"><?= htmlspecialchars((string)$mensaje) ?></div>
        <?php endif; ?>

        <?php if (empty($is_admin)): ?>
            <section class="auth-wrap" id="tarifario-login">
                <section class="stack">
                    <section class="app-toolbar">
                        <div>
                            <div class="title">Cotizador protegido</div>
                            <div class="sub">Acceso restringido para preparar y enviar cotizaciones legales.</div>
                        </div>
                        <span class="dot" aria-hidden="true"></span>
                    </section>

                    <section class="app-hero-card">
                        <h1>Tarifario operativo para abogados</h1>
                        <p>Este modulo sirve para armar una propuesta, calcular total, anticipo y saldo, y generar un mensaje listo para compartir por WhatsApp o email.</p>
                    </section>

                    <section class="card">
                        <h2 style="font-size:16px;">Entrar al cotizador</h2>
                        <p>Usa el acceso admin del proyecto. La misma sesion habilita este modulo y el dashboard administrativo.</p>
                        <form method="post" action="/tarifario/login" class="form-grid" style="margin-top:10px;">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrf_token ?? '')) ?>">
                            <label>Usuario
                                <input class="input" type="text" name="username" autocomplete="username" required placeholder="admin">
                            </label>
                            <label>Clave
                                <input class="input" type="password" name="password" autocomplete="current-password" required placeholder="••••••••">
                            </label>
                            <div class="actions">
                                <button class="btn btn-primary" type="submit">Entrar al cotizador</button>
                                <a class="btn btn-ghost" href="/explorar">Volver al sitio</a>
                            </div>
                        </form>
                    </section>

                    <section class="subtle-box">
                        <strong style="display:block; font-size:14px;">Que hace este modulo</strong>
                        <div class="list">
                            <div class="list-item">Arma cotizaciones por cliente, materia y servicio.</div>
                            <div class="list-item">Calcula honorarios, gastos, descuento, anticipo y saldo.</div>
                            <div class="list-item">Genera texto listo para copiar o mandar por WhatsApp o email.</div>
                        </div>
                    </section>
                </section>
            </section>
        <?php else: ?>
            <section class="stack">
                <section class="app-toolbar">
                    <div>
                        <div class="title">Cotizador Tu Estudio Juridico</div>
                        <div class="sub">MVP para que los abogados preparen y manden cotizaciones rapidamente.</div>
                    </div>
                    <span class="dot" aria-hidden="true"></span>
                </section>

                <section class="app-hero-card">
                    <h1>Prepara una cotizacion y compartela en el momento.</h1>
                    <p>Completa los datos del cliente, define alcance y montos, y usa el mensaje generado para copiar, enviar por WhatsApp o abrir un borrador por email.</p>
                    <div class="row" style="margin-top:10px;">
                        <span class="pill">Sesion protegida por admin</span>
                        <span class="pill">Sin librerias extra</span>
                        <span class="pill">Listo para envio manual</span>
                    </div>
                </section>

                <section class="kpis">
                    <article class="kpi">
                        <div class="kpi-label">Total cotizado</div>
                        <div class="kpi-value" id="quoteTotal">$0</div>
                    </article>
                    <article class="kpi">
                        <div class="kpi-label">Anticipo</div>
                        <div class="kpi-value" id="quoteAdvance">$0</div>
                    </article>
                    <article class="kpi">
                        <div class="kpi-label">Saldo</div>
                        <div class="kpi-value" id="quoteBalance">$0</div>
                    </article>
                </section>

                <section class="layout">
                    <form class="card form-grid" id="quoteForm" autocomplete="off">
                        <section class="form-grid">
                            <h2 style="font-size:16px;">Datos del cliente</h2>
                            <div class="field-grid">
                                <label>Nombre cliente
                                    <input class="input" type="text" id="clientName" placeholder="Nombre y apellido">
                                </label>
                                <label>Whatsapp cliente
                                    <input class="input" type="text" id="clientWhatsapp" placeholder="+56912345678">
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>Email cliente
                                    <input class="input" type="email" id="clientEmail" placeholder="cliente@correo.cl">
                                </label>
                                <label>Materia
                                    <select id="matter">
                                        <option value="Derecho Civil">Derecho Civil</option>
                                        <option value="Derecho Familiar">Derecho Familiar</option>
                                        <option value="Derecho Laboral">Derecho Laboral</option>
                                        <option value="Derecho Penal">Derecho Penal</option>
                                        <option value="Derecho Comercial">Derecho Comercial</option>
                                        <option value="Derecho Tributario">Derecho Tributario</option>
                                        <option value="Proteccion al Consumidor">Proteccion al Consumidor</option>
                                        <option value="Derechos Humanos">Derechos Humanos</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                </label>
                            </div>
                        </section>

                        <section class="form-grid">
                            <h2 style="font-size:16px;">Propuesta</h2>
                            <div class="field-grid">
                                <label>Servicio
                                    <input class="input" type="text" id="serviceName" placeholder="Consulta, demanda, defensa, revision contractual">
                                </label>
                                <label>Modalidad
                                    <select id="serviceMode">
                                        <option value="Online">Online</option>
                                        <option value="Presencial">Presencial</option>
                                        <option value="Mixta">Mixta</option>
                                    </select>
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>Plazo estimado
                                    <input class="input" type="text" id="turnaround" placeholder="24 horas, 3 dias habiles, 2 semanas">
                                </label>
                                <label>Vigencia de la cotizacion
                                    <input class="input" type="text" id="validity" placeholder="7 dias corridos">
                                </label>
                            </div>
                            <label>Alcance
                                <textarea id="scope" placeholder="Describe lo incluido: reuniones, revision de antecedentes, escritos, comparecencias, negociacion, etc."></textarea>
                            </label>
                            <label>No incluye
                                <textarea id="exclusions" placeholder="Ej: tasas judiciales, peritajes, notariales, apelaciones, audiencias extraordinarias."></textarea>
                            </label>
                        </section>

                        <section class="form-grid">
                            <h2 style="font-size:16px;">Datos del abogado</h2>
                            <div class="field-grid">
                                <label>Nombre abogado
                                    <input class="input" type="text" id="lawyerName" placeholder="Nombre del abogado o abogada">
                                </label>
                                <label>Firma o estudio
                                    <input class="input" type="text" id="firmName" placeholder="Nombre del estudio">
                                </label>
                            </div>
                            <div class="field-grid">
                                <label>Whatsapp abogado
                                    <input class="input" type="text" id="lawyerWhatsapp" placeholder="+56912345678">
                                </label>
                                <label>Email abogado
                                    <input class="input" type="email" id="lawyerEmail" placeholder="abogado@estudio.cl">
                                </label>
                            </div>
                        </section>

                        <section class="form-grid">
                            <h2 style="font-size:16px;">Monto</h2>
                            <div class="field-grid-3">
                                <label>Honorarios base (CLP)
                                    <input class="input money" type="number" min="0" step="1000" id="baseFee" value="0">
                                </label>
                                <label>Gastos operativos (CLP)
                                    <input class="input money" type="number" min="0" step="1000" id="extraCosts" value="0">
                                </label>
                                <label>Descuento (CLP)
                                    <input class="input money" type="number" min="0" step="1000" id="discount" value="0">
                                </label>
                            </div>
                            <div class="field-grid-3">
                                <label>Anticipo (CLP)
                                    <input class="input money" type="number" min="0" step="1000" id="advance" value="0">
                                </label>
                                <label>Forma de pago
                                    <input class="input" type="text" id="paymentTerms" placeholder="Transferencia 50/50, 3 cuotas, contado">
                                </label>
                                <label>Link de pago
                                    <input class="input" type="url" id="paymentLink" placeholder="https://...">
                                </label>
                            </div>
                            <label>Notas adicionales
                                <textarea id="notes" placeholder="Condiciones, agenda, disponibilidad, requisito de documentos, etc."></textarea>
                            </label>
                        </section>
                    </form>

                    <aside class="stack">
                        <section class="card">
                            <h2 style="font-size:16px;">Mensaje listo para enviar</h2>
                            <p>El texto se actualiza en vivo segun los datos ingresados.</p>
                            <textarea id="quotePreview" class="input preview" spellcheck="false"></textarea>
                            <div class="actions">
                                <button class="btn btn-primary" type="button" id="copyQuote">Copiar texto</button>
                                <button class="btn btn-ghost" type="button" id="sendWhatsapp">Abrir WhatsApp</button>
                                <button class="btn btn-ghost" type="button" id="sendEmail">Abrir email</button>
                            </div>
                        </section>

                        <section class="subtle-box">
                            <strong style="display:block; font-size:14px;">Uso sugerido</strong>
                            <div class="list">
                                <div class="list-item">1. Completa datos del cliente y del asunto.</div>
                                <div class="list-item">2. Define honorarios, gastos, descuento y anticipo.</div>
                                <div class="list-item">3. Copia el texto o abre el canal de envio.</div>
                            </div>
                        </section>
                    </aside>
                </section>

                <div class="footer">© 2026 Tu Estudio Juridico · Cotizador interno protegido</div>
            </section>
        <?php endif; ?>
    </main>
    <?php if (!empty($is_admin)): ?>
        <script>
            (function () {
                const $ = (id) => document.getElementById(id);
                const form = $('quoteForm');
                if (!form) return;

                const fields = {
                    clientName: $('clientName'),
                    clientWhatsapp: $('clientWhatsapp'),
                    clientEmail: $('clientEmail'),
                    matter: $('matter'),
                    serviceName: $('serviceName'),
                    serviceMode: $('serviceMode'),
                    turnaround: $('turnaround'),
                    validity: $('validity'),
                    scope: $('scope'),
                    exclusions: $('exclusions'),
                    lawyerName: $('lawyerName'),
                    firmName: $('firmName'),
                    lawyerWhatsapp: $('lawyerWhatsapp'),
                    lawyerEmail: $('lawyerEmail'),
                    baseFee: $('baseFee'),
                    extraCosts: $('extraCosts'),
                    discount: $('discount'),
                    advance: $('advance'),
                    paymentTerms: $('paymentTerms'),
                    paymentLink: $('paymentLink'),
                    notes: $('notes'),
                    preview: $('quotePreview'),
                    total: $('quoteTotal'),
                    advanceView: $('quoteAdvance'),
                    balance: $('quoteBalance'),
                    copy: $('copyQuote'),
                    sendWhatsapp: $('sendWhatsapp'),
                    sendEmail: $('sendEmail')
                };

                const currency = new Intl.NumberFormat('es-CL', {
                    style: 'currency',
                    currency: 'CLP',
                    maximumFractionDigits: 0
                });

                function toNumber(value) {
                    const n = Number(value || 0);
                    return Number.isFinite(n) && n > 0 ? n : 0;
                }

                function formatMoney(value) {
                    return currency.format(Math.max(0, Math.round(value || 0)));
                }

                function cleanPhone(value) {
                    const digits = String(value || '').replace(/\D+/g, '');
                    if (digits.length === 9) return '56' + digits;
                    if (digits.length === 11 && digits.startsWith('56')) return digits;
                    return digits;
                }

                function buildPreview() {
                    const baseFee = toNumber(fields.baseFee.value);
                    const extraCosts = toNumber(fields.extraCosts.value);
                    const discount = toNumber(fields.discount.value);
                    const advance = toNumber(fields.advance.value);
                    const total = Math.max(0, baseFee + extraCosts - discount);
                    const safeAdvance = Math.min(total, advance);
                    const balance = Math.max(0, total - safeAdvance);

                    fields.total.textContent = formatMoney(total);
                    fields.advanceView.textContent = formatMoney(safeAdvance);
                    fields.balance.textContent = formatMoney(balance);

                    const lines = [
                        'COTIZACION LEGAL',
                        '',
                        'Cliente: ' + (fields.clientName.value.trim() || 'Por definir'),
                        'Materia: ' + (fields.matter.value.trim() || 'Por definir'),
                        'Servicio: ' + (fields.serviceName.value.trim() || 'Por definir'),
                        'Modalidad: ' + (fields.serviceMode.value.trim() || 'Por definir'),
                        'Plazo estimado: ' + (fields.turnaround.value.trim() || 'Por definir'),
                        'Vigencia: ' + (fields.validity.value.trim() || 'Por definir'),
                        '',
                        'ALCANCE',
                        fields.scope.value.trim() || 'Por definir.',
                        '',
                        'NO INCLUYE',
                        fields.exclusions.value.trim() || 'No especificado.',
                        '',
                        'DETALLE ECONOMICO',
                        '- Honorarios base: ' + formatMoney(baseFee),
                        '- Gastos operativos: ' + formatMoney(extraCosts),
                        '- Descuento: ' + formatMoney(discount),
                        '- Total cotizado: ' + formatMoney(total),
                        '- Anticipo: ' + formatMoney(safeAdvance),
                        '- Saldo: ' + formatMoney(balance),
                        '- Forma de pago: ' + (fields.paymentTerms.value.trim() || 'Por definir'),
                    ];

                    if (fields.paymentLink.value.trim()) {
                        lines.push('- Link de pago: ' + fields.paymentLink.value.trim());
                    }

                    lines.push(
                        '',
                        'NOTAS',
                        fields.notes.value.trim() || 'Sin observaciones adicionales.',
                        '',
                        'CONTACTO',
                        (fields.lawyerName.value.trim() || 'Abogado/a por definir') + (fields.firmName.value.trim() ? ' | ' + fields.firmName.value.trim() : ''),
                        'WhatsApp: ' + (fields.lawyerWhatsapp.value.trim() || 'Por definir'),
                        'Email: ' + (fields.lawyerEmail.value.trim() || 'Por definir')
                    );

                    fields.preview.value = lines.join('\n');
                    return {
                        text: fields.preview.value,
                        total: total,
                        advance: safeAdvance,
                        balance: balance
                    };
                }

                function copyText() {
                    const payload = buildPreview().text;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(payload).then(function () {
                            fields.copy.textContent = 'Copiado';
                            setTimeout(function () { fields.copy.textContent = 'Copiar texto'; }, 1200);
                        }).catch(function () {
                            fields.preview.select();
                            document.execCommand('copy');
                        });
                        return;
                    }
                    fields.preview.select();
                    document.execCommand('copy');
                }

                function sendWhatsapp() {
                    const payload = buildPreview().text;
                    const phone = cleanPhone(fields.clientWhatsapp.value);
                    if (!phone) {
                        alert('Ingresa un WhatsApp del cliente para abrir el envio.');
                        return;
                    }
                    window.open('https://wa.me/' + encodeURIComponent(phone) + '?text=' + encodeURIComponent(payload), '_blank', 'noopener');
                }

                function sendEmail() {
                    const payload = buildPreview().text;
                    const email = fields.clientEmail.value.trim();
                    if (!email) {
                        alert('Ingresa un email del cliente para abrir el borrador.');
                        return;
                    }
                    const subject = 'Cotizacion legal - ' + (fields.serviceName.value.trim() || fields.matter.value.trim() || 'Tu Estudio Juridico');
                    window.location.href = 'mailto:' + encodeURIComponent(email) + '?subject=' + encodeURIComponent(subject) + '&body=' + encodeURIComponent(payload);
                }

                form.addEventListener('input', buildPreview);
                fields.copy.addEventListener('click', copyText);
                fields.sendWhatsapp.addEventListener('click', sendWhatsapp);
                fields.sendEmail.addEventListener('click', sendEmail);

                buildPreview();
            })();
        </script>
    <?php endif; ?>
</body>
</html>
