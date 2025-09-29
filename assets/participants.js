// 1 seul rôle / utilisateur coché à la fois
function switchUserJoin(checkbox) {
    const typeTab = new Array('encadrants', 'initiateurs', 'coencadrants', 'benevoles');
    const tab = checkbox.getAttribute('data-id').split('_');
    const prefix = tab[1];
    const type = tab[2];
    const id = tab[4];

    // pour chaque type (ensemble de checkbox)
    let tmpType;
    for (let i = 0; i < typeTab.length; i++) {
        tmpType = typeTab[i];
        // on ne s'intéresse qu'aux autres blocs de types, pas celui qu'on parcourt
        if (type != tmpType) {
            let selector = '_' + prefix + '_' + tmpType + '_entry_' + id;
            const field = document.querySelector('[data-id="' + selector + '"]');
            if (undefined !== field && null !== field) {
                const parent = field.parentElement;

                if (checkbox.checked) {
                    field.setAttribute('disabled', 'disabled');
                    if (parent.tagName.toLowerCase() === 'label') {
                        parent.classList.add('off');
                        parent.classList.remove('up');
                        parent.classList.add('down');
                    }
                }
                // case visée décochée : affichage de ses frères dans les autres cases
                else {
                    field.removeAttribute('disabled');
                    if (parent.tagName.toLowerCase() === 'label') {
                        parent.classList.remove('off');
                    }
                }
            }
        }
    }
}

// mise en forme des checkboxes
function niceCheckbox(checkbox) {
    const parent = checkbox.parentElement;
    if (checkbox.checked) {
        if (parent.tagName.toLowerCase() === 'label') {
            parent.classList.add('up');
            parent.classList.remove('down');
        }
    }
    // case visée décochée : affichage de ses frères dans les autres cases
    else {
        if (parent.tagName.toLowerCase() === 'label') {
            parent.classList.remove('up');
            parent.classList.add('down');
        }
    }
}

function initParticipantsCheckboxes(context) {
    context.querySelectorAll('#individus input[type=checkbox]').forEach(function(elem) {
        elem.addEventListener('click', (e) => {
            niceCheckbox(elem);
            switchUserJoin(elem);
        });
        elem.addEventListener('change', (e) => {
            niceCheckbox(elem);
            switchUserJoin(elem);
        });
    });
}

window.initParticipantsCheckboxes = initParticipantsCheckboxes;
window.niceCheckbox = niceCheckbox;
