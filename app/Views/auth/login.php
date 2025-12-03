<?php
// Note : Dans un système complet, on inclurait ici les fichiers header et footer du layout.
// Pour la simplicité, nous fournissons le code HTML/CSS minimal pour le formulaire.
// Le message d'erreur $errorMessage est censé être disponible via le AuthController.
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Connexion - <?= APP_NAME ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 350px;
        }

        h1 {
            text-align: center;
            color: #007bff;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            /* Important pour que le padding n'augmente pas la taille totale */
        }

        .btn-primary {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h1><?= APP_NAME ?></h1>

        <?php if (isset($errorMessage) && $errorMessage): ?>
            <div class="error-message">
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>?controller=Auth&action=login">
            <div class="form-group">
                <label for="identifiant">Identifiant</label>
                <input type="text" id="identifiant" name="identifiant" required autofocus>
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de Passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="btn-primary">Se Connecter</button>


            <hr style="margin-top: 20px; border-color: #eee;">

            <p style="margin-top: 15px;">
                Vous êtes client ?
                <a href="<?= BASE_URL ?>?controller=ClientFront&action=login" style="font-weight: bold; color: #007bff;">
                    Accéder à l'Espace Client
                </a>
            </p>
        </form>
    </div>
</body>

</html>