<?php
/**
 * releve_mensuel.php - Releve de compte mensuel, imprimable.
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= htmlspecialchars($data['title'] ?? "Releve Mensuel") ?></h2>

<!-- Formulaire de selection -->
<div class="form-card no-print">
    <form method="GET" action="<?= BASE_URL ?>?controller=Rapport&action=releveMensuel" class="filter-form">
        <input type="hidden" name="controller" value="Rapport">
        <input type="hidden" name="action" value="releveMensuel">
        <div class="filter-group">
            <label>Numero de compte</label>
            <input type="text" name="numero_compte" class="form-control" placeholder="Ex: 123456789012"
                   value="<?= htmlspecialchars($data['numero_compte'] ?? '') ?>">
        </div>
        <div class="filter-group">
            <label>Mois (YYYY-MM)</label>
            <input type="month" name="mois" class="form-control" value="<?= htmlspecialchars($data['mois'] ?? date('Y-m')) ?>">
        </div>
        <div class="filter-group" style="align-self:flex-end;">
            <button type="submit" class="btn-filter">Generer</button>
        </div>
    </form>
</div>

<?php if (!empty($data['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['error']) ?>", 'error'));</script>
<?php endif; ?>

<?php if (!empty($data['compte'])): ?>
<?php
$compte = $data['compte'];
$moisLabel = date('F Y', strtotime($data['debut_mois']));
?>

<!-- ============ RELEVE IMPRIMABLE ============ -->
<div class="releve-container" id="releve-print">

    <!-- En-tete banque -->
    <div class="releve-header">
        <div class="bank-info">
            <div class="bank-name">EBANKING</div>
            <div class="bank-sub">Systeme de Gestion Bancaire</div>
        </div>
        <div class="releve-title-block">
            <div class="releve-title">RELEVE DE COMPTE</div>
            <div class="releve-period">Periode : <?= htmlspecialchars(strtoupper($moisLabel)) ?></div>
        </div>
    </div>

    <hr class="releve-divider">

    <!-- Infos client & compte -->
    <div class="client-compte-grid">
        <div class="client-bloc">
            <div class="bloc-title">TITULAIRE</div>
            <div class="bloc-val bold"><?= htmlspecialchars($compte['nom'] . ' ' . $compte['prenom']) ?></div>
            <div><?= htmlspecialchars($compte['adresse'] ?? '') ?></div>
            <div><?= htmlspecialchars($compte['telephone'] ?? '') ?></div>
            <div><?= htmlspecialchars($compte['email'] ?? '') ?></div>
        </div>
        <div class="compte-bloc">
            <div class="bloc-title">COMPTE</div>
            <table class="info-mini">
                <tr><td>Numero</td><td class="bold mono"><?= htmlspecialchars($compte['numero_compte']) ?></td></tr>
                <tr><td>Type</td><td><?= htmlspecialchars($compte['type_compte'] ?? '') ?></td></tr>
                <tr><td>Ouverture</td><td><?= htmlspecialchars(date('d/m/Y', strtotime($compte['date_ouverture'] ?? 'now'))) ?></td></tr>
                <tr><td>Periode</td><td><?= htmlspecialchars($data['debut_mois']) ?> au <?= htmlspecialchars($data['fin_mois']) ?></td></tr>
            </table>
        </div>
    </div>

    <!-- Recapitulatif financier -->
    <div class="recap-grid">
        <div class="recap-item recap-initial">
            <div class="recap-label">Solde Initial (estime)</div>
            <div class="recap-val"><?= number_format($data['solde_initial'], 2, ',', ' ') ?> FCFA</div>
        </div>
        <div class="recap-item recap-credit">
            <div class="recap-label">Total Credits (+)</div>
            <div class="recap-val green"><?= number_format($data['total_credits'], 2, ',', ' ') ?> FCFA</div>
        </div>
        <div class="recap-item recap-debit">
            <div class="recap-label">Total Debits (-)</div>
            <div class="recap-val red"><?= number_format($data['total_debits'], 2, ',', ' ') ?> FCFA</div>
        </div>
        <div class="recap-item recap-final">
            <div class="recap-label">Solde Final</div>
            <div class="recap-val bold"><?= number_format($data['solde_final'], 2, ',', ' ') ?> FCFA</div>
        </div>
    </div>

    <div class="table-scroll-wrap">
    <!-- Tableau des mouvements -->
    <table class="mvt-table" style="min-width: 800px;">
        <thead>
            <tr>
                <th>Date</th>
                <th>Reference</th>
                <th>Libelle</th>
                <th>Debit</th>
                <th>Credit</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($data['transactions'])): ?>
            <tr><td colspan="5" class="empty-row">Aucun mouvement sur la periode.</td></tr>
        <?php else: ?>
            <?php foreach ($data['transactions'] as $t): ?>
            <tr>
                <td class="date-cell"><?= htmlspecialchars(date('d/m/Y', strtotime($t['date_transaction']))) ?></td>
                <td class="ref-cell"><?= htmlspecialchars($t['reference_externe']) ?></td>
                <td><?= htmlspecialchars($t['type_transaction']) ?></td>
                <td class="debit-cell">
                    <?= $t['sens'] === 'Débit' ? number_format((float)$t['montant'], 2, ',', ' ') : '' ?>
                </td>
                <td class="credit-cell">
                    <?= $t['sens'] === 'Crédit' ? number_format((float)$t['montant'], 2, ',', ' ') : '' ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3"><strong>TOTAUX</strong></td>
                <td class="debit-cell"><strong><?= number_format($data['total_debits'], 2, ',', ' ') ?></strong></td>
                <td class="credit-cell"><strong><?= number_format($data['total_credits'], 2, ',', ' ') ?></strong></td>
            </tr>
        </tfoot>
    </table>
    </div>

    <div class="releve-footer">
        Document genere le <?= date('d/m/Y a H:i:s') ?> &mdash; EBANKING Systeme Bancaire
    </div>
</div>

<!-- Bouton impression -->
<div class="print-actions no-print">
    <button onclick="window.print()" class="btn-print">Imprimer le Releve</button>
</div>

<?php endif; ?>

<style>
/* Impression gérée par responsive-core.css .no-print */
@media print { body { font-size: 11px; } }
.form-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.07);margin-bottom:20px}
.filter-form{display:flex;flex-wrap:wrap;gap:14px;align-items:flex-start}
.filter-group{display:flex;flex-direction:column;gap:4px;min-width:180px}
.filter-group label{font-weight:600;font-size:13px;color:#444}
.form-control{padding:8px 12px;border:1.5px solid #dde;border-radius:8px;font-size:13px}
.btn-filter{padding:9px 20px;background:#042e5a;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600}
.btn-print{padding:10px 28px;background:#007bff;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:15px;font-weight:600}
.print-actions{margin-top:16px;text-align:right}
/* Releve */
.releve-container{background:#fff;border:1px solid #ddd;border-radius:8px;padding:28px;max-width:860px;font-family:Arial,sans-serif;font-size:13px}
.releve-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px}
.bank-name{font-size:22px;font-weight:700;color:#042e5a;letter-spacing:2px}
.bank-sub{font-size:11px;color:#666}
.releve-title{font-size:18px;font-weight:700;color:#042e5a;text-align:right}
.releve-period{font-size:12px;color:#555;text-align:right}
.releve-divider{border:none;border-top:2px solid #042e5a;margin:12px 0}
.client-compte-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.bloc-title{font-size:10px;font-weight:700;color:#042e5a;text-transform:uppercase;letter-spacing:1px;margin-bottom:4px}
.bloc-val{font-size:15px;font-weight:700;margin-bottom:4px}
.info-mini{width:100%;border-collapse:collapse;font-size:12px}
.info-mini td{padding:3px 6px}
.info-mini td:first-child{color:#666;width:90px}
.bold{font-weight:700}.mono{font-family:monospace}
.recap-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
.recap-item{border-radius:8px;padding:12px;border:1px solid #e0e0e0}
.recap-initial{background:#f4f6f9}.recap-credit{background:#d4edda}.recap-debit{background:#f8d7da}.recap-final{background:#042e5a;color:#fff}
.recap-label{font-size:10px;font-weight:600;text-transform:uppercase;margin-bottom:4px;opacity:.8}
.recap-val{font-size:16px;font-weight:700}
.green{color:#155724}.red{color:#721c24}
.mvt-table{width:100%;border-collapse:collapse;font-size:12px}
.mvt-table th{background:#042e5a;color:#fff;padding:8px 10px;text-align:left}
.mvt-table td{padding:6px 10px;border-bottom:1px solid #eee}
.date-cell{white-space:nowrap;font-family:monospace}
.ref-cell{font-size:10px;color:#888}
.debit-cell{text-align:right;color:#dc3545;font-weight:600}
.credit-cell{text-align:right;color:#28a745;font-weight:600}
.total-row td{background:#f4f6f9;padding:8px 10px}
.empty-row{text-align:center;color:#999;padding:20px}
.releve-footer{margin-top:20px;font-size:10px;color:#888;text-align:center;border-top:1px solid #eee;padding-top:10px}
</style>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
