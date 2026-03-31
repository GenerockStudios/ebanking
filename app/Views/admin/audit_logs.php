<?php
/**
 * audit_logs.php - Interface de visualisation du Journal d'Audit Panoptique.
 * Filtres : type (SUCCESS/FAILURE/START), utilisateur, plage de dates.
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= htmlspecialchars($data['title'] ?? "Journal d'Audit") ?></h2>

<!-- Formulaire de Filtres -->
<div class="filter-card">
    <form method="GET" action="<?= BASE_URL ?>?controller=Admin&action=auditLogs" class="filter-form">
        <input type="hidden" name="controller" value="Admin">
        <input type="hidden" name="action" value="auditLogs">

        <div class="filter-group">
            <label>Type d'action</label>
            <select name="filter_type" class="form-control">
                <option value="">Tous</option>
                <option value="SUCCESS" <?= ($data['filter_type'] ?? '') === 'SUCCESS' ? 'selected' : '' ?>>Succès uniquement</option>
                <option value="FAILURE" <?= ($data['filter_type'] ?? '') === 'FAILURE' ? 'selected' : '' ?>>Échecs uniquement</option>
                <option value="START"   <?= ($data['filter_type'] ?? '') === 'START'   ? 'selected' : '' ?>>Tentatives (START)</option>
            </select>
        </div>

        <div class="filter-group">
            <label>Utilisateur</label>
            <input type="text" name="filter_user" class="form-control" placeholder="Identifiant..."
                   value="<?= htmlspecialchars($data['filter_user'] ?? '') ?>">
        </div>

        <div class="filter-group">
            <label>Cible (Table)</label>
            <input type="text" name="filter_table" class="form-control" placeholder="Clients, Comptes..."
                   value="<?= htmlspecialchars($_GET['filter_table'] ?? '') ?>">
        </div>

        <div class="filter-group">
            <label>Du</label>
            <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($data['date_debut'] ?? '') ?>">
        </div>

        <div class="filter-group">
            <label>Au</label>
            <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($data['date_fin'] ?? '') ?>">
        </div>

        <div class="filter-group" style="padding-top: 25px;">
            <label style="display:inline-flex; align-items:center; cursor:pointer; font-size: 12px;">
                <input type="checkbox" name="only_failures" style="margin-right:8px;" <?= isset($_GET['only_failures']) ? 'checked' : '' ?>>
                Échecs uniquement
            </label>
        </div>

        <div class="filter-group" style="align-self:flex-end; display:flex; gap:10px;">
            <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filtrer</button>
            <a href="<?= BASE_URL ?>?controller=Admin&action=securityAuditReport&date_debut=<?= $data['date_debut'] ?>&date_fin=<?= $data['date_fin'] ?><?= isset($_GET['only_failures']) ? '&only_failures=1' : '' ?>" 
               class="btn-report" target="_blank">
                <i class="fas fa-file-shield"></i> Rapport Expert
            </a>
        </div>
    </form>
</div>

<?php if (isset($data['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['error']) ?>", 'error', 6000));</script>
<?php endif; ?>

<!-- Compteur -->
<p class="log-count"><strong><?= count($data['logs'] ?? []) ?></strong> entrées trouvées pour cette période.</p>

<!-- Tableau des logs -->
<div class="table-scroll-wrap">
<table class="data-table">
    <thead>
        <tr>
            <th>#</th>
            <th>Horodatage</th>
            <th>Utilisateur</th>
            <th>Action</th>
            <th>Table</th>
            <th>Élément</th>
            <th>Détails</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($data['logs'])): ?>
        <tr><td colspan="7" style="text-align:center;color:#999;padding:20px;">Aucun log pour cette période.</td></tr>
    <?php else: ?>
        <?php foreach ($data['logs'] as $log): ?>
        <?php
            $typeClass = 'log-neutral';
            if (strpos($log['type_action'], '_SUCCESS') !== false || strpos($log['type_action'], 'CLOTURE') !== false) $typeClass = 'log-success';
            elseif (strpos($log['type_action'], '_FAILURE') !== false || strpos($log['type_action'], 'ERROR') !== false) $typeClass = 'log-failure';
            elseif (strpos($log['type_action'], '_START') !== false) $typeClass = 'log-start';
        ?>
        <tr class="<?= $typeClass ?>">
            <td class="log-id" style="font-size:11px; color:#8792a2;"><?= htmlspecialchars($log['log_id']) ?></td>
            <td class="log-date" style="white-space:nowrap;"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['date_heure']))) ?></td>
            <td class="log-user" style="font-weight:600; color:#042e5a;"><?= htmlspecialchars($log['username'] ?? 'Système') ?></td>
            <td><span class="type-badge type-badge-<?= $typeClass ?>"><?= htmlspecialchars($log['type_action']) ?></span></td>
            <td><?= htmlspecialchars($log['table_affectee'] ?? '') ?></td>
            <td><?= htmlspecialchars($log['identifiant_element_affecte'] ?? '') ?></td>
            <td class="log-details" style="max-width:300px; word-break:break-word; color:#555;"><?= htmlspecialchars($log['details']) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<style>
/* Responsive Filter Card */
.filter-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 24px;
}
.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}
.filter-group { display: flex; flex-direction: column; gap: 8px; }
.filter-group label { font-weight: 600; font-size: 13px; color: #64748b; }
.form-control {
    padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px;
    font-size: 15px; color: #0f172a; outline: none; transition: border-color .2s;
    width: 100%; box-sizing: border-box; min-height: 48px;
}
.form-control:focus { border-color: #042e5a; box-shadow: 0 0 0 3px rgba(4,46,90,0.1); }
.btn-filter, .btn-report {
    padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 15px;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; border: none; min-height: 48px;
}
.btn-filter { background: #042e5a; color: #fff; width: 100%; }
.btn-filter:hover { background: #021d3a; }
.btn-report { background: #28a745; color: #fff; text-decoration: none; min-width: 160px; }
.btn-report:hover { background: #1e7e34; }
.log-count { margin: 10px 0; color: #64748b; font-size: 14px; }

/* Styles log statiques (surcharge data-table) */
.log-success td { background: #f0fff4 !important; }
.log-failure td { background: #fff5f5 !important; }
.log-start   td { background: #fffbf0 !important; }
.log-neutral td { background: #fff !important; }
.type-badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; display: inline-block; }
.type-badge-log-success { background: #d4edda; color: #155724; }
.type-badge-log-failure { background: #f8d7da; color: #721c24; }
.type-badge-log-start { background: #fff3cd; color: #856404; }
.type-badge-log-neutral { background: #e2e3e5; color: #383d41; }
</style>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
