<?php
    $quoteCounts = [
        'BORRADOR' => 0,
        'ENVIADA' => 0,
        'ACEPTADA' => 0,
        'RECHAZADA' => 0,
        'ANULADA' => 0,
    ];
    $quoteBuckets = $quoteCounts;
    foreach ($quoteBuckets as $quoteBucketKey => $_tmpQuoteCount) {
        $quoteBuckets[$quoteBucketKey] = [];
    }

    $quoteDraftTotal = 0.0;
    $quoteAcceptedTotal = 0.0;
    $quoteFollowupCount = 0;
    $quoteCollectionsPendingTotal = 0.0;
    $quoteRetainerPendingTotal = 0.0;
    $quoteReminderDueCount = 0;

    foreach (($cotizaciones ?? []) as $quoteItem) {
        $quoteState = strtoupper(trim((string)($quoteItem['estado'] ?? 'BORRADOR')));
        if (!isset($quoteCounts[$quoteState])) {
            $quoteState = 'BORRADOR';
        }
        $quoteCounts[$quoteState]++;
        $quoteBuckets[$quoteState][] = $quoteItem;
        $quoteTotal = (float)($quoteItem['total'] ?? 0);
        if ($quoteState === 'BORRADOR') {
            $quoteDraftTotal += $quoteTotal;
        } elseif ($quoteState === 'ENVIADA') {
            $quoteFollowupCount++;
        } elseif ($quoteState === 'ACEPTADA') {
            $quoteAcceptedTotal += $quoteTotal;
            $quoteCollectionsPendingTotal += (float)($quoteItem['por_cobrar_monto'] ?? 0);
            if (strtoupper((string)($quoteItem['cobro_estado_resuelto'] ?? 'PENDIENTE')) === 'PENDIENTE') {
                $quoteRetainerPendingTotal += (float)($quoteItem['anticipo'] ?? 0);
            }
        }
        if (!empty($quoteItem['collection_reminder_ready'])) {
            $quoteReminderDueCount++;
        }
    }

    $quoteTotalCount = count((array)($cotizaciones ?? []));
    $quoteFmtMoney = static fn($value) => formatClpAmount($value ?? 0);
    $quoteBucketTitles = [
        'BORRADOR' => 'Borradores',
        'ENVIADA' => 'Enviadas',
        'ACEPTADA' => 'Aceptadas',
        'RECHAZADA' => 'Rechazadas',
        'ANULADA' => 'Anuladas',
    ];
    $quoteBucketDescriptions = [
        'BORRADOR' => 'Propuestas en preparacion o listas para afinar.',
        'ENVIADA' => 'Cotizaciones despachadas, pendientes de seguimiento o decision.',
        'ACEPTADA' => 'Negocio cerrado o listo para convertir en trabajo.',
        'RECHAZADA' => 'Propuestas que el cliente no acepto por ahora.',
        'ANULADA' => 'Cotizaciones dejadas sin efecto o descartadas.',
    ];
    $quoteSubscription = (array)($subscription_state ?? []);
    $quotePlanKey = (string)($quoteSubscription['plan_key'] ?? 'free');
    $quoteUpgradeHref = (string)($quoteSubscription['cta_href'] ?? 'mailto:contacto@example.com');
    $quoteUpgradeLabel = (string)($quoteSubscription['cta_label'] ?? 'Pasar a PRO');
    $quoteNudgeTitle = 'Vende mejor desde la bandeja';
    $quoteNudgeBody = 'Activa una capa PRO visible: seguimiento, branding y cierre comercial más consistente.';
    if (in_array($quotePlanKey, ['pro', 'pro_founder'], true)) {
        $quoteUpgradeHref = 'mailto:' . rawurlencode((string)($quoteSubscription['contact_email'] ?? 'contacto@example.com')) . '?subject=' . rawurlencode('Quiero escalar a Tu Estudio Juridico Team');
        $quoteUpgradeLabel = 'Escalar a Team';
        $quoteNudgeTitle = 'Comparte cotizaciones con tu estudio';
        $quoteNudgeBody = 'Con Team puedes operar cotizaciones, pipeline y seguimiento sobre un workspace compartido.';
    }
