<?php
    $subscriptionPlan = (array)($subscription_state ?? []);
    $subscriptionPlans = (array)($subscriptionPlan['plans'] ?? []);
?>
<section id="view-subscription" class="grid" style="margin-top: 16px;">
    <div class="dash-card subscription-hero tone-<?= htmlspecialchars((string)($subscriptionPlan['tone'] ?? 'founder')) ?>">
        <div class="subscription-hero-copy">
            <div class="home-kicker">Cuenta / Plan</div>
            <h2 class="home-title"><?= htmlspecialchars((string)($subscriptionPlan['plan_label'] ?? 'Suscripción')) ?></h2>
            <p class="home-subtitle">
                <?= htmlspecialchars((string)($subscriptionPlan['headline'] ?? 'Estado del plan profesional.')) ?>
                <?= htmlspecialchars((string)($subscriptionPlan['status_note'] ?? '')) ?>
            </p>
        </div>
        <div class="subscription-hero-side">
            <div class="subscription-hero-price"><?= htmlspecialchars((string)($subscriptionPlan['price_label'] ?? '$0')) ?></div>
            <div class="subscription-hero-status"><?= htmlspecialchars((string)($subscriptionPlan['status_label'] ?? 'Activa')) ?></div>
            <div class="subscription-hero-renewal"><?= htmlspecialchars((string)($subscriptionPlan['renewal_label'] ?? '')) ?></div>
            <a class="btn btn-ghost" href="/dashboard/cuenta">Volver a Cuenta</a>
        </div>
    </div>

    <div class="subscription-metrics-grid">
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Estado</span>
            <strong><?= htmlspecialchars((string)($subscriptionPlan['status_label'] ?? 'Activa')) ?></strong>
            <small><?= htmlspecialchars((string)($subscriptionPlan['renewal_label'] ?? 'Sin información adicional')) ?></small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Plan</span>
            <strong><?= htmlspecialchars((string)($subscriptionPlan['badge'] ?? 'Plan')) ?></strong>
            <small>Visible dentro del producto para dar contexto comercial.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Contacto</span>
            <strong><?= htmlspecialchars((string)($subscriptionPlan['contact_email'] ?? 'contacto@example.com')) ?></strong>
            <small>Canal base para upgrades, cambios y soporte comercial.</small>
        </article>
    </div>

    <div class="subscription-layout">
        <div class="dash-card subscription-current-card">
            <div class="home-section-head">
                <h2 class="section-title">Qué incluye tu plan actual</h2>
            </div>
            <div class="subscription-feature-list">
                <?php foreach ((array)($subscriptionPlan['features'] ?? []) as $feature): ?>
                    <div class="subscription-feature-item">
                        <strong><?= htmlspecialchars((string)$feature) ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dash-card subscription-plans-card">
            <div class="home-section-head">
                <h2 class="section-title">Planes disponibles</h2>
            </div>
            <div class="subscription-team-callout">
                <div>
                    <div class="upsell-inline-kicker">Team jurídico</div>
                    <strong>El plan pensado para compartir leads, catálogo y cotizaciones con otros abogados</strong>
                    <p>Este es el siguiente upsell natural del producto: un workspace común para despacho o equipo legal.</p>
                </div>
                <a class="btn btn-primary" href="/dashboard/cuenta/team">Abrir Team</a>
            </div>
            <div class="subscription-plan-grid">
                <?php foreach ($subscriptionPlans as $planCard): ?>
                    <?php $isCurrentPlan = (($subscriptionPlan['plan_key'] ?? '') === ($planCard['key'] ?? '')); ?>
                    <article class="subscription-plan-card<?= $isCurrentPlan ? ' is-current' : '' ?>">
                        <div class="subscription-plan-top">
                            <strong><?= htmlspecialchars((string)($planCard['label'] ?? 'Plan')) ?></strong>
                            <span><?= htmlspecialchars((string)($planCard['price'] ?? '$0')) ?></span>
                        </div>
                        <p><?= htmlspecialchars((string)($planCard['summary'] ?? '')) ?></p>
                        <div class="subscription-plan-features">
                            <?php foreach ((array)($planCard['features'] ?? []) as $planFeature): ?>
                                <div><?= htmlspecialchars((string)$planFeature) ?></div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($isCurrentPlan): ?>
                            <span class="dash-tag status-ganado">Plan actual</span>
                        <?php else: ?>
                            <a class="btn btn-ghost" href="<?= htmlspecialchars((string)($subscriptionPlan['cta_href'] ?? 'mailto:contacto@example.com')) ?>">Consultar</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
