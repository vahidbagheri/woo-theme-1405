(() => {
    'use strict';
    const form = document.getElementById('srm-form');
    if (!form) return;
    const menus = [...form.querySelectorAll('input[name="menus[]"][type="checkbox"]')];
    const caps = [...form.querySelectorAll('input[name="caps[]"]')];
    const requirements = input => JSON.parse(input.dataset.requiredCaps || '[]');
    const refresh = () => {
        const required = new Set(menus.filter(input => input.checked && !input.disabled).flatMap(requirements));
        caps.forEach(input => {
            const automatic = required.has(input.value);
            if (automatic || input.value === 'read') input.checked = true;
            input.disabled = automatic || input.value === 'read';
            input.title = automatic ? 'مجوز لازم برای منوهای انتخاب‌شده' : '';
        });
        const enabled = new Set(caps.filter(input => input.checked).map(input => input.value));
        menus.forEach(input => {
            const note = input.closest('label').querySelector('.srm-cap-note');
            if (note) note.hidden = !input.checked || requirements(input).every(cap => enabled.has(cap));
        });
        form.querySelectorAll('.srm-menu-card').forEach(card => {
            card.classList.toggle('is-selected', !!card.querySelector('input:checked:not(:disabled)'));
        });
    };
    form.addEventListener('change', event => {
        if (menus.includes(event.target)) {
            menus.filter(input => input.value === event.target.value && !input.disabled).forEach(input => {
                input.checked = event.target.checked;
            });
        }
        refresh();
    });
    form.addEventListener('submit', refresh);
    form.querySelectorAll('[data-select]').forEach(button => button.addEventListener('click', () => {
        menus.filter(input => !input.disabled).forEach(input => { input.checked = button.dataset.select === 'all'; });
        refresh();
    }));
    form.querySelectorAll('.srm-group-toggle').forEach(button => button.addEventListener('click', () => {
        const inputs = [...button.closest('.srm-menu-card').querySelectorAll('input:not(:disabled)')];
        const checked = !inputs.every(input => input.checked);
        const values = new Set(inputs.map(input => input.value));
        menus.filter(input => values.has(input.value) && !input.disabled).forEach(input => { input.checked = checked; });
        refresh();
    }));
    document.getElementById('srm-search').addEventListener('input', event => {
        const normalize = value => value.replace(/ي/g, 'ی').replace(/ك/g, 'ک').toLocaleLowerCase().trim();
        const term = normalize(event.target.value);
        const cards = [...form.querySelectorAll('.srm-menu-card')];
        cards.forEach(card => { card.hidden = !normalize(card.textContent).includes(term); });
        document.getElementById('srm-no-results').hidden = cards.some(card => !card.hidden);
    });
    document.getElementById('srm-preset').addEventListener('change', event => {
        const preset = (window.srmPresets || {})[event.target.value];
        if (!preset) return;
        caps.forEach(input => { input.checked = input.value === 'read' || preset.includes(input.value); });
        refresh();
    });
    const deleteForm = document.querySelector('.srm-delete');
    if (deleteForm) deleteForm.addEventListener('submit', event => {
        if (!window.confirm('این نقش حذف شود؟')) event.preventDefault();
    });
    refresh();
})();
