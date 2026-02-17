<?php
/**
 * Forgot Password View
 *
 * Displays the form to request a password reset code via email.
 * Features:
 * - Email input field.
 * - Error message display.
 * - Navigation back to login.
 *
 * @var string|null $message    Feedback message (error or success)
 * @var array $t                Associative array of translations
 */
?>
<div class="main-content">
    <div class="login-container"> 
        <h2><?= $t['forgot_title'] ?? 'Réinitialisation' ?></h2>
        
        <?php if (isset($message)): ?>
            <p class="error-msg"><?= $message ?></p>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/resetPassword" method="POST">
            <div class="form-group">
                <label for="email"><?= $t['forgot_label_email'] ?? 'Votre adresse email' ?></label>
                <input type="email" id="email" name="email" required placeholder="<?= $t['forgot_placeholder_email'] ?? 'exemple@email.com' ?>">
            </div>
            
            <button type="submit" class="btn-submit"><?= $t['forgot_btn_submit'] ?? 'Envoyer le code' ?></button>
        </form>

        <div class="login-footer">
            <p><a href="<?= $_ENV['BASE_URL'] ?>/user/login"><?= $t['forgot_link_login'] ?? 'Retour à la connexion' ?></a></p>
        </div>
    </div>
</div>