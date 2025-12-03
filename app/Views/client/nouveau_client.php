<?php
/**
 * nouveau_client.php
 * Vue pour la création d'un nouveau client et l'ouverture d'un compte.
 * Reçoit $data['title'], $data['success'], $data['error'] et $data['account_types'].
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= $data['title'] ?? "Nouveau Client" ?></h2>

<?php if (isset($data['success'])): ?>
    <div class="alert-success">
        <strong>Client Créé!</strong> <?= htmlspecialchars($data['success']) ?>
    </div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
    <div class="alert-error">
        <strong>Échec!</strong> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>?controller=Client&action=nouveauClient">
    
    <h3>1. Informations Personnelles (KYC)</h3>
    
    <div class="form-group-grid">
        <div class="form-group">
            <label for="nom">Nom :</label>
            <input type="text" id="nom" name="nom" class="form-control" required 
                   value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="prenom">Prénom(s) :</label>
            <input type="text" id="prenom" name="prenom" class="form-control" required 
                   value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
        </div>
    </div>

    <div class="form-group-grid">
        <div class="form-group">
            <label for="date_naissance">Date de Naissance :</label>
            <input type="date" id="date_naissance" name="date_naissance" class="form-control" required 
                   value="<?= htmlspecialchars($_POST['date_naissance'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="numero_identite">N° Pièce d'Identité (CNI/Passeport) :</label>
            <input type="text" id="numero_identite" name="numero_identite" class="form-control" required 
                   value="<?= htmlspecialchars($_POST['numero_identite'] ?? '') ?>">
        </div>
    </div>
    
    <h3>2. Coordonnées</h3>

    <div class="form-group">
        <label for="adresse">Adresse Complète :</label>
        <input type="text" id="adresse" name="adresse" class="form-control" 
               value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
    </div>

    <div class="form-group-grid">
        <div class="form-group">
            <label for="telephone">Téléphone :</label>
            <input type="tel" id="telephone" name="telephone" class="form-control" 
                   value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label for="email">Email :</label>
            <input type="email" id="email" name="email" class="form-control" 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
    </div>
    
    <h3>3. Ouverture de Compte Initial</h3>
    
    <div class="form-group">
        <label for="type_compte_id">Type de Compte à Ouvrir :</label>
        <select id="type_compte_id" name="type_compte_id" class="form-control" required>
            <option value="">-- Choisir un type --</option>
            <?php 
            // Boucler sur les types de comptes fournis par le contrôleur
            foreach ($data['account_types'] ?? [] as $id => $name): 
                // Pour maintenir la sélection en cas d'erreur de formulaire
                $selected = (isset($_POST['type_compte_id']) && (int)$_POST['type_compte_id'] === $id) ? 'selected' : '';
            ?>
                <option value="<?= $id ?>" <?= $selected ?>><?= htmlspecialchars($name) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group" style="margin-top: 30px;">
        <input type="hidden" name="utilisateur_id" value="<?= $_SESSION['user_id'] ?>">
        <button type="submit" class="btn-create-client">Créer le Client & Ouvrir le Compte</button>
    </div>
</form>

<style>
/* CSS spécifique au formulaire */
.form-group-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 10px;
}
.btn-create-client { 
    width: 100%;
    padding: 12px; 
    background-color: #007bff; /* Bleu standard */
    color: white; 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
    font-size: 1.1em;
}
.btn-create-client:hover { background-color: #0056b3; }

/* Réutilisation des styles généraux pour .form-group et .form-control */
.form-group { margin-bottom: 15px; }
.form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>