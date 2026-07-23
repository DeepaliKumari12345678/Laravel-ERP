<script>
(function () {
    function debounce(fn, wait) {
        let timer;
        return function () {
            const ctx = this;
            const args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(ctx, args);
            }, wait);
        };
    }

    function submitForm(form) {
        if (typeof form.requestSubmit === 'function') {
            // Prefer requestSubmit so HTML5 validation still runs when needed
            const btn = form.querySelector('button[type="submit"][hidden], button[type="submit"]');
            if (btn) {
                form.requestSubmit(btn);
            } else {
                form.requestSubmit();
            }
        } else {
            form.submit();
        }
    }

    document.querySelectorAll('form[method="get"]').forEach(function (form) {
        if (form.dataset.autoSearch === 'off') {
            return;
        }

        form.querySelectorAll('button[type="submit"]').forEach(function (btn) {
            var label = (btn.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
            if (label === 'search') {
                btn.hidden = true;
                btn.setAttribute('tabindex', '-1');
                btn.setAttribute('aria-hidden', 'true');
            }
        });

        var debouncedSubmit = debounce(function () {
            submitForm(form);
        }, 400);

        form.querySelectorAll('input, select, textarea').forEach(function (field) {
            if (field.type === 'hidden' || field.type === 'submit' || field.type === 'button') {
                return;
            }

            var isInstant = field.tagName === 'SELECT'
                || field.type === 'checkbox'
                || field.type === 'radio'
                || field.type === 'date';

            field.addEventListener(isInstant ? 'change' : 'input', function () {
                if (isInstant) {
                    submitForm(form);
                } else {
                    debouncedSubmit();
                }
            });
        });
    });
})();
</script>
