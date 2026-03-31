<?php
/**
 * pv_cloture.php - Procès-Verbal d'Arrêté de Caisse (Document Imprimable)
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<style>
/* L'impression (A4, masquages) est gérée globalement par responsive-core.css via .no-print et .doc-wrapper */
.no-print .page-actions { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.no-print .page-actions h2 { margin:0; color:#042e5a; font-size:1.4rem; font-weight:700; border:none; padding:0; }
.btn-action {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border-radius:6px; border:none; cursor:pointer;
    font-weight:600; font-size:14px; text-decoration:none; transition:0.2s ease;
}
.btn-navy  { background:#042e5a; color:#fff; } .btn-navy:hover  { background:#021d3a; }
.btn-green { background:#28a745; color:#fff; } .btn-green:hover { background:#1e7e34; }
.btn-gray  { background:#6c757d; color:#fff; } .btn-gray:hover  { background:#545b62; }

.doc-wrapper {
    background:#fff; border:2px solid #2c3e50; border-radius:8px; padding:36px; max-width:900px; margin:0 auto;
}
.doc-header {
    display:flex; justify-content:space-between; align-items:center;
    border-bottom:3px double #2c3e50; padding-bottom:18px; margin-bottom:26px;
}
.logo-brand { font-size:24px; font-weight:900; color:#042e5a; }
.doc-title  { font-size:22px; font-weight:800; text-transform:uppercase; letter-spacing:2px; color:#2c3e50; }
.doc-meta   { text-align:right; font-size:12px; color:#444; line-height:1.5; }

.section-label {
    background:#f8f9fa; padding:9px 16px; font-weight:700;
    border-left:5px solid #2c3e50; margin:22px 0 14px;
    text-transform:uppercase; font-size:13px; border-radius:0 4px 4px 0;
    color:#2c3e50;
}
.info-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:10px 24px; margin-bottom:20px; }
.info-grid .cell strong { color:#555; font-size:12px; text-transform:uppercase; font-weight:600; display:block; margin-bottom:2px; }
.info-grid .cell span { font-size:15px; color:#1a1f36; }

.finance-table { width:100%; border-collapse:collapse; margin-bottom:20px; font-size:13px; }
.finance-table thead th { background:#f2f2f2; padding:11px 13px; text-align:left; border:1px solid #ddd; font-weight:600; }
.finance-table thead th.num { text-align:right; }
.finance-table tbody td { padding:11px 13px; border:1px solid #e8e8e8; }
.finance-table tbody td.num { text-align:right; font-weight:700; }
.finance-table tfoot td { padding:11px 13px; border:1px solid #ddd; font-weight:800; background:#f8f9fa; }
.finance-table tfoot td.num { text-align:right; }
.highlight   { background:#fffdf0; font-weight:700; }
.diff-positive { color:#28a745; font-weight:700; }
.diff-negative { color:#dc3545; font-weight:700; }
.diff-zero     { color:#6c757d; font-weight:700; }

.obs-box { border:1.5px solid #e0e0e0; border-radius:6px; padding:14px 16px; min-height:70px; font-size:13px; color:#555; margin-bottom:20px; }
.sig-row { display:flex; justify-content:space-between; margin-top:60px; gap:30px; }
.sig-box { flex:1; text-align:center; padding-top:14px; border-top:1px dotted #2c3e50; font-style:italic; font-size:12px; color:#555; }
.doc-footer-note { margin-top:32px; font-size:10px; color:#aaa; text-align:center; border-top:1px solid #f0f0f0; padding-top:8px; }
.badge-ok  { display:inline-block; background:#d4edda; color:#155724; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
.badge-err { display:inline-block; background:#f8d7da; color:#721c24; padding:4px 12px; border-radius:20px; font-size:12px; font-weight:700; }
</style>

<div class="no-print">
    <div class="page-actions">
        <h2>
            <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">receipt_long</span>
            Procès-Verbal d'Arrêté de Caisse
        </h2>
        <div style="display:flex;gap:10px;">
            <button onclick="window.print()" class="btn-action btn-green">
                <span class="material-symbols-rounded">print</span> Imprimer le PV
            </button>
            <a href="<?= BASE_URL ?>?controller=Caisse&action=dashboard" class="btn-action btn-gray">
                <span class="material-symbols-rounded">arrow_back</span> Dashboard
            </a>
        </div>
    </div>
</div>

<div class="doc-wrapper">
    <div class="doc-header">
        <div class="logo-brand">E-BANKING PRO</div>
        <div class="doc-title">Arrêté de Caisse</div>
        <div class="doc-meta">
            <strong><?= APP_NAME ?></strong><br>
            Direction des Opérations<br>
            Édité le : <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>

    <!-- Infos session -->
    <div class="section-label">I. Informations sur la Session</div>
    <div class="info-grid">
        <div class="cell">
            <strong>ID Session</strong>
            <span>#<?= str_pad($data['session']->session_id, 6, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="cell">
            <strong>Caissier</strong>
            <span><?= htmlspecialchars($data['session']->caissier_nom ?? $_SESSION['nom_complet'] ?? 'N/A') ?></span>
        </div>
        <div class="cell">
            <strong>Date</strong>
            <span><?= date('d/m/Y', strtotime($data['session']->date_ouverture)) ?></span>
        </div>
        <div class="cell">
            <strong>Horaires</strong>
            <span><?= $data['session']->heure_ouverture ?? '-' ?> — <?= $data['session']->heure_fermeture ?? '-' ?></span>
        </div>
    </div>

    <!-- Flux -->
    <div class="section-label">II. Récapitulatif des Flux Journaliers</div>
    <table class="finance-table">
        <thead>
            <tr>
                <th>Type d'Opération</th>
                <th class="num">Nombre</th>
                <th class="num">Total Brut (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalIn  = 0;
            $totalOut = 0;
            if (!empty($data['stats'])) {
                foreach ($data['stats'] as $stat) {
                    if ($stat['type_transaction'] === 'DEPOT')   $totalIn  += $stat['total'];
                    if ($stat['type_transaction'] === 'RETRAIT') $totalOut += $stat['total'];
                    $typeLabel = match($stat['type_transaction']) {
                        'DEPOT'   => 'Dépôts (Entrées)',
                        'RETRAIT' => 'Retraits (Sorties)',
                        default   => htmlspecialchars($stat['type_transaction']),
                    };
                    echo "<tr>
                        <td>$typeLabel</td>
                        <td class=\"num\">{$stat['nb']}</td>
                        <td class=\"num\">" . number_format($stat['total'], 2, ',', ' ') . "</td>
                    </tr>";
                }
            } else {
                echo '<tr><td colspan="3" style="text-align:center;padding:20px;color:#aaa;font-style:italic;">Aucun mouvement durant cette session.</td></tr>';
            }
            ?>
        </tbody>
    </table>

    <!-- Bilan financier -->
    <div class="section-label">III. Bilan de l'Arrêté</div>
    <?php
        $systeme = $data['session']->solde_final_systeme ?? 0;
        $reel    = $data['session']->solde_final_reel    ?? 0;
        $diff    = $data['session']->difference          ?? 0;
        $diffClass = ($diff < 0) ? 'diff-negative' : (($diff > 0) ? 'diff-positive' : 'diff-zero');
    ?>
    <table class="finance-table">
        <tbody>
            <tr>
                <td><strong>Fond de Caisse Initial (A)</strong></td>
                <td class="num"><?= number_format($data['session']->solde_initial_caisse ?? 0, 2, ',', ' ') ?></td>
            </tr>
            <tr>
                <td>Cumul des Entrées (Dépôts)</td>
                <td class="num" style="color:#28a745;">+ <?= number_format($totalIn, 2, ',', ' ') ?></td>
            </tr>
            <tr>
                <td>Cumul des Sorties (Retraits)</td>
                <td class="num" style="color:#dc3545;">- <?= number_format($totalOut, 2, ',', ' ') ?></td>
            </tr>
            <tr class="highlight">
                <td><strong>Solde Théorique Système (B = A + Entrées − Sorties)</strong></td>
                <td class="num" style="color:#042e5a;"><?= number_format($systeme, 2, ',', ' ') ?></td>
            </tr>
            <tr class="highlight">
                <td><strong>Solde Réel Constaté (C)</strong></td>
                <td class="num"><?= number_format($reel, 2, ',', ' ') ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td><strong>Écart de Caisse (D = C − B)</strong></td>
                <td class="num <?= $diffClass ?>">
                    <?= number_format($diff, 2, ',', ' ') ?> FCFA
                    &nbsp;
                    <?php if ($diff == 0): ?>
                        <span class="badge-ok">À l'équilibre</span>
                    <?php elseif ($diff < 0): ?>
                        <span class="badge-err">Manquant</span>
                    <?php else: ?>
                        <span class="badge-ok">Excédent</span>
                    <?php endif; ?>
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Observations -->
    <div class="section-label">IV. Observations</div>
    <div class="obs-box">
        <?php if ($diff == 0): ?>
            Caisse arrêtée à l'équilibre. Aucune observation particulière.
        <?php else: ?>
            Écart de <?= number_format(abs($diff), 2, ',', ' ') ?> FCFA constaté.
            Justification à fournir au responsable d'agence.
        <?php endif; ?>
    </div>

    <!-- Signatures -->
    <div class="sig-row">
        <div class="sig-box">
            Le Caissier<br>
            <strong><?= htmlspecialchars($data['session']->caissier_nom ?? '') ?></strong>
        </div>
        <div class="sig-box">Le Responsable d'Agence<br>(Contrôle et Validation)</div>
    </div>

    <div class="doc-footer-note">
        Généré le <?= date('d/m/Y H:i:s') ?> | Réf : PV-CAISSE-<?= str_pad($data['session']->session_id, 6, '0', STR_PAD_LEFT) ?>
    </div>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
