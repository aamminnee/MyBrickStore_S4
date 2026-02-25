<?php
/**
 * user account dashboard view
 *
 * displays the authenticated user's profile information, status, and edit form.
 *
 * @var object|array $user  user data
 * @var string|null $success success message
 * @var string|null $error  error message
 * @var array $t            associative array of translations
 */
$email = is_object($user) ? $user->email : ($user['email'] ?? '');
$status = is_object($user) ? $user->status : ($user['status'] ?? 'invalide');

$firstName = is_object($user) ? ($user->first_name ?? '') : ($user['first_name'] ?? '');
$lastName = is_object($user) ? ($user->last_name ?? '') : ($user['last_name'] ?? '');
$phone = is_object($user) ? ($user->phone ?? '') : ($user['phone'] ?? '');
$address = is_object($user) ? ($user->address_line ?? '') : ($user['address_line'] ?? '');
$zip = is_object($user) ? ($user->zip_code ?? '') : ($user['zip_code'] ?? '');
$city = is_object($user) ? ($user->city ?? '') : ($user['city'] ?? '');

$avatarSrc = $_ENV['BASE_URL'] . '/img/avatar.png'; 
if (!empty($user->avatar)) {
    $avatarSrc = 'data:image/jpeg;base64,' . base64_encode($user->avatar);
}
?>

