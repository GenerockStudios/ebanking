<?php
/**
 * retrait.php - Formulaire de retrait avec saisie prédictive AJAX.
 * Design épuré unifié avec transfert.php.
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<?php if (isset($data['success'])): ?>
<div class="alert-success">
    <strong>Succès!</strong> <?= htmlspecialchars($data['success']) ?>
    <?php if (isset($data['new_balance'])): ?>
        &mdash; Nouveau solde : <strong><?= number_format($data['new_balance'], 2, ',', ' ') ?> FCFA</strong>
    <?php endif; ?>
    <?php if (!empty($data['show_receipt'])): ?>
        &nbsp;<a href="<?= BASE_URL ?>?controller=Caisse&action=recu&id=<?= $data['transaction_id'] ?>" target="_blank" class="btn-receipt">Imprimer le reçu officiel</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (isset($data['error'])): ?>
<div class="alert-error"><strong>Erreur!</strong> <?= htmlspecialchars($data['error']) ?></div>
<?php endif; ?>

<div class="flex-justify-center">
    <div class="op-container">

        <div class="card">
            <div class="card-header">
                <div style="margin-bottom:16px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <polyline points="2,10 22,10"/>
                        <line x1="10" y1="14" x2="14" y2="14"/>
                    </svg>
                </div>
                <h1>Nouveau Retrait</h1>
                <p>Débiter le compte d'un client</p>
            </div>

            <form method="POST" action="<?= BASE_URL ?>?controller=Caisse&action=retrait">
                <div class="input-group">
                    <input type="text" id="numero_compte" name="numero_compte"
                           required placeholder=" " autocomplete="off"
                           value="<?= htmlspecialchars($_POST['numero_compte'] ?? '') ?>">
                    <label for="numero_compte">Numéro de Compte Source</label>
                    <div id="suggestions" class="suggestions-box"></div>
                </div>
                <div id="client-preview" class="client-preview" style="display:none;"></div>
                <div class="input-group">
                    <input type="number" id="montant" name="montant"
                           required min="0.01" step="0.01" placeholder=" ">
                    <label for="montant">Montant du Retrait (FCFA)</label>
                </div>
                <button type="submit" class="submit-btn">Confirmer le Retrait</button>
            </form>
        </div>

        <?php if (!empty($data['derniers_mouvements'])): ?>
        <div class="mvt-card">
            <h3>5 Derniers Mouvements</h3>
            <table class="mini-table">
                <thead><tr><th>Date</th><th>Type</th><th>Sens</th><th>Montant</th></tr></thead>
                <tbody>
                <?php foreach ($data['derniers_mouvements'] as $m): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($m['date_transaction']))) ?></td>
                    <td><?= htmlspecialchars($m['type_transaction']) ?></td>
                    <td><span class="badge-<?= $m['sens'] === 'Crédit' ? 'credit' : 'debit' ?>"><?= htmlspecialchars($m['sens']) ?></span></td>
                    <td class="montant-cell"><?= number_format((float)$m['montant'], 2, ',', ' ') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
.flex-justify-center { display: flex; justify-content: center; }
.op-container { width: 100%; max-width: 480px; }
.card {
    background: #ffffff;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 4px rgba(0,0,0,.02), 0 8px 16px rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.05);
    margin-bottom: 20px;
}
.card-header { text-align: center; margin-bottom: 32px; }
.card-header h1 { color: #1a1f36; font-size: 1.5rem; font-weight: 600; margin-bottom: 8px; }
.card-header p { color: #8792a2; font-size: 14px; }
.input-group { position: relative; margin-bottom: 24px; }
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
    box-sizing: border-box;
}
.input-group input:focus { border-color: #dc3545; }
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
.input-group input:focus + label,
.input-group input:not(:placeholder-shown) + label {
    top: 0;
    font-size: 12px;
    font-weight: 500;
    color: #dc3545;
    transform: translateY(-50%);
}
.submit-btn {
    width: 100%;
    background: #dc3545;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 14px 20px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all 0.2s ease;
    box-shadow: 0 4px 12px rgba(220,53,69,.2);
}
.submit-btn:hover {
    background: #c82333;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,53,69,.4);
}
.btn-receipt {
    display: inline-block;
    padding: 4px 12px;
    background: #007bff;
    color: #fff;
    border-radius: 4px;
    text-decoration: none;
    font-size: 13px;
    margin-left: 8px;
}
.suggestions-box {
    position: absolute;
    left: 0; right: 0; top: 100%;
    background: #fff;
    border: 1px solid #e3e8ee;
    border-top: none;
    border-radius: 0 0 6px 6px;
    z-index: 200;
    max-height: 220px;
    overflow-y: auto;
    box-shadow: 0 4px 10px rgba(0,0,0,.08);
}
.suggestion-item { padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f5f5f5; font-size: 14px; }
.suggestion-item:hover { background: #fff5f5; }
.acc-num { font-weight: 700; color: #042e5a; }
.acc-suspended { color: #dc3545; font-size: 12px; font-weight: 600; }
.client-preview {
    background: #fff5f5;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    padding: 10px 14px;
    margin-bottom: 16px;
    font-size: 14px;
    color: #721c24;
}
.mvt-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.05);
}
.mvt-card h3 { margin: 0 0 14px; font-size: 15px; color: #042e5a; font-weight: 600; }
.mini-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.mini-table th { padding: 8px 10px; text-align: left; font-weight: 600; color: #555; border-bottom: 2px solid #f0f0f0; }
.mini-table td { padding: 7px 10px; border-bottom: 1px solid #f5f5f5; }
.badge-credit { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.badge-debit  { background: #f8d7da; color: #721c24; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.montant-cell { font-weight: 700; text-align: right; }
</style>

<script>
(function(){
    var input   = document.getElementById('numero_compte'),
        box     = document.getElementById('suggestions'),
        preview = document.getElementById('client-preview'),
        timer   = null;
    input.addEventListener('input', function(){
        var val = this.value.replace(/\D/g, '');
        clearTimeout(timer); box.innerHTML = ''; preview.style.display = 'none';
        if (val.length < 5) return;
        timer = setTimeout(function(){
            fetch('<?= BASE_URL ?>?controller=Api&action=lookupAccount&prefix=' + encodeURIComponent(val))
            .then(function(r){ return r.json(); })
            .then(function(d){
                box.innerHTML = '';
                if (!d.results || !d.results.length) {
                    box.innerHTML = '<div class="suggestion-item" style="color:#999">Aucun compte trouvé.</div>';
                    return;
                }
                d.results.forEach(function(acc){
                    var div = document.createElement('div'); div.className = 'suggestion-item';
                    div.innerHTML = '<span class="acc-num">' + acc.numero_compte + '</span> &mdash; ' + acc.nom_client
                        + (acc.est_suspendu ? ' <span class="acc-suspended">[SUSPENDU]</span>' : '');
                    div.addEventListener('click', function(){
                        input.value = acc.numero_compte; box.innerHTML = '';
                        preview.style.display = 'block';
                        preview.innerHTML = '<strong>' + acc.nom_client + '</strong>'
                            + (acc.telephone ? ' &middot; ' + acc.telephone : '')
                            + ' &middot; Solde&nbsp;: <strong>' + acc.solde + ' FCFA</strong>'
                            + (acc.est_suspendu ? ' <span style="color:#dc3545;font-weight:700;">[SUSPENDU]</span>' : '');
                    });
                    box.appendChild(div);
                });
            }).catch(function(){ box.innerHTML = ''; });
        }, 300);
    });
    document.addEventListener('click', function(e){
        if (!input.contains(e.target) && !box.contains(e.target)) box.innerHTML = '';
    });
})();
</script>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
