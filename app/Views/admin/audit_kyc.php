<?php
/**
 * audit_kyc.php — Audit de Conformité KYC (Rapport Sectoriel Imprimable)
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<style>
/* Layout impression A4 Portrait géré globalement par responsive-core.css .no-print */
.watermark {
    display: none;
    position: fixed; top: 50%; left: 50%;
    transform: translate(-50%, -50%) rotate(-40deg);
    font-size: 72pt; font-weight: 900;
    color: rgba(40, 167, 69, 0.07);
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
.btn-green { background: #28a745; color: #fff; }
.btn-green:hover { background: #1e7e34; }
.btn-gray { background: #6c757d; color: #fff; }
.btn-gray:hover { background: #545b62; }
.btn-navy { background: #042e5a; color: #fff; }
.btn-navy:hover { background: #021d3a; }

.filter-strip {
    background: #f8f9fa; border: 1px solid #dee2e6;
    border-radius: 8px; padding: 14px 20px;
    display: flex; gap: 16px; align-items: center;
    flex-wrap: wrap; margin-bottom: 24px;
}
.filter-strip label { font-size: 14px; font-weight: 500; color: #555; }
.filter-strip select {
    padding: 8px 12px; border-radius: 6px;
    border: 1px solid #ced4da; font-size: 14px;
    background: #fff; cursor: pointer;
}

.doc-wrapper {
    border: 2px solid #28a745; border-radius: 8px;
    background: #fff; padding: 32px; margin-bottom: 24px;
}
.doc-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 3px double #28a745; padding-bottom: 18px; margin-bottom: 22px;
}
.doc-title { font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; color: #155724; }
.doc-subtitle { font-size: 13px; color: #333; margin-top: 4px; }
.doc-meta { text-align: right; font-size: 11px; color: #555; line-height: 1.6; }
.doc-meta strong { font-size: 13px; color: #042e5a; }
.ref-badge {
    display: inline-block; background: #d4edda; color: #155724;
    padding: 2px 10px; border-radius: 20px; font-size: 11px;
    font-weight: 700; margin-top: 4px;
}

.audit-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 16px; }
.audit-table thead th {
    background: #f1f8f3; color: #155724; font-weight: 700;
    padding: 11px 13px; text-align: left;
    border-bottom: 2px solid #28a745; border-top: 1px solid #c3e6cb;
}
.audit-table tbody td { padding: 10px 13px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.audit-table tbody tr:hover { background: #f8fff9; }
.badge-risk { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.risk-high { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
.risk-medium { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
.client-name { font-weight: 700; color: #042e5a; }
.client-tel { font-size: 11px; color: #666; margin-top: 2px; }
.date-expired { color: #dc3545 !important; font-weight: 700; }
.empty-state {
    text-align: center; padding: 60px 30px;
    background: #f1f8f3; border-radius: 8px; color: #28a745;
}
.empty-state .icon { font-size: 56px; margin-bottom: 12px; }
.stat-footer {
    margin-top: 24px; padding: 14px 18px;
    border-left: 5px solid #28a745; background: #f8f9fa;
    font-size: 13px; border-radius: 0 6px 6px 0;
}
.sig-row { display: flex; justify-content: space-between; margin-top: 60px; gap: 20px; }
.sig-box { width: 42%; text-align: center; padding-top: 12px; border-top: 1px dotted #333; font-style: italic; font-size: 12px; color: #555; }

.back-link { text-align: center; margin-top: 20px; }
</style>

<div class="watermark">CONFIDENTIEL</div>

<!-- Actions barre no-print -->
<div class="no-print">
    <div class="page-actions">
        <h2>
            <span class="material-symbols-rounded" style="vertical-align:middle;color:#28a745;">fact_check</span>
            Moteur d'Audit de Conformité KYC
        </h2>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button onclick="window.print()" class="btn-action btn-green">
                <span class="material-symbols-rounded">print</span> Imprimer le Rapport
            </button>
            <a href="<?= BASE_URL ?>?controller=Admin&action=analyticsDashboard" class="btn-action btn-navy">
                <span class="material-symbols-rounded">arrow_back</span> Tableau de Bord
            </a>
        </div>
    </div>

    <form method="GET" action="<?= BASE_URL ?>" class="filter-strip">
        <input type="hidden" name="controller" value="Admin">
        <input type="hidden" name="action" value="auditKyc">
        <label for="type_doc">Filtrer par type :</label>
        <select name="type_doc" id="type_doc" onchange="this.form.submit()">
            <option value="">Tous les types</option>
            <option value="CNI" <?= ($data['filters']['type_document'] ?? '') === 'CNI' ? 'selected' : '' ?>>CNI</option>
            <option value="PASSEPORT" <?= ($data['filters']['type_document'] ?? '') === 'PASSEPORT' ? 'selected' : '' ?>>Passeport</option>
        </select>
        <a href="<?= BASE_URL ?>?controller=Admin&action=auditKyc" class="btn-action btn-gray">Réinitialiser</a>
    </form>
</div>

<!-- Document imprimable -->
<div class="doc-wrapper">
    <div class="doc-header">
        <div>
            <div class="doc-title">Rapport Sectoriel d'Audit KYC</div>
            <div class="doc-subtitle">Détection d'Anomalies et Risques de Conformité</div>
        </div>
        <div class="doc-meta">
            <strong><?= APP_NAME ?></strong><br>
            Direction de la Conformité et du Risque<br>
            Édité le : <?= $data['date_edition'] ?><br>
            <span class="ref-badge">KYC-AUD-<?= date('Ymd') ?>-<?= strtoupper(substr(uniqid(), -4)) ?></span>
        </div>
    </div>

    <p style="font-size:13px;line-height:1.7;color:#333;margin-bottom:20px;">
        Le présent rapport identifie les dossiers clients présentant des non-conformités critiques
        (documents expirés ou non validés). Ces dossiers nécessitent une régularisation immédiate
        pour éviter toute sanction réglementaire.
    </p>

    <?php if (empty($data['anomalies'])): ?>
        <div class="empty-state">
            <div class="icon">✔</div>
            <h3 style="margin:0 0 8px;">Aucune anomalie détectée</h3>
            <p style="margin:0;font-size:14px;">Tous les documents KYC analysés sont conformes et à jour.</p>
        </div>
    <?php else: ?>
        <table class="audit-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Client</th>
                    <th>Type Doc.</th>
                    <th>Date Expiration</th>
                    <th>Validateur</th>
                    <th>Statut / Risque</th>
                    <th class="no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data['anomalies'] as $i => $a):
                    $expired = !empty($a['date_expiration']) && strtotime($a['date_expiration']) < time();
                    $riskClass = $expired ? 'risk-high' : 'risk-medium';
                    $riskLabel = $expired ? 'EXPIRÉ — CRITIQUE' : 'NON VALIDÉ';
                ?>
                <tr>
                    <td style="color:#999;font-size:12px;"><?= $i + 1 ?></td>
                    <td>
                        <div class="client-name"><?= strtoupper(htmlspecialchars($a['nom'])) ?> <?= htmlspecialchars($a['prenom']) ?></div>
                        <div class="client-tel"><?= htmlspecialchars($a['telephone'] ?? '') ?></div>
                    </td>
                    <td><strong><?= htmlspecialchars($a['type_document']) ?></strong></td>
                    <td class="<?= $expired ? 'date-expired' : '' ?>">
                        <?= !empty($a['date_expiration']) ? date('d/m/Y', strtotime($a['date_expiration'])) : 'Indéfinie' ?>
                    </td>
                    <td><?= htmlspecialchars($a['validateur_nom'] ?? 'Non assigné') ?></td>
                    <td><span class="badge-risk <?= $riskClass ?>"><?= $riskLabel ?></span></td>
                    <td class="no-print">
                        <a href="<?= BASE_URL ?>?controller=Admin&action=ficheClient&client_id=<?= $a['client_id'] ?>"
                           class="btn-action btn-navy" style="padding:6px 12px;font-size:12px;">
                            <span class="material-symbols-rounded" style="font-size:16px;">person_search</span> Fiche
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="stat-footer">
            <strong>Résumé :</strong> <?= count($data['anomalies']) ?> anomalie(s) détectée(s) au
            <?= date('d/m/Y') ?>. Régularisation impérative pour chaque dossier signalé.
        </div>
    <?php endif; ?>

    <div class="sig-row">
        <div class="sig-box">Chef de Service Conformité</div>
        <div class="sig-box">Directeur des Opérations / Risques</div>
    </div>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