<div class="account-wrapper">
    <div class="account-container">
        
        <div class="account-header">
            <h1><?= $t['account_title'] ?? 'Mon Tableau de Bord' ?></h1>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert-box success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert-box error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="account-grid">
            
            <div class="account-card">
                <div class="card-icon-wrapper">
                    <img src="<?= $avatarSrc ?>" alt="Avatar" class="avatar-circle" style="width:100px; height:100px; border-radius:50%; object-fit:cover;">
                </div>
                <h3><?= $t['account_personal_info'] ?? 'Statut du Profil' ?></h3>
                
                <div class="info-row">
                    <span class="info-label"><?= $t['account_label_email'] ?? 'Email Actuel' ?></span>
                    <span class="info-value"><?= htmlspecialchars($email ?? '') ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label"><?= $t['account_label_status'] ?? 'Statut du compte' ?></span>
                    <?php if ($status === 'valide' || $status === 'valid'): ?>
                        <span class="status-badge enabled"><?= $t['account_status_valid'] ?? 'Vérifié' ?></span>
                    <?php else: ?>
                        <span class="status-badge disabled"><?= $t['account_status_invalid'] ?? 'Non vérifié' ?></span>
                        <div style="margin-top: 15px;">
                            <a href="<?= $_ENV['BASE_URL'] ?>/compte/activer" class="btn-action btn-primary" style="display:inline-block; text-align:center;">
                                <?= $t['account_btn_verify'] ?? 'Vérifier mon compte' ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="account-card form-card" id="profile-view-mode">
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="margin: 0;"><?= $t['account_edit_title'] ?? 'Mes Informations Personnelles' ?></h3>
                    <button type="button" class="btn-edit-icon" onclick="toggleProfileEdit()" title="Tout modifier" style="background: none; border: none; cursor: pointer; color: #666; transition: color 0.3s;" onmouseover="this.style.color='#000'" onmouseout="this.style.color='#666'">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="profile-data-list">
                    
                    <div class="data-item">
                        <div class="data-info">
                            <span class="data-label"><?= $t['payment_label_firstname'] ?? 'Prénom' ?></span>
                            <span class="data-value"><?= !empty($firstName) ? htmlspecialchars($firstName) : 'Non renseigné' ?></span>
                        </div>
                    </div>

                    <div class="data-item">
                        <div class="data-info">
                            <span class="data-label"><?= $t['payment_label_lastname'] ?? 'Nom' ?></span>
                            <span class="data-value"><?= !empty($lastName) ? htmlspecialchars($lastName) : 'Non renseigné' ?></span>
                        </div>
                    </div>

                    <div class="data-item">
                        <div class="data-info">
                            <span class="data-label"><?= $t['register_label_email'] ?? 'Adresse Email' ?></span>
                            <span class="data-value"><?= !empty($email) ? htmlspecialchars($email) : 'Non renseigné' ?></span>
                        </div>
                    </div>

                    <div class="data-item">
                        <div class="data-info">
                            <span class="data-label"><?= $t['payment_label_phone'] ?? 'Téléphone' ?></span>
                            <span class="data-value"><?= !empty($phone) ? htmlspecialchars($phone) : 'Non renseigné' ?></span>
                        </div>
                    </div>

                    <div class="data-item">
                        <div class="data-info">
                            <span class="data-label"><?= $t['payment_label_address'] ?? 'Adresse postale' ?></span>
                            <span class="data-value">
                                <?php 
                                    if(!empty($address) || !empty($zip) || !empty($city)) {
                                        echo htmlspecialchars(trim("$address $zip $city"));
                                    } else {
                                        echo 'Non renseignée';
                                    }
                                ?>
                            </span>
                        </div>
                    </div>

                </div>
            </div>

            <div class="account-card form-card" id="profile-edit-mode" style="display: none;">
                <h3><?= $t['account_edit_title'] ?? 'Mes Informations Personnelles' ?></h3>
                <p class="form-desc" style="margin-bottom: 20px; font-size: 0.9em; color: #666;">
                    <?= $t['account_edit_desc'] ?? 'Mettez à jour vos coordonnées. Pour des raisons de sécurité, une validation par email sera demandée.' ?>
                </p>

                <form action="<?= $_ENV['BASE_URL'] ?>/compte/updateAvatar" method="POST" enctype="multipart/form-data" id="avatar-form">
                    <div class="form-group" style="margin-bottom: 20px; align-items: center; display: flex; flex-direction: column; gap: 10px;">
                        <label for="avatar_upload" style="cursor: pointer;">
                            <div class="avatar-preview-container">
                                <img src="<?= $avatarSrc ?>" alt="Avatar" class="avatar-large" id="avatar-preview">
                                <div class="avatar-overlay"><?= $t['account_update_avatar'] ?? 'Modifier la photo' ?></div>
                            </div>
                        </label>
                        <input type="file" id="avatar_upload" name="avatar" style="display: none;" accept="image/*" onchange="document.getElementById('avatar-form').submit();">

                        <?php if (!empty($user->avatar) || !empty($_SESSION['user_avatar'])): ?>
                            <a href="#" onclick="event.preventDefault(); document.getElementById('remove-avatar-form').submit();" style="color: #D92328; font-size: 0.9em; text-decoration: underline;">
                                <?= $t['account_delete_avatar'] ?? 'Supprimer la photo actuelle' ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <form action="<?= $_ENV['BASE_URL'] ?>/compte/update" method="POST" class="profile-form">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name"><?= $t['payment_label_firstname'] ?? 'Prénom' ?></label>
                            <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($firstName) ?>" placeholder="Jean">
                        </div>
                        <div class="form-group">
                            <label for="last_name"><?= $t['payment_label_lastname'] ?? 'Nom' ?></label>
                            <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($lastName) ?>" placeholder="Dupont">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email"><?= $t['register_label_email'] ?? 'Adresse Email' ?></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone"><?= $t['payment_label_phone'] ?? 'Téléphone' ?></label>
                        <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" placeholder="06 12 34 56 78">
                    </div>

                    <div class="form-group">
                        <label for="address_line"><?= $t['payment_label_address'] ?? 'Adresse postale' ?></label>
                        <input type="text" id="address_line" name="address_line" class="form-control" value="<?= htmlspecialchars($address) ?>" placeholder="123 Rue de la Brique">
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 1;">
                            <label for="zip_code"><?= $t['payment_label_zip'] ?? 'Code Postal' ?></label>
                            <input type="text" id="zip_code" name="zip_code" class="form-control" value="<?= htmlspecialchars($zip) ?>" placeholder="75000">
                        </div>
                        <div class="form-group" style="flex: 2;">
                            <label for="city"><?= $t['payment_label_city'] ?? 'Ville' ?></label>
                            <input type="text" id="city" name="city" class="form-control" value="<?= htmlspecialchars($city) ?>" placeholder="Paris">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-action btn-cancel" onclick="toggleProfileEdit()">
                               <?= $t['account_btn_return'] ?? 'Annuler' ?>
                        </button>
                        <button type="submit" class="btn-action btn-primary submit-btn">
                            <?= $t['account_btn_save'] ?? 'Valider les modifications' ?>
                        </button>
                    </div>
                </form>
            </div>
            <form id="remove-avatar-form" action="<?= $_ENV['BASE_URL'] ?>/compte/removeAvatar" method="POST" style="display: none;"></form>

        </div>
    </div>
</div>
<script src="<?= $_ENV['BASE_URL'] ?>/JS/account.js"></script>