<?php
require_once __DIR__ . '/../../src/Middleware/Auth.php';
Auth::requireAdmin();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Controllers/UserController.php';
require_once __DIR__ . '/../../src/Controllers/RestaurantController.php';

$ctrl = new UserController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $adminId = (int)$_SESSION['user_id'];
    if ($id === $adminId) {
        $_SESSION['flash_err'] = 'Vous ne pouvez pas modifier votre propre compte.';
    } elseif ($action === 'toggle' && $id) {
        $ctrl->toggleStatus($id, $adminId);
        $_SESSION['flash'] = 'Statut mis à jour ✓';
    } elseif ($action === 'ban' && $id) {
        $ctrl->ban($id, $adminId);
        $_SESSION['flash'] = 'Utilisateur banni ✓';
    }
    header('Location: /foodreviewpro/view/admin/users.php?' . http_build_query(array_filter($_GET)));
    exit;
}

$page  = max(1, (int)($_GET['page'] ?? 1));
$total = $ctrl->count();
$pages = max(1, (int)ceil($total / PER_PAGE));
$rows  = $ctrl->getAll($page);
$stats = $ctrl->getStats();

$pendingCount = (new RestaurantController($pdo))->count(['status' => STATUS_PENDING]);
$pageTitle = 'Utilisateurs';
require_once __DIR__ . '/../layouts/header.php';
?>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 mb-5 text-sm font-medium">
    <?= htmlspecialchars($_SESSION['flash']) ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_err'])): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm font-medium">
    <?= htmlspecialchars($_SESSION['flash_err']) ?>
  </div>
  <?php unset($_SESSION['flash_err']); ?>
<?php endif; ?>

<!-- Stats row -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <?php
  $cards = [
    ['Total inscrits',    $stats['total'],     'bg-blue-50',   'text-blue-600'],
    ['Comptes actifs',    $stats['active'],    'bg-green-50',  'text-green-600'],
    ['Suspendus',         $stats['suspended'], 'bg-amber-50',  'text-amber-600'],
    ['Bannis',            $stats['banned'],    'bg-red-50',    'text-red-500'],
  ];
  foreach ($cards as [$label, $val, $bg, $txt]):
  ?>
  <div class="bg-white rounded-2xl p-5 border border-slate-100 card-hover">
    <p class="text-xs font-medium text-slate-400 uppercase tracking-wide"><?= $label ?></p>
    <p class="text-2xl font-semibold <?= $txt ?> mt-1"><?= number_format($val) ?></p>
  </div>
  <?php endforeach; ?>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full">
      <thead class="bg-slate-50 border-b border-slate-100">
        <tr>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Utilisateur</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Email</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Rôle</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Avis</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Inscription</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Statut</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center py-12 text-slate-400 text-sm">Aucun utilisateur trouvé.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $u):
          $isMe  = $u['id'] === (int)$_SESSION['user_id'];
          $initials = strtoupper(substr($u['user_name'], 0, 2));
          $statusBadge = match($u['status']) {
            'active'    => '<span class="badge badge-green">Actif</span>',
            'suspended' => '<span class="badge badge-amber">Suspendu</span>',
            'banned'    => '<span class="badge badge-red">Banni</span>',
            default     => ''
          };
          $roleBadge = $u['role'] === 'admin'
            ? '<span class="badge badge-purple">Admin</span>'
            : '<span class="badge badge-gray">Utilisateur</span>';
        ?>
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-xs font-semibold flex-shrink-0">
                <?= $initials ?>
              </div>
              <p class="text-sm font-medium text-slate-800">
                <?= htmlspecialchars($u['user_name']) ?>
                <?= $isMe ? '<span class="text-xs text-slate-400">(vous)</span>' : '' ?>
              </p>
            </div>
          </td>
          <td class="px-5 py-3.5 text-sm text-slate-500"><?= htmlspecialchars($u['email']) ?></td>
          <td class="px-5 py-3.5"><?= $roleBadge ?></td>
          <td class="px-5 py-3.5 text-sm font-medium text-slate-700"><?= $u['review_count'] ?></td>
          <td class="px-5 py-3.5 text-xs text-slate-400"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
          <td class="px-5 py-3.5"><?= $statusBadge ?></td>
          <td class="px-5 py-3.5">
            <?php if (!$isMe && $u['role'] !== 'admin'): ?>
            <div class="flex gap-1.5">
              <!-- Toggle suspend -->
              <form method="POST" class="inline">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <input type="hidden" name="action" value="toggle">
                <button type="submit"
                  class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                  title="<?= $u['status'] === 'active' ? 'Suspendre' : 'Réactiver' ?>">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/>
                  </svg>
                </button>
              </form>
              <!-- Ban -->
              <?php if ($u['status'] !== 'banned'): ?>
              <form method="POST" class="inline"
                onsubmit="return confirm('Bannir définitivement <?= addslashes(htmlspecialchars($u['user_name'])) ?> ?')">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <input type="hidden" name="action" value="ban">
                <button type="submit"
                  class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Bannir">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                  </svg>
                </button>
              </form>
              <?php endif; ?>
            </div>
            <?php else: ?>
              <span class="text-xs text-slate-300">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <div class="flex items-center justify-between px-5 py-3 border-t border-slate-100">
    <span class="text-xs text-slate-400">Page <?= $page ?> / <?= $pages ?></span>
    <div class="flex gap-1">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="?page=<?= $p ?>"
          class="px-3 py-1.5 text-xs rounded-lg <?= $p===$page ? 'bg-blue-600 text-white font-medium' : 'border border-slate-200 text-slate-500 hover:bg-slate-50' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>