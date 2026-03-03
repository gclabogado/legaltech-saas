<?php
    $leadTotal = (int)array_sum($crmCounts ?? []);
    $leadWonAmount = 0.0;
    $leadStaleCount = 0;
    $leadFollowupCount = 0;
    $leadFreshCount = 0;
    $leadNow = time();
    $leadInboxBuckets = [
        'NUEVOS' => [],
        'PENDIENTE' => [],
        'REABIERTO' => [],
        'CONTACTADO' => [],
        'GANADO' => [],
        'ARCHIVO' => [],
    ];

    foreach (($mis_casos ?? []) as $leadStat) {
        $state = strtoupper(trim((string)($leadStat['estado'] ?? 'PENDIENTE')));
        $createdTs = strtotime((string)($leadStat['fecha_revelado'] ?? $leadStat['cliente_created_at'] ?? $leadStat['created_at'] ?? '')) ?: null;
        $updatedTs = strtotime((string)($leadStat['estado_updated_at'] ?? '')) ?: $createdTs;
        if ($state === 'GANADO') {
            $leadWonAmount += (float)($leadStat['presupuesto'] ?? 0);
        }
        if ($state === 'PENDIENTE' && $updatedTs && ($leadNow - $updatedTs) >= 7200) {
            $leadStaleCount++;
        }
        if ($state === 'CONTACTADO') {
            $leadFollowupCount++;
        }
        if ($createdTs && date('Y-m-d', $createdTs) === date('Y-m-d', $leadNow)) {
            $leadFreshCount++;
        }

        if ($state === 'PENDIENTE') {
            if ($createdTs && date('Y-m-d', $createdTs) === date('Y-m-d', $leadNow)) {
                $leadInboxBuckets['NUEVOS'][] = $leadStat;
            } else {
                $leadInboxBuckets['PENDIENTE'][] = $leadStat;
            }
        } elseif ($state === 'CONTACTADO') {
            if (!empty($leadStat['reabierto_at'])) {
                $leadInboxBuckets['REABIERTO'][] = $leadStat;
            } else {
                $leadInboxBuckets['CONTACTADO'][] = $leadStat;
            }
        } elseif ($state === 'GANADO') {
            $leadInboxBuckets['GANADO'][] = $leadStat;
        } else {
            $leadInboxBuckets['ARCHIVO'][] = $leadStat;
        }
    }

    $leadMoney = static fn($value) => '$' . number_format((float)$value, 0, ',', '.');
    $leadSubscription = (array)($subscription_state ?? []);
    $leadPlanKey = (string)($leadSubscription['plan_key'] ?? 'free');
    $leadUpgradeHref = (string)($leadSubscription['cta_href'] ?? 'mailto:contacto@example.com');
    $leadUpgradeLabel = (string)($leadSubscription['cta_label'] ?? 'Pasar a PRO');
    $leadWorkspace = (array)($workspace_context ?? []);
    $leadTeamState = (array)($team_state ?? []);
    $leadAssignees = array_values((array)($lead_assignees ?? []));
    $leadHasTeamWorkspace = !empty($leadWorkspace['team_id']);
    $leadNudgeTitle = 'Escala esta bandeja';
    $leadNudgeBody = 'Desbloquea playbooks, automatización y una operación más fuerte sobre leads y seguimientos.';
    if (in_array($leadPlanKey, ['pro', 'pro_founder'], true)) {
        $leadUpgradeHref = 'mailto:' . rawurlencode((string)($leadSubscription['contact_email'] ?? 'contacto@example.com')) . '?subject=' . rawurlencode('Quiero activar Tu Estudio Juridico Team');
        $leadUpgradeLabel = 'Escalar a Team';
        $leadNudgeTitle = 'Comparte la bandeja con tu equipo';
        $leadNudgeBody = 'El siguiente salto es Team: leads compartidos, colaboración y workspace jurídico conjunto.';
    }
    $leadNewCount = count((array)($leadInboxBuckets['NUEVOS'] ?? []));
    $leadPendingCount = count((array)($leadInboxBuckets['PENDIENTE'] ?? []));
    $leadReopenedCount = count((array)($leadInboxBuckets['REABIERTO'] ?? []));
    $leadArchiveCount = count((array)($leadInboxBuckets['ARCHIVO'] ?? []));
    $leadBucketTitles = [
        'NUEVOS' => 'Nuevos',
        'PENDIENTE' => 'No contactado',
        'REABIERTO' => 'Reabiertos',
        'CONTACTADO' => 'Contactado',
        'GANADO' => 'Cerrados',
        'ARCHIVO' => 'Archivo',
    ];
    $leadBucketDescriptions = [
        'NUEVOS' => 'Entradas de hoy que conviene tocar primero.',
        'PENDIENTE' => 'Leads sin primer contacto fuera del ingreso de hoy.',
        'REABIERTO' => 'Leads que volviste a abrir desde archivo o no cerró.',
        'CONTACTADO' => 'Oportunidades movidas, esperando cierre, cotización o siguiente paso.',
        'GANADO' => 'Clientes que ya contrataron o pagaron.',
        'ARCHIVO' => 'Leads no cerrados o archivados, fuera de la operación activa.',
    ];
    $leadBucketTagClass = [
        'NUEVOS' => 'status-pendiente',
        'PENDIENTE' => 'status-pendiente',
        'REABIERTO' => 'status-contactado',
        'CONTACTADO' => 'status-contactado',
        'GANADO' => 'status-ganado',
        'ARCHIVO' => 'status-cancelado',
    ];
