<?php
require_once __DIR__ . '/../../src/Middleware/Auth.php';
Auth::requireAdmin();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../src/Controllers/RestaurantController.php';

$ctrl = new RestaurantController($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = (int)($_POST['id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $adminId = (int)$_SESSION['user_id'];
    if ($action === 'delete' && $id)
        $ctrl->delete($id, $adminId);
    elseif ($action === 'toggle' && $id)
        $ctrl->updateStatus($id,
            $_POST['current_status'] === 'active' ? STATUS_INACTIVE : STATUS_ACTIVE,
            $adminId);
    $_SESSION['flash'] = 'Action effectuée ✓';
    header('Location: /foodreviewpro/view/admin/restaurants.php?' . http_build_query(array_filter($_GET)));
    exit;
}

$filters      = ['status' => $_GET['status'] ?? '', 'category' => $_GET['category'] ?? '', 'city' => $_GET['city'] ?? ''];
$page         = max(1, (int)($_GET['page'] ?? 1));
$total        = $ctrl->count($filters);
$pages        = max(1, (int)ceil($total / PER_PAGE));
$rows         = $ctrl->getAll($filters, $page);
$categories   = ['Français','Italien','Asiatique','Fast Food','Végétarien','Brasserie','Autre'];
$pendingCount = $ctrl->count(['status' => STATUS_PENDING]);

// All active restaurants with coordinates for the map
$allForMap = $pdo->query(
    "SELECT id, name, city, address, category, avg_rating, review_count, status, latitude, longitude
     FROM restaurants
     WHERE latitude IS NOT NULL AND longitude IS NOT NULL"
)->fetchAll();

$pageTitle = 'Restaurants';
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<style>
  #restaurant-map { height: 420px; border-radius: 14px; z-index: 1; }
  .leaflet-popup-content-wrapper { border-radius: 10px; box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
  .leaflet-popup-content { margin: 12px 14px; font-family: 'DM Sans', sans-serif; }
  .map-popup-name  { font-weight: 600; font-size: 13px; color: #1e293b; margin-bottom: 3px; }
  .map-popup-city  { font-size: 11px; color: #64748b; margin-bottom: 5px; }
  .map-popup-badge { display:inline-block; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 600; }
  .map-tab-btn { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; border: 1px solid #e2e8f0; background: #fff; color: #475569; transition: all 0.15s; }
  .map-tab-btn.active { background: #1e40af; color: #fff; border-color: #1e40af; }
</style>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 mb-5 text-sm font-medium">
    <?= htmlspecialchars($_SESSION['flash']) ?>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<!-- ══════════════ MAP SECTION ══════════════ -->
<div class="bg-white rounded-2xl border border-slate-100 p-5 mb-6">
  <div class="flex items-center justify-between mb-4">
    <div>
      <h3 class="text-sm font-semibold text-slate-800">Carte des Restaurants</h3>
      <p class="text-xs text-slate-400 mt-0.5"><?= count($allForMap) ?> restaurant<?= count($allForMap) > 1 ? 's' : '' ?> géolocalisé<?= count($allForMap) > 1 ? 's' : '' ?></p>
    </div>
    <div class="flex gap-1.5">
      <button class="map-tab-btn active" onclick="filterMapMarkers(this, 'all')">Tous</button>
      <button class="map-tab-btn" onclick="filterMapMarkers(this, 'active')">Actifs</button>
      <button class="map-tab-btn" onclick="filterMapMarkers(this, 'pending')">En attente</button>
    </div>
  </div>

  <?php if (empty($allForMap)): ?>
    <div class="flex flex-col items-center justify-center h-48 text-slate-400 bg-slate-50 rounded-xl border border-slate-200">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mb-2 opacity-40">
        <circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 010 16c-4.418 0-8-4-8-8s3.582-8 8-8z"/><path d="M12 22v-6"/>
      </svg>
      <p class="text-sm">Aucun restaurant avec coordonnées GPS</p>
      <p class="text-xs mt-1">Importez <code class="bg-slate-100 px-1 rounded">add_coordinates.sql</code> puis ajoutez lat/lng lors de la création</p>
    </div>
  <?php else: ?>
    <div id="restaurant-map"></div>
  <?php endif; ?>
</div>

<!-- ══════════════ HEADER + FILTERS ══════════════ -->
<div class="flex items-center justify-between mb-5">
  <p class="text-sm text-slate-500"><?= $total ?> résultat<?= $total > 1 ? 's' : '' ?></p>
  <button onclick="openAddModal()"
    class="flex items-center gap-2 px-3 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-colors">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Ajouter un Restaurant
  </button>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl border border-slate-100 p-4 mb-5">
  <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
    <div>
      <label class="block text-xs font-medium text-slate-500 mb-1">Catégorie</label>
      <select name="category" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        <option value="">Toutes</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c ?>" <?= $filters['category'] === $c ? 'selected' : '' ?>><?= $c ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-500 mb-1">Statut</label>
      <select name="status" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
        <option value="">Tous</option>
        <?php foreach (['active'=>'Actif','pending'=>'En attente','inactive'=>'Inactif','rejected'=>'Refusé'] as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $filters['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs font-medium text-slate-500 mb-1">Ville</label>
      <input type="text" name="city" value="<?= htmlspecialchars($filters['city']) ?>"
        placeholder="ex: Paris"
        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
    </div>
    <div class="flex items-end gap-2">
      <button type="submit" class="flex-1 bg-blue-600 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">Filtrer</button>
      <a href="/foodreviewpro/view/admin/restaurants.php" class="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-500 hover:bg-slate-50">Reset</a>
    </div>
  </form>
</div>

<!-- ══════════════ TABLE ══════════════ -->
<div class="bg-white rounded-2xl border border-slate-100 overflow-hidden">
  <div class="overflow-x-auto">
    <table class="w-full">
      <thead class="bg-slate-50 border-b border-slate-100">
        <tr>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Restaurant</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Catégorie</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Note</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Avis</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Prix</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">GPS</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Statut</th>
          <th class="px-5 py-3 text-left text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-50">
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center py-12 text-slate-400 text-sm">Aucun restaurant trouvé.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $filled = (int)round($r['avg_rating']);
          $badge  = match($r['status']) {
            'active'   => '<span class="badge badge-green">Actif</span>',
            'inactive' => '<span class="badge badge-gray">Inactif</span>',
            'pending'  => '<span class="badge badge-amber">En attente</span>',
            'rejected' => '<span class="badge badge-red">Refusé</span>',
            default    => ''
          };
          $hasGps = !empty($r['latitude']) && !empty($r['longitude']);
        ?>
        <tr class="hover:bg-slate-50 transition-colors <?= $hasGps ? 'cursor-pointer' : '' ?>"
          <?= $hasGps ? "onclick=\"flyToMarker({$r['latitude']},{$r['longitude']},'" . addslashes(htmlspecialchars($r['name'])) . "')\"" : '' ?>>
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center text-sm font-bold text-blue-600 flex-shrink-0">
                <?= strtoupper($r['name'][0]) ?>
              </div>
              <div>
                <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($r['name']) ?></p>
                <p class="text-xs text-slate-400"><?= htmlspecialchars($r['city']) ?></p>
              </div>
            </div>
          </td>
          <td class="px-5 py-3.5"><span class="badge badge-blue"><?= htmlspecialchars($r['category']) ?></span></td>
          <td class="px-5 py-3.5">
            <div class="flex items-center gap-1">
              <span class="text-amber-400 text-xs"><?= str_repeat('★',$filled).str_repeat('☆',5-$filled) ?></span>
              <span class="text-xs font-medium text-slate-700"><?= $r['avg_rating'] ?></span>
            </div>
          </td>
          <td class="px-5 py-3.5 text-sm font-medium text-slate-700"><?= number_format($r['review_count']) ?></td>
          <td class="px-5 py-3.5 text-xs text-slate-500"><?= htmlspecialchars($r['price_range']) ?></td>
          <td class="px-5 py-3.5">
            <?php if ($hasGps): ?>
              <span class="badge badge-green gps-btn" style="cursor:pointer"
                data-lat="<?= (float)$r['latitude'] ?>"
                data-lng="<?= (float)$r['longitude'] ?>"
                data-name="<?= htmlspecialchars($r['name'], ENT_QUOTES) ?>"
                title="Voir sur la carte — <?= $r['latitude'] ?>, <?= $r['longitude'] ?>">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 010 16c-4.418 0-8-4-8-8s3.582-8 8-8z"/></svg>
                GPS
              </span>
            <?php else: ?>
              <span class="badge badge-gray">—</span>
            <?php endif; ?>
          </td>
          <td class="px-5 py-3.5"><?= $badge ?></td>
          <td class="px-5 py-3.5" onclick="event.stopPropagation()">
            <div class="flex gap-1.5">
              <form method="POST" class="inline">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="current_status" value="<?= $r['status'] ?>">
                <button type="submit" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                  title="<?= $r['status'] === 'active' ? 'Désactiver' : 'Activer' ?>">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                </button>
              </form>
              <form method="POST" class="inline"
                onsubmit="return confirm('Supprimer « <?= addslashes(htmlspecialchars($r['name'])) ?> » ?')">
                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                </button>
              </form>
            </div>
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
      <?php for ($p = 1; $p <= $pages; $p++): $q = http_build_query(array_merge($filters, ['page'=>$p])); ?>
        <a href="?<?= $q ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $p===$page ? 'bg-blue-600 text-white font-medium' : 'border border-slate-200 text-slate-500 hover:bg-slate-50' ?>"><?= $p ?></a>
      <?php endfor; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ══════════════ ADD MODAL ══════════════ -->
<div id="add-modal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,0.55);backdrop-filter:blur(2px);z-index:9999;overflow-y:auto;padding:20px 12px;">
  <div style="background:#fff;border-radius:16px;padding:22px 24px;width:100%;max-width:540px;margin:0 auto;box-shadow:0 24px 48px rgba(0,0,0,0.2);">

    <!-- Header -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
      <h3 style="font-size:15px;font-weight:600;color:#1e293b;margin:0">Ajouter un Restaurant</h3>
      <button onclick="closeAddModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:4px 8px;border-radius:6px;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">✕</button>
    </div>

    <form method="POST" action="/foodreviewpro/public/index.php?action=add_restaurant">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">

        <!-- Nom -->
        <div style="grid-column:1/-1">
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Nom *</label>
          <input type="text" name="name" placeholder="ex: La Belle Assiette" required
            style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>

        <!-- Catégorie -->
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Catégorie *</label>
          <select name="category" required style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
            <?php foreach ($categories as $c): ?><option><?= $c ?></option><?php endforeach; ?>
          </select>
        </div>

        <!-- Ville -->
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Ville *</label>
          <input type="text" name="city" placeholder="ex: Paris" required
            style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>

        <!-- Adresse -->
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Adresse</label>
          <input type="text" name="address" placeholder="Rue, numéro..."
            style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>

        <!-- Téléphone -->
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Téléphone</label>
          <input type="text" name="phone" placeholder="01 XX XX XX XX"
            style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>

        <!-- Prix -->
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Prix</label>
          <select name="price_range" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
            <option value="€">€ — Économique</option>
            <option value="€€" selected>€€ — Modéré</option>
            <option value="€€€">€€€ — Gastronomique</option>
          </select>
        </div>

        <!-- Statut -->
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Statut initial</label>
          <select name="status" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
            <option value="pending">En attente de validation</option>
            <option value="active">Actif directement</option>
          </select>
        </div>

        <!-- Description -->
        <div style="grid-column:1/-1">
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Description</label>
          <textarea name="description" rows="2"
            style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;resize:none;box-sizing:border-box;"></textarea>
        </div>

        <!-- GPS row -->
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Latitude <span style="color:#94a3b8;font-weight:400">(ex: 48.8566)</span></label>
          <input type="number" name="latitude" id="input-lat" step="0.0000001" placeholder="48.8566"
            style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>
        <div>
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">Longitude <span style="color:#94a3b8;font-weight:400">(ex: 2.3522)</span></label>
          <input type="number" name="longitude" id="input-lng" step="0.0000001" placeholder="2.3522"
            style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:7px 11px;font-size:13px;outline:none;box-sizing:border-box;">
        </div>

        <!-- Picker map -->
        <div style="grid-column:1/-1">
          <label style="display:block;font-size:11px;font-weight:500;color:#475569;margin-bottom:4px">
            Cliquer sur la carte pour positionner
            <span style="color:#94a3b8;font-weight:400">(optionnel)</span>
          </label>
          <div id="picker-map" style="height:180px;border-radius:10px;border:1px solid #e2e8f0;"></div>
        </div>

      </div>

      <!-- Buttons -->
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px;">
        <button type="button" onclick="closeAddModal()"
          style="padding:8px 18px;border-radius:8px;border:1px solid #e2e8f0;background:#fff;font-size:13px;color:#475569;cursor:pointer;">
          Annuler
        </button>
        <button type="submit"
          style="padding:8px 18px;border-radius:8px;border:none;background:#2563eb;color:#fff;font-size:13px;font-weight:500;cursor:pointer;">
          Enregistrer
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// ── Restaurant data from PHP ─────────────────────────────────
const restaurants = <?= json_encode($allForMap) ?>;

// Debug: log what the map has loaded
console.log('Map data loaded:', restaurants.length, 'restaurants with GPS');
restaurants.forEach(r => console.log(' -', r.name, '|', r.latitude, r.longitude, '| status:', r.status));

// ── Colour per status ────────────────────────────────────────
const colours = {
  active:   '#2563eb',
  pending:  '#d97706',
  inactive: '#94a3b8',
  rejected: '#dc2626'
};

function makeIcon(status) {
  const c = colours[status] || '#2563eb';
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="28" height="36" viewBox="0 0 28 36">
    <path d="M14 0C6.268 0 0 6.268 0 14c0 9.333 14 22 14 22S28 23.333 28 14C28 6.268 21.732 0 14 0z"
      fill="${c}" stroke="white" stroke-width="1.5"/>
    <circle cx="14" cy="14" r="5" fill="white"/>
  </svg>`;
  return L.divIcon({
    html: svg,
    iconSize:   [28, 36],
    iconAnchor: [14, 36],
    popupAnchor:[0, -36],
    className: ''
  });
}

// ── Main map ─────────────────────────────────────────────────
let map, markers = [], markerLayer;

<?php if (!empty($allForMap)): ?>
map = L.map('restaurant-map', { zoomControl: true });

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
  maxZoom: 18
}).addTo(map);

markerLayer = L.layerGroup().addTo(map);

function buildMarkers(data) {
  markerLayer.clearLayers();
  markers = [];
  data.forEach(r => {
    if (!r.latitude || !r.longitude) return;
    const stars = '★'.repeat(Math.round(r.avg_rating)) + '☆'.repeat(5 - Math.round(r.avg_rating));
    const badgeColour = r.status === 'active' ? '#065f46' : r.status === 'pending' ? '#92400e' : '#475569';
    const badgeBg     = r.status === 'active' ? '#d1fae5' : r.status === 'pending' ? '#fef3c7' : '#f1f5f9';
    const statusLabel = { active:'Actif', pending:'En attente', inactive:'Inactif', rejected:'Refusé' }[r.status] ?? r.status;

    const popup = `
      <div style="min-width:180px">
        <p class="map-popup-name">${r.name}</p>
        <p class="map-popup-city">📍 ${r.city}${r.address ? ' — ' + r.address : ''}</p>
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px">
          <span style="color:#f59e0b;font-size:12px">${stars}</span>
          <span style="font-size:11px;color:#475569">${r.avg_rating} (${r.review_count} avis)</span>
        </div>
        <span class="map-popup-badge" style="background:${badgeBg};color:${badgeColour}">${statusLabel}</span>
        <span style="background:#dbeafe;color:#1e40af;font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;margin-left:4px">${r.category}</span>
      </div>`;

    const m = L.marker([parseFloat(r.latitude), parseFloat(r.longitude)], {
      icon: makeIcon(r.status),
      title: r.name,
      riseOnHover: true
    }).bindPopup(popup);

    m._status = r.status;
    markers.push(m);
    markerLayer.addLayer(m);
  });

  if (markers.length) {
    const group = L.featureGroup(markers);
    map.fitBounds(group.getBounds().pad(0.15));
  } else {
    map.setView([33.8869, 9.5375], 7);
  }
}

buildMarkers(restaurants);

// ── Filter markers by status ─────────────────────────────────
function filterMapMarkers(btn, status) {
  document.querySelectorAll('.map-tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  const filtered = status === 'all' ? restaurants : restaurants.filter(r => r.status === status);
  buildMarkers(filtered);
}

// ── Click GPS badge → jump to marker ────────────────────────
function flyToMarker(lat, lng, name) {
  if (!map) return;
  lat = parseFloat(lat);
  lng = parseFloat(lng);

  // Scroll map div into view
  document.getElementById('restaurant-map').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  // Use setView (instant) instead of flyTo to avoid animation bugs
  map.invalidateSize();
  map.setView([lat, lng], 15);

  // Find and open matching marker popup
  let found = null;
  markers.forEach(m => {
    const ll = m.getLatLng();
    if (Math.abs(ll.lat - lat) < 0.01 && Math.abs(ll.lng - lng) < 0.01) {
      found = m;
    }
  });

  if (found) {
    found.openPopup();
  } else {
    // New restaurant added after page load — create marker on the fly
    const popup = `<div style="min-width:160px">
      <p class="map-popup-name">${name}</p>
      <p class="map-popup-city">📍 ${lat.toFixed(5)}, ${lng.toFixed(5)}</p>
    </div>`;
    const tmp = L.marker([lat, lng], { icon: makeIcon('active'), title: name })
      .bindPopup(popup)
      .addTo(markerLayer);
    markers.push(tmp);
    tmp.openPopup();
  }
}
<?php endif; ?>

// ── Event delegation — GPS badges & table rows ──────────────
document.addEventListener('click', function(e) {
  // GPS badge click
  const badge = e.target.closest('.gps-btn');
  if (badge) {
    e.stopPropagation();
    flyToMarker(
      parseFloat(badge.dataset.lat),
      parseFloat(badge.dataset.lng),
      badge.dataset.name
    );
    return;
  }
  // Table row click (but not action buttons)
  const row = e.target.closest('tr.map-row');
  if (row && !e.target.closest('button, form, a')) {
    const lat = parseFloat(row.dataset.lat);
    const lng = parseFloat(row.dataset.lng);
    if (lat && lng) flyToMarker(lat, lng, row.dataset.name);
  }
});

// ── Add modal open / close ───────────────────────────────────
let pickerMap, pickerMarker;

function openAddModal() {
  document.getElementById('add-modal').style.display = 'block';
  document.body.style.overflow = 'hidden';
  setTimeout(initPicker, 80);
}

function closeAddModal() {
  document.getElementById('add-modal').style.display = 'none';
  document.body.style.overflow = '';
}

// Close on backdrop click
document.getElementById('add-modal').addEventListener('click', function(e) {
  if (e.target === this) closeAddModal();
});

// ── Coordinate picker map in the Add Modal ───────────────────
function initPicker() {
  if (pickerMap) { pickerMap.invalidateSize(); return; }
  pickerMap = L.map('picker-map').setView([33.8869, 9.5375], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 18
  }).addTo(pickerMap);

  pickerMap.on('click', e => {
    const { lat, lng } = e.latlng;
    document.getElementById('input-lat').value = lat.toFixed(7);
    document.getElementById('input-lng').value = lng.toFixed(7);
    if (pickerMarker) pickerMarker.setLatLng(e.latlng);
    else {
      pickerMarker = L.marker(e.latlng, { icon: makeIcon('active'), draggable: true }).addTo(pickerMap);
      pickerMarker.on('dragend', ev => {
        const p = ev.target.getLatLng();
        document.getElementById('input-lat').value = p.lat.toFixed(7);
        document.getElementById('input-lng').value = p.lng.toFixed(7);
      });
    }
  });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>