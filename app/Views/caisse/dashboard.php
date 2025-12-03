<?php
/**
 * dashboard.php
 * Tableau de bord principal pour les utilisateurs connectés (Caissier/Superviseur/Admin).
 * Cette vue est chargée par CaisseController->dashboard().
 * Reçoit $data['title'] et $data['user_role'].
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';

// Déterminer le rôle de l'utilisateur pour personnaliser le contenu
$roleName = $data['user_role'] ?? 'Utilisateur'; // Supposons que le contrôleur passe le nom du rôle
$identifiant = $_SESSION['identifiant'] ?? 'N/A';
?>

<h2>Tableau de Bord Principal</h2>
<p>
    Bienvenue, **<?= htmlspecialchars($roleName) ?>** (<?= htmlspecialchars($identifiant) ?>).
    Vous êtes connecté à l'interface de gestion du **<?= APP_NAME ?>**.
</p>

<hr>

<div class="dashboard-grid">
    
    <?php if (in_array($roleName, ['Caissier', 'Superviseur', 'Admin'])): ?>
        <div class="card operation-card">
            <h3>Opérations de Caisse</h3>
            <ul>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=depot">💵 Dépôt d'Espèces</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=retrait">➖ Retrait d'Espèces</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=transfert">🔁 Transfert Interne</a></li>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (in_array($roleName, ['Caissier', 'Admin'])): ?>
        <div class="card client-card">
            <h3>Gestion Client</h3>
            <p>Ouvrir de nouveaux comptes et gérer le KYC.</p>
            <a href="<?= BASE_URL ?>?controller=Client&action=nouveauClient" class="btn-action">Créer Nouveau Client</a>
        </div>
    <?php endif; ?>

    <?php if (in_array($roleName, ['Superviseur', 'Admin'])): ?>
        <div class="card report-card">
            <h3>Supervision & Audit</h3>
            <ul>
                <li><a href="<?= BASE_URL ?>?controller=Rapport&action=rapportTransactions">📊 Rapport Transactions</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Rapport&action=clotureJournee">🔒 Clôture de Journée</a></li>
                <?php if ($roleName === 'Admin'): ?>
                    <li><a href="<?= BASE_URL ?>?controller=Admin&action=manageUsers">👑 Gestion Utilisateurs</a></li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
    
</div>

<style>
/* Styles spécifiques pour le tableau de bord */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.card {
    border: 1px solid #ddd;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    background-color: #fff;
}
.card h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    color: #007bff;
}
.btn-action {
    display: block;
    padding: 8px;
    margin-top: 10px;
    background-color: #28a745;
    color: white;
    text-align: center;
    border-radius: 4px;
    text-decoration: none;
}
.btn-action:hover {
    background-color: #1e7e34;
}
.operation-card ul, .report-card ul {
    list-style: none;
    padding: 0;
}
.operation-card li, .report-card li {
    margin-bottom: 8px;
}
.operation-card a, .report-card a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
}
.operation-card a:hover, .report-card a:hover {
    color: #007bff;
}
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>