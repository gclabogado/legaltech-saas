<?php
    $builderServiceCount = count((array)($servicios ?? []));
    $builderClientCount = count((array)($clientes_para_cotizar ?? []));
?>
<section id="view-services" class="grid" style="margin-top: 16px;">
    <div class="crm-breadcrumb">Negocio<span class="sep">/</span>Cotizador</div>

    <div class="dash-card builder-hero">
        <div class="builder-hero-copy">
            <div class="home-kicker">Quote composer</div>
            <h2 class="home-title">Arma la propuesta, revisa el cierre y envíala sin salir del flujo</h2>
            <p class="home-subtitle">
                Usa <?= $builderServiceCount ?> servicio<?= $builderServiceCount === 1 ? '' : 's' ?> del catálogo,
                <?= $builderClientCount ?> lead<?= $builderClientCount === 1 ? '' : 's' ?> disponibles para cotizar
                y un preview listo para WhatsApp, email o PDF.
            </p>
        </div>
        <div class="builder-hero-actions">
            <a class="btn btn-ghost" href="/dashboard/catalogo">Ver catálogo</a>
        </div>
    </div>

    <div class="builder-summary-grid">
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Servicios listos</span>
            <strong><?= $builderServiceCount ?></strong>
            <small>Base rápida para cotizar sin partir desde cero.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Leads para cotizar</span>
            <strong><?= $builderClientCount ?></strong>
            <small>Clientes ya disponibles para completar en un paso.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Flujo</span>
            <strong>3 pasos</strong>
            <small>Cliente, alcance y cierre económico.</small>
        </article>
    </div>

    <div class="quote-layout builder-layout">
        <div class="quote-column quote-pane" id="servicesQuoteBuilderPane">
            <div class="dash-card builder-form-shell">
                <div class="quote-section-head">
                    <div>
                        <h2 class="section-title">Generar cotización</h2>
                        <p class="muted">Completa solo lo necesario en cada pantalla. El preview se actualiza mientras avanzas.</p>
                    </div>
                </div>
                <form id="quoteBuilderForm" action="/dashboard/cotizaciones/guardar" method="POST" class="quote-form-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="hidden" name="quote_id" id="quote_id" value="">
                    <input type="checkbox" id="quote_branding_enabled"<?= !empty($branding_settings['enabled']) ? ' checked' : '' ?> hidden>
                    <input type="hidden" id="quote_brand_name" value="<?= htmlspecialchars((string)($branding_settings['brand_name'] ?? '')) ?>">
                    <input type="hidden" id="quote_brand_legal_name" value="<?= htmlspecialchars((string)($branding_settings['legal_name'] ?? '')) ?>">
                    <input type="hidden" id="quote_brand_rut" value="<?= htmlspecialchars((string)($branding_settings['rut'] ?? '')) ?>">
                    <input type="hidden" id="quote_brand_phone" value="<?= htmlspecialchars((string)($branding_settings['phone'] ?? '')) ?>">
                    <input type="hidden" id="quote_brand_email" value="<?= htmlspecialchars((string)($branding_settings['email'] ?? '')) ?>">
                    <input type="hidden" id="quote_brand_address" value="<?= htmlspecialchars((string)($branding_settings['address'] ?? '')) ?>">
                    <input type="hidden" id="quote_brand_legal_notice" value="<?= htmlspecialchars((string)($branding_settings['legal_notice'] ?? '')) ?>">

                    <div class="quote-stepper builder-stepper" id="quoteStepper">
                        <div class="quote-stepper-item is-active" data-step-indicator="1">
                            <span>1</span>
                            <small>Cliente</small>
                        </div>
                        <div class="quote-stepper-item" data-step-indicator="2">
                            <span>2</span>
                            <small>Alcance</small>
                        </div>
                        <div class="quote-stepper-item" data-step-indicator="3">
                            <span>3</span>
                            <small>Cierre</small>
                        </div>
                    </div>

                    <section class="quote-step is-active builder-step-card" data-step="1">
                        <div class="quote-step-head builder-step-head">
                            <div>
                                <div class="builder-step-kicker">Paso 1</div>
                                <h3>Cliente y servicio base</h3>
                            </div>
                            <p class="muted">Elige un lead existente o crea una cotización personalizada desde cero.</p>
                        </div>

                        <div class="builder-callout-grid">
                            <div class="builder-callout">
                                <strong>Atajo</strong>
                                <span>Selecciona un lead para completar nombre, WhatsApp, email y materia automáticamente.</span>
                            </div>
                            <div class="builder-callout">
                                <strong>Catálogo</strong>
                                <span>Selecciona un servicio para precargar detalle, plazo, honorarios y gastos.</span>
                            </div>
                        </div>

                        <div class="quote-fields-2">
                            <label class="quote-label">
                                Cliente del CRM
                                <select class="input" name="cliente_id" id="quote_cliente_id">
                                    <option value="">Selecciona un lead existente</option>
                                    <?php foreach (($clientes_para_cotizar ?? []) as $cliDash): ?>
                                        <option
                                            value="<?= (int)($cliDash['id'] ?? 0) ?>"
                                            data-name="<?= htmlspecialchars((string)($cliDash['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-whatsapp="<?= htmlspecialchars((string)($cliDash['whatsapp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars((string)($cliDash['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-matter="<?= htmlspecialchars((string)($cliDash['especialidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string)($cliDash['nombre'] ?? 'Cliente')) ?> · <?= htmlspecialchars((string)($cliDash['especialidad'] ?? 'Sin materia')) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="quote-label">
                                Servicio
                                <select class="input" name="service_id" id="quote_service_id">
                                    <option value="">Cotización personalizada</option>
                                    <?php foreach (($servicios ?? []) as $srv): ?>
                                        <option
                                            value="<?= (int)($srv['id'] ?? 0) ?>"
                                            data-name="<?= htmlspecialchars((string)($srv['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-matter="<?= htmlspecialchars((string)($srv['materia'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-detail="<?= htmlspecialchars((string)($srv['detalle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-term="<?= htmlspecialchars((string)($srv['plazo_estimado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-price="<?= htmlspecialchars((string)($srv['precio_base'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>"
                                            data-costs="<?= htmlspecialchars((string)($srv['gastos_base'] ?? '0'), ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string)($srv['nombre'] ?? 'Servicio')) ?> · <?= formatClpAmount($srv['precio_base'] ?? 0) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>
                        <div class="quote-fields-3">
                            <label class="quote-label">
                                Nombre cliente
                                <input class="input" type="text" name="client_name" id="quote_client_name" placeholder="Nombre y apellido" required>
                            </label>
                            <label class="quote-label">
                                WhatsApp cliente
                                <input class="input" type="text" name="client_whatsapp" id="quote_client_whatsapp" placeholder="912345678">
                            </label>
                            <label class="quote-label">
                                Email cliente
                                <input class="input" type="email" name="client_email" id="quote_client_email" placeholder="cliente@correo.cl">
                            </label>
                        </div>

                        <div class="quote-fields-2">
                            <label class="quote-label">
                                Asunto / servicio cotizado
                                <input class="input" type="text" name="asunto" id="quote_asunto" placeholder="Ej: Defensa en audiencia de control" required>
                            </label>
                            <label class="quote-label">
                                Materia
                                <select class="input" name="materia" id="quote_materia">
                                    <option value="">Selecciona una materia</option>
                                    <?php foreach (array_keys((array)($materias_taxonomia ?? [])) as $matDash): ?>
                                        <option value="<?= htmlspecialchars((string)$matDash) ?>"><?= htmlspecialchars((string)$matDash) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        </div>

                        <div class="quote-step-actions">
                            <button class="btn btn-primary" type="button" data-step-next>Continuar con alcance</button>
                        </div>
                    </section>

                    <section class="quote-step builder-step-card" data-step="2" hidden>
                        <div class="quote-step-head builder-step-head">
                            <div>
                                <div class="builder-step-kicker">Paso 2</div>
                                <h3>Alcance y condiciones</h3>
                            </div>
                            <p class="muted">Define con claridad qué incluye la propuesta, qué no incluye y bajo qué condiciones se ofrece.</p>
                        </div>

                        <div class="builder-callout-grid single">
                            <div class="builder-callout">
                                <strong>Consejo</strong>
                                <span>Mientras más claro sea el alcance, menor fricción tendrás al cobrar, coordinar y ejecutar.</span>
                            </div>
                        </div>

                        <div class="quote-fields-2">
                            <label class="quote-label">
                                Plazo estimado
                                <input class="input" type="text" name="plazo_estimado" id="quote_plazo" placeholder="24 horas, 5 días hábiles">
                            </label>
                            <label class="quote-label">
                                Vigencia
                                <input class="input" type="text" name="vigencia" id="quote_vigencia" placeholder="7 días corridos">
                            </label>
                        </div>

                        <label class="quote-label">
                            Detalle de la propuesta
                            <textarea class="input quote-textarea" name="detalle" id="quote_detalle" placeholder="Describe alcance, entregables, reuniones, escritos o gestiones incluidas."></textarea>
                        </label>
                        <label class="quote-label">
                            No incluye
                            <textarea class="input quote-textarea quote-textarea-sm" name="no_incluye" id="quote_no_incluye" placeholder="Ej: tasas judiciales, notarías, peritajes, apelaciones, audiencias extraordinarias."></textarea>
                        </label>

                        <div class="quote-fields-2">
                            <label class="quote-label">
                                Forma de pago
                                <input class="input" type="text" name="condiciones_pago" id="quote_condiciones_pago" placeholder="Transferencia 50/50, 3 cuotas, contado">
                            </label>
                            <label class="quote-label">
                                Link de pago
                                <input class="input" type="url" name="payment_link" id="quote_payment_link" placeholder="https://...">
                            </label>
                        </div>

                        <div class="quote-fields-2">
                            <label class="quote-label">
                                Estado
                                <select class="input" name="estado" id="quote_estado">
                                    <option value="BORRADOR">Borrador</option>
                                    <option value="ENVIADA">Enviada</option>
                                    <option value="ACEPTADA">Aceptada</option>
                                    <option value="RECHAZADA">Rechazada</option>
                                    <option value="ANULADA">Anulada</option>
                                </select>
                            </label>
                            <label class="quote-label">
                                Notas internas o aclaraciones
                                <input class="input" type="text" name="notas" id="quote_notas" placeholder="Documentos requeridos, agenda, urgencia, etc.">
                            </label>
                        </div>

                        <div class="quote-step-actions">
                            <button class="btn btn-ghost" type="button" data-step-prev>Volver</button>
                            <button class="btn btn-primary" type="button" data-step-next>Continuar al cierre</button>
                        </div>
                    </section>

                    <section class="quote-step builder-step-card" data-step="3" hidden>
                        <div class="quote-step-head builder-step-head">
                            <div>
                                <div class="builder-step-kicker">Paso 3</div>
                                <h3>Montos y generación final</h3>
                            </div>
                            <p class="muted">Ajusta honorarios, anticipo y saldo. Desde aquí guardas la cotización lista para seguimiento.</p>
                        </div>

                        <div class="quote-fields-4">
                            <label class="quote-label">
                                Honorarios
                                <input class="input quote-money" type="number" min="0" step="1000" name="honorarios" id="quote_honorarios" value="0">
                            </label>
                            <label class="quote-label">
                                Gastos
                                <input class="input quote-money" type="number" min="0" step="1000" name="gastos" id="quote_gastos" value="0">
                            </label>
                            <label class="quote-label">
                                Descuento
                                <input class="input quote-money" type="number" min="0" step="1000" name="descuento" id="quote_descuento" value="0">
                            </label>
                            <label class="quote-label">
                                Anticipo
                                <input class="input quote-money" type="number" min="0" step="1000" name="anticipo" id="quote_anticipo" value="0">
                            </label>
                        </div>

                        <div class="quote-summary-grid builder-totals-grid">
                            <div class="dash-stat quote-summary-box"><strong id="quote_total_view">$0</strong>Total</div>
                            <div class="dash-stat quote-summary-box"><strong id="quote_anticipo_view">$0</strong>Anticipo</div>
                            <div class="dash-stat quote-summary-box"><strong id="quote_saldo_view">$0</strong>Saldo</div>
                        </div>

                        <div class="quote-step-actions">
                            <button class="btn btn-ghost" type="button" data-step-prev>Volver</button>
                            <button class="btn btn-ghost" type="button" onclick="dashboardResetQuoteForm()">Nueva cotización</button>
                            <button class="btn btn-primary" type="submit">Guardar cotización</button>
                        </div>
                    </section>
                </form>
            </div>
        </div>

        <aside class="quote-column builder-preview-column">
            <div class="dash-card builder-preview-shell">
                <div class="quote-section-head">
                    <div>
                        <h2 class="section-title">Preview listo para enviar</h2>
                        <p class="muted">Revísalo como si fuera el mensaje real que verá el cliente.</p>
                    </div>
                </div>
                <div class="builder-preview-stats">
                    <div class="builder-preview-stat">
                        <span>Canales</span>
                        <strong>WhatsApp / Email</strong>
                    </div>
                    <div class="builder-preview-stat">
                        <span>Salida</span>
                        <strong>Texto + PDF</strong>
                    </div>
                </div>
                <textarea id="quote_preview" class="input quote-preview builder-preview-box" readonly></textarea>
                <div class="quote-actions-grid builder-preview-actions">
                    <button class="btn btn-primary" type="button" id="quoteWhatsappBtn">Abrir WhatsApp</button>
                    <button class="btn btn-ghost" type="button" id="quoteEmailBtn">Abrir email</button>
                    <button class="btn btn-ghost" type="button" id="quotePreviewCopyBtn">Copiar texto</button>
                </div>
            </div>
        </aside>
    </div>
</section>
