<?php
/**
 * login.php
 * Formulaire de connexion pour l'accès client (consultation en ligne).
 * Cette vue est chargée par ClientFrontController->login().
 */

// Pas de header/footer interne du caissier ici, on utilise un layout minimal
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $data['title'] ?? APP_NAME . " - Accès Client" ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background-color: #e9eff5; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); width: 350px; text-align: center; }
        h2 { color: #007bff; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; text-align: left; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 0.9em; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-login { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 1.1em; margin-top: 15px; }
        .btn-login:hover { background-color: #0056b3; }
        .alert-error { background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Accès Espace Client</h2>

        <?php if (isset($data['error'])): ?>
            <div class="alert-error"><?= htmlspecialchars($data['error']) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>?controller=ClientFront&action=login">
            <div class="form-group">
                <label for="numero_compte">Numéro de Compte</label>
                <input type="text" id="numero_compte" name="numero_compte" class="form-control" required 
                       value="<?= htmlspecialchars($_POST['numero_compte'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de Passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required>
            </div>
            <button type="submit" class="btn-login">Se Connecter</button>
        </form>
        <p style="margin-top: 20px; font-size: 0.8em;"><a href="<?= BASE_URL ?>?controller=Auth&action=login">Accès Caissier / Admin</a></p>
    </div>
</body>
</html>