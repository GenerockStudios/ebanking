<?php
/**
 * manage_users.php
 * Vue pour la gestion des utilisateurs (création, liste) par l'administrateur.
 * Reçoit $data['title'], $data['users'], $data['roles'], $data['success'], et $data['error'].
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= $data['title'] ?? "Gestion des Utilisateurs" ?></h2>

<?php if (isset($data['success'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['success']) ?>", 'success'));</script>
<?php endif; ?>
<?php if (isset($data['error'])): ?>
    <script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['error']) ?>", 'error'));</script>
<?php endif; ?>

<!-- ===== Formulaire de création ===== -->
<div class="flex-justify-center" style="margin-bottom:32px;">
    <div class="form-container">
        <div class="card">
            <div class="card-header">
                <div style="margin-bottom:20px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#635BFF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <line x1="19" y1="8" x2="19" y2="14"/>
                        <line x1="22" y1="11" x2="16" y2="11"/>
                    </svg>
                </div>
                <h1>Créer un Nouvel Utilisateur</h1>
                <p>Renseignez les informations du compte système</p>
            </div>

            <form method="POST" action="<?= BASE_URL ?>?controller=Admin&action=manageUsers">
                <input type="hidden" name="action" value="create">

                <div class="input-row">
                    <div class="input-group">
                        <input type="text" id="identifiant" name="identifiant" required placeholder=" "
                               value="<?= htmlspecialchars($_POST['identifiant'] ?? '') ?>">
                        <label for="identifiant">Identifiant (Login)</label>
                    </div>
                    <div class="input-group">
                        <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="8" placeholder=" ">
                        <label for="mot_de_passe">Mot de Passe Initial</label>
                    </div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <input type="text" id="nom_complet" name="nom_complet" required placeholder=" "
                               value="<?= htmlspecialchars($_POST['nom_complet'] ?? '') ?>">
                        <label for="nom_complet">Nom Complet</label>
                    </div>
                    <div class="select-group">
                        <label for="role_id">Rôle</label>
                        <select id="role_id" name="role_id" class="form-select" required>
                            <option value="">-- Choisir un rôle --</option>
                            <?php foreach ($data['roles'] ?? [] as $id => $name):
                                $selected = (isset($_POST['role_id']) && (int)$_POST['role_id'] === $id) ? 'selected' : ''; ?>
                                <option value="<?= $id ?>" <?= $selected ?>><?= htmlspecialchars($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="submit-btn">Créer l'Utilisateur</button>
            </form>
        </div>
    </div>
</div>

<!-- ===== Filtres & Recherche ===== -->
<div class="filter-card">
    <div class="filter-form">
        <div class="filter-group">
            <label>Rechercher</label>
            <input type="text" id="searchInput" class="form-control" placeholder="Identifiant, nom, rôle...">
        </div>
        <div class="filter-group">
            <label>Statut</label>
            <select id="filterStatus" class="form-control">
                <option value="">Tous</option>
                <option value="actif">Actif</option>
                <option value="inactif">Inactif</option>
            </select>
        </div>
        <div class="filter-group" style="align-self:flex-end;">
            <button type="button" class="btn-filter" onclick="resetFilters()">Réinitialiser</button>
        </div>
    </div>
</div>

<p class="result-count"><strong id="rowCount"><?= count($data['users'] ?? []) ?></strong> utilisateur(s) trouvé(s).</p>

<!-- ===== Tableau des utilisateurs ===== -->
<div class="table-scroll-wrap">
    <table class="data-table" id="userTable" style="min-width:800px;">
        <thead>
            <tr>
                <th>#ID</th>
                <th>Identifiant</th>
                <th>Nom Complet</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Créé le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($data['users'])): ?>
                <?php foreach ($data['users'] as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['utilisateur_id']) ?></td>
                    <td><strong><?= htmlspecialchars($user['identifiant']) ?></strong></td>
                    <td><?= htmlspecialchars($user['nom_complet']) ?></td>
                    <td><?= htmlspecialchars($data['roles'][$user['role_id']] ?? 'Inconnu') ?></td>
                    <td>
                        <?php if ($user['est_actif']): ?>
                            <span class="badge-active">ACTIF</span>
                        <?php else: ?>
                            <span class="badge-inactive">INACTIF</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($user['date_creation'])) ?></td>
                    <td>
                        <a href="#" class="btn-sm-danger" title="Désactiver">Désactiver</a>
                        <a href="#" class="btn-sm-secondary" title="Réinitialiser MDP">Reset MDP</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="empty-row">Aucun utilisateur trouvé.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
/* ---- Formulaire ---- */
.flex-justify-center { display: flex; justify-content: center; }
.form-container { width: 100%; max-width: 680px; }

