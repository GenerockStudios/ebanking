<?php
/**
 * cloture_journee.php
 * Vue pour la réconciliation et la clôture de la journée comptable.
 * Reçoit $data['title'], $data['reconciliation'], $data['success'], et $data['error'].
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';

// Récupérer les données de réconciliation
$reconciliation = $data['reconciliation'] ?? null;
?>

<h2><?= $data['title'] ?? "Clôture de Journée" ?></h2>

<?php if (isset($data['success'])): ?>
    <div class="alert-success">
        <strong>Opération réussie!</strong> <?= htmlspecialchars($data['success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
    <div class="alert-error">
        <strong>Échec!</strong> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

<h3>1. Bilan des Opérations du Jour (<?= date('Y-m-d') ?>)</h3>

<?php if ($reconciliation): ?>
    <table class="reconciliation-table">
        <thead>
            <tr>
                <th>Indicateur</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Total des Dépôts du Jour</td>
                <td class="amount total-depot"><?= number_format((float)$reconciliation['total_depots'], 2, ',', ' ') ?></td>
            </tr>
            <tr>
                <td>Total des Retraits du Jour</td>
                <td class="amount total-retrait"><?= number_format((float)$reconciliation['total_retraits'], 2, ',', ' ') ?></td>
            </tr>
            <tr>
                <td>**Flux Net de Trésorerie**</td>
                <?php 
                    $netFlow = (float)$reconciliation['total_depots'] - (float)$reconciliation['total_retraits'];
                    $flowClass = $netFlow >= 0 ? 'flow-positive' : 'flow-negative';
                ?>
                <td class="amount <?= $flowClass ?>">**<?= number_format($netFlow, 2, ',', ' ') ?>**</td>
            </tr>
            <tr>
                <td>Nombre Total de Transactions</td>
                <td class="amount"><?= (int)$reconciliation['total_txns'] ?></td>
            </tr>
        </tbody>
    </table>

    <div class="cloture-action">
        <h3>2. Validation et Clôture</h3>
        <p>Veuillez confirmer que tous les totaux ont été réconciliés avant de procéder à la clôture de la journée comptable.</p>
        
        <form method="POST" action="<?= BASE_URL ?>?controller=Rapport&action=clotureJournee">
            <input type="hidden" name="action" value="cloturer">
            <button type="submit" class="btn-cloture" 
                    onclick="return confirm('Êtes-vous SÛR de vouloir procéder à la CLÔTURE DE JOURNÉE ? Cette action est irréversible.')">
                Exécuter la Clôture de Journée
            </button>
        </form>
    </div>

<?php else: ?>
    <div class="alert-error">
        Impossible de récupérer les données de réconciliation. Vérifiez la connexion à la base de données ou les logs.
    </div>
<?php endif; ?>

<style>
/* CSS spécifique au tableau de réconciliation */
.reconciliation-table { width: 50%; border-collapse: collapse; margin-top: 15px; }
.reconciliation-table th, .reconciliation-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
.reconciliation-table th { background-color: #e6e6fa; }
.amount { text-align: right; font-weight: bold; }

.total-depot { color: #28a745; }
.total-retrait { color: #dc3545; }
.flow-positive { color: #0056b3; font-size: 1.1em; }
.flow-negative { color: #dc3545; font-size: 1.1em; }

.cloture-action { margin-top: 40px; padding: 20px; border: 2px solid #ffc107; border-radius: 8px; background-color: #fff9e6; }
.btn-cloture {
    padding: 12px 25px;
    background-color: #ffc107; /* Orange pour l'alerte/action critique */
    color: #333;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1.1em;
    font-weight: bold;
    margin-top: 15px;
    width: 100%;
}
.btn-cloture:hover { background-color: #e0a800; }
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>