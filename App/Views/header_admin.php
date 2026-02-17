<?php
/**
 * Admin Header Partial View
 *
 * The top navigation bar specific to the Administration panel.
 * Features:
 * - Admin-specific logo.
 * - Navigation links to Stats, Supplier, Inventory, and Settings.
 * - Logout button.
 *
 * @var array $t            Associative array of translations
 */

$baseUrl = $_ENV['BASE_URL'] ?? dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim($baseUrl, '/\\');

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
?>

<header class="header header-admin">
    <div class="header-container">
        
        <a href="<?= $baseUrl ?>/admin" class="logo">
            <img src="<?= $baseUrl ?>/img/logo_admin.png" alt="Admin Logo" class="logo-img">
        </a>

        <nav class="nav-menu main-nav">
            <?php if ($isAdmin): ?>
                
                <a href="<?= $baseUrl ?>/admin/stats" class="nav-link">
                    <?= $t['header_admin_stats'] ?? 'Statistiques' ?>
                </a>
                <a href="<?= $baseUrl ?>/admin/supplier" class="nav-link">
                    <?= $t['header_admin_supplier'] ?? 'Fournisseur' ?>
                </a>
                <a href="<?= $baseUrl ?>/stock" class="nav-link">
                    <?= $t['header_admin_inventory'] ?? 'Inventaire' ?>
                </a>
                <a href="<?= $baseUrl ?>/setting" class="nav-link">
                    <?= $t['header_admin_settings'] ?? 'Paramètres' ?>
                </a>

                <a href="<?= $baseUrl ?>/user/logout" class="btn-header-base btn-outline">
                    <?= $t['header_admin_logout'] ?? 'Déconnexion' ?>
                </a>

            <?php else: ?>
                <a href="<?= $baseUrl ?>/index.php" class="btn-header-base btn-primary">
                    <?= $t['header_admin_back'] ?? 'Retour au site' ?>
                </a>
            <?php endif; ?>
        </nav>
        
    </div>
</header>