<?php
/**
 * User Registration View
 *
 * Displays the account creation form.
 * Features:
 * - Fields for Username, Lastname, Email.
 * - Double password entry with visibility toggle.
 * - Error feedback display.
 * - Navigation back to login.
 *
 * @var string|null $error      Error message passed from controller
 * @var array $t                Associative array of translations
 */
?>

<div class="main-content">
    <div class="register-container">
        <h2><?= $t['register_title'] ?? 'Inscription' ?></h2>

        <?php if (isset($_SESSION['register_message'])): ?>
            <p class="error-msg"><?= $_SESSION['register_message'] ?></p>
            <?php unset($_SESSION['register_message']); ?>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/register" method="POST">
            
            <div class="form-group">
                <label for="username"><?= $t['register_label_username'] ?? "Nom d'utilisateur" ?></label>
                <input type="text" id="username" name="username" required 
                       placeholder="<?= $t['register_placeholder_username'] ?? 'Choisis ton pseudo' ?>">
            </div>

            <div class="form-group">
                <label for="lastname"><?= $t['register_label_lastname'] ?? 'Nom de famille' ?></label>
                <input type="text" name="lastname" id="lastname" required 
                       placeholder="<?= $t['register_placeholder_lastname'] ?? 'Ton nom de famille' ?>">
            </div>

            <div class="form-group">
                <label for="email"><?= $t['register_label_email'] ?? 'Adresse Email' ?></label>
                <input type="email" id="email" name="email" required 
                       placeholder="<?= $t['register_placeholder_email'] ?? 'exemple@email.com' ?>">
            </div>
            
            <div class="form-group">
                <label for="password"><?= $t['register_password_label'] ?? 'Mot de passe' ?></label>
                <div class="password-wrapper" style="position: relative;">
                    <input type="password" id="password" name="password" required placeholder="************" style="width: 100%; padding-right: 40px;">
                    <span class="toggle-password" onclick="togglePassword('password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </span>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password"><?= $t['register_confirm_label'] ?? 'Confirmer le mot de passe' ?></label>
                <div class="password-wrapper" style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="************" style="width: 100%; padding-right: 40px;">
                    <span class="toggle-password" onclick="togglePassword('confirm_password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn-submit"><?= $t['register_btn_submit'] ?? "Créer mon compte" ?></button>
        </form>

        <div class="login-footer">
            <p>
                <?= $t['register_have_account'] ?? 'Déjà un compte ?' ?> 
                <a href="<?= $_ENV['BASE_URL'] ?>/user/login"><?= $t['register_link_login'] ?? 'Se connecter' ?></a>
            </p>
        </div>
    </div>
</div>

<script src="<?= $_ENV['BASE_URL'] ?>/JS/toggle_password.js"></script>