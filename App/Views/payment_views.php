<?php 
/**
 * Payment Checkout View
 *
 * Displays the final checkout step: shipping information form and order summary.
 * Features:
 * - Shipping details form (Address, Phone).
 * - Visual summary of cart items.
 * - Total calculation.
 * - Action to trigger payment process (PayPal redirect).
 *
 * @var array $cart         List of items in the cart
 * @var float $total        Total amount to pay
 * @var array $t            Associative array of translations
 */

$items = isset($cart) ? (array)$cart : [];
?>

<div class="payment-wrapper">
    <div class="payment-layout">
        
        <div class="payment-form-container">
            <h2 class="payment-title"><?= $t['payment_title'] ?? 'Finaliser votre commande' ?></h2>
            
            <form action="<?= $_ENV['BASE_URL'] ?>/payment/process" method="POST" class="lego-form">

                <div class="form-group">
                    <label for="phone"><?= $t['payment_label_phone'] ?? 'Téléphone' ?></label>
                    <input type="tel" id="phone" name="phone" required 
                        placeholder="<?= $t['payment_placeholder_phone'] ?? 'ex: 06 12 34 56 78' ?>" 
                        value="07 77 77 77 77">
                </div>

                <div class="form-group">
                    <label for="adress"><?= $t['payment_label_address'] ?? 'Adresse complète de livraison' ?></label>
                    <input type="text" id="adress" name="adress" required 
                        placeholder="<?= $t['payment_placeholder_address'] ?? 'ex: 12 Rue de la Paix, 75000 Paris' ?>" 
                        value="12 Rue de la Paix, 75002 Paris">
                </div>
                
                <div style="margin: 20px 0;">
                    <p>Redirection sécurisée vers PayPal Sandbox.</p>
                </div>

                <button type="submit" class="btn-pay">
                    Payer avec PayPal (<?= number_format($total, 2, ',', ' ') ?> €)
                </button>
            </form>
        </div>

        <div class="order-summary">
            <h3><?= $t['payment_summary_title'] ?? 'Récapitulatif' ?></h3>
            
            <div class="summary-items">
                <?php foreach ($items as $item): 
                    $item = (array)$item;
                    $imgSrc = "data:" . ($item['image_type'] ?? 'image/png') . ";base64," . $item['image_data'];
                ?>
                    <div class="mosaic-preview">
                        <img src="<?= $imgSrc ?>" alt="Votre Pavage">
                        <div class="preview-info">
                            <p class="preview-title"><?= $t['payment_product_title'] ?? 'Pavage LEGO®' ?></p>
                            <p class="preview-details"><?= $item['size'] ?>x<?= $item['size'] ?> - <?= ucfirst($item['style']) ?></p>
                            <p class="preview-price"><?= number_format($item['price'], 2) ?> €</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-divider"></div>

            <div class="summary-row total-row">
                <span><?= $t['payment_total_label'] ?? 'Total à payer' ?></span>
                <span class="total-price"><?= number_format($total, 2, ',', ' ') ?> €</span>
            </div>
        </div>

    </div>
</div>