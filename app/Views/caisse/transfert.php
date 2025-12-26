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
<div class="flex-justify-center">
    <div class="container">
        <div class="card">
            <div class="header">
                <div style="margin-bottom: 20px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#635BFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 3l4 4-4 4M8 21l-4-4 4-4M20 7H4M4 17h16" />
                    </svg>
                </div>
                <h1>Nouveau Transfert</h1>
                <p>Veuillez renseigner les détails du virement</p>
            </div>

            <form method="POST" action="<?= BASE_URL ?>?controller=Caisse&action=transfert">

                <div class="input-group">
                    <input type="text" id="compte_source" name="compte_source" required
                        placeholder=" " value="<?= htmlspecialchars($_POST['compte_source'] ?? '') ?>">
                    <label for="compte_source">Numéro de Compte Source (Débit)</label>
                </div>

                <div class="input-group">
                    <input type="text" id="compte_destination" name="compte_destination" required
                        placeholder=" " value="<?= htmlspecialchars($_POST['compte_destination'] ?? '') ?>">
                    <label for="compte_destination">Numéro de Compte Destination (Crédit)</label>
                </div>

                <div class="input-group">
                    <input type="number" id="montant" name="montant" required min="0.01" step="0.01" placeholder=" ">
                    <label for="montant">Montant du Transfert (Devise locale)</label>
                </div>

                <input type="hidden" name="utilisateur_id" value="<?= $_SESSION['user_id'] ?>">

                <button type="submit" class="submit-btn">
                    Confirmer le Transfert
                </button>
            </form>
        </div>
    </div>


</div>
<style>
    .flex-justify-center {
        display: flex;
        justify-content: center;
    }

    .container {
        width: 100%;
        max-width: 450px;
    }

    .card {
        background: #ffffff;
        border-radius: 12px;
        padding: 40px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02), 0 8px 16px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .header {
        text-align: center;
        margin-bottom: 32px;
    }

    .header h1 {
        color: #1a1f36;
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .header p {
        color: #8792a2;
        font-size: 14px;
    }

    /* Floating Labels Logic */
    .input-group {
        position: relative;
        margin-bottom: 24px;
    }

    .input-group input {
        width: 100%;
        background: #ffffff;
        border: 1px solid #e3e8ee;
        border-radius: 6px;
        padding: 16px 14px 8px 14px;
        color: #1a1f36;
        font-size: 16px;
        outline: none;
        transition: all 0.2s ease;
    }

    .input-group input:focus {
        border-color: #635BFF;
    }

    .input-group label {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #8792a2;
        font-size: 16px;
        pointer-events: none;
        transition: all 0.2s ease;
        background: #ffffff;
        padding: 0 4px;
    }

    /* Animation du label qui monte */
    .input-group input:focus+label,
    .input-group input:not(:placeholder-shown)+label {
        top: 0;
        font-size: 12px;
        font-weight: 500;
        color: #635BFF;
        transform: translateY(-50%);
    }

    .submit-btn {
        width: 100%;
        background: #635BFF;
        /* On garde le bleu pro, ou mettez #ffc107 pour le jaune */
        color: #ffffff;
        border: none;
        border-radius: 6px;
        padding: 14px 20px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        transition: all 0.2s ease;
        box-shadow: 0 4px 12px rgba(99, 91, 255, 0.2);
    }

    .submit-btn:hover {
        background: #4c44d4;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 91, 255, 0.4);
    }
</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>