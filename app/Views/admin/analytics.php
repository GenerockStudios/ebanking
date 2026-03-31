<?php
/**
 * analytics.php - Dashboard analytique Admin avec graphiques Chart.js.
 */
require_once VIEW_PATH . 'layout/header.php';
$sj  = $data['stats_jour']     ?? [];
$sg  = $data['stats_globales'] ?? [];
$c7j = $data['chart_7j']       ?? [];
$rep = $data['repartition']    ?? [];
?>

<h2><?= htmlspecialchars($data['title'] ?? 'Dashboard Analytique') ?></h2>

<?php if (!empty($data['error'])): ?>
<script>document.addEventListener('DOMContentLoaded', () => showToast("<?= addslashes($data['error']) ?>", 'error', 6000));</script>
<?php endif; ?>

<!-- ========== ACCÈS RAPIDE ADMIN ========== -->
<div class="qa-wrapper">

    <!-- Section 1 : Administration -->
    <div class="qa-section">
        <div class="qa-section-title">
            <span class="material-symbols-rounded">admin_panel_settings</span>
            Administration &amp; Rapports
        </div>
        <div class="qa-grid">
            <a href="<?= BASE_URL ?>?controller=Admin&action=snapshotBilan" class="qa-card qa-blue">
                <span class="qa-icon material-symbols-rounded">bar_chart</span>
                <span class="qa-label">Snapshot Fin de Mois</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Admin&action=auditKyc" class="qa-card qa-green">
                <span class="qa-icon material-symbols-rounded">fact_check</span>
                <span class="qa-label">Audit KYC</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Admin&action=auditLogs" class="qa-card qa-purple">
                <span class="qa-icon material-symbols-rounded">security</span>
                <span class="qa-label">Audit Sécurité</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Admin&action=managePlafonds" class="qa-card qa-orange">
                <span class="qa-icon material-symbols-rounded">shield_lock</span>
                <span class="qa-label">Gestion Plafonds</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Admin&action=manageClients" class="qa-card qa-teal">
                <span class="qa-icon material-symbols-rounded">groups</span>
                <span class="qa-label">Gestion Clients</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Admin&action=manageUsers" class="qa-card qa-navy">
                <span class="qa-icon material-symbols-rounded">manage_accounts</span>
                <span class="qa-label">Utilisateurs</span>
            </a>
        </div>
    </div>

    <!-- Section 2 : Opérations Caisse (Admin a accès intégral) -->
    <div class="qa-section">
        <div class="qa-section-title">
            <span class="material-symbols-rounded">point_of_sale</span>
            Opérations de Caisse &amp; Documents
        </div>
        <div class="qa-grid">
            <a href="<?= BASE_URL ?>?controller=Caisse&action=depot" class="qa-card qa-green">
                <span class="qa-icon material-symbols-rounded">account_balance_wallet</span>
                <span class="qa-label">Dépôt</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Caisse&action=retrait" class="qa-card qa-red">
                <span class="qa-icon material-symbols-rounded">payments</span>
                <span class="qa-label">Retrait</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Caisse&action=transfert" class="qa-card qa-blue">
                <span class="qa-icon material-symbols-rounded">sync_alt</span>
                <span class="qa-label">Transfert</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Caisse&action=releve" class="qa-card qa-purple">
                <span class="qa-icon material-symbols-rounded">receipt_long</span>
                <span class="qa-label">Relevé de Compte</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Caisse&action=cloture" class="qa-card qa-orange">
                <span class="qa-icon material-symbols-rounded">lock_clock</span>
                <span class="qa-label">Session Caisse</span>
            </a>
            <a href="<?= BASE_URL ?>?controller=Caisse&action=simulation" class="qa-card qa-teal">
                <span class="qa-icon material-symbols-rounded">savings</span>
                <span class="qa-label">Simulation Épargne</span>
            </a>
            <a href="#" onclick="const num=prompt('Numéro de compte :');if(num)window.location.href='<?= BASE_URL ?>?controller=Caisse&action=rib&numero_compte='+num;" class="qa-card qa-navy">
                <span class="qa-icon material-symbols-rounded">account_balance</span>
                <span class="qa-label">Édition RIB</span>
            </a>
        </div>
    </div>

</div>

