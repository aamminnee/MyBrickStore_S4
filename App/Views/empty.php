<?php
/**
 * Empty Layout Template
 *
 * A minimal layout wrapper used for specific views that require no navigation, 
 * header, or footer (e.g., printable assembly plans, PDF generation).
 *
 * @var string $content       The rendered HTML content of the specific view to display
 */

$lang = $_SESSION['lang'] ?? 'fr';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
    <?= $content ?>
</html>