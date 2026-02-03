function searchCommunes(context) {
    const field = document.getElementById('event_place');
    const list = document.getElementById("place_suggestions");
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

                                // Remplir les champs cachés startLat et startLong
                                const startLatField = document.getElementById('event_startLat');
                                const startLongField = document.getElementById('event_startLong');
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
