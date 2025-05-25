import { modal } from './modal/index';

// Initialisation au chargement du DOM
$(document).ready(function() {
    // Toggle de fieldset
    $('.legendToggle').each(function() {
        $(this).click(function() {
            const toggleZone = $(this).next();
            if (toggleZone && toggleZone.hasClass('toggleZone')) {
                toggleZone.css('display', toggleZone.css('display') === 'none' ? 'block' : 'none');
            }
        });
    });

    // Loading sur formulaires
    // Version jQuery 1.8 (actuelle)
    $("form.loading").submit(function() {
        $("#loading1").fadeIn('fast');
        $("#loading2").fadeIn('fast');
    });

    // Version moderne (future)
    /*
    document.querySelectorAll('form.loading').forEach(form => {
        form.addEventListener('submit', function() {
            const loading1 = document.getElementById('loading1');
            const loading2 = document.getElementById('loading2');
            
            if (loading1) loading1.style.display = 'block';
            if (loading2) loading2.style.display = 'block';
        });
    });
    */

    // Fermeur de messages
    $('.msgCloser').each(function() {
        $(this).click(function() {
            $(this).parent().css('display', 'none');
        });
    });

    // Protection des emails
    $('span.mailme').each(function() {
        const text = $(this).text();
        const email = text.replace(/ at /, '@').replace(/ dot /g, '.');
        const link = $('<a>').attr({
            href: 'mailto:' + email,
            title: 'Envoyer un email',
            text: email
        });
        $(this).parent().append(link);
        $(this).remove();
    });

    // Gestion des liens blank (pour les alertes navigateur)
    // Version jQuery 1.8 (actuelle)
    $('a.blank').mouseenter(function() {
        $(this).attr("target", "_blank");
    });
    $('a.blank').mouseleave(function() {
        $(this).removeAttr("target");
    });

    // Version moderne (future)
    /*
    document.querySelectorAll('a.blank').forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.target = '_blank';
        });
        link.addEventListener('mouseleave', function() {
            this.removeAttribute('target');
        });
    });
    */

    // Initialiser les remplacements Fancybox
    modal.initFancyboxReplacements();
}); 