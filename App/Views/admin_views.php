<?php
/**
 * Admin Dashboard View
 *
 * Displays the main administration dashboard with Key Performance Indicators (KPIs).
 * Features:
 * - Global statistics (Revenue, Order count, User count, Stock alerts).
 * - Factory/Supplier balance overview.
 * - Recent orders table.
 * - Quick action buttons for common tasks.
 *
 * @var array $stats            Associative array of KPIs (revenue, orders_count, users_count, low_stock)
 * @var array $lastOrders       List of recent orders (id, user, date, amount, status)
 * @var array $t                Translation array
 * @var int|null $_SESSION['last_factory_balance']  Cached balance from the factory system
 */
?>
<div class="admin-container">
    <div class="admin-header">
        <h1><?= $t['dashboard_title'] ?? 'Tableau de bord' ?></h1>
        <p class="text-muted"><?= $t['dashboard_subtitle'] ?? 'Aperçu de l\'activité de MyBrixStore' ?></p>
    </div>

    <div class="admin-content">
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-label"><?= $t['dashboard_revenue'] ?? 'Chiffre d\'Affaires' ?></span>
                <span class="stat-value"><?= number_format($stats['revenue'], 2) ?> €</span>
                <span class="stat-desc"><?= $t['dashboard_revenue_trend'] ?? '+12% ce mois-ci' ?></span>
            </div>

            <div class="stat-card">
                <span class="stat-label"><?= $t['dashboard_orders'] ?? 'Total Commandes' ?></span>
                <span class="stat-value"><?= $stats['orders_count'] ?></span>
                <span class="stat-desc"><?= $t['dashboard_orders_desc'] ?? 'Depuis le début' ?></span>
            </div>

            <div class="stat-card">
                <span class="stat-label"><?= $t['dashboard_users'] ?? 'Clients Inscrits' ?></span>
                <span class="stat-value"><?= $stats['users_count'] ?></span>
                <span class="stat-desc"><?= $t['dashboard_users_desc'] ?? 'Utilisateurs actifs' ?></span>
            </div>

            <div class="stat-card">
                <span class="stat-label"><?= $t['dashboard_factory_balance'] ?? 'Solde Actuel' ?></span>
                <span class="stat-value">
                    <?= isset($_SESSION['last_factory_balance']) ? number_format($_SESSION['last_factory_balance']) : '---' ?> 
                    <small class="credit">Crédits</small>
                </span>
                <span class="stat-desc">Compte B2B Fournisseur</span>
            </div>

            <div class="stat-card alert"> 
                <span class="stat-label"><?= $t['dashboard_low_stock'] ?? 'Stock Critique' ?></span>
                <span class="stat-value"><?= $stats['low_stock'] ?></span>
                <span class="stat-desc"><?= $t['dashboard_low_stock_desc'] ?? 'Références à réapprovisionner' ?></span>
            </div>
        </div>

        <hr class="separator">

        <div class="dashboard-grid">
            
            <div class="card-admin">
                <div class="card-header-flex">
                    <h2><?= $t['dashboard_latest_orders'] ?? 'Dernières Commandes' ?></h2>
                </div>

                <div class="table-responsive">
                    <table class="admin-table"> 
                        <thead>
                            <tr>
                                <th><?= $t['dashboard_col_id'] ?? 'ID' ?></th>
                                <th><?= $t['dashboard_col_client'] ?? 'Client' ?></th>
                                <th><?= $t['dashboard_col_date'] ?? 'Date' ?></th>
                                <th><?= $t['dashboard_col_amount'] ?? 'Montant' ?></th>
                                <th><?= $t['dashboard_col_status'] ?? 'Statut' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($lastOrders)): ?>
                                <?php foreach ($lastOrders as $order): ?>
                                    <tr>
                                        <td>#<?= $order['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($order['user']) ?></strong></td>
                                        <td><?= date('d/m/Y', strtotime($order['date'])) ?></td>
                                        <td class="nb"><?= number_format($order['amount'], 2) ?> €</td>
                                        <td>
                                            <span class="badge <?= $order['status'] === 'Payée' ? 'badge-success' : 'badge-warning' ?>">
                                                <?= $order['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="no-order"><?= $t['dashboard_no_orders'] ?? 'Aucune commande récente.' ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-admin">
                <h2><?= $t['dashboard_quick_actions'] ?? 'Actions Rapides' ?></h2>
                <div class="quick-actions-list">
                    
                    <a href="<?= ($_ENV['BASE_URL'] ?? '') ?>/stock" class="quick-action-btn">
                        <div class="qa-icon">📦</div>
                        <div class="qa-text">
                            <strong><?= $t['dashboard_action_inventory'] ?? 'Gérer l\'inventaire' ?></strong>
                            <span><?= $t['dashboard_action_inventory_desc'] ?? 'Ajouter ou retirer du stock' ?></span>
                        </div>
                    </a>

                    <a href="<?= ($_ENV['BASE_URL'] ?? '') ?>/admin/supplier" class="quick-action-btn">
                        <div class="qa-icon">🚚</div>
                        <div class="qa-text">
                            <strong><?= $t['dashboard_action_suppliers'] ?? 'Fournisseurs' ?></strong>
                            <span><?= $t['dashboard_action_suppliers_desc'] ?? 'Gérer les approvisionnements' ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>