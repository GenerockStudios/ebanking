<?php
/**
 * manage_clients.php
 * Liste des clients avec solde, compte et bouton Suspendre/Reactiver.
 */
require_once VIEW_PATH . 'layout/header.php';
?>

<h2><?= htmlspecialchars($data['title'] ?? "Gestion des Clients") ?></h2>

<?php if (isset($_GET['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($_GET['success']) ?>", 'success'));</script>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($_GET['error']) ?>", 'error'));</script>
<?php endif; ?>
<?php if (isset($data['success'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['success']) ?>", 'success'));</script>
<?php endif; ?>

<div class="top-actions">
    <a href="<?= BASE_URL ?>?controller=Client&action=nouveauClient" class="btn-action-green">+ Creer Nouveau Client</a>
</div>

<!-- ===== Filtres & Recherche ===== -->
<div class="filter-card">
    <div class="filter-form">
        <div class="filter-group">
            <label>Rechercher</label>
            <input type="text" id="searchInput" class="form-control" placeholder="Nom, téléphone, email, N° compte...">
        </div>
        <div class="filter-group">
            <label>Statut</label>
            <select id="filterStatus" class="form-control">
                <option value="">Tous</option>
                <option value="actif">Actif</option>
                <option value="suspendu">Suspendu</option>
            </select>
        </div>
        <div class="filter-group" style="align-self:flex-end;">
            <button type="button" class="btn-filter" onclick="resetFilters()">Réinitialiser</button>
        </div>
    </div>
</div>

<p class="result-count"><strong id="rowCount"><?= count($data['users'] ?? []) ?></strong> client(s) trouvé(s).</p>

<div class="table-scroll-wrap">
<table class="data-table" id="clientTable" style="min-width: 1000px;">
    <thead>
        <tr>
            <th>#ID</th>
            <th>Nom &amp; Prenom</th>
            <th>Telephone</th>
            <th>Email</th>
            <th>N&deg; Identite</th>
            <th>Adresse</th>
            <th>N&deg; Compte</th>
            <th>Solde (FCFA)</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!empty($data['users'])): ?>
        <?php foreach ($data['users'] as $u): ?>
        <tr class="<?= !empty($u['est_suspendu']) ? 'row-suspended' : '' ?>">
            <td><?= htmlspecialchars($u['client_id']) ?></td>
            <td>
                <strong><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></strong><br>
                <small class="text-muted"><?= htmlspecialchars(date('d/m/Y', strtotime($u['date_naissance']))) ?></small>
            </td>
            <td><?= htmlspecialchars($u['telephone']) ?></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['numero_identite']) ?></td>
            <td><?= htmlspecialchars($u['adresse']) ?></td>
            <td class="compte-num"><?= htmlspecialchars($u['numero_compte']) ?></td>
            <td class="montant-cell"><?= number_format((float)$u['solde'], 2, ',', ' ') ?></td>
            <td>
                <?php if (!empty($u['est_suspendu'])): ?>
                    <span class="badge-suspended">SUSPENDU</span>
                <?php else: ?>
                    <span class="badge-active">ACTIF</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= BASE_URL ?>?controller=Admin&action=ficheClient&client_id=<?= $u['client_id'] ?>" 
                   class="btn-fiche" title="Voir Fiche 360°">
                    <i class="fas fa-file-invoice"></i> Fiche 360°
                </a>
                <form method="POST" action="<?= BASE_URL ?>?controller=Admin&action=suspendCompte"
                      onsubmit="return confirm('Confirmer cette action ?');" style="display:inline;">
                    <input type="hidden" name="compte_id"   value="<?= htmlspecialchars($u['compte_id']) ?>">
                    <?php if (!empty($u['est_suspendu'])): ?>
                        <input type="hidden" name="action_type" value="reactiver">
                        <button type="submit" class="btn-reactiver">Reactiver</button>
                    <?php else: ?>
                        <input type="hidden" name="action_type" value="suspendre">
                        <button type="submit" class="btn-suspendre">Suspendre</button>
                    <?php endif; ?>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="10" class="empty-row">Aucun client enregistre.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>

<style>
<style>
/* Responsive Filter Card */
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
.form-control:focus { border-color: #042e5a; box-shadow: 0 0 0 3px rgba(4,46,90,0.1); }
.btn-filter {
    padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 15px;
    cursor: pointer; display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; border: none; min-height: 48px; background: #042e5a; color: #fff; width: 100%;
}
.btn-filter:hover { background: #021d3a; }

.result-count { margin: 10px 0; color: #64748b; font-size: 14px; }
.top-actions { margin-bottom: 16px; }
.btn-action-green {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 12px 20px; background: #28a745; color: #fff; border-radius: 8px;
    text-decoration: none; font-weight: 600; font-size: 15px; min-height: 48px;
}
.btn-action-green:hover { background: #1e7e34; }

/* Table overrides */
.row-suspended td { background: #fff5f5 !important; }
.compte-num { font-family: monospace; font-size: 13px; color: #042e5a; font-weight: 600; letter-spacing: 0.5px; }
.montant-cell { text-align: right; font-weight: 700; font-family: monospace; font-size: 14px; }
.text-muted { color: #64748b; }
.badge-active { background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
.badge-suspended { background: #f8d7da; color: #721c24; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; }
.btn-suspendre { padding: 8px 12px; background: #dc3545; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; min-width: 90px; }
.btn-suspendre:hover { background: #c82333; }
.btn-reactiver { padding: 8px 12px; background: #28a745; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; min-width: 90px; }
.btn-reactiver:hover { background: #1e7e34; }
.btn-fiche { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 12px; background: #0056b3; color: #fff; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; margin-right: 4px; min-width: 100px; }
.btn-fiche:hover { background: #004494; }
.empty-row { text-align: center; color: #8792a2; padding: 32px !important; }
</style>
</style>

<script>
const searchInput  = document.getElementById('searchInput');
const filterStatus = document.getElementById('filterStatus');
const clientTable  = document.getElementById('clientTable');
const rowCount     = document.getElementById('rowCount');

function filterTable() {
    const query  = searchInput.value.toLowerCase();
    const status = filterStatus.value.toLowerCase();
    const rows   = clientTable.querySelectorAll('tbody tr');
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
