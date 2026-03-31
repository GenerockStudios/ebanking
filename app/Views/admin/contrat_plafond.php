<?php
/**
 * contrat_plafond.php — Contrat de Déplafonnement (Document Imprimable)
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<style>
/* Layout impression A4 Portrait géré globalement par responsive-core.css .no-print */
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
.btn-navy { background: #042e5a; color: #fff; } .btn-navy:hover { background: #021d3a; }
.btn-gray { background: #6c757d; color: #fff; } .btn-gray:hover { background: #545b62; }

.contrat {
    background: #fff; border: 2px solid #042e5a;
    border-radius: 8px; padding: 40px; max-width: 820px; margin: 0 auto;
}
.contrat-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    border-bottom: 3px double #042e5a; padding-bottom: 20px; margin-bottom: 28px;
}
.logo-brand { font-size: 22px; font-weight: 900; color: #042e5a; }
.contrat-meta { text-align: right; font-size: 12px; color: #666; line-height: 1.7; }
.contrat-title {
    font-size: 18px; font-weight: 800; text-transform: uppercase;
    letter-spacing: 2px; color: #042e5a; text-align: center;
    margin-bottom: 6px;
}
.contrat-subtitle { text-align: center; color: #777; font-size: 13px; margin-bottom: 32px; }

.section-block { margin-bottom: 28px; }
.section-label {
    font-size: 11px; font-weight: 700; color: #042e5a;
    text-transform: uppercase; letter-spacing: 1px;
    border-bottom: 1px solid #dee2e6; padding-bottom: 6px; margin-bottom: 14px;
}
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px 24px; }
.info-line { font-size: 13px; }
.info-line span { font-weight: 300; color: #666; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px; }
.info-line strong { color: #1a1f36; font-size: 15px; }

.plafond-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
.plafond-table th {
    background: #042e5a; color: #fff; padding: 11px 14px;
    text-align: left; font-weight: 600;
}
.plafond-table td { padding: 11px 14px; border-bottom: 1px solid #f0f0f0; }
.plafond-table tr:last-child td { border-bottom: none; }
.plafond-table .amount { font-weight: 700; color: #042e5a; font-size: 15px; }

.legal-text {
    background: #f8f9fa; border: 1px dashed #ced4da;
    border-radius: 6px; padding: 16px 18px;
    font-size: 11px; color: #555; line-height: 1.7;
}
.sig-row { display: flex; justify-content: space-between; margin-top: 60px; gap: 30px; }
.sig-box {
    flex: 1; text-align: center; padding-top: 14px;
    border-top: 1px dotted #042e5a;
    font-style: italic; font-size: 12px; color: #555; line-height: 1.6;
}
</style>

<div class="no-print">
    <div class="page-actions">
        <h2>
            <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">description</span>
            Contrat d'Engagement — Modification de Plafonds
        </h2>
        <div style="display:flex;gap:10px;">
            <button onclick="window.print()" class="btn-action btn-navy">
                <span class="material-symbols-rounded">print</span> Imprimer le Contrat
            </button>
            <a href="<?= BASE_URL ?>?controller=Admin&action=managePlafonds" class="btn-action btn-gray">
                <span class="material-symbols-rounded">arrow_back</span> Retour
            </a>
        </div>
    </div>
</div>

<div class="contrat">
    <div class="contrat-header">
        <div class="logo-brand">E-BANKING PRO</div>
        <div class="contrat-meta">
            <strong><?= APP_NAME ?></strong><br>
            Service de Gestion des Comptes<br>
            Émis le : <?= $data['date'] ?><br>
            Ref : CONT-PLAF-<?= date('Ymd') ?>-<?= str_pad($data['plafond']['compte_id'], 4, '0', STR_PAD_LEFT) ?>
        </div>
    </div>

    <div class="contrat-title">Contrat d'Engagement de Modification de Plafonds</div>
    <div class="contrat-subtitle">Document Officiel — À conserver dans le dossier client</div>

    <div class="section-block">
        <div class="section-label">I. Identification du Titulaire</div>
        <div class="info-grid">
            <div class="info-line">
                <span>Nom complet</span>
                <strong><?= strtoupper(htmlspecialchars($data['plafond']['nom'])) ?> <?= htmlspecialchars($data['plafond']['prenom']) ?></strong>
            </div>
            <div class="info-line">
                <span>Numéro de Compte</span>
                <strong><?= htmlspecialchars($data['plafond']['numero_compte']) ?></strong>
            </div>
        </div>
    </div>

    <div class="section-block">
        <div class="section-label">II. Nouveaux Plafonds Autorisés</div>
        <table class="plafond-table">
            <thead>
                <tr><th>Type de Plafond</th><th>Montant Autorisé (FCFA)</th><th>Période</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>Plafond de Retrait</td>
                    <td class="amount"><?= number_format($data['plafond']['plafond_retrait_journalier'] ?? 0, 0, ',', ' ') ?></td>
                    <td>Par Jour</td>
                </tr>
                <tr>
                    <td>Plafond de Dépôt</td>
                    <td class="amount"><?= number_format($data['plafond']['plafond_depot_journalier'] ?? 0, 0, ',', ' ') ?></td>
                    <td>Par Jour</td>
                </tr>
                <tr>
                    <td>Plafond de Transfert</td>
                    <td class="amount"><?= number_format($data['plafond']['plafond_transfert_mensuel'] ?? 0, 0, ',', ' ') ?></td>
                    <td>Par Mois</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section-block">
        <div class="section-label">III. Clauses d'Engagement</div>
        <div class="legal-text">
            Le titulaire du compte soussigné reconnaît et accepte les nouvelles limites opérationnelles définies ci-dessus.
            Il s'engage à respecter les conditions générales d'utilisation des services de <?= APP_NAME ?> et à
            informer l'institution de tout changement de situation. La présente autorisation peut être révoquée
            à tout moment par la direction de l'établissement en cas de suspicion de fraude ou d'utilisation abusive.
            Toute opération dépassant les plafonds définis fera l'objet d'un blocage automatique et d'une inscription
            au journal d'audit.
        </div>
    </div>

    <div class="sig-row">
        <div class="sig-box">
            Le Titulaire du Compte<br>
            <strong><?= strtoupper(htmlspecialchars($data['plafond']['nom'])) ?></strong>
        </div>
        <div class="sig-box">
            Le Directeur d'Agence<br>
            Cachet &amp; Signature
        </div>
    </div>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
