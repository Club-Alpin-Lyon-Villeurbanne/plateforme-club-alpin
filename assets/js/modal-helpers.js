import MicroModal from 'micromodal';

/**
 * Helpers pour remplacer toutes les utilisations Fancybox
 * Basé sur vos 3 patterns détectés: fancybox, fancyframe, fancyframeadmin
 */

/**
 * Remplace $.fancybox() simple
 */
export function showModal(content, title = '') {
    document.getElementById('modal-1-title').textContent = title;
    document.getElementById('modal-1-content').innerHTML = content;
    MicroModal.show('modal-1');
}

/**
 * Remplace $.fancybox() avec iframe (fancyframe)
 */
export function showModalFrame(url, title = '', width = 950, height = '80%') {
    const iframe = `<iframe src="${url}" width="100%" height="${height}" frameborder="0" style="min-height: 500px; border-radius: 4px;"></iframe>`;
    
    document.getElementById('modal-1-title').textContent = title;
    document.getElementById('modal-1-content').innerHTML = iframe;
    
    // Ajuster taille container pour iframe
    const container = document.querySelector('.modal__container');
    container.style.width = `${width}px`;
    container.style.maxWidth = '95vw';
    
    MicroModal.show('modal-1');
}

/**
 * Remplace confirmations Fancybox
 */
export function showConfirmation(message, onConfirm, onCancel = null, title = 'Confirmation') {
    const content = `
        <div style="text-align: center; padding: 20px;">
            <p style="margin-bottom: 30px; font-size: 16px; line-height: 1.5;">${message}</p>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="confirm-yes" style="
                    background: #dc3545; 
                    color: white; 
                    padding: 12px 24px; 
                    border: none; 
                    border-radius: 4px; 
                    cursor: pointer;
                    font-size: 14px;
                    font-weight: bold;
                ">
                    Confirmer
                </button>
                <button id="confirm-no" style="
                    background: #6c757d; 
                    color: white; 
                    padding: 12px 24px; 
                    border: none; 
                    border-radius: 4px; 
                    cursor: pointer;
                    font-size: 14px;
                ">
                    Annuler
                </button>
            </div>
        </div>
    `;
    
    document.getElementById('modal-1-title').textContent = title;
    document.getElementById('modal-1-content').innerHTML = content;
    
    // Reset container style
    const container = document.querySelector('.modal__container');
    container.style.width = '';
    container.style.maxWidth = '500px';
    
    MicroModal.show('modal-1');
    
    // Event listeners pour boutons
    document.getElementById('confirm-yes').addEventListener('click', function() {
        MicroModal.close('modal-1');
        if (onConfirm) onConfirm();
    });
    
    document.getElementById('confirm-no').addEventListener('click', function() {
        MicroModal.close('modal-1');
        if (onCancel) onCancel();
    });
}

/**
 * Fermer modal (remplace $.fancybox.close())
 */
export function closeModal() {
    MicroModal.close('modal-1');
}

/**
 * Auto-initialiser les liens avec classes Fancybox
 * Remplace $("a.fancybox").fancybox() etc.
 */
export function initFancyboxReplacements() {
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

// Expose les fonctions globalement
window.showModal = showModal;
window.closeModal = closeModal;