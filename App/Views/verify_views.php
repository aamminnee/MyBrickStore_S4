<?php
/**
 * 2FA / Code Verification View
 *
 * Displays a form to enter the 6-digit security code.
 * Differentiates between Email Code and Authenticator App (TOTP) instructions.
 *
 * @var string|null $error      Error message passed from controller
 * @var array $t                Associative array of translations
 */

$mode = $_SESSION['temp_2fa_mode'] ?? 'email'; 
?>

<div class="verify-wrapper">
    
    <div class="verify-container">
        <div class="icon-lock"><?= $mode === 'app' ? '📱' : '🔒' ?></div>
        
        <h2><?= $t['verify_title'] ?? 'Vérification' ?></h2>
        
        <?php if ($mode === 'app'): ?>
            <p class="verify-desc"><?= $t['verify_desc_app'] ?? 'Ouvrez votre application d\'authentification (Google Authenticator) et saisissez le code à 6 chiffres.' ?></p>
        <?php else: ?>
            <p class="verify-desc"><?= $t['verify_desc'] ?? 'Un code de sécurité a été envoyé à votre adresse email.' ?></p>
        <?php endif; ?>

        <?php if (isset($message)): ?>
            <p class="error-msg" style="color: #D92328; font-weight: bold; margin-bottom: 15px;"><?= $message ?></p>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/verify" method="POST">
            <div class="form-group">
                <input type="text" id="token" name="token" required 
                       class="code-input" 
                       placeholder="<?= $t['verify_placeholder_token'] ?? '000000' ?>" 
                       maxlength="6" 
                       autocomplete="off">
            </div>
            <button type="submit" class="btn-submit"><?= $t['verify_btn_validate'] ?? 'Valider le code' ?></button>
        </form>
        
        <div class="verify-footer">
            <a href="<?= $_ENV['BASE_URL'] ?>/user/login" class="back-link">
                <?= $t['verify_link_back'] ?? '&larr; Retour à la connexion' ?>
            </a>
        </div>
    </div>

</div>