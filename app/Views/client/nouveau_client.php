<?php
/**
 * nouveau_client.php
 * Vue pour la création d'un nouveau client et l'ouverture d'un compte.
 * Reçoit $data['title'], $data['success'], $data['error'] et $data['account_types'].
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= $data['title'] ?? "Nouveau Client" ?></h2>

<?php if (isset($data['success'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['success']) ?>", 'success'));</script>
<?php endif; ?>
<?php if (isset($data['error'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['error']) ?>", 'error'));</script>
<?php endif; ?>

<div class="flex-justify-center">
    <div class="form-container">
        <div class="card">
            <div class="card-header">
                <div style="margin-bottom:20px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#635BFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <h1>Nouveau Client</h1>
                <p>Veuillez renseigner les informations du client</p>
            </div>

            <form method="POST" action="<?= BASE_URL ?>?controller=Client&action=nouveauClient">

                <p class="section-label">1. Informations Personnelles (KYC)</p>

                <div class="input-row">
                    <div class="input-group">
                        <input type="text" id="nom" name="nom" required placeholder=" "
                               value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                        <label for="nom">Nom</label>
                    </div>
                    <div class="input-group">
                        <input type="text" id="prenom" name="prenom" required placeholder=" "
                               value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                        <label for="prenom">Prénom(s)</label>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <input type="date" id="date_naissance" name="date_naissance" required placeholder=" "
                               value="<?= htmlspecialchars($_POST['date_naissance'] ?? '') ?>">
                        <label for="date_naissance">Date de Naissance</label>
                    </div>
                    <div class="input-group">
                        <input type="text" id="numero_identite" name="numero_identite" required placeholder=" "
                               value="<?= htmlspecialchars($_POST['numero_identite'] ?? '') ?>">
                        <label for="numero_identite">N° Pièce d'Identité (CNI/Passeport)</label>
                    </div>
                </div>

                <p class="section-label">2. Coordonnées</p>

                <div class="input-group">
                    <input type="text" id="adresse" name="adresse" placeholder=" "
                           value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
                    <label for="adresse">Adresse Complète</label>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <input type="tel" id="telephone" name="telephone" placeholder=" "
                               value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                        <label for="telephone">Téléphone</label>
                    </div>
                    <div class="input-group">
                        <input type="email" id="email" name="email" placeholder=" "
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <label for="email">Email</label>
                    </div>
                </div>

                <p class="section-label">3. Ouverture de Compte Initial</p>

                <div class="select-group">
                    <label for="type_compte_id">Type de Compte à Ouvrir</label>
                    <select id="type_compte_id" name="type_compte_id" class="form-select" required>
                        <option value="">-- Choisir un type --</option>
                        <?php foreach ($data['account_types'] ?? [] as $id => $name):
                            $selected = (isset($_POST['type_compte_id']) && (int)$_POST['type_compte_id'] === $id) ? 'selected' : ''; ?>
                            <option value="<?= $id ?>" <?= $selected ?>><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="hidden" name="utilisateur_id" value="<?= $_SESSION['user_id'] ?>">
                <button type="submit" class="submit-btn">Créer le Client &amp; Ouvrir le Compte</button>
            </form>
        </div>
    </div>
</div>

<style>
.flex-justify-center { display: flex; justify-content: center; }
.form-container { width: 100%; max-width: 680px; }

.card {
    background: #ffffff;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 4px rgba(0,0,0,.02), 0 8px 16px rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.05);
}

.card-header {
    text-align: center;
    margin-bottom: 32px;
}
.card-header h1 { color: #1a1f36; font-size: 1.5rem; font-weight: 600; margin-bottom: 8px; }
.card-header p  { color: #8792a2; font-size: 14px; }

.section-label {
    font-size: 11px;
    font-weight: 700;
    color: #635BFF;
    text-transform: uppercase;
    letter-spacing: .06em;
    margin: 20px 0 14px;
    padding-bottom: 6px;
    border-bottom: 1px solid #f0f0f0;
}

.input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* Floating Labels */
.input-group { position: relative; margin-bottom: 20px; }

.input-group input {
    width: 100%;
    background: #ffffff;
    border: 1px solid #e3e8ee;
    border-radius: 6px;
    padding: 16px 14px 8px 14px;
    color: #1a1f36;
    font-size: 16px;
    outline: none;
    transition: all .2s ease;
    box-sizing: border-box;
}
.input-group input:focus { border-color: #635BFF; }

.input-group label {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #8792a2;
    font-size: 16px;
    pointer-events: none;
    transition: all .2s ease;
    background: #ffffff;
    padding: 0 4px;
}
.input-group input:focus + label,
.input-group input:not(:placeholder-shown) + label {
    top: 0;
    font-size: 12px;
    font-weight: 500;
    color: #635BFF;
    transform: translateY(-50%);
}

/* Select stylé */
.select-group { margin-bottom: 20px; }
.select-group > label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #635BFF;
    margin-bottom: 6px;
}
.form-select {
    width: 100%;
    border: 1px solid #e3e8ee;
    border-radius: 6px;
    padding: 12px 14px;
    color: #1a1f36;
    font-size: 15px;
    outline: none;
    background: #ffffff;
    transition: border-color .2s ease;
    box-sizing: border-box;
}
.form-select:focus { border-color: #635BFF; }

.submit-btn {
    width: 100%;
    background: #635BFF;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    padding: 14px 20px;
    cursor: pointer;
    font-size: 16px;
    font-weight: 600;
    transition: all .2s ease;
    box-shadow: 0 4px 12px rgba(99,91,255,.2);
    margin-top: 8px;
}
.submit-btn:hover {
    background: #4c44d4;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99,91,255,.4);
}
</style>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
