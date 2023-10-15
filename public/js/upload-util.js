function onUploadFieldChange(field) {
    const filenameTarget = field.parentNode.querySelector('.justification-filename');
    const filenameSpanTarget = filenameTarget.querySelector('span');
    const uploadControlsTarget = document.querySelector('#justification-control-' + field.dataset.utilId);
    
    if (field.files.length === 0) {
        filenameTarget.style.display = 'none';
        uploadControlsTarget.style.display = 'block';
        return;
    }
    const filename = field.files[0].name;
    filenameSpanTarget.innerText = filename;
    filenameTarget.style.display = 'block';
    uploadControlsTarget.style.display = 'none';
}

function clearUploadField(id) {
    const field = document.querySelector('#upload-field-' + id);
    field.value = null;
    onUploadFieldChange(field);
}

function onExpenseReportFormChange(){
    document.querySelector('#expense-report-summary ').style.display = 'block';
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.ndf-form-wrapper input').forEach(function(field) {
        field.addEventListener('change', onUploadFieldChange);
    });
});
