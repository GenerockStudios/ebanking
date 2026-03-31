<?php
/**
 * cloture_journee.php
 * Vue pour la réconciliation et la clôture de la journée comptable.
 * Reçoit $data['title'], $data['reconciliation'], $data['success'], et $data['error'].
 */
require_once VIEW_PATH . 'layout/header.php';

$reconciliation = $data['reconciliation'] ?? null;
?>

<h2><?= $data['title'] ?? "Clôture de Journée" ?></h2>

<?php if (isset($data['success'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['success']) ?>", 'success'));</script>
<?php endif; ?>
<?php if (isset($data['error'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['error']) ?>", 'error'));</script>
<?php endif; ?>

<?php if ($reconciliation):
    $netFlow   = (float)$reconciliation['total_depots'] - (float)$reconciliation['total_retraits'];
    $flowPositif = $netFlow >= 0;
?>

<!-- ===== Bilan du jour ===== -->
<div class="section-card">
    <div class="section-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#042e5a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
        </svg>
        Bilan des Opérations du Jour &mdash; <?= date('d/m/Y') ?>
    </div>

    <div class="kpi-row">
        <div class="kpi-card kpi-depot">
            <span class="kpi-label">Total Dépôts</span>
            <span class="kpi-value"><?= number_format((float)$reconciliation['total_depots'], 2, ',', ' ') ?> FCFA</span>
        </div>
        <div class="kpi-card kpi-retrait">
            <span class="kpi-label">Total Retraits</span>
            <span class="kpi-value"><?= number_format((float)$reconciliation['total_retraits'], 2, ',', ' ') ?> FCFA</span>
        </div>
        <div class="kpi-card kpi-net <?= $flowPositif ? 'kpi-net-pos' : 'kpi-net-neg' ?>">
            <span class="kpi-label">Flux Net de Trésorerie</span>
            <span class="kpi-value"><?= ($flowPositif ? '+' : '') . number_format($netFlow, 2, ',', ' ') ?> FCFA</span>
        </div>
        <div class="kpi-card kpi-txns">
            <span class="kpi-label">Nb. Transactions</span>
            <span class="kpi-value"><?= (int)$reconciliation['total_txns'] ?></span>
        </div>
    </div>

    <div class="table-scroll-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Indicateur</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total des Dépôts du Jour</td>
                    <td class="montant-cell depot-val"><?= number_format((float)$reconciliation['total_depots'], 2, ',', ' ') ?> FCFA</td>
                </tr>
                <tr>
                    <td>Total des Retraits du Jour</td>
                    <td class="montant-cell retrait-val"><?= number_format((float)$reconciliation['total_retraits'], 2, ',', ' ') ?> FCFA</td>
                </tr>
                <tr class="row-net">
                    <td><strong>Flux Net de Trésorerie</strong></td>
                    <td class="montant-cell <?= $flowPositif ? 'flow-pos' : 'flow-neg' ?>">
                        <strong><?= ($flowPositif ? '+' : '') . number_format($netFlow, 2, ',', ' ') ?> FCFA</strong>
                    </td>
                </tr>
                <tr>
                    <td>Nombre Total de Transactions</td>
                    <td class="montant-cell"><?= (int)$reconciliation['total_txns'] ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== Action de clôture ===== -->
<div class="cloture-card">
    <div class="cloture-icon">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#b45309" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
    </div>
    <div class="cloture-body">
        <h3>Validation &amp; Clôture</h3>
        <p>Vérifiez que tous les totaux sont réconciliés avant de procéder. <strong>Cette action est irréversible.</strong></p>
        <form method="POST" action="<?= BASE_URL ?>?controller=Rapport&action=clotureJournee">
            <input type="hidden" name="action" value="cloturer">
            <button type="submit" class="btn-cloture"
                    onclick="return confirm('Êtes-vous SÛR de vouloir procéder à la CLÔTURE DE JOURNÉE ? Cette action est irréversible.')">
                Exécuter la Clôture de Journée
            </button>
        </form>
    </div>
</div>

<?php else: ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast("Impossible de récupérer les données de réconciliation. Vérifiez la connexion à la base de données ou les logs.", 'error'));</script>
<?php endif; ?>

<style>
/* ---- Section card ---- */
.section-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
    margin-bottom: 24px;
}
.section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 700;
    color: #042e5a;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f0f0f0;
}

/* ---- KPI row ---- */
.kpi-row { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; }
.kpi-card {
    flex: 1;
    min-width: 160px;
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.kpi-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; opacity: .75; }
.kpi-value { font-size: 18px; font-weight: 700; }

.kpi-depot   { background: #f0fff4; border: 1px solid #b7ebc8; }
.kpi-depot .kpi-label  { color: #155724; }
.kpi-depot .kpi-value  { color: #155724; }

.kpi-retrait { background: #fff5f5; border: 1px solid #f5c6cb; }
.kpi-retrait .kpi-label { color: #721c24; }
.kpi-retrait .kpi-value { color: #721c24; }

.kpi-net-pos { background: #eff6ff; border: 1px solid #bfdbfe; }
.kpi-net-pos .kpi-label { color: #1e40af; }
.kpi-net-pos .kpi-value { color: #1e40af; }

.kpi-net-neg { background: #fff5f5; border: 1px solid #f5c6cb; }
.kpi-net-neg .kpi-label { color: #721c24; }
.kpi-net-neg .kpi-value { color: #721c24; }

.kpi-txns { background: #f8f9fa; border: 1px solid #dee2e6; }
.kpi-txns .kpi-label { color: #495057; }
.kpi-txns .kpi-value { color: #042e5a; }

/* ---- Tableau ---- */
.table-scroll { overflow-x: auto; }
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.data-table th { background: #042e5a; color: #fff; padding: 11px 12px; text-align: left; white-space: nowrap; }
.data-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.data-table tr:hover td { background: #f9f9fb; }
.row-net td { background: #f0f4ff; }
.row-net:hover td { background: #e6edff; }

.montant-cell { text-align: right; font-weight: 700; }
.depot-val    { color: #155724; }
.retrait-val  { color: #721c24; }
.flow-pos     { color: #1e40af; font-size: 14px; }
.flow-neg     { color: #721c24; font-size: 14px; }

/* ---- Clôture card ---- */
.cloture-card {
    display: flex;
    gap: 20px;
    align-items: flex-start;
    background: #fffbeb;
    border: 1.5px solid #fcd34d;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.cloture-icon { flex-shrink: 0; padding-top: 2px; }
.cloture-body h3 { color: #92400e; font-size: 15px; font-weight: 700; margin: 0 0 8px; }
.cloture-body p  { color: #78350f; font-size: 14px; margin: 0 0 16px; }

.btn-cloture {
    display: inline-block;
    padding: 12px 28px;
    background: #d97706;
    color: #ffffff;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 700;
    transition: all .2s ease;
    box-shadow: 0 4px 12px rgba(217,119,6,.25);
}
.btn-cloture:hover {
    background: #b45309;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(180,83,9,.4);
}
</style>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
