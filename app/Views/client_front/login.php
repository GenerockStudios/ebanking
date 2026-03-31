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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/ebanking/src/css/responsive-core.css">

    <style>
        body {
            background-color: #f4f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: max(16px, env(safe-area-inset-top)) max(16px, env(safe-area-inset-right)) max(16px, env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left));
        }
        .login-card {
            background: #ffffff;
            border-radius: 16px;
            padding: clamp(2rem, 5vw, 3rem);
            box-shadow: 0 4px 12px rgba(0,0,0,.04), 0 12px 32px rgba(0,0,0,.08);
            border: 1px solid rgba(0,0,0,.05);
            width: 100%;
            max-width: 420px;
            text-align: center;
        }
        .login-card h2 { color: #042e5a; margin-bottom: 2rem; font-weight: 700; }
        .alert-error { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 24px; font-size: 0.9rem; border-left: 4px solid #dc3545; text-align: left; }
    </style>
</head>
<body>
    <!-- Système de toast inclus nativement si nécessaire -->
    <div id="toast-container"></div>
    <div class="login-card">
        <h2>🔒 L'Oasis — Espace Client</h2>

        <?php if (isset($data['error'])): ?>
            <div class="alert-error"><?= htmlspecialchars($data['error']) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>?controller=ClientFront&action=login" novalidate>
            <!-- Design pattern champs flottants via responsive-core.css -->
            <div class="form-group" style="text-align: left;">
                <input type="tel" id="numero_compte" name="numero_compte" inputmode="numeric" required placeholder=" " 
                       value="<?= htmlspecialchars($_POST['numero_compte'] ?? '') ?>">
                <label for="numero_compte">Numéro de Compte</label>
            </div>
            
            <div class="form-group" style="text-align: left; margin-bottom: 2rem;">
                <input type="password" id="mot_de_passe" name="mot_de_passe" required placeholder=" ">
                <label for="mot_de_passe">Mot de Passe sécurisé</label>
            </div>
            
            <button type="submit" class="btn-xl btn-primary">Se Connecter &rarr;</button>
        </form>
        
        <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #f0f0f0;">
            <p style="font-size: 0.85rem; color: #8792a2;">
                Collaborateur ? <a href="<?= BASE_URL ?>?controller=Auth&action=login" style="color: #042e5a; font-weight: 600; text-decoration: none;">Accès Staff interne</a>
            </p>
        </div>
    </div>
</body>
</html>