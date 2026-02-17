<?php
/**
 * Image Cropping View
 *
 * Interface to resize and crop the uploaded image before processing.
 * Features:
 * - JavaScript-based image cropper (Cropper.js).
 * - Configuration sidebar for Size and Aspect Ratio.
 *
 * @var array|null $image   The uploaded image data (id, file, file_type, filename)
 * @var array $t            Associative array of translations
 * @var string $baseUrl     Base URL environment variable
 */

$baseUrl = $_ENV['BASE_URL'] ?? '';
?>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
</head>

<div class="crop-workspace">
    
    <div class="workspace-header">
        <h1><?= $t['crop_title'] ?? 'Recadrer & Configurer' ?></h1>
        <p><?= $t['crop_subtitle'] ?? 'Sélectionnez la zone à transformer en briques.' ?></p>
    </div>

    <?php if (isset($image) && !empty($image) && isset($image['file'])): ?>
        
        <div class="crop-interface">
            
            <div class="editor-zone">
                <div class="image-container">
                    <img id="image-to-crop" 
                         src="data:<?= $image['file_type'] ?>;base64,<?= base64_encode($image['file']) ?>" 
                         alt="<?= htmlspecialchars($image['filename']) ?>"
                         data-id="<?= $image['id_Image'] ?>"> 
                </div>
            </div>

            <aside id="options-panel" class="settings-sidebar">
                <div class="settings-card">
                    <div class="card-deco-bar"></div>

                    <h3><?= $t['crop_settings_title'] ?? 'Paramètres' ?></h3>
                    
                    <div class="option-group">
                        <label for="size">
                            <?= $t['crop_label_size'] ?? 'Taille du tableau' ?>
                        </label>
                        <div class="select-wrapper">
                            <select id="size">
                                <option value="32"><?= $t['crop_size_small'] ?? '32 x 32 (Petit)' ?></option>
                                <option value="48"><?= $t['crop_size_medium'] ?? '48 x 48 (Moyen)' ?></option>
                                <option value="64" selected><?= $t['crop_size_large'] ?? '64 x 64 (Grand)' ?></option>
                                <option value="96"><?= $t['crop_size_xl'] ?? '96 x 96 (Très Grand)' ?></option>
                                <option value="128"><?= $t['crop_size_giant'] ?? '128 x 128 (Géant)' ?></option>
                            </select>
                            <div class="select-arrow">▼</div>
                        </div>
                    </div>

                    <div class="option-group">
                        <label for="aspect">
                            <?= $t['crop_label_aspect'] ?? 'Format (Ratio)' ?>
                        </label>
                        <div class="select-wrapper">
                            <select id="aspect">
                                <option value="1" selected><?= $t['crop_aspect_square'] ?? 'Carré (1:1)' ?></option>
                                <option value="1.33333"><?= $t['crop_aspect_landscape'] ?? 'Paysage (4:3)' ?></option>
                                <option value="1.77777"><?= $t['crop_aspect_cinema'] ?? 'Cinéma (16:9)' ?></option>
                                <option value="0.75"><?= $t['crop_aspect_portrait'] ?? 'Portrait (3:4)' ?></option>
                            </select>
                            <div class="select-arrow">▼</div>
                        </div>
                    </div>

                    <div class="action-footer">
                        <button id="btn-crop" class="btn-validate">
                            <?= $t['crop_btn_generate'] ?? 'Générer la Mosaïque' ?>
                        </button>
                    </div>
                </div>
            </aside>

        </div>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
        <script src="<?= $baseUrl ?>/JS/crop_images.js"></script>

    <?php else: ?>
        <div class="empty-state">
            <div class="alert-box">
                <p><?= $t['crop_error_no_image'] ?? 'Oups, aucune image trouvée.' ?></p>
                <a href="<?= $baseUrl ?>/images" class="btn-validate"><?= $t['crop_btn_upload'] ?? 'Téléverser une image' ?></a>
            </div>
        </div>
    <?php endif; ?>

</div>