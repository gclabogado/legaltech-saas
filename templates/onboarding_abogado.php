<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil Profesional | Tu Estudio Juridico</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app-shell.css?v=202602231">
    <link rel="stylesheet" href="/onboarding-abogado-page.css?v=2026022601">
    <script src="/app-shell.js?v=202602231" defer></script>
</head>
<?php $lawyerThemeEmail = strtolower(trim((string)($_SESSION['email'] ?? ''))); ?>
<body class="theme-black<?= ($lawyerThemeEmail === 'gmcalderonlewin@gmail.com') ? ' theme-admin' : ' theme-olive' ?>">
    <?php
        $foto = !empty($user['google_picture']) ? $user['google_picture'] : "https://ui-avatars.com/api/?name=".urlencode($user['nombre']);
        $universidadesChile = $universidades_chile ?? [];
        $materiasTaxonomia = $materias_taxonomia ?? [];
        $regionesChile = $regiones_chile ?? [];
        $comunasSugeridas = $comunas_sugeridas ?? [];
        $comunasCatalogo = $comunas_catalogo ?? [];
        $submateriasActuales = [];
        if (!empty($user['submaterias'])) {
            $tmpSub = json_decode((string)$user['submaterias'], true);
            if (is_array($tmpSub)) {
                $submateriasActuales = array_values(array_filter(array_map('strval', $tmpSub)));
            }
        }
        $regionActual = trim((string)($user['regiones_servicio'] ?? ''));
        $normalizar = function ($text) {
            $text = trim((string)$text);
            if ($text === '') {
                return '';
            }
            if (function_exists('iconv')) {
                $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
                if ($converted !== false) {
                    $text = $converted;
                }
            }
            $text = strtolower($text);
            $text = preg_replace('/[^a-z0-9]+/', ' ', $text);
            return trim((string)$text);
        };
        $regionActualKey = $normalizar($regionActual);
        $materiasTaxonomiaJson = json_encode($materiasTaxonomia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $submateriasActualesJson = json_encode($submateriasActuales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $submateriasSecundariasActuales = [];
        if (!empty($user['submaterias_secundarias'])) {
            $tmpSubSec = json_decode((string)$user['submaterias_secundarias'], true);
            if (is_array($tmpSubSec)) {
                $submateriasSecundariasActuales = array_values(array_filter(array_map('strval', $tmpSubSec)));
            }
        }
        $submateriasSecundariasActualesJson = json_encode($submateriasSecundariasActuales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $ciudadesPlazaActuales = array_values(array_filter(array_map('trim', explode(',', (string)($user['ciudades_plaza'] ?? '')))));
        $ciudadesPlazaActualesJson = json_encode($ciudadesPlazaActuales, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $faqPersonalizadasActuales = [];
        if (!empty($user['faq_personalizadas_json'])) {
            $tmpFaq = json_decode((string)$user['faq_personalizadas_json'], true);
            if (is_array($tmpFaq)) {
                foreach ($tmpFaq as $it) {
                    if (!is_array($it)) continue;
                    $q = trim((string)($it['q'] ?? ''));
                    $a = trim((string)($it['a'] ?? ''));
                    if ($q !== '' && $a !== '') {
                        $faqPersonalizadasActuales[] = ['q' => $q, 'a' => $a];
                    }
                }
            }
        }
        for ($i = count($faqPersonalizadasActuales); $i < 4; $i++) {
            $faqPersonalizadasActuales[] = ['q' => '', 'a' => ''];
        }
        $regionComunasMap = [];
        if (is_array($comunasCatalogo)) {
            foreach ($comunasCatalogo as $row) {
                if (!is_array($row)) continue;
                $reg = trim((string)($row['region'] ?? ''));
                $com = trim((string)($row['comuna'] ?? ''));
                if ($reg === '' || $com === '') continue;
                if (!isset($regionComunasMap[$reg])) {
                    $regionComunasMap[$reg] = [];
                }
                if (!in_array($com, $regionComunasMap[$reg], true)) {
                    $regionComunasMap[$reg][] = $com;
                }
            }
        }
        foreach ($regionComunasMap as $r => $coms) {
            sort($coms, SORT_NATURAL | SORT_FLAG_CASE);
            $regionComunasMap[$r] = array_values($coms);
        }
        $regionComunasMapJson = json_encode($regionComunasMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    ?>
    <nav class="nav">
        <div class="nav-inner">
            <a class="brand" href="/explorar">Tu Estudio Juridico</a>
            <div class="nav-links">
                <a href="/explorar">Explorar</a>
                <a href="/panel">Mi panel</a>
                <a class="active" href="/completar-datos?modo=abogado">Perfil abogado</a>
            </div>
            <div class="nav-actions">
                <a class="btn btn-ghost" href="/panel">Mi panel</a>
                <a class="btn btn-primary" href="/logout">Salir</a>
            </div>
        </div>
    </nav>

    <main class="wrap">
        <?php if (!empty($mensaje)): ?>
            <div class="flash-msg <?= htmlspecialchars((string)($tipo_mensaje ?? 'info')) ?>" role="status" aria-live="polite">
                <?= htmlspecialchars((string)$mensaje) ?>
            </div>
        <?php endif; ?>
        <section class="hero">
            <div class="split" style="align-items: center;">
                <div style="display:flex; gap:14px; align-items:center;">
                    <img src="<?= htmlspecialchars($foto) ?>" class="avatar" alt="Foto de perfil">
                    <div>
                        <div class="badge">Perfil profesional</div>
                        <h1>Hola, <?= htmlspecialchars($user['nombre']) ?>.</h1>
                        <p>Completa tu ficha para aparecer en la galeria.</p>
                    </div>
                </div>
                <div>
                    <div class="hint">Progreso de tu perfil</div>
                    <div class="progress"><span id="progressBar"></span></div>
                    <div class="hint" id="progressText" style="margin-top:6px;"></div>
                </div>
            </div>
            <?php if (!empty($lawyer_verification_enabled)): ?>
                <?php
                    $estadoVerif = trim((string)($user['estado_verificacion_abogado'] ?? 'pendiente'));
                    $estadoLabel = [
                        'pendiente' => 'Pendiente verificación PJUD',
                        'verificado' => 'Abogado verificado',
                        'rechazado' => 'Verificación rechazada / falta info'
                    ][$estadoVerif] ?? ('Estado: ' . $estadoVerif);
                ?>
                <div class="hint" style="margin-top:8px;">
                    Estado de habilitación: <strong><?= htmlspecialchars($estadoLabel) ?></strong>
                    <?php if (!empty($user['abogado_verificado'])): ?> · Visible en galería<?php else: ?> · Aún no visible en galería<?php endif; ?>
                </div>
            <?php endif; ?>
            <?php
                $perfilPct = (int)($perfil_completion_pct ?? 0);
                $perfilChecklist = is_array($perfil_completion_checklist ?? null) ? $perfil_completion_checklist : [];
                $faltantesMap = [
                    'whatsapp' => 'WhatsApp profesional',
                    'materia' => 'Materia principal',
                    'submaterias' => 'Submaterias o materia secundaria',
                    'universidad' => 'Universidad (alma mater)',
                    'experiencia' => 'Año de titulación / experiencia',
                    'cobertura' => 'Región/comunas o Todo Chile',
                    'sexo' => 'Sexo (visible en perfil y filtro)',
                    'links' => 'Al menos un link (IG / TikTok / Web / Facebook / LinkedIn)',
                ];
                $faltantesPerfil = [];
                foreach ($faltantesMap as $k => $label) {
                    if (empty($perfilChecklist[$k])) $faltantesPerfil[] = $label;
                }
            ?>
            <div class="card" style="margin-top:10px; box-shadow:none; border-style:dashed; border-color: rgba(255,255,255,.18); background: rgba(255,255,255,.03);">
                <h2 style="margin-bottom:6px;">Estado de publicación del perfil</h2>
                <div class="hint" style="margin-bottom:8px;">
                    Tu perfil profesional está <strong><?= $perfilPct >= 80 ? 'listo para mostrarse' : 'oculto hasta completar 80%' ?></strong>.
                    Progreso backend actual: <strong><?= $perfilPct ?>%</strong>.
                </div>
                <?php if ($perfilPct < 80): ?>
                    <div class="hint" style="margin-bottom:6px; color:#fbd38d;">Checklist para aparecer en la galeria (completa lo pendiente):</div>
                    <div class="chips">
                        <?php foreach ($faltantesPerfil as $f): ?>
                            <span class="chip-btn" style="cursor:default; border-color: rgba(251,211,141,.25); background: rgba(251,211,141,.08); color:#fde68a;">Pendiente: <?= htmlspecialchars($f) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="chips"><span class="chip-btn" style="cursor:default; border-color: rgba(63,208,168,.25); background: rgba(63,208,168,.08); color:#baf7e8;">✅ Cumples el mínimo para aparecer en el listado</span></div>
                <?php endif; ?>
            </div>
        </section>

        <form id="lawyerWizardForm" action="/guardar-abogado" method="POST" class="grid" style="margin-top: 18px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
            <div class="wizard-rail">
                <div class="wizard-top">
                    <div class="wizard-steps" id="wizardSteps">
                        <div class="wizard-step-pill active" data-step-indicator="1"><div class="n">Paso 1</div><div class="t">Identidad y especialidad</div></div>
                        <div class="wizard-step-pill" data-step-indicator="2"><div class="n">Paso 2</div><div class="t">Perfil público</div></div>
                        <div class="wizard-step-pill" data-step-indicator="3"><div class="n">Paso 3</div><div class="t">Previsualización</div></div>
                        <div class="wizard-step-pill" data-step-indicator="4"><div class="n">Paso 4</div><div class="t">Publicación</div></div>
                    </div>
                    <div class="wizard-step-legend" id="wizardLegend">Paso 1 de 4 · Completa tus datos profesionales.</div>
                </div>
            </div>
            <section class="wizard-step is-active" data-step="1">
            <div class="card">
                <h2>Datos esenciales</h2>
                <div class="grid" style="grid-template-columns: 1fr;">
                    <div>
                        <label class="hint">WhatsApp (sin +56)</label>
                        <div class="split" style="grid-template-columns: 90px 1fr;">
                            <input class="input" value="+56" readonly>
                            <input type="tel" name="whatsapp" value="<?= htmlspecialchars($user['whatsapp'] ?? '') ?>" required placeholder="912345678" class="input">
                        </div>
                    </div>

                    <div>
                        <label class="hint">Especialidad principal</label>
                        <select name="especialidad" required>
                            <option value="" disabled <?= empty($user['especialidad']) ? 'selected' : '' ?>>Selecciona una...</option>
                            <?php
                            $legacyOcultas = ['Civil','Familia','Laboral','Penal','Comercial','Tributario','Consumidor','Inmobiliario','Otros'];
                            $areas = array_values(array_filter(array_keys($materiasTaxonomia ?: ['Derecho Familiar' => []]), fn($k) => !in_array($k, $legacyOcultas, true)));
                            $currentMateria = (string)($user['especialidad'] ?? '');
                            if ($currentMateria !== '' && !in_array($currentMateria, $areas, true)) { $areas[] = $currentMateria; }
                            foreach($areas as $area):
                            ?>
                                <option value="<?= $area ?>" <?= ($user['especialidad'] ?? '') == $area ? 'selected' : '' ?>><?= $area ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="chips" id="materiaChips">
                            <?php foreach($areas as $area): ?>
                                <button type="button" class="chip-btn <?= (($user['especialidad'] ?? '') == $area) ? 'active' : '' ?>" data-value="<?= htmlspecialchars($area) ?>"><?= htmlspecialchars($area) ?></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="hint" style="margin-top:6px;">Sugerencia de perfil: completa hasta 2 materias (cuando activemos materia secundaria) y hasta 3 submaterias por materia.</div>
                    </div>

                    <div>
                        <label class="hint">Submaterias (hasta 3) · recomendado marcar las más relevantes</label>
                        <input type="hidden" name="submaterias_json" id="submateriasJson" value="<?= htmlspecialchars((string)$submateriasActualesJson) ?>">
                        <div id="submateriasWrap" class="chips" style="min-height:44px;"></div>
                        <div class="hint" id="submateriasHint" style="margin-top:6px;">Selecciona una materia para ver submaterias.</div>
                        <div id="submateriasSeleccionadas" class="chips" style="margin-top:8px;"></div>
                    </div>

                    <div>
                        <label class="hint">Materia secundaria (opcional) · recomendado completar hasta 2 materias en total</label>
                        <select name="materia_secundaria" id="materiaSecundaria" class="input">
                            <option value="">Sin materia secundaria</option>
                            <?php foreach($areas as $area): ?>
                                <option value="<?= $area ?>" <?= (($user['materia_secundaria'] ?? '') == $area) ? 'selected' : '' ?>><?= $area ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="hint">Submaterias materia secundaria (hasta 3)</label>
                        <input type="hidden" name="submaterias_secundarias_json" id="submateriasSecundariasJson" value="<?= htmlspecialchars((string)$submateriasSecundariasActualesJson) ?>">
                        <div id="submateriasSecundariasWrap" class="chips" style="min-height:44px;"></div>
                        <div class="hint" id="submateriasSecundariasHint" style="margin-top:6px;">Opcional. Selecciona materia secundaria para ver submaterias.</div>
                        <div id="submateriasSecundariasSeleccionadas" class="chips" style="margin-top:8px;"></div>
                    </div>

                    <div>
                        <label class="hint">Alma mater / Universidad</label>
                        <input type="text" name="universidad" list="universidades-cl" value="<?= htmlspecialchars($user['universidad'] ?? '') ?>" placeholder="Selecciona o escribe tu universidad" class="input">
                        <datalist id="universidades-cl">
                            <?php foreach ($universidadesChile as $uni): ?>
                                <option value="<?= htmlspecialchars($uni) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="split">
                        <div>
                            <label class="hint">Sexo (visible en perfil y filtro)</label>
                            <select name="sexo" class="input" required>
                                <option value="" disabled <?= empty($user['sexo']) ? 'selected' : '' ?>>Selecciona...</option>
                                <?php $sexoOpts = ['mujer' => 'Mujer', 'hombre' => 'Hombre', 'no_binario' => 'No binario', 'prefiero_no_decir' => 'Prefiero no decir']; ?>
                                <?php foreach ($sexoOpts as $sxVal => $sxLabel): ?>
                                    <option value="<?= $sxVal ?>" <?= (($user['sexo'] ?? '') === $sxVal) ? 'selected' : '' ?>><?= htmlspecialchars($sxLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="hint">Fecha de titulación (año)</label>
                            <select name="anio_titulacion" id="anioTitulacion" class="input">
                                <option value="">Selecciona año...</option>
                                <?php
                                $anioDetectado = !empty($user['anio_titulacion']) ? (int)$user['anio_titulacion'] : null;
                                if ($anioDetectado === null && !empty($user['experiencia']) && preg_match('/(\d+)/', (string)$user['experiencia'], $mExp)) {
                                    $anioDetectado = (int)date('Y') - (int)$mExp[1];
                                }
                                for ($y = (int)date('Y'); $y >= 1950; $y--): ?>
                                    <option value="<?= $y ?>" <?= ($anioDetectado === $y) ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="hint">Años de experiencia (auto)</label>
                            <input type="text" id="experienciaAuto" name="experiencia" value="<?= htmlspecialchars($user['experiencia'] ?? '') ?>" placeholder="Se calcula automáticamente" class="input" readonly>
                        </div>
                    </div>

                    <?php if (!empty($lawyer_verification_enabled)): ?>
                    <div>
                        <label class="hint">RUT abogado (para verificación PJUD)</label>
                        <input type="text" name="rut_abogado" value="<?= htmlspecialchars($user['rut_abogado'] ?? '') ?>" placeholder="12.345.678-9" class="input">
                        <div class="hint" style="margin-top:6px;">Se usa para verificar habilitación profesional en PJUD. Búsqueda por RUT es la más confiable.</div>
                    </div>
                    <?php endif; ?>

                    <div class="card" style="box-shadow:none; border-style:dashed;">
                        <h2 style="margin-bottom:8px;">Formación adicional (Master o diplomado)</h2>
                        <div class="split">
                            <div>
                                <label class="hint" style="display:flex; gap:8px; align-items:center;">
                                    <input type="checkbox" id="tienePostitulo" name="tiene_postitulo" value="1" <?= !empty($user['tiene_postitulo']) ? 'checked' : '' ?>>
                                    Tiene master
                                </label>
                                <input type="text" id="nombrePostitulo" name="nombre_postitulo" value="<?= htmlspecialchars($user['nombre_postitulo'] ?? '') ?>" placeholder="Nombre del master" class="input" style="margin-top:8px; <?= empty($user['tiene_postitulo']) ? 'display:none;' : '' ?>">
                                <input type="text" id="universidadPostitulo" name="universidad_postitulo" value="<?= htmlspecialchars($user['universidad_postitulo'] ?? '') ?>" placeholder="Universidad del master (opcional)" class="input" style="margin-top:8px; <?= empty($user['tiene_postitulo']) ? 'display:none;' : '' ?>">
                            </div>
                            <div>
                                <label class="hint" style="display:flex; gap:8px; align-items:center;">
                                    <input type="checkbox" id="tieneDiplomado" name="tiene_diplomado" value="1" <?= !empty($user['tiene_diplomado']) ? 'checked' : '' ?>>
                                    Tiene diplomado
                                </label>
                                <input type="text" id="nombreDiplomado" name="nombre_diplomado" value="<?= htmlspecialchars($user['nombre_diplomado'] ?? '') ?>" placeholder="Nombre del diplomado" class="input" style="margin-top:8px; <?= empty($user['tiene_diplomado']) ? 'display:none;' : '' ?>">
                                <input type="text" id="universidadDiplomado" name="universidad_diplomado" value="<?= htmlspecialchars($user['universidad_diplomado'] ?? '') ?>" placeholder="Universidad del diplomado (opcional)" class="input" style="margin-top:8px; <?= empty($user['tiene_diplomado']) ? 'display:none;' : '' ?>">
                            </div>
                        </div>
                    </div>

                    <div class="card" style="box-shadow:none; border-style:dashed;">
                        <h2 style="margin-bottom:8px;">Cobertura de servicio</h2>
                        <label class="hint" style="display:flex; gap:8px; align-items:center;">
                            <input type="checkbox" name="cobertura_nacional" value="1" <?= !empty($user['cobertura_nacional']) ? 'checked' : '' ?>>
                            Atiendo todo Chile
                        </label>
                        <label class="hint" style="display:flex; gap:8px; align-items:center; margin-top:8px;">
                            <input type="checkbox" name="entrevista_presencial" value="1" <?= !empty($user['entrevista_presencial']) ? 'checked' : '' ?>>
                            Doy entrevistas presenciales (destacar en perfil)
                        </label>
                        <label class="hint" style="display:flex; gap:8px; align-items:center; margin-top:8px;">
                            <input type="checkbox" name="audiencias_para_abogados_plaza" value="1" <?= !empty($user['audiencias_para_abogados_plaza']) ? 'checked' : '' ?>>
                            Estoy disponible para tomar audiencias para otros abogados en mi ciudad/plaza (solo visible para abogados)
                        </label>

                        <div class="split" style="margin-top:10px;">
                            <div>
                                <label class="hint">Región principal</label>
                                <select name="region_servicio" class="input">
                                    <option value="">Selecciona region...</option>
                                    <?php foreach($regionesChile as $region): ?>
                                        <?php $regionKey = $normalizar($region); ?>
                                        <option value="<?= htmlspecialchars($region) ?>" <?= ($regionActualKey !== '' && $regionActualKey === $regionKey) ? 'selected' : '' ?>><?= htmlspecialchars($region) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="hint">Comunas de atencion</label>
                                <input type="text" name="comunas_servicio" list="comunas-cl" value="<?= htmlspecialchars($user['comunas_servicio'] ?? '') ?>" placeholder="Ej: Providencia, Ñuñoa, Santiago" class="input">
                                <datalist id="comunas-cl">
                                    <?php foreach ($comunasSugeridas as $comuna): ?>
                                        <option value="<?= htmlspecialchars($comuna) ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div>
                                <label class="hint">Ciudades/comunas donde eres “abogado de la plaza”</label>
                                <input type="hidden" name="ciudades_plaza" id="ciudadesPlazaInput" value="<?= htmlspecialchars((string)($user['ciudades_plaza'] ?? '')) ?>">
                                <div id="ciudadesPlazaWrap" class="chip-grid"></div>
                                <div class="hint" id="ciudadesPlazaHint" style="margin-top:6px;">Selecciona tu región principal para marcar ciudades/comunas de mayor presencia.</div>
                                <div id="ciudadesPlazaSeleccionadas" class="chip-selected-list"></div>
                                <div class="split" style="grid-template-columns: 1fr auto; margin-top:8px;">
                                    <input type="text" id="ciudadPlazaManual" list="comunas-cl" placeholder="Agregar comuna manualmente" class="input">
                                    <button type="button" class="btn btn-ghost" id="agregarCiudadPlazaBtn">Agregar</button>
                                </div>
                                <div class="hint" style="margin-top:6px;">Marca hasta 6 ciudades/comunas dentro de tu región. Si falta alguna, agrégala manualmente.</div>
                            </div>
                        </div>

                        <input id="servicioLat" type="hidden" name="servicio_lat" value="<?= htmlspecialchars($user['servicio_lat'] ?? '') ?>">
                        <input id="servicioLng" type="hidden" name="servicio_lng" value="<?= htmlspecialchars($user['servicio_lng'] ?? '') ?>">
                        <div style="margin-top:10px;">
                            <button type="button" class="btn btn-ghost" onclick="usarUbicacionAbogado()">Usar mi ubicacion actual</button>
                        </div>
                        <div class="hint" style="margin-top:8px;">Tip: Usa tu ubicación para sugerir ciudades cercanas. No mostramos latitud/longitud; solo usamos esa referencia para ayudarte a completar cobertura local.</div>
                    </div>
                </div>
            </div>

            </section>
            <section class="wizard-step" data-step="2">
            <div class="card">
                <h2>Perfil publico</h2>
                <div class="grid" style="grid-template-columns: 1fr;">
                    <div>
                        <label class="hint">Bio corta (opcional)</label>
                        <textarea id="biografiaInput" name="biografia" maxlength="300" placeholder="Opcional. Cuéntale al cliente brevemente qué tipo de casos atiendes, cómo trabajas y en qué puedes ayudar (máx. 300 caracteres)."><?= htmlspecialchars($user['biografia'] ?? '') ?></textarea>
                        <div class="hint" id="biografiaCount" style="margin-top:6px;">Opcional · máximo 300 caracteres.</div>
                    </div>

                    <div class="split">
                        <div>
                            <label class="hint">Instagram</label>
                            <input type="text" name="instagram" value="<?= htmlspecialchars($user['instagram'] ?? '') ?>" placeholder="@usuario" class="input">
                        </div>
                        <div>
                            <label class="hint">TikTok</label>
                            <input type="text" name="tiktok" value="<?= htmlspecialchars($user['tiktok'] ?? '') ?>" placeholder="@usuario" class="input">
                        </div>
                    </div>

                    <?php
                        $colorMarcaActual = trim((string)($user['color_marca'] ?? 'azul'));
                        $coloresMarca = [
                            'azul' => ['Azul', '#5c8ed6'],
                            'verde' => ['Verde', '#3fd0a8'],
                            'dorado' => ['Dorado', '#d8c2a5'],
                            'rosa' => ['Rosa', '#ef6da8'],
                            'vino' => ['Vino', '#b45b7a'],
                            'grafito' => ['Grafito', '#7f8ea3'],
                        ];
                        if (!isset($coloresMarca[$colorMarcaActual])) $colorMarcaActual = 'azul';
                    ?>
                    <div class="card" style="box-shadow:none; border-style:dashed;">
                        <h2 style="margin-bottom:8px;">Color de marca del perfil</h2>
                        <label class="hint" style="margin-bottom:8px;">Elige un color discreto para resaltar tu foto y acentos del perfil.</label>
                        <input type="hidden" name="color_marca" id="colorMarcaInput" value="<?= htmlspecialchars($colorMarcaActual) ?>">
                        <div class="chips" id="colorMarcaWrap">
                            <?php foreach ($coloresMarca as $k => [$label, $hex]): ?>
                                <button type="button" class="chip-btn color-chip-btn <?= $colorMarcaActual === $k ? 'active' : '' ?>" data-color="<?= htmlspecialchars($k) ?>" style="display:inline-flex; align-items:center; gap:8px;">
                                    <span style="width:12px; height:12px; border-radius:999px; background:<?= htmlspecialchars($hex) ?>; box-shadow:0 0 0 1px rgba(255,255,255,.22) inset;"></span>
                                    <?= htmlspecialchars($label) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                        <div class="hint" style="margin-top:8px;">Se verá en el borde de tu foto y detalles visuales del perfil.</div>
                    </div>

                    <div>
                        <label class="hint">Sitio web</label>
                        <input type="text" name="web" value="<?= htmlspecialchars($user['web'] ?? '') ?>" placeholder="tuweb.cl" class="input">
                    </div>
                    <div class="split">
                        <div>
                            <label class="hint">Facebook</label>
                            <input type="text" name="facebook" value="<?= htmlspecialchars($user['facebook'] ?? '') ?>" placeholder="facebook.com/tu-perfil" class="input">
                        </div>
                        <div>
                            <label class="hint">LinkedIn</label>
                            <input type="text" name="linkedin" value="<?= htmlspecialchars($user['linkedin'] ?? '') ?>" placeholder="linkedin.com/in/tu-perfil" class="input">
                        </div>
                    </div>

                    <?php
                        $mediosPagoActuales = [];
                        if (!empty($user['medios_pago_json'])) {
                            $tmpMedios = json_decode((string)$user['medios_pago_json'], true);
                            if (is_array($tmpMedios)) $mediosPagoActuales = array_values(array_filter(array_map('strval', $tmpMedios)));
                        }
                        $mediosCatalogo = [
                            'efectivo' => 'Efectivo',
                            'transferencia' => 'Transferencia',
                            'tarjeta_credito' => 'Tarjeta de crédito',
                            'tarjeta_debito' => 'Tarjeta de débito',
                            'crypto' => 'Crypto'
                        ];
                    ?>
                    <div class="card" style="box-shadow:none; border-style:dashed;">
                        <h2 style="margin-bottom:8px;">Medios de pago</h2>
                        <label class="hint" style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                            <input type="checkbox" name="exhibir_medios_pago" value="1" <?= !empty($user['exhibir_medios_pago']) ? 'checked' : '' ?>>
                            Mostrar medios de pago en mi perfil público
                        </label>
                        <div class="chips">
                            <?php foreach ($mediosCatalogo as $medioKey => $medioLabel): ?>
                                <label class="chip-btn" style="cursor:pointer;">
                                    <input type="checkbox" name="medios_pago[]" value="<?= htmlspecialchars($medioKey) ?>" <?= in_array($medioKey, $mediosPagoActuales, true) ? 'checked' : '' ?> style="margin-right:6px;">
                                    <?= htmlspecialchars($medioLabel) ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="hint" style="margin-top:8px;">Opcional. Puedes elegir cuáles mostrar a los clientes.</div>
                    </div>

                    <div class="card" style="box-shadow:none; border-style:dashed;">
                        <h2 style="margin-bottom:8px;">Preguntas frecuentes (editables)</h2>
                        <div class="hint" style="margin-bottom:8px;">Opcional. Si dejas este bloque vacío, el perfil mostrará preguntas frecuentes genéricas.</div>
                        <div class="grid" style="grid-template-columns:1fr; gap:12px;">
                            <?php foreach (array_slice($faqPersonalizadasActuales, 0, 4) as $idx => $faqRow): ?>
                                <div class="card" style="box-shadow:none; border-style:dashed; padding:12px;">
                                    <div class="hint" style="margin-bottom:6px;">FAQ <?= $idx + 1 ?></div>
                                    <label class="hint">Pregunta <span class="faq-counter faq-q-counter" data-min="10" data-max="140"></span></label>
                                    <input type="text" class="input faq-q-input" name="faq_q[]" value="<?= htmlspecialchars((string)$faqRow['q']) ?>" maxlength="140" placeholder="Ej: ¿Atiendes urgencias fuera de horario?">
                                    <label class="hint" style="margin-top:8px;">Respuesta <span class="faq-counter faq-a-counter" data-min="20" data-max="400"></span></label>
                                    <textarea class="input faq-a-input" name="faq_a[]" rows="3" maxlength="400" placeholder="Respuesta breve, clara y útil para el cliente."><?= htmlspecialchars((string)$faqRow['a']) ?></textarea>
                                    <div class="hint faq-inline-status">Opcional. Si completas una, completa ambas.</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            </section>
            <section class="wizard-step" data-step="3">
            <div class="card">
                <h2>Previsualización del perfil público</h2>
                <div class="hint" style="margin-bottom:8px;">Vista rápida de cómo verán tu perfil (resumen, servicios y FAQ). Se actualiza mientras editas.</div>
                <div class="grid" style="grid-template-columns:1fr; gap:12px;">
                    <div class="card" style="box-shadow:none; border-style:dashed; padding:12px;">
                        <strong style="display:block; margin-bottom:6px; font-size:13px;">Resumen del perfil</strong>
                        <div class="hint" id="previewResumenPerfil">Abogado/a, formación, experiencia y especialidad aparecerán aquí.</div>
                    </div>
                    <div class="card" style="box-shadow:none; border-style:dashed; padding:12px;">
                        <strong style="display:block; margin-bottom:6px; font-size:13px;">Servicios legales más consultados</strong>
                        <div id="previewServiciosPerfil" class="chips"></div>
                        <div class="hint" style="margin-top:6px;">Se generan a partir de las submaterias que selecciones.</div>
                    </div>
                    <div class="card" style="box-shadow:none; border-style:dashed; padding:12px;">
                        <strong style="display:block; margin-bottom:6px; font-size:13px;">Preguntas frecuentes antes de contratar</strong>
                        <div id="previewFaqPerfil" style="display:grid; gap:8px;"></div>
                    </div>
                </div>
            </div>

            <div class="card card-linkbox">
                <h2>Tu enlace publico</h2>
                <p class="hint">Tu ficha pública mostrará tu perfil profesional y tus datos se desbloquean cuando el cliente envía el formulario de contacto.</p>
                <div class="split" style="grid-template-columns: 120px 1fr;">
                    <input class="input" value="example.com/" readonly>
                    <input type="text" name="slug" value="<?= htmlspecialchars($user['slug'] ?? '') ?>" required class="input">
                </div>
            </div>

            </section>
            <section class="wizard-step" data-step="4">
                <div class="wizard-review-hint">
                    Revisión final. Guarda tu perfil cuando esté listo. Si llegas al <strong>80%</strong>, tu perfil podrá publicarse (verificación PJUD pendiente hasta revisión manual).
                </div>
                <div class="card">
                    <button type="submit" class="cta cta-primary">Guardar perfil profesional</button>
                    <div class="hint" style="margin-top: 8px; text-align: center;">Un solo envío final · mantiene el cálculo de 80% y el flujo actual.</div>
                </div>
            </section>
            <div class="wizard-nav">
                <button type="button" class="btn btn-ghost" id="wizardPrevBtn" disabled>Anterior</button>
                <div class="wizard-status" id="wizardStatus">Paso 1 / 4</div>
                <div class="wizard-nav-spacer"></div>
                <button type="button" class="btn btn-primary" id="wizardNextBtn">Siguiente</button>
            </div>
            <div class="wizard-inline-msg" id="wizardInlineMsg"></div>
        </form>
    </main>

    <script id="onboardingAbogadoConfig" type="application/json"><?= json_encode([
        'materiasTaxonomia' => $materiasTaxonomia,
        'regionComunasMap' => $regionComunasMap,
        'ciudadesPlazaActuales' => $ciudadesPlazaActuales
    ], JSON_UNESCAPED_UNICODE) ?: '{}' ?></script>
    <script src="/onboarding-abogado-page.js?v=2026022601"></script>
</body>
</html>
