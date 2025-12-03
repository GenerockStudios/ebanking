<?php
/**
 * retrait.php
 * Vue pour l'opération de retrait.
 * Reçoit $data['title'], $data['success'], et $data['error'].
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';
?>

<h2>Opération de Retrait d'Espèces</h2>

<?php if (isset($data['success'])): ?>
    <div class="alert-success">
        <strong>Succès!</strong> <?= htmlspecialchars($data['success']) ?>
        <?php if (isset($data['new_balance'])): ?>
            <p>Nouveau solde du compte : **<?= number_format($data['new_balance'], 2, ',', ' ') ?>**</p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
    <div class="alert-error">
        <strong>Erreur!</strong> <?= htmlspecialchars($data['error']) ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= BASE_URL ?>?controller=Caisse&action=retrait">
    
    <div class="form-group">
        <label for="numero_compte">Numéro de Compte Source :</label>
        <input type="text" id="numero_compte" name="numero_compte" class="form-control" required 
               placeholder="Ex: 123456789012" 
               value="<?= htmlspecialchars($_POST['numero_compte'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label for="montant">Montant du Retrait :</label>
        <input type="number" id="montant" name="montant" class="form-control" required min="0.01" step="0.01"
               placeholder="Montant en devise locale">
    </div>
    
    <div class="form-group">
        <input type="hidden" name="utilisateur_id" value="<?= $_SESSION['user_id'] ?>">
    </div>
    
    <button type="submit" class="btn-retrait">Confirmer le Retrait</button>
</form>

<style>
/* CSS spécifique au bouton de Retrait */
.btn-retrait { 
    padding: 10px 20px; 
    background-color: #dc3545; /* Rouge pour les retraits (opération de débit) */ 
    color: white; 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
}
.btn-retrait:hover { background-color: #c82333; }

/* Réutilisation des styles généraux pour .form-group et .form-control */
.form-group { margin-bottom: 15px; }
.form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>