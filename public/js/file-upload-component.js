document.addEventListener('DOMContentLoaded', function() {
    const imageUploadComponents = document.querySelectorAll('.image-upload-component');
    
    imageUploadComponents.forEach(component => {
        initImageUploadComponent(component);
    });
    
    function initImageUploadComponent(component) {
        const uploadUrl = component.dataset.uploadUrl;
        const mediaIdInputId = component.dataset.mediaIdInput;
        const maxFileSize = parseInt(component.dataset.maxFileSize) || 5;
        
        const fileInput = component.querySelector('input[type="file"]');
        const previewDiv = component.querySelector('.upload-preview');
        const loadingIndicator = component.querySelector('[id$="-loading-indicator"]');
        const templateId = fileInput.id.replace('-file-input', '-image-preview-template');
        const template = document.getElementById(templateId);
        
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            
            if (file.size > maxFileSize * 1024 * 1024) {
                alert(`Le fichier est trop volumineux. La taille maximale est de ${maxFileSize} Mo.`);
                this.value = '';
                return;
            }
            
            const formData = new FormData();
            formData.append('file', file);
            
            loadingIndicator.style.display = 'flex';
            
            const placeholder = component.querySelector('.upload-placeholder');
            if (placeholder) placeholder.style.display = 'none';
            
            const existingImage = component.querySelector('.preview-image');
            if (existingImage) existingImage.style.display = 'none';
            
            const existingOverlay = component.querySelector('.preview-overlay');
            if (existingOverlay) existingOverlay.style.display = 'none';
            
            fetch(uploadUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du téléchargement');
                }
                return response.json();
            })
            .then(data => {
                if (mediaIdInputId) {
                    const mediaIdInput = document.getElementById(mediaIdInputId);
                    if (mediaIdInput) {
                        mediaIdInput.value = data.id;
                    }
                }
                
                loadingIndicator.style.display = 'none';
                
                if (placeholder) {
                    placeholder.remove();
                }
                
                if (existingImage) {
                    existingImage.src = data.url;
                    existingImage.style.display = 'block';
                    if (existingOverlay) {
                        existingOverlay.style.display = 'flex';
                    }
                } else {
                    const clone = document.importNode(template.content, true);
                    
                    const img = clone.querySelector('.preview-image');
                    img.src = data.url;
                    
                    previewDiv.appendChild(clone);
                }
                
                const overlay = previewDiv.querySelector('.preview-overlay');
                overlay.style.opacity = '0';
                if (overlay) {
                    previewDiv.addEventListener('mouseenter', function() {
                        overlay.style.opacity = '1';
                    });
                    previewDiv.addEventListener('mouseleave', function() {
                        overlay.style.opacity = '0';
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                loadingIndicator.style.display = 'none';
                
                if (placeholder) placeholder.style.display = 'block';
                if (existingImage) {
                    existingImage.style.display = 'block';
                    if (existingOverlay) existingOverlay.style.display = 'flex';
                }
                
                alert('Erreur lors du téléchargement du fichier. Veuillez réessayer.');
            });
        });
    }
}); 