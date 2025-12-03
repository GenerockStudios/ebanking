<?php
/**
 * transactions.php
 * Vue pour afficher le rapport des transactions entre deux dates.
 * Reçoit $data['title'], $data['transactions'], $data['date_debut'], $data['date_fin'], et $data['error'].
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= $data['title'] ?? "Rapport de Transactions" ?></h2>

<form method="GET" action="<?= BASE_URL ?>?controller=Rapport&action=rapportTransactions" class="filter-form">
    <input type="hidden" name="controller" value="Rapport">
    <input type="hidden" name="action" value="rapportTransactions">
    
    <div class="form-group-inline">
        <label for="date_debut">Date de Début :</label>
        <input type="date" id="date_debut" name="date_debut" class="form-control" required 
               value="<?= htmlspecialchars($data['date_debut'] ?? '') ?>">
    </div>
    
    <div class="form-group-inline">
        <label for="date_fin">Date de Fin :</label>
        <input type="date" id="date_fin" name="date_fin" class="form-control" required 
               value="<?= htmlspecialchars($data['date_fin'] ?? '') ?>">
    </div>
    
    <button type="submit" class="btn-filter">Filtrer</button>
</form>

<hr>

<?php if (isset($data['error'])): ?>
    <div class="alert-error">
        <strong>Erreur de Rapport!</strong> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

<h3>Transactions du <?= htmlspecialchars($data['date_debut'] ?? '') ?> au <?= htmlspecialchars($data['date_fin'] ?? '') ?></h3>

<?php if (empty($data['transactions'])): ?>
    <p>Aucune transaction trouvée pour la période sélectionnée.</p>
<?php else: ?>
    <table class="transaction-table">
        <thead>
            <tr>
                <th>Réf. Externe</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Compte Source</th>
                <th>Compte Destination</th>
                <th>Horodatage</th>
                <th>Caissier</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $totalMontant = 0;
            foreach ($data['transactions'] as $txn): 
                $totalMontant += $txn['montant'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($txn['reference_externe']) ?></td>
                    <td><span class="type-<?= strtolower($txn['type_transaction']) ?>"><?= htmlspecialchars($txn['type_transaction']) ?></span></td>
                    <td class="amount"><?= number_format($txn['montant'], 2, ',', ' ') ?></td>
                    <td><?= htmlspecialchars($txn['source'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($txn['destination'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($txn['horodatage_transaction']) ?></td>
                    <td><?= htmlspecialchars($txn['caissier']) ?></td>
                    <td><?= htmlspecialchars($txn['statut']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total des montants bruts</td>
                <td class="amount total-sum"><?= number_format($totalMontant, 2, ',', ' ') ?></td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>
<?php endif; ?>

<style>
/* CSS spécifique au rapport */
.filter-form { display: flex; gap: 20px; align-items: flex-end; margin-bottom: 20px; }
.form-group-inline { display: flex; flex-direction: column; }
.form-control { padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
.btn-filter { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
.btn-filter:hover { background-color: #0056b3; }

.transaction-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.transaction-table th, .transaction-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
.transaction-table th { background-color: #f2f2f2; }
.amount { text-align: right; font-weight: bold; }
.total-sum { background-color: #e9ecef; }

/* Codes couleurs pour les types de transaction */
.type-depot { color: green; font-weight: bold; }
.type-retrait { color: red; font-weight: bold; }
.type-transfert_int { color: orange; font-weight: bold; }
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>