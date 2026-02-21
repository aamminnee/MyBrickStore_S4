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
 * - TOTP setup interface.
 *
 * @var string|null $message    Feedback message (info)
 * @var string|null $success    Feedback message (success)
 * @var string|null $error      Feedback message (error)
 * @var string|null $totpSecret The user's secret key for authenticator app
 * @var array $t                Associative array of translations
 */

$currentLang = $_SESSION['lang'] ?? 'fr';
$activeMode = $_SESSION['mode'] ?? '';
$isSettingUpTotp = isset($_SESSION['setup_totp']) && $_SESSION['setup_totp'] === true;
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

        <?php if (isset($success) && !empty($success)): ?>
            <div class="alert-box success">
                <?= htmlspecialchars($success) ?>
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
                
                <!-- 2fa configuration section -->
                <div class="setting-card">
                    <div class="card-icon">🔐</div>
                    <div class="card-content">
                        <h3><?= $t['2fa_settings_title'] ?? 'Paramètres de double authentification (2FA)' ?></h3>
                        <p class="card-desc"><?= $t['2fa_settings_desc'] ?? 'Choisissez votre méthode de double authentification pour sécuriser votre compte.' ?></p>
                        
                        <?php if (isset($error) && !empty($error)): ?>
                            <div class="alert-box error" style="margin-top: 10px;">
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($isSettingUpTotp): ?>
                            
                            <!-- totp configuration step display -->
                            <div class="totp-setup-box">
                                <h4 class="totp-title"><?= $t['2fa_setup_title'] ?? 'Configuration Initiale' ?></h4>
                                
                                <div class="totp-step">
                                    <span class="step-badge">1</span>
                                    <p><?= $t['2fa_setup_step1'] ?? 'Scannez ce QR Code avec votre application (Google Authenticator, Authy...).' ?></p>
                                </div>

                                <div class="qr-container">
                                    <?php 
                                        $userEmail = $_SESSION['email'] ?? 'Utilisateur';
                                        $issuer = urlencode('MyBrickStore');
                                        $accountName = urlencode($userEmail);
                                        $otpauth = "otpauth://totp/{$issuer}:{$accountName}?secret={$totpSecret}&issuer={$issuer}";
                                        $qrCodeUrl = "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($otpauth) . "&size=200x200";
                                    ?>
                                    <img src="<?= $qrCodeUrl ?>" alt="QR Code TOTP" class="qr-image">
                                    
                                    <div class="manual-key-box">
                                        <span><?= $t['2fa_setup_manual'] ?? 'Ou saisissez cette clé manuellement :' ?></span>
                                        <strong class="totp-secret"><?= $totpSecret ?></strong>
                                    </div>
                                </div>

                                <div class="totp-step">
                                    <span class="step-badge">2</span>
                                    <p><?= $t['2fa_setup_step2'] ?? 'Entrez le code à 6 chiffres généré par l\'application pour valider :' ?></p>
                                </div>

                                <form action="<?= $_ENV['BASE_URL'] ?? '' ?>/setting/confirm2faApp" method="POST" class="totp-confirm-form">
                                    <input type="text" name="totp_code" class="totp-input" required maxlength="6" placeholder="<?= $t['2fa_setup_placeholder'] ?? '000000' ?>" autocomplete="off">
                                    <button type="submit" class="btn-action btn-primary"><?= $t['2fa_setup_confirm'] ?? 'Confirmer' ?></button>
                                </form>

                                <form action="<?= $_ENV['BASE_URL'] ?? '' ?>/setting/cancel2faApp" method="POST" class="totp-cancel-form">
                                    <button type="submit" class="btn-cancel-link"><?= $t['2fa_setup_cancel'] ?? 'Annuler la configuration' ?></button>
                                </form>
                            </div>

                        <?php else: ?>

                            <!-- standard 2fa selection options -->
                            <div class="two-fa-options">
                                
                                <form action="<?= $_ENV['BASE_URL'] ?? '' ?>/setting/update2fa" method="POST">
                                    <button type="submit" name="2fa_type" value="email" class="btn-action btn-outline btn-full <?= ($activeMode === 'email' || $activeMode === '2FA') ? 'active-primary' : '' ?>">
                                        <?= $t['2fa_email_btn'] ?? 'Activer 2FA par token de mail' ?>
                                    </button>
                                </form>
                                
                                <form action="<?= $_ENV['BASE_URL'] ?? '' ?>/setting/setup2faApp" method="POST">
                                    <button type="submit" class="btn-action btn-outline btn-full <?= ($activeMode === 'app') ? 'active-primary' : '' ?>">
                                        <?= $t['2fa_app_btn'] ?? 'Activer / Reconfigurer 2FA via application' ?>
                                    </button>
                                </form>
                                
                                <form action="<?= $_ENV['BASE_URL'] ?? '' ?>/setting/update2fa" method="POST">
                                    <button type="submit" name="2fa_type" value="none" class="btn-action btn-outline btn-full btn-danger-outline <?= empty($activeMode) ? 'active-danger' : '' ?>">
                                        <?= $t['2fa_disable_btn'] ?? 'Ne pas utiliser le 2FA' ?>
                                    </button>
                                </form>

                            </div>

                            <?php if ($activeMode === 'app'): ?>
                                <p class="active-2fa-msg">
                                    <span class="icon-check">✅</span> <?= $t['2fa_setup_active_msg'] ?? 'L\'authentification par application est active et configurée.' ?>
                                </p>
                            <?php endif; ?>

                        <?php endif; ?>

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