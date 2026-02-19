<?php 
/**
 * Payment Checkout View
 *
 * Displays the final checkout step: shipping information form and order summary.
 * Features:
 * - Shipping details form (Firstname, Lastname, Address, Zip, City, Phone).
 * - Auto-fill capabilities from existing profile.
 * - Visual summary of cart items.
 * - Total calculation.
 * - Action to trigger payment process (PayPal redirect).
 *
 * @var array $items        List of items to pay
 * @var float $total        Total amount to pay
 * @var array $client       Client profile data (if exists)
 * @var array $t            Associative array of translations
 */

$items = isset($cart) ? (array)$cart : [];
$c = isset($client) ? (array)$client : [];
?>

<div class="payment-wrapper">
    <div class="payment-layout">
        
        <div class="payment-form-container">
            <h2 class="payment-title"><?= $t['payment_title'] ?? 'Adresse de livraison & Facturation' ?></h2>
            <p class="payment-subtitle">
                <?= $t['payment_subtitle'] ?? 'Veuillez renseigner vos coordonnées pour l\'expédition de votre commande.' ?>
            </p>
            
            <form action="<?= $_ENV['BASE_URL'] ?>/payment/process" method="POST" class="lego-form">

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name"><?= $t['payment_label_firstname'] ?? 'Prénom' ?></label>
                        <input type="text" id="first_name" name="first_name" required 
                            placeholder="Jean" 
                            value="<?= !empty($c['first_name']) ? htmlspecialchars($c['first_name']) : 'Zabi' ?>"
                            autocomplete="given-name">
                    </div>
                    <div class="form-group">
                        <label for="last_name"><?= $t['payment_label_lastname'] ?? 'Nom' ?></label>
                        <input type="text" id="last_name" name="last_name" required 
                            placeholder="Dupont" 
                            value="<?= !empty($c['last_name']) ? htmlspecialchars($c['last_name']) : 'Alk' ?>"
                            autocomplete="family-name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address_line"><?= $t['payment_label_address'] ?? 'Adresse (Rue, numéro, bâtiment)' ?></label>
                    <input type="text" id="address_line" name="address_line" required 
                        placeholder="12 Rue de la Paix" 
                        value="<?= !empty($c['address_line']) ? htmlspecialchars($c['address_line']) : '12 Rue de la Paix' ?>"
                        autocomplete="address-line1">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="zip_code"><?= $t['payment_label_zip'] ?? 'Code Postal' ?></label>
                        <input type="text" id="zip_code" name="zip_code" required 
                            placeholder="75000" 
                            value="<?= !empty($c['zip_code']) ? htmlspecialchars($c['zip_code']) : '75000' ?>"
                            autocomplete="postal-code">
                    </div>
                    <div class="form-group city-group">
                        <label for="city"><?= $t['payment_label_city'] ?? 'Ville' ?></label>
                        <input type="text" id="city" name="city" required 
                            placeholder="Paris" 
                            value="<?= !empty($c['city']) ? htmlspecialchars($c['city']) : 'Paris' ?>"
                            autocomplete="address-level2">
                    </div>
                </div>

                <div class="form-group">
                    <label for="phone"><?= $t['payment_label_phone'] ?? 'Téléphone' ?></label>
                    <input type="tel" id="phone" name="phone" required 
                        placeholder="ex: 06 12 34 56 78" 
                        value="<?= !empty($c['phone']) ? htmlspecialchars($c['phone']) : '0612345678' ?>"
                        autocomplete="tel">
                </div>
                
                <div class="secure-payment-info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="secure-icon"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                    <span><?= $t['payment_secure_info'] ?? 'Paiement 100% sécurisé via la plateforme PayPal. Vos données sont cryptées.' ?></span>
                </div>

                <button type="submit" class="btn-pay">
                    <?= $t['payment_pay_button'] ?? 'Payer via PayPal' ?> (<?= number_format($total, 2, ',', ' ') ?> €)
                </button>
            </form>
        </div>

        <div class="payment-sidebar">
            
            <div class="order-summary">
                <h3><?= $t['payment_summary_title'] ?? 'Récapitulatif' ?></h3>
                
                <div class="summary-items">
                    <?php foreach ($items as $item): 
                        $item = (array)$item;
                        $imgSrc = "data:" . ($item['image_type'] ?? 'image/png') . ";base64," . $item['image_data'];
                    ?>
                        <div class="mosaic-preview">
                            <img src="<?= $imgSrc ?>" alt="<?= $t['payment_alt_paving'] ?? 'Votre Pavage' ?>">
                            <div class="preview-info">
                                <p class="preview-title"><?= $t['payment_product_title'] ?? 'Mosaïque de Briques' ?></p>
                                <p class="preview-details"><?= $item['size'] ?>x<?= $item['size'] ?> - <?= $t['payment_format'] ?? 'Format' ?> <?= ucfirst($item['style']) ?></p>
                                <p class="preview-price"><?= number_format($item['price'], 2, ',', ' ') ?> €</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="summary-costs">
                    <div class="summary-row">
                        <span><?= $t['payment_subtotal'] ?? 'Sous-total' ?></span>
                        <span><?= number_format($total - 4.99, 2, ',', ' ') ?> €</span>
                    </div>
                    <div class="summary-row">
                        <span><?= $t['payment_shipping_fee'] ?? 'Frais de livraison' ?></span>
                        <span>4,99 €</span>
                    </div>
                    
                    <div class="summary-divider"></div>

                    <div class="summary-row total-row">
                        <span><?= $t['payment_total_label'] ?? 'Total à payer' ?></span>
                        <span class="total-price"><?= number_format($total, 2, ',', ' ') ?> €</span>
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
                
            </div> <div class="sandbox-alert">
                <div class="sandbox-alert-header">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                    <h4><?= $t['payment_sandbox_title'] ?? 'Environnement de Test (Sandbox)' ?></h4>
                </div>
                <p><?= $t['payment_sandbox_desc'] ?? 'Ce paiement est une simulation. Aucun montant réel ne sera débité. Pour valider le test PayPal, utilisez ces identifiants fictifs :' ?></p>

                <div class="sandbox-credentials">
                    <p><strong><?= $t['payment_sandbox_email'] ?? 'Email :' ?></strong> sb-o00un48707050@personal.example.com</p>
                    <p><strong><?= $t['payment_sandbox_password'] ?? 'Mot de passe :' ?></strong> 0oH&XU{K</p>
                </div>
                
                <p class="sandbox-warning"><?= $t['payment_sandbox_warning'] ?? '⚠️ Pensez bien à les copier dans un bloc-notes avant de cliquer sur "Payer" pour ne pas avoir à revenir en arrière !' ?></p>
            </div>

        </div> </div>
</div>

<script>
    function togglePromoCode() {
        var group = document.getElementById('promo-input-group');
        var icon = document.getElementById('promo-icon');
        
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