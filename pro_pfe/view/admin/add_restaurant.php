<?php
require_once '../../config/database.php';
require_once '../../model/Auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Récupérer les catégories depuis la BDD (si la table existe)
$categories = [];
try {
    $stmt_cats = $pdo->query("SELECT DISTINCT category FROM restaurants WHERE category IS NOT NULL ORDER BY category");
    $categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $categories = ['Tunisien', 'Fruits de mer', 'International', 'Pizzeria', 'Fast-food', 'Brasserie'];
}
if (empty($categories)) {
    $categories = ['Tunisien', 'Fruits de mer', 'International', 'Pizzeria', 'Fast-food', 'Brasserie'];
}

$towns = ['Tunis', 'La Marsa', 'Sousse', 'Sfax', 'Monastir', 'Hammamet', 'Nabeul', 'Bizerte', 'Gabès', 'Djerba'];

// Messages flash
$error_msg   = isset($_GET['error'])   ? htmlspecialchars(urldecode($_GET['error']))   : '';
$success_msg = isset($_GET['success']) ? htmlspecialchars(urldecode($_GET['success'])) : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un restaurant - Administration</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>

    <main class="admin-main">
        <?php include 'includes/header.php'; ?>

        <!-- ── Alerts ─────────────────────────────────────────────────────── -->
        <?php if ($error_msg): ?>
        <div id="flash-error" style="margin: 0 0 24px 0; padding: 14px 20px; background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.4); border-radius: 10px; color: #f87171; display: flex; align-items: center; gap: 12px;">
            <i class="fa-solid fa-circle-xmark"></i>
            <span><?= $error_msg ?></span>
            <button onclick="document.getElementById('flash-error').remove()" style="margin-left:auto;background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
        <div id="flash-success" style="margin: 0 0 24px 0; padding: 14px 20px; background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.4); border-radius: 10px; color: #4ade80; display: flex; align-items: center; gap: 12px;">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= $success_msg ?></span>
            <button onclick="document.getElementById('flash-success').remove()" style="margin-left:auto;background:none;border:none;color:inherit;cursor:pointer;font-size:1.1rem;"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <?php endif; ?>

        <!-- ── En-tête de page ──────────────────────────────────────────────── -->
        <div class="page-header" style="margin-bottom: 30px;">
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="restaurants.php" style="color: var(--text-muted); text-decoration: none; font-size: 1.2rem; padding: 8px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card);">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 style="font-size: 1.5rem;">Ajouter un restaurant</h1>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">
                        Restaurants <i class="fa-solid fa-chevron-right" style="font-size: 0.6rem; margin: 0 5px;"></i>
                        <span style="color: var(--text-main);">Ajouter</span>
                    </p>
                </div>
            </div>
            <div class="page-actions" id="desktop-actions">
                <a href="restaurants.php" class="btn-secondary">Annuler</a>
                <button type="submit" form="add-restaurant-form" class="btn-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                </button>
            </div>
        </div>

        <!-- ── Formulaire ───────────────────────────────────────────────────── -->
        <form id="add-restaurant-form"
              action="../../controller/add_restaurant.php"
              method="POST"
              enctype="multipart/form-data">

            <!-- Champs cachés GPS -->
            <input type="hidden" name="lat" id="field-lat" value="">
            <input type="hidden" name="lng" id="field-lng" value="">
            <!-- Champ JSON menu -->
            <input type="hidden" name="menu" id="field-menu" value="[]">

            <div class="form-grid">

                <!-- ── Colonne Gauche ──────────────────────────────────────── -->
                <div class="form-column">

                    <div class="form-section">
                        <h3>Informations générales</h3>

                        <div class="form-group">
                            <label for="f-name">Nom du restaurant <span style="color:#f97316;">*</span></label>
                            <input type="text" id="f-name" name="name" class="form-control"
                                   placeholder="Ex: Le Golfe" required>
                        </div>

                        <div class="form-group">
                            <label for="f-category">Catégorie <span style="color:#f97316;">*</span></label>
                            <select id="f-category" name="category" class="form-control" required>
                                <option value="">Sélectionner une catégorie</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="f-town">Ville</label>
                            <select id="f-town" name="town" class="form-control">
                                <option value="">Sélectionner une ville</option>
                                <?php foreach ($towns as $t): ?>
                                    <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="f-address">Adresse</label>
                            <input type="text" id="f-address" name="address" class="form-control"
                                   placeholder="Adresse complète du restaurant">
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group">
                                <label for="f-phone">Téléphone</label>
                                <input type="text" id="f-phone" name="phone" class="form-control"
                                       placeholder="+216 XX XXX XXX">
                            </div>
                            <div class="form-group">
                                <label for="f-email">Email</label>
                                <input type="email" id="f-email" name="email" class="form-control"
                                       placeholder="email@exemple.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="f-tags">Tags (séparés par des virgules)</label>
                            <input type="text" id="f-tags" name="tags" class="form-control"
                                   placeholder="Ex: vue mer, terrasse, halal">
                        </div>

                        <div class="form-group">
                            <label for="f-description">Description <span style="color:#f97316;">*</span></label>
                            <textarea id="f-description" name="description" class="form-control"
                                      rows="5" placeholder="Description du restaurant..." required></textarea>
                        </div>
                    </div>

                    <!-- ── Menu ────────────────────────────────────────── -->
                    <div class="form-section">
                        <h3>Menu <span style="font-weight:400; font-size:0.85rem; color:var(--text-muted);">(optionnel)</span></h3>

                        <div id="menu-items-list" style="display:flex; flex-direction:column; gap:10px; margin-bottom:12px;"></div>

                        <button type="button" id="btn-add-menu-item"
                                style="width:100%; padding:10px; background:rgba(249,115,22,0.08); border:1px dashed rgba(249,115,22,0.4); border-radius:8px; color:#f97316; cursor:pointer; font-family:inherit; font-size:0.9rem; transition:all .2s;">
                            <i class="fa-solid fa-plus"></i> Ajouter un plat
                        </button>
                    </div>
                </div>

                <!-- ── Colonne Droite ──────────────────────────────────────── -->
                <div class="form-column">

                    <div class="form-section">
                        <h3>Image principale</h3>

                        <!-- Dropzone -->
                        <div id="dropzone" class="dropzone" style="cursor:pointer; position:relative; overflow:hidden;">
                            <input type="file" id="image_file" name="image_file"
                                   accept="image/jpeg,image/png,image/webp,image/gif"
                                   style="position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;">
                            <div id="dz-placeholder">
                                <i class="fa-solid fa-cloud-arrow-up" style="font-size:2.5rem; color:#f97316; margin-bottom:12px;"></i>
                                <p style="font-weight:600;">Glissez-déposez une image ici</p>
                                <p style="color:var(--text-muted);">ou <span style="color:#f97316;">cliquez pour parcourir</span></p>
                                <p class="hint">JPG, PNG ou WebP. Max 5MB.</p>
                            </div>
                            <img id="dz-preview" src="" alt="Aperçu"
                                 style="display:none; max-width:100%; max-height:220px; border-radius:8px; object-fit:cover;">
                        </div>

                        <div style="margin-top:14px;">
                            <label for="image_url" style="font-size:0.82rem; color:var(--text-muted);">
                                <i class="fa-solid fa-link"></i> Ou saisir une URL d'image
                            </label>
                            <input type="url" id="image_url" name="image_url" class="form-control"
                                   placeholder="https://..." style="margin-top:6px;"
                                   oninput="previewUrl(this.value)">
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Localisation GPS <span style="font-weight:400; font-size:0.85rem; color:var(--text-muted);">(optionnel)</span></h3>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="f-lat">Latitude</label>
                                <input type="number" id="f-lat" class="form-control"
                                       placeholder="36.8065" step="0.000001"
                                       oninput="document.getElementById('field-lat').value=this.value">
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label for="f-lng">Longitude</label>
                                <input type="number" id="f-lng" class="form-control"
                                       placeholder="10.1815" step="0.000001"
                                       oninput="document.getElementById('field-lng').value=this.value">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-group" style="margin-bottom: 0;">
                            <label for="f-status">Statut de publication</label>
                            <select id="f-status" name="status" class="form-control">
                                <option value="active">Publié (actif immédiatement)</option>
                                <option value="pending" selected>En attente de validation</option>
                            </select>
                        </div>
                    </div>

                    <!-- Actions mobile -->
                    <div class="form-actions" style="margin-bottom: 30px;">
                        <a href="restaurants.php" class="btn-secondary" style="width:100%; text-align:center;">Annuler</a>
                        <button type="submit" form="add-restaurant-form" class="btn-primary"
                                style="width:100%; justify-content: center;">
                            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
                        </button>
                    </div>

                </div>
            </div>
        </form>

    </main>
