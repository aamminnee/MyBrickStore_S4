document.addEventListener("DOMContentLoaded", () => {
    const dropArea = document.getElementById('drop-zone');
    const input = document.getElementById('file-upload');
    const form = dropArea ? dropArea.closest('form') : null;
    const actionArea = document.getElementById('action-area');

    window.addEventListener('dragover', e => e.preventDefault(), false);
    window.addEventListener('drop', e => e.preventDefault(), false);

    if (!dropArea || !input) {
        console.error("erreur critique : la zone de drop ou l'input est introuvable.");
        return;
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.add('dragover'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => dropArea.classList.remove('dragover'), false);
    });

    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) handleFiles(files);
    });

    dropArea.addEventListener('click', () => input.click());

    input.addEventListener('change', function() {
        if (this.files.length > 0) handleFiles(this.files);
    });

    window.addEventListener('paste', (e) => {
        if (e.clipboardData && e.clipboardData.files.length > 0) {
            e.preventDefault();
            handleFiles(e.clipboardData.files);
        }
    });

    function handleFiles(files) {
        const file = files[0];
        if (file.type.startsWith('image/')) {
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
            
            previewFile(file);
        } else {
            alert("ce n'est pas une image valide !");
        }
    }

    function previewFile(file) {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onloadend = function() {
            const img = document.createElement('img');
            img.src = reader.result;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '400px';
            img.style.objectFit = 'contain';
            img.style.borderRadius = '12px';

            dropArea.innerHTML = '';
            dropArea.appendChild(img); 
            dropArea.classList.add('has-image'); 

            if (actionArea) {
                actionArea.classList.remove('hidden');
            }
        }
    }

    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (input.files.length === 0) {
                alert("veuillez sélectionner une image.");
                return;
            }

            const formData = new FormData(form);
            const btn = form.querySelector('button[type="submit"]');
            const oldText = btn.innerText;
            btn.innerText = "envoi...";
            btn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Image envoyée !',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = data.redirect || "cropImages";
                    });

                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Oups...',
                        text: data.message,
                        confirmButtonText: 'Compris',
                        confirmButtonColor: '#3085d6',
                        footer: data.redirect ? '<a href="' + data.redirect + '">Se connecter maintenant</a>' : null
                    }).then((result) => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        }
                    });
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire({
                    icon: 'error',
                    title: 'Erreur technique',
                    text: "Impossible de contacter le serveur."
                });
            })
            .finally(() => {
                btn.innerText = oldText;
                btn.disabled = false;
            });
        });
    }
});