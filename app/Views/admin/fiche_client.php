<?php
/**
 * fiche_client.php — Vue consolidée 360° du client (imprimable)
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<style>
/* Layout impression A4 Portrait géré globalement par responsive-core.css .no-print */
@media print {
    .doc-wrapper { border: 2px solid #2c3e50 !important; }
    .data-table thead th { background: #f8f9fa !important; color: #000 !important; }
}
.no-print .page-actions {
    display: flex; justify-content: space-between; align-items: center;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.no-print .page-actions h2 { margin: 0; color: #042e5a; font-size: 1.4rem; font-weight: 700; border: none; padding: 0; }
.btn-action {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 6px; border: none;
    cursor: pointer; font-weight: 600; font-size: 14px;
    text-decoration: none; transition: 0.2s ease;
}
.btn-navy  { background: #042e5a; color: #fff; } .btn-navy:hover  { background: #021d3a; }
.btn-green { background: #28a745; color: #fff; } .btn-green:hover { background: #1e7e34; }
.btn-gray  { background: #6c757d; color: #fff; } .btn-gray:hover  { background: #545b62; }

.doc-wrapper {
    background: #fff; border: 1px solid #e1e8ed;
    border-radius: 8px; padding: 40px;
    box-shadow: 0 4px 16px rgba(0,0,0,.05);
}
.doc-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 3px double #2c3e50; padding-bottom: 20px; margin-bottom: 28px;
}
.logo-brand { font-size: 28px; font-weight: 900; color: #042e5a; }
.doc-meta { text-align: right; font-size: 12px; color: #555; line-height: 1.6; }
.doc-title {
    font-size: 22px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 2px; color: #2c3e50; text-align: center; margin-bottom: 32px;
}
.section-title {
    font-size: 14px; font-weight: 700; color: #042e5a;
    border-bottom: 2px solid #e8f0fe; padding-bottom: 8px;
    margin: 28px 0 16px; display: flex; align-items: center; gap: 8px;
}
.info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px 28px; margin-bottom: 20px; }
.info-item .info-label { font-size: 11px; text-transform: uppercase; color: #888; font-weight: 600; display: block; margin-bottom: 2px; }
.info-item .info-value { font-size: 15px; color: #1a1f36; font-weight: 500; }
.info-item.span2 { grid-column: span 2; }

.data-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
.data-table thead th {
    background: #042e5a; color: #fff; padding: 11px 13px;
    text-align: left; font-weight: 600; white-space: nowrap;
}
.data-table tbody td { padding: 11px 13px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.data-table tbody tr:hover { background: #f8faff; }
.data-table tbody tr:last-child td { border-bottom: none; }
.data-table tfoot td { padding: 11px 13px; font-weight: 700; background: #f8f9fa; }
.amount { font-family: 'Courier New', monospace; font-weight: 700; text-align: right; }
.badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-active { background: #e3fcef; color: #006644; }
.badge-suspended { background: #ffebe6; color: #bf2600; }
.badge-valid { background: #d4edda; color: #155724; }
.badge-pending { background: #fff3cd; color: #856404; }
.no-data { text-align: center; padding: 24px; color: #aaa; font-style: italic; }
.sig-row { display: flex; justify-content: space-between; margin-top: 60px; gap: 20px; }
.sig-box { flex: 1; text-align: center; padding-top: 12px; border-top: 1px dotted #333; font-style: italic; font-size: 12px; color: #555; }
.doc-footer-note { margin-top: 32px; font-size: 10px; color: #aaa; text-align: center; border-top: 1px solid #f0f0f0; padding-top: 8px; }
</style>

<div class="no-print">
    <div class="page-actions">
        <h2>
            <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">person_search</span>
            Fiche Signalétique 360° — <?= strtoupper(htmlspecialchars($data['client']['nom'])) ?> <?= htmlspecialchars($data['client']['prenom']) ?>
        </h2>
        <div style="display:flex;gap:10px;">
            <button onclick="window.print()" class="btn-action btn-green">
                <span class="material-symbols-rounded">print</span> Imprimer
            </button>
            <a href="<?= BASE_URL ?>?controller=Admin&action=manageClients" class="btn-action btn-gray">
                <span class="material-symbols-rounded">arrow_back</span> Retour
            </a>
        </div>
    </div>
</div>

<div class="doc-wrapper">
    <div class="doc-header">
        <div class="logo-brand">E-BANKING PRO</div>
        <div class="doc-meta">
            <strong><?= APP_NAME ?></strong><br>
            Service Gestion Clientèle<br>
            Édité le : <?= date('d/m/Y à H:i:s') ?><br>
            <span style="font-family:monospace;font-size:10px;">FICHE-<?= $data['client']['client_id'] ?>-<?= date('Ymd') ?></span>
        </div>
    </div>

    <div class="doc-title">Fiche Signalétique du Titulaire</div>

    <!-- Section 1: Info personnelles -->
    <div class="section-title">
        <span class="material-symbols-rounded" style="font-size:18px;">person</span>
        État Civil &amp; Coordonnées
    </div>
    <div class="info-grid">
        <div class="info-item">
            <span class="info-label">Nom complet</span>
            <span class="info-value"><?= strtoupper(htmlspecialchars($data['client']['nom'])) ?> <?= htmlspecialchars($data['client']['prenom']) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">N° d'Identité (CNI/Passeport)</span>
            <span class="info-value"><?= htmlspecialchars($data['client']['numero_identite']) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Date de Naissance</span>
            <span class="info-value"><?= !empty($data['client']['date_naissance']) ? date('d/m/Y', strtotime($data['client']['date_naissance'])) : 'N/R' ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Date d'Adhésion</span>
            <span class="info-value"><?= date('d/m/Y', strtotime($data['client']['date_creation'])) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Téléphone</span>
            <span class="info-value"><?= htmlspecialchars($data['client']['telephone'] ?? 'N/R') ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Email</span>
            <span class="info-value"><?= htmlspecialchars($data['client']['email'] ?: 'Non renseigné') ?></span>
        </div>
        <div class="info-item span2">
            <span class="info-label">Adresse de Domiciliation</span>
            <span class="info-value"><?= htmlspecialchars($data['client']['adresse'] ?? 'N/R') ?></span>
        </div>
    </div>

    <!-- Section 2: Comptes -->
    <div class="section-title">
        <span class="material-symbols-rounded" style="font-size:18px;">account_balance_wallet</span>
        Portefeuille de Comptes Consolidé
    </div>
    <div class="table-scroll-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>N° Compte</th>
                <th>Type</th>
                <th>Date Ouverture</th>
                <th>Statut</th>
                <th style="text-align:right;">Solde Actuel (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['accounts'])): ?>
            <tr><td colspan="5" class="no-data">Aucun compte actif pour ce client.</td></tr>
            <?php else: ?>
            <?php foreach ($data['accounts'] as $acc): ?>
            <tr>
                <td><strong><?= htmlspecialchars($acc['numero_compte']) ?></strong></td>
                <td><?= htmlspecialchars($acc['nom_type'] ?? $acc['type_compte_id']) ?></td>
                <td><?= !empty($acc['date_ouverture']) ? date('d/m/Y', strtotime($acc['date_ouverture'])) : 'N/R' ?></td>
                <td>
                    <?php if ($acc['est_suspendu']): ?>
                        <span class="badge badge-suspended">Suspendu</span>
                    <?php else: ?>
                        <span class="badge badge-active">Actif</span>
                    <?php endif; ?>
                </td>
                <td class="amount"><?= number_format((float)$acc['solde'], 2, ',', ' ') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <?php if (!empty($data['accounts'])): ?>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;">TOTAL ENCOURS :</td>
                <td class="amount"><?= number_format(array_sum(array_column($data['accounts'], 'solde')), 2, ',', ' ') ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>
    </div>

    <!-- Section 3: KYC -->
    <div class="section-title">
        <span class="material-symbols-rounded" style="font-size:18px;">shield</span>
        Documents de Conformité (KYC)
    </div>
    <div class="table-scroll-wrap">
    <table class="data-table">
        <thead>
            <tr>
                <th>Type de Document</th>
                <th>Référence Fichier</th>
                <th>Date Expiration</th>
                <th>Validé par</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data['documents'])): ?>
            <tr><td colspan="5" class="no-data">Aucun document KYC enregistré.</td></tr>
            <?php else: ?>
            <?php foreach ($data['documents'] as $doc): ?>
            <tr>
                <td><strong><?= htmlspecialchars($doc['type_document']) ?></strong></td>
                <td style="font-size:11px;color:#666;"><?= htmlspecialchars(basename($doc['reference_fichier'] ?? '')) ?></td>
                <td><?= !empty($doc['date_expiration']) ? date('d/m/Y', strtotime($doc['date_expiration'])) : 'N/A' ?></td>
                <td><?= htmlspecialchars($doc['validateur'] ?? 'Système') ?></td>
                <td>
                    <?php if ($doc['est_valide']): ?>
                        <span class="badge badge-valid">Valide</span>
                    <?php else: ?>
                        <span class="badge badge-pending">En attente</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>

    <div class="sig-row">
        <div class="sig-box">Signature du Titulaire<br><small>(Lu et approuvé)</small></div>
        <div class="sig-box">Le Responsable d'Agence<br><small>(Cachet et Signature)</small></div>
    </div>

    <div class="doc-footer-note">
        Imprimé le <?= date('d/m/Y à H:i:s') ?> | Validé par Admin ID: <?= $_SESSION['user_id'] ?> | Réf: FICHE-<?= $data['client']['client_id'] ?>-<?= time() ?>
    </div>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
