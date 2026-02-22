<!-- footer main container -->
<footer class="site-footer">
    <div class="footer-top">
        <!-- brand and description -->
        <div class="footer-col">
            <h3 class="footer-logo">MyBrickStore</h3>
            <p>votre boutique de briques en ligne préférée. construisez vos rêves pièce par pièce avec notre large sélection.</p>
        </div>
        
        <!-- quick links -->
        <div class="footer-col">
            <h4>navigation</h4>
            <ul>
                <li><a href="<?= $baseUrl ?>/index.php">accueil</a></li>
                <li><a href="<?= $baseUrl ?>/cart">votre panier</a></li>
            </ul>
        </div>

        <!-- about and partners -->
        <div class="footer-col">
            <h4>à propos</h4>
            <ul>
                <!-- link to the team page, matching the TeamController name -->
                <li><a  href="<?= $baseUrl ?>/team">notre équipe</a></li>
                <!-- external link to azer ty -->
                <li><a href="https://azer-ty.fr" target="_blank" rel="noopener noreferrer">partenaire : azer ty</a></li>
            </ul>
        </div>

        <!-- contact info -->
        <div class="footer-col">
            <h4>contactez-nous</h4>
            <p>email: contact@mybrickstore.com</p>
            <p>téléphone: +33 1 23 45 67 89</p>
        </div>
    </div>
    
    <!-- copyright section -->
    <div class="footer-bottom">
        <p>&copy; 2026 MyBrickStore. tous droits réservés.</p>
    </div>
</footer>