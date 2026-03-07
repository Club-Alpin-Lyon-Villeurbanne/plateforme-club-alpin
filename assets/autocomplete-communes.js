function searchCommunes(context) {
    const field = document.getElementById('event_place');
    const list = document.getElementById("place_suggestions");
    const container = field ? field.closest('[data-start-lat-field][data-start-long-field]') || field.parentElement : null;
    let timer;

    field.addEventListener('keyup', (e) => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const val = field.value.toLowerCase();
            list.innerHTML = '';

            if (val.length >= 3) {
                fetch('/commune/autocompletion', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        query: val
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        list.innerHTML = "";
                        data.forEach(item => {
                            let liContentText = item. codePostal + ' ' + item.nomCommune;
                            if ('' !== item.ligne5) {
                                liContentText += ' (' + item.ligne5 + ')';
                            }
                            const li = document.createElement("li");

                            li.textContent = liContentText;
                            li.style.cursor = "pointer";
                            li.onclick = () => {
                                field.value = liContentText;
                                list.innerHTML = '';

                                // Remplir les champs cachés latDepart et longDepart
                                const startLatSelector = container ? container.getAttribute('data-start-lat-field') : null;
                                const startLongSelector = container ? container.getAttribute('data-start-long-field') : null;
                                const startLatField = startLatSelector ? document.getElementById(startLatSelector) : document.getElementById('event_latDepart');
                                const startLongField = startLongSelector ? document.getElementById(startLongSelector) : document.getElementById('event_longDepart');
                                if (startLatField && item.latitude) {
                                    startLatField.value = item.latitude;
                                }
                                if (startLongField && item.longitude) {
                                    startLongField.value = item.longitude;
                                }
                            };
                            list.appendChild(li);
                        });
                    })
                ;
            }
        }, 300);
    });

    context.addEventListener("click", e => {
        if (e.target !== field) list.innerHTML = '';
    });
}

window.searchCommunes = searchCommunes;
