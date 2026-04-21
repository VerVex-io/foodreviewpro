-- =============================================
-- Ala Dhawkek (على ذوقك) - Base de Données
-- Script complet : DROP + CREATE + INSERT
-- =============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- Suppression des tables existantes
-- =============================================
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `restaurants`;
DROP TABLE IF EXISTS `users`;

-- =============================================
-- Table: users
-- =============================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `status` enum('active','inactive') DEFAULT 'active',
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: restaurants
-- =============================================
CREATE TABLE `restaurants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `rating` decimal(3,2) DEFAULT 0.00,
  `reviews_count` int(11) DEFAULT 0,
  `image` varchar(500) NOT NULL,
  `description` text NOT NULL,
  `location_lat` decimal(10,7) NOT NULL,
  `location_lng` decimal(10,7) NOT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `status` enum('pending','active','rejected') DEFAULT 'active',
  `user_id` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `stat_food`     decimal(3,2) DEFAULT 4.00 COMMENT 'TEQ — Excellence culinaire (1-5)',
  `stat_service`  decimal(3,2) DEFAULT 4.00 COMMENT 'AT  — Accueil & service (1-5)',
  `stat_price`    decimal(3,2) DEFAULT 3.00 COMMENT 'DEF — Rapport qualité/prix (1-5)',
  `stat_ambiance` decimal(3,2) DEFAULT 4.00 COMMENT 'TEC — Ambiance & décor (1-5)',
  `stat_location` decimal(3,2) DEFAULT 4.00 COMMENT 'VEL — Emplacement (1-5)',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: reviews
-- =============================================
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `text` text NOT NULL,
  `rating` int(1) NOT NULL,
  `status` enum('pending','approved','deleted') DEFAULT 'approved',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- Table: menu_items
-- =============================================
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `restaurant_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `is_best_seller` TINYINT(1) DEFAULT 0,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Plat',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

START TRANSACTION;

-- =============================================
-- Données: Utilisateurs
-- =============================================
INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `avatar`) VALUES
('Ahmed Ben Salah', 'admin@example.com', 'admin123', 'admin', 'active', 'https://i.pravatar.cc/150?u=ahmed'),
('Sonia Mansour', 'sonia@example.com', 'user123', 'user', 'active', 'https://i.pravatar.cc/150?u=sonia'),
('Karim Trabelsi', 'karim@example.com', 'user123', 'user', 'inactive', 'https://i.pravatar.cc/150?u=karim');

