/**
 * DRL CRÉATIONS — Script d'administration (gestion dynamique du mode de tarification)
 */
document.addEventListener('DOMContentLoaded', () => {
    const pricingSelect = document.getElementById('pricing_mode');
    const priceInput = document.getElementById('price');

    function syncPricingState() {
        if (!pricingSelect || !priceInput) return;
        if (pricingSelect.value === 'on_request') {
            priceInput.value = '';
            priceInput.placeholder = 'Désactivé (Prix sur demande)';
            priceInput.disabled = true;
        } else {
            priceInput.disabled = false;
            priceInput.placeholder = 'Ex: 150000';
        }
    }

    if (pricingSelect) {
        pricingSelect.addEventListener('change', syncPricingState);
        syncPricingState();
    }
});