?>
<section id="view-services" class="grid" style="margin-top: 16px;">
    <div class="dash-card quotes-hero">
        <div class="quotes-hero-copy">
            <h2 class="home-title">Cotizaciones</h2>
            <p class="home-subtitle">
                <?= $quoteTotalCount ?> cotización<?= $quoteTotalCount === 1 ? '' : 'es' ?> guardada<?= $quoteTotalCount === 1 ? '' : 's' ?>,
                <?= (int)$quoteCounts['BORRADOR'] ?> borrador<?= (int)$quoteCounts['BORRADOR'] === 1 ? '' : 'es' ?>,
                <?= (int)$quoteCounts['ENVIADA'] ?> enviada<?= (int)$quoteCounts['ENVIADA'] === 1 ? '' : 's' ?>
                y <?= (int)$quoteCounts['ACEPTADA'] ?> aceptada<?= (int)$quoteCounts['ACEPTADA'] === 1 ? '' : 's' ?>.
            </p>
        </div>
    </div>

    <div class="quotes-metrics-grid">
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Borradores activos</span>
            <strong><?= (int)$quoteCounts['BORRADOR'] ?></strong>
            <small><?= htmlspecialchars($quoteFmtMoney($quoteDraftTotal)) ?> esperando envío o edición.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Pendientes de seguimiento</span>
            <strong><?= (int)$quoteCounts['ENVIADA'] ?></strong>
            <small><?= $quoteFollowupCount ?> propuesta<?= $quoteFollowupCount === 1 ? '' : 's' ?> ya enviada<?= $quoteFollowupCount === 1 ? '' : 's' ?>.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Monto aceptado</span>
            <strong><?= htmlspecialchars($quoteFmtMoney($quoteAcceptedTotal)) ?></strong>
            <small>Negocio convertido desde la bandeja.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Por cobrar</span>
            <strong><?= htmlspecialchars($quoteFmtMoney($quoteCollectionsPendingTotal)) ?></strong>
            <small><?= htmlspecialchars($quoteFmtMoney($quoteRetainerPendingTotal)) ?> en anticipos pendientes.</small>
        </article>
    </div>

    <div class="quote-layout quotes-layout">
        <aside class="crm-sidebar quotes-sidebar">
            <div class="crm-sidebar-title">Estados</div>
            <div class="quotes-status-list">
                <?php foreach (['BORRADOR', 'ENVIADA', 'ACEPTADA', 'RECHAZADA', 'ANULADA'] as $quoteStatusKey): ?>
                    <div class="quotes-status-item">
                        <span class="dash-tag <?= htmlspecialchars((string)([
                            'BORRADOR' => 'status-pendiente',
                            'ENVIADA' => 'status-contactado',
                            'ACEPTADA' => 'status-ganado',
                            'RECHAZADA' => 'status-perdido',
                            'ANULADA' => 'status-cancelado',
                        ][$quoteStatusKey] ?? 'status-pendiente')) ?>"><?= htmlspecialchars($quoteBucketTitles[$quoteStatusKey]) ?></span>
                        <strong><?= (int)($quoteCounts[$quoteStatusKey] ?? 0) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="leads-sidebar-panel">
                <div class="leads-sidebar-panel-label">En foco</div>
                <ul class="leads-sidebar-list">
                    <li><?= (int)$quoteCounts['BORRADOR'] ?> borrador<?= (int)$quoteCounts['BORRADOR'] === 1 ? '' : 'es' ?> por convertir en envío</li>
                    <li><?= (int)($dashboard_metrics['followup_due'] ?? 0) ?> cotización<?= (int)($dashboard_metrics['followup_due'] ?? 0) === 1 ? '' : 'es' ?> con seguimiento vencido</li>
                    <li><?= htmlspecialchars($quoteFmtMoney($quoteCollectionsPendingTotal)) ?> todavía por cobrar</li>
                    <?php if ($quoteReminderDueCount > 0): ?>
                        <li><?= $quoteReminderDueCount ?> recordatorio<?= $quoteReminderDueCount === 1 ? '' : 's' ?> de caja listo<?= $quoteReminderDueCount === 1 ? '' : 's' ?></li>
                    <?php endif; ?>
                </ul>
            </div>
        </aside>

        <div class="quote-column quote-pane quotes-main" id="servicesQuoteInboxPane">
            <?php if (empty($cotizaciones)): ?>
                <div class="dash-card leads-empty-state">
                    <h3>No tienes cotizaciones guardadas todavía</h3>
                    <p class="muted">Empieza con una propuesta nueva desde el cotizador o convierte un lead directamente desde la bandeja comercial.</p>
                    <div class="quotes-hero-actions">
                        <a class="btn btn-primary" href="/dashboard/cotizador">Nueva cotización</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach (['BORRADOR', 'ENVIADA', 'ACEPTADA', 'RECHAZADA', 'ANULADA'] as $quoteBucketKey): ?>
                    <?php $quoteItems = $quoteBuckets[$quoteBucketKey] ?? []; if (empty($quoteItems)) continue; ?>
                    <section class="quotes-bucket">
                        <div class="dash-card leads-bucket-head">
                            <div>
                                <strong><?= htmlspecialchars($quoteBucketTitles[$quoteBucketKey] ?? $quoteBucketKey) ?></strong>
                                <div class="muted"><?= htmlspecialchars($quoteBucketDescriptions[$quoteBucketKey] ?? '') ?></div>
                            </div>
                            <span class="dash-tag <?= htmlspecialchars((string)([
                                'BORRADOR' => 'status-pendiente',
                                'ENVIADA' => 'status-contactado',
                                'ACEPTADA' => 'status-ganado',
                                'RECHAZADA' => 'status-perdido',
                                'ANULADA' => 'status-cancelado',
                            ][$quoteBucketKey] ?? 'status-pendiente')) ?>"><?= (int)count($quoteItems) ?> cotización<?= count($quoteItems) === 1 ? '' : 'es' ?></span>
                        </div>

                        <div class="quotes-card-grid">
                            <?php foreach ($quoteItems as $cot): ?>
                                <?php
                                    $quoteStateKey = strtoupper(trim((string)($cot['estado'] ?? 'BORRADOR')));
                                    $quoteWorkflow = (array)($cot['workflow'] ?? []);
                                    $quoteCollectionWorkflow = (array)($cot['collection_workflow'] ?? []);
                                    $quoteUpdatedLabel = $humanLeadAgo($cot['updated_at'] ?? $cot['created_at'] ?? null) ?? 'sin fecha visible';
                                    $quoteReminderSentLabel = !empty($cot['cobro_reminder_sent_at'])
                                        ? ($humanLeadAgo($cot['cobro_reminder_sent_at']) ?? null)
                                        : null;
                                    $quoteBody = trim((string)($cot['detalle'] ?? ''));
                                    if ($quoteBody !== '' && function_exists('mb_strlen') && mb_strlen($quoteBody) > 220) {
                                        $quoteBody = rtrim((string)mb_substr($quoteBody, 0, 220)) . '...';
                                    } elseif ($quoteBody !== '' && strlen($quoteBody) > 220) {
                                        $quoteBody = rtrim(substr($quoteBody, 0, 220)) . '...';
                                    }
                                    $quotePriority = 'Baja';
                                    if ($quoteStateKey === 'BORRADOR') {
                                        $quotePriority = 'Alta';
                                    } elseif ($quoteStateKey === 'ENVIADA') {
                                        $quotePriority = 'Media';
                                    } elseif ($quoteStateKey === 'ACEPTADA') {
                                        $quotePriority = 'Cerrado';
                                    }
                                    $quotePriorityClass = [
                                        'Alta' => 'priority-alta',
                                        'Media' => 'priority-media',
                                        'Baja' => 'priority-baja',
                                        'Cerrado' => 'priority-cerrado',
                                    ][$quotePriority] ?? 'priority-baja';
                                ?>
                                <article class="quote-item-card quote-inbox-card">
                                    <div class="quote-card-top">
                                        <div class="quote-card-title">
                                            <div class="lead-card-pills">
                                                <span class="home-pill <?= htmlspecialchars($quotePriorityClass) ?>"><?= htmlspecialchars($quotePriority) ?></span>
                                                <span class="dash-tag <?= htmlspecialchars((string)($cot['estado_class'] ?? 'status-pendiente')) ?>"><?= htmlspecialchars((string)($cot['estado_ui'] ?? 'Borrador')) ?></span>
                                                <?php if (($cot['estado'] ?? '') === 'ACEPTADA'): ?>
                                                    <span class="dash-tag <?= htmlspecialchars((string)($cot['cobro_estado_class'] ?? 'status-contactado')) ?>"><?= htmlspecialchars((string)($cot['cobro_estado_ui'] ?? 'Por cobrar')) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <strong><?= htmlspecialchars((string)($cot['servicio_nombre_resuelto'] ?? 'Cotización')) ?></strong>
                                            <div class="muted"><?= htmlspecialchars((string)($cot['client_name'] ?? 'Cliente')) ?> · <?= htmlspecialchars((string)($cot['materia'] ?? 'Sin materia')) ?></div>
                                        </div>
                                        <div class="lead-card-rail">
                                            <span><?= htmlspecialchars($quoteUpdatedLabel) ?></span>
                                            <span><?= !empty($cot['client_whatsapp']) ? '+56 ' . htmlspecialchars((string)$cot['client_whatsapp']) : 'Sin WhatsApp' ?></span>
                                        </div>
                                    </div>

                                    <div class="quote-amounts-inline quote-amounts-grid">
                                        <span>Total <strong><?= formatClpAmount($cot['total'] ?? 0) ?></strong></span>
                                        <span>Anticipo <strong><?= formatClpAmount($cot['anticipo'] ?? 0) ?></strong></span>
                                        <span>Saldo <strong><?= formatClpAmount($cot['saldo'] ?? 0) ?></strong></span>
                                        <?php if (($cot['estado'] ?? '') === 'ACEPTADA'): ?>
                                            <span>Pagado <strong><?= formatClpAmount($cot['cobrado_monto_resuelto'] ?? 0) ?></strong></span>
                                            <span>Por cobrar <strong><?= formatClpAmount($cot['por_cobrar_monto'] ?? 0) ?></strong></span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($quoteBody !== ''): ?>
                                        <p class="quote-body quote-body-box"><?= nl2br(htmlspecialchars($quoteBody)) ?></p>
                                    <?php endif; ?>

                                    <div class="lead-next-step quote-next-step">
                                        <strong>Siguiente paso comercial</strong>
                                        <span>
                                            <?= htmlspecialchars((string)($quoteWorkflow['body'] ?? 'Revisa la siguiente acción comercial para esta cotización.')) ?>
                                        </span>
                                        <?php if (!empty($quoteWorkflow['due_label'])): ?>
                                            <small class="workflow-due-label priority-<?= htmlspecialchars(strtolower((string)($quoteWorkflow['priority'] ?? 'baja'))) ?>">
                                                <?= htmlspecialchars((string)$quoteWorkflow['due_label']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="lead-next-step quote-next-step quote-collection-step">
                                        <strong>Siguiente paso de caja</strong>
                                        <span>
                                            <?= htmlspecialchars((string)($quoteCollectionWorkflow['body'] ?? 'Todavía no hay acción de caja para esta cotización.')) ?>
                                        </span>
                                        <?php if (!empty($quoteCollectionWorkflow['due_label'])): ?>
                                            <small class="workflow-due-label priority-<?= htmlspecialchars(strtolower((string)($quoteCollectionWorkflow['priority'] ?? 'baja'))) ?>">
                                                <?= htmlspecialchars((string)$quoteCollectionWorkflow['due_label']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (($cot['estado'] ?? '') === 'ACEPTADA' && (float)($cot['por_cobrar_monto'] ?? 0) > 0 && (!empty($cot['collection_reminder_ready']) || !empty($cot['cobro_reminder_sent_at']))): ?>
                                        <div class="quote-reminder-meta">
                                            <?php if (!empty($cot['collection_reminder_ready'])): ?>
                                                <span class="dash-tag status-contactado">Recordatorio listo</span>
                                            <?php endif; ?>
                                            <?php if ($quoteReminderSentLabel): ?>
                                                <span class="muted">Último recordatorio <?= htmlspecialchars($quoteReminderSentLabel) ?></span>
                                            <?php endif; ?>
                                            <?php if ((int)($cot['cobro_reminder_count_resuelto'] ?? 0) > 0): ?>
                                                <span class="muted"><?= (int)$cot['cobro_reminder_count_resuelto'] ?> envío<?= (int)$cot['cobro_reminder_count_resuelto'] === 1 ? '' : 's' ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="quote-actions-grid">
                                        <button
                                            type="button"
                                            class="btn btn-primary"
                                            onclick="dashboardEditQuote(this)"
                                            data-quote="<?= $jsonAttr([
                                                'id' => (int)($cot['id'] ?? 0),
                                                'service_id' => (int)($cot['servicio_id'] ?? 0),
                                                'cliente_id' => (int)($cot['cliente_id'] ?? 0),
                                                'client_name' => (string)($cot['client_name'] ?? ''),
                                                'client_whatsapp' => (string)($cot['client_whatsapp'] ?? ''),
                                                'client_email' => (string)($cot['client_email'] ?? ''),
                                                'asunto' => (string)($cot['asunto'] ?? ''),
                                                'materia' => (string)($cot['materia'] ?? ''),
                                                'detalle' => (string)($cot['detalle'] ?? ''),
                                                'no_incluye' => (string)($cot['no_incluye'] ?? ''),
                                                'plazo_estimado' => (string)($cot['plazo_estimado'] ?? ''),
                                                'vigencia' => (string)($cot['vigencia'] ?? ''),
                                                'honorarios' => (string)($cot['honorarios'] ?? '0'),
                                                'gastos' => (string)($cot['gastos'] ?? '0'),
                                                'descuento' => (string)($cot['descuento'] ?? '0'),
                                                'anticipo' => (string)($cot['anticipo'] ?? '0'),
                                                'condiciones_pago' => (string)($cot['condiciones_pago'] ?? ''),
                                                'payment_link' => (string)($cot['payment_link'] ?? ''),
                                                'notas' => (string)($cot['notas'] ?? ''),
                                                'estado' => (string)($cot['estado'] ?? 'BORRADOR'),
                                            ]) ?>">Abrir</button>
                                        <a class="btn btn-ghost" href="/dashboard/cotizaciones/<?= (int)($cot['id'] ?? 0) ?>/pdf" target="_blank" rel="noopener">PDF</a>
                                    </div>

                                    <?php if (($cot['estado'] ?? '') === 'ACEPTADA' && (float)($cot['por_cobrar_monto'] ?? 0) > 0): ?>
                                        <div class="tiny-row quote-collection-actions">
                                            <?php foreach ([
                                                'PENDIENTE' => 'Pendiente',
                                                'ANTICIPO' => 'Anticipo recibido',
                                                'PAGADA' => 'Pagada',
                                            ] as $collectionKey => $collectionLabel): ?>
                                                <?php if (($cot['cobro_estado_resuelto'] ?? 'PENDIENTE') === $collectionKey) continue; ?>
                                                <form method="POST" action="/dashboard/cotizaciones/cobro">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <input type="hidden" name="quote_id" value="<?= (int)($cot['id'] ?? 0) ?>">
                                                    <button class="btn btn-ghost" name="collection_action" value="<?= htmlspecialchars($collectionKey) ?>" type="submit"><?= htmlspecialchars($collectionLabel) ?></button>
                                                </form>
                                            <?php endforeach; ?>
                                            <?php if (!empty($cot['client_email'])): ?>
                                                <form method="POST" action="/dashboard/cotizaciones/cobro-recordatorio">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <input type="hidden" name="quote_id" value="<?= (int)($cot['id'] ?? 0) ?>">
                                                    <button class="btn btn-ghost" type="submit">Recordar por email</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (!empty($cot['collection_reminder_whatsapp_href'])): ?>
                                                <a class="btn btn-ghost" href="<?= htmlspecialchars((string)$cot['collection_reminder_whatsapp_href']) ?>" target="_blank" rel="noopener">WhatsApp cobro</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="quote-secondary-links">
                                        <?php if (!empty($cot['client_whatsapp_href'])): ?>
                                            <a href="<?= htmlspecialchars((string)$cot['client_whatsapp_href']) ?>" target="_blank" rel="noopener">WhatsApp</a>
                                        <?php endif; ?>
                                        <?php if (!empty($cot['client_email_href'])): ?>
                                            <a href="<?= htmlspecialchars((string)$cot['client_email_href']) ?>">Email</a>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-linklike" onclick="copyTextFromButton(this)" data-copy-text="<?= htmlspecialchars((string)($cot['mensaje_texto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Copiar</button>
                                    </div>

                                    <div class="tiny-row quote-state-actions">
                                        <?php foreach (['BORRADOR' => 'Borrador', 'ENVIADA' => 'Enviada', 'ACEPTADA' => 'Aceptada', 'RECHAZADA' => 'Rechazada', 'ANULADA' => 'Anulada'] as $estadoKey => $estadoLabel): ?>
                                            <?php if (($cot['estado'] ?? 'BORRADOR') === $estadoKey) continue; ?>
                                            <form method="POST" action="/dashboard/cotizaciones/estado">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                <input type="hidden" name="quote_id" value="<?= (int)($cot['id'] ?? 0) ?>">
                                                <button class="btn btn-ghost" name="estado" value="<?= htmlspecialchars($estadoKey) ?>" type="submit"><?= htmlspecialchars($estadoLabel) ?></button>
                                            </form>
                                        <?php endforeach; ?>
                                        <?php if (strtoupper(trim((string)($cot['estado'] ?? 'BORRADOR'))) === 'BORRADOR'): ?>
                                            <form method="POST" action="/dashboard/cotizaciones/eliminar">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                <input type="hidden" name="quote_id" value="<?= (int)($cot['id'] ?? 0) ?>">
                                                <button class="btn btn-ghost" type="submit">Eliminar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="dash-card upsell-inline-card tone-<?= htmlspecialchars((string)($quoteSubscription['tone'] ?? 'founder')) ?>">
        <div class="upsell-inline-copy">
            <div class="upsell-inline-kicker">Siguiente nivel</div>
            <strong><?= htmlspecialchars($quoteNudgeTitle) ?></strong>
            <p><?= htmlspecialchars($quoteNudgeBody) ?></p>
        </div>
        <a class="btn btn-primary" href="<?= htmlspecialchars($quoteUpgradeHref) ?>"><?= htmlspecialchars($quoteUpgradeLabel) ?></a>
    </div>
</section>
