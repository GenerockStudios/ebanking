<?php
/**
 * cloture.php — Gestion Sessions de Caisse (Ouverture / Clôture)
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<style>
.section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.section-header h2 { margin:0; color:#042e5a; font-size:1.4rem; font-weight:700; border:none; padding:0; }
.btn-action {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border-radius:6px; border:none;
    cursor:pointer; font-weight:600; font-size:14px;
    text-decoration:none; transition:0.2s ease;
}
.btn-navy   { background:#042e5a; color:#fff; } .btn-navy:hover   { background:#021d3a; }
.btn-green  { background:#28a745; color:#fff; } .btn-green:hover  { background:#1e7e34; }
.btn-danger { background:#dc3545; color:#fff; } .btn-danger:hover { background:#b21f2d; }

<?php if (isset($_GET['success'])): ?>
.alert-success { background:#d4edda; color:#155724; padding:12px 16px; border-radius:6px; margin-bottom:16px; border:1px solid #c3e6cb; }
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
.alert-error { background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:6px; margin-bottom:16px; border:1px solid #f5c6cb; }
<?php endif; ?>

.page-card {
    background:#fff; border-radius:12px; max-width:700px; margin:0 auto;
    box-shadow:0 2px 12px rgba(0,0,0,.07); border:1px solid #f0f0f0;
    overflow:hidden;
}
.card-top {
    background:linear-gradient(135deg, #042e5a, #0a4a8a);
    color:#fff; padding:24px 28px;
    display:flex; align-items:center; gap:16px;
}
.card-top .icon-circle {
    width:52px; height:52px; border-radius:50%;
    background:rgba(255,255,255,.15);
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0;
}
.card-top h3 { margin:0; font-size:1.2rem; font-weight:700; }
.card-top p  { margin:4px 0 0; font-size:13px; opacity:.75; }
.card-body { padding:28px; }

.stats-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:24px; }
.stat-box  { background:#f8f9fa; border-radius:8px; padding:16px 18px; }
.stat-box .label { font-size:11px; font-weight:600; text-transform:uppercase; color:#888; margin-bottom:6px; }
.stat-box .val   { font-size:20px; font-weight:800; color:#042e5a; }
.stat-highlight  { background:linear-gradient(135deg,#042e5a,#0a4a8a); color:#fff; }
.stat-highlight .label { color:rgba(255,255,255,.7); }
.stat-highlight .val   { color:#fff; font-size:22px; }

.flux-table { width:100%; border-collapse:collapse; font-size:13px; margin-bottom:20px; }
.flux-table thead th { background:#042e5a; color:#fff; padding:10px 13px; text-align:left; font-weight:600; }
.flux-table tbody td { padding:10px 13px; border-bottom:1px solid #f0f0f0; }
.flux-table tbody tr:last-child td { border-bottom:none; }
.type-depot    { background:#e3fcef; color:#006644; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.type-retrait  { background:#ffebe6; color:#bf2600; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.type-transfert{ background:#e8f0fe; color:#1a56db; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; }
.num { text-align:right; font-weight:700; }

.warning-box { background:#fff8e1; border-left:4px solid #ffc107; border-radius:0 6px 6px 0; padding:12px 16px; font-size:13px; color:#856404; margin-bottom:20px; }
.form-group { margin-bottom:18px; }
.form-group label { display:block; font-size:12px; font-weight:600; color:#555; text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
.form-group .field-wrap { position:relative; }
.form-group .field-icon { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#aaa; }
.form-group input[type=number] {
    width:100%; box-sizing:border-box; padding:13px 14px 13px 44px;
    border:1.5px solid #e3e8ee; border-radius:8px; font-size:16px; color:#1a1f36;
    transition:border-color 0.2s; outline:none;
}
.form-group input[type=number]:focus { border-color:#042e5a; }
.btn-submit {
    width:100%; border:none; border-radius:8px; padding:15px; font-size:16px;
    font-weight:700; cursor:pointer; transition:0.2s ease;
    display:flex; align-items:center; justify-content:center; gap:10px;
}
.btn-submit.open   { background:#28a745; color:#fff; } .btn-submit.open:hover   { background:#1e7e34; }
.btn-submit.close  { background:#dc3545; color:#fff; } .btn-submit.close:hover  { background:#b21f2d; }

.empty-state { text-align:center; padding:40px; color:#aaa; }
.divider { border:none; border-top:1px solid #f0f0f0; margin:20px 0; }
</style>

<div class="section-header">
    <h2>
        <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">store</span>
        <?= htmlspecialchars($data['title']) ?>
    </h2>
    <a href="<?= BASE_URL ?>?controller=Caisse&action=dashboard" class="btn-action btn-navy">
        <span class="material-symbols-rounded">dashboard</span> Dashboard
    </a>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="alert-success"><strong>Succès !</strong> <?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="alert-error"><strong>Erreur.</strong> <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="page-card">

<?php if (isset($data['no_session'])): ?>
    <!-- Ouverture de caisse -->
    <div class="card-top">
        <div class="icon-circle"><span class="material-symbols-rounded" style="font-size:28px;">door_open</span></div>
        <div>
            <h3>Aucune session ouverte</h3>
            <p>Ouvrez votre caisse pour commencer la journée.</p>
        </div>
    </div>
    <div class="card-body">
        <form action="<?= BASE_URL ?>?controller=Caisse&action=ouvrirCaisse" method="POST">
            <div class="form-group">
                <label for="solde_initial">Fond de Caisse Initial (FCFA)</label>
                <div class="field-wrap">
                    <span class="field-icon material-symbols-rounded">payments</span>
                    <input type="number" id="solde_initial" name="solde_initial"
                           min="0" step="50" placeholder="0" required>
                </div>
                <div style="font-size:11px;color:#aaa;margin-top:6px;">Entrez le montant réel présent dans le tiroir-caisse.</div>
            </div>
            <button type="submit" class="btn-submit open">
                <span class="material-symbols-rounded">play_circle</span> Ouvrir la Caisse
            </button>
        </form>
    </div>

<?php else: ?>
    <!-- Clôture de caisse -->
    <div class="card-top">
        <div class="icon-circle"><span class="material-symbols-rounded" style="font-size:28px;">lock_clock</span></div>
        <div>
            <h3>Session en cours</h3>
            <p>Ouverte le <?= date('d/m/Y', strtotime($data['session']->date_ouverture)) ?> à <?= $data['session']->heure_ouverture ?></p>
        </div>
    </div>
    <div class="card-body">
        <!-- KPI -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="label">Fond Initial</div>
                <div class="val"><?= number_format($data['session']->solde_initial_caisse, 2, ',', ' ') ?> <small style="font-size:12px;font-weight:400;">FCFA</small></div>
            </div>
            <div class="stat-box stat-highlight">
                <div class="label">Solde Théorique (Système)</div>
                <div class="val"><?= number_format($data['system_balance'] ?? 0, 2, ',', ' ') ?> <small style="font-size:12px;font-weight:400;">FCFA</small></div>
            </div>
        </div>

        <!-- Récapitulatif flux -->
        <table class="flux-table">
            <thead>
                <tr>
                    <th>Type d'Opération</th>
                    <th style="text-align:center;">Nb</th>
                    <th style="text-align:right;">Total Cumulé</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['stats'])): ?>
                <tr><td colspan="3" style="text-align:center;padding:20px;color:#aaa;">Aucune transaction effectuée lors de cette session.</td></tr>
                <?php else: ?>
                <?php foreach ($data['stats'] as $stat): ?>
                <tr>
                    <td>
                        <?php if ($stat['type_transaction'] === 'DEPOT'): ?>
                            <span class="type-depot">Dépôts</span>
                        <?php elseif ($stat['type_transaction'] === 'RETRAIT'): ?>
                            <span class="type-retrait">Retraits</span>
                        <?php else: ?>
                            <span class="type-transfert"><?= htmlspecialchars($stat['type_transaction']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;"><?= $stat['nb'] ?></td>
                    <td class="num"><?= number_format($stat['total'], 2, ',', ' ') ?> FCFA</td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <hr class="divider">
        <div class="warning-box">
            <span class="material-symbols-rounded" style="vertical-align:middle;font-size:18px;">warning</span>
            <strong>Attention :</strong> La clôture est <strong>définitive</strong> pour cette session.
        </div>

        <!-- Formulaire clôture -->
        <form action="<?= BASE_URL ?>?controller=Caisse&action=validerCloture" method="POST"
              onsubmit="return confirm('Êtes-vous sûr de vouloir arrêter et clôturer cette session ?');">
            <div class="form-group">
                <label for="solde_reel">Solde Réel en Caisse (Comptage Physique)</label>
                <div class="field-wrap">
                    <span class="field-icon material-symbols-rounded">calculate</span>
                    <input type="number" id="solde_reel" name="solde_reel"
                           step="0.01" value="<?= $data['system_balance'] ?? 0 ?>" required>
                </div>
                <div style="font-size:11px;color:#aaa;margin-top:6px;">Saisissez le montant exact compté physiquement dans le tiroir.</div>
            </div>
            <button type="submit" class="btn-submit close">
                <span class="material-symbols-rounded">lock</span> Valider l'Arrêté et Clôturer
            </button>
        </form>
    </div>
<?php endif; ?>

</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
