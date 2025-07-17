import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

document.querySelectorAll('.ckeditor').forEach((element) => {
    ClassicEditor
        .create(element)
        .catch(error => {
            console.error(error);
        });
});
