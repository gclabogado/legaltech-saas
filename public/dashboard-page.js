/* Dashboard page scripts extracted from templates/dashboard.php */
(function () {
    var configEl = document.getElementById('dashboardPageConfig');
    var initialDashTab = (configEl && configEl.dataset && configEl.dataset.initialTab) || 'home';
    var pendingLeadCount = Number((configEl && configEl.dataset && configEl.dataset.pendingLeadCount) || 0);

    var dashMessage = document.querySelector('.dash-message[data-autodismiss-ms]');
    if (dashMessage) {
        var dismissMs = Number(dashMessage.getAttribute('data-autodismiss-ms') || 4000);
        setTimeout(function () { dashMessage.remove(); }, dismissMs);
    }

    function parseJsonAttr(node, attrName) {
        if (!node) return null;
        var raw = node.getAttribute(attrName);
        if (!raw) return null;
        try { return JSON.parse(raw); } catch (_) { return null; }
    }

    function copyText(text, onSuccess) {
        var value = String(text || '');
        if (!value) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(function () {
                if (typeof onSuccess === 'function') onSuccess();
            }).catch(function () {
                fallbackCopy(value);
                if (typeof onSuccess === 'function') onSuccess();
            });
            return;
        }
        fallbackCopy(value);
        if (typeof onSuccess === 'function') onSuccess();
    }

    function fallbackCopy(value) {
        var input = document.createElement('textarea');
        input.value = value;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        input.remove();
    }

    function formatClp(value) {
        var amount = Number(value || 0);
        if (!isFinite(amount)) amount = 0;
        return new Intl.NumberFormat('es-CL', {
            style: 'currency',
            currency: 'CLP',
            maximumFractionDigits: 0
        }).format(Math.max(0, Math.round(amount)));
    }

    function cleanPhone(value) {
        var digits = String(value || '').replace(/\D+/g, '');
        if (!digits) return '';
        if (digits.length === 11 && digits.indexOf('56') === 0) return digits;
        if (digits.length === 9) return '56' + digits;
        return '';
    }

    var quoteBuilderStorageKeys = {
        service: 'lawyers_quote_builder_service',
        lead: 'lawyers_quote_builder_lead',
        quote: 'lawyers_quote_builder_quote'
    };

    function setStoredJson(key, value) {
        try { sessionStorage.setItem(key, JSON.stringify(value || null)); } catch (_) {}
    }

    function consumeStoredJson(key) {
        try {
            var raw = sessionStorage.getItem(key);
            if (!raw) return null;
            sessionStorage.removeItem(key);
            return JSON.parse(raw);
        } catch (_) {
            return null;
        }
    }

    function openDashboardBuilder() {
        window.location.href = '/dashboard?tab=builder';
    }

    window.dashboardUseServiceForQuote = function (button) {
        var service = parseJsonAttr(button, 'data-service');
        if (!service) return;
        setStoredJson(quoteBuilderStorageKeys.service, service);
        openDashboardBuilder();
    };

    window.dashboardQuoteFromLead = function (button) {
        var lead = parseJsonAttr(button, 'data-lead');
        if (!lead) return;
        setStoredJson(quoteBuilderStorageKeys.lead, lead);
        openDashboardBuilder();
    };

    window.dashboardEditQuote = function (button) {
        var quote = parseJsonAttr(button, 'data-quote');
        if (!quote) return;
        setStoredJson(quoteBuilderStorageKeys.quote, quote);
        openDashboardBuilder();
    };

    function resolveDashboardState(rawTab) {
        var tab = rawTab || 'home';
        if (tab === 'home' || tab === 'inicio') {
            return { main: 'home', sub: 'home' };
        }
        if (tab === 'marketplace' || tab === 'crm' || tab === 'inbox' || tab === 'leads') {
            return { main: 'inbox', sub: 'leads' };
        }
        if (tab === 'quote' || tab === 'builder' || tab === 'cotizador') {
            return { main: 'business', sub: 'builder' };
        }
        if (tab === 'quotes' || tab === 'cotizaciones') {
            return { main: 'inbox', sub: 'quotes' };
        }
        if (tab === 'services' || tab === 'catalog' || tab === 'catalogo' || tab === 'business' || tab === 'negocio') {
            return { main: 'business', sub: 'catalog' };
        }
        if (tab === 'branding' || tab === 'brand' || tab === 'marca') {
            return { main: 'business', sub: 'branding' };
        }
        if (tab === 'performance' || tab === 'rendimiento') {
            return { main: 'business', sub: 'performance' };
        }
        if (tab === 'subscription' || tab === 'suscripcion' || tab === 'cuenta-plan') {
            return { main: 'perfil', sub: 'subscription' };
        }
        if (tab === 'team' || tab === 'equipo' || tab === 'cuenta-team') {
            return { main: 'perfil', sub: 'team' };
        }
        if (tab === 'perfil' || tab === 'cuenta' || tab === 'cuenta-perfil') {
            return { main: 'perfil', sub: 'perfil' };
        }
        return { main: 'home', sub: 'home' };
    }

    window.switchTab = function (t) {
        var state = resolveDashboardState(t);
        var viewHome = document.getElementById('view-home');
        var viewCrm = document.getElementById('view-crm');
        var viewPerformance = document.getElementById('view-performance');
        var viewPerfil = document.getElementById('view-perfil');
        var viewServices = document.getElementById('view-services');
        var servicesCatalogPane = document.getElementById('servicesCatalogPane');
        var servicesQuoteBuilderPane = document.getElementById('servicesQuoteBuilderPane');
        var servicesQuoteInboxPane = document.getElementById('servicesQuoteInboxPane');
        var servicesBrandingPane = document.getElementById('servicesBrandingPane');
        var servicesInboxSubtabs = document.getElementById('servicesInboxSubtabs');
        var servicesBusinessSubtabs = document.getElementById('servicesBusinessSubtabs');
        if (viewHome) viewHome.style.display = state.sub === 'home' ? 'grid' : 'none';
        if (viewCrm) viewCrm.style.display = state.sub === 'leads' ? 'grid' : 'none';
        if (viewPerformance) viewPerformance.style.display = state.sub === 'performance' ? 'grid' : 'none';
        if (viewPerfil) viewPerfil.style.display = state.main === 'perfil' ? 'grid' : 'none';
        if (viewServices) viewServices.style.display = (state.sub === 'quotes' || state.sub === 'catalog' || state.sub === 'builder' || state.sub === 'branding') ? 'grid' : 'none';
        if (servicesCatalogPane) servicesCatalogPane.hidden = state.sub !== 'catalog';
        if (servicesQuoteBuilderPane) servicesQuoteBuilderPane.hidden = state.sub !== 'builder';
        if (servicesQuoteInboxPane) servicesQuoteInboxPane.hidden = state.sub !== 'quotes';
        if (servicesBrandingPane) servicesBrandingPane.hidden = state.sub !== 'branding';
        if (servicesInboxSubtabs) servicesInboxSubtabs.hidden = state.sub !== 'quotes';
        if (servicesBusinessSubtabs) servicesBusinessSubtabs.hidden = !(state.sub === 'catalog' || state.sub === 'builder' || state.sub === 'branding');

        var tabHome = document.getElementById('tab-home');
        var tabInbox = document.getElementById('tab-inbox');
        var tabBusiness = document.getElementById('tab-business');
        var tabPerfil = document.getElementById('tab-perfil');
        if (tabHome) tabHome.classList.toggle('active', state.main === 'home');
        if (tabInbox) tabInbox.classList.toggle('active', state.main === 'inbox');
        if (tabBusiness) tabBusiness.classList.toggle('active', state.main === 'business');
        if (tabPerfil) tabPerfil.classList.toggle('active', state.main === 'perfil');

        ['subtab-leads-crm', 'subtab-leads-services'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) node.classList.toggle('active', state.sub === 'leads');
        });
        ['subtab-quotes-crm', 'subtab-quotes-services'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) node.classList.toggle('active', state.sub === 'quotes');
        });
        ['subtab-catalog-services', 'subtab-catalog-performance'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) node.classList.toggle('active', state.sub === 'catalog');
        });
        ['subtab-builder-services', 'subtab-builder-performance'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) node.classList.toggle('active', state.sub === 'builder');
        });
        ['subtab-branding-services', 'subtab-branding-performance'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) node.classList.toggle('active', state.sub === 'branding');
        });
        ['subtab-performance-services', 'subtab-performance'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) node.classList.toggle('active', state.sub === 'performance');
        });
    };

    window.abrirModalProspecto = function () {
        var el = document.getElementById('modalProspecto');
        if (el) el.style.display = 'flex';
    };

    window.cerrarModalProspecto = function () {
        var el = document.getElementById('modalProspecto');
        if (el) el.style.display = 'none';
    };

    window.copyLink = function (link) {
        copyText(link);
    };

    window.copyTextFromButton = function (button) {
        if (!button) return;
        copyText(button.getAttribute('data-copy-text') || '', function () {
            var old = button.textContent;
            button.textContent = 'Copiado';
            setTimeout(function () { button.textContent = old; }, 1200);
        });
    };

    function bindCrmFilters() {
        var row = document.getElementById('crmFilterRow');
        if (!row) return;
        var buttons = Array.from(row.querySelectorAll('.crm-filter-btn'));
        var buckets = Array.from(document.querySelectorAll('.crm-bucket'));
        var storageKey = 'lawyers_crm_filter';

        var applyFilter = function (filter) {
            if (filter === 'ALL') filter = 'NUEVOS';
            if (filter === 'PERDIDO' || filter === 'CANCELADO') filter = 'ARCHIVO';
            var hasMatchingButton = buttons.some(function (b) {
                return (b.dataset.filter || 'ALL') === filter;
            });
            if (!hasMatchingButton && buttons[0]) {
                filter = buttons[0].dataset.filter || 'NUEVOS';
            }
            buttons.forEach(function (b) {
                b.classList.toggle('active', (b.dataset.filter || 'ALL') === filter);
            });
            buckets.forEach(function (bucket) {
                var ok = filter === 'ALL' || bucket.dataset.bucket === filter;
                bucket.hidden = !ok;
            });
        };

        var initial = 'ALL';
        try { initial = sessionStorage.getItem(storageKey) || 'ALL'; } catch (_) {}
        applyFilter(initial);

        row.addEventListener('click', function (e) {
            var btn = e.target.closest('.crm-filter-btn');
            if (!btn) return;
            var filter = btn.dataset.filter || 'ALL';
            try { sessionStorage.setItem(storageKey, filter); } catch (_) {}
            applyFilter(filter);
        });
    }

    function setupServiceManager() {
        var form = document.getElementById('serviceForm');
        if (!form) return;

        var fields = {
            id: document.getElementById('service_id'),
            nombre: document.getElementById('service_nombre'),
            materia: document.getElementById('service_materia'),
            detalle: document.getElementById('service_detalle'),
            plazo: document.getElementById('service_plazo'),
            precio: document.getElementById('service_precio'),
            gastos: document.getElementById('service_gastos'),
            activo: document.getElementById('service_activo')
        };

        window.dashboardResetServiceForm = function () {
            form.reset();
            if (fields.id) fields.id.value = '';
            if (fields.precio) fields.precio.value = '0';
            if (fields.gastos) fields.gastos.value = '0';
            if (fields.activo) fields.activo.checked = true;
        };

        window.dashboardEditService = function (button) {
            var service = parseJsonAttr(button, 'data-service');
            if (!service) return;
            window.switchTab('catalog');
            if (fields.id) fields.id.value = service.id || '';
            if (fields.nombre) fields.nombre.value = service.nombre || '';
            if (fields.materia) fields.materia.value = service.materia || '';
            if (fields.detalle) fields.detalle.value = service.detalle || '';
            if (fields.plazo) fields.plazo.value = service.plazo_estimado || '';
            if (fields.precio) fields.precio.value = service.precio_base || '0';
            if (fields.gastos) fields.gastos.value = service.gastos_base || '0';
            if (fields.activo) fields.activo.checked = Number(service.activo || 0) === 1;
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
    }

    function setupQuoteBuilder() {
        var form = document.getElementById('quoteBuilderForm');
        if (!form) return;

        var fields = {
            quoteId: document.getElementById('quote_id'),
            clientId: document.getElementById('quote_cliente_id'),
            serviceId: document.getElementById('quote_service_id'),
            clientName: document.getElementById('quote_client_name'),
            clientWhatsapp: document.getElementById('quote_client_whatsapp'),
            clientEmail: document.getElementById('quote_client_email'),
            asunto: document.getElementById('quote_asunto'),
            materia: document.getElementById('quote_materia'),
            plazo: document.getElementById('quote_plazo'),
            vigencia: document.getElementById('quote_vigencia'),
            detalle: document.getElementById('quote_detalle'),
            noIncluye: document.getElementById('quote_no_incluye'),
            honorarios: document.getElementById('quote_honorarios'),
            gastos: document.getElementById('quote_gastos'),
            descuento: document.getElementById('quote_descuento'),
            anticipo: document.getElementById('quote_anticipo'),
            condicionesPago: document.getElementById('quote_condiciones_pago'),
            paymentLink: document.getElementById('quote_payment_link'),
            estado: document.getElementById('quote_estado'),
            notas: document.getElementById('quote_notas'),
            brandingEnabled: document.getElementById('quote_branding_enabled'),
            brandingName: document.getElementById('quote_brand_name'),
            brandingLegalName: document.getElementById('quote_brand_legal_name'),
            brandingRut: document.getElementById('quote_brand_rut'),
            brandingPhone: document.getElementById('quote_brand_phone'),
            brandingEmail: document.getElementById('quote_brand_email'),
            brandingAddress: document.getElementById('quote_brand_address'),
            brandingLegalNotice: document.getElementById('quote_brand_legal_notice'),
            preview: document.getElementById('quote_preview'),
            totalView: document.getElementById('quote_total_view'),
            anticipoView: document.getElementById('quote_anticipo_view'),
            saldoView: document.getElementById('quote_saldo_view'),
            copyBtn: document.getElementById('quoteCopyBtn'),
            previewCopyBtn: document.getElementById('quotePreviewCopyBtn'),
            whatsappBtn: document.getElementById('quoteWhatsappBtn'),
            emailBtn: document.getElementById('quoteEmailBtn')
        };
        var currentStep = 1;
        var steps = Array.from(form.querySelectorAll('.quote-step'));
        var indicators = Array.from(document.querySelectorAll('[data-step-indicator]'));

        function setQuoteStep(step) {
            currentStep = Math.max(1, Math.min(3, Number(step || 1)));
            steps.forEach(function (node) {
                var isActive = Number(node.getAttribute('data-step')) === currentStep;
                node.hidden = !isActive;
                node.classList.toggle('is-active', isActive);
            });
            indicators.forEach(function (node) {
                var isActive = Number(node.getAttribute('data-step-indicator')) === currentStep;
                var isDone = Number(node.getAttribute('data-step-indicator')) < currentStep;
                node.classList.toggle('is-active', isActive);
                node.classList.toggle('is-done', isDone);
            });
        }

        function validateCurrentStep() {
            var activeStep = form.querySelector('.quote-step.is-active');
            if (!activeStep) return true;
            var requiredFields = Array.from(activeStep.querySelectorAll('input[required], select[required], textarea[required]'));
            for (var i = 0; i < requiredFields.length; i++) {
                if (!requiredFields[i].reportValidity()) return false;
            }
            return true;
        }

        function applyClientFromSelect() {
            var option = fields.clientId && fields.clientId.options[fields.clientId.selectedIndex];
            if (!option || !option.value) return;
            fields.clientName.value = option.getAttribute('data-name') || fields.clientName.value;
            fields.clientWhatsapp.value = option.getAttribute('data-whatsapp') || fields.clientWhatsapp.value;
            fields.clientEmail.value = option.getAttribute('data-email') || fields.clientEmail.value;
            if (!fields.materia.value) {
                fields.materia.value = option.getAttribute('data-matter') || '';
            }
            buildQuotePreview();
        }

        function applyServiceFromSelect() {
            var option = fields.serviceId && fields.serviceId.options[fields.serviceId.selectedIndex];
            if (!option || !option.value) return;
            fields.asunto.value = option.getAttribute('data-name') || fields.asunto.value;
            fields.materia.value = option.getAttribute('data-matter') || fields.materia.value;
            fields.detalle.value = option.getAttribute('data-detail') || fields.detalle.value;
            fields.plazo.value = option.getAttribute('data-term') || fields.plazo.value;
            fields.honorarios.value = option.getAttribute('data-price') || fields.honorarios.value;
            fields.gastos.value = option.getAttribute('data-costs') || fields.gastos.value;
            buildQuotePreview();
        }

        function buildQuotePreview() {
            var honorarios = Math.max(0, Number(fields.honorarios.value || 0));
            var gastos = Math.max(0, Number(fields.gastos.value || 0));
            var descuento = Math.max(0, Number(fields.descuento.value || 0));
            var total = Math.max(0, Math.round(honorarios + gastos - descuento));
            var anticipo = Math.max(0, Number(fields.anticipo.value || 0));
            if (anticipo > total) anticipo = total;
            var saldo = Math.max(0, total - anticipo);

            fields.totalView.textContent = formatClp(total);
            fields.anticipoView.textContent = formatClp(anticipo);
            fields.saldoView.textContent = formatClp(saldo);

            var lines = [
                'COTIZACION LEGAL',
                '',
                'Cliente: ' + (fields.clientName.value.trim() || 'Cliente'),
                'Servicio: ' + (fields.asunto.value.trim() || 'Servicio legal'),
                'Materia: ' + (fields.materia.value.trim() || 'Por definir'),
                'Plazo estimado: ' + (fields.plazo.value.trim() || 'Por definir'),
                'Vigencia: ' + (fields.vigencia.value.trim() || 'Por definir'),
                '',
            ];

            lines = lines.concat([
                'DETALLE',
                fields.detalle.value.trim() || 'Por definir.',
                '',
                'NO INCLUYE',
                fields.noIncluye.value.trim() || 'No especificado.',
                '',
                'DETALLE ECONOMICO',
                '- Honorarios: ' + formatClp(honorarios),
                '- Gastos: ' + formatClp(gastos),
                '- Descuento: ' + formatClp(descuento),
                '- Total: ' + formatClp(total),
                '- Anticipo: ' + formatClp(anticipo),
                '- Saldo: ' + formatClp(saldo),
                '- Forma de pago: ' + (fields.condicionesPago.value.trim() || 'Por definir')
            ]);

            if (fields.paymentLink.value.trim()) {
                lines.push('- Link de pago: ' + fields.paymentLink.value.trim());
            }

            lines.push(
                '',
                'NOTAS',
                fields.notas.value.trim() || 'Sin observaciones adicionales.'
            );

            if (fields.brandingEnabled && fields.brandingEnabled.checked) {
                var brandingLines = [];
                if (fields.brandingName && fields.brandingName.value.trim()) {
                    brandingLines.push(fields.brandingName.value.trim());
                }
                if (fields.brandingLegalName && fields.brandingLegalName.value.trim()) {
                    brandingLines.push(fields.brandingLegalName.value.trim());
                }
                if (fields.brandingRut && fields.brandingRut.value.trim()) {
                    brandingLines.push('RUT ' + fields.brandingRut.value.trim());
                }
                var contactLines = [];
                if (fields.brandingPhone && fields.brandingPhone.value.trim()) {
                    contactLines.push('📞 ' + fields.brandingPhone.value.trim());
                }
                if (fields.brandingEmail && fields.brandingEmail.value.trim()) {
                    contactLines.push('📧 ' + fields.brandingEmail.value.trim());
                }
                if (fields.brandingAddress && fields.brandingAddress.value.trim()) {
                    contactLines.push(fields.brandingAddress.value.trim());
                }
                if (brandingLines.length || contactLines.length || (fields.brandingLegalNotice && fields.brandingLegalNotice.value.trim())) {
                    lines.push('');
                    Array.prototype.push.apply(lines, brandingLines);
                    if (contactLines.length) {
                        lines.push('');
                        lines.push('Contacto');
                        Array.prototype.push.apply(lines, contactLines);
                    }
                    if (fields.brandingLegalNotice && fields.brandingLegalNotice.value.trim()) {
                        lines.push('');
                        lines.push(fields.brandingLegalNotice.value.trim());
                    }
                }
            }

            fields.preview.value = lines.join('\n');
            return fields.preview.value;
        }

        function openQuoteWhatsapp() {
            var phone = cleanPhone(fields.clientWhatsapp.value);
            if (!phone) {
                alert('Ingresa un WhatsApp válido del cliente para abrir el envío.');
                return;
            }
            window.open('https://wa.me/' + encodeURIComponent(phone) + '?text=' + encodeURIComponent(buildQuotePreview()), '_blank', 'noopener');
        }

        function openQuoteEmail() {
            var email = (fields.clientEmail.value || '').trim();
            if (!email) {
                alert('Ingresa un email del cliente para abrir el borrador.');
                return;
            }
            var subject = 'Cotizacion legal - ' + (fields.asunto.value.trim() || 'Tu Estudio Juridico');
            window.location.href = 'mailto:' + encodeURIComponent(email)
                + '?subject=' + encodeURIComponent(subject)
                + '&body=' + encodeURIComponent(buildQuotePreview());
        }

        window.dashboardResetQuoteForm = function () {
            form.reset();
            if (fields.quoteId) fields.quoteId.value = '';
            if (fields.honorarios) fields.honorarios.value = '0';
            if (fields.gastos) fields.gastos.value = '0';
            if (fields.descuento) fields.descuento.value = '0';
            if (fields.anticipo) fields.anticipo.value = '0';
            if (fields.estado) fields.estado.value = 'BORRADOR';
            setQuoteStep(1);
            buildQuotePreview();
        };

        function populateQuoteFromService(service) {
            if (!service) return;
            setQuoteStep(1);
            if (fields.serviceId) fields.serviceId.value = String(service.id || '');
            fields.asunto.value = service.nombre || '';
            fields.materia.value = service.materia || '';
            fields.detalle.value = service.detalle || '';
            fields.plazo.value = service.plazo_estimado || '';
            fields.honorarios.value = service.precio_base || '0';
            fields.gastos.value = service.gastos_base || '0';
            buildQuotePreview();
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function populateQuoteFromLead(lead) {
            if (!lead) return;
            setQuoteStep(1);
            if (fields.clientId && lead.client_id) fields.clientId.value = String(lead.client_id);
            fields.clientName.value = lead.name || '';
            fields.clientWhatsapp.value = lead.whatsapp || '';
            fields.clientEmail.value = lead.email || '';
            if (!fields.materia.value) fields.materia.value = lead.matter || '';
            if (!fields.notas.value && lead.notes) fields.notas.value = lead.notes;
            buildQuotePreview();
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function populateQuoteFromSavedQuote(quote) {
            if (!quote) return;
            if (fields.quoteId) fields.quoteId.value = quote.id || '';
            if (fields.clientId) fields.clientId.value = quote.cliente_id || '';
            if (fields.serviceId) fields.serviceId.value = quote.service_id || '';
            fields.clientName.value = quote.client_name || '';
            fields.clientWhatsapp.value = quote.client_whatsapp || '';
            fields.clientEmail.value = quote.client_email || '';
            fields.asunto.value = quote.asunto || '';
            fields.materia.value = quote.materia || '';
            fields.plazo.value = quote.plazo_estimado || '';
            fields.vigencia.value = quote.vigencia || '';
            fields.detalle.value = quote.detalle || '';
            fields.noIncluye.value = quote.no_incluye || '';
            fields.honorarios.value = quote.honorarios || '0';
            fields.gastos.value = quote.gastos || '0';
            fields.descuento.value = quote.descuento || '0';
            fields.anticipo.value = quote.anticipo || '0';
            fields.condicionesPago.value = quote.condiciones_pago || '';
            fields.paymentLink.value = quote.payment_link || '';
            fields.notas.value = quote.notas || '';
            fields.estado.value = quote.estado || 'BORRADOR';
            setQuoteStep(3);
            buildQuotePreview();
            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        if (fields.clientId) fields.clientId.addEventListener('change', applyClientFromSelect);
        if (fields.serviceId) fields.serviceId.addEventListener('change', applyServiceFromSelect);
        form.addEventListener('input', buildQuotePreview);
        ['brandingEnabled','brandingName','brandingLegalName','brandingRut','brandingPhone','brandingEmail','brandingAddress','brandingLegalNotice'].forEach(function (key) {
            if (fields[key]) fields[key].addEventListener('input', buildQuotePreview);
            if (fields[key] && fields[key].type === 'checkbox') fields[key].addEventListener('change', buildQuotePreview);
        });
        if (fields.copyBtn) fields.copyBtn.addEventListener('click', function () { copyText(buildQuotePreview()); });
        if (fields.previewCopyBtn) fields.previewCopyBtn.addEventListener('click', function () { copyText(buildQuotePreview()); });
        if (fields.whatsappBtn) fields.whatsappBtn.addEventListener('click', openQuoteWhatsapp);
        if (fields.emailBtn) fields.emailBtn.addEventListener('click', openQuoteEmail);
        Array.from(form.querySelectorAll('[data-step-next]')).forEach(function (button) {
            button.addEventListener('click', function () {
                if (!validateCurrentStep()) return;
                setQuoteStep(currentStep + 1);
            });
        });
        Array.from(form.querySelectorAll('[data-step-prev]')).forEach(function (button) {
            button.addEventListener('click', function () {
                setQuoteStep(currentStep - 1);
            });
        });

        setQuoteStep(1);
        buildQuotePreview();
        var storedQuote = consumeStoredJson(quoteBuilderStorageKeys.quote);
        var storedLead = consumeStoredJson(quoteBuilderStorageKeys.lead);
        var storedService = consumeStoredJson(quoteBuilderStorageKeys.service);
        if (storedQuote) {
            populateQuoteFromSavedQuote(storedQuote);
        } else {
            if (storedLead) populateQuoteFromLead(storedLead);
            if (storedService) populateQuoteFromService(storedService);
        }
    }

    try {
        if (initialDashTab === 'marketplace') {
            sessionStorage.setItem('lawyers_crm_filter', pendingLeadCount > 0 ? 'NUEVOS' : 'CONTACTADO');
        }
    } catch (_) {}

    bindCrmFilters();
    setupServiceManager();
    setupQuoteBuilder();
    window.switchTab(initialDashTab);
})();
