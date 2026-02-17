<?php
/**
 * Shopping Cart View
 *
 * Displays the user's current selection of mosaics.
 * Features:
 * - List of items with visual preview and specifications (size, pieces).
 * - Price calculation per item.
 * - Option to remove items.
 * - Order summary (Subtotal, Shipping, Total) and Checkout button.
 *
 * @var array $items       List of cart items (mixed array/object depending on source)
 * @var float $subTotal    Sum of item prices before shipping
 * @var float $total       Grand total including shipping
 * @var array $t           Associative array of translations
 */
?>

<div class="cart-wrapper">
    <div class="cart-container">
        
        <div class="cart-header">
            <h1><?= $t['cart_page_title'] ?? 'Mon Panier' ?></h1>
            <p>
                <?php 
                $count = is_array($items) ? count($items) : 0;
                $defaultEmpty = "Votre panier est vide.";
                $defaultCount = "Vous avez %s création(s) en attente.";
                if ($count > 0) {
                    echo sprintf($t['cart_msg_count'] ?? $defaultCount, $count);
                } else {
                    echo $t['cart_msg_empty'] ?? $defaultEmpty;
                }
                ?>
            </p>
        </div>

        <?php if (empty($items)): ?>
            
            <div class="empty-cart-state">
                <div class="empty-illustration">
                    🧱
                </div>
                <h3><?= $t['cart_empty_block_title'] ?? "C'est bien vide ici !" ?></h3>
                <p><?= $t['cart_empty_block_text'] ?? 'Commencez par créer votre première mosaïque personnalisée.' ?></p>
                <a href="<?= $_ENV['BASE_URL'] ?>/index.php" class="btn-create">
                    <span class="icon">+</span> <?= $t['cart_btn_create'] ?? 'Créer une Mosaïque' ?>
                </a>
            </div>

        <?php else: ?>

            <div class="cart-layout">
                
                <div class="cart-items-list">
                    <?php foreach ($items as $item): 
                        $i_id = is_object($item) ? $item->id_cart : $item['id_unique'];
                        $i_style = is_object($item) ? $item->style : $item['style'];
                        $i_size = is_object($item) ? $item->size : $item['size'];
                        $i_pieces = is_object($item) ? $item->pieces_count : $item['pieces_count'];
                        $i_price = is_object($item) ? $item->price : $item['price'];
                        $imgData = is_object($item) ? base64_encode($item->file) : $item['image_data'];
                        $imgType = is_object($item) ? $item->file_type : $item['image_type'];
                    ?>
                        <div class="cart-card style-<?= $i_style ?>">
                            <div class="card-visual">
                                <img src="data:<?= $imgType ?>;base64,<?= $imgData ?>" alt="Aperçu Mosaïque">
                            </div>
                            
                            <div class="card-info">
                                <div class="info-top">
                                    <h3><?= $t['cart_product_title'] ?? 'Mosaïque Personnalisée' ?></h3>
                                    <span class="badge badge-<?= $i_style ?>"><?= ucfirst($item['style'] ?? 'Standard') ?></span>

                                </div>
                                
                                <div class="specs-grid">
                                    <div class="spec">
                                        <span class="label"><?= $t['cart_label_size'] ?? 'Taille' ?></span>
                                        <span class="val"><?= $i_size ?>x<?= $i_size ?></span>
                                    </div>
                                    <div class="spec">
                                        <span class="label"><?= $t['cart_label_pieces'] ?? 'Pièces' ?></span>
                                        <span class="val"><?= $i_pieces ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-price-action">
                                <div class="price"><?= number_format($i_price, 2, ',', ' ') ?> €</div>
                                
                                <form action="<?= $_ENV['BASE_URL'] ?>/cart/remove" method="POST">
                                    <input type="hidden" name="cart_id" value="<?= $i_id ?>">
                                    <button type="submit" class="btn-remove" title="<?= $t['cart_tooltip_delete'] ?? 'Supprimer' ?>">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <div class="summary-card">
                        <h3><?= $t['cart_summary_title'] ?? 'Récapitulatif' ?></h3>
                        
                        <div class="summary-row">
                            <span class="label"><?= $t['cart_label_subtotal'] ?? 'Sous-total' ?> (<?= count($items) ?> articles)</span>
                            <span class="value"><?= number_format($subTotal, 2) ?> €</span>
                        </div>
                        
                        <div class="summary-row highlight">
                            <span class="label"><?= $t['cart_label_shipping'] ?? 'Livraison standard' ?></span>
                            <span class="value">4,99 €</span>
                        </div>

                        <div class="divider"></div>

                        <div class="summary-total">
                            <span><?= $t['cart_label_total'] ?? 'Total à payer' ?></span>
                            <span class="total-amount"><?= number_format($total, 2) ?> €</span>
                        </div>

                        <a href="<?= $_ENV['BASE_URL'] ?>/payment" class="btn-checkout"><?= $t['cart_btn_checkout'] ?? 'Payer maintenant' ?></a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>