?>
<section id="view-crm" class="grid" style="margin-top: 16px;">
    <div class="dash-card leads-hero">
        <div class="leads-hero-copy">
            <h2 class="home-title">Leads</h2>
            <p class="home-subtitle">
                <?= $leadNewCount ?> nuevo<?= $leadNewCount === 1 ? '' : 's' ?>,
                <?= $leadPendingCount ?> no contactado<?= $leadPendingCount === 1 ? '' : 's' ?>,
                <?= $leadReopenedCount ?> reabierto<?= $leadReopenedCount === 1 ? '' : 's' ?> y
                <?= (int)($crmCounts['CONTACTADO'] ?? 0) ?> en seguimiento
                y <?= (int)($crmCounts['GANADO'] ?? 0) ?> cerrado<?= (int)($crmCounts['GANADO'] ?? 0) === 1 ? '' : 's' ?>.<?php if ($leadHasTeamWorkspace): ?> Workspace compartido activo.<?php endif; ?>
            </p>
            <div class="leads-hero-badges">
                <span class="dash-tag status-pendiente"><?= $leadHasTeamWorkspace ? 'Workspace compartido' : 'Workspace individual' ?></span>
                <span class="dash-tag status-contactado"><?= $leadStaleCount ?> sin respuesta crítica</span>
                <span class="dash-tag status-ganado"><?= $leadNewCount ?> nuevos hoy</span>
            </div>
        </div>
    </div>

    <div class="leads-metrics-grid">
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Sin respuesta</span>
            <strong><?= (int)($crmCounts['PENDIENTE'] ?? 0) ?></strong>
            <small><?= $leadStaleCount ?> con mas de 2 horas sin movimiento.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">En seguimiento</span>
            <strong><?= (int)($crmCounts['CONTACTADO'] ?? 0) ?></strong>
            <small><?= $leadFollowupCount ?> oportunidad<?= $leadFollowupCount === 1 ? '' : 'es' ?> viva<?= $leadFollowupCount === 1 ? '' : 's' ?>.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Leads de hoy</span>
            <strong><?= $leadFreshCount ?></strong>
            <small>Entrada fresca que exige respuesta rapida.</small>
        </article>
    </div>

    <div class="crm-layout leads-layout">
        <aside class="crm-sidebar leads-sidebar">
            <div class="crm-sidebar-title">Filtros</div>
            <div class="crm-filter-row" id="crmFilterRow">
                <button type="button" class="crm-filter-btn tone-new active" data-filter="NUEVOS"><span class="label">Nuevos</span><span class="count"><?= $leadNewCount ?></span></button>
                <button type="button" class="crm-filter-btn tone-pending" data-filter="PENDIENTE"><span class="label">No contactados</span><span class="count"><?= $leadPendingCount ?></span></button>
                <button type="button" class="crm-filter-btn tone-reopened" data-filter="REABIERTO"><span class="label">Reabiertos</span><span class="count"><?= $leadReopenedCount ?></span></button>
                <button type="button" class="crm-filter-btn tone-contacted" data-filter="CONTACTADO"><span class="label">Contactados</span><span class="count"><?= (int)($crmCounts['CONTACTADO'] ?? 0) ?></span></button>
                <button type="button" class="crm-filter-btn tone-won" data-filter="GANADO"><span class="label">Cerrados</span><span class="count"><?= (int)($crmCounts['GANADO'] ?? 0) ?></span></button>
                <button type="button" class="crm-filter-btn tone-archive" data-filter="ARCHIVO"><span class="label">Archivo 🗂️</span><span class="count"><?= $leadArchiveCount ?></span></button>
            </div>

            <div class="leads-sidebar-panel">
                <div class="leads-sidebar-panel-label">En foco</div>
                <ul class="leads-sidebar-list">
                    <li><?= $leadStaleCount ?> lead<?= $leadStaleCount === 1 ? '' : 's' ?> sin respuesta hace mas de 2 horas</li>
                    <li><?= (int)($dashboard_metrics['cold_leads'] ?? 0) ?> lead<?= (int)($dashboard_metrics['cold_leads'] ?? 0) === 1 ? '' : 's' ?> ya estan frios o piden seguimiento</li>
                    <li><?= $leadFreshCount ?> lead<?= $leadFreshCount === 1 ? '' : 's' ?> entraron hoy</li>
                </ul>
            </div>
        </aside>

        <div class="crm-main leads-main">
            <?php if (empty($mis_casos)): ?>
                <div class="dash-card leads-empty-state">
                    <h3>No tienes leads todavia</h3>
                    <p class="muted">Los leads llegan cuando un cliente completa el formulario desde tu perfil o cuando agregas un prospecto manual.</p>
                    <div class="leads-hero-actions">
                        <button type="button" onclick="abrirModalProspecto()" class="btn btn-primary">Crear prospecto</button>
                        <a class="btn btn-ghost" href="/mi-tarjeta">Compartir mi perfil</a>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach (['NUEVOS', 'PENDIENTE', 'REABIERTO', 'CONTACTADO', 'GANADO', 'ARCHIVO'] as $bucketKey): ?>
                    <?php $bucketItems = $leadInboxBuckets[$bucketKey] ?? []; if (empty($bucketItems)) continue; ?>
                    <section class="crm-bucket leads-bucket" data-bucket="<?= htmlspecialchars($bucketKey) ?>">
                        <div class="dash-card leads-bucket-head">
                            <div>
                                <strong><?= htmlspecialchars($leadBucketTitles[$bucketKey] ?? $bucketKey) ?></strong>
                                <div class="muted">
                                    <?= htmlspecialchars((string)($leadBucketDescriptions[$bucketKey] ?? '')) ?>
                                </div>
                            </div>
                            <span class="dash-tag <?= htmlspecialchars($leadBucketTagClass[$bucketKey] ?? '') ?>"><?= (int)count($bucketItems) ?> lead<?= count($bucketItems) === 1 ? '' : 's' ?></span>
                        </div>

                        <div class="leads-card-grid">
                            <?php foreach ($bucketItems as $m): ?>
                                <?php
                                    $mStateKey = strtoupper((string)($m['estado'] ?? 'PENDIENTE'));
                                    $leadWorkflow = (array)($m['workflow'] ?? []);
                                    $mStateTagClass = $crmStateTagClass[$mStateKey] ?? 'status-pendiente';
                                    $mStateShort = [
                                        'PENDIENTE' => $bucketKey === 'NUEVOS' ? 'Nuevo' : 'No contactado',
                                        'CONTACTADO' => 'Contactado',
                                        'GANADO' => 'Cerro',
                                        'PERDIDO' => 'No cerro',
                                        'CANCELADO' => 'Archivado',
                                    ][$mStateKey] ?? ($m['estado_ui'] ?? ($m['estado'] ?? 'PENDIENTE'));
                                    $leadUpdatedLabel = $humanLeadAgo($m['estado_updated_at'] ?? null) ?? 'sin movimiento reciente';
                                    $leadCreatedLabel = $humanLeadAgo($m['fecha_revelado'] ?? ($m['created_at'] ?? null)) ?? null;
                                    $leadPriority = 'Baja';
                                    if ($mStateKey === 'PENDIENTE') {
                                        $leadPriority = $leadUpdatedLabel !== 'sin movimiento reciente' && strpos($leadUpdatedLabel, 'dia') !== false ? 'Alta' : 'Alta';
                                    } elseif ($mStateKey === 'CONTACTADO') {
                                        $leadPriority = 'Media';
                                    } elseif ($mStateKey === 'GANADO') {
                                        $leadPriority = 'Cerrado';
                                    }
                                    $leadPriorityClass = [
                                        'Alta' => 'priority-alta',
                                        'Media' => 'priority-media',
                                        'Baja' => 'priority-baja',
                                        'Cerrado' => 'priority-cerrado',
                                    ][$leadPriority] ?? 'priority-baja';
                                    $leadQuickNote = !empty($m['consulta']) ? trim((string)$m['consulta']) : 'Lead recibido desde el formulario del perfil.';
                                    $leadChannel = trim((string)($m['medio_contacto'] ?? 'Web'));
                                    $leadCity = !empty($m['ciudad']) ? trim((string)$m['ciudad']) : 'Lugar no indicado';
                                    $leadEmail = trim((string)($m['email'] ?? ''));
                                ?>
                                <article class="dash-case lead-inbox-card lead-state-<?= htmlspecialchars(strtolower($mStateKey)) ?>" id="caso-<?= (int)$m['id'] ?>">
                                    <div class="lead-card-top">
                                        <div class="lead-card-title">
                                            <div class="lead-card-pills">
                                                <span class="home-pill <?= htmlspecialchars($leadPriorityClass) ?>"><?= htmlspecialchars($leadPriority) ?></span>
                                                <span class="dash-tag <?= htmlspecialchars($mStateTagClass) ?>"><?= htmlspecialchars((string)$mStateShort) ?></span>
                                            </div>
                                            <strong><?= htmlspecialchars((string)($m['nombre'] ?? 'Cliente')) ?></strong>
                                            <div class="muted"><?= htmlspecialchars((string)($m['especialidad'] ?? 'Sin materia')) ?> · <?= htmlspecialchars($leadChannel) ?> · <?= htmlspecialchars($leadCity) ?></div>
                                        </div>
                                        <div class="lead-card-rail">
                                            <?php if ($leadCreatedLabel): ?>
                                                <span><?= htmlspecialchars($leadCreatedLabel) ?></span>
                                            <?php endif; ?>
                                            <span>Ultimo cambio: <?= htmlspecialchars($leadUpdatedLabel) ?></span>
                                        </div>
                                    </div>

                                    <div class="lead-contact-row">
                                        <span>+56 <?= htmlspecialchars((string)($m['whatsapp'] ?? '')) ?></span>
                                        <span><?= htmlspecialchars($leadEmail !== '' ? $leadEmail : 'Sin correo') ?></span>
                                    </div>

                                    <?php if ($leadHasTeamWorkspace): ?>
                                        <?php
                                            $leadOwnerName = trim((string)($m['lead_owner_name'] ?? ''));
                                            $leadAssignedId = (int)($m['assigned_abogado_id'] ?? $m['abogado_id'] ?? 0);
                                            $leadAssignedName = trim((string)($m['assigned_abogado_nombre'] ?? $leadOwnerName));
                                        ?>
                                        <div class="lead-team-row">
                                            <div class="lead-team-meta">
                                                <span><strong>Origen:</strong> <?= htmlspecialchars($leadOwnerName !== '' ? $leadOwnerName : 'Miembro del team') ?></span>
                                                <span><strong>Responsable:</strong> <?= htmlspecialchars($leadAssignedName !== '' ? $leadAssignedName : 'Sin asignar') ?></span>
                                            </div>
                                            <?php if (!empty($leadAssignees)): ?>
                                                <form method="POST" action="/dashboard/leads/asignar" class="lead-assign-form">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                    <input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>">
                                                    <select class="input" name="assigned_abogado_id">
                                                        <?php foreach ($leadAssignees as $assignee): ?>
                                                            <option value="<?= (int)($assignee['id'] ?? 0) ?>"<?= (int)($assignee['id'] ?? 0) === $leadAssignedId ? ' selected' : '' ?>>
                                                                <?= htmlspecialchars((string)($assignee['name'] ?? 'Miembro')) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button class="btn btn-ghost" type="submit">Guardar</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <p class="lead-summary"><?= htmlspecialchars($leadQuickNote) ?></p>

                                    <div class="lead-next-step">
                                        <strong>Siguiente paso</strong>
                                        <span>
                                            <?= htmlspecialchars((string)($leadWorkflow['body'] ?? 'Revisar el siguiente movimiento comercial de este lead.')) ?>
                                        </span>
                                        <?php if (!empty($leadWorkflow['due_label'])): ?>
                                            <small class="workflow-due-label priority-<?= htmlspecialchars(strtolower((string)($leadWorkflow['priority'] ?? 'baja'))) ?>">
                                                <?= htmlspecialchars((string)$leadWorkflow['due_label']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="dash-actions-3 lead-primary-actions">
                                        <a class="btn btn-primary" href="https://wa.me/56<?= htmlspecialchars((string)($m['whatsapp'] ?? '')) ?>?text=Hola%20<?= urlencode((string)($m['nombre'] ?? '')) ?>%2C%20te%20contacto%20desde%20Tu%20Estudio%20Juridico">Contactar</a>
                                        <button
                                            type="button"
                                            class="btn btn-ghost"
                                            onclick="dashboardQuoteFromLead(this)"
                                            data-lead="<?= $jsonAttr([
                                                'client_id' => (int)($m['cliente_id'] ?? 0),
                                                'name' => (string)($m['nombre'] ?? ''),
                                                'whatsapp' => (string)($m['whatsapp'] ?? ''),
                                                'email' => (string)($m['email'] ?? ''),
                                                'matter' => (string)($m['especialidad'] ?? ''),
                                                'notes' => (string)($m['consulta'] ?? ''),
                                            ]) ?>">Cotizar</button>
                                    </div>
                                    <?php if ($mStateKey === 'CONTACTADO'): ?>
                                        <div class="muted" style="font-size:12px;">Cierre solo desde cotización aceptada.</div>
                                    <?php endif; ?>

                                    <div class="lead-secondary-links">
                                        <a href="tel:+56<?= htmlspecialchars((string)($m['whatsapp'] ?? '')) ?>">Llamar</a>
                                        <?php if ($leadEmail !== ''): ?>
                                            <a href="mailto:<?= htmlspecialchars($leadEmail) ?>">Email</a>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($mStateKey === 'GANADO'): ?>
                                        <div class="lead-amount-row lead-amount-highlight">
                                            <div class="muted"><strong>Cierre:</strong> Cerrado desde cotización aceptada.</div>
                                            <div class="lead-secondary-links">
                                                <a href="/dashboard?tab=quotes">Ver cotizaciones</a>
                                            </div>
                                            <form method="POST" action="/actualizar-estado-caso" class="lead-amount-edit">
                                                <input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                                <button class="btn btn-ghost" name="estado" value="CANCELADO" type="submit">Dejar sin efecto</button>
                                            </form>
                                        </div>
                                    <?php endif; ?>

                                    <div class="tiny-row lead-state-actions">
                                        <?php if ($mStateKey === 'PENDIENTE'): ?>
                                            <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-primary" name="estado" value="CONTACTADO" type="submit">Marcar contactado</button></form>
                                            <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-ghost" name="estado" value="CANCELADO" type="submit">Archivar</button></form>
                                        <?php elseif ($mStateKey === 'CONTACTADO'): ?>
                                            <button
                                                type="button"
                                                class="btn btn-primary"
                                                onclick="dashboardQuoteFromLead(this)"
                                                data-lead="<?= $jsonAttr([
                                                    'client_id' => (int)($m['cliente_id'] ?? 0),
                                                    'name' => (string)($m['nombre'] ?? ''),
                                                    'whatsapp' => (string)($m['whatsapp'] ?? ''),
                                                    'email' => (string)($m['email'] ?? ''),
                                                    'matter' => (string)($m['especialidad'] ?? ''),
                                                    'notes' => (string)($m['consulta'] ?? ''),
                                                ]) ?>">Crear cotización</button>
                                            <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-ghost" name="estado" value="PERDIDO" type="submit">No cerro</button></form>
                                            <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-ghost" name="estado" value="CANCELADO" type="submit">Archivar</button></form>
                                        <?php elseif (in_array($mStateKey, ['PERDIDO', 'CANCELADO'], true)): ?>
                                            <form method="POST" action="/actualizar-estado-caso"><input type="hidden" name="id_caso" value="<?= (int)$m['id'] ?>"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>"><button class="btn btn-ghost" name="estado" value="CONTACTADO" type="submit">Reabrir</button></form>
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

    <?php if (!$leadHasTeamWorkspace): ?>
        <div class="dash-card upsell-inline-card tone-<?= htmlspecialchars((string)($leadSubscription['tone'] ?? 'founder')) ?>">
            <div class="upsell-inline-copy">
                <div class="upsell-inline-kicker">Siguiente nivel</div>
                <strong><?= htmlspecialchars($leadNudgeTitle) ?></strong>
                <p><?= htmlspecialchars($leadNudgeBody) ?></p>
            </div>
            <a class="btn btn-primary" href="<?= htmlspecialchars($leadUpgradeHref) ?>"><?= htmlspecialchars($leadUpgradeLabel) ?></a>
        </div>
    <?php endif; ?>
