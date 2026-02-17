<?php
/**
 * Admin Statistics View
 *
 * Displays the local Matomo dashboard via an iframe for audience measurement.
 *
 * @var array $t   Associative array of translations (keys: stats_page_title, stats_dashboard_title, etc.)
 */
?>
<div class="admin-container">
    <h1><?= $t['stats_page_title'] ?? 'Mesure d\'audience (Statistiques)' ?></h1>
    
    <div class="admin-card">
        <h3><?= $t['stats_dashboard_title'] ?? 'Tableau de bord Matomo (Local)' ?></h3>
        
        <p><?= $t['stats_dashboard_desc'] ?? 'Données de fréquentation hébergées localement.' ?></p>
        
        <div class="stats-placeholder">
            <iframe 
                style="width: 100%; height: 800px; border: 0; overflow: hidden;" 
                src="http://localhost/matomo/index.php?module=Widgetize&action=iframe&moduleToWidgetize=Dashboard&actionToWidgetize=index&idSite=1&period=day&date=yesterday"
            ></iframe>
        </div>
    </div>
    
    <div class="admin-card">
        <p><em><?= $t['stats_reminder'] ?? 'Rappel : Pour générer des données, naviguez sur le site en tant que visiteur.' ?></em></p>
        <a href="http://localhost/matomo/index.php?module=CoreHome&action=index&idSite=1&period=day&date=yesterday#?period=day&date=yesterday&category=Dashboard_Dashboard&subcategory=1" target="_blank" class="btn-primary">
            <?= $t['stats_btn_interface'] ?? 'Interface complète Matomo' ?>
        </a>
    </div>
</div>