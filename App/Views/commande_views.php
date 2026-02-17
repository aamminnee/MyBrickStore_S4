<?php
/**
 * Orders History View
 *
 * Displays a list of past orders for the logged-in user.
 * Features:
 * - List of orders with visual previews.
 * - Status badges (Paid, Shipped, etc.).
 * - Links to details and invoices.
 *
 * @var array $commandes    List of order objects (id_commande, date_commande, montant, status, visuel)
 * @var array $t            Associative array of translations
 */
?>
<div class="orders-container">
    <a href="<?= $_ENV['BASE_URL'] ?>/index.php" class="btn-home-back">
        <?= $t['orders_back'] ?? '&larr; Retour à l\'accueil' ?>
    </a>
    <br>
    <h1 class="page-title"><?= $t['orders_title'] ?? 'Mes Commandes' ?></h1>
    <?php if (empty($commandes)): ?>
        <div class="empty-state">
            <p><?= $t['orders_empty'] ?? 'Vous n\'avez pas encore passé de commande.' ?></p>
            <a href="<?= $_ENV['BASE_URL'] ?>/images" class="btn-action">
                <?= $t['orders_create_btn'] ?? 'Créer mon premier pavage' ?>
            </a>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($commandes as $c): ?>
                <div class="order-card">
                    <div class="order-visual">
                        <?php if (!empty($c->visuel)): ?>
                            <img src="<?= htmlspecialchars($c->visuel) ?>" alt="<?= $t['orders_alt_img'] ?? 'Pavage #' ?><?= $c->id_commande ?>">
                        <?php else: ?>
                            <div class="no-image"><?= $t['orders_no_img'] ?? 'Pas d\'aperçu' ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="order-info">
                        <div class="info-header">
                            <span class="order-ref"><?= $t['orders_number'] ?? 'Commande #' ?><?= htmlspecialchars($c->id_commande) ?></span>&nbsp;
                            <span class="order-date"><?= date('d/m/Y', strtotime($c->date_commande)) ?></span>
                        </div>
                        
                        <div class="info-status">
                            <span class="status-badge status-<?= strtolower($c->status) ?>">
                                <?php 
                                    $statusKey = 'status_' . mb_strtolower($c->status, 'UTF-8');
                                    echo htmlspecialchars($t[$statusKey] ?? $c->status); 
                                ?>
                            </span>
                        </div>

                        <div class="info-price">
                            <?= $t['orders_price'] ?? 'Prix :' ?> <span class="price-val"><?= number_format($c->montant, 2) ?> €</span>
                        </div>
                    </div>

                    <div class="order-actions">
                        <a href="<?= $_ENV['BASE_URL'] ?>/commande/detail/<?= $c->id_commande ?>" class="btn-details">
                            <?= $t['orders_detail_btn'] ?? 'Voir le détail' ?>
                        </a>
                        <?php if ($c->status === 'Payée'): ?>
                            <a href="<?= $_ENV['BASE_URL'] ?>/payment/confirmation?id=<?= $c->id_commande ?>" class="btn-invoice">
                                <?= $t['orders_invoice_btn'] ?? 'Facture' ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>