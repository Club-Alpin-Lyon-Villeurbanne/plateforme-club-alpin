// Fonction pour afficher une modale
function showModal(content) {
    // Créer l'élément modale s'il n'existe pas
    if (!document.getElementById('modal-container')) {
        const modalContainer = document.createElement('div');
        modalContainer.id = 'modal-container';
        modalContainer.className = 'modal-container';
        document.body.appendChild(modalContainer);
    }

    // Créer le contenu de la modale
    const modalContent = document.createElement('div');
    modalContent.className = 'modal-content';
    modalContent.innerHTML = content;

    // Ajouter le bouton de fermeture
    const closeButton = document.createElement('button');
    closeButton.className = 'modal-close';
    closeButton.innerHTML = '×';
    closeButton.onclick = closeModal;
    modalContent.appendChild(closeButton);

    // Afficher la modale
    const modalContainer = document.getElementById('modal-container');
    modalContainer.innerHTML = '';
    modalContainer.appendChild(modalContent);
    modalContainer.style.display = 'flex';
}

// Fonction pour fermer la modale
function closeModal() {
    const modalContainer = document.getElementById('modal-container');
    if (modalContainer) {
        modalContainer.style.display = 'none';
    }
}

// Fermer la modale en cliquant en dehors
document.addEventListener('click', function(event) {
    const modalContainer = document.getElementById('modal-container');
    if (modalContainer && event.target === modalContainer) {
        closeModal();
    }
});

// Fermer la modale avec la touche Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeModal();
    }
}); 