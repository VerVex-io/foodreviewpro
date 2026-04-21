<?php
require_once __DIR__ . '/../../src/Middleware/Auth.php';
Auth::requireAdmin();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Controllers/RestaurantController.php';
require_once __DIR__ . '/../../src/Controllers/ReviewController.php';
require_once __DIR__ . '/../../src/Controllers/UserController.php';

$restaurantCtrl   = new RestaurantController($pdo);
$reviewCtrl       = new ReviewController($pdo);
$userCtrl         = new UserController($pdo);

$totalRestaurants  = $restaurantCtrl->count();
$activeRestaurants = $restaurantCtrl->count(['status' => 'active']);
$pendingCount      = $restaurantCtrl->count(['status' => STATUS_PENDING]);
$reviewStats       = $reviewCtrl->getStats();
$userStats         = $userCtrl->getStats();
$topRestaurants    = $restaurantCtrl->getTop(5);
$ratingDist        = $reviewCtrl->getRatingDistribution();

// Activity last 14 days
$activityRaw = $pdo->query(
    "SELECT DATE(created_at) AS day,
            COUNT(*) AS total,
            SUM(status='approved') AS approved
     FROM reviews
     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
     GROUP BY DATE(created_at) ORDER BY day ASC"
)->fetchAll();
$activityByDay = [];
for ($i = 13; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $activityByDay[$d] = ['total' => 0, 'approved' => 0];
}
foreach ($activityRaw as $row) $activityByDay[$row['day']] = $row;
$chartDays     = array_map(fn($d) => date('d/m', strtotime($d)), array_keys($activityByDay));
$chartTotal    = array_map('intval', array_column(array_values($activityByDay), 'total'));
$chartApproved = array_map('intval', array_column(array_values($activityByDay), 'approved'));

// Category breakdown
$categoryData = $pdo->query(
    "SELECT category, COUNT(*) AS cnt, ROUND(AVG(avg_rating),1) AS avg_r
     FROM restaurants WHERE status='active'
     GROUP BY category ORDER BY cnt DESC"
)->fetchAll();

// Recent activity logs
$recentActivity = $pdo->query(
    "SELECT al.action, al.target_type, al.details, al.created_at
     FROM activity_logs al ORDER BY al.created_at DESC LIMIT 8"
)->fetchAll();

// New users this week
$newUsersWeek = (int)$pdo->query(
    "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
)->fetchColumn();

// Reviews today
$reviewsToday = (int)$pdo->query(
    "SELECT COUNT(*) FROM reviews WHERE DATE(created_at) = CURDATE()"
)->fetchColumn();

$pageTitle = 'Tableau de Bord';
require_once __DIR__ . '/../layouts/header.php';
?>

