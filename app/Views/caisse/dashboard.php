<?php
/**
 * dashboard.php
 * Tableau de bord principal pour les utilisateurs connectés (Caissier/Superviseur/Admin).
 * Cette vue est chargée par CaisseController->dashboard().
 * Reçoit $data['title'] et $data['user_role'].
 */

// Inclure le header
require_once VIEW_PATH . 'layout/header.php';

// Déterminer le rôle de l'utilisateur pour personnaliser le contenu
$roleName = $data['user_role'] ?? 'Utilisateur'; // Supposons que le contrôleur passe le nom du rôle
$identifiant = $_SESSION['identifiant'] ?? 'N/A';
?>

<h2>Tableau de Bord</h2>

<hr>

<div class="grid-container">
    
    <?php if (in_array($roleName, ['Caissier', 'Superviseur', 'Admin'])): ?>
        <div class="card operation-card">
            <h3>Opérations de Caisse</h3>
            <ul>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=depot">💵 Dépôt d'Espèces</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=retrait">➖ Retrait d'Espèces</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=transfert">🔁 Transfert Interne</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=releve"><i class="fas fa-file-invoice"></i> Relevé de Compte Détaillé</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=cloture"><i class="fas fa-lock"></i> Clôture de Caisse (Session)</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Caisse&action=simulation"><i class="fas fa-chart-line"></i> Simulateur d'Épargne</a></li>
                <li><a href="#" onclick="const num = prompt('Saisir le numéro de compte :'); if(num) window.location.href='<?= BASE_URL ?>?controller=Caisse&action=rib&numero_compte='+num;"><i class="fas fa-university"></i> Édition de RIB (RIB/IBAN)</a></li>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (in_array($roleName, ['Caissier', 'Admin'])): ?>
        <div class="card client-card">
            <h3>Gestion Client</h3>
            <ul class="list-style-none">
                <li><p>Ouvrir de nouveaux comptes <br> et gérer le KYC.</p></li>
                <li><p> .</p></li>
                <li><a href="<?= BASE_URL ?>?controller=Client&action=nouveauClient" class="btn-action">Créer Nouveau Client</a></li>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (in_array($roleName, ['Superviseur', 'Admin'])): ?>
        <div class="card report-card">
            <h3>Supervision & Audit</h3>
            <ul>
                <li><a href="<?= BASE_URL ?>?controller=Rapport&action=rapportTransactions">📊 Rapport Transactions</a></li>
                <li><a href="<?= BASE_URL ?>?controller=Rapport&action=clotureJournee">🔒 Clôture de Journée</a></li>
                <?php if ($roleName === 'Admin'): ?>
                    <li><a href="<?= BASE_URL ?>?controller=Admin&action=manageUsers">👑 Gestion Utilisateurs</a></li>
                    <li><a href="<?= BASE_URL ?>?controller=Admin&action=manageClients">👑 Gestion Clients</a></li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>
    
</div>


<style>
/* Styles spécifiques pour le tableau de bord */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}
.card {
    border: 1px solid #ddd;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    background-color: #fff;
}
.card h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
    color: #007bff;
}
.btn-action {
    display: block;
    padding: 8px;
    margin-top: 10px;
    background-color: #28a745;
    color: white;
    text-align: center;
    border-radius: 4px;
    text-decoration: none;
}
.btn-action:hover {
    background-color: #1e7e34;
}
.operation-card ul, .report-card ul {
    list-style: none;
    padding: 0;
}
.operation-card li, .report-card li {
    margin-bottom: 8px;
    font-size: 18px;
}
.operation-card a, .report-card a {
    text-decoration: none;
    color: #333;
    font-weight: 500;
}
.operation-card a:hover, .report-card a:hover {
    color: #007bff;
}

.list-style-none {
    list-style: none;
}


@import url('https://pro.fontawesome.com/releases/v6.0.0-beta1/css/all.css');
  

.grid-container {
  width: min(75rem, 100%);
  margin-inline: auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(min(24rem, 100%), 1fr));
  gap: 2rem;
}
.card {
  --grad: red, blue;
  padding: 2.5rem;
  background-image: linear-gradient(to bottom left, #ffffff, #f2f6f9);
  border-radius: 2rem;
  gap: 1.5rem;
  display: grid;
  grid-template: 'title icon' 'content content' 'bar bar' / 1fr auto;
  font-family: system-ui, sans-serif;
  color: #444447;
  box-shadow: 
    inset -2px 2px hsl(0 0 100% / 1),
    -20px 20px 40px hsl(0 0 0 / .25) ;
  
  .title {
    font-size: 1.5rem;
    grid-area: title;
    align-self: end;
    text-transform: uppercase;
    font-weight: 500;
    word-break: break-all;
    
  }
  .icon {
    grid-area: icon;
    font-size: 3rem;
    
    > i {
      color: transparent;
      background: linear-gradient(to right, var(--grad));
      background-clip: text;
    }
  }
  .content {
    grid-area: content;
    & > *:first-child { margin-top: 0rem}
    & > *:last-child { margin-bottom: 0rem}
  }
  &::after {
    content: "";
    grid-area: bar;
    height: 2px;
    background-image: linear-gradient(90deg, var(--grad));
/*     margin-inline: -1.5rem; */
  }
}

</style>

<?php
// Inclure le footer
require_once VIEW_PATH . 'layout/footer.php';
?>