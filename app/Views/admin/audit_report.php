<?php
/**
 * audit_report.php — Rapport d'Audit Sécurité & Intégrité (Vue Imprimable)
 * Intégré dans le layout existant (header/footer).
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<style>
@media print {
    @page { size: A4 portrait; margin: 1.5cm; }
    .no-print { display: none !important; }
    .sidebar, footer, .content > footer { display: none !important; }
    .content { padding: 0 !important; box-shadow: none !important; width: 100% !important; padding-left: 0 !important; }
    * { box-shadow: none !important; }
    .watermark { display: block !important; }
    .type-failure { color: #c0392b !important; }
    .type-success { color: #27ae60 !important; }
}
.watermark {
    display: none; position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%) rotate(-40deg);
    font-size: 72pt; font-weight: 900;
    color: rgba(200, 200, 200, 0.12);
    pointer-events: none; z-index: -1; white-space: nowrap;
}
.page-actions {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.page-actions h2 { margin: 0; color: #042e5a; font-size: 1.4rem; font-weight: 700; border: none; padding: 0; }
.btn-action {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 6px; border: none;
    cursor: pointer; font-weight: 600; font-size: 14px;
    text-decoration: none; transition: 0.2s ease;
}
.btn-navy  { background: #042e5a; color: #fff; } .btn-navy:hover  { background: #021d3a; }
.btn-gray  { background: #6c757d; color: #fff; } .btn-gray:hover  { background: #545b62; }

.doc-wrapper {
    background: #fff; border: 2px solid #2c3e50;
    border-radius: 8px; padding: 36px;
    position: relative; margin-bottom: 24px;
}
.doc-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 3px double #2c3e50; padding-bottom: 20px; margin-bottom: 28px;
}
.logo-brand { font-size: 26px; font-weight: 900; color: #042e5a; letter-spacing: -1px; }
.doc-meta-right { text-align: right; font-size: 12px; color: #555; line-height: 1.6; }
.doc-title { font-size: 22px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; color: #2c3e50; text-align: center; margin-bottom: 6px; }
.doc-period { text-align: center; color: #7f8c8d; font-style: italic; margin-bottom: 28px; font-size: 14px; }
.badge-confidentiel {
    display: inline-block; background: #e74c3c; color: #fff;
    padding: 4px 16px; border-radius: 4px; font-weight: 700;
    text-transform: uppercase; font-size: 11px; margin-bottom: 20px;
}
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-card { border: 1px solid #dde; border-radius: 8px; padding: 18px; text-align: center; }
.stat-val { font-size: 26px; font-weight: 800; color: #042e5a; }
.stat-val.danger { color: #e74c3c; }
.stat-label { font-size: 11px; color: #7f8c8d; text-transform: uppercase; letter-spacing: .5px; margin-top: 4px; }
.section-title {
    background: #f8f9fa; padding: 10px 16px;
    border-left: 5px solid #042e5a; font-weight: 700;
    margin: 24px 0 14px; font-size: 14px; color: #2c3e50;
    border-radius: 0 4px 4px 0;
}
.data-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.data-table thead th {
    background: #ecf0f1; color: #2c3e50; padding: 10px 12px;
    text-align: left; border-bottom: 2px solid #bdc3c7; font-weight: 700;
}
.data-table tbody td { padding: 9px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: top; }
.data-table tbody tr:hover { background: #f9fafb; }
.type-failure { color: #c0392b; font-weight: 700; }
.type-success { color: #27ae60; font-weight: 600; }
.sig-row { display: flex; justify-content: space-between; margin-top: 60px; gap: 20px; }
.sig-box { width: 42%; text-align: center; padding-top: 12px; border-top: 1px dotted #333; font-style: italic; font-size: 12px; color: #555; }
.doc-footer-note { margin-top: 40px; font-size: 10px; color: #aaa; text-align: center; border-top: 1px solid #eee; padding-top: 12px; }
</style>

<div class="watermark">STRICTEMENT CONFIDENTIEL</div>

<!-- Page actions -->
<div class="no-print">
    <div class="page-actions">
        <h2>
            <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">security</span>
            Rapport d'Audit Sécurité &amp; Intégrité
        </h2>
        <div style="display:flex;gap:10px;">
            <button onclick="window.print()" class="btn-action btn-navy">
                <span class="material-symbols-rounded">print</span> Imprimer
            </button>
            <a href="<?= BASE_URL ?>?controller=Admin&action=auditLogs" class="btn-action btn-gray">
                <span class="material-symbols-rounded">arrow_back</span> Retour
            </a>
        </div>
    </div>
</div>

<!-- Document officiel -->
<div class="doc-wrapper">
    <div class="doc-header">
        <div class="logo-brand">E-BANKING PRO</div>
        <div class="doc-meta-right">
            <strong><?= APP_NAME ?></strong><br>
            Direction de la Conformité et Risques<br>
            Généré le : <?= $data['date_edition'] ?>
        </div>
    </div>

    <div style="text-align:center;margin-bottom:14px;">
        <span class="badge-confidentiel">Strictement Confidentiel</span>
    </div>
    <div class="doc-title"><?= htmlspecialchars($data['title']) ?></div>
    <div class="doc-period">
        Période du <?= date('d/m/Y', strtotime($data['date_debut'])) ?>
        au <?= date('d/m/Y', strtotime($data['date_fin'])) ?>
    </div>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-val"><?= $data['stats']['total_actions'] ?? 0 ?></div>
            <div class="stat-label">Actions Journalisées</div>
        </div>
        <div class="stat-card">
            <div class="stat-val danger"><?= $data['stats']['total_failures'] ?? 0 ?></div>
            <div class="stat-label">Échecs / Alertes</div>
        </div>
        <div class="stat-card">
            <div class="stat-val"><?= count($data['stats']['top_users'] ?? []) ?></div>
            <div class="stat-label">Acteurs Actifs</div>
        </div>
    </div>

    <!-- Journal détaillé -->
    <div class="section-title">Journal de l'Audit — Détails des Actions</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Date / Heure</th>
                <th>Utilisateur</th>
                <th>Action</th>
                <th>Cible</th>
                <th>Détails</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['logs'])): ?>
                <tr><td colspan="5" style="text-align:center;padding:30px;color:#999;">Aucun enregistrement pour cette période.</td></tr>
            <?php else: ?>
                <?php foreach ($data['logs'] as $log):
                    $isFailure = (strpos($log['type_action'], '_FAILURE') !== false || strpos($log['type_action'], 'DENIED') !== false);
                ?>
                <tr>
                    <td style="white-space:nowrap;"><?= date('d/m/Y H:i:s', strtotime($log['date_heure'])) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($log['username'] ?? 'Système') ?></strong><br>
                        <small style="color:#999;"><?= htmlspecialchars($log['nom_complet'] ?? '') ?></small>
                    </td>
                    <td class="<?= $isFailure ? 'type-failure' : 'type-success' ?>">
                        <?= htmlspecialchars($log['type_action']) ?>
                    </td>
                    <td><?= htmlspecialchars($log['table_affectee'] ?? 'N/A') ?><br><small style="color:#aaa;">ID : <?= htmlspecialchars($log['identifiant_element_affecte'] ?? '-') ?></small></td>
                    <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Top utilisateurs -->
    <?php if (!empty($data['stats']['top_users'])): ?>
    <div class="section-title">Palmarès des Activités par Utilisateur</div>
    <table class="data-table">
        <thead>
            <tr><th>Identifiant</th><th>Volume d'Actions</th><th>Niveau de Risque</th></tr>
        </thead>
        <tbody>
            <?php foreach ($data['stats']['top_users'] as $top): ?>
            <tr>
                <td><strong><?= htmlspecialchars($top['identifiant']) ?></strong></td>
                <td><?= $top['nb'] ?> actions</td>
                <td>
                    <?php if ($top['nb'] > 100): ?>
                        <span style="color:#e67e22;font-weight:700;">Surveillance Requise</span>
                    <?php else: ?>
                        <span style="color:#27ae60;font-weight:600;">Normal</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="sig-row">
        <div class="sig-box">Propriétaire du Rapport (Admin)</div>
        <div class="sig-box">Direction des Systèmes d'Information</div>
    </div>

    <div class="doc-footer-note">
        Document généré par le système d'audit interne. Toute reproduction sans autorisation est passible de sanctions.
        ID : SEC-<?= time() ?>
    </div>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
