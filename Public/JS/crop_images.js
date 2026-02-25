const image = document.getElementById('image-to-crop');
const cropButton = document.getElementById('btn-crop');
const aspectSelect = document.getElementById('aspect');
const sizeSelect = document.getElementById('size');

const progressContainer = document.getElementById('progress-container');
const progressText = document.getElementById('progress-text');
const progressBarFill = document.getElementById('progress-bar-fill');
const warnings = document.getElementById('warnings');

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
        progressContainer.style.display = 'block';
        warnings.textContent = translations.crop_warning_large;
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

    progressContainer.style.display = 'block';
    progressBarFill.style.backgroundColor = "#006DB7"; 

    if (cropWidth < minSize || cropHeight < minSize) {
        progressText.textContent = translations.crop_error_small;
        progressText.style.color = "#E3000B";
        progressBarFill.style.width = "100%";
        progressBarFill.style.backgroundColor = "#E3000B";
        return; 
    }

    progressText.textContent = translations.crop_processing;
    progressText.style.color = "#333"; 
    warnings.textContent = "";
    cropButton.disabled = true;
    
    progressBarFill.style.width = "100%";
    progressBarFill.classList.add('processing');

    const boardSize = parseInt(sizeSelect.value);
    const canvasData = cropper.getCroppedCanvas({
        width: cropWidth,
        height: cropHeight
    });

    if (!canvasData) {
        showError(translations.crop_prep_error);
        return;
    }

    canvasData.toBlob(blob => {
        const formData = new FormData();
        formData.append('cropped_image', blob, 'cropped.png');
    
        const originalName = image.getAttribute('alt') || 'image';
        formData.append('original_name', originalName);

        const imageId = image.getAttribute('data-id');
        if (imageId) { formData.append('image_id', imageId); }

        formData.append('size', boardSize); 

        fetch('cropImages/process', { 
            method: 'POST',
            body: formData
        })
        .then(res => {
            if (!res.ok) throw new Error(translations.crop_server_error + " (code " + res.status + ")");
            return res.json();
        })
        .then(data => {
            if (data.status === 'success') {
                progressBarFill.classList.remove('processing');
                progressText.textContent = translations.crop_success;
                progressText.style.color = "#006DB7";
                window.location.href = "reviewImages?img=" + encodeURIComponent(data.file);
            } else {
                showError(translations.error + ": " + (data.message || translations.crop_unknown_error));
            }
        })
        .catch(err => {
            console.error(err);
            showError(translations.error + ": " + err.message + ". " + translations.crop_check_model);
        });
    }, 'image/png');
});

function showError(msg) {
    progressText.textContent = msg;
    progressText.style.color = "#E3000B";
    progressBarFill.classList.remove('processing');
    progressBarFill.style.backgroundColor = "#E3000B";
    cropButton.disabled = false;
}