</div>

<style>
/* ── Responsive ── */
.form-actions { display: none; }
@media (max-width: 900px) {
    #desktop-actions { display: none !important; }
    .form-actions    { display: flex; flex-direction: column; gap: 10px; }
    .form-grid       { grid-template-columns: 1fr; }
}

/* ── Dropzone ── */
.dropzone {
    border: 2px dashed var(--border-color);
    border-radius: 12px;
    padding: 32px 20px;
    text-align: center;
    transition: border-color .2s, background .2s;
}
.dropzone:hover, .dropzone.dragover {
    border-color: #f97316;
    background: rgba(249,115,22,0.05);
}

/* ── Menu item row ── */
.menu-row {
    display: grid;
    grid-template-columns: 1fr 120px 36px;
    gap: 8px;
    align-items: center;
}
.menu-row input { padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-main); color: var(--text-main); font-family: inherit; font-size: 0.9rem; }
.menu-row input:focus { outline: none; border-color: #f97316; }
.menu-row .btn-remove-item {
    width: 36px; height: 36px; border: none;
    background: rgba(239,68,68,0.1); color: #f87171;
    border-radius: 8px; cursor: pointer; font-size: 1rem;
    transition: background .2s;
}
.menu-row .btn-remove-item:hover { background: rgba(239,68,68,0.25); }
</style>

<script>
// ── Dropzone : aperçu de l'image uploadée ─────────────────────────────────────
const fileInput   = document.getElementById('image_file');
const dzPlaceholder = document.getElementById('dz-placeholder');
const dzPreview   = document.getElementById('dz-preview');

fileInput.addEventListener('change', function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        dzPreview.src = e.target.result;
        dzPreview.style.display = 'block';
        dzPlaceholder.style.display = 'none';
    };
    reader.readAsDataURL(file);
});

