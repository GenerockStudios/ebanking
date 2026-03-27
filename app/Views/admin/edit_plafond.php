<?php
/**
 * edit_plafond.php — Formulaire d'édition des plafonds d'un compte
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<style>
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 12px; }
.page-header h2 { margin: 0; color: #042e5a; font-size: 1.4rem; font-weight: 700; border: none; padding: 0; }
.btn-action {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 6px; border: none;
    cursor: pointer; font-weight: 600; font-size: 14px;
    text-decoration: none; transition: 0.2s ease;
}
.btn-navy { background: #042e5a; color: #fff; } .btn-navy:hover { background: #021d3a; }
.btn-gray { background: #6c757d; color: #fff; } .btn-gray:hover { background: #545b62; }

.form-container { max-width: 640px; margin: 0 auto; }
.client-card {
    background: linear-gradient(135deg, #042e5a, #0a4a8a);
    color: #fff; border-radius: 12px; padding: 24px 28px;
    margin-bottom: 24px;
    display: flex; justify-content: space-between; align-items: center;
}
.client-card .client-name { font-size: 20px; font-weight: 700; }
.client-card .account-num { font-size: 13px; opacity: .75; margin-top: 4px; }
.client-card .solde-badge {
    background: rgba(255,255,255,.15); border-radius: 8px;
    padding: 10px 18px; text-align: center;
}
.client-card .solde-badge .val { font-size: 22px; font-weight: 800; }
.client-card .solde-badge .lbl { font-size: 11px; opacity: .7; }

.form-card {
    background: #fff; border-radius: 12px; padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,.07); border: 1px solid #f0f0f0;
}
.form-card h3 { margin: 0 0 24px; font-size: 15px; color: #042e5a; font-weight: 700; }
.plafond-field { margin-bottom: 22px; }
.plafond-field label {
    display: block; font-size: 12px; font-weight: 600;
    color: #555; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px;
}
.field-icon-wrapper { position: relative; }
.field-icon-wrapper .icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #8792a2; pointer-events: none;
}
.plafond-field input[type=number] {
    width: 100%; box-sizing: border-box;
    padding: 13px 14px 13px 44px;
    border: 1.5px solid #e3e8ee; border-radius: 8px;
    font-size: 16px; color: #1a1f36;
    transition: border-color 0.2s ease; outline: none;
}
.plafond-field input[type=number]:focus { border-color: #042e5a; }
.field-hint { font-size: 11px; color: #aaa; margin-top: 5px; }
.period-badge {
    display: inline-block; background: #e8eeff; color: #042e5a;
    border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 600;
    margin-left: 8px; vertical-align: middle;
}
.divider { border: none; border-top: 1px solid #f0f0f0; margin: 20px 0; }
.btn-submit {
    width: 100%; background: #042e5a; color: #fff; border: none;
    border-radius: 8px; padding: 15px; font-size: 16px; font-weight: 700;
    cursor: pointer; transition: 0.2s ease;
    display: flex; align-items: center; justify-content: center; gap: 10px;
}
.btn-submit:hover { background: #021d3a; transform: translateY(-1px); }
.warning-box {
    background: #fff8e1; border-left: 4px solid #ffc107;
    border-radius: 0 6px 6px 0; padding: 12px 16px;
    font-size: 12px; color: #856404; margin-bottom: 20px;
}
</style>

<div class="page-header">
    <h2>
        <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">shield_lock</span>
        Paramétrage des Plafonds
    </h2>
    <div style="display:flex;gap:10px;">
        <a href="<?= BASE_URL ?>?controller=Admin&action=contratPlafond&compte_id=<?= $data['plafond']['compte_id'] ?>"
           class="btn-action btn-navy" style="background:#28a745;">
            <span class="material-symbols-rounded">description</span> Générer Contrat
        </a>
        <a href="<?= BASE_URL ?>?controller=Admin&action=managePlafonds" class="btn-action btn-gray">
            <span class="material-symbols-rounded">arrow_back</span> Retour
        </a>
    </div>
</div>

<div class="form-container">
    <!-- Client info -->
    <div class="client-card">
        <div>
            <div class="client-name">
                <?= strtoupper(htmlspecialchars($data['plafond']['nom'])) ?>
                <?= htmlspecialchars($data['plafond']['prenom']) ?>
            </div>
            <div class="account-num">N° <?= htmlspecialchars($data['plafond']['numero_compte']) ?></div>
        </div>
        <div class="solde-badge">
            <div class="val"><?= number_format($data['plafond']['solde'] ?? 0, 0, ',', ' ') ?></div>
            <div class="lbl">Solde FCFA</div>
        </div>
    </div>

    <!-- Formulaire -->
    <div class="form-card">
        <h3>
            <span class="material-symbols-rounded" style="vertical-align:middle;font-size:18px;">edit</span>
            Modifier les Plafonds Autorisés
        </h3>

        <div class="warning-box">
            <strong>Attention :</strong> Toute modification de plafond est tracée dans le journal d'audit.
            Générez le contrat de déplafonnement avant application.
        </div>

        <form method="POST" action="<?= BASE_URL ?>?controller=Admin&action=updatePlafond">
            <input type="hidden" name="compte_id" value="<?= $data['plafond']['compte_id'] ?>">

            <div class="plafond-field">
                <label for="plafond_retrait">Plafond Retrait <span class="period-badge">Par Jour</span></label>
                <div class="field-icon-wrapper">
                    <span class="icon material-symbols-rounded">payments</span>
                    <input type="number" id="plafond_retrait" name="plafond_retrait_journalier"
                           min="0" step="500"
                           value="<?= htmlspecialchars($data['plafond']['plafond_retrait_journalier'] ?? 0) ?>"
                           required>
                </div>
                <div class="field-hint">Montant max de retrait autorisé par jour calendaire (FCFA)</div>
            </div>

            <div class="plafond-field">
                <label for="plafond_depot">Plafond Dépôt <span class="period-badge">Par Jour</span></label>
                <div class="field-icon-wrapper">
                    <span class="icon material-symbols-rounded">account_balance_wallet</span>
                    <input type="number" id="plafond_depot" name="plafond_depot_journalier"
                           min="0" step="500"
                           value="<?= htmlspecialchars($data['plafond']['plafond_depot_journalier'] ?? 0) ?>"
                           required>
                </div>
                <div class="field-hint">Montant max de dépôt autorisé par jour calendaire (FCFA)</div>
            </div>

            <div class="plafond-field">
                <label for="plafond_transfert">Plafond Transfert <span class="period-badge">Par Mois</span></label>
                <div class="field-icon-wrapper">
                    <span class="icon material-symbols-rounded">sync_alt</span>
                    <input type="number" id="plafond_transfert" name="plafond_transfert_mensuel"
                           min="0" step="1000"
                           value="<?= htmlspecialchars($data['plafond']['plafond_transfert_mensuel'] ?? 0) ?>"
                           required>
                </div>
                <div class="field-hint">Montant max de transferts cumulés par mois (FCFA)</div>
            </div>

            <hr class="divider">
            <button type="submit" class="btn-submit">
                <span class="material-symbols-rounded">save</span>
                Enregistrer les Nouveaux Plafonds
            </button>
        </form>
    </div>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
