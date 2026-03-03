<?php
    $fmtMoney = static fn($value) => '$' . number_format((float)$value, 0, ',', '.');
    $avgResponseLabel = !empty($dashboard_metrics['avg_response_minutes'])
        ? ((int)$dashboard_metrics['avg_response_minutes'] >= 60
            ? number_format(((int)$dashboard_metrics['avg_response_minutes']) / 60, 1, ',', '.') . ' h'
            : (int)$dashboard_metrics['avg_response_minutes'] . ' min')
        : 'Sin datos';
    $homeTasks = array_slice(array_values((array)($dashboard_tasks ?? [])), 0, 4);
    $homeActivity = array_slice(array_values((array)($dashboard_activity ?? [])), 0, 4);
    $homeAlerts = array_slice(array_values((array)($dashboard_alerts ?? [])), 0, 3);
    $homeNotifications = array_slice(array_values((array)($dashboard_notifications ?? [])), 0, 3);
    $homeTopTask = $homeTasks[0] ?? null;
    $homeSecondTask = $homeTasks[1] ?? null;
    $homeCloseRate = (int)($dashboard_metrics['lead_close_rate'] ?? 0);
    $homeQuoteAcceptance = (int)($dashboard_metrics['quote_acceptance_rate'] ?? 0);
    $homeCollectionsPending = (float)($dashboard_finance['collections_pending_total'] ?? 0);
    $homeRetainerPending = (float)($dashboard_finance['retainer_pending_total'] ?? 0);
