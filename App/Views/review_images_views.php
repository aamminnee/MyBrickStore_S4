<?php
/**
 * Style Selection View (Review)
 *
 * Displays the generated preview of the uploaded image in 4 different algorithmic styles.
 * Allows the user to compare price, piece count, and visual result before adding to cart.
 *
 * @var array $image        Original uploaded image data
 * @var array $previews     Associative array of base64 preview images (key = style)
 * @var array $prices       Associative array of calculated prices (key = style)
 * @var array $counts       Associative array of piece counts (key = style)
 * @var array $t            Associative array of translations
 */
?>

<div class="review-wrapper">
    <div class="review-container">

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="flash-success">
                <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']);?>
        <?php endif; ?>

        <?php if (isset($error_msg) && $error_msg): ?>
            <div class="alert-box error">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>
        
        <div class="review-top-section">
            
            <div class="header-left">
                <h2><?= $t['review_title'] ?? 'Choisissez votre finition' ?></h2>
                <p class="subtitle"><?= $t['review_subtitle'] ?? 'Sélectionnez le style qui correspond le mieux à votre projet.' ?></p>
                
                <div class="lego-scatter">
                    <div class="brick b-red b-2x4"></div>
                    <div class="brick b-blue b-2x2"></div>
                    <div class="brick b-yellow b-2x4"></div>
                    <div class="brick b-green b-2x2"></div>
                    <div class="brick b-red b-2x2"></div>
                </div>
            </div>

            <?php if (isset($image) && !empty($image)): ?>
                <div class="user-image-preview post-it-style">
                    <div class="pin-icon">📍</div>
                    <h3><?= $t['review_original_label'] ?? 'Original' ?></h3>
                    <div class="img-frame">
                        <img src="data:<?= $image['file_type'] ?>;base64,<?= base64_encode($image['file']) ?>" 
                             alt="Original" 
                             class="original-img">
                    </div>
                </div>
            <?php endif; ?>
            
        </div>

        <?php if (isset($image) && !empty($image)): ?>
            <div class="mosaic-options">
                
                <?php 
                $styles = [
                    'rentabilite'   => ['label' => 'Économique', 'desc' => 'Ce mode est optimisé pour votre portefeuille. L\'algorithme choisit intelligemment les briques les moins chères pour réduire le coût total du projet. En contrepartie, la mosaïque sera légèrement dégradée.', 'color' => 'var(--lego-green)'],
                    'libre' => ['label' => 'Renforcé', 'desc' => 'Idéal pour les grandes surfaces. Cet algorithme force l\'utilisation des plus grandes briques possibles pour remplir les zones de couleur.', 'color' => 'var(--lego-red)'],
                    'stock'   => ['label' => 'Express', 'desc' => 'Pas d\'attente ! Ce rendu est généré exclusivement à partir des pièces physiquement présentes dans notre entrepôt. Si vous commandez ce modèle, l\'expédition est immédiate car nous n\'avons pas besoin de commander les pièces.', 'color' => 'var(--lego-yellow)'],
                    'minimisation' => ['label' => 'Classique', 'desc' => 'Le parfait équilibre entre qualité et facilité. Cet algorithme privilégie la fidélité des détails de votre photo tout en utilisant des pièces standards. C\'est le meilleur choix pour un rendu visuel optimal et un montage agréable.', 'color' => 'var(--lego-blue)']
                ];
                ?>

                <?php foreach ($styles as $key => $info): ?>
                    <div class="option-card style-<?= $key ?>">
                        <div class="card-top-bar"></div>

                        <div class="card-header-row">
                            <h3><?= $t['style_' . $key . '_label'] ?? $info['label'] ?></h3>
                        </div>

                        <div class="preview-box">
                            <?php 
                            if (isset($previews[$key])) {
                                $imgSrc = $previews[$key];
                            } else {
                                $imgSrc = "data:" . $image['file_type'] . ";base64," . base64_encode($image['file']);
                            }
                            ?>
                            <img src="<?= $imgSrc ?>" alt="<?= $info['label'] ?>">
                        </div>

                        <div class="card-stats">
                            <div class="stat-item">
                                <span class="stat-label"><?= $t['review_stat_price'] ?? 'Prix estimé' ?></span>
                                <span class="stat-value price">
                                    <?php 
                                        if (isset($prices[$key]) && $prices[$key] > 0) {
                                            echo number_format($prices[$key], 2, ',', ' ') . ' €';
                                        } else {
                                            echo '--,-- €';
                                        }
                                    ?>
                                </span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <span class="stat-label"><?= $t['review_stat_pieces'] ?? 'Pièces' ?></span>
                                <span class="stat-value pieces">
                                    <?= (isset($counts[$key]) && $counts[$key] > 0) ? $counts[$key] : '----' ?> p.
                                </span>
                            </div>
                        </div>

                        <p class="desc"><?= $t['style_' . $key . '_desc'] ?? $info['desc'] ?></p>
                        
                        <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/reviewImages/handleChoice" method="POST" class="card-action-form">
                            <input type="hidden" name="image_id" value="<?= $image['id_Image'] ?>">
                            <input type="hidden" name="choice" value="<?= $key ?>">
                            
                            <div class="btn-group" style="display:flex; gap:10px;">
                                <button type="submit" name="action" value="cart" class="btn-select" style="flex:1;">
                                    <?= $t['review_btn_add'] ?? 'Au panier' ?>
                                </button>
                                
                                <button type="submit" name="action" value="buy_now" class="btn-select" style="flex:1">
                                    <?= $t['review_btn_buy'] ?? 'Achat Immédiat' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endforeach; ?>

            </div>

        <?php else: ?>
            <div class="empty-state">
                <div class="alert-box warning">
                    <p><?= $t['review_error_no_image'] ?? "Erreur de chargement." ?></p>
                    <a href="/images" class="btn-retry"><?= $t['review_btn_retry'] ?? 'Réessayer' ?></a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>