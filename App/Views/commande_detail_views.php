<?php
/**
 * Order Detail View
 *
 * Displays the complete details of a specific order.
 * Features:
 * - Shipping progress tracker based on order date.
 * - List of purchased items (mosaics) with download links (Plan, CSV, Image).
 * - Detailed inventory table of bricks required.
 * - Shipping and Billing summary.
 *
 * @var object $commande    The order object containing details (id, date, amount, address, etc.)
 * @var array $items        List of items in the order
 * @var array $briques      Aggregated list of bricks for the order
 * @var string $visuel      Fallback visual image URL
 * @var array $t            Associative array of translations
 */

$dateCommande = new DateTime($commande->order_date);
$maintenant = new DateTime();
$interval = $dateCommande->diff($maintenant);
$joursPasses = $interval->days;

$statusLivraison = "";
$progressWidth = "0%";
$classEtat = "";

if ($joursPasses < 3) {
    $statusLivraison = $t['order_status_prep'] ?? "En préparation / Expédition";
    $progressWidth = "33%";
    $classEtat = "state-shipping"; 
} elseif ($joursPasses >= 3 && $joursPasses <= 7) {
    $statusLivraison = $t['order_status_transit'] ?? "En cours de livraison";
    $progressWidth = "66%";
    $classEtat = "state-transit"; 
} else {
    $statusLivraison = $t['order_status_delivered'] ?? "Livrée";
    $progressWidth = "100%";
    $classEtat = "state-delivered"; 
}

if (!isset($items) || empty($items)) {
    $items = [ 
        (object)[
            'id_Mosaic' => $commande->id_Mosaic ?? 0,
            'visuel' => $visuel,
            'size' => 64,
            'style' => 'Classique'
        ] 
    ];
}
?>

