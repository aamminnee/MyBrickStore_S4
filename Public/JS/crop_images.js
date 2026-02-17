const image = document.getElementById('image-to-crop');
const cropButton = document.getElementById('btn-crop');
const aspectSelect = document.getElementById('aspect');
const sizeSelect = document.getElementById('size');

let message = document.getElementById('message');
let warnings = document.getElementById('warnings');

if (!message) {
    message = document.createElement('div');
    message.id = 'message';
    message.style.textAlign = 'center';
    message.style.marginTop = '10px';
    message.style.fontWeight = 'bold';
    cropButton.parentElement.insertBefore(message, cropButton);
}

if (!warnings) {
    warnings = document.createElement('div');
    warnings.id = 'warnings';
    warnings.style.color = '#e67e22';
    warnings.style.textAlign = 'center';
    warnings.style.marginBottom = '10px';
    message.parentElement.insertBefore(warnings, message);
}

let cropper = new Cropper(image, {
    aspectRatio: 1,
    viewMode: 1,
    background: false,
    autoCropArea: 1,
    ready() {
        const initialAspect = parseFloat(aspectSelect.value);
        this.cropper.setAspectRatio(initialAspect);
    }
});

image.addEventListener('load', () => {
    if (image.naturalWidth > 3000 || image.naturalHeight > 3000) {
        warnings.textContent = "L'image est très grande, les performances peuvent être réduites.";
    }
});

aspectSelect.addEventListener('change', () => {
    const value = parseFloat(aspectSelect.value);
    cropper.setAspectRatio(value);
});

cropButton.addEventListener('click', () => {
    const cropData = cropper.getData(true);
    const cropWidth = Math.round(cropData.width);
    const cropHeight = Math.round(cropData.height);

    const minSize = 50; 

    if (cropWidth < minSize || cropHeight < minSize) {
        message.textContent = "Erreur : la zone sélectionnée est trop petite.";
        message.style.color = "#E3000B";
        return; 
    }

    message.textContent = "Traitement en cours...";
    message.style.color = "#333"; 
    warnings.textContent = "";
    cropButton.disabled = true;

    const boardSize = parseInt(sizeSelect.value);

    const canvasData = cropper.getCroppedCanvas({
        width: cropWidth,
        height: cropHeight
    });

    if (!canvasData) {
        message.textContent = "Erreur lors de la génération de l'image.";
        cropButton.disabled = false;
        return;
    }

    canvasData.toBlob(blob => {
        const formData = new FormData();
        formData.append('cropped_image', blob, 'cropped.png');
    
        const originalName = image.getAttribute('alt') || 'image';
        formData.append('original_name', originalName);

        const imageId = image.getAttribute('data-id');
        if (imageId) {
            formData.append('image_id', imageId);
        }

        formData.append('size', boardSize); 

        fetch('cropImages/process', { 
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (!res.ok) {
                throw new Error("Erreur serveur (code " + res.status + ")");
            }
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                message.textContent = "Image recadrée avec succès !";
                message.style.color = "#006DB7";
                window.location.href = "reviewImages?img=" + encodeURIComponent(data.file);
            } else {
                message.textContent = "Erreur : " + (data.message || "Erreur inconnue");
                message.style.color = "#E3000B";
                cropButton.disabled = false;
            }
        })
        .catch(err => {
            console.error(err);
            message.textContent = "Erreur : " + err.message + ". Vérifiez ImagesModel.php.";
            message.style.color = "#E3000B";
            cropButton.disabled = false;
        });
    }, 'image/png');
});