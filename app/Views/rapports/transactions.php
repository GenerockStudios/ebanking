<?php
/**
 * transactions.php
 * Vue pour afficher le rapport des transactions entre deux dates.
 * Reçoit $data['title'], $data['transactions'], $data['date_debut'], $data['date_fin'], et $data['error'].
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= $data['title'] ?? "Rapport de Transactions" ?></h2>

<!-- ===== Filtres ===== -->
<div class="filter-card">
    <form method="GET" action="<?= BASE_URL ?>?controller=Rapport&action=rapportTransactions" class="filter-form">
        <input type="hidden" name="controller" value="Rapport">
        <input type="hidden" name="action"     value="rapportTransactions">

        <div class="filter-group">
            <label>Du</label>
            <input type="date" name="date_debut" class="form-control"
                   value="<?= htmlspecialchars($data['date_debut'] ?? '') ?>">
        </div>

        <div class="filter-group">
            <label>Au</label>
            <input type="date" name="date_fin" class="form-control"
                   value="<?= htmlspecialchars($data['date_fin'] ?? '') ?>">
        </div>

        <div class="filter-group">
            <label>Type</label>
            <select name="filter_type" class="form-control">
                <option value="">Tous</option>
                <option value="DEPOT"        <?= ($data['filter_type'] ?? '') === 'DEPOT'        ? 'selected' : '' ?>>Dépôt</option>
                <option value="RETRAIT"      <?= ($data['filter_type'] ?? '') === 'RETRAIT'      ? 'selected' : '' ?>>Retrait</option>
                <option value="TRANSFERT_INT"<?= ($data['filter_type'] ?? '') === 'TRANSFERT_INT'? 'selected' : '' ?>>Transfert interne</option>
            </select>
        </div>

        <div class="filter-group" style="align-self:flex-end;">
            <button type="submit" class="btn-filter">Filtrer</button>
        </div>
    </form>
</div>

<?php if (isset($data['error'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['error']) ?>", 'error'));</script>
<?php endif; ?>

<!-- ===== Barre de recherche & compteur ===== -->
<div class="search-bar">
    <input type="text" id="searchInput" class="form-control" placeholder="Rechercher (référence, compte, caissier...)">
    <p class="result-count"><strong id="rowCount"><?= count($data['transactions'] ?? []) ?></strong> transaction(s) trouvée(s).</p>
</div>

<!-- ===== Tableau ===== -->
<div class="table-scroll-wrap">
    <table class="data-table" id="txnTable" style="min-width:1000px;">
        <thead>
            <tr>
                <th>Réf. Externe</th>
                <th>Type</th>
                <th>Montant (FCFA)</th>
                <th>Compte Source</th>
                <th>Compte Destination</th>
                <th>Horodatage</th>
                <th>Caissier</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($data['transactions'])):
            $totalMontant = 0;
            foreach ($data['transactions'] as $txn):
                $totalMontant += $txn['montant'];
        ?>
            <tr>
                <td class="ref-num"><?= htmlspecialchars($txn['reference_externe']) ?></td>
                <td><span class="type-badge type-<?= strtolower($txn['type_transaction']) ?>"><?= htmlspecialchars($txn['type_transaction']) ?></span></td>
                <td class="montant-cell"><?= number_format($txn['montant'], 2, ',', ' ') ?></td>
                <td><?= htmlspecialchars($txn['source']      ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($txn['destination'] ?? 'N/A') ?></td>
                <td class="date-cell"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($txn['date_transaction']))) ?></td>
                <td><?= htmlspecialchars($txn['caissier']) ?></td>
                <td><span class="badge-statut badge-<?= strtolower($txn['statut']) ?>"><?= htmlspecialchars($txn['statut']) ?></span></td>
            </tr>
        <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" class="empty-row">Aucune transaction pour la période sélectionnée.</td></tr>
        <?php endif; ?>
        </tbody>
        <?php if (!empty($data['transactions'])): ?>
        <tfoot>
            <tr>
                <td colspan="2" style="font-weight:700;color:#042e5a;">Total des montants bruts</td>
                <td class="montant-cell total-sum"><?= number_format($totalMontant, 2, ',', ' ') ?></td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
</div>

<style>
/* ---- Filtres ---- */
.filter-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 24px;
}
.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}
.filter-group { display: flex; flex-direction: column; gap: 8px; }
.filter-group label { font-weight: 600; font-size: 13px; color: #64748b; }
.form-control {
    padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px;
    font-size: 15px; color: #0f172a; outline: none; transition: border-color .2s;
    width: 100%; box-sizing: border-box; min-height: 48px;
}
.form-control:focus { border-color: #042e5a; box-shadow: 0 0 0 3px rgba(4,46,90,0.1); }
.btn-filter {
    padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 15px;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; border: none; min-height: 48px; background: #042e5a; color: #fff; width: 100%;
}
.btn-filter:hover { background: #021d3a; }

/* ---- Recherche ---- */
.search-bar { display: flex; align-items: center; gap: 20px; margin-bottom: 12px; }
.search-bar .form-control { flex: 1; max-width: 360px; }
.result-count { color: #555; font-size: 14px; margin: 0; }

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
.data-table td { padding: 9px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.data-table tr:hover td { background: #f9f9fb; }
.data-table tfoot td { background: #f0f4ff; padding: 10px 12px; border-top: 2px solid #042e5a; }

.ref-num    { font-family: monospace; font-size: 12px; color: #042e5a; font-weight: 600; }
.montant-cell { text-align: right; font-weight: 700; }
.total-sum  { color: #042e5a; font-size: 14px; }
.date-cell  { white-space: nowrap; font-family: monospace; font-size: 12px; }

/* Badges types */
.type-badge         { padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.type-depot         { background: #d4edda; color: #155724; }
.type-retrait       { background: #f8d7da; color: #721c24; }
.type-transfert_int { background: #fff3cd; color: #856404; }

/* Badges statut */
.badge-statut                              { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
.badge-valide, .badge-success, .badge-completed  { background: #d4edda; color: #155724; }
.badge-echec, .badge-failed, .badge-error        { background: #f8d7da; color: #721c24; }
.badge-en_attente, .badge-pending                { background: #fff3cd; color: #856404; }

.empty-row { text-align: center; color: #999; padding: 24px; }
</style>

<script>
const searchInput = document.getElementById('searchInput');
const txnTable    = document.getElementById('txnTable');
const rowCount    = document.getElementById('rowCount');

searchInput.addEventListener('input', function () {
    const query = this.value.toLowerCase();
    const rows  = txnTable.querySelectorAll('tbody tr');
    let visible = 0;
    rows.forEach(row => {
        const match = !query || row.textContent.toLowerCase().includes(query);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    rowCount.textContent = visible;
});
</script>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
