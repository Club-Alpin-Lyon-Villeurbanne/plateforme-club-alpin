// Affiche/masque le champ "nombre de véhicules" du formulaire sortie
// selon le mode de transport sélectionné. La liste des modes véhiculés
// vient du data-attribute généré par Twig (source unique : TransportModeEnum).

function initTransportModeVehicles() {
    const select = document.getElementById('event_modeTransport');
    const wrapper = document.getElementById('nb-vehicules-wrapper');
    const input = document.getElementById('event_nbVehicules');

    if (!select || !wrapper || !input) {
        return;
    }

    const modesWithVehicles = (wrapper.dataset.modesWithVehicles || '')
        .split(',')
        .map((v) => v.trim())
        .filter(Boolean);

    const sync = () => {
        const show = modesWithVehicles.includes(select.value);
        wrapper.style.display = show ? '' : 'none';
        if (!show) {
            input.value = 1;
        }
    };

    select.addEventListener('change', sync);
    sync();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTransportModeVehicles);
} else {
    initTransportModeVehicles();
}
