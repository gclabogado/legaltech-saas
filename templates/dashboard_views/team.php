<?php
    $teamStateView = (array)($team_state ?? []);
    $team = (array)($teamStateView['team'] ?? []);
    $teamMembership = (array)($teamStateView['membership'] ?? []);
    $teamMembers = (array)($teamStateView['members'] ?? []);
    $teamPending = (array)($teamStateView['pending_invites'] ?? []);
    $teamActivityFeed = array_slice(array_values((array)($team_activity_feed ?? [])), 0, 8);
    $teamCanManage = !empty($teamStateView['can_manage']);
    $teamExists = !empty($team);
    $teamActivityToday = 0;
    foreach ($teamActivityFeed as $teamActivityRow) {
        if (strpos((string)($teamActivityRow['created_at'] ?? ''), date('Y-m-d')) === 0) {
            $teamActivityToday++;
        }
    }
?>
<section id="view-team" class="grid" style="margin-top: 16px;">
    <div class="dash-card team-hero">
        <div class="team-hero-copy">
            <div class="home-kicker">Cuenta / Team</div>
            <h2 class="home-title"><?= $teamExists ? htmlspecialchars((string)($team['nombre'] ?? 'Tu team')) : 'Convierte el despacho en un workspace compartido' ?></h2>
            <p class="home-subtitle">
                <?php if ($teamExists): ?>
                    Miembros activos: <?= count($teamMembers) ?>. Invitaciones pendientes: <?= count($teamPending) ?>.
                <?php else: ?>
                    Crea un team e invita por email para operar un workspace jurídico compartido.
                <?php endif; ?>
            </p>
        </div>
        <div class="team-hero-actions">
            <a class="btn btn-ghost" href="/dashboard/cuenta">Volver a Cuenta</a>
        </div>
    </div>

    <?php if (!$teamExists): ?>
        <div class="team-grid">
            <div class="dash-card team-panel">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Crear team jurídico</h2>
                        <p class="muted">Un abogado crea el team y luego invita a otros por email.</p>
                    </div>
                </div>
                <form id="teamCreateForm" class="quote-form-grid" method="POST" action="/dashboard/team/create">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <label class="quote-label">
                        Nombre del team
                        <input class="input" type="text" name="team_name" placeholder="Ej: FLOCID Penal & Litigios" required>
                    </label>
                    <div class="dash-actions-3">
                        <button class="btn btn-primary" type="submit">Crear team</button>
                    </div>
                </form>
            </div>

            <div class="dash-card team-panel">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Qué habilita este MVP</h2>
                        <p class="muted">No es solo un upsell visual: ya deja una estructura operativa real de equipo.</p>
                    </div>
                </div>
                <div class="subscription-feature-list">
                    <div class="subscription-feature-item"><strong>Team persistido en base de datos</strong></div>
                    <div class="subscription-feature-item"><strong>Invitaciones por email con roles</strong></div>
                    <div class="subscription-feature-item"><strong>Auto-vinculación cuando el abogado invitado entra al panel</strong></div>
                    <div class="subscription-feature-item"><strong>Base lista para compartir workspace en la siguiente fase</strong></div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="team-metrics-grid">
            <article class="dash-stat home-metric-card">
                <span class="home-metric-label">Miembros activos</span>
                <strong><?= count($teamMembers) ?></strong>
                <small>Abogados ya vinculados al team.</small>
            </article>
            <article class="dash-stat home-metric-card">
                <span class="home-metric-label">Invitaciones pendientes</span>
                <strong><?= count($teamPending) ?></strong>
                <small>Se activan cuando ese correo entra al panel profesional.</small>
            </article>
            <article class="dash-stat home-metric-card">
                <span class="home-metric-label">Tu rol</span>
                <strong><?= htmlspecialchars((string)($teamMembership['rol'] ?? 'member')) ?></strong>
                <small><?= $teamCanManage ? 'Puedes gestionar el team.' : 'Tienes acceso como miembro.' ?></small>
            </article>
            <article class="dash-stat home-metric-card">
                <span class="home-metric-label">Actividad hoy</span>
                <strong><?= $teamActivityToday ?></strong>
                <small>Movimiento registrado dentro del workspace compartido.</small>
            </article>
        </div>

        <div class="team-grid">
            <div class="dash-card team-panel">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Miembros del team</h2>
                        <p class="muted">Lista actual del equipo jurídico y su rol dentro del workspace.</p>
                    </div>
                </div>
                <div class="team-member-list">
                    <?php foreach ($teamMembers as $member): ?>
                        <div class="team-member-card">
                            <div>
                                <strong><?= htmlspecialchars((string)($member['abogado_nombre'] ?? $member['nombre_invitado'] ?? $member['email'] ?? 'Miembro')) ?></strong>
                                <div class="muted"><?= htmlspecialchars((string)($member['abogado_email'] ?? $member['email'] ?? '')) ?></div>
                            </div>
                            <div class="team-member-side">
                                <span class="dash-tag status-ganado"><?= htmlspecialchars((string)($member['rol'] ?? 'member')) ?></span>
                                <?php if ($teamCanManage && strtolower((string)($member['rol'] ?? 'member')) !== 'owner'): ?>
                                    <form method="POST" action="/dashboard/team/member/remove" onsubmit="return confirm('¿Quitar este miembro del team?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <input type="hidden" name="member_id" value="<?= (int)($member['id'] ?? 0) ?>">
                                        <button class="btn btn-ghost" type="submit">Quitar</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dash-card team-panel">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Invitar abogado al team</h2>
                        <p class="muted">Puedes invitar por email aunque la otra persona aún no se haya registrado.</p>
                    </div>
                </div>
                <?php if ($teamCanManage): ?>
                    <form id="teamInviteForm" class="quote-form-grid" method="POST" action="/dashboard/team/invite">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                        <label class="quote-label">
                            Nombre referencial
                            <input class="input" type="text" name="invite_name" placeholder="Ej: Daniela Flocid">
                        </label>
                        <div class="quote-fields-2">
                            <label class="quote-label">
                                Email
                                <input class="input" type="email" name="invite_email" placeholder="colega@correo.cl" required>
                            </label>
                            <label class="quote-label">
                                Rol
                                <select class="input" name="invite_role">
                                    <option value="member">Miembro</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </label>
                        </div>
                        <div class="dash-actions-3">
                            <button class="btn btn-primary" type="submit">Guardar invitación</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="muted">Tu rol actual no permite invitar miembros. Pide apoyo al owner o admin del team.</p>
                <?php endif; ?>

                <div class="home-section-head" style="margin-top:18px;">
                    <div>
                        <h2 class="section-title">Pendientes</h2>
                        <p class="muted">Quedan activas cuando ese correo entra al panel profesional.</p>
                    </div>
                </div>
                <div class="team-member-list">
                    <?php if (empty($teamPending)): ?>
                        <div class="muted">No hay invitaciones pendientes.</div>
                    <?php else: ?>
                        <?php foreach ($teamPending as $member): ?>
                            <div class="team-member-card pending">
                                <div>
                                    <strong><?= htmlspecialchars((string)($member['nombre_invitado'] ?? $member['email'] ?? 'Invitación')) ?></strong>
                                    <div class="muted"><?= htmlspecialchars((string)($member['email'] ?? '')) ?></div>
                                </div>
                                <div class="team-member-side">
                                    <span class="dash-tag status-contactado"><?= htmlspecialchars((string)($member['rol'] ?? 'member')) ?> · pendiente</span>
                                    <?php if ($teamCanManage): ?>
                                        <form method="POST" action="/dashboard/team/member/remove" onsubmit="return confirm('¿Eliminar esta invitación?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                            <input type="hidden" name="member_id" value="<?= (int)($member['id'] ?? 0) ?>">
                                            <button class="btn btn-ghost" type="submit">Eliminar</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dash-card team-panel">
                <div class="home-section-head">
                    <div>
                        <h2 class="section-title">Actividad reciente del team</h2>
                        <p class="muted">Trazabilidad básica de cambios, invitaciones y movimientos del workspace.</p>
                    </div>
                </div>
                <div class="team-activity-list">
                    <?php if (empty($teamActivityFeed)): ?>
                        <div class="muted">Todavía no hay actividad registrada del team.</div>
                    <?php else: ?>
                        <?php foreach ($teamActivityFeed as $activity): ?>
                            <div class="team-activity-item">
                                <div>
                                    <strong><?= htmlspecialchars((string)($activity['title'] ?? 'Actividad')) ?></strong>
                                    <div class="muted"><?= htmlspecialchars((string)($activity['actor_nombre'] ?? 'Team')) ?> · <?= htmlspecialchars((string)($activity['meta'] ?? '')) ?></div>
                                </div>
                                <span class="dash-tag status-contactado"><?= htmlspecialchars(date('d/m H:i', strtotime((string)($activity['created_at'] ?? 'now')))) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>
