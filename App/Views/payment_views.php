<?php
/**
 * Payment checkout view
 * Displays the checkout form and a detailed order summary.
 * Handles saved address auto-filling.
 * * @var array $user  User data passed from controller
 * @var array $t     Translations
 * @var array $items Items in the current checkout session
 * @var float $total Total amount to pay
 */

// combination of both versions: check for items (controller update) or cart (fallback)
$itemsList = isset($items) ? (array)$items : (isset($cart) ? (array)$cart : []);
$c = isset($client) ? (array)$client : [];

// extract user info safely for the auto-fill feature
$userEmail = $user['email'] ?? '';
$userFirstName = $user['first_name'] ?? '';
$userLastName = $user['last_name'] ?? '';
$userAddress = $user['address_line'] ?? '';
$userZip = $user['zip_code'] ?? '';
$userCity = $user['city'] ?? '';
$userPhone = $user['phone'] ?? '';

// check if user has a complete saved address
$hasSavedAddress = (!empty($userAddress) && !empty($userCity) && !empty($userZip));

// calculate subtotal from items list
$subTotal = 0;
foreach ($itemsList as $item) {
    $price = is_object($item) ? ($item->price ?? 0) : ($item['price'] ?? 0);
    $subTotal += $price;
}
$shippingCost = $total - $subTotal;
?>

