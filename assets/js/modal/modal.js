import MicroModal from 'micromodal';

class Modal {
    constructor() {
        this.init();
    }

    init() {
        MicroModal.init({
            disableScroll: true,
            debugMode: true
        });
    }

    show(content, title = '') {
        document.getElementById('app-modal-title').textContent = title;
        document.getElementById('app-modal-content').innerHTML = content;
        MicroModal.show('app-modal');
    }

    close() {
        MicroModal.close('app-modal');
    }
}

const modal = new Modal();

window.modal = modal;