?>
<section id="view-home" class="grid" style="margin-top: 16px;">
    <div class="dash-card home-command-hero">
        <div class="home-command-main">
            <div class="home-command-kicker">Centro de comando</div>
            <h2 class="home-command-title">Inicio</h2>
            <p class="home-command-subtitle">
                <?= (int)($dashboard_metrics['new_leads_today'] ?? 0) ?> lead<?= (int)($dashboard_metrics['new_leads_today'] ?? 0) === 1 ? '' : 's' ?> nuevo<?= (int)($dashboard_metrics['new_leads_today'] ?? 0) === 1 ? '' : 's' ?> hoy,
                <?= (int)($dashboard_metrics['quotes_pending'] ?? 0) ?> cotización<?= (int)($dashboard_metrics['quotes_pending'] ?? 0) === 1 ? '' : 'es' ?> por mover
                y <?= (int)($dashboard_metrics['pending_leads'] ?? 0) ?> lead<?= (int)($dashboard_metrics['pending_leads'] ?? 0) === 1 ? '' : 's' ?> sin cierre.
            </p>

            <div class="home-command-actions">
                <a class="btn btn-primary" href="/dashboard/leads">Abrir leads</a>
                <?php if ($homeCollectionsPending > 0): ?>
                    <a class="btn btn-ghost" href="/dashboard/cotizaciones">Ver cobranza</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="home-focus-panel">
            <div class="home-focus-label">Haz esto ahora</div>
            <?php if ($homeTopTask): ?>
                <a class="home-focus-card" href="<?= htmlspecialchars((string)($homeTopTask['href'] ?? '/dashboard')) ?>">
                    <span class="home-pill priority-<?= htmlspecialchars(strtolower((string)($homeTopTask['priority'] ?? 'baja'))) ?>"><?= htmlspecialchars((string)($homeTopTask['priority'] ?? 'Baja')) ?></span>
                    <strong><?= htmlspecialchars((string)($homeTopTask['title'] ?? 'Acción pendiente')) ?></strong>
                    <p><?= htmlspecialchars((string)($homeTopTask['subtitle'] ?? '')) ?></p>
                    <small><?= htmlspecialchars((string)($homeTopTask['cta'] ?? 'Abrir')) ?></small>
                </a>
            <?php else: ?>
                <div class="home-focus-card is-empty">
                    <strong>Bandeja al día</strong>
                    <p>No hay acciones urgentes ahora. Buen momento para empujar cierres o actualizar catálogo.</p>
                </div>
            <?php endif; ?>

            <?php if ($homeSecondTask): ?>
                <a class="home-focus-secondary" href="<?= htmlspecialchars((string)($homeSecondTask['href'] ?? '/dashboard')) ?>">
                    <span><?= htmlspecialchars((string)($homeSecondTask['title'] ?? 'Siguiente acción')) ?></span>
                    <small><?= htmlspecialchars((string)($homeSecondTask['cta'] ?? 'Abrir')) ?></small>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="home-signal-grid">
        <article class="dash-stat home-signal-card">
            <span class="home-metric-label">Leads nuevos hoy</span>
            <strong><?= (int)($dashboard_metrics['new_leads_today'] ?? 0) ?></strong>
            <small>Entrada fresca que exige velocidad.</small>
        </article>
        <article class="dash-stat home-signal-card">
            <span class="home-metric-label">Leads fríos</span>
            <strong><?= (int)($dashboard_metrics['cold_leads'] ?? 0) ?></strong>
            <small>Leads vencidos o movidos sin seguimiento.</small>
        </article>
        <article class="dash-stat home-signal-card">
            <span class="home-metric-label">Respuesta promedio</span>
            <strong><?= htmlspecialchars($avgResponseLabel) ?></strong>
            <small>Tiempo medio hasta el primer movimiento.</small>
        </article>
        <article class="dash-stat home-signal-card">
            <span class="home-metric-label">Conversión comercial</span>
            <strong><?= $homeCloseRate ?>% / <?= $homeQuoteAcceptance ?>%</strong>
            <small>Cierre de leads y aceptación de cotizaciones.</small>
        </article>
    </div>

    <div class="home-overview-grid">
        <div class="home-overview-main">
            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <h2 class="section-title">Prioridad hoy</h2>
                </div>
                <div class="home-priority-stack">
                    <?php foreach ($homeTasks as $task): ?>
                        <a class="home-priority-item" href="<?= htmlspecialchars((string)($task['href'] ?? '/dashboard')) ?>">
                            <div class="home-priority-main">
                                <span class="home-pill priority-<?= htmlspecialchars(strtolower((string)($task['priority'] ?? 'baja'))) ?>"><?= htmlspecialchars((string)($task['priority'] ?? 'Baja')) ?></span>
                                <strong><?= htmlspecialchars((string)($task['title'] ?? 'Acción pendiente')) ?></strong>
                                <div class="muted"><?= htmlspecialchars((string)($task['subtitle'] ?? '')) ?></div>
                            </div>
                            <span class="home-list-cta"><?= htmlspecialchars((string)($task['cta'] ?? 'Abrir')) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <h2 class="section-title">Actividad reciente</h2>
                </div>
                <div class="home-activity-stack">
                    <?php foreach ($homeActivity as $activity): ?>
                        <a class="home-activity-item" href="<?= htmlspecialchars((string)($activity['href'] ?? '/dashboard')) ?>">
                            <div class="home-dot kind-<?= htmlspecialchars((string)($activity['kind'] ?? 'generic')) ?>"></div>
                            <div class="home-list-main">
                                <strong><?= htmlspecialchars((string)($activity['title'] ?? 'Actividad')) ?></strong>
                                <div class="muted"><?= htmlspecialchars((string)($activity['meta'] ?? '')) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <h2 class="section-title">Negocio hoy</h2>
                </div>
                <div class="home-business-grid">
                    <div class="home-business-card">
                        <span>Nuevos</span>
                        <strong><?= (int)($dashboard_pipeline['nuevos'] ?? 0) ?></strong>
                    </div>
                    <div class="home-business-card">
                        <span>Contactados</span>
                        <strong><?= (int)($dashboard_pipeline['contactados'] ?? 0) ?></strong>
                    </div>
                    <div class="home-business-card">
                        <span>Cotizaciones</span>
                        <strong><?= (int)($dashboard_pipeline['cotizaciones'] ?? 0) ?></strong>
                    </div>
                    <div class="home-business-card">
                        <span>Cerrados</span>
                        <strong><?= (int)($dashboard_pipeline['cerrados'] ?? 0) ?></strong>
                    </div>
                    <div class="home-business-card">
                        <span>Cobrado</span>
                        <strong><?= htmlspecialchars($fmtMoney($dashboard_finance['month_collected'] ?? 0)) ?></strong>
                    </div>
                    <div class="home-business-card">
                        <span>Pipeline</span>
                        <strong><?= htmlspecialchars($fmtMoney($dashboard_finance['pipeline_value'] ?? 0)) ?></strong>
                    </div>
                    <div class="home-business-card">
                        <span>Por cobrar</span>
                        <strong><?= htmlspecialchars($fmtMoney($homeCollectionsPending)) ?></strong>
                    </div>
                    <div class="home-business-card">
                        <span>Anticipos pendientes</span>
                        <strong><?= htmlspecialchars($fmtMoney($homeRetainerPending)) ?></strong>
                    </div>
                </div>
            </section>
        </div>

        <div class="home-overview-side">
            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <h2 class="section-title">Alertas</h2>
                </div>
                <div class="home-alert-stack">
                    <?php foreach ($homeAlerts as $alert): ?>
                        <a class="home-alert severity-<?= htmlspecialchars((string)($alert['severity'] ?? 'low')) ?>" href="<?= htmlspecialchars((string)($alert['href'] ?? '/dashboard')) ?>">
                            <strong><?= htmlspecialchars((string)($alert['title'] ?? 'Alerta')) ?></strong>
                            <p><?= htmlspecialchars((string)($alert['body'] ?? '')) ?></p>
                            <span><?= htmlspecialchars((string)($alert['cta'] ?? 'Abrir')) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php if (!empty($homeNotifications)): ?>
                <section class="dash-card home-section-card">
                    <div class="home-section-head">
                        <h2 class="section-title">Próximos hitos</h2>
                    </div>
                    <div class="home-notification-stack">
                        <?php foreach ($homeNotifications as $notification): ?>
                            <div class="home-notification-item">
                                <div class="home-list-main">
                                    <strong><?= htmlspecialchars((string)($notification['title'] ?? 'Notificación')) ?></strong>
                                    <div class="muted"><?= htmlspecialchars((string)($notification['body'] ?? '')) ?></div>
                                </div>
                                <span class="home-pill priority-baja">Próximo</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </div>
</section>
