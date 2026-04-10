<?php
/**
 * promotional game page view
 *
 * @var array  $t               associative array of translations
 * @var string $gameUrl         url to play the game
 * @var string $appDownloadUrl  url to download the app
 * @var array  $policy          loyalty points policy configuration
 */
?>
<?php // load the specific stylesheet for the promo page ?>
<link rel="stylesheet" href="/css/game_promo_views.css">

<div class="promo-page-wrapper">
    <div class="promo-container">
        <div class="promo-hero">
            <div class="promo-logo">
                <img src="<?= $_ENV['BASE_URL']?>/img/logo_jeux.png" alt="Logo MyBrickGames" style="max-width: 600px; margin-bottom: 10px;">
            </div>
            
            <h1 class="promo-hero-title"><?= $t['promo_hero_title'] ?? 'découvrez' ?></h1>
            <p class="promo-hero-subtitle"><?= $t['promo_hero_subtitle'] ?? 'jouez, construisez, et gagnez des points de fidélité !' ?></p>
            
            <div class="hero-actions">
                <a href="<?= htmlspecialchars($gameUrl ?? '#') ?>" class="btn-play" target="_blank"><?= $t['promo_btn_play'] ?? 'jouer maintenant !' ?></a>
                <a href="<?= htmlspecialchars($appDownloadUrl ?? '#') ?>" class="btn-download" target="_blank" download><?= $t['promo_btn_download'] ?? 'télécharger mybrickapp' ?></a>
            </div>
        </div>

        <h2 class="promo-section-title"><?= $t['promo_modes_title'] ?? 'nos modes de jeu' ?></h2>
        <div class="promo-grid">
            <div class="promo-card">
                <div class="promo-card-image">
                    <img src="<?= $_ENV['BASE_URL']?>/img/reproduction.png" alt="<?= $t['promo_mode_repro_title'] ?? 'reproduction' ?>">
                </div>
                <h3 class="promo-card-title"><?= $t['promo_mode_repro_title'] ?? 'reproduction' ?></h3>
                <p class="promo-card-text"><?= $t['promo_mode_repro_desc'] ?? 'reproduisez des mosaïques de briques le plus fidèlement possible. choisissez votre difficulté (facile, moyen, difficile) et prouvez vos talents de constructeur !' ?></p>
            </div>

            <div class="promo-card">
                <div class="promo-card-image">
                    <img src="<?= $_ENV['BASE_URL']?>/img/tetris.png" alt="<?= $t['promo_mode_tetris_title'] ?? 'casse-briques' ?>">
                </div>
                <h3 class="promo-card-title"><?= $t['promo_mode_tetris_title'] ?? 'casse-briques' ?></h3>
                <p class="promo-card-text"><?= $t['promo_mode_tetris_desc'] ?? 'placez vos briques stratégiquement pour compléter des lignes et marquer un maximum de points. attention, le plateau se remplit très vite !' ?></p>
            </div>

            <div class="promo-card">
                <div class="promo-card-image">
                    <img src="<?= $_ENV['BASE_URL']?>/img/multi.png" alt="<?= $t['promo_mode_multi_title'] ?? 'mode multijoueur' ?>">
                </div>
                <h3 class="promo-card-title"><?= $t['promo_mode_multi_title'] ?? 'mode multijoueur' ?></h3>
                <p class="promo-card-text"><?= $t['promo_mode_multi_desc'] ?? 'défiez vos amis ! créez un salon d\'attente, partagez votre code et affrontez-vous en temps réel pour déterminer qui est le meilleur architecte.' ?></p>
            </div>
        </div>

        <h2 class="promo-section-title title-green"><?= $t['promo_loyalty_title'] ?? 'gagnez des bons d\'achat' ?></h2>
        <div class="policy-section">
            <p class="policy-intro">
                <?= $t['promo_loyalty_intro'] ?? 'chaque partie jouée vous rapporte des points de fidélité, directement convertibles en bons d\'achat sur notre boutique. voici comment maximiser vos gains :' ?>
            </p>

            <ul class="policy-list">
                <li>
                    <span class="policy-text"><strong><?= $t['promo_policy_part_label'] ?? 'participation :' ?></strong> <?= $t['promo_policy_part_desc'] ?? 'chaque partie vous rapporte des points garantis, même en cas de défaite !' ?></span>
                    <span class="promo-badge">+<?= htmlspecialchars($policy['participation']['points'] ?? '50') ?> <?= $t['promo_pts'] ?? 'pts' ?></span>
                </li>

                <li>
                    <span class="policy-text"><strong><?= $t['promo_policy_repro_label'] ?? 'jeu de reproduction :' ?></strong> <?= $t['promo_policy_repro_desc'] ?? 'gagnez des points bonus en jouant dans les modes de difficulté supérieurs.' ?></span>
                    <span class="promo-badge"><?= $t['promo_policy_multiplier'] ?? 'multiplicateur' ?> x<?= htmlspecialchars($policy['reproduction']['multipliers']['hard'] ?? '4.0') ?> (<?= $t['promo_policy_hard'] ?? 'difficile' ?>)</span>
                </li>
                <li>
                    <span class="policy-text"><strong><?= $t['promo_policy_perf_label'] ?? 'reproduction parfaite :' ?></strong> <?= $t['promo_policy_perf_desc'] ?? 'reproduisez l\'image à 100% sans la moindre erreur.' ?></span>
                    <span class="promo-badge">+<?= htmlspecialchars($policy['reproduction']['perfectBonus']['points'] ?? '100') ?> <?= $t['promo_pts'] ?? 'pts' ?></span>
                </li>

                <li>
                    <span class="policy-text"><strong><?= $t['promo_policy_tetris_label'] ?? 'casse-briques :' ?></strong> <?= $t['promo_policy_tetris_desc'] ?? 'accomplissez des exploits en dépassant des paliers de score.' ?></span>
                    <span class="promo-badge">+<?= htmlspecialchars($policy['tetris']['achievements'][0]['bonus'] ?? '500') ?> <?= $t['promo_pts'] ?? 'pts' ?> (> <?= htmlspecialchars($policy['tetris']['achievements'][0]['threshold'] ?? '20000') ?> <?= $t['promo_policy_score'] ?? 'de score' ?>)</span>
                </li>

                <li>
                    <span class="policy-text"><strong><?= $t['promo_policy_hh_label'] ?? 'happy hour' ?> (<?= htmlspecialchars($policy['dynamicBoosts']['happyHour']['startHour'] ?? '12') ?>h - <?= htmlspecialchars($policy['dynamicBoosts']['happyHour']['endHour'] ?? '14') ?>h) :</strong> <?= $t['promo_policy_hh_desc'] ?? 'jouez sur le temps de midi pour booster tous vos points de jeu !' ?></span>
                    <span class="promo-badge badge-yellow"><?= $t['promo_policy_boost'] ?? 'boost' ?> x<?= htmlspecialchars($policy['dynamicBoosts']['happyHour']['multiplier'] ?? '2.0') ?></span>
                </li>
                <li>
                    <span class="policy-text"><strong><?= $t['promo_policy_we_label'] ?? 'bonus du week-end :' ?></strong> <?= $t['promo_policy_we_desc'] ?? 'détendez-vous en fin de semaine et gagnez encore plus de récompenses.' ?></span>
                    <span class="promo-badge badge-yellow"><?= $t['promo_policy_boost'] ?? 'boost' ?> x<?= htmlspecialchars($policy['dynamicBoosts']['weekend']['multiplier'] ?? '1.5') ?></span>
                </li>
            </ul>
            <p class="policy-disclaimer">
                <?= $t['promo_disclaimer_1'] ?? '* les points de participation expirent après' ?> <?= htmlspecialchars($policy['participation']['expirationDays'] ?? '7') ?> <?= $t['promo_disclaimer_2'] ?? 'jours. les bonus majeurs sont valables jusqu\'à' ?> <?= htmlspecialchars($policy['reproduction']['perfectBonus']['expirationDays'] ?? '90') ?> <?= $t['promo_disclaimer_3'] ?? 'jours. n\'oubliez pas de les convertir en bons d\'achat lors de votre prochaine commande !' ?>
            </p>
        </div>
    </div>
</div>