<div class="detail-wrapper">
    <div class="detail-container">
        
        <div class="page-header">
            <a href="<?= $_ENV['BASE_URL'] ?>/commande" class="btn-back">
                <span class="icon">←</span> <?= $t['order_detail_back'] ?? 'Retour aux commandes' ?>
            </a>
            <h1><?= $t['order_detail_title'] ?? 'Commande' ?> <span>#<?= htmlspecialchars($commande->id_Order) ?></span></h1>
            <p class="subtitle"><?= $t['order_detail_date'] ?? 'Effectuée le' ?> <?= date('d/m/Y à H:i', strtotime($commande->order_date)) ?></p>
        </div>

        <div class="detail-grid">
            
            <div class="main-column">
                
                <div class="card tracking-card">
                    <div class="tracking-header">
                        <h3><?= $t['order_tracking_title'] ?? 'Suivi de livraison' ?></h3>
                        <span class="status-text <?= $classEtat ?>"><?= $statusLivraison ?></span>
                    </div>

                    <div class="tracking-visual">
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?= $progressWidth ?>;"></div>
                        </div>
                        
                        <div class="steps">
                            <div class="step <?= $joursPasses >= 0 ? 'active' : '' ?>">
                                <div class="step-icon">📦</div>
                                <span class="step-label"><?= $t['order_step_shipping'] ?? 'Expédition' ?><br>(< 3j)</span>
                            </div>
                            <div class="step <?= $joursPasses >= 3 ? 'active' : '' ?>">
                                <div class="step-icon">🚚</div>
                                <span class="step-label"><?= $t['order_step_transit'] ?? 'En transit' ?><br>(3-7j)</span>
                            </div>
                            <div class="step <?= $joursPasses > 7 ? 'active' : '' ?>">
                                <div class="step-icon">🏠</div>
                                <span class="step-label"><?= $t['order_step_delivered'] ?? 'Livrée' ?><br>(> 7j)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="items-section">
                    <h3 class="section-title"><?= $t['order_items_title'] ?? 'Contenu du colis' ?></h3>
                    
                    <?php foreach ($items as $item): 
                        $imgSrc = $item->visuel ?? ($visuel ?? '');
                        $mosaicId = $item->id_Mosaic ?? 0;
                    ?>
                        <div class="item-card">
                            <div class="item-visual">
                                <?php if ($imgSrc): ?>
                                    <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Aperçu">
                                <?php else: ?>
                                    <div class="no-img"><?= $t['order_no_image'] ?? 'Pas d\'image' ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="item-content">
                                <div class="item-main">
                                    <h4><?= $t['order_product_custom'] ?? 'Mosaïque Personnalisée' ?></h4>
                                    <div class="badges">
                                        <span class="badge size"><?= $item->size ?? '?' ?>x<?= $item->size ?? '?' ?></span>
                                        <span class="badge style"><?= ucfirst($item->style ?? 'Standard') ?></span>
                                    </div>
                                </div>

                                <div class="item-downloads">
                                    <span><?= $t['order_download_label'] ?? 'Télécharger :' ?></span>
                                    <div class="dl-buttons">
                                        <a href="<?= $_ENV['BASE_URL'] ?>/commande/downloadPlan/<?= $mosaicId ?>" target="_blank" class="btn-dl pdf" title="Plan PDF">📄 <?= $t['order_btn_plan'] ?? 'Plan' ?></a>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/commande/downloadCsv/<?= $mosaicId ?>" class="btn-dl csv" title="Liste Excel">📊 <?= $t['order_btn_csv'] ?? 'CSV' ?></a>
                                        <a href="<?= $_ENV['BASE_URL'] ?>/commande/downloadImage/<?= $mosaicId ?>" download class="btn-dl img" title="Image HD">🖼️ <?= $t['order_btn_image'] ?? 'Image' ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($briques)): ?>
                    <div class="card inventory-card">
                        <h3 class="card-title"><?= $t['order_inventory_title'] ?? 'Contenu de la boîte' ?></h3>
                        <div class="table-scroll">
                            <table class="bricks-table">
                                <thead>
                                    <tr>
                                        <th><?= $t['order_col_color'] ?? 'Couleur' ?></th>
                                        <th><?= $t['order_col_size'] ?? 'Taille' ?></th>
                                        <th><?= $t['order_col_color'] ?? 'Couleur' ?></th>
                                        <th class="text-right"><?= $t['order_col_qty'] ?? 'Quantité' ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($briques as $b): ?>
                                        <tr>
                                            <td><span class="brick-dot" style="background-color: <?= $b['color'] ?>;"></span></td>
                                            <td><?= htmlspecialchars($b['size']) ?></td>
                                            <td class="color-code"><?= strtoupper($b['color']) ?></td>
                                            <td class="text-right"><strong>x<?= $b['count'] ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="inventory-total">
                            <?= sprintf($t['order_total_bricks'] ?? 'Total : %s briques', array_sum(array_column($briques, 'count'))) ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <div class="sidebar-column">
                
                <div class="card info-card">
                    <h3><?= $t['order_shipping_title'] ?? 'Livraison' ?></h3>
                    <div class="info-group">
                        <span class="label"><?= $t['order_recipient'] ?? 'Destinataire' ?></span>
                        <p class="val"><?= htmlspecialchars($commande->first_name ?? '') ?> <?= htmlspecialchars($commande->last_name ?? '') ?></p>
                    </div>
                    <div class="info-group">
                        <span class="label"><?= $t['order_address'] ?? 'Adresse' ?></span>
                        <p class="val address-val">
                            <?= nl2br(htmlspecialchars($commande->adress ?? ($t['order_address_empty'] ?? 'Non renseignée'))) ?>
                        </p>
                    </div>
                </div>

                <div class="card summary-card">
                    <h3><?= $t['order_payment_title'] ?? 'Paiement' ?></h3>
                    <div class="row">
                        <span><?= $t['order_subtotal'] ?? 'Sous-total' ?></span>
                        <span><?= number_format($commande->total_amount, 2) ?> €</span>
                    </div>
                    <div class="row">
                        <span><?= $t['order_shipping_title'] ?? 'Livraison' ?></span>
                        <span class="free">4,99 €</span>
                    </div>
                    <div class="divider"></div>
                    <div class="row total">
                        <span><?= $t['order_total'] ?? 'Total' ?></span>
                        <span><?= number_format($commande->total_amount, 2) ?> €</span>
                    </div>

                    <a href="<?= $_ENV['BASE_URL'] ?>/payment/confirmation?id=<?= $commande->id_Order ?>" class="btn-invoice">
                        <?= $t['order_btn_invoice'] ?? 'Télécharger la facture' ?>
                    </a>
                </div>

                <div class="help-box">
                    <p><?= $t['order_help_text'] ?? 'Un problème avec cette commande ?' ?></p>
                    <a href="mailto:amine.mourali77@gmail"><?= $t['order_help_link'] ?? 'Contacter le support' ?></a>
                </div>

            </div>

        </div>
    </div>
</div>