<?php
    $accountProfileVisible = !empty($user['activo']);
    $accountPlan = (array)($subscription_state ?? []);
    $accountTeam = (array)($team_state ?? []);
    $accountTeamExists = !empty($accountTeam['team']);
    $workspaceContext = (array)($workspace_context ?? []);
    $accountWorkspaceName = trim((string)($workspaceContext['team_name'] ?? ''));
    if ($accountWorkspaceName === '') {
        $accountWorkspaceName = 'Workspace individual';
    }
    $accountLeadPending = (int)($dashboard_metrics['pending_leads'] ?? 0);
    $accountQuotePending = (int)($dashboard_metrics['quotes_pending'] ?? 0);
    $accountCollectionsPending = (float)($dashboard_finance['collections_pending_total'] ?? 0);
    $accountRetainerPending = (float)($dashboard_finance['retainer_pending_total'] ?? 0);
    $accountTeamName = $accountTeamExists
        ? trim((string)($accountTeam['team']['nombre'] ?? 'Team activo'))
        : 'Sin team activo';
    $accountPlanLabel = trim((string)($accountPlan['plan_label'] ?? 'Free'));
    $accountPlanStatus = trim((string)($accountPlan['status_label'] ?? 'Sin cobro activo'));
    $accountDisplayEmail = trim((string)($user['email'] ?? ''));
    $accountDisplaySlug = trim((string)($user['slug'] ?? ''));
    $accountChecklist = [];
    if (!$accountProfileVisible) {
        $accountChecklist[] = ['title' => 'Publica tu perfil', 'body' => 'Activa o completa tu perfil para volver a aparecer en el directorio.', 'href' => '/completar-datos?modo=abogado', 'cta' => 'Completar perfil'];
    }
    if ($accountQuotePending > 0) {
        $accountChecklist[] = ['title' => 'Mueve cotizaciones', 'body' => $accountQuotePending . ' propuesta' . ($accountQuotePending === 1 ? '' : 's') . ' siguen esperando movimiento comercial.', 'href' => '/dashboard/cotizaciones', 'cta' => 'Abrir cotizaciones'];
    }
    if ($accountCollectionsPending > 0) {
        $accountChecklist[] = ['title' => 'Baja a caja', 'body' => formatClpAmount($accountCollectionsPending) . ' siguen pendientes de cobro en el workspace.', 'href' => '/dashboard/cotizaciones', 'cta' => 'Ver cobranza'];
    }
    if (!$accountTeamExists) {
        $accountChecklist[] = ['title' => 'Activa Team', 'body' => 'Invita a otro abogado y convierte el workspace en operación compartida.', 'href' => '/dashboard/cuenta/team', 'cta' => 'Abrir Team'];
    }
    if (empty($accountChecklist)) {
        $accountChecklist[] = ['title' => 'Cuenta al día', 'body' => 'Perfil, plan y workspace están en orden. Buen momento para seguir operando.', 'href' => '/dashboard/home', 'cta' => 'Ir al inicio'];
    }
