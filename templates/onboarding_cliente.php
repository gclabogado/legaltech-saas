<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Caso | Tu Estudio Juridico</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <link rel="stylesheet" href="/onboarding-cliente-page.css?v=2026022601">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<body class="theme-black">
    <?php
        $weeklyLimit = $weekly_limit ?? [];
        $weeklyBlocked = !empty($weeklyLimit['is_blocked']);
        $nextWeeklyWindow = $weeklyLimit['next_available_label'] ?? null;
        $comunasSugeridas = $comunas_sugeridas ?? [];
        $weeklyBlockedMessage = 'Por política anti-spam solo puedes publicar 1 consulta nueva cada 7 días.';
        if ($nextWeeklyWindow) {
            $weeklyBlockedMessage .= ' Próxima ventana: ' . $nextWeeklyWindow . ' (hora servidor).';
        }
    ?>
    <nav class="nav">
        <div class="nav-inner">
            <a class="brand" href="/explorar">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/explorar">Explorar</a>
                <a href="/panel">Mi panel</a>
                <a class="active" href="/completar-datos?modo=cliente">Mi caso</a>
            </div>
            <div class="nav-actions">
                <span class="badge">Panel Cliente</span>
                <a class="btn btn-ghost" href="/logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <section class="hero">
            <h1>Hola, <?= htmlspecialchars(explode(' ', $user['nombre'])[0]) ?>.</h1>
            <p>Pide 1 abogado con un flujo de 3 pasos: categoría, lugar y problema.</p>
            <p class="hint">Tu nombre no aparece en la galeria publica. Solo compartimos tu contacto cuando un abogado se interesa en tu caso.</p>
            <div class="cta-directed">
                <div>
                    <strong>CTA: solicita 1 abogado en menos de 2 minutos</strong>
                    <div class="hint">Número de WhatsApp obligatorio para contacto.</div>
                </div>
                <a id="heroStartCta" class="btn btn-primary" href="#prospectoForm">Comenzar ahora</a>
            </div>
            <div class="card" style="padding: 12px !important;">
                <strong style="font-size: 12px;">Disclaimer</strong>
                <div class="hint" style="margin-top: 6px;">Solo puedes ingresar 1 consulta nueva cada 7 días para ser contactado como cliente. Se requiere WhatsApp válido para activar contacto.</div>
                <?php if ($weeklyBlocked): ?>
                    <div class="alert alert-error" style="margin-top: 10px;"><?= htmlspecialchars($weeklyBlockedMessage) ?></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($lawyer_verification_enabled)): ?>
                <div class="card" style="padding: 12px !important;">
                    <strong style="font-size: 12px;">¿Eres abogado/a?</strong>
                    <div class="hint" style="margin-top: 6px;">Usa el acceso profesional para solicitar habilitación y validación. Este flujo está optimizado para clientes.</div>
                    <a class="btn btn-ghost" style="margin-top:10px;" href="/acceso-profesional">Ir a acceso profesional</a>
                </div>
            <?php endif; ?>
        </section>

        <?php if (!empty($user['descripcion_caso'])): ?>
            <div class="grid">
                <div class="card">
                    <div class="split" style="align-items: center;">
                        <div>
                            <span class="pill">Caso publicado</span>
                            <h2><?= htmlspecialchars($user['especialidad']) ?></h2>
                            <p class="muted">"<?= htmlspecialchars($user['descripcion_caso']) ?>"</p>
                        </div>
                        <div class="stack">
                            <a class="cta cta-ghost" href="/bajar-caso" onclick="return confirm('¿Seguro? Tu caso dejará de ser visible para abogados.')">Cerrar publicación</a>
                            <button class="cta cta-primary" onclick="document.getElementById('form-section').classList.toggle('hidden')">Editar caso</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <h2>Abogados interesados (<?= count($interesados ?? []) ?>)</h2>
                    <?php if(empty($interesados)): ?>
                        <div class="case-card">
                            <p class="muted">Estamos difundiendo tu caso. Recibirás contactos aquí mismo.</p>
                        </div>
                    <?php else: ?>
                        <div class="list">
                            <?php foreach($interesados as $abg): ?>
                                <div class="case-card">
                                    <div class="split" style="align-items: center;">
                                        <div>
                                            <strong><?= htmlspecialchars($abg['nombre']) ?></strong>
                                            <div class="muted" style="font-size: 12px;"><?= htmlspecialchars($abg['especialidad']) ?></div>
                                            <a href="/<?= $abg['slug'] ?>" class="hint">Ver perfil público</a>
                                        </div>
                                        <a class="cta cta-primary" href="https://wa.me/56<?= $abg['whatsapp'] ?>">WhatsApp</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <section id="form-section" class="<?= (!empty($user['descripcion_caso']) && empty($error) && empty($mensaje)) ? 'hidden' : '' ?>" style="margin-top: 22px;">
            <div class="card">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error" style="margin-bottom: 10px;"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if (!empty($mensaje)): ?>
                    <div class="alert alert-success" style="margin-bottom: 10px;"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                <h2><?= !empty($user['descripcion_caso']) ? 'Actualizar caso' : 'Publicar mi caso' ?></h2>
                <p class="hint">Objetivo UX 2026: reducir dudas y llevarte directo al contacto correcto.</p>

                <form id="prospectoForm" action="/guardar-cliente" method="POST" class="grid" style="margin-top: 14px;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;opacity:0;height:0;width:0;pointer-events:none;">
                    <div class="progress-wrap">
                        <div class="hint" id="stepLabel">Paso 1 de 5 · Materia legal</div>
                        <div class="progress-track"><span id="progressBar"></span></div>
                        <div class="hint" id="stepHint">Responde tocando opciones. Al final te pediremos tu WhatsApp para confirmar.</div>
                    </div>

                    <div class="form-step" data-step="1">
                        <label class="hint">¿Qué necesitas resolver?</label>
                        <div id="specialtyOptions" class="split" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));">
                            <?php foreach (['Familia','Laboral','Civil','Penal','Inmobiliario','Deudores'] as $area): ?>
                                <button type="button" class="btn btn-ghost option-btn" data-target="especialidadInput" data-value="<?= htmlspecialchars($area) ?>"><?= htmlspecialchars($area) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <select id="especialidadInput" name="especialidad" required style="position:absolute;left:-9999px;opacity:0;pointer-events:none;">
                            <option value="">Selecciona materia...</option>
                            <option value="Familia" <?= ($user['especialidad'] == 'Familia') ? 'selected' : '' ?>>Familia</option>
                            <option value="Laboral" <?= ($user['especialidad'] == 'Laboral') ? 'selected' : '' ?>>Laboral</option>
                            <option value="Civil" <?= ($user['especialidad'] == 'Civil') ? 'selected' : '' ?>>Civil</option>
                            <option value="Penal" <?= ($user['especialidad'] == 'Penal') ? 'selected' : '' ?>>Penal</option>
                            <option value="Inmobiliario" <?= ($user['especialidad'] == 'Inmobiliario') ? 'selected' : '' ?>>Inmobiliario</option>
                            <option value="Deudores" <?= ($user['especialidad'] == 'Deudores') ? 'selected' : '' ?>>Deudores</option>
                        </select>
                        <p class="hint">Selecciona una opción para continuar.</p>
                    </div>

                    <div class="form-step hidden" data-step="2">
                        <label class="hint">¿Dónde ocurre el caso?</label>
                        <div class="split" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));">
                            <button type="button" class="btn btn-ghost option-btn" data-target="locationModeInput" data-value="comuna">En una comuna específica</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="locationModeInput" data-value="remoto">Puede ser remoto / online</button>
                            <button type="button" class="btn btn-ghost" id="useGeoWizardBtn">Usar mi ubicación</button>
                        </div>
                        <input type="hidden" id="locationModeInput" value="comuna">
                        <input type="text" id="ciudadInput" name="ciudad" list="comunas-cl" required minlength="2" maxlength="80" placeholder="Ej: Santiago Centro" value="<?= htmlspecialchars($user['ciudad'] ?? '') ?>" class="input">
                        <p class="hint">Escribe comuna o ciudad donde ocurre el problema.</p>
                    </div>

                    <div class="form-step hidden" data-step="3">
                        <label class="hint">¿Qué describe mejor tu situación?</label>
                        <div class="split" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
                            <button type="button" class="btn btn-ghost option-btn" data-target="issueTypeInput" data-value="Me demandaron">Me demandaron</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="issueTypeInput" data-value="Me despidieron">Me despidieron</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="issueTypeInput" data-value="Necesito iniciar una demanda">Necesito iniciar demanda</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="issueTypeInput" data-value="Problema de arriendo/propiedad">Arriendo / propiedad</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="issueTypeInput" data-value="Cobranza/deudas">Cobranza / deudas</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="issueTypeInput" data-value="Otro">Otro</button>
                        </div>
                        <input type="hidden" id="issueTypeInput" value="">
                        <label class="hint">Urgencia</label>
                        <div class="split" style="grid-template-columns:repeat(3,minmax(0,1fr));">
                            <button type="button" class="btn btn-ghost option-btn" data-target="urgencyInput" data-value="Hoy">Hoy</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="urgencyInput" data-value="Esta semana">Esta semana</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="urgencyInput" data-value="Estoy evaluando">Estoy evaluando</button>
                        </div>
                        <input type="hidden" id="urgencyInput" value="">
                        <label class="hint">Qué buscas</label>
                        <div class="split" style="grid-template-columns:repeat(3,minmax(0,1fr));">
                            <button type="button" class="btn btn-ghost option-btn" data-target="goalInput" data-value="Orientación">Orientación</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="goalInput" data-value="Cotización">Cotización</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="goalInput" data-value="Representación">Representación</button>
                        </div>
                        <input type="hidden" id="goalInput" value="">
                        <label class="hint">Cuéntanos en breve (mínimo 20 caracteres)</label>
                        <textarea id="descripcionInput" name="descripcion" required minlength="20" placeholder="Escribe en simple qué pasó, desde cuándo y qué necesitas."></textarea>
                        <p class="hint">Con esto armamos el resumen del caso para abogados.</p>
                        <input type="hidden" id="descripcionComposedInput" value="">
                    </div>

                    <div class="form-step hidden" data-step="4">
                        <label class="hint">Identificación para validar el caso</label>
                        <div class="split" style="grid-template-columns:repeat(2,minmax(0,1fr));">
                            <button type="button" class="btn btn-ghost option-btn" data-target="identityModeInput" data-value="chileno">Tengo RUT chileno</button>
                            <button type="button" class="btn btn-ghost option-btn" data-target="identityModeInput" data-value="extranjero">Soy extranjero</button>
                        </div>
                        <input type="hidden" id="identityModeInput" value="<?= !empty($user['es_extranjero']) ? 'extranjero' : 'chileno' ?>">
                        <label class="consent" style="display:none;">
                            <input id="extranjeroInput" type="checkbox" name="es_extranjero" value="1" <?= !empty($user['es_extranjero']) ? 'checked' : '' ?>>
                            <span class="hint">Soy extranjero (sin RUT chileno)</span>
                        </label>
                        <label class="hint">RUT chileno (si aplica)</label>
                        <input type="text" id="rutInput" name="rut_cliente" placeholder="Ej: 12.345.678-5" value="<?= htmlspecialchars($user['rut_cliente'] ?? '') ?>" class="input" <?= !empty($user['es_extranjero']) ? 'disabled' : 'required' ?>>
                        <div class="split" style="grid-template-columns: 1fr 140px 150px;">
                            <div>
                                <label class="hint">Calle (opcional)</label>
                                <input type="text" id="direccionCalleInput" name="direccion_calle" maxlength="120" placeholder="Ej: Av. Apoquindo" value="<?= htmlspecialchars($user['direccion_calle'] ?? '') ?>" class="input">
                            </div>
                            <div>
                                <label class="hint">Número</label>
                                <input type="text" id="direccionNumeroInput" name="direccion_numero" maxlength="20" placeholder="1234" value="<?= htmlspecialchars($user['direccion_numero'] ?? '') ?>" class="input">
                            </div>
                            <div>
                                <label class="hint">Código postal</label>
                                <input type="text" id="codigoPostalInput" name="codigo_postal" maxlength="12" placeholder="7500000" value="<?= htmlspecialchars($user['codigo_postal'] ?? '') ?>" class="input">
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-ghost" id="postalLookupBtn">Buscar código postal</button>
                            <span class="hint">Servicio beta, puede tener intermitencia.</span>
                        </div>
                    </div>

                    <div class="form-step hidden" data-step="5">
                        <label class="hint">Último paso: WhatsApp obligatorio</label>
                        <div class="split" style="grid-template-columns: 110px 1fr;">
                            <input class="input" value="+56" readonly>
                            <input type="tel" id="whatsappInput" name="whatsapp" value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>" required pattern="9[0-9]{8}" maxlength="9" placeholder="912345678" class="input">
                        </div>
                        <p class="hint">Usaremos este número solo para ponerte en contacto con abogados sobre este caso.</p>
                        <label class="consent">
                            <input id="consentInput" type="checkbox" required>
                            <span class="hint">Acepto que abogados me contacten por WhatsApp para este caso.</span>
                        </label>
                        <div class="card" style="box-shadow:none; border-style:dashed;">
                            <div class="hint" id="wizardSummaryPreview">Tu resumen aparecerá aquí antes de confirmar.</div>
                        </div>
                    </div>

                    <div class="step-actions">
                        <button type="button" id="btnBack" class="cta cta-ghost hidden" onclick="prevStep()" <?= $weeklyBlocked ? 'disabled' : '' ?>>Volver</button>
                        <button type="button" id="btnNext" class="cta cta-primary span-2" onclick="nextStep()" <?= $weeklyBlocked ? 'disabled' : '' ?>>Guardar y continuar</button>
                        <button type="button" id="btnConfirm" class="cta cta-primary span-2 hidden" onclick="openConfirmation()" <?= $weeklyBlocked ? 'disabled' : '' ?>>
                            <?= !empty($user['descripcion_caso']) ? 'Revisar y actualizar ahora' : 'Revisar y publicar ahora' ?>
                        </button>
                    </div>
                </form>
                <datalist id="comunas-cl">
                    <?php foreach ($comunasSugeridas as $comuna): ?>
                        <option value="<?= htmlspecialchars($comuna) ?>"></option>
                    <?php endforeach; ?>
                </datalist>
            </div>
        </section>
    </main>

    <div class="footer-nav">
        <a href="/panel" class="active">Mi panel</a>
        <a href="/explorar">Explorar</a>
    </div>

    <div id="confirmModal" class="modal">
        <div class="modal-card">
            <h3>Confirmar publicación</h3>
            <p class="muted">Revisa tu información antes de publicar el caso.</p>
                <div class="summary-block">
                    <div class="summary-item">
                        <span class="hint">Categoria</span>
                        <strong id="displaySpecialty"></strong>
                    </div>
                    <div class="summary-item">
                        <span class="hint">Lugar</span>
                        <strong id="displayCity"></strong>
                    </div>
                    <div class="summary-item">
                        <span class="hint">Identificación</span>
                        <strong id="displayIdentity"></strong>
                    </div>
                    <div class="summary-item">
                        <span class="hint">Descripción</span>
                        <strong id="displayDescription"></strong>
                </div>
                <div class="summary-item">
                    <span class="hint">WhatsApp</span>
                    <strong id="displayPhone"></strong>
                </div>
            </div>
            <div class="modal-actions">
                <button onclick="submitForm()" class="cta cta-primary">Confirmar y publicar</button>
                <button onclick="closeConfirmation()" class="cta cta-ghost">Editar datos</button>
            </div>
            <p class="hint" style="margin-top: 10px;">Disclaimer: máximo 1 consulta nueva cada 7 días.</p>
        </div>
    </div>

    <script id="onboardingClienteConfig" type="application/json"><?= json_encode([
        'weeklyBlocked' => (bool)$weeklyBlocked,
        'weeklyBlockedMessage' => (string)$weeklyBlockedMessage,
        'hasExistingCase' => !empty($user['descripcion_caso']),
        'errorMessage' => !empty($error) ? (string)$error : null
    ], JSON_UNESCAPED_UNICODE) ?: '{}' ?></script>
    <script src="/onboarding-cliente-page.js?v=2026022601"></script>
</body>
</html>