.card {
    background: #ffffff;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 2px 4px rgba(0,0,0,.02), 0 8px 16px rgba(0,0,0,.06);
    border: 1px solid rgba(0,0,0,.05);
}
.card-header { text-align: center; margin-bottom: 32px; }
.card-header h1 { color: #1a1f36; font-size: 1.5rem; font-weight: 600; margin-bottom: 8px; }
.card-header p  { color: #8792a2; font-size: 14px; }

.input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

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

/* ---- Filtres ---- */
.filter-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    padding: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 24px;
}
.filter-form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}
.filter-group { display: flex; flex-direction: column; gap: 8px; }
.filter-group label { font-weight: 600; font-size: 13px; color: #64748b; }
.form-control {
    padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 8px;
    font-size: 15px; color: #0f172a; outline: none; transition: border-color .2s;
    width: 100%; box-sizing: border-box; min-height: 48px;
}
.form-control:focus { border-color: #635BFF; box-shadow: 0 0 0 3px rgba(99,91,255,0.1); }
.btn-filter {
    padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 15px;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; border: none; min-height: 48px; background: #042e5a; color: #fff; width: 100%;
}
.btn-filter:hover { background: #021d3a; }
.result-count { margin: 10px 0; color: #64748b; font-size: 14px; }

/* ---- Tableau ---- */
.table-scroll { overflow-x: auto; }
.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.07);
}
.data-table th { background: #042e5a; color: #fff; padding: 11px 12px; text-align: left; white-space: nowrap; }
.data-table td { padding: 9px 12px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.data-table tr:hover td { background: #f9f9fb; }

.badge-active   { background: #d4edda; color: #155724; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
.badge-inactive { background: #f8d7da; color: #721c24; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }

.btn-sm-danger    { display: inline-block; padding: 4px 10px; background: #dc3545; color: #fff; border-radius: 5px; font-size: 11px; font-weight: 600; text-decoration: none; margin-right: 4px; }
.btn-sm-danger:hover { background: #c82333; }
.btn-sm-secondary { display: inline-block; padding: 4px 10px; background: #6c757d; color: #fff; border-radius: 5px; font-size: 11px; font-weight: 600; text-decoration: none; }
.btn-sm-secondary:hover { background: #545b62; }

.empty-row { text-align: center; color: #999; padding: 24px; }
</style>

<script>
const searchInput  = document.getElementById('searchInput');
const filterStatus = document.getElementById('filterStatus');
const userTable    = document.getElementById('userTable');
const rowCount     = document.getElementById('rowCount');

function filterTable() {
    const query  = searchInput.value.toLowerCase();
    const status = filterStatus.value.toLowerCase();
    const rows   = userTable.querySelectorAll('tbody tr');
    let visible  = 0;
    rows.forEach(row => {
        const text      = row.textContent.toLowerCase();
        const badge     = row.querySelector('[class^="badge-"]');
        const rowStatus = badge ? badge.textContent.toLowerCase().trim() : '';
        const matchQ    = !query  || text.includes(query);
        const matchS    = !status || rowStatus === status;
        row.style.display = (matchQ && matchS) ? '' : 'none';
        if (matchQ && matchS) visible++;
    });
    rowCount.textContent = visible;
}

function resetFilters() {
    searchInput.value  = '';
    filterStatus.value = '';
    filterTable();
}

searchInput.addEventListener('input', filterTable);
filterStatus.addEventListener('change', filterTable);
</script>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
