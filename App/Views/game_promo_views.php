<?php 
// link the external css file (adjust the path based on your actual public folder structure)
?>
<link rel="stylesheet" href="/css/game_promo.css">

<div class="promo-container">
    <?php // hero section with the link to the game ?>
    <div class="promo-hero">
        <h1>découvrez mybrickgame</h1>
        <p style="font-size: 1.5rem;">jouez, construisez, et gagnez des points de fidélité !</p>
        <a href="<?= htmlspecialchars($gameUrl ?? '#') ?>" class="btn-play" target="_blank">jouer maintenant ! 🚀</a>
    </div>

    <?php // introduction of the different game modes ?>
    <h2 class="promo-section-title">nos modes de jeu</h2>
    <div class="promo-grid">
        <?php // reproduction mode ?>
        <div class="promo-card">
            <div class="promo-card-image">
                <?php // replace the src with your image path ?>
                <?php // <img src="/images/promo_reproduction.jpg" alt="jeu de reproduction"> ?>
                
            </div>
            <h3 style="color: #006CB7; margin-bottom: 1rem; font-weight: bold;">reproduction</h3>
            <p>reproduisez des mosaïques de briques le plus fidèlement possible. choisissez votre difficulté (facile, moyen, difficile) et prouvez vos talents de constructeur !</p>
        </div>

        <?php // tetris mode ?>
        <div class="promo-card">
            <div class="promo-card-image">
                <?php // replace the src with your image path ?>
                <?php // <img src="/images/promo_tetris.jpg" alt="jeu tetris"> ?>
                
            </div>
            <h3 style="color: #006CB7; margin-bottom: 1rem; font-weight: bold;">casse-briques</h3>
            <p>placez vos briques stratégiquement pour compléter des lignes et marquer un maximum de points. attention, le plateau se remplit très vite !</p>
        </div>

        <?php // multiplayer mode ?>
        <div class="promo-card">
            <div class="promo-card-image">
                <?php // replace the src with your image path ?>
                <?php // <img src="/images/promo_multiplayer.jpg" alt="mode multijoueur"> ?>
                
            </div>
            <h3 style="color: #006CB7; margin-bottom: 1rem; font-weight: bold;">mode multijoueur</h3>
            <p>défiez vos amis ! créez un salon d'attente, partagez votre code et affrontez-vous en temps réel pour déterminer qui est le meilleur architecte.</p>
        </div>
    </div>

    <?php // explanation of the loyalty points policy loaded dynamically ?>
    <h2 class="promo-section-title" style="color: #237841;">gagnez des bons d'achat</h2>
    <div class="policy-section">
        <p style="font-size: 1.2rem; margin-bottom: 2rem; line-height: 1.6;">
            chaque partie jouée vous rapporte des points de fidélité, directement convertibles en bons d'achat sur notre boutique. voici comment maximiser vos gains :
        </p>

        <ul class="policy-list">
            <?php // base points for participation ?>
            <li>
                <span><strong>participation :</strong> chaque partie vous rapporte des points garantis, même en cas de défaite !</span>
                <span class="promo-badge">+<?= htmlspecialchars($policy['participation']['points'] ?? '50') ?> pts</span>
            </li>

            <?php // reproduction multipliers and bonuses ?>
            <li>
                <span><strong>jeu de reproduction :</strong> gagnez des points bonus en jouant dans les modes de difficulté supérieurs.</span>
                <span class="promo-badge">multiplicateur x<?= htmlspecialchars($policy['reproduction']['multipliers']['hard'] ?? '4.0') ?> (difficile)</span>
            </li>
            <li>
                <span><strong>reproduction parfaite :</strong> reproduisez l'image à 100% sans la moindre erreur.</span>
                <span class="promo-badge">+<?= htmlspecialchars($policy['reproduction']['perfectBonus']['points'] ?? '100') ?> pts</span>
            </li>

            <?php // tetris thresholds ?>
            <li>
                <span><strong>casse-briques :</strong> accomplissez des exploits en dépassant des paliers de score.</span>
                <span class="promo-badge">+<?= htmlspecialchars($policy['tetris']['achievements'][0]['bonus'] ?? '500') ?> pts (> <?= htmlspecialchars($policy['tetris']['achievements'][0]['threshold'] ?? '20000') ?> de score)</span>
            </li>

            <?php // dynamic time boosts ?>
            <li>
                <span><strong>happy hour (<?= htmlspecialchars($policy['dynamicBoosts']['happyHour']['startHour'] ?? '12') ?>h - <?= htmlspecialchars($policy['dynamicBoosts']['happyHour']['endHour'] ?? '14') ?>h) :</strong> jouez sur le temps de midi pour booster tous vos points de jeu !</span>
                <span class="promo-badge" style="background-color: #FFCF00; color: #333;">boost x<?= htmlspecialchars($policy['dynamicBoosts']['happyHour']['multiplier'] ?? '2.0') ?></span>
            </li>
            <li>
                <span><strong>bonus du week-end :</strong> détendez-vous en fin de semaine et gagnez encore plus de récompenses.</span>
                <span class="promo-badge" style="background-color: #FFCF00; color: #333;">boost x<?= htmlspecialchars($policy['dynamicBoosts']['weekend']['multiplier'] ?? '1.5') ?></span>
            </li>
        </ul>
        
        <p style="margin-top: 2rem; color: #6c757d; font-style: italic; font-size: 0.9rem;">
            * les points de participation expirent après <?= htmlspecialchars($policy['participation']['expirationDays'] ?? '7') ?> jours. les bonus majeurs sont valables jusqu'à <?= htmlspecialchars($policy['reproduction']['perfectBonus']['expirationDays'] ?? '90') ?> jours. n'oubliez pas de les convertir en bons d'achat lors de votre prochaine commande !
        </p>
    </div>
</div>