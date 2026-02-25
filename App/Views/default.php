<?php
/**
 * Main Layout Template
 *
 * The master template wrapping all view content.
 * Handles the HTML skeleton, dynamic CSS loading, Header/Footer inclusion, and Matomo analytics.
 *
 * @var string $content       The rendered HTML content of the specific view to display
 * @var string|null $titre    The page title (defaults to 'MyBrickStore')
 * @var string|null $css      Optional specific CSS filename for the current view (e.g. 'cart_views.css')
 */

$baseUrl = $_ENV['BASE_URL'] ?? '';
$lang = $_SESSION['lang'] ?? 'fr';
$isAdmin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titre ?? 'MyBrickStore') ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <?php if ($isAdmin): ?>
        <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/header_admin.css">
    <?php else: ?>
        <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/header.css">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/footer.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/style.css">


    <?php if (isset($css) && !empty($css)): ?>
        <link rel="stylesheet" href="<?= $baseUrl ?>/CSS/<?= htmlspecialchars($css) ?>">
    <?php endif; ?>
    
    <link rel="icon" href="<?= $baseUrl ?>/img/favicon.png">
</head>
<body>
    
    <?php 
    if ($isAdmin) {
        require_once ROOT . '/App/Views/header_admin.php';
    } else {
        require_once ROOT . '/App/Views/header.php';
    }
    ?>

    <main class="main-container">
        <?= $content ?>
    </main>

    <?php require_once ROOT . '/App/Views/footer.php'; ?>

    <script>
        var _paq = window._paq = window._paq || [];
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        (function() {
            var u="//localhost/matomo/";
            _paq.push(['setTrackerUrl', u+'matomo.php']);
            _paq.push(['setSiteId', '1']);
            var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
            g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
        })();
    </script>
</body>
</html>