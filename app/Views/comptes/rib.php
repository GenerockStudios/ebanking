<?php
/**
 * rib.php — Relevé d'Identité Bancaire (Document Imprimable)
 */
require_once VIEW_PATH . 'layout/header.php';

$compte = $data['compte'];
$rib    = $data['rib'];
?>

<style>
@media print {
    @page { size: A4 portrait; margin: 1.5cm; }
    .no-print { display: none !important; }
    .sidebar, footer { display: none !important; }
    .content { padding: 0 !important; box-shadow: none !important; width: 100% !important; padding-left: 0 !important; }
    * { box-shadow: none !important; }
}
.no-print .page-actions { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.no-print .page-actions h2 { margin:0; color:#042e5a; font-size:1.4rem; font-weight:700; border:none; padding:0; }
.btn-action {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 20px; border-radius:6px; border:none; cursor:pointer;
    font-weight:600; font-size:14px; text-decoration:none; transition:0.2s ease;
}
.btn-navy  { background:#042e5a; color:#fff; } .btn-navy:hover  { background:#021d3a; }
.btn-green { background:#28a745; color:#fff; } .btn-green:hover { background:#1e7e34; }
.btn-gray  { background:#6c757d; color:#fff; } .btn-gray:hover  { background:#545b62; }

.doc-wrapper {
    background:#fff; border:2px solid #2c3e50;
    border-radius:8px; padding:36px;
    max-width:800px; margin:0 auto;
}
.doc-header {
    display:flex; justify-content:space-between; align-items:center;
    border-bottom:3px double #2c3e50; padding-bottom:18px; margin-bottom:26px;
}
.logo-brand { font-size:24px; font-weight:900; color:#042e5a; }
.doc-title  { font-size:22px; font-weight:800; text-transform:uppercase; letter-spacing:3px; color:#2c3e50; }
.doc-meta   { text-align:right; font-size:12px; color:#555; line-height:1.6; }

.titulaire-block { margin-bottom:32px; }
.info-label { font-size:11px; text-transform:uppercase; color:#888; font-weight:600; margin-bottom:4px; }
.info-value { font-size:18px; font-weight:700; color:#1a1f36; }
.info-sub   { font-size:13px; color:#666; margin-top:4px; }

.rib-table { width:100%; border-collapse:collapse; margin-bottom:24px; }
.rib-table thead th {
    background:#042e5a; color:#fff; text-align:center;
    padding:12px; font-size:12px; text-transform:uppercase; letter-spacing:.5px;
}
.rib-table tbody td {
    border:2px solid #dee2e6; padding:16px;
    text-align:center; font-family:'Courier New', Courier, monospace;
    font-size:22px; font-weight:700; letter-spacing:3px; color:#042e5a;
}

.iban-box {
    background:#f1f5f9; border-left:5px solid #042e5a;
    border-radius:0 8px 8px 0; padding:18px 22px; margin-bottom:24px;
}
.iban-box .iban-label { font-size:11px; text-transform:uppercase; color:#777; font-weight:600; margin-bottom:8px; }
.iban-box .iban-val {
    font-family:'Courier New', Courier, monospace;
    font-size:20px; font-weight:700; color:#042e5a;
    letter-spacing:2px;
}
.iban-box .bic-val { font-size:14px; color:#555; margin-top:8px; }

.legal-text { font-size:10px; color:#888; font-style:italic; line-height:1.6; margin-bottom:24px; text-align:justify; }

.sig-row { display:flex; justify-content:space-between; margin-top:60px; gap:20px; }
.sig-box { flex:1; text-align:center; padding-top:12px; border-top:1px dotted #2c3e50; font-style:italic; font-size:12px; color:#555; }
</style>

<!-- Actions no-print -->
<div class="no-print">
    <div class="page-actions">
        <h2>
            <span class="material-symbols-rounded" style="vertical-align:middle;color:#042e5a;">account_balance</span>
            Relevé d'Identité Bancaire (RIB / IBAN)
        </h2>
        <div style="display:flex;gap:10px;">
            <button onclick="window.print()" class="btn-action btn-green">
                <span class="material-symbols-rounded">print</span> Imprimer
            </button>
            <a href="<?= BASE_URL ?>?controller=Caisse&action=dashboard" class="btn-action btn-gray">
                <span class="material-symbols-rounded">arrow_back</span> Retour
            </a>
        </div>
    </div>
</div>

<!-- Document officiel -->
<div class="doc-wrapper">
    <div class="doc-header">
        <div class="logo-brand">E-BANKING PRO</div>
        <div class="doc-title">RIB / IBAN</div>
        <div class="doc-meta">
            <strong><?= APP_NAME ?></strong><br>
            Swift/BIC : <?= $rib['bic'] ?><br>
            Édité le : <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>

    <!-- Titulaire -->
    <div class="titulaire-block">
        <div class="info-label">Titulaire du Compte</div>
        <div class="info-value"><?= strtoupper(htmlspecialchars($compte->nom)) ?> <?= htmlspecialchars($compte->prenom) ?></div>
        <div class="info-sub"><?= nl2br(htmlspecialchars($compte->adresse ?? '')) ?></div>
        <div class="info-sub" style="margin-top:10px;">
            <span style="background:#e8f0fe;color:#1a56db;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">
                <?= htmlspecialchars($compte->type_compte ?? '') ?>
            </span>
        </div>
    </div>

    <!-- Tableau RIB -->
    <table class="rib-table">
        <thead>
            <tr>
                <th>Code Banque</th>
                <th>Code Guichet</th>
                <th>N° de Compte</th>
                <th>Clé RIB</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= $rib['banque'] ?></td>
                <td><?= $rib['guichet'] ?></td>
                <td><?= $rib['compte'] ?></td>
                <td><?= $rib['cle'] ?></td>
            </tr>
        </tbody>
    </table>

    <!-- IBAN -->
    <div class="iban-box">
        <div class="iban-label">Identifiant International de Compte Bancaire (IBAN)</div>
        <div class="iban-val"><?= $rib['iban'] ?></div>
        <div class="bic-val">BIC / SWIFT : <strong><?= $rib['bic'] ?></strong></div>
    </div>

    <!-- Mentions légales -->
    <p class="legal-text">
        Ce document est délivré par <?= APP_NAME ?> pour servir et valoir ce que de droit.
        Il permet de communiquer vos coordonnées bancaires pour la domiciliation de vos virements ou prélèvements.
        Toute reproduction non autorisée est passible de sanctions conformément aux règlements bancaires en vigueur.
    </p>

    <!-- Signatures -->
    <div class="sig-row">
        <div class="sig-box">Signature du Titulaire</div>
        <div class="sig-box">Cachet de l'Agence et Signature</div>
    </div>
</div>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
