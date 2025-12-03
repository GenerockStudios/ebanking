<?php
// Protection : s'assurer que l'utilisateur est connecté pour inclure le header sécurisé
if (!AuthController::isLoggedIn()) {
    header("Location: " . BASE_URL . "?controller=Auth&action=login");
    exit();
}

// Variables de session pour les rôles
$userName = $_SESSION['nom_complet'] ?? 'Utilisateur';
$roleName = $_SESSION['role_name'] ?? 'Invité';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?? APP_NAME ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* CSS de Base pour la Caisse/Admin */
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f4f9; }
        .header { background-color: #007bff; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); }
        .header a { color: white; text-decoration: none; margin-left: 20px; font-weight: bold; }
        .header a:hover { text-decoration: underline; }
        .user-info { font-size: 0.9em; }
        .content { padding: 20px; max-width: 1200px; margin: 20px auto; background: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.05); }
        h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 0; }
        /* Style pour les messages */
        .alert-success { background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="header">
        <nav>
            <a href="<?= BASE_URL ?>?controller=Caisse&action=dashboard">Accueil</a>
            
            <?php if ($roleName === 'Caissier' || $roleName === 'Admin'): ?>
                <a href="<?= BASE_URL ?>?controller=Caisse&action=depot">Dépôt</a>
                <a href="<?= BASE_URL ?>?controller=Caisse&action=retrait">Retrait</a>
                <a href="<?= BASE_URL ?>?controller=Caisse&action=transfert">Transfert Interne</a>
            <?php endif; ?>
            
            <?php if ($roleName === 'Superviseur' || $roleName === 'Admin'): ?>
                <a href="<?= BASE_URL ?>?controller=Rapport&action=rapportTransactions">Rapports</a>
                <a href="<?= BASE_URL ?>?controller=Rapport&action=clotureJournee">Clôture Journée</a>
            <?php endif; ?>

            <?php if ($roleName === 'Admin'): ?>
                <a href="<?= BASE_URL ?>?controller=Admin&action=manageUsers">Admin Utilisateurs</a>
            <?php endif; ?>
        </nav>
        <div class="user-info">
            Connecté: **<?= htmlspecialchars($userName) ?>** (<?= $roleName ?>) | 
            <a href="<?= BASE_URL ?>?controller=Auth&action=logout">Déconnexion</a>
        </div>
    </div>
    <div class="content">