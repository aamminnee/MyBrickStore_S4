<?php
/**
 * 2FA / Code Verification View
 *
 * Displays a form to enter the 6-digit security code.
 * Differentiates between Email Code and Authenticator App (TOTP) instructions.
 * Includes functionality to request a new code.
 *
 * @var string|null $message    Error message passed from controller
 * @var string|null $success    Success message passed from controller
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

        <?php if (isset($success)): ?>
            <p class="success-msg" style="color: #047857; font-weight: bold; margin-bottom: 15px; padding: 10px; background-color: #d1fae5; border-radius: 6px; border-left: 4px solid #047857;">
                <?= $success ?>
            </p>
        <?php endif; ?>

        <?php if (isset($message)): ?>
            <p class="error-msg" style="color: #D92328; font-weight: bold; margin-bottom: 15px; padding: 10px; background-color: #fee2e2; border-radius: 6px; border-left: 4px solid #D92328;">
                <?= $message ?>
            </p>
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
        
        <?php if ($mode !== 'app'): ?>
            <div class="resend-container" style="margin-top: 20px; text-align: center;">
                <p style="font-size: 0.9em; color: #666; margin-bottom: 5px;"><?= $t['verify_didnt_receive'] ?? "Vous n'avez pas reçu le code ?" ?></p>
                <form action="<?= $_ENV['BASE_URL'] ?>/user/resendCode" method="POST" style="display: inline;">
                    <button type="submit" class="btn-resend" style="background: none; border: none; color: #006CB7; font-weight: bold; text-decoration: underline; cursor: pointer; padding: 0;">
                        <?= $t['verify_btn_resend'] ?? "Renvoyer un nouveau code" ?>
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="verify-footer" style="margin-top: 15px;">
            <a href="<?= $_ENV['BASE_URL'] ?>/user/login" class="back-link">
                <?= $t['verify_link_back'] ?? '&larr; Retour à la connexion' ?>
            </a>
        </div>
    </div>

</div>