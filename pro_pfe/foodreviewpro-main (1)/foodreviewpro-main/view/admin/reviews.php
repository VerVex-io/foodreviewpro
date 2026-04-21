<?php
require_once __DIR__ . '/../../src/Middleware/Auth.php';
Auth::requireAdmin();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Controllers/ReviewController.php';
require_once __DIR__ . '/../../src/Controllers/RestaurantController.php';

$ctrl = new ReviewController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id']     ?? 0);
    $action  = $_POST['action']        ?? '';
    $adminId = (int)$_SESSION['user_id'];

    if ($action === 'approve' && $id)    $ctrl->approve($id, $adminId);
    elseif ($action === 'delete' && $id) $ctrl->delete($id, $adminId);
    elseif ($action === 'flag'   && $id) $ctrl->flag($id, $adminId, trim($_POST['reason'] ?? ''));

    $_SESSION['flash'] = match($action) {
        'approve' => 'Avis approuvé ✓',
        'delete'  => 'Avis supprimé',
        'flag'    => 'Avis signalé',
        default   => 'Action effectuée ✓'
    };
    header('Location: /foodreviewpro/view/admin/reviews.php?' . http_build_query(array_filter($_GET)));
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$total        = $ctrl->count($statusFilter);
$pages        = max(1, (int)ceil($total / PER_PAGE));
$rows         = $ctrl->getAll($statusFilter, $page);
$stats        = $ctrl->getStats();
$dist         = $ctrl->getRatingDistribution();

$pendingCount = (int)$stats['pending'];
$pageTitle    = 'Avis & Modération';
require_once __DIR__ . '/../layouts/header.php';
?>

