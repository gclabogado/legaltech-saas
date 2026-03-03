const AdminApp = (() => {
    const selectors = {
        themeSelect: '#previewThemeSelect',
        themeReset: '#previewThemeReset',
        promotedToggle: '[data-admin-promoted-toggle="promovidos"]',
        promotedPanel: '[data-admin-promoted-panel="promovidos"]',
    };

    const applyTheme = (value) => {
        document.body.classList.remove('theme-admin', 'theme-olive', 'theme-client-lite', 'theme-blue');
        document.body.classList.add(value === 'cliente' ? 'theme-client-lite' : (value === 'abogado' ? 'theme-olive' : 'theme-admin'));
    };

    const initThemeControls = () => {
        const select = document.querySelector(selectors.themeSelect);
        const reset = document.querySelector(selectors.themeReset);
        if (!select) return;
        applyTheme(select.value);
        select.addEventListener('change', () => applyTheme(select.value));
        reset?.addEventListener('click', () => {
            select.value = 'admin';
            applyTheme('admin');
        });
    };

    const initPromotedToggle = () => {
        const button = document.querySelector(selectors.promotedToggle);
        const panel = document.querySelector(selectors.promotedPanel);
        if (!button || !panel) return;
        const showLabel = 'Mostrar abogados promovidos (publicados o en revisión PJUD)';
        const hideLabel = 'Ocultar abogados promovidos';
        const syncLabel = () => {
            const visible = !panel.hasAttribute('hidden');
            button.textContent = visible ? hideLabel : showLabel;
        };
        button.addEventListener('click', () => {
            if (panel.hasAttribute('hidden')) {
                panel.removeAttribute('hidden');
            } else {
                panel.setAttribute('hidden', '');
            }
            syncLabel();
        });
        syncLabel();
    };

    return {
        init: () => {
            document.addEventListener('DOMContentLoaded', () => {
                initThemeControls();
                initPromotedToggle();
            });
        },
    };
})();

AdminApp.init();
