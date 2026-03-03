const onboardingClienteConfigEl = document.getElementById('onboardingClienteConfig');
let onboardingClienteConfig = {};
try {
    onboardingClienteConfig = JSON.parse(onboardingClienteConfigEl?.textContent || '{}');
} catch (e) {
    onboardingClienteConfig = {};
}

function sendUxEvent(eventName, payload) {
    const body = JSON.stringify({ event: eventName, payload: payload || {} });
    if (navigator.sendBeacon) {
        const blob = new Blob([body], { type: 'application/json' });
        navigator.sendBeacon('/api/event', blob);
    } else {
        fetch('/api/event', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: body,
            keepalive: true
        }).catch(() => {});
    }
}

const totalSteps = 5;
let currentStep = 1;
const weeklyBlocked = !!onboardingClienteConfig.weeklyBlocked;
const weeklyBlockedMessage = String(onboardingClienteConfig.weeklyBlockedMessage || 'Por política anti-spam solo puedes publicar 1 consulta nueva cada 7 días.');
const hasExistingCase = !!onboardingClienteConfig.hasExistingCase;
const submitErrorReason = onboardingClienteConfig.errorMessage || null;

        function renderStep() {
            document.querySelectorAll('.form-step').forEach((stepEl) => {
                const stepNumber = Number(stepEl.dataset.step);
                stepEl.classList.toggle('hidden', stepNumber !== currentStep);
            });

            const labels = {
                1: 'Paso 1 de 5 · Materia legal',
                2: 'Paso 2 de 5 · Lugar',
                3: 'Paso 3 de 5 · Situación',
                4: 'Paso 4 de 5 · Identificación',
                5: 'Paso 5 de 5 · WhatsApp'
            };
            const hints = {
                1: 'Elige la materia para abrir opciones relevantes.',
                2: 'Indica dónde ocurre el caso.',
                3: 'Selecciona opciones y agrega un detalle breve.',
                4: 'Validamos identidad para reducir spam.',
                5: 'Confirmas tu WhatsApp para recibir contacto.'
            };
            document.getElementById('stepLabel').textContent = labels[currentStep];
            document.getElementById('stepHint').textContent = hints[currentStep];
            document.getElementById('progressBar').style.width = ((currentStep / totalSteps) * 100) + '%';

            const btnBack = document.getElementById('btnBack');
            const btnNext = document.getElementById('btnNext');
            const btnConfirm = document.getElementById('btnConfirm');

            btnBack.classList.toggle('hidden', currentStep === 1);
            btnNext.classList.toggle('hidden', currentStep === totalSteps);
            btnConfirm.classList.toggle('hidden', currentStep !== totalSteps);
            btnNext.classList.toggle('span-2', currentStep === 1);
            syncOptionHighlights();
            updateWizardSummaryPreview();
        }

        function validateStep(step) {
            const specialty = document.getElementById('especialidadInput').value;
            const city = document.getElementById('ciudadInput').value.trim();
            const foreigner = document.getElementById('extranjeroInput').checked;
            const rut = document.getElementById('rutInput').value.trim();
            const description = document.getElementById('descripcionInput').value.trim();
            const phone = document.getElementById('whatsappInput').value.trim();
            const consent = document.getElementById('consentInput').checked;
            const issueType = document.getElementById('issueTypeInput')?.value || '';
            const urgency = document.getElementById('urgencyInput')?.value || '';
            const goal = document.getElementById('goalInput')?.value || '';

            if (step === 1) return !!specialty;
            if (step === 2) return city.length >= 2;
            if (step === 3) return !!issueType && !!urgency && !!goal && description.length >= 20;
            if (step === 4) {
                const rutOk = foreigner ? true : isRutValidClient(rut);
                return foreigner ? true : rutOk;
            }
            if (step === 5) return /^9\d{8}$/.test(phone) && consent;
            return true;
        }

        function composeDescription() {
            const issueType = document.getElementById('issueTypeInput')?.value || '';
            const urgency = document.getElementById('urgencyInput')?.value || '';
            const goal = document.getElementById('goalInput')?.value || '';
            const city = document.getElementById('ciudadInput')?.value.trim() || '';
            const detail = document.getElementById('descripcionInput')?.value.trim() || '';
            const parts = [];
            if (issueType) parts.push('Situación: ' + issueType + '.');
            if (urgency) parts.push('Urgencia: ' + urgency + '.');
            if (goal) parts.push('Busca: ' + goal + '.');
            if (city) parts.push('Lugar: ' + city + '.');
            if (detail) parts.push('Detalle: ' + detail);
            const composed = parts.join(' ');
            const hidden = document.getElementById('descripcionComposedInput');
            if (hidden) hidden.value = composed;
            const descripcionInput = document.getElementById('descripcionInput');
            if (descripcionInput && composed.length >= 20) {
                descripcionInput.dataset.composed = composed;
            }
            return composed;
        }

        function updateWizardSummaryPreview() {
            const preview = document.getElementById('wizardSummaryPreview');
            if (!preview) return;
            const specialty = document.getElementById('especialidadInput')?.value || 'Sin materia';
            const city = document.getElementById('ciudadInput')?.value.trim() || 'Sin lugar';
            const urgency = document.getElementById('urgencyInput')?.value || 'Sin urgencia';
            const goal = document.getElementById('goalInput')?.value || 'Sin objetivo';
            preview.textContent = `Resumen: ${specialty} · ${city} · ${urgency} · ${goal}`;
        }

        function syncOptionHighlights() {
            document.querySelectorAll('.option-btn[data-target]').forEach((btn) => {
                const targetId = btn.dataset.target;
                const target = document.getElementById(targetId);
                const value = btn.dataset.value || '';
                let active = false;
                if (target) {
                    if (target.tagName === 'SELECT') {
                        active = target.value === value;
                    } else {
                        active = String(target.value) === value;
                    }
                }
                btn.classList.toggle('btn-primary', active);
                btn.classList.toggle('btn-ghost', !active);
            });
        }

        function normalizeRutClient(value) {
            const clean = (value || '').toUpperCase().replace(/[^0-9K]/g, '');
            if (clean.length < 2) return null;
            const body = clean.slice(0, -1);
            const dv = clean.slice(-1);
            if (!/^[0-9]+$/.test(body)) return null;
            const normalizedBody = body.replace(/^0+/, '') || '0';
            return `${normalizedBody}-${dv}`;
        }

        function calculateRutDvClient(body) {
            if (!/^[0-9]+$/.test(body)) return null;
            let sum = 0;
            let mul = 2;
            for (let i = body.length - 1; i >= 0; i -= 1) {
                sum += Number(body[i]) * mul;
                mul = (mul === 7) ? 2 : mul + 1;
            }
            const rest = 11 - (sum % 11);
            if (rest === 11) return '0';
            if (rest === 10) return 'K';
            return String(rest);
        }

        function isRutValidClient(value) {
            const normalized = normalizeRutClient(value);
            if (!normalized) return false;
            const [body, dv] = normalized.split('-');
            const expected = calculateRutDvClient(body);
            return expected !== null && dv === expected;
        }

        function toggleRutRequirement() {
            const foreigner = document.getElementById('extranjeroInput').checked;
            const rutInput = document.getElementById('rutInput');
            rutInput.disabled = foreigner;
            rutInput.required = !foreigner;
            if (foreigner) {
                rutInput.classList.remove('input-error');
            }
        }

        function nextStep() {
            if (weeklyBlocked) {
                sendUxEvent('lead_weekly_limit_blocked_frontend', { stage: 'next_step' });
                alert(weeklyBlockedMessage);
                return;
            }
            if (!validateStep(currentStep)) {
                sendUxEvent('lead_validation_error', { step: currentStep, reason: 'invalid_step_data' });
                if (currentStep === 4 && !document.getElementById('extranjeroInput').checked && !isRutValidClient(document.getElementById('rutInput').value.trim())) {
                    alert('RUT inválido. Corrige el RUT o marca "Soy extranjero".');
                    return;
                }
                alert('Completa correctamente este paso antes de continuar.');
                return;
            }
            if (currentStep < totalSteps) {
                sendUxEvent('lead_step_completed', { step: currentStep });
                currentStep++;
                renderStep();
            }
        }

        function prevStep() {
            if (currentStep > 1) {
                currentStep--;
                renderStep();
            }
        }

        function openConfirmation() {
            if (weeklyBlocked) {
                sendUxEvent('lead_weekly_limit_blocked_frontend', { stage: 'open_confirmation' });
                alert(weeklyBlockedMessage);
                return;
            }
            if (!validateStep(1) || !validateStep(2) || !validateStep(3) || !validateStep(4) || !validateStep(5)) {
                sendUxEvent('lead_validation_error', { step: currentStep, reason: 'invalid_before_confirmation' });
                alert('Revisa los datos del formulario antes de publicar.');
                return;
            }

            const specialty = document.getElementById('especialidadInput').value;
            const city = document.getElementById('ciudadInput').value.trim();
            const foreigner = document.getElementById('extranjeroInput').checked;
            const rut = document.getElementById('rutInput').value.trim();
            const description = composeDescription() || document.getElementById('descripcionInput').value.trim();
            const phone = document.getElementById('whatsappInput').value.trim();
            const identity = foreigner ? 'Extranjero (sin RUT chileno)' : (normalizeRutClient(rut) || rut);

            document.getElementById('displaySpecialty').textContent = specialty;
            document.getElementById('displayCity').textContent = city;
            document.getElementById('displayIdentity').textContent = identity;
            document.getElementById('displayDescription').textContent = description.length > 180
                ? description.slice(0, 180) + '...'
                : description;
            document.getElementById('displayPhone').textContent = '+56 ' + phone;

            document.getElementById('confirmModal').style.display = 'flex';
            document.body.classList.add('modal-active');
            sendUxEvent('lead_confirmation_opened', { specialty: specialty });
        }

        function closeConfirmation() {
            document.getElementById('confirmModal').style.display = 'none';
            document.body.classList.remove('modal-active');
        }

        function submitForm() {
            if (weeklyBlocked) {
                sendUxEvent('lead_weekly_limit_blocked_frontend', { stage: 'submit' });
                alert(weeklyBlockedMessage);
                return;
            }
            const composed = composeDescription();
            if (composed.length >= 20) {
                document.getElementById('descripcionInput').value = composed;
            }
            sendUxEvent('lead_submitted_frontend', { source: 'onboarding_cliente' });
            document.getElementById('prospectoForm').submit();
        }

        document.getElementById('heroStartCta')?.addEventListener('click', function () {
            const section = document.getElementById('form-section');
            if (section.classList.contains('hidden')) {
                section.classList.remove('hidden');
            }
        });

        document.getElementById('extranjeroInput')?.addEventListener('change', function () {
            toggleRutRequirement();
            document.getElementById('identityModeInput').value = this.checked ? 'extranjero' : 'chileno';
            syncOptionHighlights();
        });

        document.getElementById('rutInput')?.addEventListener('blur', function (event) {
            const input = event.target;
            if (document.getElementById('extranjeroInput').checked) {
                return;
            }
            const normalized = normalizeRutClient(input.value);
            if (normalized) {
                input.value = normalized;
            }
        });

        document.getElementById('postalLookupBtn')?.addEventListener('click', async function () {
            const commune = document.getElementById('ciudadInput').value.trim();
            const street = document.getElementById('direccionCalleInput').value.trim();
            const number = document.getElementById('direccionNumeroInput').value.trim();
            if (!commune || !street || !number) {
                alert('Para buscar código postal necesitas comuna, calle y número.');
                return;
            }

            const btn = this;
            btn.disabled = true;
            const original = btn.textContent;
            btn.textContent = 'Buscando...';
            try {
                const query = new URLSearchParams({ commune, street, number });
                const res = await fetch('/api/postalcode?' + query.toString());
                const json = await res.json();
                if (json && json.success && json.postal_code) {
                    document.getElementById('codigoPostalInput').value = json.postal_code;
                    alert('Código postal actualizado.');
                } else {
                    alert('No pudimos obtener el código postal ahora. Puedes continuar sin este dato.');
                }
            } catch (e) {
                alert('Servicio de código postal no disponible ahora.');
            } finally {
                btn.disabled = false;
                btn.textContent = original;
            }
        });

        document.querySelectorAll('.option-btn[data-target]')?.forEach((btn) => {
            btn.addEventListener('click', function () {
                const targetId = this.dataset.target;
                const value = this.dataset.value || '';
                const target = document.getElementById(targetId);
                if (!target) return;
                if (target.tagName === 'SELECT') {
                    target.value = value;
                } else {
                    target.value = value;
                    if (targetId === 'identityModeInput') {
                        const extranjero = value === 'extranjero';
                        const extranjeroInput = document.getElementById('extranjeroInput');
                        extranjeroInput.checked = extranjero;
                        toggleRutRequirement();
                    }
                }
                if (targetId === 'locationModeInput' && value === 'remoto') {
                    const cityInput = document.getElementById('ciudadInput');
                    if (!cityInput.value.trim()) cityInput.value = 'Atención remota / online';
                }
                composeDescription();
                syncOptionHighlights();
                updateWizardSummaryPreview();
            });
        });

        document.getElementById('descripcionInput')?.addEventListener('input', function () {
            composeDescription();
            updateWizardSummaryPreview();
        });
        document.getElementById('ciudadInput')?.addEventListener('input', updateWizardSummaryPreview);

        document.getElementById('useGeoWizardBtn')?.addEventListener('click', function () {
            if (!navigator.geolocation) {
                alert('Tu navegador no permite geolocalización.');
                return;
            }
            navigator.geolocation.getCurrentPosition(() => {
                alert('Ubicación detectada. Escribe o confirma tu comuna/ciudad en el campo.');
            }, () => {
                alert('No pudimos obtener tu ubicación.');
            }, { enableHighAccuracy: true, timeout: 10000 });
        });

        toggleRutRequirement();
        composeDescription();
        renderStep();
sendUxEvent('lead_form_started', { has_existing_case: hasExistingCase });
if (submitErrorReason) {
    sendUxEvent('lead_submit_failed_frontend', { reason: submitErrorReason });
}