<style>
.rev-table th { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#94a3b8; padding:10px 14px; text-align:left; background:#f8fafc; }
.rev-table td { padding:12px 14px; vertical-align:top; border-bottom:0.5px solid #f1f5f9; }
.rev-table tr:hover td { background:#fafafa; }
.rev-table tr:last-child td { border-bottom:none; }
.stars-filled { color:#f59e0b; }
.stars-empty  { color:#e2e8f0; }
.comment-text { font-size:12px; color:#475569; line-height:1.5; max-width:320px; }
.action-btn { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:6px; font-size:11px; font-weight:500; cursor:pointer; border:none; transition:all .15s; }
.btn-approve { background:#dcfce7; color:#15803d; }
.btn-approve:hover { background:#bbf7d0; }
.btn-delete  { background:#fee2e2; color:#dc2626; }
.btn-delete:hover  { background:#fecaca; }
.btn-flag    { background:#f1f5f9; color:#475569; }
.btn-flag:hover    { background:#e2e8f0; }
.pill-filter { padding:5px 14px; border-radius:20px; font-size:12px; font-weight:500; cursor:pointer; border:0.5px solid #e2e8f0; background:#fff; color:#475569; text-decoration:none; transition:all .15s; display:inline-block; }
.pill-filter:hover { background:#f8fafc; }
.pill-filter.active { background:#1e40af; color:#fff; border-color:#1e40af; }
.stat-row { display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:0.5px solid #f1f5f9; }
.stat-row:last-child { border-bottom:none; }
.progress-bar { height:5px; border-radius:3px; background:#f1f5f9; overflow:hidden; margin-top:3px; }
.progress-fill { height:100%; border-radius:3px; }
</style>

<?php if (!empty($_SESSION['flash'])): ?>
  <div style="background:#f0fdf4;border:0.5px solid #86efac;color:#15803d;border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:13px;font-weight:500">
    <?= htmlspecialchars($_SESSION['flash']) ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- Filter pills -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;align-items:center">
  <?php
  $pills = [
    ''        => ['Tous',       $stats['total'],   ''],
    'pending' => ['À modérer',  $stats['pending'], '#d97706'],
    'flagged' => ['Signalés',   $stats['flagged'], '#dc2626'],
    'approved'=> ['Approuvés',  $stats['approved'],'#059669'],
    'deleted' => ['Supprimés',  $stats['deleted'], '#94a3b8'],
  ];
  foreach ($pills as $val => [$label, $count, $color]):
  ?>
  <a href="?status=<?= $val ?>" class="pill-filter <?= $statusFilter === $val ? 'active' : '' ?>">
    <?= $label ?>
    <span style="margin-left:4px;font-size:10px;opacity:.7">(<?= $count ?>)</span>
  </a>
  <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 260px;gap:14px;align-items:start">

  <!-- Main table -->
  <div style="background:#fff;border:0.5px solid #e2e8f0;border-radius:14px;overflow:hidden">
    <?php if (empty($rows)): ?>
      <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:48px 24px;color:#94a3b8">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:.4;margin-bottom:10px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        <p style="font-size:13px;margin:0">Aucun avis dans cette catégorie</p>
      </div>
    <?php else: ?>
    <div style="overflow-x:auto">
      <table class="rev-table" style="width:100%;border-collapse:collapse">
        <thead>
          <tr>
            <th>Utilisateur</th>
            <th>Restaurant</th>
            <th>Note</th>
            <th>Commentaire</th>
            <th>Date</th>
            <th>Statut</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r):
            $initials    = strtoupper(substr($r['user_name'], 0, 2));
            $filledStars = (int)$r['rating'];
            $badge       = match($r['status']) {
              'pending'  => '<span class="badge badge-amber">À modérer</span>',
              'flagged'  => '<span class="badge badge-red">Signalé</span>',
              'approved' => '<span class="badge badge-green">Approuvé</span>',
              'deleted'  => '<span class="badge badge-gray">Supprimé</span>',
              default    => ''
            };
            $rowBg = $r['status'] === 'flagged' ? 'background:#fff9f9' : ($r['status'] === 'pending' ? 'background:#fffdf5' : '');
          ?>
          <tr style="<?= $rowBg ?>">
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div style="width:30px;height:30px;border-radius:50%;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#2563eb;flex-shrink:0">
                  <?= $initials ?>
                </div>
                <span style="font-size:12px;font-weight:500;color:#0f172a;white-space:nowrap"><?= htmlspecialchars($r['user_name']) ?></span>
              </div>
            </td>
            <td>
              <span style="font-size:12px;color:#334155;white-space:nowrap;max-width:130px;display:block;overflow:hidden;text-overflow:ellipsis">
                <?= htmlspecialchars($r['restaurant_name']) ?>
              </span>
            </td>
            <td style="white-space:nowrap">
              <span class="stars-filled" style="font-size:12px"><?= str_repeat('★', $filledStars) ?></span><span class="stars-empty" style="font-size:12px"><?= str_repeat('★', 5-$filledStars) ?></span>
              <span style="font-size:11px;font-weight:600;color:#0f172a;margin-left:3px"><?= $r['rating'] ?></span>
            </td>
            <td>
              <?php if ($r['comment']): ?>
                <p class="comment-text"><?= htmlspecialchars(mb_strimwidth($r['comment'], 0, 100, '…')) ?></p>
              <?php else: ?>
                <span style="font-size:11px;color:#cbd5e1;font-style:italic">Aucun commentaire</span>
              <?php endif; ?>
              <?php if ($r['status'] === 'flagged' && $r['flag_reason']): ?>
                <p style="font-size:10.5px;color:#dc2626;margin:4px 0 0;background:#fef2f2;padding:3px 7px;border-radius:5px;display:inline-block">⚑ <?= htmlspecialchars($r['flag_reason']) ?></p>
              <?php endif; ?>
            </td>
            <td style="white-space:nowrap;font-size:11px;color:#94a3b8"><?= date('d/m/Y', strtotime($r['created_at'])) ?><br><?= date('H:i', strtotime($r['created_at'])) ?></td>
            <td><?= $badge ?></td>
            <td>
              <div style="display:flex;gap:4px;flex-wrap:nowrap">
                <?php if ($r['status'] !== 'approved' && $r['status'] !== 'deleted'): ?>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="action-btn btn-approve">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5"/></svg>
                      OK
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($r['status'] !== 'deleted'): ?>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cet avis ?')">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="action-btn btn-delete">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6"/></svg>
                      Suppr.
                    </button>
                  </form>
                <?php endif; ?>

                <?php if ($r['status'] !== 'flagged' && $r['status'] !== 'deleted'): ?>
                  <form method="POST" style="display:inline" onsubmit="event.preventDefault();flagReview(this)">
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="action" value="flag">
                    <input type="hidden" name="reason" value="">
                    <button type="submit" class="action-btn btn-flag">
                      <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
                      Signaler
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-top:0.5px solid #f1f5f9">
      <span style="font-size:11px;color:#94a3b8">Page <?= $page ?> / <?= $pages ?> — <?= $total ?> avis</span>
      <div style="display:flex;gap:4px">
        <?php for ($p = 1; $p <= $pages; $p++): ?>
          <a href="?status=<?= $statusFilter ?>&page=<?= $p ?>"
            style="padding:4px 10px;border-radius:6px;font-size:11px;text-decoration:none;<?= $p===$page ? 'background:#2563eb;color:#fff;font-weight:600' : 'border:0.5px solid #e2e8f0;color:#64748b' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>

  <!-- Sidebar -->
  <div style="display:flex;flex-direction:column;gap:12px">

    <!-- Stats -->
    <div style="background:#fff;border:0.5px solid #e2e8f0;border-radius:14px;padding:16px">
      <p style="font-size:13px;font-weight:600;color:#0f172a;margin:0 0 12px">Statistiques</p>

      <div class="stat-row">
        <span style="font-size:12px;color:#475569">Note moyenne</span>
        <span style="font-size:14px;font-weight:700;color:#f59e0b">★ <?= number_format((float)($stats['avg_rating']??0),1) ?></span>
      </div>
      <div class="stat-row">
        <span style="font-size:12px;color:#475569">Total avis</span>
        <span style="font-size:12px;font-weight:600;color:#0f172a"><?= number_format((int)$stats['total']) ?></span>
      </div>
      <div class="stat-row">
        <span style="font-size:12px;color:#475569">Approuvés</span>
        <span style="font-size:12px;font-weight:600;color:#15803d"><?= $stats['approved'] ?></span>
      </div>
      <div class="stat-row">
        <span style="font-size:12px;color:#475569">À modérer</span>
        <span style="font-size:12px;font-weight:600;color:#d97706"><?= $stats['pending'] ?></span>
      </div>
      <div class="stat-row">
        <span style="font-size:12px;color:#475569">Signalés</span>
        <span style="font-size:12px;font-weight:600;color:#dc2626"><?= $stats['flagged'] ?></span>
      </div>
      <div class="stat-row">
        <span style="font-size:12px;color:#475569">Supprimés</span>
        <span style="font-size:12px;font-weight:600;color:#94a3b8"><?= $stats['deleted'] ?></span>
      </div>
    </div>

    <!-- Rating distribution -->
    <div style="background:#fff;border:0.5px solid #e2e8f0;border-radius:14px;padding:16px">
      <p style="font-size:13px;font-weight:600;color:#0f172a;margin:0 0 12px">Distribution</p>
      <?php
      $distByRating = [];
      foreach ($dist as $d) $distByRating[$d['rating']] = $d['cnt'];
      $totalA = array_sum($distByRating) ?: 1;
      for ($s = 5; $s >= 1; $s--):
        $cnt = $distByRating[$s] ?? 0;
        $pct = round(($cnt / $totalA) * 100);
        $barColor = $s >= 4 ? '#22c55e' : ($s === 3 ? '#f59e0b' : '#ef4444');
      ?>
      <div style="margin-bottom:8px">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:3px">
          <span style="font-size:11px;color:#64748b"><?= str_repeat('★',$s).str_repeat('☆',5-$s) ?></span>
          <span style="font-size:11px;font-weight:600;color:#0f172a"><?= $pct ?>%</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div></div>
      </div>
      <?php endfor; ?>
    </div>

    <!-- Auto-moderation -->
    <div style="background:#fff;border:0.5px solid #e2e8f0;border-radius:14px;padding:16px">
      <p style="font-size:13px;font-weight:600;color:#0f172a;margin:0 0 12px">Auto-Modération</p>
      <?php foreach (['Filtre spam'=>true,'Contenu offensant'=>true,'Approbation auto (4★+)'=>false] as $lbl=>$chk): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:0.5px solid #f8fafc">
        <span style="font-size:11.5px;color:#475569"><?= $lbl ?></span>
        <label style="position:relative;display:inline-flex;align-items:center;cursor:pointer">
          <input type="checkbox" <?= $chk?'checked':'' ?> style="position:absolute;opacity:0;width:100%;height:100%;cursor:pointer;margin:0">
          <div style="width:34px;height:18px;background:<?= $chk?'#2563eb':'#e2e8f0' ?>;border-radius:9px;transition:background .2s;position:relative">
            <div style="position:absolute;top:2px;<?= $chk?'left:18px':'left:2px' ?>;width:14px;height:14px;background:#fff;border-radius:50%;transition:left .2s"></div>
          </div>
        </label>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</div>

<script>
function flagReview(form) {
  const reason = prompt('Motif du signalement (optionnel) :') ?? '';
  form.querySelector('[name="reason"]').value = reason;
  form.submit();
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>