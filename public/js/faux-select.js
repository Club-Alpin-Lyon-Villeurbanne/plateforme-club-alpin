$(function() {

    // Faux select (ex : page agenda)
    // La liste s'ouvre au survol de la souris, au focus clavier ou au toucher du choix courant.
    // Elle se ferme quand on choisit une option, qu'on touche ailleurs, qu'on presse Échap,
    // que la souris quitte le sélecteur ou que le focus clavier en sort.
    var $selects = $('.faux-select');

    function options($select) {
        return $select.find('a').not('.up');
    }

    function ouvrir($select) {
        $selects.not($select).each(function() {
            fermer($(this));
        });
        $select.addClass('faux-select-ouvert');
        options($select).stop(true, true).fadeIn({duration: 100, queue: false});
    }

    function fermer($select) {
        if (!$select.hasClass('faux-select-ouvert')) {
            return;
        }
        $select.removeClass('faux-select-ouvert');
        options($select).stop(true, true).fadeOut({duration: 100, queue: false});
    }

    function estSouris(event) {
        return event.originalEvent && event.originalEvent.pointerType === 'mouse';
    }

    $selects.each(function() {
        options($(this)).hide();
    });

    // Survol à la souris (ordinateur) : on ignore les pointeurs tactiles,
    // dont les événements simulés refermaient la liste avant le toucher de l'option.
    $selects.on('pointerenter', function(event) {
        if (estSouris(event)) {
            ouvrir($(this));
        }
    });
    $selects.on('pointerleave', function(event) {
        if (estSouris(event)) {
            fermer($(this));
        }
    });

    // Toucher ou clic sur le choix courant : ouvre la liste sans recharger la page.
    $selects.on('click', 'a.up', function(event) {
        event.preventDefault();
        ouvrir($(event.delegateTarget));
    });

    // Clavier : le focus ouvre la liste, la sortie du focus ou Échap la referme.
    var retourFocusApresEchap = false;
    $selects.on('focusin', function() {
        if (retourFocusApresEchap) {
            retourFocusApresEchap = false;
            return;
        }
        ouvrir($(this));
    });
    $selects.on('focusout', function() {
        var $select = $(this);
        setTimeout(function() {
            if (!$.contains($select[0], document.activeElement)) {
                fermer($select);
            }
        }, 0);
    });
    $selects.on('keydown', function(event) {
        if (event.key === 'Escape' || event.key === 'Esc') {
            var $courant = $(this).find('a.up');
            fermer($(this));
            if (document.activeElement !== $courant[0]) {
                retourFocusApresEchap = true;
                $courant.trigger('focus');
            }
        }
    });

    // Toucher ou clic en dehors d'un sélecteur : referme les listes ouvertes.
    $(document).on('pointerdown', function(event) {
        $selects.each(function() {
            if (!$.contains(this, event.target)) {
                fermer($(this));
            }
        });
    });
});
