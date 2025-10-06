class UploadAdapter {
    constructor(loader) {
        this.loader = loader;
    }

    upload() {
        return this.loader.file
            .then(file => new Promise((resolve, reject) => {
                const data = new FormData();
                data.append('file', file);

                fetch('/upload-image', {
                    method: 'POST',
                    body: data
                })
                    .then(response => response.json())
                    .then(result => {
                        if (result.uploaded) {
                            resolve({
                                default: result.url
                            });
                        } else {
                            reject(result.error && result.error.message ? result.error.message : 'Upload error');
                        }
                    })
                    .catch(err => reject('Upload failed: ' + err));
            }));
    }
}
