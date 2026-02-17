<?php
/**
 * Admin Supplier View
 *
 * Displays the interface for interacting with the external Factory system.
 * Features include:
 * - Viewing output from the Java factory tool (mining/restocking results).
 * - Displaying the current virtual credit balance.
 * - Action buttons to trigger mining or auto-restock processes.
 * - A history list of orders placed to the factory.
 *
 * @var array $orders                     List of grouped factory orders (containing 'info' object and 'items' array)
 * @var array $t                          Associative array of translations
 * @var string|null $_SESSION['factory_output']       Flash message containing the Java tool output
 * @var int|null $_SESSION['last_factory_balance']    Cached balance from the last factory operation
 */
?>
<div class="admin-container">
    <h1><?= $t['supplier_title'] ?? 'Espace Fournisseur & Approvisionnement' ?></h1>

    <?php if (isset($_SESSION['factory_output'])): ?>
        <div class="admin-card factory-terminal">
            <h3>Résultat Opération :</h3>
            <pre><?= htmlspecialchars($_SESSION['factory_output'], ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
        <?php unset($_SESSION['factory_output']); ?>
    <?php endif; ?>

    <div class="admin-card factory-wallet-card">
        
        <div class="wallet-header">
            <h2>Portefeuille MyBrickFactory</h2>
            <p>
                Solde actuel estimé : 
                <strong class="wallet-balance">
                    <?= isset($_SESSION['last_factory_balance']) ? number_format($_SESSION['last_factory_balance']) : '?' ?> Crédits
                </strong>
            </p>
        </div>

        <div class="factory-actions">
            
            <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/admin/runFactory" method="POST">
                <input type="hidden" name="action" value="refill">
                <button type="submit" class="btn-primary btn-mine">
                    Minage (+ Crédits)
                </button>
            </form>

            <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/admin/runFactory" method="POST">
                <input type="hidden" name="action" value="proactive">
                <button type="submit" class="btn-primary btn-proactive">
                    <?= $t['supplier_history_title'] ?? 'Espace Fournisseur - Historique des Commandes Usine' ?>
                    Auto-Réapprovisionnement (Proactive)
                </button>
            </form>
        </div>
    </div>

    <h1><?= $t['supplier_history_title'] ?? 'Espace Fournisseur - Historique des Commandes Usine' ?></h1>

    <?php 
    if (isset($orders) && !empty($orders)): 
    ?>
        <div class="factory-orders-list">
            <?php 
            foreach ($orders as $orderId => $data): 
                $orderInfo = $data['info'];
            ?>
                <div class="admin-card">
                    <div class="order-header">
                        <div>
                            <h3>
                                <?= $t['supplier_order'] ?? 'Commande' ?> <span>#<?= htmlspecialchars($orderId) ?></span>
                            </h3>
                            <div>
                                <?= $t['supplier_date'] ?? 'Date :' ?> <strong><?= date('d/m/Y', strtotime($orderInfo->order_date)) ?></strong>
                            </div>
                        </div>
                        <div>
                            <div class="total-price">
                                <?= $t['supplier_total'] ?? 'Total :' ?> <?= htmlspecialchars($orderInfo->total_price) ?> €
                            </div>
                        </div>
                    </div>

                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th><?= $t['supplier_col_id'] ?? 'ID Article' ?></th>
                                <th><?= $t['supplier_col_shape'] ?? 'Forme' ?></th>
                                <th><?= $t['supplier_col_color'] ?? 'Couleur' ?></th>
                                <th><?= $t['supplier_col_qty'] ?? 'Quantité' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            foreach ($data['items'] as $item): 
                            ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($item->id_Item) ?></td>
                                    <td><?= htmlspecialchars($item->shape_name) ?></td>
                                    <td><?= htmlspecialchars($item->color_name) ?></td>
                                    <td>
                                        <span class="qty-badge"><?= htmlspecialchars($item->quantity) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
        
    <?php else: ?>
        <div class="admin-card">
            <p><?= $t['supplier_empty'] ?? 'Aucune commande passée à l\'usine pour le moment.' ?></p>
        </div>
    <?php endif; ?>
</div>