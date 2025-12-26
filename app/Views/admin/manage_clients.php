<?php
/**
 * manage_users.php
 * Vue pour la gestion des utilisateurs (création, liste) par l'administrateur.
 * Reçoit $data['title'], $data['users'], $data['roles'], $data['success'], et $data['error'].
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';

// Déterminer le rôle de l'utilisateur pour personnaliser le contenu
$roleName = $data['user_role'] ?? 'Utilisateur'; // Supposons que le contrôleur passe le nom du rôle
$identifiant = $_SESSION['identifiant'] ?? 'N/A';
?>

<h2><?= $data['title'] ?? "Gestion des Clients" ?></h2>

<?php if (isset($data['success'])): ?>
    <div class="alert-success">
        <strong>Succès!</strong> <?= htmlspecialchars($data['success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
    <div class="alert-error">
        <strong>Échec!</strong> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

        <div class="card client-card">
            <p>Ouvrir de nouveaux comptes et gérer le KYC.</p>
            <a href="<?= BASE_URL ?>?controller=Client&action=nouveauClient" class="btn-action">Créer Nouveau Client</a>
        </div>
<hr>

<h3>Liste des Utilisateurs du Système</h3>
<table class="user-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Date de naissance</th>
            <th>Téléphone </th>
            <th>Email</th>
            <th>Numéro d'identité</th>
            <th>D'adresse</th>
            <th>Solde</th>
            <th>Numéro de compte</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($data['users'])): ?>
            <?php foreach ($data['users'] as $user): ?>
                <tr>
                    <!-- , , ,  , , , ,  -->
                    <td><?= htmlspecialchars($user['client_id']) ?></td>
                    <td><?= htmlspecialchars($user['nom']) ?></td>
                    <td><?= htmlspecialchars($user['prenom']) ?></td>
                    <td><?= date('Y-m-d', strtotime($user['date_naissance'])) ?></td>
                    <td><?= htmlspecialchars($user['telephone']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['numero_identite']) ?></td>
                    <td><?= htmlspecialchars($user['adresse']) ?></td>
                    <td><?= htmlspecialchars($user['solde']) ?></td>
                    <td><?= htmlspecialchars($user['numero_compte']) ?></td>


                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7">Aucun utilisateur trouvé.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<style>
/* Styles spécifiques */
.card { border: 1px solid #ddd; padding: 20px; border-radius: 6px; margin-bottom: 20px; }
.create-user-card { background-color: #e6f7ff; border-left: 5px solid #007bff; }

.form-group-half {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 10px;
}

.btn-primary-admin { 
    width: 100%;
    padding: 10px; 
    background-color: #007bff; 
    color: white; 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
    margin-top: 10px;
}
.btn-primary-admin:hover { background-color: #0056b3; }

.user-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
.user-table th, .user-table td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.9em; }
.user-table th { background-color: #f2f2f2; }

.status-active { color: green; font-weight: bold; }
.status-inactive { color: red; }
.action-link { font-size: 0.85em; }

/* Styles généraux réutilisés */
.form-group { margin-bottom: 15px; }
.form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }


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
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>