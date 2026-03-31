<?php
/**
 * releve.php — Relevé de Compte Détaillé (Imprimable)
 */
require_once VIEW_PATH . 'layout/header.php';

$filters      = $data['filters'];
$compte       = $data['compte'] ?? null;
$transactions = $data['transactions'] ?? [];
$bilan        = $data['bilan'] ?? null;
?>

<style>
/* Layout impression géré globalement par responsive-core.css via les classes .no-print et .doc-wrapper */

.section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.section-header h2 { margin:0; color:#042e5a; font-size:1.4rem; font-weight:700; border:none; padding:0; }
.btn-action {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border-radius:6px; border:none; cursor:pointer;
    font-weight:600; font-size:14px; text-decoration:none; transition:0.2s ease;
}
.btn-navy   { background:#042e5a; color:#fff; } .btn-navy:hover   { background:#021d3a; }
.btn-green  { background:#28a745; color:#fff; } .btn-green:hover  { background:#1e7e34; }
.btn-submit { background:#042e5a; color:#fff; } .btn-submit:hover { background:#021d3a; }

/* Formulaire de filtrage responsive */
.filter-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 24px; margin-bottom: 24px;
    display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end;
    box-shadow: 0 4px 6px rgba(0,0,0,0.02);
}
.filter-group { display: flex; flex-direction: column; gap: 8px; }
.filter-group label { font-size: 13px; font-weight: 600; color: #64748b; }
.filter-group input[type=text],
.filter-group input[type=date] {
    padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px;
    font-size: 15px; color: #0f172a; outline: none; transition: border-color .2s;
    width: 100%; box-sizing: border-box;
    /* Pour iOS touch targets */
    min-height: 48px;
}
.filter-group input:focus { border-color: #042e5a; box-shadow: 0 0 0 3px rgba(4,46,90,0.1); }
.btn-submit { min-height: 48px; width: 100%; justify-content: center; }

.alert-error { background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:6px; margin-bottom:16px; border:1px solid #f5c6cb; }

/* Document imprimable */
.doc-wrapper {
    background:#fff; border:2px solid #2c3e50; border-radius:8px;
    padding:36px; max-width:960px; margin:0 auto;
}
.doc-header {
    display:flex; justify-content:space-between; align-items:flex-start;
    border-bottom:3px double #2c3e50; padding-bottom:18px; margin-bottom:24px;
}
.doc-left .doc-title { font-size:20px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#2c3e50; }
.doc-left .doc-period { font-size:13px; color:#555; margin-top:4px; }
.doc-meta { text-align:right; font-size:12px; color:#555; line-height:1.6; }

.parties-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:24px; }
.partie-box { background:#f8f9fa; border-radius:8px; padding:14px 16px; }
.partie-box h5 { font-size:12px; font-weight:700; text-transform:uppercase; color:#042e5a; margin:0 0 8px; border-bottom:1px solid #e0e0e0; padding-bottom:6px; }
.partie-box p  { margin:3px 0; font-size:13px; color:#333; }

.bilan-row { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:24px; }
.bilan-cell { background:#f8f9fa; border-radius:8px; padding:14px; text-align:center; }
.bilan-cell.primary { background:#042e5a; color:#fff; }
.bilan-cell .cell-label { font-size:10px; font-weight:700; text-transform:uppercase; color:#888; margin-bottom:6px; }
.bilan-cell.primary .cell-label { color:rgba(255,255,255,.7); }
.bilan-cell .cell-val { font-size:16px; font-weight:800; color:#042e5a; }
.bilan-cell.primary .cell-val { color:#fff; }
.bilan-cell .red { color:#dc3545; }
.bilan-cell .green { color:#28a745; }

.stmt-table { width:100%; border-collapse:collapse; font-size:12px; }
.stmt-table thead th {
    background:#2c3e50; color:#fff; padding:10px 12px;
    text-align:left; font-weight:600; text-transform:uppercase; font-size:11px; letter-spacing:.5px;
}
.stmt-table thead th.num { text-align:right; }
.stmt-table tbody td { padding:9px 12px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.stmt-table tbody tr.opener td { color:#777; font-style:italic; }
.stmt-table tbody tr:hover { background:#f8faff; }
.stmt-table tfoot td { padding:10px 12px; background:#f8f9fa; font-weight:700; border-top:2px solid #2c3e50; }
.stmt-table tfoot td.num { text-align:right; }
.num { text-align:right; }
.credit { color:#28a745; font-weight:600; }
.debit  { color:#dc3545; font-weight:600; }

.sig-row { display:flex; justify-content:space-between; margin-top:60px; gap:20px; }
.sig-box { flex:1; text-align:center; padding-top:12px; border-top:1px dotted #2c3e50; font-style:italic; font-size:12px; color:#555; }
.legal-note { font-size:10px; color:#aaa; text-align:center; margin-top:30px; border-top:1px solid #f0f0f0; padding-top:10px; }

.print-btn-row { text-align:center; margin-top:20px; }
.empty-state { text-align:center; padding:40px; color:#aaa; background:#f8f9fa; border-radius:8px; }
</style>

<!-- En-tête page -->
<div class="section-header no-print">
    <h2>
        <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">receipt_long</span>
        Relevé de Compte Détaillé
    </h2>
    <a href="<?= BASE_URL ?>?controller=Caisse&action=dashboard" class="btn-action btn-navy">
        <span class="material-symbols-rounded">arrow_back</span> Retour
    </a>
</div>

<!-- Formulaire filtres -->
<form method="POST" action="<?= BASE_URL ?>?controller=Caisse&action=releve" class="filter-card no-print">
    <div class="filter-group">
        <label for="numero_compte">Numéro de Compte</label>
        <input type="text" id="numero_compte" name="numero_compte"
               value="<?= htmlspecialchars($filters['numero_compte']) ?>"
               placeholder="Ex: 10001234567" required>
    </div>
    <div class="filter-group">
        <label for="date_debut">Date Début</label>
        <input type="date" id="date_debut" name="date_debut"
               value="<?= htmlspecialchars($filters['date_debut']) ?>" required>
    </div>
    <div class="filter-group">
        <label for="date_fin">Date Fin</label>
        <input type="date" id="date_fin" name="date_fin"
               value="<?= htmlspecialchars($filters['date_fin']) ?>" required>
    </div>
    <button type="submit" class="btn-action btn-submit">
        <span class="material-symbols-rounded">search</span> Filtrer
    </button>
</form>

<?php if (isset($data['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['error']) ?>", 'error', 6000));</script>
<?php endif; ?>
<?php if ($compte): ?>

<!-- Document officiel imprimable -->
<div class="doc-wrapper">
    <div class="doc-header">
        <div class="doc-left">
            <div class="doc-title">Extrait de Compte</div>
            <div class="doc-period">
                Période du <strong><?= date('d/m/Y', strtotime($filters['date_debut'])) ?></strong>
                au <strong><?= date('d/m/Y', strtotime($filters['date_fin'])) ?></strong>
            </div>
        </div>
        <div class="doc-meta">
            <strong><?= APP_NAME ?></strong><br>
            Édité le : <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>

    <!-- Titulaire et compte -->
    <div class="parties-grid">
        <div class="partie-box">
            <h5>Titulaire du Compte</h5>
            <p><strong><?= htmlspecialchars($compte->nom . ' ' . $compte->prenom) ?></strong></p>
            <p><?= nl2br(htmlspecialchars($compte->adresse ?? '')) ?></p>
            <p><?= htmlspecialchars($compte->telephone ?? '') ?></p>
        </div>
        <div class="partie-box" style="text-align:right;">
            <h5>Détails Compte</h5>
            <p>N° <strong><?= htmlspecialchars($compte->numero_compte) ?></strong></p>
            <p>Type : <?= htmlspecialchars($compte->type_compte ?? '') ?></p>
            <p>Monnaie : <strong>FCFA</strong></p>
        </div>
    </div>

    <!-- Bilan résumé -->
    <div class="bilan-row">
        <div class="bilan-cell">
            <div class="cell-label">Solde Initial</div>
            <div class="cell-val"><?= number_format($bilan['solde_initial'], 2, ',', ' ') ?></div>
        </div>
        <div class="bilan-cell">
            <div class="cell-label">Total Débits</div>
            <div class="cell-val red"><?= number_format($bilan['total_debit'], 2, ',', ' ') ?></div>
        </div>
        <div class="bilan-cell">
            <div class="cell-label">Total Crédits</div>
            <div class="cell-val green"><?= number_format($bilan['total_credit'], 2, ',', ' ') ?></div>
        </div>
        <div class="bilan-cell primary">
            <div class="cell-label">Solde Final</div>
            <div class="cell-val"><?= number_format($bilan['solde_final'], 2, ',', ' ') ?></div>
        </div>
    </div>

    <!-- Liste des transactions -->
    <div class="table-scroll-wrap">
        <table class="stmt-table">
            <thead>
                <tr>
                    <th width="15%">Date</th>
                    <th width="38%">Libellé / Référence</th>
                    <th class="num" width="16%">Débit (−)</th>
                    <th class="num" width="16%">Crédit (+)</th>
                    <th class="num" width="15%">Solde</th>
                </tr>
            </thead>
            <tbody>
                <!-- Ligne solde initial -->
            <tr class="opener">
                <td><?= date('d/m/Y', strtotime($filters['date_debut'])) ?></td>
                <td>SOLDE INITIAL REPORTÉ</td>
                <td class="num">—</td>
                <td class="num">—</td>
                <td class="num"><?= number_format($bilan['solde_initial'], 2, ',', ' ') ?></td>
            </tr>
            <?php
            $soldeRunning = $bilan['solde_initial'];
            foreach ($transactions as $t):
                if ($t['sens'] === 'DEBIT') {
                    $soldeRunning -= (float)$t['montant'];
                    $debitCell  = number_format($t['montant'], 2, ',', ' ');
                    $creditCell = '—';
                } else {
                    $soldeRunning += (float)$t['montant'];
                    $creditCell = number_format($t['montant'], 2, ',', ' ');
                    $debitCell  = '—';
                }
            ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($t['date_transaction'])) ?></td>
                <td>
                    <strong><?= htmlspecialchars($t['type_transaction']) ?></strong><br>
                    <small style="color:#999;">Réf : <?= htmlspecialchars($t['reference_externe'] ?? '') ?></small>
                </td>
                <td class="num debit"><?= $debitCell ?></td>
                <td class="num credit"><?= $creditCell ?></td>
                <td class="num" style="font-weight:700;"><?= number_format($soldeRunning, 2, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;">TOTAUX DE LA PÉRIODE</td>
                <td class="num debit"><?= number_format($bilan['total_debit'], 2, ',', ' ') ?></td>
                <td class="num credit"><?= number_format($bilan['total_credit'], 2, ',', ' ') ?></td>
                <td class="num">SOLDE FINAL : <?= number_format($bilan['solde_final'], 2, ',', ' ') ?></td>
            </tr>
            </tfoot>
        </table>
    </div>

    <div class="sig-row">
        <div class="sig-box">Le Client (Signature)</div>
        <div class="sig-box">La Banque (Cachet et Signature)</div>
    </div>

    <div class="legal-note">
        Ce relevé est un document officiel généré par le système <?= APP_NAME ?>.
        En cas de contestation, merci de contacter votre conseiller sous 30 jours.
    </div>
</div>

<!-- Bouton impression -->
<div class="print-btn-row no-print" style="margin-top:20px;">
    <button onclick="window.print()" class="btn-action btn-green" style="margin:0 auto;">
        <span class="material-symbols-rounded">print</span> Imprimer le Relevé
    </button>
</div>

<?php elseif (!empty($filters['numero_compte'])): ?>
<div class="empty-state">
    <span class="material-symbols-rounded" style="font-size:40px;display:block;margin-bottom:12px;color:#aaa;">inbox</span>
    Aucune transaction trouvée pour la période sélectionnée.
</div>
<?php endif; ?>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
