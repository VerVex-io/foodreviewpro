# FoodReviewPro — Admin Dashboard

Application PHP de gestion de restaurants et d'avis.

## Installation

```bash
# 1. Clone the project
git clone ... && cd foodreviewpro

# 2. Copy environment file
cp .env.example .env
# Edit .env with your DB credentials

# 3. Create database
mysql -u root -p < database/schema.sql
mysql -u root -p foodreviewpro < database/seeds.sql

# 4. Point your web server DocumentRoot to /public/
# Apache: DocumentRoot /path/to/foodreviewpro/public
# Or use PHP built-in server:
php -S localhost:8000 -t public/
```

## Default admin login
- Email: `admin@foodreview.fr`
- Password: `Admin@1234`

## Folder structure

```
foodreviewpro/
├── view/
│   ├── auth/           ← Login / Signup pages
│   ├── admin/          ← Admin dashboard pages (protected)
│   └── layouts/        ← header.php, footer.php
├── public/             ← Web root (point server here)
│   ├── css/
│   ├── js/
│   └── index.php       ← Entry point & router
├── src/
│   ├── Controllers/    ← Business logic
│   ├── Models/         ← DB queries
│   └── Middleware/     ← Auth guard
├── config/             ← db.php, constants.php
├── database/           ← schema.sql, seeds.sql
└── .env                ← DB credentials (never commit)
```
