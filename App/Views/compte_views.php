<?php
/**
 * User Account Dashboard View
 *
 * Displays the authenticated user's profile information and status.
 *
 * @var object|array $user  User data
 * @var array $t            Associative array of translations
 */
$email = is_object($user) ? $user->email : ($user['email'] ?? '');
$status = is_object($user) ? $user->status : ($user['status'] ?? 'invalide');
?>

<div class="account-wrapper">
    <div class="account-container">
        
        <div class="account-header">
            <h1><?= $t['account_title'] ?? 'Mon Tableau de Bord' ?></h1>
        </div>

        <div class="account-grid">
            
            <div class="account-card">
                <div class="card-icon-wrapper">👤</div>
                <h3><?= $t['account_personal_info'] ?? 'Mon Profil' ?></h3>
                
                <div class="info-row">
                    <span class="info-label"><?= $t['account_label_email'] ?? 'Email' ?></span>
                    <span class="info-value"><?= htmlspecialchars($email ?? '') ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label"><?= $t['account_label_status'] ?? 'Statut du compte' ?></span>
                    <?php if ($status === 'valide' || $status === 'valid'): ?>
                        <span class="status-badge enabled"><?= $t['account_status_valid'] ?? 'Vérifié' ?></span>
                    <?php else: ?>
                        <span class="status-badge disabled"><?= $t['account_status_invalid'] ?? 'Non vérifié' ?></span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>