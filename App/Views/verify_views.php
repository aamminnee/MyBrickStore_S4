<?php
/**
 * 2FA / Email Verification View
 *
 * Displays a form to enter the 6-digit security code sent via email.
 * Features:
 * - Centered numeric input optimized for codes.
 * - Error message display.
 * - Navigation back to login.
 *
 * @var string|null $error      Error message passed from controller
 * @var array $t                Associative array of translations
 */
?>

<div class="verify-wrapper">
    
    <div class="verify-container">
        <div class="icon-lock">🔒</div>
        
        <h2><?= $t['verify_title'] ?? 'Vérification' ?></h2>
        <p class="verify-desc"><?= $t['verify_desc'] ?? 'Un code de sécurité a été envoyé à votre adresse email.' ?></p>

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