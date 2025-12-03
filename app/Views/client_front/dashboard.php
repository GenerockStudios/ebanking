<?php
/**
 * dashboard.php
 * Tableau de bord client affichant le solde et l'historique des transactions.
 * Reçoit $data['title'], $data['solde'], $data['transactions'].
 */

// Layout minimal pour le client
$numeroCompte = $_SESSION['client_numero_compte'] ?? 'N/A';
$solde = $data['solde'] ?? 'N/A';
$transactions = $data['transactions'] ?? [];
$userName = $data['client_name'] ?? 'Client'; // Devrait être chargé par ClientFrontController

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?? APP_NAME . " - Dashboard" ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f0f4f8; }
        .header { background-color: #007bff; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; }
        .content { padding: 30px; max-width: 1000px; margin: 30px auto; background: white; border-radius: 8px; box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); }
        h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 0; color: #333; }

        /* Solde Card */
        .solde-card { background-color: #e6f7ff; border: 1px solid #b3e0ff; padding: 25px; border-radius: 8px; margin-bottom: 30px; text-align: center; }
        .solde-label { font-size: 1.1em; color: #007bff; }
        .solde-amount { font-size: 2.5em; font-weight: bold; color: #0056b3; margin-top: 5px; }

        /* Transaction Table */
        .transaction-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .transaction-table th, .transaction-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .transaction-table th { background-color: #f2f2f2; }
        .amount-col { text-align: right; font-weight: bold; }
        .flux-debit { color: #dc3545; }
        .flux-credit { color: #28a745; }
        
    </style>
</head>
<body>
    <div class="header">
        <div class="welcome-text">Bienvenue, <?= htmlspecialchars($userName) ?> (Compte: **<?= htmlspecialchars($numeroCompte) ?>**)</div>
        <nav>
            <a href="<?= BASE_URL ?>?controller=ClientFront&action=logout">Déconnexion</a>
        </nav>
    </div>
    
    <div class="content">
        <h2>Tableau de Bord de Mon Compte</h2>

        <div class="solde-card">
            <div class="solde-label">Solde Actuel</div>
            <div class="solde-amount">
                <?php if (is_numeric($solde)): ?>
                    <?= number_format($solde, 2, ',', ' ') ?>
                <?php else: ?>
                    Solde non disponible
                <?php endif; ?>
            </div>
        </div>

        <h3>Historique des Transactions Récentes</h3>

        <?php if (empty($transactions)): ?>
            <p>Aucune transaction récente à afficher.</p>
        <?php else: ?>
            <table class="transaction-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type Opération</th>
                        <th>Référence</th>
                        <th class="amount-col">Montant</th>
                        <th>Sens du Flux</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><?= date('Y-m-d H:i', strtotime($txn['horodatage_transaction'])) ?></td>
                            <td><?= htmlspecialchars($txn['type_transaction']) ?></td>
                            <td><?= htmlspecialchars($txn['reference_externe']) ?></td>
                            <td class="amount-col"><?= number_format($txn['montant'], 2, ',', ' ') ?></td>
                            <td class="<?= ($txn['sens_flux'] === 'Débit') ? 'flux-debit' : 'flux-credit' ?>">
                                <?= htmlspecialchars($txn['sens_flux']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    </div>
</body>
</html>