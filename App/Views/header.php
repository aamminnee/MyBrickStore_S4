<?php
/**
 * main header view
 *
 * displays the top navigation bar for the public site.
 * features:
 * - logo.
 * - dynamic navigation based on login status.
 * - cart icon with item count.
 * - profile dropdown menu.
 * - language switcher with flags.
 *
 * @var array $t associative array of translations
 */

// check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
// get base url safely
$baseUrl = $_ENV['BASE_URL'] ?? '';

// initialize cart count
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = count($_SESSION['cart']);
}
?>

<header>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <div class="header-container">
        <!-- site logo -->
        <a href="<?= $baseUrl ?>/index.php" class="logo">
            <img src="<?= $baseUrl ?>/img/logo.png" alt="MyBrixStore Logo">
        </a>

        <!-- main navigation menu -->
        <nav class="main-nav">
            <ul>
                <!-- shopping cart icon -->
                <li class="nav-item">
                    <a href="<?= $baseUrl ?>/cart" class="btn-header cart-btn" id="cart-container" title="<?= $t['nav_cart'] ?? 'Panier' ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
                        </svg>
                        
                        <!-- cart item counter badge -->
                        <span id="cart-count" class="cart-badge" style="<?= $cartCount > 0 ? 'display:flex;' : 'display:none;' ?>">
                            <?= $cartCount ?>
                        </span>
                    </a>
                </li>

                <?php if ($isLoggedIn): ?>
                    
                    <!-- user profile dropdown -->
                    <li class="profile-menu">
                        <div class="profile-trigger">
                            <span><?= htmlspecialchars($_SESSION['user_name'] ?? ($t['nav_account'] ?? 'Mon Compte')) ?></span>
                            <img src="<?= $baseUrl ?>/img/avatar.png" alt="Avatar" class="avatar-mini" 
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user_name'] ?? 'User') ?>&background=random'">
                        </div>
                        
                        <ul class="dropdown">
                            <li><a href="<?= $baseUrl ?>/setting"><?= $t['nav_settings'] ?? 'Paramètres' ?></a></li>
                            <li><a href="<?= $baseUrl ?>/commande"><?= $t['nav_orders'] ?? 'Mes Commandes' ?></a></li>
                            <li class="separator"></li>
                            <li><a href="<?= $baseUrl ?>/user/logout" class="logout-btn"><?= $t['nav_logout'] ?? 'Déconnexion' ?></a></li>
                        </ul>
                    </li>

                <?php else: ?>
                    <!-- login and register links for guests -->
                    <li><a href="<?= $baseUrl ?>/user/login" class="nav-link"><?= $t['nav_login'] ?? 'Connexion' ?></a></li>
                    <li><a href="<?= $baseUrl ?>/user/register" class="btn-header"><?= $t['nav_register'] ?? 'Inscription' ?></a></li>
                    
                    <!-- language switcher with flags -->
                    <li class="lang-switch-container">
                        <?php 
                        // default language is french
                        $currentLang = $_SESSION['lang'] ?? 'fr'; 
                        ?>
                        
                        <!-- french flag button -->
                        <a href="<?= $baseUrl ?>/setting/setLanguage?lang=fr" 
                           class="lang-link <?= $currentLang === 'fr' ? 'active' : '' ?>"
                           title="Français">
                           <img src="https://flagcdn.com/w40/fr.png" srcset="https://flagcdn.com/w80/fr.png 2x" alt="FR" class="flag-icon">
                        </a>
                        
                        <!-- us flag button for english -->
                        <a href="<?= $baseUrl ?>/setting/setLanguage?lang=en" 
                           class="lang-link <?= $currentLang === 'en' ? 'active' : '' ?>"
                           title="English">
                           <img src="https://flagcdn.com/w40/us.png" srcset="https://flagcdn.com/w80/us.png 2x" alt="US" class="flag-icon">
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>