<style>
.qa-wrapper { display:flex; flex-direction:column; gap:20px; margin-bottom:28px; }
.qa-section { background:#fff; border-radius:14px; padding:20px 22px; box-shadow:0 2px 8px rgba(0,0,0,.06); border:1px solid #f0f2f5; }
.qa-section-title {
    display:flex; align-items:center; gap:8px;
    font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.5px;
    color:#042e5a; margin-bottom:16px; padding-bottom:12px;
    border-bottom:2px solid #f0f2f5;
}
.qa-section-title .material-symbols-rounded { font-size:18px; color:#042e5a; }
.qa-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(130px, 1fr)); gap:12px; }
.qa-card {
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    gap:10px; padding:18px 10px; border-radius:12px; text-decoration:none;
    transition:transform .18s ease, box-shadow .18s ease; cursor:pointer;
    border:none; text-align:center; min-height:100px;
    -webkit-tap-highlight-color: transparent;
}
.qa-card:hover, .qa-card:active { transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,.10); }
.qa-icon { font-size:28px; transition:transform .18s ease; }
.qa-card:hover .qa-icon, .qa-card:active .qa-icon { transform:scale(1.1); }
.qa-label { font-size:13px; font-weight:700; line-height:1.2; }

/* Couleurs */
.qa-blue   { background:#e8f0fe; color:#1a56db; }
.qa-green  { background:#e3fcef; color:#0a6640; }
.qa-red    { background:#fde8e8; color:#b02a2a; }
.qa-purple { background:#f0ebff; color:#6b21a8; }
.qa-orange { background:#fff3e0; color:#c05621; }
.qa-teal   { background:#e0f5f5; color:#0e7490; }
.qa-navy   { background:#e8ecf4; color:#042e5a; }

.qa-blue:hover   { background:#dae5fd; }
.qa-green:hover  { background:#c6f7df; }
.qa-red:hover    { background:#fcd5d5; }
.qa-purple:hover { background:#e5d9ff; }
.qa-orange:hover { background:#ffe8c1; }
.qa-teal:hover   { background:#c0ecec; }
.qa-navy:hover   { background:#d5ddf0; }
</style>



<!-- STAT CARDS -->
<div class="stat-grid">
    <div class="stat-card stat-blue">
        <div class="stat-icon">&#128179;</div>
        <div class="stat-body">
            <div class="stat-value"><?= number_format((int)($sg['nb_clients'] ?? 0)) ?></div>
            <div class="stat-label">Clients enregistres</div>
        </div>
    </div>
    <div class="stat-card stat-green">
        <div class="stat-icon">&#127968;</div>
        <div class="stat-body">
            <div class="stat-value"><?= number_format((int)($sg['nb_comptes'] ?? 0)) ?></div>
            <div class="stat-label">Comptes ouverts</div>
        </div>
    </div>
    <div class="stat-card stat-orange">
        <div class="stat-icon">&#128202;</div>
        <div class="stat-body">
            <div class="stat-value"><?= number_format((int)($sj['total_txn_aujourd_hui'] ?? 0)) ?></div>
            <div class="stat-label">Transactions aujourd'hui</div>
        </div>
    </div>
    <div class="stat-card stat-purple">
        <div class="stat-icon">&#128184;</div>
        <div class="stat-body">
            <div class="stat-value"><?= number_format((float)($sg['total_encours'] ?? 0), 0, ',', ' ') ?></div>
            <div class="stat-label">Total encours (FCFA)</div>
        </div>
    </div>
</div>

<!-- RESUME DU JOUR -->
<div class="day-summary">
    <h3>Resume du Jour</h3>
    <div class="day-grid">
        <div class="day-item day-depot">
            <span class="day-label">Depots</span>
            <span class="day-val"><?= number_format((float)($sj['total_depots'] ?? 0), 0, ',', ' ') ?> FCFA</span>
        </div>
        <div class="day-item day-retrait">
            <span class="day-label">Retraits</span>
            <span class="day-val"><?= number_format((float)($sj['total_retraits'] ?? 0), 0, ',', ' ') ?> FCFA</span>
        </div>
        <div class="day-item day-transfert">
            <span class="day-label">Transferts</span>
            <span class="day-val"><?= number_format((float)($sj['total_transferts'] ?? 0), 0, ',', ' ') ?> FCFA</span>
        </div>
    </div>
</div>

<!-- GRAPHIQUES -->
<div class="charts-grid">
    <div class="chart-card chart-main">
        <h3 class="chart-title">Volume des Transactions - 7 Derniers Jours</h3>
        <canvas id="chartVolume"></canvas>
    </div>
    <div class="chart-card">
        <h3 class="chart-title">Repartition par Type (30 jours)</h3>
        <canvas id="chartRepartition"></canvas>
    </div>
    <div class="chart-card">
        <h3 class="chart-title">Depot vs Retrait - 7 Jours</h3>
        <canvas id="chartCompare"></canvas>
    </div>
</div>

<style>
.stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.stat-card{display:flex;align-items:center;gap:16px;background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.07);border-left:5px solid transparent}
.stat-blue{border-left-color:#007bff}.stat-green{border-left-color:#28a745}.stat-orange{border-left-color:#fd7e14}.stat-purple{border-left-color:#6f42c1}
.stat-icon{font-size:2.2rem}.stat-value{font-size:1.8rem;font-weight:700;color:#042e5a}.stat-label{font-size:12px;color:#777}
.day-summary{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.07);margin-bottom:24px}
.day-summary h3{margin:0 0 14px;font-size:16px;color:#042e5a}
.day-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
.day-item{border-radius:10px;padding:14px;display:flex;flex-direction:column;gap:4px}
.day-depot{background:#d4edda}.day-retrait{background:#f8d7da}.day-transfert{background:#fff3cd}
.day-label{font-size:12px;font-weight:600;color:#555}.day-val{font-size:20px;font-weight:700;color:#042e5a}
.charts-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:30px}
@media(max-width:900px){.charts-grid{grid-template-columns:1fr}}
.chart-main{grid-column:1 / -1}
.chart-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.07)}
.chart-title{font-size:14px;font-weight:600;color:#042e5a;margin:0 0 12px}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
var chart7j     = <?= json_encode(array_values($c7j), JSON_UNESCAPED_UNICODE) ?>;
var repartition = <?= json_encode(array_values($rep), JSON_UNESCAPED_UNICODE) ?>;

// Graphique 1 : Barres groupees Volume 7 jours
(function(){
    var labels   = chart7j.map(function(r){ return r.jour; });
    var nb       = chart7j.map(function(r){ return parseInt(r.nb_transactions)||0; });
    var depots   = chart7j.map(function(r){ return parseFloat(r.depots)||0; });
    var retraits = chart7j.map(function(r){ return parseFloat(r.retraits)||0; });
    new Chart(document.getElementById('chartVolume'), {
        type: 'bar',
        data: { labels: labels, datasets: [
            { label: 'Nb Transactions', data: nb, backgroundColor: 'rgba(4,46,90,0.75)', borderRadius:4, yAxisID:'yNb' },
            { label: 'Depots (FCFA)',   data: depots, backgroundColor: 'rgba(40,167,69,0.65)', borderRadius:4, yAxisID:'yAmt' },
            { label: 'Retraits (FCFA)', data: retraits, backgroundColor: 'rgba(220,53,69,0.65)', borderRadius:4, yAxisID:'yAmt' }
        ]},
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                yNb:  { type:'linear', display:true, position:'left',  title:{ display:true, text:'Nb transactions' } },
                yAmt: { type:'linear', display:true, position:'right', title:{ display:true, text:'Montant FCFA' }, grid:{ drawOnChartArea:false } }
            }
        }
    });
})();

// Graphique 2 : Doughnut repartition par type
(function(){
    if(!repartition.length){ document.getElementById('chartRepartition').parentElement.innerHTML += '<p style="color:#999;text-align:center;font-size:13px">Aucune donnee sur 30 jours.</p>'; return; }
    var labels = repartition.map(function(r){ return r.type_transaction; });
    var vals   = repartition.map(function(r){ return parseInt(r.nb)||0; });
    var colors = ['rgba(0,123,255,0.85)','rgba(220,53,69,0.85)','rgba(255,193,7,0.95)','rgba(40,167,69,0.85)'];
    new Chart(document.getElementById('chartRepartition'), {
        type: 'doughnut',
        data: { labels: labels, datasets: [{ data: vals, backgroundColor: colors.slice(0,labels.length), borderWidth:2 }] },
        options: { responsive:true, plugins:{ legend:{ position:'bottom', labels:{ font:{size:11} } } } }
    });
})();

// Graphique 3 : Lignes depot vs retrait
(function(){
    var labels   = chart7j.map(function(r){ return r.jour; });
    var depots   = chart7j.map(function(r){ return parseFloat(r.depots)||0; });
    var retraits = chart7j.map(function(r){ return parseFloat(r.retraits)||0; });
    new Chart(document.getElementById('chartCompare'), {
        type: 'line',
        data: { labels: labels, datasets: [
            { label: 'Depots',   data: depots,   borderColor:'#28a745', backgroundColor:'rgba(40,167,69,0.1)', tension:0.3, fill:true, pointRadius:4 },
            { label: 'Retraits', data: retraits, borderColor:'#dc3545', backgroundColor:'rgba(220,53,69,0.1)', tension:0.3, fill:true, pointRadius:4 }
        ]},
        options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
    });
})();
</script>

<?php require_once VIEW_PATH . 'layout/footer.php'; ?>
