<?php
/**
 * recu.php - Vue pour l'impression du reçu de transaction.
 * Utilise des styles CSS spécifiques pour une impression propre (sans signatures navigateur).
 */

require_once VIEW_PATH . 'layout/header.php';

$t = $data['transaction'];
$isTransfert = in_array($t->type_transaction, ['TRANSFERT', 'TRANSFERT_INT']);
$isRetrait   = ($t->type_transaction === 'RETRAIT');
$isDepot     = ($t->type_transaction === 'DEPOT');

// Formatage de la date
$dateTrans = date('d/m/Y H:i:s', strtotime($t->date_transaction));
?>

<style>
/* Le reset de l'impression (A4) est géré globalement par responsive-core.css */
/* Style Professionnel du Document Impression */
.document-officiel {
    border: 2px solid #2c3e50;
    padding: 30px;
    border-radius: 8px;
    margin-bottom: 20px;
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    background: #fff;
    position: relative;
    overflow: hidden;
}
.header-banque {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 3px double #2c3e50;
    margin-bottom: 30px;
    padding-bottom: 15px;
}
.titre-document {
    font-size: 24px;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 2px;
}
.infos-banque { text-align: right; font-size: 12px; color: #555; }
.logo-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-size: 100px;
    color: rgba(0,0,0,0.03);
    font-weight: bold;
    z-index: 0;
    white-space: nowrap;
    pointer-events: none;
}
.reçu-body { position: relative; z-index: 1; }
.section-client {
    margin-bottom: 25px;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 6px;
}
.label-val { display: flex; margin-bottom: 8px; }
.label-val .label { font-weight: bold; width: 180px; color: #34495e; }
.label-val .val { flex: 1; }

.montant-box {
    text-align: center;
    padding: 20px;
    background: #ecf0f1;
    border: 1px dashed #7f8c8d;
    margin: 30px 0;
}
.montant-chiffres { font-size: 32px; font-weight: bold; color: #2c3e50; }
.montant-lettres { font-style: italic; font-size: 14px; margin-top: 5px; }

.signature-box { display: flex; justify-content: space-between; margin-top: 70px; }
.signature-box div { border-top: 1px dotted #000; width: 40%; text-align: center; padding-top: 10px; font-style: italic; }

.btn-actions-reçu { text-align: center; margin-top: 30px; }
.btn-print {
    background: #2c3e50;
    color: #fff;
    padding: 12px 25px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: background 0.2s;
}
.btn-print:hover { background: #34495e; }
.btn-retour {
    background: #95a5a6;
    color: #fff;
    padding: 12px 25px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
    margin-left: 10px;
}

/* Style "Ticket Thermique" (optionnel via classe si on veut un format réduit) */
.thermique {
    max-width: 80mm;
    font-size: 12px;
    border: none;
    padding: 10px;
}
.thermique .titre-document { font-size: 18px; }
.thermique .montant-chiffres { font-size: 24px; }
</style>

<div class="no-print" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;">
    <a href="<?= BASE_URL ?>?controller=Caisse&action=dashboard" style="text-decoration:none;color:#666;font-size:14px;display:inline-flex;align-items:center;gap:6px;">
        <span class="material-symbols-rounded" style="font-size:18px;">arrow_back</span> Retour au Tableau de Bord
    </a>
</div>

<div class="document-officiel">
    <div class="logo-watermark"><?= APP_NAME ?></div>
    
    <div class="header-banque">
        <div class="titre-document">REÇU DE TRANSACTION</div>
        <div class="infos-banque">
            <strong><?= APP_NAME ?></strong><br>
            Agence Centrale MSB<br>
            Tél: +33 1 23 45 67 89<br>
            Date: <?= date('d/m/Y') ?>
        </div>
    </div>

    <div class="reçu-body">
        <div class="section-client">
            <div class="label-val">
                <span class="label">Date & Heure :</span>
                <span class="val"><?= $dateTrans ?></span>
            </div>
            <div class="label-val">
                <span class="label">Référence :</span>
                <span class="val"><strong><?= $t->reference_externe ?></strong></span>
            </div>
            <div class="label-val">
                <span class="label">Type d'opération :</span>
                <span class="val"><?= $t->type_transaction ?></span>
            </div>
        </div>

        <div class="section-client">
            <div class="label-val">
                <?php if ($isDepot): ?>
                    <span class="label">Compte Crédité :</span>
                    <span class="val"><?= $t->num_dest ?> (<?= $t->client_dest ?>)</span>
                <?php elseif ($isRetrait): ?>
                    <span class="label">Compte Débité :</span>
                    <span class="val"><?= $t->num_source ?> (<?= $t->client_source ?>)</span>
                <?php elseif ($isTransfert): ?>
                    <span class="label">Source :</span>
                    <span class="val"><?= $t->num_source ?> (<?= $t->client_source ?>)</span>
                <?php endif; ?>
            </div>
            
            <?php if ($isTransfert): ?>
            <div class="label-val">
                <span class="label">Destination :</span>
                <span class="val"><?= $t->num_dest ?> (<?= $t->client_dest ?>)</span>
            </div>
            <?php endif; ?>
        </div>

        <div class="montant-box">
            <div class="montant-chiffres"><?= number_format($t->montant, 2, ',', ' ') ?> FCFA</div>
            <div class="montant-lettres">Somme reçue/payée par le client</div>
        </div>

        <div style="font-size: 13px; color: #555; text-align: center;">
            <?php 
                $soldeAffiche = $isDepot || ($isTransfert && $t->num_dest) ? $t->solde_dest : $t->solde_source; 
            ?>
            Solde actuel du compte : <strong><?= number_format($soldeAffiche, 2, ',', ' ') ?> FCFA</strong>
        </div>

        <div class="signature-box">
            <div>Le Client<br><small>(Signature précédée de la mention "Lu et approuvé")</small></div>
            <div>Le Caissier<br><small>(<?= $t->caissier_nom ?>)</small></div>
        </div>
    </div>
</div>

<div class="btn-actions-reçu no-print">
    <button onclick="window.print()" class="btn-print">
        <span class="material-symbols-rounded">print</span> Lancer l'Impression
    </button>
    <a href="<?= BASE_URL ?>?controller=Caisse&action=dashboard" class="btn-retour">Terminer</a>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
