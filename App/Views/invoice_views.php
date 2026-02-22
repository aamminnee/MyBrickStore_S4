<?php
/**
 * Invoice View - MyBrickStore Brand Colors (Rouge, Jaune, Bleu, Blanc)
 *
 * @var array $order        Order details (invoice number, date, client info)
 * @var array $items        List of purchased items
 * @var float $itemsHT      Total items price excluding tax
 * @var float $totalTVA     Total VAT amount
 * @var float $totalTTC     Grand total price (Tax Included)
 * @var array $t            Associative array of translations
 */
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="invoice-page">

    <div class="invoice-controls">
        <a href="<?= ($_ENV['BASE_URL'] ?? '') ?>/commande" class="btn-back">
            ← <?= $t['invoice_btn_back'] ?? 'Retour aux commandes' ?>
        </a>
        <button onclick="downloadPDF()" class="btn-download">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
            <?= $t['invoice_btn_download'] ?? 'Télécharger la facture (PDF)' ?>
        </button>
    </div>

    <div id="invoice-content">
        <div class="invoice-stripe"></div>
        <div class="watermark">FACTURE</div>

        <div class="invoice-body">

            <div class="paper-header">
                <div class="logo-block">
                    <img class="logo-img" src="<?= $_ENV['BASE_URL'] ?>/img/logo.png" alt="MyBrickStore">
                    <span class="brand-name">MyBrickStore</span>
                    <div class="brand-address">
                        123 Rue des Briques<br>
                        75000 Paris, France<br>
                        SIRET : 123 456 789 00000
                    </div>
                </div>

                <div class="invoice-meta-block">
                    <span class="invoice-label"><?= $t['invoice_title'] ?? 'Facture' ?></span>
                    <div>
                        <span class="invoice-status-badge"><span>✓</span> Payée</span>
                    </div>
                    <div class="meta-grid">
                        <span class="m-label"><?= $t['invoice_number'] ?? 'N° Facture' ?></span>
                        <span class="m-value"><?= htmlspecialchars($order['invoice_number'] ?? $order['id_Order']) ?></span>

                        <span class="m-label"><?= $t['invoice_date'] ?? 'Date' ?></span>
                        <span class="m-value"><?= date('d/m/Y', strtotime($order['issue_date'] ?? $order['order_date'] ?? 'now')) ?></span>

                        <span class="m-label"><?= $t['invoice_ref'] ?? 'Réf. Commande' ?></span>
                        <span class="m-value">#<?= $order['id_Order'] ?></span>
                    </div>
                </div>
            </div>

            <hr class="hairline">

            <div class="addresses-row">
                <div class="address-block sender">
                    <div class="address-label"><span class="al-dot"></span>Expéditeur</div>
                    <div class="address-name">MyBrickStore SAS</div>
                    <div class="address-detail">
                        123 Rue des Briques<br>
                        75000 Paris, France<br>
                        contact@mybrickstore.fr
                    </div>
                </div>
                <div class="address-block client">
                    <div class="address-label"><span class="al-dot"></span><?= $t['invoice_billed_to'] ?? 'Facturé à' ?></div>
                    <div class="address-name"><?= htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')) ?></div>
                    <div class="address-detail">
                        <?= nl2br(htmlspecialchars($order['adress'] ?? ($t['invoice_addr_missing'] ?? 'Adresse non renseignée'))) ?><br>
                        <?= htmlspecialchars($order['email'] ?? '') ?>
                    </div>
                </div>
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:46%"><?= $t['invoice_col_desc'] ?? 'Description' ?></th>
                        <th><?= $t['invoice_col_pieces'] ?? 'Pièces' ?></th>
                        <th><?= $t['invoice_col_qty'] ?? 'Qté' ?></th>
                        <th><?= $t['invoice_col_unit_price'] ?? 'Prix unitaire' ?></th>
                        <th><?= $t['invoice_col_total'] ?? 'Total HT' ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($items) && !empty($items)): ?>
                        <?php foreach ($items as $item):
                            $isObj = is_object($item);
                            $price    = $isObj ? ($item->price ?? 0)   : ($item['price']   ?? 0);
                            $pieces   = $isObj ? ($item->pieces ?? 0)  : ($item['pieces']  ?? 0);
                            $idMosaic = $isObj ? $item->id_Mosaic : $item['id_Mosaic'];
                        ?>
                        <tr class="item">
                            <td>
                                <div class="item-title"><?= $t['invoice_item_mosaic'] ?? 'Mosaïque Personnalisée' ?></div>
                                <div class="item-sub">
                                    <span class="item-ref-tag">MS-<?= $idMosaic ?></span>
                                    <span><?= sprintf($t['invoice_item_handling'] ?? 'dont %s € de frais de préparation', number_format($handlingUnit ?? 4.99, 2)) ?></span>
                                </div>
                            </td>
                            <td><?= $pieces ?></td>
                            <td>1</td>
                            <td><?= number_format($price, 2) ?> €</td>
                            <td><?= number_format($price, 2) ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <tr class="delivery-row">
                        <td colspan="3"><?= $t['invoice_item_delivery'] ?? 'Livraison Standard — Colissimo' ?></td>
                        <td><?= number_format($deliveryTTC ?? 4.99, 2) ?> €</td>
                        <td><?= number_format($deliveryTTC ?? 4.99, 2) ?> €</td>
                    </tr>
                </tbody>
            </table>

            <div class="invoice-footer-section">
                <div class="invoice-note">
                    <?php if (isset($totalHandling) && $totalHandling > 0): ?>
                    <div class="note-card">
                        <strong><?= $t['invoice_note_title'] ?? 'Note informative' ?></strong>
                        <?= sprintf($t['invoice_note_text'] ?? 'Le montant des articles inclut %s € de frais de préparation.', number_format($totalHandling, 2)) ?>
                        <br><em><?= $t['invoice_note_vat'] ?? 'TVA applicable sur l\'ensemble : 20,00 %' ?></em>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="totals-block">
                    <div class="totals-line">
                        <span class="tl-label"><?= $t['invoice_total_items_ht'] ?? 'Articles HT' ?></span>
                        <span class="tl-val"><?= number_format($itemsHT ?? 0, 2) ?> €</span>
                    </div>
                    <div class="totals-line">
                        <span class="tl-label"><?= $t['invoice_total_shipping_ht'] ?? 'Frais de port HT' ?></span>
                        <span class="tl-val"><?= number_format($deliveryHT ?? 0, 2) ?> €</span>
                    </div>
                    <hr class="totals-sep">
                    <div class="totals-line">
                        <span class="tl-label"><?= $t['invoice_total_ht_net'] ?? 'Total HT' ?></span>
                        <span class="tl-val"><?= number_format($totalHT ?? 0, 2) ?> €</span>
                    </div>
                    <div class="totals-line">
                        <span class="tl-label"><?= $t['invoice_total_vat'] ?? 'TVA 20 %' ?></span>
                        <span class="tl-val"><?= number_format($totalTVA ?? 0, 2) ?> €</span>
                    </div>
                    <div class="totals-grand">
                        <span class="tg-label"><?= $t['invoice_total_ttc'] ?? 'Net à payer' ?></span>
                        <span class="tg-amount"><?= number_format($totalTTC ?? 0, 2) ?> €</span>
                    </div>
                </div>
            </div>

            <div class="paper-footer">
                <span class="footer-thanks"><?= $t['invoice_footer_thanks'] ?? 'Merci pour votre confiance !' ?></span>
                <div class="footer-legal">
                    <?= $t['invoice_footer_capital'] ?? 'MyBrickStore SAS — Capital de 10 000 €' ?><br>
                    RCS Paris 123 456 789 — N° TVA : FR 00 123 456 789
                </div>
            </div>

        </div>
    </div>

</div>

<script>
    function downloadPDF() {
        const element = document.getElementById('invoice-content');
        const opt = {
            margin:       0,
            filename:     'Facture_<?= htmlspecialchars($order['invoice_number'] ?? 'commande', ENT_QUOTES) ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, scrollY: 0},
            jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak:    { mode: 'avoid' }
        };
        html2pdf().set(opt).from(element).save();
    }
</script>