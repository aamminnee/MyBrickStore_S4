<?php
/**
 * Reset Password Form View
 *
 * Displays the form to define a new password after clicking the email link.
 * Features:
 * - Double password entry for confirmation.
 * - Password visibility toggle.
 * - Error message display.
 *
 * @var string|null $message    Feedback message (error or success)
 * @var array $t                Associative array of translations
 */
?>

<div class="reset-wrapper">

    <div class="reset-container">
        <div class="icon-key">🔑</div>
        
        <h2><?= $t['reset_title'] ?? 'Nouveau mot de passe' ?></h2>
        <p class="reset-desc"><?= $t['reset_desc'] ?? 'Sécurisez votre compte avec un nouveau mot de passe fort.' ?></p>
        
        <?php if (isset($message) && !empty($message)): ?>
            <div class="alert error-msg">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/resetPasswordForm" method="POST">
            <input type="hidden" name="reset_password" value="true">
            
            <div class="form-group">
                <label for="password"><?= $t['reset_label_new_pass'] ?? 'Nouveau mot de passe' ?></label>
                <input type="password" id="password" name="password" required placeholder="••••••••" autocomplete="new-password">
            </div>
            
            <div class="form-group">
                <label for="password_confirm"><?= $t['reset_label_confirm_pass'] ?? 'Confirmer le mot de passe' ?></label>
                <input type="password" id="password_confirm" name="password_confirm" required placeholder="••••••••" autocomplete="new-password">
            </div>
            
            <button type="submit" class="btn-submit"><?= $t['reset_btn_change'] ?? 'Changer le mot de passe' ?></button>
        </form>
    </div>

</div>