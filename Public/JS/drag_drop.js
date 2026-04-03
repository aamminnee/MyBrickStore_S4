// attente du chargement du dom
document.addEventListener("DOMContentLoaded", () => {
    const dropArea = document.getElementById('drop-zone');
    const input = document.getElementById('file-upload');
    // recherche du formulaire de maniere plus robuste
    const form = input ? input.closest('form') : (dropArea ? dropArea.closest('form') : null);
    const actionArea = document.getElementById('action-area');
    
    const isDailyInput = document.getElementById('is_daily');
    const dailyBtn = document.getElementById('btn-buy-daily');

    // sauvegarde du fichier en memoire
    let currentFile = null;

    window.addEventListener('dragover', e => e.preventDefault(), false);
    window.addEventListener('drop', e => e.preventDefault(), false);

    if (!dropArea || !input) {
        console.error(typeof translations !== 'undefined' ? translations.error_critical : 'erreur critique');
        return;
    }

    // empecher le comportement par defaut
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

    // gestion des fichiers deposes
    dropArea.addEventListener('drop', async (e) => {
        const dt = e.dataTransfer;
        
        // --- debug : on affiche ce que le navigateur a recu ---
        console.log("--- evenement drop (glisser-deposer) ---");
        console.log("fichiers detectes :", dt.files.length);

        // verifier si l'image du jour a ete glissee depuis le navigateur
        let isDaily = false;
        try {
            const htmlData = dt.getData('text/html') || '';
            const uriData = dt.getData('text/uri-list') || '';
            
            // si c'est l'image du jour
            if (htmlData.includes('getDailyImage') || uriData.includes('getDailyImage') || htmlData.includes('daily-img')) {
                console.log("debug : image du jour detectee au glisser-deposer !");
                isDaily = true;
                
                // on ignore le fichier fantome et on fait une requete propre
                const fetchUrl = form ? form.action.replace('/upload', '/getDailyImage') : '/images/getDailyImage';
                try {
                    const response = await fetch(fetchUrl);
                    const blob = await response.blob();
                    const day = new Date().getDate();
                    const mimeType = blob.type || 'image/jpeg';
                    const file = new File([blob], `image_du_jour_${day}.jpg`, { type: mimeType });
                    
                    console.log("debug : image reconstruite avec succes", file.size, "octets");
                    handleFiles([file], true);
                    return; // on arrete ici pour ne pas traiter le fichier fantome
                } catch (err) {
                    console.error("debug : erreur lors de l'interception de l'image du jour :", err);
                }
            }
        } catch (err) {
            console.warn("debug : impossible de lire le presse-papier virtuel", err);
        }

        // traitement normal des autres fichiers
        if (dt.files.length > 0) {
            console.log("debug : traitement du fichier glisse", dt.files[0].name);
            handleFiles(dt.files, isDaily);
        }
    });

    // clic pour parcourir
    dropArea.addEventListener('click', () => input.click());

    // changement sur l'input
    input.addEventListener('change', function() {
        if (this.files.length > 0) handleFiles(this.files, false);
    });

    // coller une image
    window.addEventListener('paste', (e) => {
        if (e.clipboardData && e.clipboardData.files.length > 0) {
            e.preventDefault();
            handleFiles(e.clipboardData.files, false);
        }
    });

    // traitement des fichiers
    function handleFiles(files, isDaily = false) {
        const file = files[0];
        if (file && file.type.startsWith('image/')) {
            
            // blocage coté client si le fichier depasse la limite de php (ex: 15 mo)
            const tailleMaxMo = 15;
            if (file.size > tailleMaxMo * 1024 * 1024) {
                const tailleActuelle = (file.size / 1024 / 1024).toFixed(1);
                const msg = `l'image est trop lourde (${tailleActuelle} mo). le maximum autorisé est de ${tailleMaxMo} mo.`;
                console.warn("debug :", msg);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: typeof translations !== 'undefined' ? translations.error_title : 'oups...',
                        text: msg
                    });
                } else {
                    alert(msg);
                }
                return; // on stoppe le processus
            }

            currentFile = file;

            // solidification du fichier en memoire
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const blob = new Blob([e.target.result], { type: file.type || 'image/jpeg' });
                    currentFile = new File([blob], file.name || 'image_upload.jpg', { type: file.type || 'image/jpeg' });
                    console.log("debug : fichier valide en memoire :", currentFile.size, "octets");
                } catch (err) {
                    console.warn('debug : erreur de creation du fichier en memoire', err);
                }
            };
            reader.readAsArrayBuffer(file);

            try {
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                input.files = dataTransfer.files;
            } catch (err) {
                console.warn('debug : datatransfer non supporte', err);
            }

            if (isDailyInput) {
                isDailyInput.value = isDaily ? '1' : '0';
            }

            previewFile(file);
        } else {
            alert(typeof translations !== 'undefined' ? translations.invalid_image : 'image non valide');
        }
    }

    // previsualisation de l'image
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

    // bouton de l'image du jour
    if (dailyBtn) {
        dailyBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            const originalText = this.innerText;
            this.innerText = (typeof translations !== 'undefined' ? translations.sending : "chargement...");
            this.disabled = true;

            try {
                const fetchUrl = form ? form.action.replace('/upload', '/getDailyImage') : '/images/getDailyImage';
                const response = await fetch(fetchUrl);
                
                if (!response.ok) throw new Error('image non trouvee');

                const blob = await response.blob();
                const day = new Date().getDate();
                const mimeType = blob.type || 'image/jpeg';
                const file = new File([blob], `image_du_jour_${day}.jpg`, { type: mimeType });

                handleFiles([file], true);

                if (actionArea) {
                    actionArea.scrollIntoView({ behavior: 'smooth' });
                }

            } catch (error) {
                console.error(error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: typeof translations !== 'undefined' ? translations.error_title : 'oups...',
                        text: "erreur lors du chargement de l'image du jour."
                    });
                } else {
                    alert("erreur lors du chargement de l'image du jour.");
                }
            } finally {
                this.innerText = originalText;
                this.disabled = false;
            }
        });
    }

    // soumission du formulaire
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            console.log("=== tentative d'envoi (submit) ===");
            
            if (!currentFile && input.files.length === 0) {
                console.log("debug : erreur, currentfile et input.files sont vides.");
                alert(typeof translations !== 'undefined' ? translations.select_image : 'veuillez selectionner une image');
                return;
            }

            // --- detection du fichier fantome ---
            let fileToSend = currentFile || input.files[0];
            if (fileToSend && fileToSend.size === 0) {
                console.error("debug erreur bloquante : le fichier fait 0 octet ! php va le refuser.");
                alert("erreur de transfert : le fichier lu par le navigateur est vide (0 octet). essayez d'importer l'image via le bouton ou le menu parcourir.");
                return;
            }

            const formData = new FormData(form);
            const inputName = input.getAttribute('name') || 'image';
            
            formData.delete(inputName); // nettoyer pour eviter les doublons
            
            if (fileToSend) {
                formData.append(inputName, fileToSend, fileToSend.name || 'upload.jpg');
                console.log(`debug : ajout de '${inputName}' au formulaire (taille: ${fileToSend.size} octets)`);
            }

            if (isDailyInput && !formData.has(isDailyInput.name)) {
                formData.append(isDailyInput.name || 'is_daily', isDailyInput.value);
            }

            console.log("contenu final du formdata envoye au php :");
            for(let pair of formData.entries()) {
                console.log(" ->", pair[0], ":", pair[1] instanceof File ? `Fichier (${pair[1].size} octets)` : pair[1]);
            }
            console.log("=======================================");

            const btn = form.querySelector('button[type="submit"]') || document.getElementById('btn-continue');
            const oldText = btn ? btn.innerText : 'envoi...';
            if (btn) {
                btn.innerText = typeof translations !== 'undefined' ? translations.sending : 'envoi...';
                btn.disabled = true;
            }

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(async r => {
                const contentType = r.headers.get("content-type");
                if (contentType && contentType.indexOf("application/json") !== -1) {
                    return r.json();
                } else {
                    const text = await r.text();
                    console.error("debug fatal : la reponse php n'est pas du json :", text);
                    throw new Error("le serveur n'a pas renvoye de json valide.");
                }
            })
            .then(data => {
                console.log("reponse json recu :", data);
                if (data.status === 'success') {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: typeof translations !== 'undefined' ? translations.image_sent : 'image envoyee !',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            window.location.href = data.redirect || "cropImages";
                        });
                    } else {
                        window.location.href = data.redirect || "cropImages";
                    }
                } else {
                    console.warn("debug : erreur signalee par php :", data.message);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: typeof translations !== 'undefined' ? translations.error_title : 'oups...',
                            text: data.message || "erreur lors de l'envoi",
                            confirmButtonText: typeof translations !== 'undefined' ? translations.confirm_btn : 'compris',
                            confirmButtonColor: '#3085d6'
                        });
                    } else {
                        alert(data.message || "erreur lors de l'envoi");
                    }
                }
            })
            .catch(err => {
                console.error("debug crash catch :", err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: typeof translations !== 'undefined' ? translations.tech_error_title : 'erreur technique',
                        text: typeof translations !== 'undefined' ? translations.tech_error_text : "impossible de traiter la reponse du serveur. verifiez la console."
                    });
                } else {
                    alert("erreur technique lors de la communication avec le serveur.");
                }
            })
            .finally(() => {
                if (btn) {
                    btn.innerText = oldText;
                    btn.disabled = false;
                }
            });
        });
    }
});