<style>
.kpi{background:#fff;border:0.5px solid #e2e8f0;border-radius:14px;padding:18px 20px;transition:box-shadow .2s}
.kpi:hover{box-shadow:0 4px 16px rgba(0,0,0,.07)}
.kpi-val{font-size:26px;font-weight:600;color:#0f172a;line-height:1.1;margin:4px 0 5px}
.kpi-label{font-size:10.5px;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:#94a3b8}
.kpi-sub{font-size:11.5px;color:#64748b}
.kpi-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.sc{background:#fff;border:0.5px solid #e2e8f0;border-radius:14px;padding:18px 20px}
.sc-title{font-size:13px;font-weight:600;color:#0f172a;margin:0 0 14px;display:flex;align-items:center;justify-content:space-between}
.sc-link{font-size:11px;color:#2563eb;font-weight:500;text-decoration:none}
.bar-bg{height:5px;border-radius:3px;background:#f1f5f9;overflow:hidden;margin-top:3px}
.bar-fg{height:100%;border-radius:3px;transition:width .4s}
.a-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;flex-shrink:0}
</style>

<?php
$urgent = $pendingCount + (int)$reviewStats['flagged'];
if ($urgent > 0):
?>
<div style="background:#fff7ed;border:0.5px solid #fed7aa;border-radius:12px;padding:12px 16px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
  <div style="display:flex;align-items:center;gap:10px">
    <div style="width:32px;height:32px;background:#ffedd5;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#c2410c" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    </div>
    <p style="font-size:13px;font-weight:600;color:#9a3412;margin:0">
      <?= $urgent ?> action<?= $urgent>1?'s':'' ?> requise<?= $urgent>1?'s':'' ?> —
      <?php if ($pendingCount) echo "$pendingCount restaurant".($pendingCount>1?'s':'')." à valider"; ?>
      <?php if ($pendingCount && $reviewStats['flagged']) echo ' · '; ?>
      <?php if ($reviewStats['flagged']) echo $reviewStats['flagged']." avis signalé".($reviewStats['flagged']>1?'s':''); ?>
    </p>
  </div>
  <div style="display:flex;gap:6px">
    <?php if ($pendingCount): ?>
    <a href="/foodreviewpro/view/admin/pending.php" style="font-size:11px;font-weight:600;color:#9a3412;background:#ffedd5;padding:5px 11px;border-radius:6px;text-decoration:none">Valider →</a>
    <?php endif; ?>
    <?php if ($reviewStats['flagged']): ?>
    <a href="/foodreviewpro/view/admin/reviews.php?status=flagged" style="font-size:11px;font-weight:600;color:#9a3412;background:#ffedd5;padding:5px 11px;border-radius:6px;text-decoration:none">Avis signalés →</a>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- KPI Row -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">

  <div class="kpi">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <p class="kpi-label">Restaurants</p>
        <p class="kpi-val"><?= $totalRestaurants ?></p>
        <p class="kpi-sub"><span style="color:#059669">●</span> <?= $activeRestaurants ?> actifs<?= $pendingCount ? " · <span style='color:#d97706'>$pendingCount en attente</span>" : '' ?></p>
      </div>
      <div class="kpi-icon" style="background:#eff6ff">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
    </div>
  </div>

  <div class="kpi">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <p class="kpi-label">Avis Total</p>
        <p class="kpi-val"><?= number_format((int)$reviewStats['total']) ?></p>
        <p class="kpi-sub"><span style="color:#f59e0b">★</span> <?= number_format((float)($reviewStats['avg_rating']??0),1) ?>/5<?= $reviewStats['pending'] ? " · <span style='color:#d97706'>".$reviewStats['pending']." à modérer</span>" : '' ?></p>
      </div>
      <div class="kpi-icon" style="background:#f5f3ff">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.8"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      </div>
    </div>
  </div>

  <div class="kpi">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <p class="kpi-label">Utilisateurs</p>
        <p class="kpi-val"><?= number_format((int)$userStats['total']) ?></p>
        <p class="kpi-sub"><span style="color:#059669;font-weight:600">+<?= $newUsersWeek ?></span> cette semaine · <?= $userStats['active'] ?> actifs</p>
      </div>
      <div class="kpi-icon" style="background:#ecfdf5">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="1.8"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      </div>
    </div>
  </div>

  <div class="kpi">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <p class="kpi-label">Avis Aujourd'hui</p>
        <p class="kpi-val"><?= $reviewsToday ?></p>
        <p class="kpi-sub">
          <?php if ((int)$reviewStats['flagged'] > 0): ?>
            <span style="color:#dc2626;font-weight:600">⚑ <?= $reviewStats['flagged'] ?> signalé<?= $reviewStats['flagged']>1?'s':'' ?></span>
          <?php else: ?>
            <span style="color:#059669">✓ Aucun signalement</span>
          <?php endif; ?>
        </p>
      </div>
      <div class="kpi-icon" style="background:#fff7ed">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      </div>
    </div>
  </div>

</div>

<!-- Charts -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:12px;margin-bottom:16px">
  <div class="sc">
    <p class="sc-title">Activité des avis — 14 jours <span style="font-size:11px;color:#94a3b8;font-weight:400">soumis vs approuvés</span></p>
    <div id="chart-activity" style="min-height:195px"></div>
  </div>
  <div class="sc">
    <p class="sc-title">Distribution des notes</p>
    <div id="chart-donut" style="min-height:195px"></div>
  </div>
</div>

<!-- Bottom 3-col -->
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">

  <!-- Top restaurants -->
  <div class="sc">
    <p class="sc-title">Top restaurants <a href="/foodreviewpro/view/admin/restaurants.php" class="sc-link">Voir tout →</a></p>
    <?php if (empty($topRestaurants)): ?>
      <p style="font-size:12px;color:#94a3b8;text-align:center;padding:20px 0">Aucun restaurant actif</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:11px">
      <?php foreach ($topRestaurants as $i => $r): ?>
      <div style="display:flex;align-items:center;gap:9px">
        <span style="font-size:11px;font-weight:700;color:#cbd5e1;width:14px;flex-shrink:0;text-align:right"><?= $i+1 ?></span>
        <div style="width:32px;height:32px;border-radius:8px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#2563eb;flex-shrink:0"><?= strtoupper($r['name'][0]) ?></div>
        <div style="flex:1;min-width:0">
          <div style="display:flex;justify-content:space-between;margin-bottom:2px">
            <p style="font-size:11.5px;font-weight:500;color:#0f172a;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:100px"><?= htmlspecialchars($r['name']) ?></p>
            <span style="font-size:11px;font-weight:600;color:#f59e0b;flex-shrink:0">★<?= $r['avg_rating'] ?></span>
          </div>
          <div class="bar-bg"><div class="bar-fg" style="width:<?= ($r['avg_rating']/5)*100 ?>%;background:#f59e0b"></div></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Categories -->
  <div class="sc">
    <p class="sc-title">Par catégorie <a href="/foodreviewpro/view/admin/restaurants.php" class="sc-link">Détails →</a></p>
    <?php if (empty($categoryData)): ?>
      <p style="font-size:12px;color:#94a3b8;text-align:center;padding:20px 0">Aucune donnée</p>
    <?php else: ?>
    <?php
    $maxCat = max(1, max(array_column($categoryData, 'cnt')));
    $cols   = ['#2563eb','#7c3aed','#059669','#d97706','#dc2626','#0891b2','#475569'];
    foreach ($categoryData as $ci => $cat): $c = $cols[$ci % 7]; ?>
    <div style="margin-bottom:10px">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
        <span style="font-size:11.5px;color:#334155"><?= htmlspecialchars($cat['category']) ?></span>
        <div style="display:flex;gap:8px;align-items:center">
          <span style="font-size:10px;color:#94a3b8"><?= $cat['avg_r'] ?>★</span>
          <span style="font-size:11.5px;font-weight:600;color:#0f172a"><?= $cat['cnt'] ?></span>
        </div>
      </div>
      <div class="bar-bg"><div class="bar-fg" style="width:<?= round(($cat['cnt']/$maxCat)*100) ?>%;background:<?= $c ?>"></div></div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Recent activity -->
  <div class="sc">
    <p class="sc-title">Activité récente</p>
    <?php if (empty($recentActivity)): ?>
      <p style="font-size:12px;color:#94a3b8;text-align:center;padding:20px 0">Aucune activité</p>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:9px">
      <?php foreach ($recentActivity as $log):
        $ib = match($log['action']) { 'APPROVE'=>'#dcfce7','CREATE'=>'#dbeafe','DELETE'=>'#fee2e2','FLAG'=>'#fef9c3',default=>'#f1f5f9' };
        $ic = match($log['action']) { 'APPROVE'=>'#16a34a','CREATE'=>'#2563eb','DELETE'=>'#dc2626','FLAG'=>'#ca8a04',default=>'#475569' };
        $ip = match($log['action']) {
          'APPROVE' => '<path d="M20 6L9 17l-5-5"/>',
          'CREATE'  => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
          'DELETE'  => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/>',
          'FLAG'    => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
          default   => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'
        };
        $ts = time() - strtotime($log['created_at']);
        $tl = $ts < 60 ? "À l'instant" : ($ts < 3600 ? round($ts/60).'min' : ($ts < 86400 ? round($ts/3600).'h' : date('d/m', strtotime($log['created_at']))));
      ?>
      <div style="display:flex;align-items:flex-start;gap:8px">
        <div class="a-icon" style="background:<?= $ib ?>;margin-top:1px">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="<?= $ic ?>" stroke-width="2.5"><?= $ip ?></svg>
        </div>
        <div style="flex:1;min-width:0">
          <p style="font-size:11.5px;color:#334155;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($log['details'] ?: ucfirst(strtolower($log['action'])).' '.$log['target_type']) ?></p>
          <p style="font-size:10px;color:#94a3b8;margin:1px 0 0"><?= $tl ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
const days     = <?= json_encode($chartDays) ?>;
const totals   = <?= json_encode($chartTotal) ?>;
const approved = <?= json_encode($chartApproved) ?>;

if (typeof ApexCharts !== 'undefined') {
  new ApexCharts(document.querySelector('#chart-activity'), {
    series: [
      { name: 'Soumis',    data: totals },
      { name: 'Approuvés', data: approved }
    ],
    chart: { type: 'area', height: 195, toolbar: { show: false } },
    colors: ['#2563eb','#059669'],
    fill: { type: 'gradient', gradient: { opacityFrom: 0.18, opacityTo: 0.01 } },
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
    legend: { position: 'top', fontSize: '11px' },
    xaxis: { categories: days, labels: { style: { fontSize: '10px' } }, tickAmount: 7 },
    yaxis: { labels: { style: { fontSize: '10px' } }, min: 0 },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 3 },
    tooltip: { x: { show: true } }
  }).render();

  const distData = <?= json_encode($ratingDist) ?>;
  const series   = [5,4,3,2,1].map(s => {
    const f = distData.find(d => parseInt(d.rating) === s);
    return f ? parseInt(f.cnt) : 0;
  });
  const tot = series.reduce((a,b)=>a+b,0)||1;

  new ApexCharts(document.querySelector('#chart-donut'), {
    series,
    chart: { type: 'donut', height: 195 },
    colors: ['#22c55e','#84cc16','#f59e0b','#f97316','#ef4444'],
    labels: ['5★','4★','3★','2★','1★'],
    legend: { position: 'bottom', fontSize: '11px' },
    plotOptions: { pie: { donut: { size: '62%',
      labels: { show: true, total: { show: true, label: 'Total', fontSize: '11px',
        formatter: () => tot + ' avis'
      }}
    }}},
    dataLabels: { enabled: false }
  }).render();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>