<?php
ob_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/Auth.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Ala Dhawkek</title>
    <script>
        // Immediatly apply theme
        (function() {
            const savedTheme = localStorage.getItem('atng_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();

        function toggleAdminTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('atng_theme', newTheme);
            
            // Refresh charts if any (specific logic for dashboard.php)
            if (typeof updateAdminCharts === 'function') {
                updateAdminCharts(newTheme);
            }
        }
    </script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- CSS Personnalisé (on réutilise celui du projet) -->
    <link rel="stylesheet" href="../style.css">
    
    <style>
        /* Surcharge spécifique pour le mode PHP classique */
        .admin-view-standalone {
            padding-top: 0;
            display: flex;
            min-height: 100vh;
        }
        .admin-content {
            flex: 1;
            padding: 3rem;
            background: var(--bg-dark);
        }
    </style>
</head>
<body class="admin-view-standalone">

    <!-- Toast Layer for Admin -->
    <div id="admin-toast-container" style="position: fixed; top: 30px; right: 30px; z-index: 10000; display: flex; flex-direction: column; gap: 12px; pointer-events: none;"></div>

    <script>
        function showAdminToast(message, type = 'success') {
            const container = document.getElementById('admin-toast-container');
            const toast = document.createElement('div');
            
            let icon = 'fa-circle-check';
            let color = '#2ed573';
            if (type === 'error' || type === 'rejected') {
                icon = 'fa-circle-xmark';
                color = '#ff4757';
            } else if (type === 'info') {
                icon = 'fa-circle-info';
                color = '#54a0ff';
            } else if (type === 'warning') {
                icon = 'fa-circle-exclamation';
                color = '#ffa502';
            }

            toast.style.cssText = `
                background: #1a1a1a;
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                border: 1px solid rgba(255,255,255,0.05);
                box-shadow: 0 10px 40px rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                gap: 15px;
                font-family: 'Inter', sans-serif;
                font-size: 0.95rem;
                min-width: 320px;
                border-left: 5px solid ${color};
                transform: translateX(120%);
                transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                pointer-events: all;
            `;

            toast.innerHTML = `
                <i class="fa-solid ${icon}" style="color: ${color}; font-size: 1.3rem;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 600; margin-bottom: 2px;">${type.charAt(0).toUpperCase() + type.slice(1)}</div>
                    <div style="color: #a0a0a0; font-size: 0.85rem;">${message}</div>
                </div>
            `;

            container.appendChild(toast);
            
            // Animation Entrée
            setTimeout(() => { toast.style.transform = 'translateX(0)'; }, 100);

            // Animation Sortie
            setTimeout(() => {
                toast.style.transform = 'translateX(120%)';
                setTimeout(() => { toast.remove(); }, 400);
            }, 5000);
        }

        // Auto-trigger from URL matches
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');
            if (msg) {
                const messages = {
                    'deleted': ['Élément supprimé avec succès', 'success'],
                    'added': ['Nouveau restaurant ajouté au catalogue', 'success'],
                    'updated': ['Les modifications ont été enregistrées', 'success'],
                    'approved': ['Le restaurant est désormais en ligne', 'success'],
                    'rejected': ['La proposition a été mise en attente', 'warning'],
                    'error': ['Une erreur est survenue lors de l\'opération', 'error']
                };
                if (messages[msg]) {
                    showAdminToast(messages[msg][0], messages[msg][1]);
                }
            }
        });
    </script>

    <!-- Modal de Confirmation Premium -->
    <div id="delete-confirm-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 20000; align-items: center; justify-content: center;">
        <div style="background: linear-gradient(135deg, #1e1e26 0%, #111115 100%); width: 100%; max-width: 440px; padding: 40px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 25px 50px rgba(0,0,0,0.4); text-align: center; animation: modalPop 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
            <div style="width: 80px; height: 80px; background: rgba(255, 68, 68, 0.1); color: #ff4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; margin: 0 auto 25px;">
                <i class="fa-solid fa-trash-can"></i>
            </div>
            <h2 style="font-family: 'Outfit', sans-serif; font-size: 1.8rem; margin-bottom: 15px; color: #fff;">Confirmation</h2>
            <p id="delete-modal-text" style="color: #a0a0a0; line-height: 1.6; margin-bottom: 30px; font-size: 1.05rem;">Êtes-vous certain de vouloir supprimer cet élément ? Cette action est irréversible.</p>
            
            <div style="display: flex; gap: 15px;">
                <button type="button" onclick="closeDeleteModal()" style="flex: 1; padding: 14px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.1); background: transparent; color: #fff; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    Annuler
                </button>
                <a id="delete-confirm-btn" href="#" style="flex: 1; padding: 14px; border-radius: 14px; border: none; background: #ff4444; color: #fff; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 10px 20px rgba(255,68,68,0.2);">
                    Supprimer
                </a>
            </div>
        </div>
    </div>

    <style>
        @keyframes modalPop {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>

    <script>
        let deleteUrl = "";
        function askDeleteConfirmation(url, itemName = "cet élément") {
            deleteUrl = url;
            document.getElementById('delete-modal-text').innerText = `Êtes-vous certain de vouloir supprimer ${itemName} ? Cette action est définitive.`;
            document.getElementById('delete-confirm-btn').href = url;
            document.getElementById('delete-confirm-modal').style.display = 'flex';
            return false;
        }

        function closeDeleteModal() {
            document.getElementById('delete-confirm-modal').style.display = 'none';
        }

        // Fermer au clic extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('delete-confirm-modal');
            if (event.target == modal) {
                closeDeleteModal();
            }
        }
    </script>
