<?php
require_once __DIR__ . '/../../src/Middleware/Auth.php';
Auth::requireAdmin();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Controllers/RestaurantController.php';

$restaurantCtrl = new RestaurantController($pdo);

// Handle approve / reject POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $adminId = (int) $_SESSION['user_id'];

    if ($action === 'approve' && $id) {
        $restaurantCtrl->approve($id, $adminId);
        $_SESSION['flash'] = 'Restaurant approuvé ✓';
    } elseif ($action === 'reject' && $id) {
        $reason = trim($_POST['reason'] ?? '');
        $restaurantCtrl->reject($id, $adminId, $reason);
        $_SESSION['flash'] = 'Restaurant refusé.';
    }
    header('Location: /foodreviewpro/view/admin/pending.php');
    exit;
}

$pendingList  = $restaurantCtrl->getPending();
$pendingCount = count($pendingList);

$pageTitle = 'File de Validation';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="flex items-center justify-between mb-6">
  <p class="text-sm text-slate-500"><?= $pendingCount ?> demande<?= $pendingCount > 1 ? 's' : '' ?> en attente</p>
  <span class="badge badge-amber text-sm px-3 py-1.5"><?= $pendingCount ?> en attente</span>
</div>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 mb-5 text-sm">
    <?= htmlspecialchars($_SESSION['flash']) ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<?php if (empty($pendingList)): ?>
  <div class="bg-white rounded-2xl border border-slate-100 flex flex-col items-center justify-center py-16 text-slate-400">
    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-3 opacity-40">
      <path d="M20 6L9 17l-5-5"/>
    </svg>
    <p class="text-sm">Aucune demande en attente</p>
  </div>
<?php else: ?>
  <div class="space-y-4">
    <?php foreach ($pendingList as $r): ?>
    <div class="bg-white rounded-2xl border border-slate-100 p-5">
      <div class="flex items-start justify-between gap-4">
        <div class="flex items-start gap-4">
          <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center text-xl font-bold text-amber-700 flex-shrink-0">
            <?= strtoupper($r['name'][0]) ?>
          </div>
          <div>
            <h3 class="text-sm font-semibold text-slate-800"><?= htmlspecialchars($r['name']) ?></h3>
            <p class="text-xs text-slate-400 mt-0.5">
              <?= htmlspecialchars($r['city']) ?> ·
              <span class="badge badge-blue"><?= htmlspecialchars($r['category']) ?></span> ·
              Propriétaire : <?= htmlspecialchars($r['owner_name']) ?>
              (<?= htmlspecialchars($r['owner_email'] ?? '') ?>)
            </p>
            <?php if ($r['description']): ?>
              <p class="text-xs text-slate-500 mt-2 max-w-xl"><?= htmlspecialchars($r['description']) ?></p>
            <?php endif; ?>
            <p class="text-[11px] text-slate-300 mt-1">
              Soumis le <?= date('d/m/Y à H:i', strtotime($r['created_at'])) ?>
            </p>
          </div>
        </div>

        <div class="flex flex-col items-end gap-2 flex-shrink-0">
          <!-- Approve -->
          <form method="POST">
            <input type="hidden" name="id"     value="<?= $r['id'] ?>">
            <input type="hidden" name="action" value="approve">
            <button type="submit"
              class="flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition-colors">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
              Approuver
            </button>
          </form>

          <!-- Reject -->
          <form method="POST" onsubmit="return confirmReject(this)">
            <input type="hidden" name="id"     value="<?= $r['id'] ?>">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="reason" id="reason-<?= $r['id'] ?>" value="">
            <button type="submit"
              class="flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-lg hover:bg-red-700 transition-colors">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <path d="M18 6L6 18M6 6l12 12"/>
              </svg>
              Refuser
            </button>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<script>
function confirmReject(form) {
  const reason = prompt('Motif du refus (optionnel) :') ?? '';
  form.querySelector('[name="reason"]').value = reason;
  return true;
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>