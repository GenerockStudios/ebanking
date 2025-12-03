<?php
/**
 * manage_users.php
 * Vue pour la gestion des utilisateurs (création, liste) par l'administrateur.
 * Reçoit $data['title'], $data['users'], $data['roles'], $data['success'], et $data['error'].
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= $data['title'] ?? "Gestion des Utilisateurs" ?></h2>

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

<div class="card create-user-card">
    <h3>Créer un Nouvel Utilisateur</h3>
    <form method="POST" action="<?= BASE_URL ?>?controller=Admin&action=manageUsers">
        <input type="hidden" name="action" value="create">
        
        <div class="form-group-half">
            <div class="form-group">
                <label for="identifiant">Identifiant (Login) :</label>
                <input type="text" id="identifiant" name="identifiant" class="form-control" required 
                       value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de Passe Initial :</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required minlength="8">
            </div>
        </div>

        <div class="form-group-half">
            <div class="form-group">
                <label for="nom_complet">Nom Complet :</label>
                <input type="text" id="nom_complet" name="nom_complet" class="form-control" required 
                       value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="role_id">Rôle :</label>
                <select id="role_id" name="role_id" class="form-control" required>
                    <option value="">-- Choisir un rôle --</option>
                    <?php 
                    // Les rôles sont supposés être passés comme un tableau associatif [id => nom] dans $data['roles']
                    foreach ($data['roles'] ?? [] as $id => $name): 
                        $selected = (isset($_POST['role_id']) && (int)$_POST['role_id'] === $id) ? 'selected' : '';
                    ?>
                        <option value="<?= $id ?>" <?= $selected ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <button type="submit" class="btn-primary-admin">Créer l'Utilisateur</button>
    </form>
</div>

<hr>

<h3>Liste des Utilisateurs du Système</h3>
<table class="user-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Identifiant</th>
            <th>Nom Complet</th>
            <th>Rôle (ID)</th>
            <th>Statut</th>
            <th>Créé le</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($data['users'])): ?>
            <?php foreach ($data['users'] as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['utilisateur_id']) ?></td>
                    <td><?= htmlspecialchars($user['identifiant']) ?></td>
                    <td><?= htmlspecialchars($user['nom_complet']) ?></td>
                    <td><?= htmlspecialchars($data['roles'][$user['role_id']] ?? 'Inconnu') ?> (<?= htmlspecialchars($user['role_id']) ?>)</td>
                    <td><span class="status-<?= $user['est_actif'] ? 'active' : 'inactive' ?>">
                        <?= $user['est_actif'] ? 'Actif' : 'Inactif' ?>
                    </span></td>
                    <td><?= date('Y-m-d', strtotime($user['date_creation'])) ?></td>
                    <td>
                        <a href="#" class="action-link" title="Désactiver">Désactiver</a> | 
                        <a href="#" class="action-link" title="Réinitialiser Mot de Passe">Reset MDP</a>
                    </td>
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
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>