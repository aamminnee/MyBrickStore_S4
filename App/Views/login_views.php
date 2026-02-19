<?php
/**
 * User Login View
 *
 * Displays the login form with username, password (toggleable visibility), and a custom captcha.
 * Features:
 * - Error message display.
 * - Password visibility toggle.
 * - HTML5 Canvas Captcha integration.
 * - Links to Registration and Password Reset.
 *
 * @var string|null $message    Feedback message (error or success)
 * @var array $t                Associative array of translations
 */
?>

<div class="main-content">
    <div class="login-container">
        <h2><?= $t['login_title'] ?? 'Connexion' ?></h2>
        
        <?php if (isset($message)): ?>
            <p class="error-msg"><?= $message ?></p>
        <?php endif; ?>

        <form action="<?= $_ENV['BASE_URL'] ?>/user/login" method="POST">
            <div class="form-group">
                <label for="email"><?= $t['register_label_email'] ?? 'Adresse Email' ?></label>
                <input type="email" id="email" name="email" required 
                    placeholder="<?= $t['register_placeholder_email'] ?? 'exemple@email.com' ?>" 
                    autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password"><?= $t['login_password_label'] ?? 'Mot de passe' ?></label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" required placeholder="************">
                    <span class="toggle-password" onclick="togglePassword('password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #666;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-eye-off"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                    </span>
                </div>
            </div>

            <div class="captcha-group">
                <div class="captcha-visual">
                    <canvas id="captcha-canvas" width="200" height="50"></canvas>
                    <button id="captcha-refresh" type="button" title="<?= $t['login_tooltip_refresh'] ?? 'Changer le code' ?>">↻</button>
                </div>
                <input type="hidden" id="captcha_token" name="captcha_token" value="">
                <input type="text" name="captcha" class="captcha-input" 
                       placeholder="<?= $t['login_placeholder_captcha'] ?? 'Recopier le code' ?>" 
                       required autocomplete="off">
            </div>
            
            <button type="submit" class="btn-submit"><?= $t['login_btn_submit'] ?? 'Se connecter' ?></button>
        </form>

        <div class="login-footer">
            <p>
                <?= $t['login_text_no_account'] ?? 'Pas encore de compte ?' ?> 
                <a href="<?= $_ENV['BASE_URL'] ?>/user/register"><?= $t['login_link_register'] ?? "Créer un compte" ?></a>
            </p>
            <p>
                <a href="<?= $_ENV['BASE_URL'] ?>/user/resetPassword"><?= $t['login_link_forgot'] ?? 'Mot de passe oublié ?' ?></a>
            </p>
        </div>
    </div>
</div>

<script src="<?= $_ENV['BASE_URL'] ?>/JS/captcha.js"></script>
<script src="<?= $_ENV['BASE_URL'] ?>/JS/toggle_password.js"></script>