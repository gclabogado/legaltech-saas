<?php
    $performanceRange = (array)($performance_range ?? []);
    $performanceRangeKey = (string)($performanceRange['key'] ?? 'month');
    $performanceRangeLabel = (string)($performanceRange['label'] ?? 'Este mes');
    $performanceRangeMetrics = (array)($performanceRange['metrics'] ?? []);
    $performanceBudget = (float)($performanceRangeMetrics['lead_presupuesto_total'] ?? ($stats['presupuesto_total'] ?? 0));
    $performanceTotal = (int)($performanceRangeMetrics['lead_total'] ?? ($stats['total'] ?? 0));
    $performanceWon = (int)($performanceRangeMetrics['lead_ganado'] ?? ($stats['ganados'] ?? 0));
    $performancePending = (int)($performanceRangeMetrics['lead_no_contactado'] ?? ($stats['pendientes'] ?? 0));
    $performanceViews = (int)($user['vistas_abogados'] ?? 0);
    $performanceCloseRate = $performanceTotal > 0 ? round(($performanceWon / max(1, $performanceTotal)) * 100) : 0;
    $perfAvgMinutes = $performanceRangeMetrics['avg_response_minutes'] ?? $dashboard_metrics['avg_response_minutes'] ?? null;
    $performanceAvgResponse = !empty($perfAvgMinutes)
        ? ((int)$perfAvgMinutes >= 60
            ? number_format(((int)$perfAvgMinutes) / 60, 1, ',', '.') . ' h'
            : (int)$perfAvgMinutes . ' min')
        : 'Sin datos';
    $performancePipeline = (float)($performanceRangeMetrics['pipeline_value'] ?? ($dashboard_finance['pipeline_value'] ?? 0));
    $performanceAccepted = (float)($performanceRangeMetrics['accepted_total'] ?? ($dashboard_finance['accepted_quotes_total'] ?? 0));
    $performanceCollectionsPending = (float)($performanceRangeMetrics['collections_pending_total'] ?? ($dashboard_finance['collections_pending_total'] ?? 0));
    $performanceRetainerPending = (float)($performanceRangeMetrics['retainer_pending_total'] ?? ($dashboard_finance['retainer_pending_total'] ?? 0));
    $performanceQuoteAcceptance = (int)($performanceRangeMetrics['quote_acceptance_rate'] ?? ($dashboard_metrics['quote_acceptance_rate'] ?? 0));
    $performanceColdLeads = (int)($performanceRangeMetrics['lead_cold'] ?? ($dashboard_metrics['cold_leads'] ?? 0));
    $performanceFollowupDue = (int)($performanceRangeMetrics['followup_due'] ?? ($dashboard_metrics['followup_due'] ?? 0));
