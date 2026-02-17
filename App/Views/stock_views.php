<?php
/**
 * Inventory Management View (Admin)
 *
 * Dashboard for managing stock levels.
 * Features:
 * - Quick stock update form (Search + Add/Remove quantity).
 * - Filterable inventory list (by shape, color, status).
 * - Pagination logic.
 *
 * @var array $stocks       List of stock items
 * @var array $allItems     List of all available items for search
 * @var array $shapesList   Available shapes for filter
 * @var array $colorsList   Available colors for filter
 * @var int $totalPages     Total pages for pagination
 * @var string|null $error  Error message
 * @var string|null $success Success message
 * @var array $t            Translations
 */
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><?= $t['stock_title'] ?? 'Gestion de l\'Inventaire' ?></h1>
    </div>

    <div class="container" style="margin-top: 20px;">
        <?php if (!empty($error)): ?>
            <div style="background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; border: 1px solid #ef9a9a; margin-bottom: 15px;">
                <strong>Erreur :</strong> <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 5px; border: 1px solid #a5d6a7; margin-bottom: 15px;">
                <strong>Succès :</strong> <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($debugMessage)): ?>
            <div style="background-color: #e3f2fd; color: #0d47a1; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 0.9em; margin-bottom: 15px;">
                <strong>Debug :</strong> <?= htmlspecialchars($debugMessage) ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="admin-content">
        
        <div class="card-admin">
            <h2><?= $t['stock_movement_title'] ?? 'Mouvement de Stock' ?></h2>
            <form action="<?= ($_ENV['BASE_URL'] ?? '') ?>/stock/add" method="POST" class="form-admin">
                <div class="form-group">
                    <label for="item_search"><?= $t['stock_label_search'] ?? 'Rechercher une pièce :' ?></label>
                    <input type="text" list="items_list" id="item_search" class="form-control" 
                           placeholder="<?= $t['stock_placeholder_search'] ?? "Tapez '2-4' ou 'Bleu'..." ?>" 
                           required autocomplete="off">
                    
                    <input type="hidden" name="item_id" id="real_item_id"> 
                    
                    <datalist id="items_list">
                        <?php if(!empty($allItems)): ?>
                            <?php foreach ($allItems as $item): ?>
                                <option value="<?= htmlspecialchars($item->id_Item) ?> - <?= htmlspecialchars($item->label) ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </datalist>
                </div>

                <div class="form-group">
                    <label for="quantity"><?= $t['stock_label_qty'] ?? 'Quantité (+/-) :' ?></label>
                    <input type="number" name="quantity" id="quantity" required class="form-control" 
                           placeholder="<?= $t['stock_placeholder_qty'] ?? 'Ex: 50 ou -10' ?>">
                </div>

                <button type="submit" class="btn-primary"><?= $t['stock_btn_update'] ?? 'Mettre à jour' ?></button>
            </form>
        </div>

        <hr class="separator">

        <div class="card-admin">
            <div class="header-actions">
                <h2><?= $t['stock_status_title'] ?? 'État du Stock' ?></h2>
                
                <?php 
                    $currentStatus = $_GET['filter_status'] ?? 'all';
                    function getStatusLink($status) {
                        $params = $_GET;
                        $params['filter_status'] = $status;
                        $params['page'] = 1; 
                        return '?' . http_build_query($params);
                    }
                ?>

                <div class="stock-tabs">
                    <a href="<?= getStatusLink('all') ?>" 
                       class="btn-tab tab-all <?= $currentStatus == 'all' ? 'active' : '' ?>">
                        Tout le stock
                    </a>
                    <a href="<?= getStatusLink('low') ?>" 
                       class="btn-tab tab-low <?= $currentStatus == 'low' ? 'active' : '' ?>">
                        Stock Faible
                    </a>
                    <a href="<?= getStatusLink('critical') ?>" 
                       class="btn-tab tab-critical <?= $currentStatus == 'critical' ? 'active' : '' ?>">
                        Ruptures
                    </a>
                </div>

                <form method="GET" action="<?= ($_ENV['BASE_URL'] ?? '') ?>/stock" class="filters-bar">
                    
                    <input type="hidden" name="filter_status" value="<?= htmlspecialchars($currentStatus) ?>">

                    <select name="filter_shape" class="select-filter">
                        <option value=""><?= $t['stock_filter_shapes'] ?? 'Toutes les formes' ?></option>
                        <?php if(!empty($shapesList)): ?>
                            <?php foreach ($shapesList as $s): ?>
                                <option value="<?= htmlspecialchars($s->name) ?>" 
                                    <?= (isset($_GET['filter_shape']) && $_GET['filter_shape'] == $s->name) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s->name) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <select name="filter_color" class="select-filter">
                        <option value=""><?= $t['stock_filter_colors'] ?? 'Toutes les couleurs' ?></option>
                        <?php if(!empty($colorsList)): ?>
                            <?php foreach ($colorsList as $c): ?>
                                <option value="<?= htmlspecialchars($c->name) ?>" 
                                    <?= (isset($_GET['filter_color']) && $_GET['filter_color'] == $c->name) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c->name) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <button type="submit" class="btn-secondary"><?= $t['stock_btn_filter'] ?? 'Filtrer' ?></button>
                    <a href="<?= ($_ENV['BASE_URL'] ?? '') ?>/stock" class="btn-secondary" title="Réinitialiser">✖</a>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table-stock">
                    <thead>
                        <tr>
                            <th><?= $t['stock_col_id'] ?? 'ID' ?></th>
                            <th><?= $t['stock_col_preview'] ?? 'Aperçu' ?></th>
                            <th><?= $t['stock_col_shape'] ?? 'Forme' ?></th>
                            <th><?= $t['stock_col_color'] ?? 'Couleur' ?></th>
                            <th><?= $t['stock_col_price'] ?? 'Prix Unit.' ?></th>
                            <th><?= $t['stock_col_stock'] ?? 'Stock Réel' ?></th>
                            <th><?= $t['stock_col_action'] ?? 'Action' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($stocks)): ?>
                            <?php foreach ($stocks as $row): ?>
                                <?php 
                                    $qty = $row->current_stock;
                                    $badgeClass = 'badge-success';
                                    if ($qty < 0) $badgeClass = 'badge-critical';
                                    elseif ($qty < 50) $badgeClass = 'badge-warning';
                                ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row->id_Item) ?></td>
                                    <td>
                                        <div class="color-preview" style="background-color: #<?= htmlspecialchars($row->hex_color) ?>;"></div>
                                    </td>
                                    <td><?= htmlspecialchars($row->shape_name) ?></td>
                                    <td><?= htmlspecialchars($row->color_name) ?></td>
                                    <td><?= htmlspecialchars($row->price) ?> €</td>
                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= $qty ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="#" class="btn-icon" 
                                        onclick="
                                            document.getElementById('item_search').value = '<?= $row->id_Item ?> - <?= htmlspecialchars($row->shape_name . ' ' . $row->color_name) ?>'; 
                                            document.getElementById('real_item_id').value = '<?= $row->id_Item ?>'; 
                                            window.scrollTo({ top: 0, behavior: 'smooth' }); 
                                            return false;
                                        "
                                        title="<?= $t['stock_tooltip_edit'] ?? 'Modifier le stock' ?>">
                                        ➕/➖
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align:center; padding: 30px; color: #94a3b8;"><?= $t['stock_empty_msg'] ?? 'Aucune pièce trouvée.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-container">
                <?php 
                $currentPage = (int)($_GET['page'] ?? 1);
                $totalPages = (int)($totalPages ?? 1); 

                if (!function_exists('getPageLink')) {
                    function getPageLink($page) {
                        $params = $_GET; 
                        $params['page'] = $page; 
                        return '?' . http_build_query($params); 
                    }
                }
                $range = 2; 
                ?>
                
                <?php if ($currentPage > 1): ?>
                    <a href="<?= getPageLink($currentPage - 1) ?>" class="page-link">&laquo;</a>
                <?php else: ?>
                    <span class="page-link disabled">&laquo;</span>
                <?php endif; ?>

                <?php if ($totalPages > 1): ?>
                    <a href="<?= getPageLink(1) ?>" class="page-link <?= ($currentPage == 1) ? 'active' : '' ?>">1</a>

                    <?php
                    $start = max(2, $currentPage - $range);
                    $end = min($totalPages - 1, $currentPage + $range);

                    if ($start > 2) echo '<span class="page-dots">...</span>';

                    for ($i = $start; $i <= $end; $i++) {
                        $isActive = ($i == $currentPage) ? 'active' : '';
                        echo '<a href="' . getPageLink($i) . '" class="page-link ' . $isActive . '">' . $i . '</a>';
                    }

                    if ($end < $totalPages - 1) echo '<span class="page-dots">...</span>';

                    if ($totalPages > 1) {
                        echo '<a href="' . getPageLink($totalPages) . '" class="page-link ' . (($currentPage == $totalPages) ? 'active' : '') . '">' . $totalPages . '</a>';
                    }
                    ?>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= getPageLink($currentPage + 1) ?>" class="page-link">&raquo;</a>
                <?php else: ?>
                    <span class="page-link disabled">&raquo;</span>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('item_search').addEventListener('input', function() {
        var val = this.value;
        var opts = document.getElementById('items_list').childNodes;
        var found = false;
        
        for (var i = 0; i < opts.length; i++) {
            if (opts[i].value === val) {
                var id = val.split(' - ')[0];
                document.getElementById('real_item_id').value = id;
                found = true;
                break;
            }
        }
        if (!found && val === '') {
            document.getElementById('real_item_id').value = '';
        }
    });

    document.querySelector('.form-admin').addEventListener('submit', function(e) {
        var id = document.getElementById('real_item_id').value;
        if (!id) {
            e.preventDefault();
            alert("<?= $t['stock_js_alert'] ?? 'Veuillez sélectionner une pièce valide dans la liste déroulante.' ?>");
        }
    });
</script>