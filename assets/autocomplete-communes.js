function searchCommunes(context) {
    const field = document.getElementById('event_place');
    const list = document.getElementById('place_suggestions');
    const feedback = document.getElementById('place_feedback');
    if (!field || !list) {
        return;
    }

    let timer;
    let items = [];            // suggestions courantes : [{label}]
    let activeIndex = -1;      // élément surligné au clavier
    let selectedLabel = '';    // libellé confirmé (vide tant qu'aucune commune n'est validée)

    field.setAttribute('role', 'combobox');
    field.setAttribute('aria-autocomplete', 'list');
    field.setAttribute('aria-expanded', 'false');
    field.setAttribute('aria-controls', 'place_suggestions');
    list.setAttribute('role', 'listbox');

    function setFeedback(state) {
        // état d'erreur visuel sur l'input (bordure rouge) hors cas « ok »/vide
        field.classList.toggle('place-input--error', state === 'todo' || state === 'required');
        if (!feedback) {
            return;
        }
        if (state === 'ok') {
            feedback.textContent = '✓ Commune reconnue';
            feedback.className = 'place-feedback place-feedback--ok';
        } else if (state === 'todo') {
            feedback.textContent = 'Veuillez choisir une commune dans la liste de suggestions.';
            feedback.className = 'place-feedback place-feedback--todo';
        } else if (state === 'required') {
            feedback.textContent = 'Le lieu de départ est obligatoire : choisissez une commune dans la liste.';
            feedback.className = 'place-feedback place-feedback--todo';
        } else {
            feedback.textContent = '';
            feedback.className = 'place-feedback';
        }
    }

    function closeList() {
        list.innerHTML = '';
        list.style.display = 'none';
        field.setAttribute('aria-expanded', 'false');
        field.removeAttribute('aria-activedescendant');
        activeIndex = -1;
        items = [];
    }

    function highlight(index) {
        const options = list.querySelectorAll('li');
        options.forEach((li, i) => {
            const isActive = i === index;
            li.classList.toggle('is-active', isActive);
            if (isActive) {
                li.setAttribute('aria-selected', 'true');
                field.setAttribute('aria-activedescendant', li.id);
                li.scrollIntoView({ block: 'nearest' });
            } else {
                li.removeAttribute('aria-selected');
            }
        });
        activeIndex = index;
    }

    function selectItem(item) {
        field.value = item.label;
        selectedLabel = item.label;
        closeList();
        setFeedback('ok');
    }

    function renderList() {
        list.innerHTML = '';
        items.forEach((item, i) => {
            const li = document.createElement('li');
            li.id = 'place_suggestion_' + i;
            li.textContent = item.label;
            li.setAttribute('role', 'option');
            li.style.cursor = 'pointer';
            // mousedown plutôt que click : se déclenche avant le blur du champ
            li.addEventListener('mousedown', (e) => {
                e.preventDefault();
                selectItem(item);
            });
            li.addEventListener('mouseenter', () => highlight(i));
            list.appendChild(li);
        });
        const hasItems = items.length > 0;
        list.style.display = hasItems ? 'block' : 'none';
        if (hasItems) {
            // ancrer le dropdown juste sous l'input (et non sous le texte d'aide).
            // Position calculée par rapport au wrapper .place-autocomplete (position: relative),
            // robuste quelle que soit la structure interne du form_row.
            const wrapRect = list.parentElement.getBoundingClientRect();
            const inputRect = field.getBoundingClientRect();
            list.style.top = (inputRect.bottom - wrapRect.top) + 'px';
            list.style.left = (inputRect.left - wrapRect.left) + 'px';
            list.style.width = inputRect.width + 'px';
        }
        field.setAttribute('aria-expanded', hasItems ? 'true' : 'false');
        activeIndex = -1;
    }

    // Confirme une valeur déjà présente dans le champ (duplication, ou ré-affichage du
    // formulaire après une erreur de validation sur un autre champ) si elle correspond
    // exactement à une commune du référentiel — même logique que le matching serveur.
    function verifyPrefilled(value) {
        const codePostal = value.match(/^\s*(\d{5})/);
        if (!codePostal) {
            return;
        }
        fetch('/commune/autocompletion', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                query: codePostal[1],
            }),
        })
            .then((response) => (response.ok ? response.json() : []))
            .then((data) => {
                const exists = (Array.isArray(data) ? data : []).some((item) => item.label === value);
                // ne confirmer que si l'utilisateur n'a pas modifié le champ entre-temps
                if (exists && field.value.trim() === value) {
                    selectedLabel = value;
                    setFeedback('ok');
                }
            })
            .catch(() => {});
    }

    let inFlight;
    function fetchSuggestions(val) {
        // annule la requête précédente pour garantir un affichage déterministe
        // (une réponse lente antérieure ne doit pas écraser une plus récente)
        if (inFlight) {
            inFlight.abort();
        }
        inFlight = new AbortController();
        fetch('/commune/autocompletion', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                query: val,
            }),
            signal: inFlight.signal,
        })
            .then((response) => (response.ok ? response.json() : []))
            .then((data) => {
                items = Array.isArray(data) ? data : [];
                renderList();
            })
            .catch((error) => {
                if (error && error.name === 'AbortError') {
                    return; // requête remplacée par une plus récente
                }
                // échec réseau / réponse non-JSON : on referme proprement
                closeList();
            });
    }

    field.addEventListener('input', () => {
        clearTimeout(timer);
        // toute frappe qui s'écarte de la sélection confirmée invalide l'état « ✓ »
        if (field.value.trim() !== selectedLabel) {
            setFeedback('');
        }
        timer = setTimeout(() => {
            const val = field.value.toLowerCase();
            if (val.length >= 3) {
                fetchSuggestions(val);
            } else {
                closeList();
            }
        }, 300);
    });

    field.addEventListener('keydown', (e) => {
        if (list.style.display === 'none' || items.length === 0) {
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            highlight((activeIndex + 1) % items.length);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            highlight((activeIndex - 1 + items.length) % items.length);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0) {
                e.preventDefault();
                selectItem(items[activeIndex]);
            }
        } else if (e.key === 'Escape') {
            closeList();
        }
    });

    field.addEventListener('blur', () => {
        // léger délai pour laisser un éventuel mousedown sur une suggestion s'exécuter
        setTimeout(() => {
            const val = field.value.trim();
            if (val !== '' && val !== selectedLabel) {
                setFeedback('todo');
            } else if (val === '') {
                setFeedback('');
            }
        }, 150);
    });

    context.addEventListener('click', (e) => {
        if (e.target !== field && !list.contains(e.target)) {
            closeList();
        }
    });

    // garde à la soumission : tant qu'aucune commune de la liste n'a été confirmée,
    // on bloque l'envoi et on affiche l'erreur côté client.
    if (field.form) {
        field.form.addEventListener('submit', (e) => {
            // les brouillons (bouton eventDraftSave) sont dispensés de la validation stricte
            const submitter = e.submitter;
            if (submitter && /eventDraftSave/.test(submitter.name || submitter.id || '')) {
                return;
            }
            // sortie à l'étranger : la commune de départ est facultative
            const etrangerField = document.getElementById('event_etranger');
            if (etrangerField && etrangerField.checked) {
                return;
            }
            const val = field.value.trim();
            if (val === '' || val !== selectedLabel) {
                e.preventDefault();
                e.stopPropagation();
                setFeedback(val === '' ? 'required' : 'todo');
                closeList();
                field.focus();
                field.scrollIntoView({ block: 'center' });
            }
        });
    }

    // valeur pré-remplie (édition, duplication, ré-affichage après erreur) : on la confirme
    // si — et seulement si — elle correspond exactement à une commune du référentiel.
    if (field.value.trim() !== '') {
        verifyPrefilled(field.value.trim());
    }

    // sortie à l'étranger : on désactive le champ commune (facultatif) et on le vide
    const etrangerField = document.getElementById('event_etranger');
    if (etrangerField) {
        const latDepart = document.getElementById('event_latDepart');
        const longDepart = document.getElementById('event_longDepart');
        const defaultPlaceholder = field.placeholder;
        const syncEtranger = () => {
            const abroad = etrangerField.checked;
            field.disabled = abroad;
            field.placeholder = abroad ? 'Non requis pour une sortie à l\'étranger' : defaultPlaceholder;
            if (abroad) {
                field.value = '';
                selectedLabel = '';
                closeList();
                setFeedback('');
                // 0 = pas de départ (sentinelle numérique, pas '')
                if (latDepart) {
                    latDepart.value = '0';
                }
                if (longDepart) {
                    longDepart.value = '0';
                }
            }
        };
        etrangerField.addEventListener('change', syncEtranger);
        syncEtranger();
    }
}

window.searchCommunes = searchCommunes;
