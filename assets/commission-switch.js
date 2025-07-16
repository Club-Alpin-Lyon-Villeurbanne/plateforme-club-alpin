function switchCommission(context) {
    const selectedItem = document.querySelector('#event_commission').selectedOptions[0];
    const commission = encodeURIComponent(selectedItem.value);

    // encadrements
    fetch('/encadrement-par-commission?commission=' + commission, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.text())
    .then(html => {
        const container = document.querySelector('#individus');
        container.innerHTML = html;
        initParticipantsCheckboxes(container);
    });
}

window.switchCommission = switchCommission;
