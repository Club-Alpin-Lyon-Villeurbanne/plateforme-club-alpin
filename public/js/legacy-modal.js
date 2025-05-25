// Configuration de MicroModal
MicroModal.init({
    openTrigger: 'data-micromodal-trigger',
    closeTrigger: 'data-micromodal-close',
    disableScroll: true,
    disableFocus: false,
    awaitOpenAnimation: true,
    awaitCloseAnimation: true
});

/**
 * Affiche une modale simple
 */
function showModal(content, title = '') {
    document.getElementById('modal-1-title').textContent = title;
    document.getElementById('modal-1-content').innerHTML = content;
    MicroModal.show('modal-1');
}

/**
 * Affiche une modale avec iframe
 */
function showModalFrame(url, title = '', width = 950, height = '80%') {
    const iframe = `<iframe src="${url}" width="100%" height="${height}" frameborder="0" style="min-height: 500px; border-radius: 4px;"></iframe>`;
    showModal(iframe, title);
}

/**
 * Ferme la modale
 */
function closeModal() {
    MicroModal.close('modal-1');
}

/**
 * Auto-initialiser les liens avec classes Fancybox
 * Remplace $("a.fancybox").fancybox() etc.
 */
function initFancyboxReplacements() {
    // Remplacer a.fancybox
    document.querySelectorAll('a.fancybox').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const title = this.getAttribute('title') || '';
            const content = this.getAttribute('href');
            showModal(content, title);
        });
    });
    
    // Remplacer a.fancyframe
    document.querySelectorAll('a.fancyframe').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const title = this.getAttribute('title') || '';
            showModalFrame(url, title, 950, '80%');
        });
    });
    
    // Remplacer a.fancyframeadmin  
    document.querySelectorAll('a.fancyframeadmin').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const title = this.getAttribute('title') || 'Administration';
            
            // Version admin = pas de fermeture sur overlay
            showModalFrame(url, title, 950, '98%');
            
            // Désactiver fermeture overlay pour admin
            const overlay = document.querySelector('.modal__overlay');
            const originalHandler = overlay.onclick;
            overlay.onclick = null;
            
            // Remettre handler après fermeture
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.target.classList.contains('modal') && 
                        !mutation.target.classList.contains('is-open')) {
                        overlay.onclick = originalHandler;
                        observer.disconnect();
                    }
                });
            });
            observer.observe(document.getElementById('modal-1'), {
                attributes: true,
                attributeFilter: ['class']
            });
        });
    });
}

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', initFancyboxReplacements);

// Expose les fonctions globalement
window.showModal = showModal;
window.closeModal = closeModal;
window.showModalFrame = showModalFrame; 