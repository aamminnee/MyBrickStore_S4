<?php
/**
 * payment checkout view
 * displays the checkout form and a detailed order summary.
 * handles saved address auto-filling.
 *
 * @var array $user  user data passed from controller
 * @var array $t     translations
 * @var array $items items in the current checkout session
 * @var float $total total amount to pay
 */

$itemsList = isset($items) ? (array)$items : (isset($cart) ? (array)$cart : []);
$c = isset($client) ? (array)$client : [];

$userEmail = $user['email'] ?? '';
$userFirstName = $user['first_name'] ?? '';
$userLastName = $user['last_name'] ?? '';
$userAddress = $user['address_line'] ?? '';
$userZip = $user['zip_code'] ?? '';
$userCity = $user['city'] ?? '';
$userPhone = $user['phone'] ?? '';

$hasSavedAddress = (!empty($userAddress) && !empty($userCity) && !empty($userZip));

$subTotal = 0;
foreach ($itemsList as $item) {
    $price = is_object($item) ? ($item->price ?? 0) : ($item['price'] ?? 0);
    $subTotal += $price;
}

// fetch standard delivery fee from model
$shippingCost = \App\Models\MosaicModel::DELIVERY_FEE;

// fetch user loyalty points if loyalty id exists in session
$loyaltyId = $user['loyalty_id'] ?? $_SESSION['user']['loyalty_id'] ?? null;
$availablePoints = 0;
if ($loyaltyId) {
    $loyaltyModel = new \App\Models\LoyaltyApiModel();
    $availablePoints = $loyaltyModel->getPoints($loyaltyId);
}

// get currently applied points and discount from session
$appliedPoints = $_SESSION['applied_points'] ?? 0;
$loyaltyDiscount = $_SESSION['loyalty_discount'] ?? 0.0;

// new conversion rate (1000 points = 1€)
$conversionRate = 0.001;

$orderTotalBeforeDiscount = $subTotal + $shippingCost;
$maxPointsNeededForCart = (int)ceil($orderTotalBeforeDiscount / $conversionRate);
$maxPointsInput = min($availablePoints, $maxPointsNeededForCart);

// virtually remaining points to display to the customer
$displayAvailablePoints = max(0, $availablePoints - $appliedPoints);

