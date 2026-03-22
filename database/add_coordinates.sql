-- ============================================================
-- Migration: add latitude & longitude to restaurants
-- Coordinates set to Tunisia
-- Safe to run multiple times (IF NOT EXISTS)
-- ============================================================

ALTER TABLE restaurants
  ADD COLUMN IF NOT EXISTS latitude  DECIMAL(10,7) NULL DEFAULT NULL AFTER address,
  ADD COLUMN IF NOT EXISTS longitude DECIMAL(10,7) NULL DEFAULT NULL AFTER latitude;

-- Update seed restaurants with Tunisia coordinates
UPDATE restaurants SET latitude = 36.8065,  longitude = 10.1815  WHERE name = 'Le Bistrot Parisien';
UPDATE restaurants SET latitude = 36.8190,  longitude = 10.1658  WHERE name = 'La Table Gourmande';
UPDATE restaurants SET latitude = 36.8185,  longitude = 10.1692  WHERE name = 'Trattoria Bella';
UPDATE restaurants SET latitude = 36.8560,  longitude = 10.2280  WHERE name = 'Sakura Tokyo';
UPDATE restaurants SET latitude = 33.8815,  longitude = 10.0982  WHERE name = 'Le Végétarien Vert';
UPDATE restaurants SET latitude = 36.7372,  longitude = 10.3277  WHERE name = 'Brasserie du Port';
UPDATE restaurants SET latitude = 36.8580,  longitude = 10.3360  WHERE name = 'Le Jardin Provençal';