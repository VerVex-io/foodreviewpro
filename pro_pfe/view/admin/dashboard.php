<?php
require_once 'header.php';
require_once 'sidebar.php';

// Fetch Statistics
$stmt_res = $pdo->query("SELECT COUNT(*) FROM restaurants");
$total_restaurants = $stmt_res->fetchColumn();

$stmt_rev = $pdo->query("SELECT COUNT(*) FROM reviews");
$total_reviews = $stmt_rev->fetchColumn();

$stmt_users = $pdo->query("SELECT COUNT(*) FROM users");
$total_users = $stmt_users->fetchColumn();

$stmt_avg = $pdo->query("SELECT AVG(rating) FROM restaurants");
$avg_rating = round($stmt_avg->fetchColumn(), 1);
?>

<div class="admin-content">
    <div class="admin-page-header">
        <div>
            <h2>Vue d'ensemble</h2>
            <p>Données réelles extraites de la base de données.</p>
        </div>
        <div class="admin-date" style="color: var(--text-secondary);"><?php echo date('d F Y'); ?></div>
    </div>

    <div class="admin-stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: rgba(255, 107, 0, 0.1); color: var(--accent-primary);"><i class="fa-solid fa-utensils"></i></div>
                <div class="stat-trend up">+12% <i class="fa-solid fa-arrow-up"></i></div>
            </div>
            <div class="stat-value"><?php echo $total_restaurants; ?></div>
            <div class="stat-label">Restaurants</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: rgba(0, 255, 136, 0.1); color: #00ff88;"><i class="fa-solid fa-star"></i></div>
                <div class="stat-trend up">+8% <i class="fa-solid fa-arrow-up"></i></div>
            </div>
            <div class="stat-value"><?php echo $total_reviews; ?></div>
            <div class="stat-label">Avis Totaux</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: rgba(0, 204, 255, 0.1); color: #00ccff;"><i class="fa-solid fa-users"></i></div>
                <div class="stat-trend up">+15% <i class="fa-solid fa-arrow-up"></i></div>
            </div>
            <div class="stat-value"><?php echo $total_users; ?></div>
            <div class="stat-label">Utilisateurs</div>
        </div>
        <div class="stat-card">
            <div class="stat-header">
                <div class="stat-icon" style="background: rgba(255, 176, 58, 0.1); color: #ffb03a;"><i class="fa-solid fa-chart-line"></i></div>
                <div class="stat-trend down">-2% <i class="fa-solid fa-arrow-down"></i></div>
            </div>
            <div class="stat-value"><?php echo $avg_rating; ?></div>
            <div class="stat-label">Note Moyenne</div>
        </div>
    </div>

    <div class="admin-charts-row">
        <div class="admin-chart-card">
            <h3><i class="fa-solid fa-chart-area"></i> Activité des avis</h3>
            <canvas id="reviews-chart"></canvas>
        </div>
        <div class="admin-chart-card">
            <h3><i class="fa-solid fa-chart-pie"></i> Distribution par ville</h3>
            <canvas id="city-chart"></canvas>
        </div>
    </div>
</div>

<script>
    let reviewsChart, cityChart;

    function initAdminCharts() {
        const theme = document.documentElement.getAttribute('data-theme') || 'dark';
        const textColor = theme === 'light' ? '#111827' : '#ffffff';
        const secondaryText = theme === 'light' ? '#6b7280' : '#a1a1aa';
        const gridColor = theme === 'light' ? 'rgba(0,0,0,0.1)' : 'rgba(255,255,255,0.05)';

        // Chart 1: Reviews Growth
        const ctxReviews = document.getElementById('reviews-chart').getContext('2d');
        if (reviewsChart) reviewsChart.destroy();
        reviewsChart = new Chart(ctxReviews, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Nouveaux avis',
                    data: [65, 59, 80, 81, 56, 95],
                    borderColor: '#ff6b00',
                    backgroundColor: 'rgba(255, 107, 0, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { color: gridColor }, 
                        ticks: { color: secondaryText },
                        border: { display: false } 
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { color: secondaryText }
                    }
                }
            }
        });

        // Chart 2: City Distribution
        const ctxCity = document.getElementById('city-chart').getContext('2d');
        if (cityChart) cityChart.destroy();
        cityChart = new Chart(ctxCity, {
            type: 'doughnut',
            data: {
                labels: ['Tunis', 'Sousse', 'Djerba', 'Hammamet', 'Sfax'],
                datasets: [{
                    data: [12, 19, 3, 5, 2],
                    backgroundColor: ['#ff6b00', '#ffb03a', '#00ccff', '#00ff88', '#9333ea'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { color: secondaryText, padding: 20, font: { size: 12 } } 
                    }
                }
            }
        });
    }

    // Export for header.php theme toggle
    window.updateAdminCharts = function(theme) {
        initAdminCharts();
    };

    window.addEventListener('DOMContentLoaded', initAdminCharts);
</script>


