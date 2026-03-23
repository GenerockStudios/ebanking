<?php
/**
 * recepisse.php - Recu de transaction optimise pour impression thermique 80mm.
 * Charge par CaisseController::recepisse() via les donnees de $_SESSION['last_receipt'].
 */
// $receipt est passee depuis le controleur
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Recu de Transaction</title>
    <style>
        @media print {
            body { width: 76mm; margin: 0 auto; }
            .no-print { display: none !important; }
            @page { margin: 2mm; size: 80mm auto; }
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 11px;
            color: #000;
            background: #fff;
            width: 76mm;
            margin: 10px auto;
        }
        .receipt { padding: 4px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        .divider-solid { border-top: 1px solid #000; margin: 6px 0; }
        .bank-name { font-size: 16px; font-weight: bold; text-align: center; letter-spacing: 1px; margin-bottom: 2px; }
        .bank-sub { font-size: 9px; text-align: center; color: #444; margin-bottom: 6px; }
        .operation-type {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            background: #000;
            color: #fff;
            padding: 4px;
            margin: 6px 0;
            letter-spacing: 2px;
        }
        .row { display: flex; justify-content: space-between; margin: 3px 0; }
        .row .lbl { color: #555; }
        .row .val { font-weight: bold; text-align: right; max-width: 55%; }
        .montant-big {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin: 8px 0;
            border: 2px solid #000;
            padding: 6px;
        }
        .footer-msg { font-size: 9px; text-align: center; color: #555; margin-top: 8px; }
        .ref-code { font-size: 9px; text-align: center; word-break: break-all; margin: 4px 0; }
        .btn-actions { text-align: center; margin: 20px 0; }
        .btn-actions button { padding: 10px 24px; margin: 0 6px; font-size: 14px; cursor: pointer; border: none; border-radius: 6px; }
        .btn-print-btn { background: #042e5a; color: #fff; }
        .btn-close-btn { background: #6c757d; color: #fff; }
    </style>
</head>
<body>

<div class="receipt">
    <!-- En-tete -->
    <div class="bank-name">EBANKING</div>
    <div class="bank-sub">Guichet Automatique de Caisse</div>
    <div class="divider-solid"></div>

    <!-- Type d'operation -->
    <div class="operation-type"><?= htmlspecialchars($receipt['operation'] ?? 'OPERATION') ?></div>

    <!-- Montant mis en valeur -->
    <div class="montant-big"><?= htmlspecialchars($receipt['montant'] ?? '0,00') ?> FCFA</div>

    <div class="divider"></div>

    <!-- Details -->
    <div class="row">
        <span class="lbl">Compte</span>
        <span class="val"><?= htmlspecialchars($receipt['numero_compte'] ?? '') ?></span>
    </div>
    <div class="row">
        <span class="lbl">Titulaire</span>
        <span class="val"><?= htmlspecialchars($receipt['nom_client'] ?? '') ?></span>
    </div>
    <?php if (!empty($receipt['compte_dest'])): ?>
    <div class="row">
        <span class="lbl">Vers compte</span>
        <span class="val"><?= htmlspecialchars($receipt['compte_dest']) ?></span>
    </div>
    <div class="row">
        <span class="lbl">Beneficiaire</span>
        <span class="val"><?= htmlspecialchars($receipt['nom_dest'] ?? '') ?></span>
    </div>
    <?php endif; ?>
    <div class="row">
        <span class="lbl">Nouveau solde</span>
        <span class="val bold"><?= htmlspecialchars($receipt['nouveau_solde'] ?? '') ?> FCFA</span>
    </div>

    <div class="divider"></div>

    <div class="row">
        <span class="lbl">Date / Heure</span>
        <span class="val"><?= htmlspecialchars($receipt['horodatage'] ?? '') ?></span>
    </div>
    <div class="row">
        <span class="lbl">Caissier</span>
        <span class="val"><?= htmlspecialchars($receipt['caissier'] ?? '') ?></span>
    </div>

    <div class="divider"></div>

    <div class="ref-code">REF: <?= htmlspecialchars($receipt['reference'] ?? '') ?></div>

    <div class="divider-solid"></div>

    <div class="footer-msg">
        Conservez ce recu comme preuve de votre transaction.<br>
        Merci de votre confiance.
    </div>

    <div class="footer-msg" style="margin-top:6px; font-size:8px;">
        *** DOCUMENT OFFICIEL - EBANKING ***
    </div>
</div>

<!-- Boutons d'action (masques a l'impression) -->
<div class="btn-actions no-print">
    <button class="btn-print-btn" onclick="window.print()">Imprimer</button>
    <button class="btn-close-btn" onclick="window.close()">Fermer</button>
</div>

<script>
// Auto-ouverture de la boite d'impression apres 500ms
setTimeout(function() { window.print(); }, 500);
</script>
</body>
</html>
