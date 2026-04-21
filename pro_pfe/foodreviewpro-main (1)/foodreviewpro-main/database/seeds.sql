-- ============================================================
-- FoodReviewPro — Seed Data
-- Run AFTER schema.sql
-- ============================================================

USE foodreviewpro;

-- ── Admin user (password: Admin@1234) ────────────────────────
INSERT INTO users (user_name, email, password_hash, role, status) VALUES
('Admin Principal', 'admin@foodreview.fr',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@1234
 'admin', 'active');

-- ── Regular users ────────────────────────────────────────────
INSERT INTO users (user_name, email, password_hash, role, status) VALUES
('Sophie Martin',   'sophie.m@email.fr',   '$2y$12$placeholderhashabcdef1', 'user', 'active'),
('Pierre Dubois',   'pierre.d@email.fr',   '$2y$12$placeholderhashabcdef2', 'user', 'suspended'),
('Marie Laurent',   'marie.l@email.fr',    '$2y$12$placeholderhashabcdef3', 'user', 'active'),
('François Moreau', 'francois.m@email.fr', '$2y$12$placeholderhashabcdef4', 'user', 'active');

-- ── Restaurants (owner_id = 1 = admin for seeds) ─────────────
INSERT INTO restaurants (owner_id, name, description, category, city, address, phone, price_range, status, avg_rating, review_count) VALUES
(1, 'Le Bistrot Parisien',
 'Cuisine française traditionnelle au cœur de Paris.',
 'Français', 'Paris', '12 Rue de Rivoli, 75001', '01 42 00 00 01', '€€', 'active', 4.20, 142),

(1, 'La Table Gourmande',
 'Restaurant gastronomique avec des créations originales.',
 'Français', 'Paris', '8 Avenue Montaigne, 75008', '01 42 00 00 02', '€€€', 'active', 4.80, 256),

(1, 'Trattoria Bella',
 'Pizzeria napolitaine avec four à bois.',
 'Italien', 'Lyon', '5 Rue Mercière, 69002', '04 72 00 00 03', '€€', 'active', 4.50, 89),

(1, 'Sakura Tokyo',
 'Sushis et cuisine japonaise authentique.',
 'Asiatique', 'Paris', '22 Rue Saint-Anne, 75001', '01 42 00 00 04', '€€', 'active', 4.60, 118),

(1, 'Le Jardin Provençal',
 'Produits locaux de saison, spécialités provençales.',
 'Français', 'Aix-en-Provence', '3 Cours Mirabeau, 13100', '04 42 00 00 05', '€€', 'pending', 0.00, 0);

-- ── Reviews ───────────────────────────────────────────────────
INSERT INTO reviews (restaurant_id, user_id, rating, comment, status) VALUES
(1, 2, 4, 'Excellente expérience ! Service impeccable et plats délicieux.', 'approved'),
(2, 3, 5, 'Une expérience gastronomique exceptionnelle.', 'flagged'),
(3, 4, 3, 'Cuisine correcte mais service un peu lent.', 'pending'),
(4, 5, 5, 'Les meilleurs sushis de Paris !', 'approved');

-- ── Activity logs ─────────────────────────────────────────────
INSERT INTO activity_logs (admin_id, action, target_type, target_id, details) VALUES
(1, 'APPROVE', 'restaurant', 1, 'Le Bistrot Parisien — approuvé'),
(1, 'APPROVE', 'review',     1, 'Avis Sophie Martin — approuvé'),
(1, 'FLAG',    'review',     2, 'Avis Pierre Dubois — signalé');