?>
<section id="view-performance" class="grid" style="margin-top: 16px;">
    <div class="dash-card performance-hero">
        <div class="performance-hero-copy">
            <div class="home-kicker">Lectura ejecutiva</div>
            <h2 class="home-title">Rendimiento</h2>
            <p class="home-subtitle">
                <?= $performanceTotal ?> lead<?= $performanceTotal === 1 ? '' : 's' ?> totales,
                <?= $performanceColdLeads ?> frí<?= $performanceColdLeads === 1 ? 'o' : 'os' ?>,
                <?= $performanceFollowupDue ?> seguimiento<?= $performanceFollowupDue === 1 ? '' : 's' ?> vencido<?= $performanceFollowupDue === 1 ? '' : 's' ?>
                y <?= $performanceQuoteAcceptance ?>% de aceptación en cotizaciones.
            </p>
            <div class="dash-subtabs" style="margin-top:8px;">
                <?php foreach (['day' => 'Día', 'month' => 'Mes', 'year' => 'Año'] as $rangeKey => $rangeLabel): ?>
                    <a class="dash-subtab<?= $performanceRangeKey === $rangeKey ? ' active' : '' ?>" href="/dashboard?tab=performance&range=<?= $rangeKey ?>">
                        <?= htmlspecialchars($rangeLabel) ?>
                    </a>
                <?php endforeach; ?>
                <span class="muted" style="font-size:12px;"><?= htmlspecialchars($performanceRangeLabel) ?></span>
            </div>
        </div>
        <div class="performance-hero-actions">
            <a class="btn btn-ghost" href="/dashboard/cotizaciones">Ver cotizaciones</a>
        </div>
    </div>

    <div class="performance-metrics-grid">
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Respuesta promedio</span>
            <strong><?= htmlspecialchars($performanceAvgResponse) ?></strong>
            <small>Tiempo medio hasta el primer movimiento.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Leads fríos</span>
            <strong><?= $performanceColdLeads ?></strong>
            <small>Lead<?= $performanceColdLeads === 1 ? '' : 's' ?> que ya piden acción inmediata.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Tasa de cierre</span>
            <strong><?= $performanceCloseRate ?>%</strong>
            <small>Ganados sobre el total de casos registrados.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Pipeline vivo</span>
            <strong>$<?= number_format($performancePipeline, 0, ',', '.') ?></strong>
            <small>Monto en cotizaciones enviadas y oportunidades abiertas.</small>
        </article>
    </div>

    <div class="performance-grid">
        <div class="dash-card performance-board">
            <div class="home-section-head">
                <div>
                    <h2 class="section-title">Workflow comercial</h2>
                    <p class="muted">Dónde se está ganando velocidad y dónde se está perdiendo.</p>
                </div>
            </div>
            <div class="performance-board-grid">
                <div class="performance-tile">
                    <span>Leads nuevos</span>
                    <strong><?= (int)($dashboard_metrics['new_leads_today'] ?? 0) ?></strong>
                </div>
                <div class="performance-tile">
                    <span>Sin respuesta</span>
                    <strong><?= (int)($dashboard_metrics['stale_leads'] ?? 0) ?></strong>
                </div>
                <div class="performance-tile">
                    <span>Seguimiento vencido</span>
                    <strong><?= $performanceFollowupDue ?></strong>
                </div>
                <div class="performance-tile">
                    <span>Cotiz. aceptadas</span>
                    <strong><?= $performanceQuoteAcceptance ?>%</strong>
                </div>
                <div class="performance-tile">
                    <span>Monto aceptado</span>
                    <strong>$<?= number_format($performanceAccepted, 0, ',', '.') ?></strong>
                </div>
                <div class="performance-tile">
                    <span>Por cobrar</span>
                    <strong>$<?= number_format($performanceCollectionsPending, 0, ',', '.') ?></strong>
                </div>
                <div class="performance-tile">
                    <span>Vistas del perfil</span>
                    <strong><?= $performanceViews ?></strong>
                </div>
            </div>
        </div>

        <div class="dash-card performance-chip-board">
            <div class="home-section-head">
                <div>
                    <h2 class="section-title">Salud del despacho</h2>
                    <p class="muted">Resumen corto para tomar decisiones sin ir pantalla por pantalla.</p>
                </div>
            </div>
            <div class="mini-chip-row mt-10">
                <span class="mini-chip app-badge badge-info">No contactado: <?= (int)($performanceRangeMetrics['lead_no_contactado'] ?? ($crmCounts['PENDIENTE'] ?? 0)) ?></span>
                <span class="mini-chip app-badge badge-accent">Contactado: <?= (int)($performanceRangeMetrics['lead_contactado'] ?? ($crmCounts['CONTACTADO'] ?? 0)) ?></span>
                <span class="mini-chip app-badge badge-success">Cerró: <?= (int)($performanceRangeMetrics['lead_ganado'] ?? ($crmCounts['GANADO'] ?? 0)) ?></span>
                <span class="mini-chip app-badge badge-warn">No cerró: <?= (int)($performanceRangeMetrics['lead_perdido'] ?? ($crmCounts['PERDIDO'] ?? 0)) ?></span>
                <span class="mini-chip app-badge">Archivado: <?= (int)($performanceRangeMetrics['lead_perdido'] ?? ($crmCounts['CANCELADO'] ?? 0)) ?></span>
                <span class="mini-chip app-badge badge-accent">Presupuesto total: $<?= number_format($performanceBudget, 0, ',', '.') ?></span>
                <span class="mini-chip app-badge badge-warn">Anticipos pendientes: $<?= number_format($performanceRetainerPending, 0, ',', '.') ?></span>
            </div>
        </div>
    </div>
</section>
