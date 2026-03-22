<?php
/**
 * config/constants.php
 * Application-wide constants.
 */

// ── User roles ───────────────────────────────────────────────
define('ROLE_ADMIN', 'admin');
define('ROLE_USER',  'user');

// ── Restaurant statuses ──────────────────────────────────────
define('STATUS_PENDING',  'pending');
define('STATUS_ACTIVE',   'active');
define('STATUS_INACTIVE', 'inactive');
define('STATUS_REJECTED', 'rejected');

// ── Review statuses ──────────────────────────────────────────
define('REVIEW_PENDING',  'pending');
define('REVIEW_APPROVED', 'approved');
define('REVIEW_FLAGGED',  'flagged');
define('REVIEW_DELETED',  'deleted');

// ── Pagination ───────────────────────────────────────────────
define('PER_PAGE', 10);

// ── App info ─────────────────────────────────────────────────
define('APP_NAME',    'FoodReviewPro');
define('APP_VERSION', '1.0.0');
