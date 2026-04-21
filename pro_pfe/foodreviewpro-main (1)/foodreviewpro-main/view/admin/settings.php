<?php
require_once __DIR__ . '/../../src/Middleware/Auth.php';
Auth::requireAdmin();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Controllers/RestaurantController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flash'] = 'Paramètres sauvegardés ✓';
    header('Location: /foodreviewpro/view/admin/settings.php');
    exit;
}

$pendingCount = (new RestaurantController($pdo))->count(['status' => STATUS_PENDING]);
$pageTitle    = 'Paramètres';
require_once __DIR__ . '/../layouts/header.php';
?>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 mb-5 text-sm font-medium">
    <?= htmlspecialchars($_SESSION['flash']) ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<form method="POST">
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

  <!-- General info -->
  <div class="bg-white rounded-2xl border border-slate-100 p-5">
    <h3 class="text-sm font-semibold text-slate-800 mb-4">Informations Générales</h3>
    <div class="space-y-3">
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Nom de la plateforme</label>
        <input type="text" name="app_name" value="FoodReviewPro"
          class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Email de contact</label>
        <input type="email" name="contact_email" value="admin@foodreview.fr"
          class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Langue par défaut</label>
        <select name="lang" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
          <option selected>Français</option>
          <option>English</option>
          <option>العربية</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Moderation policy -->
  <div class="bg-white rounded-2xl border border-slate-100 p-5">
    <h3 class="text-sm font-semibold text-slate-800 mb-4">Politique de Modération</h3>
    <div class="space-y-3">
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Validation des restaurants</label>
        <select name="restaurant_validation" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
          <option>Manuelle (admin requis)</option>
          <option>Automatique</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Validation des avis</label>
        <select name="review_validation" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
          <option>Manuelle</option>
          <option>Auto si note ≥ 4 étoiles</option>
          <option>Automatique</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Note minimale requise</label>
        <input type="number" name="min_rating" value="1" min="1" max="5"
          class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500 mb-1">Résultats par page</label>
        <select name="per_page" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
          <option>10</option><option>20</option><option>50</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Email notifications -->
  <div class="bg-white rounded-2xl border border-slate-100 p-5">
    <h3 class="text-sm font-semibold text-slate-800 mb-4">Notifications Email</h3>
    <div class="space-y-3">
      <?php
      $notifs = [
        'notif_new_restaurant' => ['Nouveau restaurant soumis', true],
        'notif_flagged_review'  => ['Avis signalé',            true],
        'notif_new_user'        => ['Nouvel utilisateur inscrit', false],
        'notif_weekly_report'   => ['Rapport hebdomadaire',    false],
      ];
      foreach ($notifs as $name => [$label, $default]):
      ?>
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-600"><?= $label ?></span>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" name="<?= $name ?>" <?= $default ? 'checked' : '' ?>>
          <div class="relative w-9 h-5 bg-gray-200 rounded-full peer
                      peer-checked:bg-blue-600 transition-colors
                      after:content-[''] after:absolute after:top-0.5 after:left-0.5
                      after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all
                      peer-checked:after:translate-x-full ml-1"></div>
        </label>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Export -->
  <div class="bg-white rounded-2xl border border-slate-100 p-5">
    <h3 class="text-sm font-semibold text-slate-800 mb-4">Données &amp; Export</h3>
    <div class="space-y-2">
      <a href="/public/index.php?action=export&type=restaurants"
        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Exporter tous les restaurants (JSON)
      </a>
      <a href="/public/index.php?action=export&type=reviews"
        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Exporter tous les avis (JSON)
      </a>
      <a href="/public/index.php?action=export&type=users"
        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition-colors">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
          <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
        </svg>
        Exporter les utilisateurs (JSON)
      </a>
    </div>
  </div>

</div>

<div class="flex justify-end mt-5">
  <button type="submit"
    class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
    Sauvegarder les modifications
  </button>
</div>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>