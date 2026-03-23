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
<div class="alert-error"><?= htmlspecialchars($data['error']) ?></div>
<?php endif; ?>

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
