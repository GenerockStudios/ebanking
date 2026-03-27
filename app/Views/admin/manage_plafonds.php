<?php
/**
 * manage_plafonds.php — Gestion des plafonds de tous les comptes
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<style>
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:12px; }
.page-header h2 { margin:0; color:#042e5a; font-size:1.4rem; font-weight:700; border:none; padding:0; }
.btn-action {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border-radius:6px; border:none;
    cursor:pointer; font-weight:600; font-size:14px;
    text-decoration:none; transition:0.2s ease;
}
.btn-navy  { background:#042e5a; color:#fff; } .btn-navy:hover  { background:#021d3a; }
.btn-green { background:#28a745; color:#fff; } .btn-green:hover { background:#1e7e34; }
.btn-blue  { background:#007bff; color:#fff; } .btn-blue:hover  { background:#0062cc; }

<?php if (isset($data['success'])): ?>
.alert-success { background:#d4edda; color:#155724; padding:12px 16px; border-radius:6px; margin-bottom:16px; border:1px solid #c3e6cb; }
<?php endif; ?>
<?php if (isset($data['error'])): ?>
.alert-error { background:#f8d7da; color:#721c24; padding:12px 16px; border-radius:6px; margin-bottom:16px; border:1px solid #f5c6cb; }
<?php endif; ?>

.data-table-wrap {
    background:#fff; border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,.06); overflow:hidden;
    border:1px solid #f0f0f0;
}
.data-table { width:100%; border-collapse:collapse; font-size:13px; }
.data-table thead th {
    background:#042e5a; color:#fff; padding:13px 16px;
    text-align:left; font-weight:600; white-space:nowrap;
}
.data-table tbody td { padding:12px 16px; border-bottom:1px solid #f5f5f5; vertical-align:middle; }
.data-table tbody tr:hover { background:#f8faff; }
.data-table tbody tr:last-child td { border-bottom:none; }
.client-name { font-weight:700; color:#042e5a; }
.account-num { font-size:11px; color:#888; margin-top:2px; }
.amount { font-weight:700; color:#1a1f36; font-size:14px; }
.no-limit { color:#dc3545; font-size:12px; font-style:italic; }
.table-actions { display:flex; gap:8px; flex-wrap:wrap; }
.btn-sm { padding:6px 12px; font-size:12px; border-radius:5px; }
.tag-daily   { display:inline-block; background:#e8f4ff; color:#0056b3; border-radius:20px; padding:2px 8px; font-size:10px; font-weight:700; margin-left:6px; vertical-align:middle; }
.tag-monthly { display:inline-block; background:#fff3e0; color:#e65100; border-radius:20px; padding:2px 8px; font-size:10px; font-weight:700; margin-left:6px; vertical-align:middle; }
</style>

<div class="page-header">
    <h2>
        <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">shield_lock</span>
        Gestion Experte des Plafonds de Comptes
    </h2>
    <a href="<?= BASE_URL ?>?controller=Admin&action=analyticsDashboard" class="btn-action btn-navy">
        <span class="material-symbols-rounded">arrow_back</span> Dashboard
    </a>
</div>

<?php if (isset($data['success'])): ?>
<div class="alert-success"><strong>Succès !</strong> <?= htmlspecialchars($data['success']) ?></div>
<?php endif; ?>
<?php if (isset($data['error'])): ?>
<div class="alert-error"><strong>Erreur.</strong> <?= htmlspecialchars($data['error']) ?></div>
<?php endif; ?>

<div class="data-table-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Client / Compte</th>
                <th>Retrait <span class="tag-daily">/ Jour</span></th>
                <th>Dépôt <span class="tag-daily">/ Jour</span></th>
                <th>Transfert <span class="tag-monthly">/ Mois</span></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['plafonds'])): ?>
            <tr>
                <td colspan="5" style="text-align:center;padding:40px;color:#aaa;">
                    <span class="material-symbols-rounded" style="font-size:32px;display:block;margin-bottom:8px;">inbox</span>
                    Aucun compte trouvé dans le système.
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($data['plafonds'] as $p): ?>
            <tr>
                <td>
                    <div class="client-name"><?= strtoupper(htmlspecialchars($p['nom'])) ?> <?= htmlspecialchars($p['prenom']) ?></div>
                    <div class="account-num">N° <?= htmlspecialchars($p['numero_compte']) ?></div>
                </td>
                <td>
                    <?php if ($p['plafond_retrait_journalier'] !== null): ?>
                        <span class="amount"><?= number_format($p['plafond_retrait_journalier'], 0, ',', ' ') ?> FCFA</span>
                    <?php else: ?>
                        <span class="no-limit">Non défini</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($p['plafond_depot_journalier'] !== null): ?>
                        <span class="amount"><?= number_format($p['plafond_depot_journalier'], 0, ',', ' ') ?> FCFA</span>
                    <?php else: ?>
                        <span class="no-limit">Non défini</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($p['plafond_transfert_mensuel'] !== null): ?>
                        <span class="amount"><?= number_format($p['plafond_transfert_mensuel'], 0, ',', ' ') ?> FCFA</span>
                    <?php else: ?>
                        <span class="no-limit">Non défini</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="table-actions">
                        <a href="<?= BASE_URL ?>?controller=Admin&action=editPlafond&compte_id=<?= $p['compte_id'] ?>"
                           class="btn-action btn-navy btn-sm">
                            <span class="material-symbols-rounded" style="font-size:15px;">edit</span> Modifier
                        </a>
                        <a href="<?= BASE_URL ?>?controller=Admin&action=contratPlafond&compte_id=<?= $p['compte_id'] ?>"
                           class="btn-action btn-green btn-sm">
                            <span class="material-symbols-rounded" style="font-size:15px;">description</span> Contrat
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
