<?php
/**
 * view/layouts/header.php
 * Shared header for all admin pages.
 * Requires $pageTitle to be set before including.
 *
 * Usage in any admin page:
 *   $pageTitle = 'Tableau de Bord';
 *   require_once __DIR__ . '/../layouts/header.php';
 */

if (session_status() === PHP_SESSION_NONE) session_start();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> — على ذوقك</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        fontFamily: { sans: ['DM Sans','sans-serif'] },
        colors: { primary:'#2563eb', secondary:'#7c3aed', success:'#059669', warning:'#d97706', danger:'#dc2626' }
      }}
    };
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/foodreviewpro/public/css/dashboard.css">
</head>
<body class="bg-slate-50">

<!-- SIDEBAR -->
<div class="bg-white w-64 border-r border-slate-200 flex flex-col fixed h-full z-30" id="sidebar">

  <!-- Logo -->
  <div class="px-5 py-5 border-b border-slate-100">
    <div class="flex items-center gap-3">
      <div class="w-9 h-9 bg-blue-600 rounded-xl flex items-center justify-center">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
          <path d="M3 11l19-9-9 19-2-8-8-2z"/>
        </svg>
      </div>
      <div>
        <span class="text-base font-semibold text-slate-800">على <span class="text-blue-600">ذوقك</span></span>
        <p class="text-[10px] text-slate-400 uppercase tracking-wide">Admin Dashboard</p>
      </div>
    </div>
  </div>

  <!-- Nav -->
  <nav class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">

    <p class="sidebar-label">Principal</p>
    <a href="/foodreviewpro/view/admin/dashboard.php"
       class="sidebar-item <?= $currentPage === 'dashboard'   ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
        <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
      </svg>
      Tableau de Bord
    </a>

    <p class="sidebar-label mt-3">Gestion</p>
    <a href="/foodreviewpro/view/admin/pending.php"
       class="sidebar-item <?= $currentPage === 'pending'     ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
      </svg>
      Validation
      <?php
        // Show badge count from DB if controller available
        if (isset($pendingCount) && $pendingCount > 0):
      ?>
        <span class="ml-auto bg-amber-100 text-amber-700 text-[10px] font-semibold px-2 py-0.5 rounded-full">
          <?= $pendingCount ?>
        </span>
      <?php endif; ?>
    </a>

    <a href="/foodreviewpro/view/admin/restaurants.php"
       class="sidebar-item <?= $currentPage === 'restaurants' ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        <polyline points="9 22 9 12 15 12 15 22"/>
      </svg>
      Restaurants
    </a>

    <a href="/foodreviewpro/view/admin/reviews.php"
       class="sidebar-item <?= $currentPage === 'reviews'     ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
      </svg>
      Avis &amp; Modération
    </a>

    <a href="/foodreviewpro/view/admin/users.php"
       class="sidebar-item <?= $currentPage === 'users'       ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
      </svg>
      Utilisateurs
    </a>

    <p class="sidebar-label mt-3">Système</p>
    <a href="/foodreviewpro/view/admin/settings.php"
       class="sidebar-item <?= $currentPage === 'settings'    ? 'active' : '' ?>">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="3"/>
        <path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
      </svg>
      Paramètres
    </a>
  </nav>

  <!-- Admin profile -->
  <div class="p-3 border-t border-slate-100">
    <div class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 cursor-pointer">
      <div class="w-9 h-9 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-semibold">
        <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 2)) ?>
      </div>
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-slate-800 truncate">
          <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
        </p>
        <p class="text-xs text-slate-400">Administrateur</p>
      </div>
      <a href="/foodreviewpro/public/index.php?action=logout" title="Déconnexion">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
          <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
      </a>
    </div>
  </div>
</div>

<!-- MAIN WRAPPER -->
<div class="flex-1 flex flex-col ml-64">

  <!-- TOP BAR -->
  <header class="bg-white border-b border-slate-200 px-6 py-3 sticky top-0 z-20">
    <div class="flex justify-between items-center">
      <div class="flex items-center gap-3">
        <button id="sidebar-toggle" class="p-2 rounded-lg hover:bg-slate-100 lg:hidden">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="6"  x2="21" y2="6"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
          </svg>
        </button>
        <h1 class="text-base font-semibold text-slate-800">
          <?= htmlspecialchars($pageTitle ?? '') ?>
        </h1>
      </div>
      <div class="flex items-center gap-2 text-xs text-slate-400">
        <?= date('d/m/Y') ?>
      </div>
    </div>
  </header>

  <!-- PAGE CONTENT starts here -->
  <main class="flex-1 overflow-y-auto p-6">
