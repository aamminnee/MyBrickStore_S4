<?php
/**
 * User Settings View
 *
 * Dashboard for managing user preferences.
 * Features:
 * - Profile shortcut.
 * - Language switcher (FR/EN).
 * - Security settings (Enable/Disable 2FA).
 * - Password reset initiation.
 *
 * @var string|null $message    Feedback message (success/error)
 * @var array $t                Associative array of translations
 */

$currentLang = $_SESSION['lang'] ?? 'fr';
$is2FA = ($_SESSION['mode'] ?? '') === '2FA';
?>

<div class="settings-wrapper">
    <div class="settings-container">
        
        <div class="settings-header">
            <h1><?= $t['settings_title'] ?? 'Paramètres' ?></h1>
            <p><?= $t['settings_subtitle'] ?? 'Gérez vos préférences et la sécurité de votre compte.' ?></p>
        </div>

        <?php if (isset($message) && !empty($message)): ?>
            <div class="alert-box info">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="settings-grid">

            <div class="setting-card">
                <div class="card-icon">👤</div>
                <div class="card-content">
                    <h3><?= $t['settings_profile_title'] ?? 'Mon Profil' ?></h3>
                    <p class="card-desc"><?= $t['settings_profile_desc'] ?? 'Voir mes informations personnelles (Email, Statut).' ?></p>
                    
                    <a href="<?= $_ENV['BASE_URL'] ?>/compte" class="btn-action btn-outline">
                        <?= $t['settings_btn_profile'] ?? 'Accéder à mon tableau de bord' ?> &rarr;
                    </a>
                </div>
            </div>

            <div class="setting-card">
                <div class="card-icon">🌍</div>
                <div class="card-content">
                    <h3><?= $t['settings_lang_title'] ?? 'Langue / Language' ?></h3>
                    <p class="card-desc"><?= $t['settings_lang_desc'] ?? 'Choisissez la langue de l\'interface.' ?></p>
                    
                    <div class="language-toggle">
                        <a href="<?= $_ENV['BASE_URL'] ?>/setting/setLanguage?lang=fr" 
                           class="lang-btn <?= $currentLang === 'fr' ? 'active' : '' ?>">
                           🇫🇷 Français
                        </a>
                        <a href="<?= $_ENV['BASE_URL'] ?>/setting/setLanguage?lang=en" 
                           class="lang-btn <?= $currentLang === 'en' ? 'active' : '' ?>">
                           🇬🇧 English
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                
                <div class="setting-card">
                    <div class="card-icon">🛡️</div>
                    <div class="card-content">
                        <h3><?= $t['settings_security_title'] ?? 'Sécurité' ?></h3>
                        <p class="card-desc">
                            <?= $t['settings_2fa_label'] ?? 'Double authentification (2FA) :' ?> 
                            <span class="status-badge <?= $is2FA ? 'enabled' : 'disabled' ?>">
                                <?= $is2FA ? ($t['settings_status_enabled'] ?? 'Activé') : ($t['settings_status_disabled'] ?? 'Désactivé') ?>
                            </span>
                        </p>
                        
                        <form action="<?= $_ENV['BASE_URL'] ?>/user/toggle2FA" method="POST">
                            <?php if ($is2FA): ?>
                                <input type="hidden" name="mode" value="disable">
                                <button type="submit" class="btn-action btn-danger">
                                    <?= $t['settings_btn_disable_2fa'] ?? 'Désactiver 2FA' ?>
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="mode" value="enable">
                                <button type="submit" class="btn-action btn-primary">
                                    <?= $t['settings_btn_enable_2fa'] ?? 'Activer 2FA' ?>
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="setting-card">
                    <div class="card-icon">🔑</div>
                    <div class="card-content">
                        <h3><?= $t['settings_pwd_section_title'] ?? 'Mot de passe' ?></h3>
                        <p class="card-desc"><?= $t['settings_pwd_desc'] ?? 'Modifiez votre mot de passe pour maintenir votre compte sécurisé.' ?></p>
                        
                        <a href="<?= $_ENV['BASE_URL'] ?>/user/resetPassword" class="btn-action btn-outline">
                            <?= $t['settings_btn_reset_link'] ?? 'Changer mon mot de passe' ?>
                        </a>
                    </div>
                </div>
                
            <?php endif; ?>

        </div>
    </div>
</div>