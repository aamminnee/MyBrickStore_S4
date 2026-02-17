<?php
/**
 * Invoice View
 *
 * Displays a printable invoice for a specific order.
 * Features:
 * - Detailed order breakdown (items, price, VAT, shipping).
 * - "Download PDF" functionality using html2pdf.js.
 *
 * @var array $order        Order details (invoice number, date, client info)
 * @var array $items        List of purchased items
 * @var float $itemsHT      Total items price excluding tax
 * @var float $totalTVA     Total VAT amount
 * @var float $totalTTC     Grand total price (Tax Included)
 * @var array $t            Associative array of translations
 */
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="invoice-controls">
    <a href="<?= ($_ENV['BASE_URL'] ?? '') ?>/commande" class="btn-back">
        &larr; <?= $t['invoice_btn_back'] ?? 'Retour aux commandes' ?>
    </a>
    <button onclick="downloadPDF()" class="btn-download">
        <?= $t['invoice_btn_download'] ?? 'Télécharger la facture (PDF)' ?>
    </button>
</div>

<div id="invoice-content" class="invoice-paper">
    
    <div class="paper-header">
        <div class="company-section">
            <img src="<?= $_ENV['BASE_URL'] ?>/img/logo.png" alt="MyBrickStore" style="height: 60px;">
            <div class="company-address">
                <strong>MyBrickStore</strong><br>
                123 Rue des Briques<br>
                75000 Paris, France<br>
                SIRET: 123 456 789 00000
            </div>
        </div>
        
        <div class="invoice-meta">
            <h2 class="invoice-title"><?= $t['invoice_title'] ?? 'FACTURE' ?></h2>
            <table class="meta-table">
                <tr>
                    <th><?= $t['invoice_number'] ?? 'N° Facture :' ?></th>
                    <td><?= htmlspecialchars($order['invoice_number'] ?? $order['id_Order']) ?></td>
                </tr>
                <tr>
                    <th><?= $t['invoice_date'] ?? 'Date :' ?></th>
                    <td><?= date('d/m/Y', strtotime($order['issue_date'] ?? $order['order_date'] ?? 'now')) ?></td>
                </tr>
                <tr>
                    <th><?= $t['invoice_ref'] ?? 'Réf. Commande :' ?></th>
                    <td>#<?= $order['id_Order'] ?></td>
                </tr>
            </table>
        </div>
    </div>

    <hr class="separator">

    <div class="client-section">
        <div class="client-box">
            <h3 class="section-title"><?= $t['invoice_billed_to'] ?? 'Facturé à :' ?></h3>
            <div class="client-details">
                <strong><?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?></strong><br>
                <?= nl2br(htmlspecialchars($order['adress'] ?? ($t['invoice_addr_missing'] ?? 'Adresse non renseignée'))) ?><br>
                Email : <?= htmlspecialchars($order['email'] ?? '') ?><br>
            </div>
        </div>
    </div>

    <div class="items-section">
        <table class="items-table">
            <thead>
                <tr>
                    <th class="col-desc"><?= $t['invoice_col_desc'] ?? 'Description' ?></th>
                    <th class="col-qty"><?= $t['invoice_col_pieces'] ?? 'Pièces' ?></th>
                    <th class="col-qty"><?= $t['invoice_col_qty'] ?? 'Quantité' ?></th>
                    <th class="col-price"><?= $t['invoice_col_unit_price'] ?? 'Prix Unitaire' ?></th>
                    <th class="col-total"><?= $t['invoice_col_total'] ?? 'Total' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (isset($items) && !empty($items)): ?>
                    <?php foreach ($items as $item): 
                        $isObj = is_object($item);
                        $price = $isObj ? ($item->price ?? 0) : ($item['price'] ?? 0);
                        $pieces = $isObj ? ($item->pieces ?? 0) : ($item['pieces'] ?? 0);
                        $idMosaic = $isObj ? $item->id_Mosaic : $item['id_Mosaic'];
                    ?>
                    <tr class="item">
                        <td>
                            <strong><?= $t['invoice_item_mosaic'] ?? 'Mosaïque Personnalisée' ?></strong>
                            <br>
                            <span class="item-ref">
                                <?= $t['invoice_item_ref'] ?? 'Réf:' ?> MS-<?= $idMosaic ?> 
                                <?= sprintf($t['invoice_item_handling'] ?? '(Dont %s € de frais de préparation inclus)', number_format($handlingUnit ?? 4.99, 2)) ?>
                            </span>
                        </td>
                        <td class="text-center"><?= $pieces ?></td>
                        <td class="text-right">1</td>
                        <td class="text-right"><?= number_format($price, 2) ?> €</td>
                        <td class="text-right"><?= number_format($price, 2) ?> €</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>

                <tr class="item delivery-row">
                    <td colspan="3" class="text-right">
                        <?= $t['invoice_item_delivery'] ?? 'Livraison Standard' ?>
                    </td>
                    <td class="text-right"><?= number_format($deliveryTTC ?? 4.99, 2) ?> €</td>
                    <td class="text-right"><?= number_format($deliveryTTC ?? 4.99, 2) ?> €</td>
                </tr>

            </tbody>
        </table>
    </div>

    <div class="totals-section">
        
        <div class="invoice-notes">
            <?php if(isset($totalHandling) && $totalHandling > 0): ?>
                <div class="note-box">
                    <strong><?= $t['invoice_note_title'] ?? 'Note informative :' ?></strong><br>
                    <?= sprintf($t['invoice_note_text'] ?? 'Le montant des articles inclut %s € de frais de préparation.', number_format($totalHandling, 2)) ?><br>
                    <em><?= $t['invoice_note_vat'] ?? 'TVA applicable sur l\'ensemble : 20.00%' ?></em>
                </div>
            <?php endif; ?>
        </div>

        <div class="financial-totals">
            <table class="totals-table">
                <tr>
                    <th><?= $t['invoice_total_items_ht'] ?? 'Total Articles HT' ?></th>
                    <td><?= number_format($itemsHT ?? 0, 2) ?> €</td>
                </tr>
                
                <tr>
                    <th><?= $t['invoice_total_shipping_ht'] ?? 'Frais de port HT' ?></th>
                    <td><?= number_format($deliveryHT ?? 0, 2) ?> €</td>
                </tr>
                
                <tr>
                    <td colspan="2"><hr></td>
                </tr>

                <tr>
                    <th><?= $t['invoice_total_ht_net'] ?? 'Total HT Net' ?></th>
                    <td><?= number_format($totalHT ?? 0, 2) ?> €</td>
                </tr>

                <tr>
                    <th><?= $t['invoice_total_vat'] ?? 'TVA (20%)' ?></th>
                    <td><?= number_format($totalTVA ?? 0, 2) ?> €</td>
                </tr>

                <tr class="grand-total">
                    <th><?= $t['invoice_total_ttc'] ?? 'Net à payer (TTC)' ?></th>
                    <td><?= number_format($totalTTC ?? 0, 2) ?>€</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="paper-footer">
        <p><?= $t['invoice_footer_thanks'] ?? 'Merci pour votre confiance !' ?></p>
        <p class="small"><?= $t['invoice_footer_capital'] ?? 'MyBrixStore - Capital de 10 000 €' ?></p>
    </div>
</div>

<script>    
    /**
     * Triggers the PDF download of the invoice using html2pdf library.
     */
    function downloadPDF() {
        const element = document.getElementById('invoice-content');
        const opt = {
            margin:       10,
            filename:     'Facture_<?= $order['invoice_number'] ?? 'Lego' ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true },
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>