// final total calculation for display
$displayTotal = max(0, $subTotal + $shippingCost - $loyaltyDiscount);
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

                    <div class="payment-selection" style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 25px;">
                        <h3 style="font-size: 1.2rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#006CB7" stroke-width="2"><path d="M21 4H3a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h18a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"></path><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            <?= $t['payment_method_title'] ?? 'Mode de paiement' ?>
                        </h3>
                        
                        <div class="payment-tabs-container">
                            <button type="button" id="tab-paypal" class="payment-tab active" onclick="switchPayment('paypal')">
                                <div class="tab-content">
                                    <img src="https://www.paypalobjects.com/webstatic/mktg/logo/pp_cc_mark_37x23.jpg" alt="PayPal">
                                    <span>PayPal</span>
                                </div>
                                <div class="tab-indicator"></div>
                            </button>
                            <button type="button" id="tab-card" class="payment-tab" onclick="switchPayment('card')">
                                <div class="tab-content">
                                    <div class="tab-icon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                                            <line x1="1" y1="10" x2="23" y2="10"/>
                                        </svg>
                                    </div>
                                    <span class="tab-label"><?= $t['payment_bank_card'] ?? 'Carte Bancaire' ?></span>
                                </div>
                            </button>
                        </div>

                        <div id="section-paypal" class="payment-method-body active">
                            <button type="submit" class="btn-pay-now paypal-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                                <?= $t['payment_pay_with'] ?? 'Payer avec' ?><strong>PayPal</strong>
                            </button>
                        </div>

                        <div id="section-card" class="payment-method-body">
                            <div class="card-form-wrapper">
                                
                                <div class="form-group mb-15">
                                    <label class="small-label"><?= $t['payment_card_number'] ?? 'NUMÉRO DE CARTE' ?></label>
                                    <div class="input-with-icon">
                                        <input type="text" id="card_num" name="card_num" placeholder="4242 4242 4242 4242" class="form-control card-input" maxlength="19">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label class="small-label"><?= $t['payment_card_expiry'] ?? 'EXPIRATION' ?></label>
                                        <input type="text" id="card_exp" name="card_exp" placeholder="MM/YY" class="form-control" maxlength="5">
                                    </div>
                                    <div class="form-group">
                                        <label class="small-label">CVV</label>
                                        <div class="input-with-icon">
                                            <input type="text" id="card_cvv" name="card_cvv" placeholder="123" class="form-control" maxlength="3">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="button" onclick="submitCardPayment()" class="btn-pay-now card-btn">
                                    <?= $t['payment_confirm'] ?? 'Confirmer le paiement par carte' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="security-notice">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                        <span><?= $t['payment_secure_info'] ?? 'Paiement 100% sécurisé et crypté.' ?></span>
                    </div>
                </form>
            </div>
        </div>

        <div class="checkout-summary-container">

            <div class="checkout-card" style="margin-bottom: 20px; background-color: #f0f7ff; border: 1px solid #cce0ff;">
                <h3 style="color: #006CB7; font-size: 1.1rem; margin-bottom: 10px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; vertical-align: middle;"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
                    <?= $t['loyalty_use_points'] ?? 'Programme de fidélité' ?>
                </h3>
                
                <?php if (empty($loyaltyId)): ?>
                    <p style="font-size: 0.9rem; margin-bottom: 15px;">
                        Associez votre compte <strong>MyBrickGames</strong> pour utiliser vos points et obtenir des réductions !
                    </p>
                    
                    <form action="<?= $_ENV['BASE_URL'] ?>/payment/lierCompteJeux" method="POST">
                        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 15px;">
                            <input type="text" name="loyalty_id" required placeholder="Ex: visitor_x8y9z0" class="form-control" style="flex: 1; padding: 8px;">
                            <button type="submit" class="btn-action" style="padding: 9px 15px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                Lier mon compte
                            </button>
                        </div>
                    </form>
                    
                    <div style="margin-top: 15px; text-align: center; border-top: 1px dashed #cce0ff; padding-top: 15px;">
                        <p style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">Vous n'avez pas encore d'identifiant ?</p>
                        <a href="<?= htmlspecialchars($_ENV['MYBRICKGAME'] ?? 'http://localhost:5173') ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           style="background-color: #e3000b; color: #ffffff; padding: 8px 15px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block; font-size: 0.85rem;">
                           Jouer pour créer un compte !
                        </a>
                    </div>

                <?php else: ?>
                    <p style="font-size: 0.9rem; margin-bottom: 15px;">
                        <?= $t['loyalty_current_points'] ?? 'Vous avez actuellement' ?> <strong><?= htmlspecialchars($displayAvailablePoints) ?> points</strong> utilisables.
                        <br><em style="color: #666; font-size: 0.85rem;">(1000 points = 1,00 € de réduction)</em>
                    </p>
                    
                    <?php if ($appliedPoints == 0): ?>
                        <form action="<?= $_ENV['BASE_URL'] ?>/payment/appliquerPoints" method="POST">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="number" name="points_a_utiliser" min="1" max="<?= htmlspecialchars($maxPointsInput) ?>" required class="form-control" style="width: 100px; padding: 8px;" placeholder="Points">
                                <button type="submit" class="btn-action" style="padding: 9px 15px; background-color: #006CB7; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                                    Appliquer
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                ✓ <?= htmlspecialchars($appliedPoints) ?> points appliqués.<br>
                                Réduction : <strong>- <?= number_format($loyaltyDiscount, 2, ',', ' ') ?> €</strong>
                            </div>
                            <form action="<?= $_ENV['BASE_URL'] ?>/payment/appliquerPoints" method="POST" style="margin: 0;">
                                <input type="hidden" name="points_a_utiliser" value="0">
                                <button type="submit" style="background: none; border: none; color: #721c24; text-decoration: underline; cursor: pointer; font-weight: bold; padding: 0;">Retirer</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

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
                                    <p class="summary-item-meta"><?= $t['cart_label_size'] ?? 'Taille' ?>: <?= $itemArray['size'] ?? '?' ?>x<?= $itemArray['size'] ?? '?' ?> &bull; Style: <?= ucfirst($itemArray['style'] ?? 'standard') ?></p>
                                    <p class="summary-item-meta"><?= $itemArray['pieces_count'] ?? 0 ?> <?= $t['cart_label_pieces'] ?? 'Pièces' ?></p>
                                </div>
                                <div class="summary-item-price">
                                    <?= number_format($itemArray['price'] ?? 0, 2, ',', ' ') ?> €
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #6b7280; font-style: italic; margin-bottom: 20px;"><?= $t['cart_show_article'] ?? 'Aucun article à afficher.' ?></p>
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
                    
                    <?php if ($loyaltyDiscount > 0): ?>
                        <div class="summary-line" style="color: #28a745; font-weight: 500;">
                            <span><?= $t['cart_label_discount'] ?? 'Remise fidélité' ?></span>
                            <span>- <?= number_format($loyaltyDiscount, 2, ',', ' ') ?> €</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-total-line">
                        <span><?= $t['cart_label_total'] ?? 'Total TTC' ?></span>
                        <span style="color: #006CB7;"><?= number_format($displayTotal, 2, ',', ' ') ?> €</span>
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
                
                <p><?= $t['payment_sandbox_desc'] ?? 'Ce paiement est une simulation. Aucun montant réel ne sera débité.' ?></p>

                <div id="sandbox-paypal-info" class="sandbox-credentials">
                    <p style="margin-bottom: 5px; font-weight: bold; color: #006CB7;"><?= $t['payment_sandbox_identifier'] ?? 'Identifiants test PayPal :' ?></p>
                    <p><strong><?= $t['payment_sandbox_email'] ?? 'Email :' ?></strong> sb-o00un48707050@personal.example.com</p>
                    <p><strong><?= $t['payment_sandbox_password'] ?? 'Mot de passe :' ?></strong> 0oH&XU{K</p>
                </div>

                <div id="sandbox-card-info" class="sandbox-credentials" style="display: none;">
                    <p style="margin-bottom: 5px; font-weight: bold; color: #28a745;"><?= $t['payment_sandbox_card'] ?? 'Coordonnées test Carte Bancaire :' ?></p>
                    <p><strong><?= $t['payment_label_card'] ?? 'Numéro de carte :' ?></strong> : 4242 4242 4242 4242</p>
                    <p><strong><?= $t['payment_label_expiry'] ?? 'Expiration :' ?></strong> : 12/34</p>
                    <p><strong>CVV</strong> : 123</p>
                </div>
                            
                <p class="sandbox-warning">
                    <?= $t['payment_sandbox_warning'] ?? '⚠️ Pensez bien à les copier dans un bloc-notes avant de cliquer sur "Payer" pour ne pas avoir à revenir en arrière !' ?>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    const CHECKOUT_CONFIG = {
        savedAddress: {
            firstName: <?= json_encode($userFirstName) ?>,
            lastName:  <?= json_encode($userLastName) ?>,
            address:   <?= json_encode($userAddress) ?>,
            zip:       <?= json_encode($userZip) ?>,
            city:      <?= json_encode($userCity) ?>,
            phone:     <?= json_encode($userPhone) ?>
        },
        urls: {
            processCard: "<?= $_ENV['BASE_URL'] ?>/payment/processCard"
        },
        i18n: {
            addressSelected: "<?= addslashes($t['payment_address_selected'] ?? 'Adresse appliquée') ?>"
        }
    };
</script>
<script src="<?= $_ENV['BASE_URL'] ?>/JS/checkout.js"></script>