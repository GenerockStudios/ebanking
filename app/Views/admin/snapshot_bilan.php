<?php
/**
 * snapshot_bilan.php — Bilan Analytique de Snapshot Fin de Mois (Imprimable A4 Paysage)
 */
require_once VIEW_PATH . 'layout/header.php';

$report       = $data['report']          ?? [];
$dates        = $data['dates']           ?? [];
$currentDate  = $data['current_date']    ?? '';
$previousDate = $data['previous_date']   ?? '';
$totalM       = $data['total_m']         ?? 0;
$totalM_1     = $data['total_m_1']       ?? 0;
$globalEvol   = $data['global_evolution'] ?? 0;
?>

<style>
@media print {
    @page { size: A4 landscape; margin: 1.5cm; }
    .no-print { display: none !important; }
    .sidebar, footer { display: none !important; }
    .content { padding: 0 !important; box-shadow: none !important; width: 100% !important; padding-left: 0 !important; }
    * { box-shadow: none !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
}
.no-print .page-actions {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
}
.no-print .page-actions h2 { margin: 0; color: #042e5a; font-size: 1.4rem; font-weight: 700; border: none; padding: 0; }
.btn-action {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 6px; border: none;
    cursor: pointer; font-weight: 600; font-size: 14px;
    text-decoration: none; transition: 0.2s ease;
}
.btn-navy  { background: #042e5a; color: #fff; } .btn-navy:hover  { background: #021d3a; }
.btn-green { background: #28a745; color: #fff; } .btn-green:hover { background: #1e7e34; }
.btn-gray  { background: #6c757d; color: #fff; } .btn-gray:hover  { background: #545b62; }

.date-strip {
    background: #f1f4f8; border-radius: 8px; padding: 14px 18px;
    margin-bottom: 20px; display: flex; gap: 16px; align-items: center; flex-wrap: wrap;
}
.date-strip label { font-size: 13px; font-weight: 600; color: #555; }
.date-strip select {
    padding: 8px 12px; border-radius: 6px; border: 1px solid #ced4da;
    font-size: 13px; background: #fff;
}

.doc-wrapper {
    background: #fff; border: 2px solid #2c3e50;
    border-radius: 8px; padding: 32px;
}
.doc-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 3px double #2c3e50; padding-bottom: 18px; margin-bottom: 24px;
}
.doc-brand { font-size: 20px; font-weight: 900; color: #042e5a; }
.doc-subtitle-brand { font-size: 10px; color: #777; margin-top: 4px; }
.doc-title { font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: #042e5a; }
.doc-meta { text-align: right; font-size: 12px; color: #555; line-height: 1.6; }

.analytics-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
.gauge-card {
    background: #fdfdfd; border: 1px solid #e0e0e0;
    border-radius: 12px; padding: 22px; text-align: center;
}
.gauge-title { font-size: 12px; font-weight: 700; color: #777; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }
.gauge-value { font-size: 32px; font-weight: 800; }
.evol-positive { color: #28a745; }
.evol-negative { color: #dc3545; }
.evol-neutral  { color: #6c757d; }
.gauge-bar { height: 10px; background: #eee; border-radius: 5px; margin: 14px 0; overflow: hidden; }
.gauge-fill { height: 100%; border-radius: 5px; transition: width 1s ease; }
.gauge-hint { font-size: 11px; color: #999; }

.fin-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.fin-table thead th {
    background: #f8f9fa; color: #2c3e50;
    border-bottom: 2px solid #2c3e50; padding: 12px 14px; text-align: left;
}
.fin-table thead th.num { text-align: right; }
.fin-table tbody td { padding: 12px 14px; border-bottom: 1px solid #eee; vertical-align: middle; }
.fin-table tbody tr:hover { background: #f8faff; }
.fin-table tfoot td {
    padding: 13px 14px; font-weight: 800; font-size: 15px;
    background: #f1f4f8; border-top: 2px solid #2c3e50;
}
.num { text-align: right; }
.trend-up   { color: #28a745; }
.trend-down { color: #dc3545; }
.trend-flat { color: #6c757d; }
.sig-row { display: flex; justify-content: space-between; margin-top: 60px; gap: 30px; }
.sig-box { flex: 1; text-align: center; padding-top: 12px; border-top: 1px dotted #2c3e50; font-style: italic; font-size: 12px; color: #555; }
.print-footer { margin-top: 24px; font-size: 10px; color: #bbb; text-align: center; }
</style>

<!-- Controls no-print -->
<div class="no-print">
    <div class="page-actions">
        <h2>
            <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">bar_chart</span>
            Bilan Analytique — Snapshot Fin de Mois
        </h2>
        <div style="display:flex;gap:10px;">
            <button onclick="window.print()" class="btn-action btn-green">
                <span class="material-symbols-rounded">print</span> Imprimer (A4 Paysage)
            </button>
            <a href="<?= BASE_URL ?>?controller=Admin&action=analyticsDashboard" class="btn-action btn-gray">
                <span class="material-symbols-rounded">arrow_back</span> Dashboard
            </a>
        </div>
    </div>

    <form method="GET" action="<?= BASE_URL ?>" class="date-strip">
        <input type="hidden" name="controller" value="Admin">
        <input type="hidden" name="action" value="snapshotBilan">
        <label>Snapshot Principal (M) :</label>
        <select name="current_date">
            <?php foreach ($dates as $d): ?>
            <option value="<?= $d ?>" <?= $d === $currentDate ? 'selected' : '' ?>><?= date('d/m/Y', strtotime($d)) ?></option>
            <?php endforeach; ?>
        </select>
        <label>Comparaison (M−1) :</label>
        <select name="previous_date">
            <?php foreach ($dates as $d): ?>
            <option value="<?= $d ?>" <?= $d === $previousDate ? 'selected' : '' ?>><?= date('d/m/Y', strtotime($d)) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-action btn-navy">
            <span class="material-symbols-rounded">compare_arrows</span> Comparer
        </button>
    </form>
</div>

<!-- Document officiel -->
<div class="doc-wrapper">
    <div class="doc-header">
        <div>
            <div class="doc-brand">E-BANKING PRO</div>
            <div class="doc-subtitle-brand">Système E-Banking — Module Analytique</div>
        </div>
        <div class="doc-title">Bilan Analytique de Snapshot</div>
        <div class="doc-meta">
            <strong><?= APP_NAME ?></strong><br>
            Direction de l'Exploitation<br>
            Édité le : <?= $data['date_edition'] ?><br>
            Réf : RPT-SNAP-<?= date('Ymd') ?>
        </div>
    </div>

    <p style="font-size:13px;color:#555;font-style:italic;margin-bottom:22px;">
        Rapport comparatif du snapshot du <strong><?= date('d/m/Y', strtotime($currentDate)) ?></strong>
        avec la période de référence du <strong><?= date('d/m/Y', strtotime($previousDate)) ?></strong>.
    </p>

    <!-- Indicateurs -->
    <div class="analytics-grid">
        <div class="gauge-card">
            <div class="gauge-title">Évolution Globale des Encours</div>
            <div class="gauge-value <?= $globalEvol >= 0 ? 'evol-positive' : 'evol-negative' ?>">
                <?= ($globalEvol >= 0 ? '+' : '') . number_format($globalEvol, 2) ?>%
            </div>
            <div class="gauge-bar">
                <div class="gauge-fill" style="width:<?= min(100, abs($globalEvol) * 5) ?>%; background:<?= $globalEvol >= 0 ? '#28a745' : '#dc3545' ?>;"></div>
            </div>
            <div class="gauge-hint">Performance vis-à-vis de M−1</div>
        </div>
        <div class="gauge-card">
            <div class="gauge-title">Total des Ressources (M)</div>
            <div class="gauge-value" style="color:#042e5a;"><?= number_format($totalM, 0, ',', ' ') ?> <small style="font-size:18px;">FCFA</small></div>
            <div style="font-size:12px;color:#888;margin-top:12px;">
                Variation : <?= number_format($totalM - $totalM_1, 0, ',', ' ') ?> FCFA
            </div>
        </div>
    </div>

    <!-- Tableau financier -->
    <table class="fin-table">
        <thead>
            <tr>
                <th>Catégories de Comptes</th>
                <th class="num">Solde au <?= date('d/m/Y', strtotime($previousDate)) ?></th>
                <th class="num">Solde au <?= date('d/m/Y', strtotime($currentDate)) ?></th>
                <th class="num">Évolution (%)</th>
                <th>Tendance</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($report as $row): ?>
            <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($row['categorie']) ?></td>
                <td class="num"><?= number_format($row['solde_m_1'], 0, ',', ' ') ?></td>
                <td class="num"><?= number_format($row['solde_m'], 0, ',', ' ') ?></td>
                <td class="num">
                    <span class="<?= $row['evolution_pct'] > 0 ? 'evol-positive' : ($row['evolution_pct'] < 0 ? 'evol-negative' : 'evol-neutral') ?>">
                        <?= ($row['evolution_pct'] > 0 ? '+' : '') . number_format($row['evolution_pct'], 2) ?>%
                    </span>
                </td>
                <td>
                    <?php if ($row['evolution_pct'] > 0): ?>
                        <span class="material-symbols-rounded trend-up" style="font-size:18px;vertical-align:middle;">trending_up</span>
                    <?php elseif ($row['evolution_pct'] < 0): ?>
                        <span class="material-symbols-rounded trend-down" style="font-size:18px;vertical-align:middle;">trending_down</span>
                    <?php else: ?>
                        <span class="material-symbols-rounded trend-flat" style="font-size:18px;vertical-align:middle;">trending_flat</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAUX GÉNÉRAUX</td>
                <td class="num"><?= number_format($totalM_1, 0, ',', ' ') ?></td>
                <td class="num" style="color:#042e5a;"><?= number_format($totalM, 0, ',', ' ') ?></td>
                <td class="num">
                    <span class="<?= $globalEvol >= 0 ? 'evol-positive' : 'evol-negative' ?>">
                        <?= ($globalEvol >= 0 ? '+' : '') . number_format($globalEvol, 2) ?>%
                    </span>
                </td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="sig-row">
        <div class="sig-box">Visa du Responsable d'Agence</div>
        <div class="sig-box">Direction des Risques &amp; Audit</div>
    </div>

    <div class="print-footer">
        Document généré par le système E-Banking — Toute reproduction sans visa est interdite.
    </div>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
