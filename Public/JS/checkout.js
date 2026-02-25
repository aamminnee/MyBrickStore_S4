function useSavedAddress() {
    const { savedAddress, i18n } = CHECKOUT_CONFIG;

    document.getElementById('first_name').value    = savedAddress.firstName;
    document.getElementById('last_name').value     = savedAddress.lastName;
    document.getElementById('address_line').value  = savedAddress.address;
    document.getElementById('zip_code').value      = savedAddress.zip;
    document.getElementById('city').value          = savedAddress.city;
    document.getElementById('phone').value         = savedAddress.phone;

    const card    = document.getElementById('saved-address-card');
    const btnText = document.getElementById('btn-use-address-text');

    card.classList.add('selected');
    btnText.innerHTML = '✓ ' + i18n.addressSelected;

    document.getElementById('checkout-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function togglePromoCode() {
    const group = document.getElementById('promo-input-group');
    const icon  = document.getElementById('promo-icon');

    if (group.classList.contains('show')) {
        group.classList.remove('show');
        icon.innerHTML = '<path d="M12 5v14m-7-7h14"></path>';
    } else {
        group.classList.add('show');
        icon.innerHTML = '<path d="M5 12h14"></path>';
        document.getElementById('promo_code').focus();
    }
}

function switchPayment(method) {
    document.querySelectorAll('.payment-tab').forEach(btn => btn.classList.remove('active'));
    document.getElementById('tab-' + method).classList.add('active');

    document.querySelectorAll('.payment-method-body').forEach(sec => sec.classList.remove('active'));
    document.getElementById('section-' + method).classList.add('active');

    const paypalInfo = document.getElementById('sandbox-paypal-info');
    const cardInfo   = document.getElementById('sandbox-card-info');

    if (method === 'paypal') {
        paypalInfo.style.display = 'block';
        cardInfo.style.display   = 'none';
    } else {
        paypalInfo.style.display = 'none';
        cardInfo.style.display   = 'block';
    }
}

function fillSavedCard() {
    const fields = {
        card_num: '4242 4242 4242 4242',
        card_exp: '12/34',
        card_cvv: '123'
    };

    for (const [id, value] of Object.entries(fields)) {
        const el = document.getElementById(id);
        el.value = value;
        el.style.backgroundColor = '#e8f0fe';
        setTimeout(() => (el.style.backgroundColor = 'white'), 500);
    }
}

function submitCardPayment() {
    const form      = document.getElementById('checkout-form');
    const cardInput = document.getElementById('card_num');

    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const cardNum = cardInput.value.replace(/\s/g, '');
    const cardExp = document.getElementById('card_exp').value;
    const cardCvv = document.getElementById('card_cvv').value;

    const AUTHORIZED_CARD = '4242424242424242';

    if (cardNum !== AUTHORIZED_CARD) {
        cardInput.style.borderColor = '#D92328';
        cardInput.style.boxShadow   = '0 0 5px rgba(217, 35, 40, 0.3)';
        alert('Carte refusée. Pour l\'environnement de test, veuillez utiliser la carte fournie dans l\'encadré Sandbox (4242 4242 4242 4242).');
        return;
    }

    if (cardExp.length < 5 || cardCvv.length < 3) {
        alert('Veuillez saisir une date d\'expiration (MM/YY) et un CVV valides.');
        return;
    }

    form.action = CHECKOUT_CONFIG.urls.processCard;
    form.submit();
}