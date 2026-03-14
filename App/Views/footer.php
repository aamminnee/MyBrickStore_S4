<?php
/**
 * footer view component
 * * displays site-wide footer with multilingual support.
 * utilizes translation array $t passed from controller.
 * * @package App\Views
 */

// ensure base url is available
$baseUrl = $baseUrl ?? ($_ENV['BASE_URL'] ?? '');
?>

<footer class="site-footer">
    <div class="footer-top">
        <div class="footer-col">
            <h3 class="footer-logo">MyBrickStore</h3>
            <p><?= $t['footer_description'] ?? 'votre boutique de briques en ligne préférée. construisez vos rêves pièce par pièce avec notre large sélection.' ?></p>
        </div>
        
        <div class="footer-col">
            <h4><?= $t['footer_nav_title'] ?? 'navigation' ?></h4>
            <ul>
                <li><a href="<?= $baseUrl ?>/index.php"><?= $t['footer_nav_home'] ?? 'Accueil' ?></a></li>
                <li><a href="<?= $baseUrl ?>/cart"><?= $t['footer_nav_cart'] ?? 'Panier' ?></a></li>
                <li><a href="<?= $baseUrl ?>/commande"><?= $t['footer_nav_orders'] ?? 'Mes commandes' ?></a></li>
                <li><a href="<?= $baseUrl ?>/compte"><?= $t['footer_nav_profile'] ?? 'Profil' ?></a></li>
                <li><a href="<?= $baseUrl ?>/setting"><?= $t['footer_nav_settings'] ?? 'Paramètres' ?></a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4><?= $t['footer_about_title'] ?? 'à propos' ?></h4>
            <ul>
                <li><a href="<?= $baseUrl ?>/team"><?= $t['footer_about_team'] ?? 'notre équipe' ?></a></li>
                <li><a href="https://azer-ty.fr" target="_blank" rel="noopener noreferrer"><?= $t['footer_about_partner'] ?? 'partenaire : azer ty' ?></a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4><?= $t['footer_contact_title'] ?? 'contactez-nous' ?></h4>
            <p><?= $t['footer_contact_email'] ?? 'email' ?>: contact@mybrickstore.com</p>
            <p><?= $t['footer_contact_phone'] ?? 'téléphone' ?>: +33 1 23 45 67 89</p>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> MyBrickStore. <?= $t['footer_rights_reserved'] ?? 'tous droits réservés.' ?></p>
    </div>
</footer>