<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-country-select]').forEach(countrySelect => {
        const stateSelect = document.getElementById(countrySelect.dataset.stateTarget);
        if (!stateSelect) return;

        const loadStates = async (keepCurrent) => {
            const option = countrySelect.options[countrySelect.selectedIndex];
            const countryCode = option?.dataset.countryCode || '';
            const currentState = keepCurrent ? (stateSelect.dataset.selectedState || stateSelect.value) : '';

            stateSelect.innerHTML = '<option value="">— Select state / province —</option>';

            if (!countryCode) {
                stateSelect.disabled = true;
                return;
            }

            stateSelect.disabled = true;

            try {
                const url = new URL(countrySelect.dataset.statesUrl, window.location.origin);
                url.searchParams.set('country', countryCode);
                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });

                if (!response.ok) throw new Error('Could not load states');

                const states = await response.json();
                let currentFound = false;

                Object.entries(states).forEach(([code, label]) => {
                    const stateOption = document.createElement('option');
                    stateOption.value = label;
                    stateOption.textContent = label;
                    stateOption.dataset.stateCode = code;
                    stateOption.selected = label === currentState;
                    currentFound ||= stateOption.selected;
                    stateSelect.appendChild(stateOption);
                });

                if (currentState && !currentFound) {
                    const existingOption = document.createElement('option');
                    existingOption.value = currentState;
                    existingOption.textContent = currentState;
                    existingOption.selected = true;
                    stateSelect.appendChild(existingOption);
                }

                if (Object.keys(states).length === 0) {
                    stateSelect.options[0].textContent = '— No states / provinces —';
                }
            } catch (error) {
                if (currentState) {
                    const existingOption = document.createElement('option');
                    existingOption.value = currentState;
                    existingOption.textContent = currentState;
                    existingOption.selected = true;
                    stateSelect.appendChild(existingOption);
                }
            } finally {
                stateSelect.disabled = false;
                stateSelect.dataset.selectedState = '';
            }
        };

        countrySelect.addEventListener('change', () => loadStates(false));
        loadStates(true);
    });
});
</script>
