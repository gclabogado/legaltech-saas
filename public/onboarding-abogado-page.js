const onboardingAbogadoConfigEl = document.getElementById('onboardingAbogadoConfig');
let onboardingAbogadoConfig = {};
try {
    onboardingAbogadoConfig = JSON.parse(onboardingAbogadoConfigEl?.textContent || '{}');
} catch (e) {
    onboardingAbogadoConfig = {};
}

const MATERIAS_TAXONOMIA = (onboardingAbogadoConfig && typeof onboardingAbogadoConfig.materiasTaxonomia === 'object' && onboardingAbogadoConfig.materiasTaxonomia)
    ? onboardingAbogadoConfig.materiasTaxonomia
    : {};
const REGION_COMUNAS_MAP = (onboardingAbogadoConfig && typeof onboardingAbogadoConfig.regionComunasMap === 'object' && onboardingAbogadoConfig.regionComunasMap)
    ? onboardingAbogadoConfig.regionComunasMap
    : {};
const CIUDADES_PLAZA_ACTUALES = Array.isArray(onboardingAbogadoConfig.ciudadesPlazaActuales)
    ? onboardingAbogadoConfig.ciudadesPlazaActuales
    : [];

let selectedSubmaterias = (() => {
    try {
        const parsed = JSON.parse(document.getElementById('submateriasJson')?.value || '[]');
        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch (e) { return []; }
})();
let selectedSubmateriasSecundarias = (() => {
    try {
        const parsed = JSON.parse(document.getElementById('submateriasSecundariasJson')?.value || '[]');
        return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch (e) { return []; }
})();
let selectedCiudadesPlaza = (() => {
    try {
        const parsed = CIUDADES_PLAZA_ACTUALES;
        return Array.isArray(parsed) ? parsed.map(String).map(s => s.trim()).filter(Boolean).slice(0, 6) : [];
    } catch (e) { return []; }
})();

        function renderSubmateriasSelector() {
            const select = document.querySelector('select[name="especialidad"]');
            const materia = (select?.value || '').trim();
            const wrap = document.getElementById('submateriasWrap');
            const hidden = document.getElementById('submateriasJson');
            const selectedWrap = document.getElementById('submateriasSeleccionadas');
            const hint = document.getElementById('submateriasHint');
            if (!wrap || !hidden || !selectedWrap || !hint) return;
            const disponibles = Array.isArray(MATERIAS_TAXONOMIA[materia]) ? MATERIAS_TAXONOMIA[materia] : [];
                            selectedSubmaterias = selectedSubmaterias.filter(s => disponibles.includes(s)).slice(0, 3);
            hidden.value = JSON.stringify(selectedSubmaterias);

            wrap.innerHTML = '';
            selectedWrap.innerHTML = '';
            if (!materia || disponibles.length === 0) {
                hint.textContent = 'Selecciona una materia para ver submaterias.';
                return;
            }
                            hint.textContent = 'Marca hasta 3 submaterias.';
            disponibles.forEach(label => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'chip-btn' + (selectedSubmaterias.includes(label) ? ' active' : '');
                btn.textContent = label;
                btn.addEventListener('click', () => {
                    const exists = selectedSubmaterias.includes(label);
                    if (exists) {
                        selectedSubmaterias = selectedSubmaterias.filter(s => s !== label);
                    } else {
                        if (selectedSubmaterias.length >= 3) {
                            alert('Puedes seleccionar hasta 3 submaterias.');
                            return;
                        }
                        selectedSubmaterias.push(label);
                    }
                    renderSubmateriasSelector();
                    calcProgress();
                });
                wrap.appendChild(btn);
            });
            selectedSubmaterias.forEach(label => {
                const chip = document.createElement('span');
                chip.className = 'chip-btn active';
                chip.style.cursor = 'default';
                chip.textContent = label;
                selectedWrap.appendChild(chip);
            });
        }

        function renderSubmateriasSecundariasSelector() {
            const select = document.getElementById('materiaSecundaria');
            const materia = (select?.value || '').trim();
            const wrap = document.getElementById('submateriasSecundariasWrap');
            const hidden = document.getElementById('submateriasSecundariasJson');
            const selectedWrap = document.getElementById('submateriasSecundariasSeleccionadas');
            const hint = document.getElementById('submateriasSecundariasHint');
            if (!wrap || !hidden || !selectedWrap || !hint) return;
            const disponibles = Array.isArray(MATERIAS_TAXONOMIA[materia]) ? MATERIAS_TAXONOMIA[materia] : [];
            selectedSubmateriasSecundarias = selectedSubmateriasSecundarias.filter(s => disponibles.includes(s)).slice(0, 3);
            hidden.value = JSON.stringify(selectedSubmateriasSecundarias);
            wrap.innerHTML = '';
            selectedWrap.innerHTML = '';
            if (!materia || disponibles.length === 0) {
                hint.textContent = 'Opcional. Selecciona materia secundaria para ver submaterias.';
                return;
            }
            hint.textContent = 'Marca hasta 3 submaterias para la materia secundaria.';
            disponibles.forEach(label => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'chip-btn' + (selectedSubmateriasSecundarias.includes(label) ? ' active' : '');
                btn.textContent = label;
                btn.addEventListener('click', () => {
                    const exists = selectedSubmateriasSecundarias.includes(label);
                    if (exists) {
                        selectedSubmateriasSecundarias = selectedSubmateriasSecundarias.filter(s => s !== label);
                    } else {
                        if (selectedSubmateriasSecundarias.length >= 3) {
                            alert('Puedes seleccionar hasta 3 submaterias.');
                            return;
                        }
                        selectedSubmateriasSecundarias.push(label);
                    }
                    renderSubmateriasSecundariasSelector();
                    calcProgress();
                });
                wrap.appendChild(btn);
            });
            selectedSubmateriasSecundarias.forEach(label => {
                const chip = document.createElement('span');
                chip.className = 'chip-btn active';
                chip.style.cursor = 'default';
                chip.textContent = label;
                selectedWrap.appendChild(chip);
            });
        }

        function calcProgress() {
            const fields = [
                document.querySelector('input[name="whatsapp"]').value.trim(),
                document.querySelector('select[name="especialidad"]').value.trim(),
                (selectedSubmaterias.length ? 'ok' : ''),
                document.querySelector('input[name="universidad"]').value.trim(),
                document.querySelector('input[name="slug"]').value.trim(),
                document.querySelector('select[name="sexo"]')?.value.trim() || '',
                document.getElementById('materiaSecundaria')?.value.trim() || '',
                (document.querySelector('input[name="cobertura_nacional"]')?.checked ? 'ok' : ''),
                document.querySelector('select[name="region_servicio"]').value.trim(),
                document.querySelector('input[name="comunas_servicio"]').value.trim(),
                document.getElementById('ciudadesPlazaInput')?.value.trim() || '',
                (document.querySelector('input[name="entrevista_presencial"]')?.checked ? 'ok' : '')
            ];
            const filled = fields.filter(v => v.length > 0).length;
            const percent = Math.round((filled / fields.length) * 100);
            const bar = document.getElementById('progressBar');
            const text = document.getElementById('progressText');
            bar.style.width = percent + '%';
            text.textContent = percent + '% completo';
        }

        let wizardStep = 1;
        const WIZARD_TOTAL = 4;
        const WIZARD_STEP_STORAGE_KEY = 'lawyers_onboarding_abogado_wizard_step_v1';
        const wizardLabels = {
            1: 'Paso 1 de 4 · Completa identidad, materias y cobertura.',
            2: 'Paso 2 de 4 · Configura tu perfil público, enlaces y FAQ.',
            3: 'Paso 3 de 4 · Revisa previsualización y tu enlace público.',
            4: 'Paso 4 de 4 · Revisión final y publicación.'
        };
        function getWizardStepIssues(step) {
            const get = (sel) => document.querySelector(sel);
            const value = (sel) => (get(sel)?.value || '').trim();
            const issues = [];
            if (step === 1) {
                if (!value('input[name="whatsapp"]')) issues.push('WhatsApp profesional');
                if (!value('select[name="especialidad"]')) issues.push('Materia principal');
                if (!Array.isArray(selectedSubmaterias) || selectedSubmaterias.length === 0) issues.push('1 submateria');
                if (!value('input[name="universidad"]')) issues.push('Universidad');
                if (!value('select[name="sexo"]')) issues.push('Sexo');
                const region = value('select[name="region_servicio"]');
                const nacional = !!get('input[name="cobertura_nacional"]')?.checked;
                if (!nacional && !region) issues.push('Región o Todo Chile');
                return issues;
            }
            if (step === 2) {
                const bio = value('#biografiaInput');
                if (bio.length > 300) issues.push('Bio corta (máx 300)');
                const faqRows = Array.from(document.querySelectorAll('.faq-q-input')).map((qInput, i) => ({
                    q: (qInput.value || '').trim(),
                    a: (document.querySelectorAll('.faq-a-input')[i]?.value || '').trim()
                }));
                for (const row of faqRows) {
                    if ((row.q && !row.a) || (!row.q && row.a)) { issues.push('FAQ incompleta'); break; }
                    if (row.q && (row.q.length < 10 || row.q.length > 140)) { issues.push('Pregunta FAQ'); break; }
                    if (row.a && (row.a.length < 20 || row.a.length > 400)) { issues.push('Respuesta FAQ'); break; }
                }
                return issues;
            }
            if (step === 3) {
                if (!value('input[name="slug"]')) issues.push('Enlace público');
                return issues;
            }
            return issues;
        }
        function refreshWizardStepUiHints() {
            const next = document.getElementById('wizardNextBtn');
            const inline = document.getElementById('wizardInlineMsg');
            const issues = getWizardStepIssues(wizardStep);
            if (next && wizardStep < WIZARD_TOTAL) {
                const base = wizardStep === (WIZARD_TOTAL - 1) ? 'Ir a revisión final' : 'Siguiente';
                next.textContent = issues.length ? `${base} · faltan ${issues.length}` : base;
            }
            if (!inline) return;
            inline.classList.remove('error', 'ok');
            if (wizardStep === WIZARD_TOTAL) {
                inline.textContent = 'Revisa el resumen final y guarda tu perfil. El backend recalcula el 80% al enviar.';
                return;
            }
            if (issues.length) {
                inline.classList.add('error');
                inline.textContent = `Falta completar: ${issues.slice(0,2).join(' · ')}${issues.length > 2 ? ` · +${issues.length-2}` : ''}`;
            } else {
                inline.classList.add('ok');
                inline.textContent = 'Paso completo. Puedes continuar.';
            }
        }
        function setWizardStep(step) {
            wizardStep = Math.max(1, Math.min(WIZARD_TOTAL, step));
            document.querySelectorAll('.wizard-step').forEach(section => {
                section.classList.toggle('is-active', Number(section.dataset.step) === wizardStep);
            });
            document.querySelectorAll('[data-step-indicator]').forEach(pill => {
                const n = Number(pill.getAttribute('data-step-indicator'));
                pill.classList.toggle('active', n === wizardStep);
                pill.classList.toggle('done', n < wizardStep);
            });
            const prev = document.getElementById('wizardPrevBtn');
            const next = document.getElementById('wizardNextBtn');
            const status = document.getElementById('wizardStatus');
            const legend = document.getElementById('wizardLegend');
            if (prev) prev.disabled = wizardStep === 1;
            if (next) {
                next.style.display = wizardStep === WIZARD_TOTAL ? 'none' : 'inline-flex';
                next.textContent = wizardStep === (WIZARD_TOTAL - 1) ? 'Ir a revisión final' : 'Siguiente';
            }
            if (status) status.textContent = `Paso ${wizardStep} / ${WIZARD_TOTAL}`;
            if (legend) legend.textContent = wizardLabels[wizardStep] || '';
            try { sessionStorage.setItem(WIZARD_STEP_STORAGE_KEY, String(wizardStep)); } catch (e) {}
            document.querySelector('.wizard-rail')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            refreshWizardStepUiHints();
        }
        function validateWizardStep(step) {
            const get = (sel) => document.querySelector(sel);
            const value = (sel) => (get(sel)?.value || '').trim();
            const focusAndFail = (sel, msg) => {
                if (sel) get(sel)?.focus();
                const inline = document.getElementById('wizardInlineMsg');
                if (inline) {
                    inline.classList.remove('ok');
                    inline.classList.add('error');
                    inline.textContent = msg;
                }
                alert(msg);
                return false;
            };
            const issues = getWizardStepIssues(step);
            if (step === 1) {
                if (!value('input[name="whatsapp"]')) return focusAndFail('input[name="whatsapp"]', 'Completa tu WhatsApp profesional.');
                if (!value('select[name="especialidad"]')) return focusAndFail('select[name="especialidad"]', 'Selecciona tu materia principal.');
                if (!Array.isArray(selectedSubmaterias) || selectedSubmaterias.length === 0) return focusAndFail(null, 'Selecciona al menos 1 submateria de tu materia principal.');
                if (!value('input[name="universidad"]')) return focusAndFail('input[name="universidad"]', 'Completa tu universidad.');
                if (!value('select[name="sexo"]')) return focusAndFail('select[name="sexo"]', 'Selecciona tu sexo.');
                const region = value('select[name="region_servicio"]');
                const nacional = !!get('input[name="cobertura_nacional"]')?.checked;
                if (!nacional && !region) return focusAndFail('select[name="region_servicio"]', 'Selecciona una región o marca "Atiendo todo Chile".');
                return true;
            }
            if (step === 2) {
                if (issues.includes('Bio corta (máx 300)')) return focusAndFail('#biografiaInput', 'La bio corta puede tener máximo 300 caracteres.');
                if (issues.includes('FAQ incompleta')) return focusAndFail(null, 'Si completas una FAQ, debes completar pregunta y respuesta.');
                if (issues.includes('Pregunta FAQ')) return focusAndFail(null, 'Las preguntas FAQ deben tener entre 10 y 140 caracteres.');
                if (issues.includes('Respuesta FAQ')) return focusAndFail(null, 'Las respuestas FAQ deben tener entre 20 y 400 caracteres.');
                return true;
            }
            if (step === 3) {
                if (issues.includes('Enlace público')) return focusAndFail('input[name="slug"]', 'Completa tu enlace público (slug) antes de continuar.');
                return true;
            }
            return true;
        }
        function setupLawyerWizard() {
            const prevBtn = document.getElementById('wizardPrevBtn');
            const nextBtn = document.getElementById('wizardNextBtn');
            const form = document.getElementById('lawyerWizardForm');
            prevBtn?.addEventListener('click', () => setWizardStep(wizardStep - 1));
            nextBtn?.addEventListener('click', () => {
                if (!validateWizardStep(wizardStep)) return;
                setWizardStep(wizardStep + 1);
            });
            form?.addEventListener('submit', () => {
                try { sessionStorage.removeItem(WIZARD_STEP_STORAGE_KEY); } catch (e) {}
            });
            let initialStep = 1;
            try {
                const saved = parseInt(sessionStorage.getItem(WIZARD_STEP_STORAGE_KEY) || '1', 10);
                if (!Number.isNaN(saved) && saved >= 1 && saved <= WIZARD_TOTAL) initialStep = saved;
            } catch (e) {}
            setWizardStep(initialStep);
            refreshWizardStepUiHints();
        }

        function usarUbicacionAbogado() {
            if (!navigator.geolocation) {
                alert('Tu navegador no permite geolocalizacion.');
                return;
            }
            navigator.geolocation.getCurrentPosition(function (position) {
                document.getElementById('servicioLat').value = position.coords.latitude.toFixed(7);
                document.getElementById('servicioLng').value = position.coords.longitude.toFixed(7);
                calcProgress();
            }, function () {
                alert('No pudimos obtener tu ubicacion.');
            }, { enableHighAccuracy: true, timeout: 10000 });
        }
        function calcExperienciaDesdeAnio() {
            const anio = parseInt(document.getElementById('anioTitulacion')?.value || '', 10);
            const exp = document.getElementById('experienciaAuto');
            if (!exp || !anio || anio < 1950) return;
            const years = Math.max(0, new Date().getFullYear() - anio);
            exp.value = years + ' años';
            calcProgress();
        }
        function firstOr(arr, fallback = '') {
            return Array.isArray(arr) && arr.length ? String(arr[0]) : fallback;
        }
        function renderPerfilPreview() {
            const nombre = (document.getElementById('displayName')?.textContent || '').trim() || 'tu nombre';
            const uni = (document.querySelector('input[name="universidad"]')?.value || '').trim() || 'tu universidad';
            const exp = (document.getElementById('experienciaAuto')?.value || '').trim() || 'tu experiencia';
            const mat1 = (document.querySelector('select[name="especialidad"]')?.value || '').trim() || 'tu materia principal';
            const sub1 = firstOr(selectedSubmaterias, '');
            const resumenEl = document.getElementById('previewResumenPerfil');
            if (resumenEl) {
                let txt = `Abogado ${nombre}, licenciado de la Universidad ${uni}, con más de ${exp} de experiencia se especializa en ${mat1}`;
                if (sub1) txt += `, específicamente en ${sub1}`;
                resumenEl.textContent = txt + '.';
            }

            const serviciosWrap = document.getElementById('previewServiciosPerfil');
            if (serviciosWrap) {
                serviciosWrap.innerHTML = '';
                const servicios = [...selectedSubmaterias, ...selectedSubmateriasSecundarias].filter(Boolean).slice(0, 6);
                (servicios.length ? servicios : ['Orientación inicial', 'Revisión de antecedentes', 'Representación y gestión']).forEach(label => {
                    const chip = document.createElement('span');
                    chip.className = 'chip-btn active';
                    chip.style.cursor = 'default';
                    chip.textContent = label;
                    serviciosWrap.appendChild(chip);
                });
            }

            const faqWrap = document.getElementById('previewFaqPerfil');
            if (!faqWrap) return;
            faqWrap.innerHTML = '';
            const qInputs = Array.from(document.querySelectorAll('.faq-q-input'));
            const aInputs = Array.from(document.querySelectorAll('.faq-a-input'));
            const rows = [];
            for (let i = 0; i < Math.max(qInputs.length, aInputs.length); i++) {
                const q = (qInputs[i]?.value || '').trim();
                const a = (aInputs[i]?.value || '').trim();
                if (q && a) rows.push({ q, a });
            }
            const fallbackFaq = [
                { q: '¿Qué conviene tener a mano antes de contactar?', a: 'Nombre completo, fechas clave y documentos relevantes. Si no tienes todo, igual puedes iniciar el contacto.' },
                { q: '¿Atienden urgencias fuera de horario?', a: 'Depende de la disponibilidad del profesional. Si es urgente, usa llamada o WhatsApp y resume brevemente la situación.' },
                { q: '¿Atienden en regiones y Santiago?', a: 'Revisa la región de ejercicio y si presta servicios en todo Chile. Muchos perfiles coordinan atención remota.' },
                { q: '¿Qué pasa después del primer contacto?', a: 'Podrás coordinar antecedentes, modalidad y siguientes pasos según tu caso.' }
            ];
            (rows.length ? rows.slice(0, 4) : fallbackFaq).forEach(item => {
                const box = document.createElement('div');
                box.className = 'card';
                box.style.boxShadow = 'none';
                box.style.borderStyle = 'dashed';
                box.style.padding = '10px';

                const title = document.createElement('div');
                title.style.fontSize = '12px';
                title.style.fontWeight = '700';
                title.style.marginBottom = '4px';
                title.textContent = item.q;

                const body = document.createElement('div');
                body.className = 'hint';
                body.textContent = item.a;

                box.appendChild(title);
                box.appendChild(body);
                faqWrap.appendChild(box);
            });
        }
        document.querySelectorAll('#materiaChips .chip-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.getAttribute('data-value') || '';
                const select = document.querySelector('select[name="especialidad"]');
                if (!select) return;
                select.value = val;
                document.querySelectorAll('#materiaChips .chip-btn').forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                renderSubmateriasSelector();
                calcProgress();
            });
        });
        document.querySelectorAll('#colorMarcaWrap .color-chip-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const val = this.getAttribute('data-color') || 'azul';
                const hidden = document.getElementById('colorMarcaInput');
                if (hidden) hidden.value = val;
                document.querySelectorAll('#colorMarcaWrap .color-chip-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                renderPerfilPreview();
                calcProgress();
            });
        });
        document.querySelector('select[name="especialidad"]')?.addEventListener('change', function () {
            document.querySelectorAll('#materiaChips .chip-btn').forEach(c => c.classList.toggle('active', c.dataset.value === this.value));
            renderSubmateriasSelector();
            calcProgress();
        });
        document.getElementById('materiaSecundaria')?.addEventListener('change', function () {
            const matPrincipal = (document.querySelector('select[name="especialidad"]')?.value || '').trim();
            if (this.value && this.value === matPrincipal) {
                alert('La materia secundaria debe ser distinta de la principal.');
                this.value = '';
            }
            renderSubmateriasSecundariasSelector();
            calcProgress();
        });
        function normalizeRegionKey(v) {
            return (v || '')
                .toString()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, ' ')
                .trim();
        }
        function getRegionComunasDisponibles(regionLabel) {
            const target = normalizeRegionKey(regionLabel);
            if (!target) return [];
            for (const [region, comunas] of Object.entries(REGION_COMUNAS_MAP || {})) {
                if (normalizeRegionKey(region) === target) {
                    return Array.isArray(comunas) ? comunas : [];
                }
            }
            return [];
        }
        function syncCiudadesPlazaInput() {
            const hidden = document.getElementById('ciudadesPlazaInput');
            if (!hidden) return;
            hidden.value = selectedCiudadesPlaza.join(', ');
        }
        function renderCiudadesPlazaSelector() {
            const wrap = document.getElementById('ciudadesPlazaWrap');
            const hint = document.getElementById('ciudadesPlazaHint');
            const selectedWrap = document.getElementById('ciudadesPlazaSeleccionadas');
            const region = document.querySelector('select[name="region_servicio"]')?.value || '';
            if (!wrap || !hint || !selectedWrap) return;
            const disponibles = getRegionComunasDisponibles(region);
            selectedCiudadesPlaza = selectedCiudadesPlaza.filter(c => {
                if (!region) return true;
                return disponibles.includes(c) || c.length > 0;
            }).slice(0, 6);
            syncCiudadesPlazaInput();
            wrap.innerHTML = '';
            selectedWrap.innerHTML = '';
            if (!region) {
                hint.textContent = 'Selecciona tu región principal para marcar ciudades/comunas de mayor presencia.';
            } else if (!disponibles.length) {
                hint.textContent = 'No encontramos comunas para esta región. Puedes agregarlas manualmente.';
            } else {
                hint.textContent = 'Marca hasta 6 comunas donde eres considerado abogado de la plaza.';
                disponibles.slice(0, 120).forEach(comuna => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'chip-btn chip-soft' + (selectedCiudadesPlaza.includes(comuna) ? ' active' : '');
                    btn.textContent = comuna;
                    btn.addEventListener('click', () => {
                        const exists = selectedCiudadesPlaza.includes(comuna);
                        if (exists) {
                            selectedCiudadesPlaza = selectedCiudadesPlaza.filter(c => c !== comuna);
                        } else {
                            if (selectedCiudadesPlaza.length >= 6) {
                                alert('Puedes seleccionar hasta 6 ciudades/comunas de la plaza.');
                                return;
                            }
                            selectedCiudadesPlaza.push(comuna);
                        }
                        renderCiudadesPlazaSelector();
                        calcProgress();
                    });
                    wrap.appendChild(btn);
                });
            }
            selectedCiudadesPlaza.forEach(comuna => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'chip-btn active';
                chip.textContent = '✓ ' + comuna;
                chip.title = 'Quitar';
                chip.addEventListener('click', () => {
                    selectedCiudadesPlaza = selectedCiudadesPlaza.filter(c => c !== comuna);
                    renderCiudadesPlazaSelector();
                    calcProgress();
                });
                selectedWrap.appendChild(chip);
            });
        }
        (function setupCiudadesPlazaManual(){
            const btn = document.getElementById('agregarCiudadPlazaBtn');
            const input = document.getElementById('ciudadPlazaManual');
            if (!btn || !input) return;
            const add = () => {
                const value = (input.value || '').trim();
                if (!value) return;
                if (selectedCiudadesPlaza.includes(value)) { input.value = ''; return; }
                if (selectedCiudadesPlaza.length >= 6) {
                    alert('Puedes seleccionar hasta 6 ciudades/comunas de la plaza.');
                    return;
                }
                selectedCiudadesPlaza.push(value);
                input.value = '';
                renderCiudadesPlazaSelector();
                calcProgress();
            };
            btn.addEventListener('click', add);
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') { e.preventDefault(); add(); }
            });
        })();
        document.querySelector('select[name="region_servicio"]')?.addEventListener('change', function () {
            renderCiudadesPlazaSelector();
            calcProgress();
        });
        document.getElementById('anioTitulacion')?.addEventListener('change', calcExperienciaDesdeAnio);
        (function setupBioCounter(){
            const bio = document.getElementById('biografiaInput');
            const counter = document.getElementById('biografiaCount');
            if (!bio || !counter) return;
            const update = () => {
                const len = bio.value.length;
                counter.textContent = `${len} caracteres · opcional · máximo 300`;
                counter.style.color = (len <= 300) ? '' : '#fca5a5';
            };
            bio.addEventListener('input', update);
            update();
        })();
        (function setupMediosPagoValidation(){
            const form = document.querySelector('form[action="/guardar-abogado"]');
            const exhibir = form?.querySelector('input[name="exhibir_medios_pago"]');
            const medios = () => Array.from(form?.querySelectorAll('input[name="medios_pago[]"]:checked') || []);
            if (!form || !exhibir) return;
            form.addEventListener('submit', function (e) {
                if (exhibir.checked && medios().length === 0) {
                    e.preventDefault();
                    alert('Si vas a mostrar medios de pago, selecciona al menos uno.');
                }
            });
        })();
        (function setupFaqCounters(){
            const rows = Array.from(document.querySelectorAll('.faq-q-input')).map((qInput, i) => {
                const box = qInput.closest('.card');
                return {
                    q: qInput,
                    a: document.querySelectorAll('.faq-a-input')[i],
                    qCounter: box?.querySelector('.faq-q-counter'),
                    aCounter: box?.querySelector('.faq-a-counter'),
                    status: box?.querySelector('.faq-inline-status')
                };
            });
            const len = (v) => (v || '').length;
            const paint = (counterEl, n, min, max) => {
                if (!counterEl) return;
                counterEl.textContent = `${n}/${max}`;
                counterEl.style.fontWeight = '700';
                counterEl.style.color = (n === 0 || (n >= min && n <= max)) ? 'var(--muted)' : '#fca5a5';
            };
            const syncRow = (row) => {
                if (!row?.q || !row?.a) return;
                const q = row.q.value.trim();
                const a = row.a.value.trim();
                paint(row.qCounter, len(q), 10, 140);
                paint(row.aCounter, len(a), 20, 400);
                row.q.style.borderColor = '';
                row.a.style.borderColor = '';
                if (row.status) {
                    row.status.style.color = 'var(--muted)';
                    row.status.textContent = 'Opcional. Si completas una, completa ambas.';
                }
                const oneFilled = (q !== '' && a === '') || (q === '' && a !== '');
                const badQ = q !== '' && (len(q) < 10 || len(q) > 140);
                const badA = a !== '' && (len(a) < 20 || len(a) > 400);
                if (badQ) row.q.style.borderColor = '#fca5a5';
                if (badA) row.a.style.borderColor = '#fca5a5';
                if (oneFilled || badQ || badA) {
                    if (row.status) {
                        row.status.style.color = '#fca5a5';
                        row.status.textContent = oneFilled
                            ? 'Completa pregunta y respuesta para guardar esta FAQ.'
                            : 'Revisa los límites de caracteres antes de guardar.';
                    }
                } else if (q !== '' && a !== '' && row.status) {
                    row.status.style.color = '#86efac';
                    row.status.textContent = 'FAQ lista para publicar.';
                }
            };
            rows.forEach(row => {
                row.q?.addEventListener('input', () => { syncRow(row); renderPerfilPreview(); });
                row.a?.addEventListener('input', () => { syncRow(row); renderPerfilPreview(); });
                syncRow(row);
            });
        })();
        function bindToggleFields(checkId, inputIds) {
            const c = document.getElementById(checkId);
            const fields = (Array.isArray(inputIds) ? inputIds : [inputIds])
                .map(id => document.getElementById(id))
                .filter(Boolean);
            if (!c || !fields.length) return;
            const sync = () => {
                fields.forEach(i => {
                    i.style.display = c.checked ? 'block' : 'none';
                    if (!c.checked) i.value = '';
                });
            };
            c.addEventListener('change', sync);
            sync();
        }
        bindToggleFields('tienePostitulo', ['nombrePostitulo', 'universidadPostitulo']);
        bindToggleFields('tieneDiplomado', ['nombreDiplomado', 'universidadDiplomado']);
        document.addEventListener('input', calcProgress);
        document.addEventListener('input', renderPerfilPreview);
        document.addEventListener('input', refreshWizardStepUiHints, true);
        document.addEventListener('change', refreshWizardStepUiHints, true);
        document.addEventListener('DOMContentLoaded', function () {
            setupLawyerWizard();
            renderSubmateriasSelector();
            renderSubmateriasSecundariasSelector();
            renderCiudadesPlazaSelector();
            calcProgress();
            calcExperienciaDesdeAnio();
            renderPerfilPreview();
            const lat = (document.getElementById('servicioLat')?.value || '').trim();
            const lng = (document.getElementById('servicioLng')?.value || '').trim();
            if (!lat && !lng && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    document.getElementById('servicioLat').value = position.coords.latitude.toFixed(7);
                    document.getElementById('servicioLng').value = position.coords.longitude.toFixed(7);
                    calcProgress();
                }, function () {}, { enableHighAccuracy: true, timeout: 7000, maximumAge: 300000 });
            }
        });
