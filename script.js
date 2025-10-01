document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.auth__tab');
    const panels = document.querySelectorAll('.auth__panel');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const targetId = tab.dataset.target;
            if (!targetId) return;

            tabs.forEach((btn) => {
                const isActive = btn === tab;
                btn.classList.toggle('auth__tab--active', isActive);
                btn.setAttribute('aria-selected', String(isActive));
            });

            panels.forEach((panel) => {
                const isActive = panel.id === targetId;
                panel.classList.toggle('auth__panel--active', isActive);
                panel.setAttribute('aria-hidden', String(!isActive));
            });
        });
    });

    const registerForm = document.querySelector('form#auth-register');
    const passwordInput = document.getElementById('register-password');
    const confirmInput = document.getElementById('register-confirm');

    const clearFieldMessage = (field) => {
        if (!field) return;
        const container = field.closest('.field');
        const message = container?.querySelector('.field__error');
        if (message) {
            message.remove();
        }
    };

    const showFieldMessage = (input, message) => {
        const container = input.closest('.field');
        if (!container) return;
        clearFieldMessage(input);
        const hint = document.createElement('small');
        hint.className = 'field__error';
        hint.textContent = message;
        container.appendChild(hint);
    };

    if (registerForm && passwordInput && confirmInput) {
        const validateMatch = () => {
            clearFieldMessage(confirmInput);
            confirmInput.setCustomValidity('');
            if (passwordInput.value && confirmInput.value && passwordInput.value !== confirmInput.value) {
                confirmInput.setCustomValidity('كلمتا المرور غير متطابقتين');
                showFieldMessage(confirmInput, 'كلمتا المرور غير متطابقتين، يرجى المراجعة.');
            }
        };

        passwordInput.addEventListener('input', validateMatch);
        confirmInput.addEventListener('input', validateMatch);

        registerForm.addEventListener('submit', (event) => {
            validateMatch();
            if (!registerForm.checkValidity()) {
                event.preventDefault();
                registerForm.reportValidity();
            }
        });
    }

    const smoothLinks = document.querySelectorAll('a[href^="#"]');
    smoothLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const targetId = link.getAttribute('href');
            if (!targetId || targetId === '#') return;
            const destination = document.querySelector(targetId);
            if (!destination) return;
            event.preventDefault();
            destination.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    document.querySelectorAll('[data-dismiss-toast]').forEach((button) => {
        button.addEventListener('click', () => {
            const toast = button.closest('.toast');
            if (toast) {
                toast.classList.add('toast--hide');
                setTimeout(() => toast.remove(), 300);
            }
        });
    });

    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.getAttribute('data-copy');
            if (!value) return;
            try {
                await navigator.clipboard.writeText(value);
                button.textContent = 'تم النسخ';
                button.classList.add('link--success');
                setTimeout(() => {
                    button.textContent = 'نسخ الرمز';
                    button.classList.remove('link--success');
                }, 2000);
            } catch (error) {
                button.textContent = 'تعذر النسخ';
            }
        });
    });

    const scopeRadios = document.querySelectorAll('input[name="department_scope"]');
    const scopeSelect = document.querySelector('[data-scope-target] select');
    const scopeField = document.querySelector('[data-scope-target]');

    if (scopeRadios.length && scopeSelect && scopeField) {
        const syncScopeState = () => {
            const selected = document.querySelector('input[name="department_scope"]:checked');
            const isSpecific = selected?.value === 'specific';
            scopeSelect.disabled = !isSpecific;
            scopeField.classList.toggle('field--disabled', !isSpecific);
            if (!isSpecific) {
                scopeSelect.value = '';
            }
        };

        scopeRadios.forEach((radio) => {
            radio.addEventListener('change', syncScopeState);
        });

        syncScopeState();
    }

    document.querySelectorAll('textarea[data-counter]').forEach((textarea) => {
        const counter = document.createElement('div');
        counter.className = 'field__counter';
        textarea.after(counter);

        const updateCounter = () => {
            counter.textContent = `${textarea.value.length} / ${textarea.maxLength || 600}`;
        };

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });
});
