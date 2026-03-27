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
<div class="alert-error"><?= htmlspecialchars($data['error']) ?></div>
<?php endif; ?>

<!-- Compteur -->
<p class="log-count"><strong><?= count($data['logs'] ?? []) ?></strong> entrées trouvées pour cette période.</p>

<!-- Tableau des logs -->
<div class="table-scroll">
<table class="log-table">
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
            <td class="log-id"><?= htmlspecialchars($log['log_id']) ?></td>
            <td class="log-date"><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($log['date_heure']))) ?></td>
            <td class="log-user"><?= htmlspecialchars($log['username'] ?? 'Système') ?></td>
            <td><span class="type-badge type-badge-<?= $typeClass ?>"><?= htmlspecialchars($log['type_action']) ?></span></td>
            <td><?= htmlspecialchars($log['table_affectee'] ?? '') ?></td>
            <td><?= htmlspecialchars($log['identifiant_element_affecte'] ?? '') ?></td>
            <td class="log-details"><?= htmlspecialchars($log['details']) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
</div>

<style>
.filter-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.07);margin-bottom:20px}
.filter-form{display:flex;flex-wrap:wrap;gap:14px;align-items:flex-start}
.filter-group{display:flex;flex-direction:column;gap:4px;min-width:160px}
.filter-group label{font-weight:600;font-size:13px;color:#444}
.form-control{padding:8px 12px;border:1.5px solid #dde;border-radius:8px;font-size:13px}
.btn-filter{padding:9px 20px;background:#042e5a;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600}
.btn-filter:hover{background:#0a4a8a}
.btn-report{padding:9px 15px;background:#28a745;color:#fff;border:none;border-radius:8px;cursor:pointer;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:8px}
.btn-report:hover{background:#218838}
.log-count{margin:10px 0;color:#555;font-size:14px}
.table-scroll{overflow-x:auto}
.log-table{width:100%;border-collapse:collapse;font-size:12px}
.log-table th{background:#042e5a;color:#fff;padding:10px 12px;text-align:left;white-space:nowrap}
.log-table td{padding:8px 12px;border-bottom:1px solid #f0f0f0;vertical-align:top}
.log-success td{background:#f0fff4}
.log-failure td{background:#fff5f5}
.log-start   td{background:#fffbf0}
.log-neutral td{background:#fff}
.log-table tr:hover td{filter:brightness(0.97)}
.type-badge{padding:2px 8px;border-radius:10px;font-size:10px;font-weight:700;white-space:nowrap}
.type-badge-log-success{background:#d4edda;color:#155724}
.type-badge-log-failure{background:#f8d7da;color:#721c24}
.type-badge-log-start{background:#fff3cd;color:#856404}
.type-badge-log-neutral{background:#e2e3e5;color:#383d41}
.log-id{color:#aaa;font-size:11px}
.log-date{white-space:nowrap;font-family:monospace}
.log-user{font-weight:600;color:#042e5a}
.log-details{max-width:300px;word-break:break-word;color:#555}
</style>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
