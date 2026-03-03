<?php
    $catalogCount = count((array)($servicios ?? []));
    $catalogActiveCount = 0;
    $catalogDraftValue = 0.0;
    foreach (($servicios ?? []) as $catalogService) {
        if (!empty($catalogService['activo'])) $catalogActiveCount++;
        $catalogDraftValue += (float)($catalogService['precio_base'] ?? 0);
    }
?>
<section id="view-services" class="grid" style="margin-top: 16px;">
    <div class="crm-breadcrumb">Negocio<span class="sep">/</span>Catálogo</div>

    <div class="dash-card catalog-hero">
        <div class="catalog-hero-copy">
            <div class="home-kicker">Service library</div>
            <h2 class="home-title">Tu catálogo comercial listo para cotizar rápido</h2>
            <p class="home-subtitle">
                <?= $catalogCount ?> servicio<?= $catalogCount === 1 ? '' : 's' ?> cargado<?= $catalogCount === 1 ? '' : 's' ?>,
                <?= $catalogActiveCount ?> activo<?= $catalogActiveCount === 1 ? '' : 's' ?> para cotizar
                y una base reusable para no volver a escribir el mismo alcance cada vez.
            </p>
        </div>
        <div class="catalog-hero-actions">
            <a class="btn btn-ghost" href="/dashboard/cotizador">Usar en cotizador</a>
        </div>
    </div>

    <div class="catalog-metrics-grid">
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Servicios totales</span>
            <strong><?= $catalogCount ?></strong>
            <small>Biblioteca comercial total disponible.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Activos</span>
            <strong><?= $catalogActiveCount ?></strong>
            <small>Listos para usar en nuevas cotizaciones.</small>
        </article>
        <article class="dash-stat home-metric-card">
            <span class="home-metric-label">Valor base agregado</span>
            <strong><?= formatClpAmount($catalogDraftValue) ?></strong>
            <small>Suma de honorarios base del catálogo actual.</small>
        </article>
    </div>

    <div class="quote-layout catalog-layout">
        <div class="quote-column quote-pane" id="servicesCatalogPane">
            <div class="dash-card catalog-form-shell">
                <div class="quote-section-head">
                    <div>
                        <h2 class="section-title">Crear o editar servicio</h2>
                        <p class="muted">Define el servicio una vez y reutilízalo en cada cotización.</p>
                    </div>
                </div>
                <form id="serviceForm" action="/dashboard/servicios/guardar" method="POST" class="quote-form-grid">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="hidden" name="service_id" id="service_id" value="">
                    <label class="quote-label">
                        Nombre del servicio
                        <input class="input" type="text" name="nombre_servicio" id="service_nombre" placeholder="Ej: Consulta penal urgente" required>
                    </label>
                    <div class="quote-fields-2">
                        <label class="quote-label">
                            Materia
                            <select class="input" name="materia" id="service_materia">
                                <option value="">Selecciona una materia</option>
                                <?php foreach (array_keys((array)($materias_taxonomia ?? [])) as $matDash): ?>
                                    <option value="<?= htmlspecialchars((string)$matDash) ?>"><?= htmlspecialchars((string)$matDash) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label class="quote-label">
                            Plazo estimado
                            <input class="input" type="text" name="plazo_estimado" id="service_plazo" placeholder="24 horas, 3 días hábiles">
                        </label>
                    </div>
                    <label class="quote-label">
                        Detalle del servicio
                        <textarea class="input quote-textarea" name="detalle" id="service_detalle" placeholder="Explica qué incluye este servicio y qué recibe el cliente."></textarea>
                    </label>
                    <div class="quote-fields-3">
                        <label class="quote-label">
                            Honorarios base
                            <input class="input" type="number" min="0" step="1000" name="precio_base" id="service_precio" value="0">
                        </label>
                        <label class="quote-label">
                            Gastos base
                            <input class="input" type="number" min="0" step="1000" name="gastos_base" id="service_gastos" value="0">
                        </label>
                        <label class="quote-label quote-checkbox">
                            <span>Estado</span>
                            <span class="quote-checkbox-row">
                                <input type="checkbox" name="activo" id="service_activo" checked>
                                Activo para cotizar
                            </span>
                        </label>
                    </div>
                    <div class="dash-actions-3">
                        <button class="btn btn-primary" type="submit">Guardar servicio</button>
                        <button class="btn btn-ghost" type="button" onclick="dashboardResetServiceForm()">Limpiar formulario</button>
                    </div>
                </form>
            </div>

            <div class="dash-card catalog-list-shell">
                <div class="quote-section-head">
                    <div>
                        <h2 class="section-title">Servicios guardados</h2>
                        <p class="muted">Edita, usa o elimina servicios sin salir del catálogo.</p>
                    </div>
                    <span class="dash-tag"><?= $catalogCount ?> servicio<?= $catalogCount === 1 ? '' : 's' ?></span>
                </div>
                <?php if (empty($servicios)): ?>
                    <p class="muted">Aún no tienes servicios cargados. Crea el primero arriba y quedará disponible para cotizar.</p>
                <?php else: ?>
                    <div class="catalog-card-grid">
                        <?php foreach ($servicios as $srv): ?>
                            <article class="quote-item-card catalog-item-card">
                                <div class="quote-item-top">
                                    <div>
                                        <strong><?= htmlspecialchars((string)($srv['nombre'] ?? 'Servicio')) ?></strong>
                                        <div class="muted"><?= htmlspecialchars((string)($srv['materia'] ?? 'Sin materia')) ?> · <?= htmlspecialchars((string)($srv['plazo_estimado'] ?? 'Plazo a definir')) ?></div>
                                    </div>
                                    <span class="dash-tag <?= !empty($srv['activo']) ? 'status-ganado' : 'status-cancelado' ?>"><?= !empty($srv['activo']) ? 'Activo' : 'Inactivo' ?></span>
                                </div>
                                <div class="quote-amounts-inline quote-amounts-grid">
                                    <span>Honorarios <strong><?= formatClpAmount($srv['precio_base'] ?? 0) ?></strong></span>
                                    <span>Gastos <strong><?= formatClpAmount($srv['gastos_base'] ?? 0) ?></strong></span>
                                </div>
                                <?php if (!empty($srv['detalle'])): ?>
                                    <p class="quote-body quote-body-box"><?= nl2br(htmlspecialchars((string)$srv['detalle'])) ?></p>
                                <?php endif; ?>
                                <div class="quote-actions-grid">
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        onclick="dashboardEditService(this)"
                                        data-service="<?= $jsonAttr([
                                            'id' => (int)($srv['id'] ?? 0),
                                            'nombre' => (string)($srv['nombre'] ?? ''),
                                            'materia' => (string)($srv['materia'] ?? ''),
                                            'detalle' => (string)($srv['detalle'] ?? ''),
                                            'plazo_estimado' => (string)($srv['plazo_estimado'] ?? ''),
                                            'precio_base' => (string)($srv['precio_base'] ?? '0'),
                                            'gastos_base' => (string)($srv['gastos_base'] ?? '0'),
                                            'activo' => !empty($srv['activo']) ? 1 : 0,
                                        ]) ?>">Editar</button>
                                    <button
                                        type="button"
                                        class="btn btn-ghost"
                                        onclick="dashboardUseServiceForQuote(this)"
                                        data-service="<?= $jsonAttr([
                                            'id' => (int)($srv['id'] ?? 0),
                                            'nombre' => (string)($srv['nombre'] ?? ''),
                                            'materia' => (string)($srv['materia'] ?? ''),
                                            'detalle' => (string)($srv['detalle'] ?? ''),
                                            'plazo_estimado' => (string)($srv['plazo_estimado'] ?? ''),
                                            'precio_base' => (string)($srv['precio_base'] ?? '0'),
                                            'gastos_base' => (string)($srv['gastos_base'] ?? '0'),
                                        ]) ?>">Usar</button>
                                    <form method="POST" action="/dashboard/servicios/eliminar" onsubmit="return confirm('¿Eliminar este servicio del catálogo?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                                        <input type="hidden" name="service_id" value="<?= (int)($srv['id'] ?? 0) ?>">
                                        <button class="btn btn-ghost" type="submit">Eliminar</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