<div class="checkout-wrapper">
    <div class="checkout-header">
        <h1><?= $t['payment_title'] ?? 'Finaliser ma commande' ?></h1>
        <p><?= $t['payment_subtitle'] ?? 'Veuillez saisir vos informations pour la livraison.' ?></p>
    </div>

    <div class="checkout-grid">
        
        <div class="checkout-form-container">
            
            <?php if ($hasSavedAddress): ?>
                <div class="checkout-card">
                    <h3>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#006CB7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <?= $t['payment_saved_address_title'] ?? 'Adresse enregistrée' ?>
                    </h3>
                    <p style="color: #6b7280; margin-bottom: 20px; font-size: 0.95rem;">
                        <?= $t['payment_saved_address_hint'] ?? 'Vos informations sont déjà pré-remplies ci-dessous grâce à votre compte.' ?>
                    </p>
                    
                    <div class="saved-address-card" id="saved-address-card" onclick="useSavedAddress()">
                        <div class="card-content">
                            <strong><?= htmlspecialchars($userFirstName . ' ' . $userLastName) ?></strong><br>
                            <?= htmlspecialchars($userAddress) ?><br>
                            <?= htmlspecialchars($userZip . ' ' . $userCity) ?><br>
                            <?= !empty($userPhone) ? htmlspecialchars($userPhone) : '' ?>
                        </div>
                        <span class="btn-use-address" id="btn-use-address-text">
                            &rarr; <?= $t['payment_btn_use_address'] ?? 'Recharger ces informations' ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="checkout-card">
                <h3>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#006CB7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    <?= $t['payment_form_title'] ?? 'Informations de livraison' ?>
                </h3>
                
                <form action="<?= $_ENV['BASE_URL'] ?>/payment/process" method="POST" id="checkout-form">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name"><?= $t['payment_label_firstname'] ?? 'Prénom' ?> *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="Ex: Jean">
                        </div>
                        <div class="form-group">
                            <label for="last_name"><?= $t['payment_label_lastname'] ?? 'Nom' ?> *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="Ex: Dupont">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email"><?= $t['register_label_email'] ?? 'Adresse Email' ?> *</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($userEmail) ?>" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="phone"><?= $t['payment_label_phone'] ?? 'Téléphone' ?></label>
                            <input type="tel" id="phone" name="phone" class="form-control" placeholder="Ex: 06 12 34 56 78">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address_line"><?= $t['payment_label_address'] ?? 'Adresse postale' ?> *</label>
                        <input type="text" id="address_line" name="address_line" class="form-control" placeholder="Ex: 123 Rue de la République" required>
                    </div><br>

                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label for="zip_code"><?= $t['payment_label_zip'] ?? 'Code Postal' ?> *</label>
                            <input type="text" id="zip_code" name="zip_code" class="form-control" required placeholder="Ex: 75000">
                        </div>
                        <div class="form-group" style="flex: 2;">
                            <label for="city"><?= $t['payment_label_city'] ?? 'Ville' ?> *</label>
                            <input type="text" id="city" name="city" class="form-control" required placeholder="Ex: Paris">
                        </div>
                    </div>

                    <button type="submit" class="btn-pay-now">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2" ry="2"></rect><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        Payer avec
                        <span class="paypal-logo-text">Pay<span>Pal</span></span>
                    </button>
                    
                    <div class="security-notice">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span><?= $t['payment_secure_info'] ?? 'Paiement 100% sécurisé et cryptées.' ?></span>
                    </div>
                </form>
            </div>
        </div>

        <div class="checkout-summary-container">
            <div class="checkout-card">
                <h3>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#006CB7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg>
                    <?= $t['cart_summary_title'] ?? 'Votre commande' ?>
                </h3>
                
                <?php if (!empty($itemsList)): ?>
                    <div class="summary-items-list">
                        <?php foreach ($itemsList as $item): 
                            $itemArray = (array)$item;
                            $imgSrc = "data:" . ($itemArray['image_type'] ?? 'image/png') . ";base64," . ($itemArray['image_data'] ?? '');
                        ?>
                            <div class="summary-item">
                                <img src="<?= $imgSrc ?>" alt="Mosaïque" class="summary-item-img">
                                <div class="summary-item-details">
                                    <h4 class="summary-item-title"><?= $t['cart_product_title'] ?? 'Mosaïque Personnalisée' ?></h4>
                                    <p class="summary-item-meta">Taille: <?= $itemArray['size'] ?? '?' ?>x<?= $itemArray['size'] ?? '?' ?> &bull; Style: <?= ucfirst($itemArray['style'] ?? 'standard') ?></p>
                                    <p class="summary-item-meta"><?= $itemArray['pieces_count'] ?? 0 ?> pièces Lego®</p>
                                </div>
                                <div class="summary-item-price">
                                    <?= number_format($itemArray['price'] ?? 0, 2, ',', ' ') ?> €
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #6b7280; font-style: italic; margin-bottom: 20px;">Aucun article à afficher.</p>
                <?php endif; ?>

                <div class="summary-totals">
                    <div class="summary-line">
                        <span><?= $t['cart_label_subtotal'] ?? 'Sous-total' ?></span>
                        <span><?= number_format($subTotal, 2, ',', ' ') ?> €</span>
                    </div>
                    <div class="summary-line">
                        <span><?= $t['cart_label_shipping'] ?? 'Frais de livraison' ?></span>
                        <span><?= number_format($shippingCost, 2, ',', ' ') ?> €</span>
                    </div>
                    
                    <div class="summary-total-line">
                        <span><?= $t['cart_label_total'] ?? 'Total TTC' ?></span>
                        <span style="color: #006CB7;"><?= number_format($total, 2, ',', ' ') ?> €</span>
                    </div>
                </div>

                <div class="promo-code-container">
                    <button type="button" class="promo-toggle-btn" onclick="togglePromoCode()">
                        <svg id="promo-icon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="M12 5v14m-7-7h14"></path></svg>
                        <?= $t['payment_add_promo'] ?? 'Ajouter un code de réduction' ?>
                    </button>
                    <div id="promo-input-group" class="promo-input-group">
                        <input type="text" id="promo_code" name="promo_code" placeholder="<?= $t['payment_promo_placeholder'] ?? 'Entrez votre code' ?>" class="promo-input">
                        <button type="button" class="btn-promo" onclick="alert('<?= addslashes($t['payment_promo_alert'] ?? 'La fonctionnalité de code promo sera bientôt disponible !') ?>')"><?= $t['payment_promo_apply'] ?? 'Appliquer' ?></button>
                    </div>
                </div>
            </div> 
            
            <div class="sandbox-alert">
                <div class="sandbox-alert-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <h4><?= $t['payment_sandbox_title'] ?? 'Environnement de Test (Sandbox)' ?></h4>
                </div>
                
                <p><?= $t['payment_sandbox_desc'] ?? 'Ce paiement est une simulation. Aucun montant réel ne sera débité. Pour valider le test PayPal, utilisez ces identifiants fictifs' ?></p>

                <div class="sandbox-credentials">
                    <p><strong><?= $t['payment_sandbox_email'] ?? 'Email :' ?></strong> sb-o00un48707050@personal.example.com</p>
                    <p><strong><?= $t['payment_sandbox_password'] ?? 'Mot de passe :' ?></strong> 0oH&XU{K</p>
                </div>
                
                <p class="sandbox-warning">
                    <?= $t['payment_sandbox_warning'] ?? '⚠️ Pensez bien à les copier dans un bloc-notes avant de cliquer sur "Payer" pour ne pas avoir à revenir en arrière !' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // auto-fill form using the saved address data
    function useSavedAddress() {
        const savedData = {
            firstName: <?= json_encode($userFirstName) ?>,
            lastName: <?= json_encode($userLastName) ?>,
            address: <?= json_encode($userAddress) ?>,
            zip: <?= json_encode($userZip) ?>,
            city: <?= json_encode($userCity) ?>,
            phone: <?= json_encode($userPhone) ?>
        };

        document.getElementById('first_name').value = savedData.firstName;
        document.getElementById('last_name').value = savedData.lastName;
        document.getElementById('address_line').value = savedData.address;
        document.getElementById('zip_code').value = savedData.zip;
        document.getElementById('city').value = savedData.city;
        document.getElementById('phone').value = savedData.phone;

        const card = document.getElementById('saved-address-card');
        const btnText = document.getElementById('btn-use-address-text');
        
        card.classList.add('selected');
        btnText.innerHTML = '✓ <?= $t['payment_address_selected'] ?? 'Adresse appliquée' ?>';
        document.getElementById('checkout-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Toggle promo code input display
    function togglePromoCode() {
        const group = document.getElementById('promo-input-group');
        const icon = document.getElementById('promo-icon');
        
        if (group.classList.contains('show')) {
            group.classList.remove('show');
            icon.innerHTML = '<path d="M12 5v14m-7-7h14"></path>';
        } else {
            group.classList.add('show');
            icon.innerHTML = '<path d="M5 12h14"></path>';
            document.getElementById('promo_code').focus();
        }
    }
</script>