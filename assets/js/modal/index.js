import '../../css/micromodal.css';
import MicroModal from 'micromodal';

class Modal {
    constructor() {
        console.log('Initializing Modal class');
        this.init();
    }

    init() {
        console.log('Setting up MicroModal');
        MicroModal.init({
            disableScroll: true,
            disableFocus: false,
            awaitOpenAnimation: false,
            awaitCloseAnimation: false,
            debugMode: true
        });
    }
    
    show(content, title = '') {
        console.log('Showing modal with content:', content, 'and title:', title);
        document.getElementById('modal-1-title').textContent = title;
        document.getElementById('modal-1-content').innerHTML = content;
        MicroModal.show('modal-1');
    }

    showFrame(url, title = '', width = 950, height = '80%') {
        console.log('Showing modal frame with URL:', url, 'and title:', title);
        const iframe = `<iframe src="${url}" width="100%" height="${height}" frameborder="0" style="min-height: 500px; border-radius: 4px;"></iframe>`;
        
        const modalElement = document.getElementById('modal-1');
        const titleElement = document.getElementById('modal-1-title');
        const contentElement = document.getElementById('modal-1-content');
        
        console.log('Modal element:', modalElement);
        console.log('Title element:', titleElement);
        console.log('Content element:', contentElement);
        
        if (!modalElement || !titleElement || !contentElement) {
            console.error('Modal elements not found in DOM');
            return;
        }
        
        titleElement.textContent = title;
        contentElement.innerHTML = iframe;
        
        // Ajuster taille container pour iframe
        const container = document.querySelector('.modal__container');
        if (container) {
            container.style.width = `${width}px`;
            container.style.maxWidth = '95vw';
        } else {
            console.error('Modal container not found');
        }
        
        console.log('Showing modal...');
        MicroModal.show('modal-1');
        
        // Vérifier si la classe is-open est ajoutée
        setTimeout(() => {
            console.log('Modal classes after show:', modalElement.classList.toString());
        }, 100);
    }

    confirm(message, onConfirm, onCancel = null, title = 'Confirmation') {
        console.log('Showing confirmation modal with message:', message);
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

    close() {
        console.log('Closing modal');
        MicroModal.close('modal-1');
    }

    initFancyboxReplacements() {
        console.log('Initializing Fancybox replacements');
        // Remplacer a.fancybox
        document.querySelectorAll('a.fancybox').forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('Fancybox link clicked:', this);
                e.preventDefault();
                const title = this.getAttribute('title') || '';
                const content = this.getAttribute('href');
                console.log('Opening modal with content:', content, 'and title:', title);
                this.show(content, title);
            });
        });
        
        // Remplacer a.fancyframe
        document.querySelectorAll('a.fancyframe').forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('Fancyframe link clicked:', this);
                e.preventDefault();
                const url = this.getAttribute('href');
                const title = this.getAttribute('title') || '';
                console.log('Opening modal with URL:', url, 'and title:', title);
                this.showFrame(url, title, 950, '80%');
            });
        });
        
        // Remplacer a.fancyframeadmin  
        document.querySelectorAll('a.fancyframeadmin').forEach(link => {
            link.addEventListener('click', function(e) {
                console.log('Fancyframeadmin link clicked:', this);
                e.preventDefault();
                const url = this.getAttribute('href');
                const title = this.getAttribute('title') || 'Administration';
                
                // Version admin = pas de fermeture sur overlay
                this.showFrame(url, title, 950, '98%');
                
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
}

// Créer et exporter l'instance
const modal = new Modal();

// Exposer l'instance globalement
window.modal = modal;

// Exporter aussi pour les imports ES6
export { modal }; 