-- =============================================
-- Données: Restaurants (45 restaurants)
-- =============================================
INSERT INTO `restaurants` (`id`, `name`, `category`, `rating`, `reviews_count`, `image`, `description`, `location_lat`, `location_lng`, `tags`) VALUES
(1,  'Dar El Jeld',             'Tunis',        4.9, 842, 'https://images.unsplash.com/photo-1541544741938-0af808871cc0?q=80&w=2069&auto=format&fit=crop', 'L\'excellence de la gastronomie Tunisienne dans une demeure traditionnelle restaurée avec la plus grande créativité.', 36.8001000, 10.1685000, 'Patrimoine, Gastronomie, Luxe'),
(2,  'La Villa Bleue',          'Sidi Bou Said', 4.8, 512, 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?q=80&w=2070&auto=format&fit=crop', 'Une vue imprenable sur le golfe de Tunis. Chaque assiette est une palette de couleurs méditerranéennes.', 36.8711000, 10.3424000, 'Vue Mer, Premium, Méditerranéen'),
(3,  'Fondouk El Charaa',       'Sousse',        4.6, 204, 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=1974&auto=format&fit=crop', 'Caché dans la vieille Médina de Sousse, découvrez des épices rares et une créativité culinaire sans limite.', 35.8256000, 10.6369000, 'Historique, Authentique, Épicé'),
(4,  'Dar Hassine',             'Djerba',        4.7, 319, 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=1974&auto=format&fit=crop', 'L\'art de vivre djerbien dans un écrin de modernité. Nos plats créatifs subliment les classiques de l\'île.', 33.8741000, 10.8559000, 'Fusion insulaire, Cosy, Tapas'),
(5,  'The Cliff',               'Sidi Bou Said', 4.9, 650, 'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=2070&auto=format&fit=crop', 'Un restaurant suspendu entre ciel et mer avec une vue époustouflante sur la Méditerranée. Dégustez nos mets délicats inspirés de la cuisine internationale.', 36.8828000, 10.3308000, 'Vue Panoramique, Gastronomique, International'),
(6,  'Le Barberousse',          'Hammamet',      4.8, 420, 'https://images.unsplash.com/photo-1510626176961-4b57d4fbad03?q=80&w=1974&auto=format&fit=crop', 'Niché dans les remparts de la Médina de Hammamet, venez déguster des fruits de mer d\'une fraîcheur exceptionnelle en admirant le golfe bleu azur.', 36.3957000, 10.6133000, 'Fruit de Mer, Romantique, Médina'),
(7,  'L\'Alba',                 'Sfax',          4.5, 154, 'https://images.unsplash.com/photo-1560684352-8497838a2229?q=80&w=2014&auto=format&fit=crop', 'Le nouveau spot par excellence à Sfax. Une cuisine fusion créative dans une ambiance lounge très moderne, idéale pour vos dîners d\'affaires ou entre amis.', 34.7431000, 10.7634000, 'Contemporain, Fusion, Lounge'),
(8,  'El Ali',                  'Tunis',         4.7, 530, 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?q=80&w=1974&auto=format&fit=crop', 'Situé en plein cœur de la Médina de Tunis, ce restaurant culturel vous offre une terrasse avec vue sur la mosquée Zitouna et des plats aux saveurs héritées de l\'Andalousie.', 36.7972000, 10.1713000, 'Médina, Culturel, Rooftop'),
(9,  'Le QG',                   'Sousse',        4.6, 340, 'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=2070&auto=format&fit=crop', 'Le repaire des gourmets avec une vue infinie sur la mer. Profitez de viandes maturées et de cocktails signature dans une ambiance très animée.', 35.8456000, 10.6169000, 'Beach Club, Festif, Viande'),
(10, 'Gusto',                   'Djerba',        4.8, 215, 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=1974&auto=format&fit=crop', 'L\'authenticité de l\'Italie au cœur de l\'île de Djerba. Ingrédients importés, pâtes fraîches au truffe et pizzas cuites au four à bois.', 33.8651000, 10.8759000, 'Italien, Cozy, Gourmet'),
(11, 'Bruschetta',              'Tunis',         4.6, 310, 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?q=80&w=2069&auto=format&fit=crop', 'Une véritable trattoria italienne en plein Tunis. Des pizzas croustillantes, des antipasti généreux et bien sûr la fameuse Bruschetta maison.', 36.8522000, 10.1534000, 'Italien, Pizzeria, Casual'),
(12, 'Miam\'s',                 'Tunis',         4.5, 425, 'https://images.unsplash.com/photo-1550547660-d9450f859349?q=80&w=1965&auto=format&fit=crop', 'L\'art du Gourmet Burger réinventé. Pains briochés artisanaux, viandes sélectionnées et frites maison dans une ambiance urbaine.', 36.8400000, 10.2001000, 'Gourmet Burger, Américain, Décontracté'),
(13, 'Le Savoy',                'Sousse',        4.8, 560, 'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1974&auto=format&fit=crop', 'Synonyme de luxe et d\'élégance à Sousse. Le Savoy propose une expérience culinaire de haute volée, mélangeant traditions et modernité.', 35.8321000, 10.6315000, 'Gastronomique, Chic, Lounge'),
(14, 'Stories',                 'Tunis',         4.4, 180, 'https://images.unsplash.com/photo-1525648199074-cee30ba79a4a?q=80&w=1974&auto=format&fit=crop', 'L\'escapade parfaite pour un brunch dominical ou un café entre amis. Stories offre un espace zen et un menu healthy.', 36.8378000, 10.2311000, 'Bistro, Brunch, Cosy'),
(15, 'Il Forno 180',            'Tunis',         4.7, 215, 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=1974&auto=format&fit=crop', 'L\'authenticité de l\'Italie au cœur d\'Ennasr. Ingrédients importés, pâtes fraîches et pizzas cuites au four à bois.', 36.8480000, 10.1550000, 'Italien, Gourmet, Pizza'),
(16, 'Pause Gourmande',         'Sousse',        4.6, 120, 'https://images.unsplash.com/photo-1550966871-3cd3cdb5ed0c?q=80&w=2070&auto=format&fit=crop', 'Une pause délicieuse pour vos envies de pâtisseries et de plats légers. Un cadre chaleureux pour une pause gourmande.', 35.8550000, 10.5950000, 'Boulangerie, Café, Sucré'),
(17, 'Chez Slah',               'Tunis',         4.7, 150, 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=1974&auto=format&fit=crop', 'Une institution discrète au centre-ville de Tunis, célèbre pour ses poissons frais et son ambiance authentique qui n\'a pas changé depuis des décennies.', 36.8021000, 10.1812000, 'Seafood, Traditionnel, Authentique'),
(18, 'Villa Didon - Le RestÔ', 'Carthage',      4.8, 320, 'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1974&auto=format&fit=crop', 'Luxe et design sur la colline de Carthage. Une cuisine fusion méditerranéenne raffinée avec une vue panoramique époustouflante sur le golfe de Tunis.', 36.8548000, 10.3245000, 'Gastronomique, Vue Panoramique, Design'),
(19, 'Dar Zarrouk',             'Sidi Bou Said', 4.9, 410, 'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=2070&auto=format&fit=crop', 'Un palais bleu et blanc surplombant Sidi Bou Said. Dar Zarrouk offre l\'une des plus belles tables de Tunisie dans un cadre historique exceptionnel.', 36.8715000, 10.3440000, 'Luxe, Vue Mer, Patrimoine'),
(20, 'Le Golfe',                'La Marsa',      4.8, 750, 'https://images.unsplash.com/photo-1510626176961-4b57d4fbad03?q=80&w=1974&auto=format&fit=crop', 'Une institution les pieds dans le sable à La Marsa depuis 1955. Élégance décontractée et saveurs iodées pour un moment de pur plaisir méditerranéen.', 36.8850000, 10.3325000, 'Beachside, Chic, Méditerranéen'),
(21, 'Darna',                   'Mahdia',        4.7, 180, 'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1974&auto=format&fit=crop', 'Comme à la maison. Une cuisine familiale authentique face à la mer de Mahdia. On y déguste les meilleurs couscous au poisson de la région.', 35.5042000, 11.0621000, 'Authentique, Familial, Seafood'),
(22, 'L\'Olivier',              'Nabeul',        4.6, 210, 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=1974&auto=format&fit=crop', 'Une table raffinée au cœur de Nabeul. Mélange subtil de cuisine internationale et tunisienne dans un cadre élégant et feutré.', 36.4561000, 10.7372000, 'Raffiné, International, Cosy'),
(23, 'El Hamma Restaurant',     'Tozeur',        4.5,  95, 'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=2070&auto=format&fit=crop', 'Une oasis de saveurs aux portes du désert. Spécialités du Sud et ambiance tamisée pour une expérience saharienne inoubliable.', 33.9185000,  8.1336000, 'Sud Tunisien, Oasis, Traditionnel'),
(24, 'Le Phénicien',            'Bizerte',       4.5, 120, 'https://images.unsplash.com/photo-1551218808-94e220e084d2?q=80&w=1974&auto=format&fit=crop', 'Dégustez les meilleurs fruits de mer sur le vieux port de Bizerte dans un cadre historique.', 37.2745000,  9.8732000, 'Seafood, Port, Traditionnel'),
(25, 'Marine Club',             'Bizerte',       4.4,  85, 'https://images.unsplash.com/photo-1510626176961-4b57d4fbad03?q=80&w=1974&auto=format&fit=crop', 'Vue panoramique sur la Méditerranée et poissons frais grillés.', 37.2850000,  9.8650000, 'Vue Mer, Terrasse, Poisson'),
(26, 'Le Barberousse Port',     'Hammamet',      4.7, 310, 'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1974&auto=format&fit=crop', 'Une institution dans la médina de Hammamet. Gastronomie fine et vue mer imprenable.', 36.3945000, 10.6120000, 'Gastronomique, Médina, Vue Mer'),
(27, 'Lella Fatma',             'Hammamet',      4.6, 150, 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?q=80&w=2070&auto=format&fit=crop', 'Cuisine traditionnelle tunisienne généreuse comme à la maison.', 36.4000000, 10.6050000, 'Traditionnel, Couscous, Authentique'),
(28, 'Bel Canto',               'Hammamet',      4.4,  90, 'https://images.unsplash.com/photo-1514333866491-910ac76a086b?q=80&w=1974&auto=format&fit=crop', 'Mélange de saveurs italiennes et méditerranéennes face à la mer.', 36.3980000, 10.6080000, 'Italien, Méditerranéen, Bord de mer'),
(29, 'Restaurant Haroun',       'Djerba',        4.8, 520, 'https://images.unsplash.com/photo-1621275476537-82b29975a85b?q=80&w=2071&auto=format&fit=crop', 'Restaurant mythique en forme de bateau au port de Houmt Souk.', 33.8820000, 10.8550000, 'Incontournable, Seafood, Spectacle'),
(30, 'El Fondouk',              'Djerba',        4.7, 280, 'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=2070&auto=format&fit=crop', 'Un ancien caravansérail transformé en restaurant chic.', 33.8750000, 10.8580000, 'Patrimoine, Chic, Méditerranéen'),
(31, 'Chez Moktar',             'Djerba',        4.6, 140, 'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1974&auto=format&fit=crop', 'La quintessence de la cuisine djerbienne familiale.', 33.8150000, 10.9950000, 'Local, Djerbien, Chaleureux'),
(32, 'Le Sultana',              'Djerba',        4.5, 110, 'https://images.unsplash.com/photo-1510626176961-4b57d4fbad03?q=80&w=1974&auto=format&fit=crop', 'Dîner romantique au bord de l\'eau sur l\'île.', 33.8650000, 10.9550000, 'Romantique, Bord de Mer, Poisson'),
(33, 'Le Pirate',               'Monastir',      4.8, 450, 'https://images.unsplash.com/photo-1551218808-94e220e084d2?q=80&w=1974&auto=format&fit=crop', 'Les meilleurs produits de la mer à Monastir.', 35.7780000, 10.8350000, 'Luxe, Seafood, Port'),
(34, 'Dar Bibi',                'Monastir',      4.7, 130, 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?q=80&w=2070&auto=format&fit=crop', 'Cuisine traditionnelle raffinée dans une maison d\'époque.', 35.7720000, 10.8280000, 'Traditionnel, Patrimoine, Raffiné'),
(35, 'La Cucina',               'Monastir',      4.4,  75, 'https://images.unsplash.com/photo-1514333866491-910ac76a086b?q=80&w=1974&auto=format&fit=crop', 'Petit coin d\'Italie avec des produits frais.', 35.7750000, 10.8320000, 'Italien, Casual, Pasta'),
(36, 'Pure Nature',             'Tabarka',       4.6, 115, 'https://images.unsplash.com/photo-1525648199074-cee30ba79a4a?q=80&w=1974&auto=format&fit=crop', 'Cuisine saine et produits du terroir.', 36.9550000,  8.7550000, 'Nature, Terroir, Sain'),
(37, 'La Belle Vue',            'Tabarka',       4.5,  90, 'https://images.unsplash.com/photo-1510626176961-4b57d4fbad03?q=80&w=1974&auto=format&fit=crop', 'Panorama exceptionnel sur les aiguilles.', 36.9600000,  8.7450000, 'Vue Panoramique, Poisson, Grillades'),
(38, 'La Voile Blanche',        'Sfax',          4.7, 340, 'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1974&auto=format&fit=crop', 'Le rendez-vous des gourmets sfaxiens.', 34.7350000, 10.7650000, 'Business, Seafood, Chic'),
(39, 'Dar Kammoun',             'Sfax',          4.6, 210, 'https://images.unsplash.com/photo-1498654896293-37aacf113fd9?q=80&w=2070&auto=format&fit=crop', 'L\'art de vivre sfaxien dans la médina.', 34.7380000, 10.7600000, 'Médina, Traditionnel, Sfaxien'),
(40, 'El Brija',                'Kairouan',      4.5, 125, 'https://images.unsplash.com/photo-1525648199074-cee30ba79a4a?q=80&w=1974&auto=format&fit=crop', 'Vue sur la Grande Mosquée.', 35.6780000, 10.0980000, 'Patrimoine, Tradition, Terrasse'),
(41, 'Dar Abderrahman Zarrouk','Kairouan',      4.7,  95, 'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=2070&auto=format&fit=crop', 'Demeure historique et gastronomie traditionnelle.', 35.6750000, 10.1020000, 'Luxe, Historique, Tradition'),
(42, 'Errachid',                'Gafsa',         4.4,  80, 'https://images.unsplash.com/photo-1514333866491-910ac76a086b?q=80&w=1974&auto=format&fit=crop', 'Spécialités de viandes et grillades.', 34.4250000,  8.7850000, 'Grillades, Viande, Sud'),
(43, 'Restaurant Abid',         'Gafsa',         4.3,  65, 'https://images.unsplash.com/photo-1510626176961-4b57d4fbad03?q=80&w=1974&auto=format&fit=crop', 'Une escale gourmande et conviviale.', 34.4150000,  8.7950000, 'Convivial, Tunisien, Escale'),
(44, 'Au Bon Vieux Temps',      'Sidi Bou Said', 4.8, 620, 'https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=1974&auto=format&fit=crop', 'Légendaire restaurant avec une vue à couper le souffle.', 36.8720000, 10.3450000, 'Incontournable, Romantique, Vue Mer'),
(45, 'Café des Nattes',         'Sidi Bou Said', 4.6, 850, 'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=2070&auto=format&fit=crop', 'Le café le plus célèbre de Tunisie.', 36.8705000, 10.3415000, 'Historique, Thé, Incontournable');

-- =============================================
-- Données: Menus (tous les restaurants)
-- =============================================
INSERT INTO `menu_items` (`restaurant_id`, `name`, `price`, `is_best_seller`) VALUES
-- 1. Dar El Jeld
(1, 'Couscous Royal à l\'Agneau',        55.00, 1),
(1, 'Ojja aux Crevettes Royales',        42.00, 1),
(1, 'Kabkabou au Poisson Frais',         48.00, 0),
(1, 'Tajine Tunisien Maison',            18.00, 0),
-- 2. La Villa Bleue
(2, 'Filet de Loup de Mer Grillé',       65.00, 1),
(2, 'Pennes aux Fruits de Mer',          38.00, 0),
(2, 'Salade de Poulpe à la Galite',      35.00, 1),
(2, 'Crème Renversée au Zeste d\'Orange',15.00, 0),
-- 3. Fondouk El Charaa
(3, 'Brik à l\'Œuf et Thon',            12.00, 1),
(3, 'Lablabi Complet',                   10.00, 1),
(3, 'Couscous Soussien au Poisson',      30.00, 0),
(3, 'Thé à la Menthe et Pignons',         6.00, 0),
-- 4. Dar Hassine
(4, 'Couscous Djerbien Barba',           25.00, 1),
(4, 'Poisson du Jour à la Djerbienne',   35.00, 1),
(4, 'Mshat Djerbi',                      22.00, 0),
-- 5. The Cliff
(5, 'Magret de Canard au Miel',          75.00, 1),
(5, 'Ceviche de Daurade',                45.00, 1),
(5, 'Risotto aux Cèpes',                 55.00, 0),
-- 6. Le Barberousse
(6, 'Daurade au Four',                   60.00, 1),
(6, 'Spaghetti aux Fruits de Mer',       45.00, 0),
(6, 'Salade Tunisienne Grillée',         18.00, 0),
-- 7. L'Alba
(7, 'Burger Truffé',                     35.00, 1),
(7, 'Salade Burrata',                    28.00, 0),
(7, 'Côte de Bœuf',                      90.00, 1),
-- 8. El Ali
(8, 'Couscous au Osban',                 40.00, 1),
(8, 'Salade Oumek Houria',               12.00, 0),
(8, 'Thé aux Pignons et Assidat Zgougou',20.00, 1),
-- 9. Le QG
(9, 'Entrecôte Maturée 300g',            85.00, 1),
(9, 'Tartare de Saumon',                 35.00, 0),
(9, 'Tiramisu Maison',                   18.00, 0),
-- 10. Gusto
(10, 'Ravioli aux Truffes',              48.00, 1),
(10, 'Pizza Bufalina',                   32.00, 1),
(10, 'Panna Cotta Pistache',             16.00, 0),
-- 11. Bruschetta
(11, 'Bruschetta Classica',              15.00, 1),
(11, 'Pizza Margherita DOP',             28.00, 1),
(11, 'Lasagne Al Forno',                 35.00, 0),
-- 12. Miam's
(12, 'Signature Truffle Burger',         32.00, 1),
(12, 'Bacon Cheese Fries',               18.00, 0),
(12, 'Milkshake Vanille',                15.00, 1),
-- 13. Le Savoy
(13, 'Filet de Bœuf Rossini',            95.00, 1),
(13, 'Carpaccio de Loup',                45.00, 0),
(13, 'Dôme Chocolat Grand Cru',          25.00, 1),
-- 14. Stories
(14, 'Brunch Royal',                     45.00, 1),
(14, 'Avocado Toast',                    22.00, 0),
(14, 'Smoothie Bowl Fruit Rouge',        18.00, 1),
-- 15. Il Forno 180
(15, 'Pizza Bufalina',                   32.00, 1),
(15, 'Lasagne à la Bolognaise',          28.00, 0),
-- 16. Pause Gourmande
(16, 'Éclair au Chocolat',                8.00, 1),
(16, 'Café Gourmand',                    22.00, 1),
-- 17. Chez Slah
(17, 'Dorade Royale Grillée',            42.00, 1),
(17, 'Spaghetti aux Fruits de Mer',      35.00, 1),
-- 18. Villa Didon
(18, 'Tagliata de Bœuf',                 55.00, 1),
(18, 'Céviche de Daurade',               38.00, 0),
-- 19. Dar Zarrouk
(19, 'Couscous aux Fruits de Mer Royale',65.00, 1),
(19, 'Souris d\'Agneau Confite',         55.00, 1),
-- 20. Le Golfe
(20, 'Spaghetti à la Poutargue',         38.00, 1),
(20, 'Tranche de Loup à la Plancha',     45.00, 1),
-- 21. Darna
(21, 'Couscous au Poisson de Mahdia',    28.00, 1),
(21, 'Brik à la Langoustine',            12.00, 1),
-- 22. L'Olivier
(22, 'Filet de Boeuf au Poivre',         45.00, 1),
(22, 'Bisque de Crevettes',              18.00, 0),
-- 23. El Hamma
(23, 'Agneau à la Gargoulette',          35.00, 1),
(23, 'Couscous Tozeurois',               25.00, 1),
-- 24. Le Phénicien
(24, 'Complet Poisson',                  35.00, 1),
-- 25. Marine Club
(25, 'Daurade Grillée',                  32.00, 1),
-- 26. Le Barberousse Port
(26, 'Loup de mer au sel',               55.00, 1),
-- 27. Lella Fatma
(27, 'Couscous Agneau',                  25.00, 1),
-- 28. Bel Canto
(28, 'Pizza Fruits de Mer',              28.00, 1),
-- 29. Restaurant Haroun
(29, 'Couscous Djerbien',                32.00, 1),
-- 30. El Fondouk
(30, 'Agneau à la Miel',                 40.00, 1),
-- 31. Chez Moktar
(31, 'Ojja Fruits de Mer',               18.00, 1),
-- 32. Le Sultana
(32, 'Poisson grillé du jour',           45.00, 1),
-- 33. Le Pirate
(33, 'Assiette du Pêcheur',              48.00, 1),
-- 34. Dar Bibi
(34, 'Couscous Monastirien',             28.00, 1),
-- 35. La Cucina
(35, 'Pasta Carbonara',                  22.00, 1),
-- 36. Pure Nature
(36, 'Salade de la Forêt',               18.00, 1),
-- 37. La Belle Vue
(37, 'Grillade Mixte',                   35.00, 1),
-- 38. La Voile Blanche
(38, 'Octopus Grillé',                   38.00, 1),
-- 39. Dar Kammoun
(39, 'Couscous Sfaxien',                 30.00, 1),
-- 40. El Brija
(40, 'Slatet Mechouia',                  12.00, 1),
-- 41. Dar Abderrahman Zarrouk
(41, 'Couscous Kairouanais',             35.00, 1),
-- 42. Errachid
(42, 'Côtelettes d\'Agneau',             32.00, 1),
-- 43. Restaurant Abid
(43, 'Plat du Jour',                     15.00, 1),
-- 44. Au Bon Vieux Temps
(44, 'Souris d\'Agneau',                 52.00, 1),
-- 45. Café des Nattes
(45, 'Thé aux Pignons',                   8.50, 1);

-- =============================================
-- Données: Avis (reviews)
-- =============================================
INSERT INTO `reviews` (`restaurant_id`, `user_name`, `text`, `rating`, `status`) VALUES
(1,  'Hedi L.',    'Un voyage dans le temps incroyable. Les saveurs de notre enfance revisitées avec génie.', 5, 'approved'),
(2,  'Amira B.',   'Le cadre et le menu fusion valent largement l\'attente.', 5, 'approved'),
(3,  'Yassine J.', 'La ojja aux fruits de mer est inégalable. Le lieu est très inspirant.', 5, 'approved'),
(4,  'Sonia W.',   'Une approche innovante du couscous au poulpe. Superbe !', 5, 'approved'),
(5,  'Selim T.',   'Le meilleur cadre de toute la banlieue nord. Incontournable !', 5, 'approved'),
(6,  'Inès M.',    'Cadre magique au sommet des remparts de Hammamet !', 4, 'approved'),
(7,  'Ahmed B.',   'Ambiance chic et plats bien présentés. C\'est exactement ce qu\'il manquait à Sfax.', 5, 'approved'),
(8,  'Fatma S.',   'Endroit magique au sommet de la vieille ville. Je recommande pour une expérience 100% tunisienne.', 5, 'approved'),
(9,  'Omar K.',    'Bonne ambiance pour le week-end, la vue au coucher du soleil est incroyable.', 4, 'approved'),
(10, 'Nadia L.',   'Les meilleures pâtes au sud de l\'Italie ! Service impeccable.', 5, 'approved'),
(11, 'Sami M.',    'Meilleure pâte à pizza de la région. On s\'y croirait presque à Naples !', 5, 'approved'),
(12, 'Yassine H.', 'Le burger à la truffe est une pépite. Un peu bruyant mais top !', 4, 'approved'),
(13, 'Aymen F.',   'Un service irréprochable et des plats dignes des plus grandes tables étoilées.', 5, 'approved'),
(14, 'Lilia R.',   'Super pour le brunch ! Pensez à réserver à l\'avance, c\'est toujours plein.', 4, 'approved'),
(15, 'Nadia L.',   'Meilleure pizzeria de Tunis !', 5, 'approved'),
(16, 'Faten S.',   'Leurs pâtisseries sont fraîches et le personnel très accueillant.', 5, 'approved'),
(17, 'Youssef S.', 'Le loup grillé est une merveille. Une adresse pour les vrais amateurs de poisson.', 5, 'approved'),
(18, 'Ines R.',    'Vue incroyable sur Carthage. Parfait pour un dîner romantique.', 5, 'approved'),
(19, 'Mouna K.',   'Le plus bel endroit de Sidi Bou Said, cuisine raffinée et cadre féérique.', 5, 'approved'),
(20, 'Mehdi T.',   'Incontournable à La Marsa. On adore l\'ambiance bord de mer chic.', 5, 'approved'),
(21, 'Sami B.',    'Le meilleur couscous au poisson que j\'ai mangé en Tunisie. Authentique et généreux.', 5, 'approved'),
(22, 'Olfa K.',    'Service impeccable et plats savoureux. Une valeur sûre à Nabeul.', 5, 'approved'),
(23, 'Hedi M.',    'Dépaysement total. L\'agneau à la gargoulette est exceptionnel.', 5, 'approved'),
(24, 'Sami R.',    'Produits de la mer ultra frais.', 5, 'approved'),
(25, 'Henda B.',   'Magnifique terrasse.', 5, 'approved'),
(26, 'Nour H.',    'Un cadre historique d\'exception.', 5, 'approved'),
(27, 'Ahmed T.',   'Très authentique.', 5, 'approved'),
(28, 'Ines M.',    'Bonne ambiance.', 4, 'approved'),
(29, 'Jean P.',    'Expérience magique.', 5, 'approved'),
(33, 'Malek W.',   'Le top !', 5, 'approved'),
(44, 'Lucas M.',   'Worth it. The best view in all of Tunisia.', 5, 'approved');

-- =============================================
-- Statistiques IA (TEQ / AT / DEF / TEC / VEL)
-- Utilisées par MinMax & Radar Chart
-- =============================================
UPDATE `restaurants` SET stat_food=4.9, stat_service=4.8, stat_price=3.5, stat_ambiance=4.9, stat_location=4.7 WHERE id=1;  -- Dar El Jeld
UPDATE `restaurants` SET stat_food=4.7, stat_service=4.6, stat_price=3.2, stat_ambiance=4.8, stat_location=4.9 WHERE id=2;  -- La Villa Bleue
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.3, stat_price=4.2, stat_ambiance=4.4, stat_location=4.3 WHERE id=3;  -- Fondouk El Charaa
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.4, stat_price=4.0, stat_ambiance=4.5, stat_location=4.2 WHERE id=4;  -- Dar Hassine
UPDATE `restaurants` SET stat_food=4.8, stat_service=4.7, stat_price=3.0, stat_ambiance=5.0, stat_location=5.0 WHERE id=5;  -- The Cliff
UPDATE `restaurants` SET stat_food=4.7, stat_service=4.5, stat_price=3.4, stat_ambiance=4.8, stat_location=4.9 WHERE id=6;  -- Le Barberousse
UPDATE `restaurants` SET stat_food=4.4, stat_service=4.3, stat_price=3.8, stat_ambiance=4.5, stat_location=4.0 WHERE id=7;  -- L'Alba
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.5, stat_price=4.1, stat_ambiance=4.7, stat_location=4.6 WHERE id=8;  -- El Ali
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.4, stat_price=3.6, stat_ambiance=4.6, stat_location=4.5 WHERE id=9;  -- Le QG
UPDATE `restaurants` SET stat_food=4.8, stat_service=4.6, stat_price=3.3, stat_ambiance=4.7, stat_location=4.4 WHERE id=10; -- Gusto
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.3, stat_price=4.3, stat_ambiance=4.2, stat_location=4.1 WHERE id=11; -- Bruschetta
UPDATE `restaurants` SET stat_food=4.4, stat_service=4.2, stat_price=4.5, stat_ambiance=4.0, stat_location=4.2 WHERE id=12; -- Miam's
UPDATE `restaurants` SET stat_food=4.7, stat_service=4.8, stat_price=3.2, stat_ambiance=4.8, stat_location=4.5 WHERE id=13; -- Le Savoy
UPDATE `restaurants` SET stat_food=4.3, stat_service=4.2, stat_price=4.4, stat_ambiance=4.3, stat_location=4.0 WHERE id=14; -- Stories
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.4, stat_price=3.9, stat_ambiance=4.4, stat_location=4.3 WHERE id=15; -- Il Forno 180
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.5, stat_price=4.6, stat_ambiance=4.1, stat_location=3.9 WHERE id=16; -- Pause Gourmande
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.4, stat_price=4.5, stat_ambiance=4.2, stat_location=4.3 WHERE id=17; -- Chez Slah
UPDATE `restaurants` SET stat_food=4.7, stat_service=4.8, stat_price=3.1, stat_ambiance=4.9, stat_location=4.8 WHERE id=18; -- Villa Didon
UPDATE `restaurants` SET stat_food=4.9, stat_service=4.8, stat_price=3.3, stat_ambiance=5.0, stat_location=5.0 WHERE id=19; -- Dar Zarrouk
UPDATE `restaurants` SET stat_food=4.7, stat_service=4.6, stat_price=3.5, stat_ambiance=4.8, stat_location=4.9 WHERE id=20; -- Le Golfe
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.5, stat_price=4.4, stat_ambiance=4.3, stat_location=4.6 WHERE id=21; -- Darna
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.4, stat_price=3.9, stat_ambiance=4.4, stat_location=4.2 WHERE id=22; -- L'Olivier
UPDATE `restaurants` SET stat_food=4.4, stat_service=4.2, stat_price=4.6, stat_ambiance=4.3, stat_location=4.0 WHERE id=23; -- El Hamma
UPDATE `restaurants` SET stat_food=4.4, stat_service=4.3, stat_price=4.5, stat_ambiance=4.2, stat_location=4.4 WHERE id=24; -- Le Phénicien
UPDATE `restaurants` SET stat_food=4.3, stat_service=4.1, stat_price=4.6, stat_ambiance=4.2, stat_location=4.5 WHERE id=25; -- Marine Club
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.5, stat_price=3.6, stat_ambiance=4.7, stat_location=4.8 WHERE id=26; -- Le Barberousse Port
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.4, stat_price=4.5, stat_ambiance=4.2, stat_location=4.1 WHERE id=27; -- Lella Fatma
UPDATE `restaurants` SET stat_food=4.3, stat_service=4.2, stat_price=4.2, stat_ambiance=4.3, stat_location=4.4 WHERE id=28; -- Bel Canto
UPDATE `restaurants` SET stat_food=4.8, stat_service=4.6, stat_price=3.7, stat_ambiance=4.9, stat_location=4.8 WHERE id=29; -- Restaurant Haroun
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.5, stat_price=3.5, stat_ambiance=4.8, stat_location=4.5 WHERE id=30; -- El Fondouk
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.4, stat_price=4.5, stat_ambiance=4.1, stat_location=4.0 WHERE id=31; -- Chez Moktar
UPDATE `restaurants` SET stat_food=4.4, stat_service=4.3, stat_price=3.8, stat_ambiance=4.5, stat_location=4.6 WHERE id=32; -- Le Sultana
UPDATE `restaurants` SET stat_food=4.7, stat_service=4.6, stat_price=3.4, stat_ambiance=4.6, stat_location=4.7 WHERE id=33; -- Le Pirate
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.5, stat_price=4.0, stat_ambiance=4.6, stat_location=4.4 WHERE id=34; -- Dar Bibi
UPDATE `restaurants` SET stat_food=4.3, stat_service=4.2, stat_price=4.4, stat_ambiance=4.1, stat_location=4.2 WHERE id=35; -- La Cucina
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.4, stat_price=4.3, stat_ambiance=4.4, stat_location=4.2 WHERE id=36; -- Pure Nature
UPDATE `restaurants` SET stat_food=4.4, stat_service=4.3, stat_price=4.2, stat_ambiance=4.5, stat_location=4.6 WHERE id=37; -- La Belle Vue
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.5, stat_price=3.6, stat_ambiance=4.5, stat_location=4.4 WHERE id=38; -- La Voile Blanche
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.4, stat_price=4.3, stat_ambiance=4.5, stat_location=4.3 WHERE id=39; -- Dar Kammoun
UPDATE `restaurants` SET stat_food=4.4, stat_service=4.3, stat_price=4.5, stat_ambiance=4.4, stat_location=4.4 WHERE id=40; -- El Brija
UPDATE `restaurants` SET stat_food=4.6, stat_service=4.5, stat_price=3.8, stat_ambiance=4.7, stat_location=4.5 WHERE id=41; -- Dar Abderrahman
UPDATE `restaurants` SET stat_food=4.3, stat_service=4.2, stat_price=4.7, stat_ambiance=3.9, stat_location=3.8 WHERE id=42; -- Errachid
UPDATE `restaurants` SET stat_food=4.2, stat_service=4.1, stat_price=4.8, stat_ambiance=3.8, stat_location=3.7 WHERE id=43; -- Restaurant Abid
UPDATE `restaurants` SET stat_food=4.7, stat_service=4.7, stat_price=3.3, stat_ambiance=4.9, stat_location=5.0 WHERE id=44; -- Au Bon Vieux Temps
UPDATE `restaurants` SET stat_food=4.5, stat_service=4.6, stat_price=4.4, stat_ambiance=4.9, stat_location=5.0 WHERE id=45; -- Café des Nattes

COMMIT;

-- =============================================
-- Migration (si la base existe déjà)
-- Exécutez seulement ces lignes si vous avez
-- déjà importé l'ancienne version du script.
-- =============================================
-- ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `stat_food`     decimal(3,2) DEFAULT 4.00;
-- ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `stat_service`  decimal(3,2) DEFAULT 4.00;
-- ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `stat_price`    decimal(3,2) DEFAULT 3.00;
-- ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `stat_ambiance` decimal(3,2) DEFAULT 4.00;
-- ALTER TABLE `restaurants` ADD COLUMN IF NOT EXISTS `stat_location` decimal(3,2) DEFAULT 4.00;