// ── Dropzone drag & drop ──────────────────────────────────────────────────────
const dropzone = document.getElementById('dropzone');
['dragover', 'dragenter'].forEach(ev => dropzone.addEventListener(ev, e => {
    e.preventDefault(); dropzone.classList.add('dragover');
}));
['dragleave', 'drop'].forEach(ev => dropzone.addEventListener(ev, e => {
    e.preventDefault(); dropzone.classList.remove('dragover');
}));
dropzone.addEventListener('drop', e => {
    const file = e.dataTransfer.files[0];
    if (!file) return;
    // Injecter le fichier dans l'input
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
    fileInput.dispatchEvent(new Event('change'));
});

// ── Aperçu image par URL ──────────────────────────────────────────────────────
function previewUrl(url) {
    if (!url) {
        dzPreview.style.display = 'none';
        dzPlaceholder.style.display = 'flex';
        return;
    }
    dzPreview.src = url;
    dzPreview.style.display = 'block';
    dzPlaceholder.style.display = 'none';
}

// ── Menu dynamique ────────────────────────────────────────────────────────────
let menuItems = [];

document.getElementById('btn-add-menu-item').addEventListener('click', () => {
    const idx = menuItems.length;
    menuItems.push({ name: '', price: '' });

    const row = document.createElement('div');
    row.className = 'menu-row';
    row.dataset.idx = idx;
    row.innerHTML = `
        <input type="text"   placeholder="Nom du plat"  value="" oninput="updateMenu(${idx},'name',this.value)">
        <input type="number" placeholder="Prix (DT)"    value="" min="0" step="0.1" oninput="updateMenu(${idx},'price',this.value)">
        <button type="button" class="btn-remove-item" onclick="removeMenu(${idx}, this.closest('.menu-row'))">
            <i class="fa-solid fa-trash-can"></i>
        </button>
    `;
    document.getElementById('menu-items-list').appendChild(row);
    syncMenuField();
});

function updateMenu(idx, key, val) {
    if (menuItems[idx]) menuItems[idx][key] = val;
    syncMenuField();
}

function removeMenu(idx, el) {
    menuItems[idx] = null;
    el.remove();
    syncMenuField();
}

function syncMenuField() {
    const valid = menuItems.filter(i => i && (i.name || i.price));
    document.getElementById('field-menu').value = JSON.stringify(valid);
}

// ── Validation côté client avant envoi ───────────────────────────────────────
document.getElementById('add-restaurant-form').addEventListener('submit', function (e) {
    const name     = document.getElementById('f-name').value.trim();
    const category = document.getElementById('f-category').value;
    const desc     = document.getElementById('f-description').value.trim();

    if (!name || !category || !desc) {
        e.preventDefault();
        alert('Veuillez remplir tous les champs obligatoires (*).');
        return;
    }
    syncMenuField();
});
</script>

</body>
</html>
