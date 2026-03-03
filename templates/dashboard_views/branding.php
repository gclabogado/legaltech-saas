<?php
    $brandingEnabled = !empty($branding_settings['enabled']);
    $brandingFieldsFilled = 0;
    foreach (['brand_name', 'legal_name', 'rut', 'phone', 'email', 'address', 'legal_notice'] as $brandingFieldKey) {
        if (trim((string)($branding_settings[$brandingFieldKey] ?? '')) !== '') $brandingFieldsFilled++;
    }
?>
<section id="view-services" class="grid" style="margin-top: 16px;">
    <div class="crm-breadcrumb">Negocio<span class="sep">/</span>Marca</div>

    <div class="dash-card branding-hero">
        <div class="branding-hero-copy">
            <div class="home-kicker">Brand settings</div>
            <h2 class="home-title">La firma comercial que ve tu cliente en cada propuesta</h2>
            <p class="home-subtitle">
                Define una identidad consistente para WhatsApp, email, cotizaciones y PDF.
                <?= $brandingEnabled ? ' La firma automática está activa.' : ' La firma automática está desactivada por ahora.' ?>
            </p>
        </div>
        <div class="branding-hero-actions">
            <a class="btn btn-ghost" href="/dashboard/cotizaciones">Ver bandeja</a>
        </div>
    </div>

    <div class="branding-metrics-grid">
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Estado</span>
            <strong><?= $brandingEnabled ? 'Activa' : 'Inactiva' ?></strong>
            <small>Controla si la firma se inserta automáticamente.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Campos completados</span>
            <strong><?= $brandingFieldsFilled ?>/7</strong>
            <small>Mientras más completa, más sólida se percibe la propuesta.</small>
        </article>
    </div>

    <div class="quote-layout branding-layout">
        <div class="quote-column quote-pane" id="servicesBrandingPane">
            <div class="dash-card branding-form-shell">
                <div class="quote-section-head">
                    <div>
                        <h2 class="section-title">Configurar firma comercial</h2>
                        <p class="muted">Estos datos aparecen en mensajes, cotizaciones y PDF.</p>
                    </div>
                </div>
                <form action="/dashboard/cotizaciones/marca" method="POST" class="quote-form-grid" id="quoteBrandingForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <label class="quote-label quote-checkbox">
                        <span>Estado</span>
                        <span class="quote-checkbox-row">
                            <input type="checkbox" name="quote_branding_enabled" id="quote_branding_enabled" <?= $brandingEnabled ? 'checked' : '' ?>>
                            Activar firma automática para mis cotizaciones
                        </span>
                    </label>
                    <div class="quote-fields-2">
                        <label class="quote-label">
                            Marca
                            <input class="input" type="text" name="quote_brand_name" id="quote_brand_name" value="<?= htmlspecialchars((string)($branding_settings['brand_name'] ?? '')) ?>" placeholder="FLOCID">
                        </label>
                        <label class="quote-label">
                            Razón social
                            <input class="input" type="text" name="quote_brand_legal_name" id="quote_brand_legal_name" value="<?= htmlspecialchars((string)($branding_settings['legal_name'] ?? '')) ?>" placeholder="Defensa y Asesoría Jurídica SpA">
                        </label>
                    </div>
                    <div class="quote-fields-2">
                        <label class="quote-label">
                            RUT
                            <input class="input" type="text" name="quote_brand_rut" id="quote_brand_rut" value="<?= htmlspecialchars((string)($branding_settings['rut'] ?? '')) ?>" placeholder="78.312.211-1">
                        </label>
                        <label class="quote-label">
                            Teléfono
                            <input class="input" type="text" name="quote_brand_phone" id="quote_brand_phone" value="<?= htmlspecialchars((string)($branding_settings['phone'] ?? '')) ?>" placeholder="+56 9 ...">
                        </label>
                    </div>
                    <div class="quote-fields-2">
                        <label class="quote-label">
                            Email de contacto
                            <input class="input" type="email" name="quote_brand_email" id="quote_brand_email" value="<?= htmlspecialchars((string)($branding_settings['email'] ?? '')) ?>" placeholder="notificaciones@...">
                        </label>
                        <label class="quote-label">
                            Dirección real
                            <input class="input" type="text" name="quote_brand_address" id="quote_brand_address" value="<?= htmlspecialchars((string)($branding_settings['address'] ?? '')) ?>" placeholder="Ingresa dirección real">
                        </label>
                    </div>
                    <label class="quote-label">
                        Aviso legal
                        <textarea class="input quote-textarea-sm" name="quote_brand_legal_notice" id="quote_brand_legal_notice" placeholder="Aviso legal o pie corporativo"><?= htmlspecialchars((string)($branding_settings['legal_notice'] ?? '')) ?></textarea>
                    </label>
                    <div class="dash-actions-3">
                        <button class="btn btn-primary" type="submit">Guardar firma</button>
                    </div>
                </form>
            </div>

            <div class="dash-card branding-preview-shell">
                <div class="quote-section-head">
                    <div>
                        <h2 class="section-title">Vista previa de firma</h2>
                        <p class="muted">Así se verá al final de tus cotizaciones y mensajes.</p>
                    </div>
                </div>
                <div class="quote-brand-block branding-preview-card">
                    <strong><?= htmlspecialchars((string)(($branding_settings['brand_name'] ?? '') !== '' ? $branding_settings['brand_name'] : 'Firma del cotizador')) ?></strong>
                    <?php if (!empty($branding_settings['legal_name'])): ?><div><?= htmlspecialchars((string)$branding_settings['legal_name']) ?></div><?php endif; ?>
                    <?php if (!empty($branding_settings['rut'])): ?><div>RUT <?= htmlspecialchars((string)$branding_settings['rut']) ?></div><?php endif; ?>
                    <div style="margin-top:8px;">Contacto</div>
                    <?php if (!empty($branding_settings['phone'])): ?><div>📞 <?= htmlspecialchars((string)$branding_settings['phone']) ?></div><?php endif; ?>
                    <?php if (!empty($branding_settings['email'])): ?><div>📧 <?= htmlspecialchars((string)$branding_settings['email']) ?></div><?php endif; ?>
                    <?php if (!empty($branding_settings['address'])): ?><div><?= htmlspecialchars((string)$branding_settings['address']) ?></div><?php else: ?><div class="muted">Falta completar la dirección real.</div><?php endif; ?>
                    <?php if (!empty($branding_settings['legal_notice'])): ?><div style="margin-top:8px;"><?= htmlspecialchars((string)$branding_settings['legal_notice']) ?></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