?>
<section id="view-perfil" class="grid" style="margin-top: 16px;">
    <div class="dash-card perfil-hero">
        <div class="perfil-hero-copy">
            <div class="home-kicker">Cuenta</div>
            <h2 class="home-title">Tu estado operativo</h2>
            <p class="home-subtitle">
                Perfil, plan, team y caja resumidos en una sola vista para mantener el workspace bajo control.
            </p>
        </div>
        <div class="perfil-hero-actions">
            <a class="btn btn-primary" href="/completar-datos?modo=abogado">Editar perfil</a>
            <a class="btn btn-ghost" href="/dashboard/cuenta/plan">Ver plan</a>
        </div>
    </div>

    <div class="subscription-metrics-grid">
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Perfil</span>
            <strong><?= $accountProfileVisible ? 'Publicado' : 'Oculto' ?></strong>
            <small><?= $accountProfileVisible ? 'Tu perfil aparece en el directorio.' : 'Tu perfil sigue fuera del directorio.' ?></small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Plan</span>
            <strong><?= htmlspecialchars($accountPlanLabel) ?></strong>
            <small><?= htmlspecialchars($accountPlanStatus) ?></small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Workspace</span>
            <strong><?= $accountTeamExists ? 'Compartido' : 'Individual' ?></strong>
            <small><?= htmlspecialchars($accountWorkspaceName) ?></small>
        </article>
    </div>

    <div class="home-overview-grid account-overview-grid">
        <div class="home-overview-main">
            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Resumen de cuenta</h2>
                        <p class="muted">Lo esencial del perfil, plan y estructura de trabajo sin salir del dashboard.</p>
                    </div>
                </div>
                <div class="account-summary-stack">
                    <div class="home-priority-item">
                        <div class="home-priority-main">
                            <strong>Perfil público</strong>
                            <span><?= $accountProfileVisible ? 'Publicado y disponible para captar clientes.' : 'Oculto hasta que decidas publicarlo o completar ajustes.' ?></span>
                        </div>
                        <span class="dash-tag <?= $accountProfileVisible ? 'status-ganado' : 'status-cancelado' ?>"><?= $accountProfileVisible ? 'Publicado' : 'Oculto' ?></span>
                    </div>
                    <div class="home-priority-item">
                        <div class="home-priority-main">
                            <strong>Plan actual</strong>
                            <span><?= htmlspecialchars($accountPlanLabel) ?> · <?= htmlspecialchars($accountPlanStatus) ?></span>
                        </div>
                        <span class="dash-tag status-contactado"><?= htmlspecialchars((string)($accountPlan['badge'] ?? 'Plan')) ?></span>
                    </div>
                    <div class="home-priority-item">
                        <div class="home-priority-main">
                            <strong>Team jurídico</strong>
                            <span><?= htmlspecialchars($accountTeamName) ?></span>
                        </div>
                        <span class="dash-tag <?= $accountTeamExists ? 'status-ganado' : 'status-pendiente' ?>"><?= $accountTeamExists ? 'Activo' : 'Sin team' ?></span>
                    </div>
                </div>
            </section>

            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Workspace hoy</h2>
                        <p class="muted">Lectura rápida de operación, pipeline y caja pendiente.</p>
                    </div>
                </div>
                <div class="home-business-grid account-business-grid">
                    <article class="home-business-card">
                        <span>Leads activos</span>
                        <strong><?= $accountLeadPending ?></strong>
                        <small class="muted">Pendientes en la bandeja comercial.</small>
                    </article>
                    <article class="home-business-card">
                        <span>Cotizaciones en juego</span>
                        <strong><?= $accountQuotePending ?></strong>
                        <small class="muted">Borradores o propuestas aún por mover.</small>
                    </article>
                    <article class="home-business-card">
                        <span>Por cobrar</span>
                        <strong><?= htmlspecialchars(formatClpAmount($accountCollectionsPending)) ?></strong>
                        <small class="muted"><?= htmlspecialchars(formatClpAmount($accountRetainerPending)) ?> en anticipos pendientes.</small>
                    </article>
                </div>
            </section>
        </div>

        <aside class="home-overview-side">
            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Accesos rápidos</h2>
                        <p class="muted">Lo que normalmente necesitas tocar desde Cuenta.</p>
                    </div>
                </div>
                <div class="account-shortcuts">
                    <?php if (!empty($user['slug'])): ?>
                        <a class="home-priority-item" href="/<?= htmlspecialchars((string)$user['slug']) ?>" target="_blank" rel="noopener">
                            <div class="home-priority-main">
                                <strong>Ver perfil público</strong>
                                <span>Revisa cómo te ve un cliente desde el directorio.</span>
                            </div>
                            <span class="dash-tag status-contactado">Abrir</span>
                        </a>
                    <?php endif; ?>
                    <a class="home-priority-item" href="/dashboard/cuenta/plan">
                        <div class="home-priority-main">
                            <strong>Plan y facturación</strong>
                            <span>Beneficios actuales, upgrade y estado comercial.</span>
                        </div>
                        <span class="dash-tag status-pendiente">Plan</span>
                    </a>
                    <a class="home-priority-item" href="/dashboard/cuenta/team">
                        <div class="home-priority-main">
                            <strong>Team jurídico</strong>
                            <span>Miembros, invitaciones y actividad del workspace.</span>
                        </div>
                        <span class="dash-tag <?= $accountTeamExists ? 'status-ganado' : 'status-contactado' ?>"><?= $accountTeamExists ? 'Equipo' : 'Crear' ?></span>
                    </a>
                </div>
            </section>

            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Próximos pasos</h2>
                        <p class="muted">Lo que hoy conviene mover desde tu cuenta y workspace.</p>
                    </div>
                </div>
                <div class="account-shortcuts">
                    <?php foreach (array_slice($accountChecklist, 0, 3) as $accountTask): ?>
                        <a class="home-priority-item" href="<?= htmlspecialchars((string)$accountTask['href']) ?>">
                            <div class="home-priority-main">
                                <strong><?= htmlspecialchars((string)$accountTask['title']) ?></strong>
                                <span><?= htmlspecialchars((string)$accountTask['body']) ?></span>
                            </div>
                            <span class="dash-tag status-pendiente"><?= htmlspecialchars((string)$accountTask['cta']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="dash-card home-section-card">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Identidad profesional</h2>
                        <p class="muted">Datos base visibles para clientes y operación.</p>
                    </div>
                </div>
                <div class="subscription-feature-list">
                    <div class="subscription-feature-item"><strong>Email:</strong> <?= htmlspecialchars($accountDisplayEmail !== '' ? $accountDisplayEmail : 'Sin email visible') ?></div>
                    <div class="subscription-feature-item"><strong>Slug:</strong> <?= htmlspecialchars($accountDisplaySlug !== '' ? $accountDisplaySlug : 'sin-slug') ?></div>
                    <div class="subscription-feature-item"><strong>Workspace:</strong> <?= htmlspecialchars($accountWorkspaceName) ?></div>
                    <div class="subscription-feature-item"><strong>Marca:</strong> <a href="/dashboard/marca">Abrir marca del estudio</a></div>
                </div>
            </section>
        </aside>
    </div>
</section>
