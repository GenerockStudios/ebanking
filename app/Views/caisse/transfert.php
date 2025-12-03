<?php
/**
 * transfert.php
 * Vue pour l'opération de transfert interne.
 * Reçoit $data['title'], $data['success'], $data['error'] et $data['new_balance'] (solde source).
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';
?>

<h2>Opération de Transfert Interne</h2>

<?php if (isset($data['success'])): ?>
    <div class="alert-success">
        <strong>Succès!</strong> <?= htmlspecialchars($data['success']) ?>
        <?php if (isset($data['new_balance'])): ?>
            <p>Nouveau solde du compte source : **<?= number_format($data['new_balance'], 2, ',', ' ') ?>**</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
    <div class="alert-error">
        <strong>Erreur!</strong> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>?controller=Caisse&action=transfert">
    
    <div class="form-group">
        <label for="compte_source">Numéro de Compte Source (Débit) :</label>
        <input type="text" id="compte_source" name="compte_source" class="form-control" required 
               placeholder="Compte à débiter" 
               value="<?= htmlspecialchars($_POST['compte_source'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label for="compte_destination">Numéro de Compte Destination (Crédit) :</label>
        <input type="text" id="compte_destination" name="compte_destination" class="form-control" required 
               placeholder="Compte à créditer" 
               value="<?= htmlspecialchars($_POST['compte_destination'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label for="montant">Montant du Transfert :</label>
        <input type="number" id="montant" name="montant" class="form-control" required min="0.01" step="0.01"
               placeholder="Montant en devise locale">
    </div>
    
    <div class="form-group">
        <input type="hidden" name="utilisateur_id" value="<?= $_SESSION['user_id'] ?>">
    </div>
    
    <button type="submit" class="btn-transfert">Confirmer le Transfert</button>
</form>

<style>
/* CSS spécifique au bouton de Transfert */
.btn-transfert { 
    padding: 10px 20px; 
    background-color: #ffc107; /* Jaune/Orange pour les transferts */ 
    color: #333; 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
    font-weight: bold;
}
.btn-transfert:hover { background-color: #e0a800; }

/* Réutilisation des styles généraux */
.form-group { margin-bottom: 15px; }
.form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>