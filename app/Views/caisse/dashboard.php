<?php
/**
 * dashboard.php
 * Tableau de bord principal (Caissier/Superviseur/Admin).
 */
require_once VIEW_PATH . 'layout/header.php';

$roleName = $data['user_role'] ?? 'Utilisateur';
$identifiant = $_SESSION['identifiant'] ?? 'N/A';
?>

<!-- FontAwesome pour les icônes du dashboard -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<div class="dashboard-wrapper">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; border-bottom:2px solid #f0f4f8; padding-bottom:12px; margin-bottom:24px;">
        <div>
            <h2>Tableau de Bord</h2>
            <p style="color:#8792a2; font-size:14px; margin-top:4px;">Plateforme d'opérations &mdash; Session: <strong><?= htmlspecialchars($roleName) ?></strong></p>
        </div>
    </div>

    <!-- Grille Responsive CSS : auto-fit minmax -->
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap: 24px;">
        
        <?php if (in_array($roleName, ['Caissier', 'Superviseur', 'Admin'])): ?>
            <!-- Carte Opérations -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="dash-icon" style="background:rgba(4, 46, 90, 0.1); color:#042e5a;"><i class="fa-solid fa-cash-register"></i></div>
                    <h3>Guichet / Caisse</h3>
                </div>
                <div class="dash-card-body">
                    <ul class="dash-menu">
                        <li><a href="<?= BASE_URL ?>?controller=Caisse&action=depot"><i class="fa-solid fa-arrow-turn-down"></i> Dépôt d'Espèces</a></li>
                        <li><a href="<?= BASE_URL ?>?controller=Caisse&action=retrait"><i class="fa-solid fa-arrow-turn-up"></i> Retrait d'Espèces</a></li>
                        <li><a href="<?= BASE_URL ?>?controller=Caisse&action=transfert"><i class="fa-solid fa-right-left"></i> Transfert Interne</a></li>
                        <li><a href="<?= BASE_URL ?>?controller=Caisse&action=releve"><i class="fa-solid fa-file-invoice"></i> Relevé Détaillé</a></li>
                        <li><a href="<?= BASE_URL ?>?controller=Caisse&action=cloture" style="color:#dc3545;"><i class="fa-solid fa-lock"></i> Arrêté de Caisse</a></li>
                    </ul>
                    <hr style="border:0; border-top:1px solid #f0f0f0; margin:16px 0;">
                    <ul class="dash-menu">
                        <li><a href="<?= BASE_URL ?>?controller=Caisse&action=simulation"><i class="fa-solid fa-chart-line"></i> Simulateur d'Épargne</a></li>
                        <li><a href="#" onclick="const num = prompt('Saisir le numéro de compte :'); if(num) window.location.href='<?= BASE_URL ?>?controller=Caisse&action=rib&numero_compte='+num;"><i class="fa-solid fa-university"></i> Édition de RIB</a></li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($roleName, ['Caissier', 'Admin'])): ?>
            <!-- Carte Gestion Client -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="dash-icon" style="background:rgba(40, 167, 69, 0.1); color:#28a745;"><i class="fa-solid fa-users"></i></div>
                    <h3>Gestion Client</h3>
                </div>
                <div class="dash-card-body" style="display:flex; flex-direction:column; justify-content:space-between; height:calc(100% - 60px);">
                    <p style="color:#666; font-size:0.95rem; line-height:1.5;">Ouverture de nouveaux comptes clients et gestion de la conformité réglementaire (KYC).</p>
                    <a href="<?= BASE_URL ?>?controller=Client&action=nouveauClient" class="btn-xl btn-primary" style="text-align:center; background:#28a745; margin-top:24px;">+ Nouveau Client</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (in_array($roleName, ['Superviseur', 'Admin'])): ?>
            <!-- Carte Supervision -->
            <div class="dash-card">
                <div class="dash-card-header">
                    <div class="dash-icon" style="background:rgba(108, 117, 125, 0.1); color:#6c757d;"><i class="fa-solid fa-shield-halved"></i></div>
                    <h3>Supervision</h3>
                </div>
                <div class="dash-card-body">
                    <ul class="dash-menu">
                        <li><a href="<?= BASE_URL ?>?controller=Rapport&action=rapportTransactions"><i class="fa-solid fa-chart-pie"></i> Rapport Transactions</a></li>
                        <li><a href="<?= BASE_URL ?>?controller=Rapport&action=clotureJournee"><i class="fa-solid fa-calendar-check"></i> Clôture de Journée</a></li>
                        
                        <?php if ($roleName === 'Admin'): ?>
                            <hr style="border:0; border-top:1px solid #f0f0f0; margin:16px 0;">
                            <li><a href="<?= BASE_URL ?>?controller=Admin&action=manageUsers" style="color:#042e5a; font-weight:600;"><i class="fa-solid fa-user-tie"></i> Gestion Staff</a></li>
                            <li><a href="<?= BASE_URL ?>?controller=Admin&action=manageClients" style="color:#042e5a; font-weight:600;"><i class="fa-solid fa-address-book"></i> Base Clients 360</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
.dash-card {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    border: 1px solid rgba(0,0,0,0.04);
    display: flex;
    flex-direction: column;
}
.dash-card-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f8f9fa;
}
.dash-card-header h3 { font-size: 1.15rem; font-weight: 600; color: #1a1f36; margin: 0; }
.dash-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
}
.dash-menu { list-style: none; padding: 0; margin: 0; }
.dash-menu li { margin-bottom: 8px; }
.dash-menu li:last-child { margin-bottom: 0; }
.dash-menu a {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    text-decoration: none; color: #4a5568;
    background: #f8fafc; border-radius: 8px;
    font-weight: 500; font-size: 0.95rem;
    transition: all 0.2s;
}
.dash-menu a i { width: 20px; text-align: center; color: #8792a2; }
.dash-menu a:hover { background: #f1f5f9; transform: translateX(4px); color: #042e5a; }
.dash-menu a:hover i { color: inherit; }
</style>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>