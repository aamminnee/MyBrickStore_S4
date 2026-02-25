function openZoomModal(imgSrc) {
    const modal = document.getElementById("image-zoom-modal");
    const modalImg = document.getElementById("zoomed-image");
    modal.style.display = "flex";
    modalImg.src = imgSrc;
}

function closeZoomModal() {
    document.getElementById("image-zoom-modal").style.display = "none";
}

document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.card-action-form');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitter = e.submitter;

            if (submitter && submitter.value === 'cart') {
                e.preventDefault(); 

                const originalText = submitter.innerHTML;

                submitter.innerHTML = translations.cart_adding;
                submitter.disabled = true;
                submitter.style.opacity = '0.8';

                const formData = new FormData(this);
                formData.append('action', 'cart'); 
                formData.append('is_ajax', 'true');

                const targetUrl = this.getAttribute('action');

                fetch(targetUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json' 
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        submitter.innerHTML = translations.cart_added;
                        submitter.classList.add('btn-success-anim');
                        submitter.style.opacity = '1';

                        const cartBadge = document.getElementById('cart-count');
                        if (cartBadge) {
                            cartBadge.innerText = data.cart_count;
                            
                            if (data.cart_count > 0) {
                                cartBadge.style.display = 'flex';
                            }

                            cartBadge.classList.add('bump');
                            setTimeout(() => {
                                cartBadge.classList.remove('bump');
                            }, 300);
                        }

                        setTimeout(() => {
                            submitter.innerHTML = originalText;
                            submitter.disabled = false;
                            submitter.classList.remove('btn-success-anim');
                        }, 2500);
                    } else {
                         submitter.innerHTML = translations.cart_error;
                         setTimeout(() => {
                             submitter.innerHTML = originalText;
                             submitter.disabled = false;
                         }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Erreur Fetch:', error);
                    submitter.innerHTML = translations.cart_error;
                    setTimeout(() => {
                        submitter.innerHTML = originalText;
                        submitter.disabled = false;
                    }, 2000);
                });
            }
        });
    });
});