</section>

<div id="modalProspecto" class="dash-modal">
    <div class="dash-modal-card">
        <h3 style="margin-top:0;">Nuevo prospecto</h3>
        <p class="muted" style="margin:4px 0 10px;font-size:12px;">Ingresa un prospecto manual para gestionarlo igual que un lead del perfil (Nuevo -> Contactado -> Cerro / No cerro).</p>
        <form action="/agregar-prospecto-crm" method="POST" class="grid mt-10" style="grid-template-columns:1fr;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <label class="muted" style="font-size:12px;font-weight:700;">Nombre completo</label>
            <input type="text" name="nombre" placeholder="Nombre completo" required class="input">

            <label class="muted" style="font-size:12px;font-weight:700;">WhatsApp</label>
            <input type="tel" name="whatsapp" placeholder="912345678" required class="input" inputmode="numeric" pattern="9[0-9]{8}">

            <label class="muted" style="font-size:12px;font-weight:700;">Correo (opcional)</label>
            <input type="email" name="email" placeholder="correo@ejemplo.cl" class="input">

            <label class="muted" style="font-size:12px;font-weight:700;">Ciudad / comuna (opcional)</label>
            <input type="text" name="ciudad" placeholder="La Serena" class="input">

            <label class="muted" style="font-size:12px;font-weight:700;">Materia principal</label>
            <select name="especialidad" class="input">
                <?php
                $taxDash = (array)($materias_taxonomia ?? []);
                $materiaKeys = array_keys($taxDash);
                if (empty($materiaKeys)) { $materiaKeys = ['Derecho Civil', 'Derecho Familiar', 'Derecho Laboral', 'Derecho Penal']; }
                foreach ($materiaKeys as $mk):
                ?>
                    <option value="<?= htmlspecialchars($mk) ?>"><?= htmlspecialchars($mk) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="muted" style="font-size:12px;font-weight:700;">Origen del lead</label>
            <select name="medio_contacto" class="input">
                <option value="Referido">Referido</option>
                <option value="Llamada">Llamada</option>
                <option value="WhatsApp">WhatsApp</option>
                <option value="Instagram">Instagram</option>
                <option value="TikTok">TikTok</option>
                <option value="Web">Web</option>
                <option value="Otro abogado">Otro abogado</option>
            </select>

            <label class="muted" style="font-size:12px;font-weight:700;">Mensaje del cliente (opcional)</label>
            <textarea name="consulta" rows="4" class="input" placeholder="Ej: Necesito orientacion penal urgente, tengo audiencia manana y quiero cotizar defensa."></textarea>

            <button type="submit" class="btn btn-primary fullw">Guardar en CRM</button>
            <button type="button" onclick="cerrarModalProspecto()" class="btn btn-ghost fullw">Cancelar</button>
        </form>
    </div>
</div>
