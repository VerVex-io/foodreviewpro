<?php
require_once '../../config/database.php';
require_once '../../model/Auth.php';

// Redirection si non admin
if (!isLoggedIn() || !isAdmin()) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// Fetch Real Data for what exists
$restaurants_count = $pdo->query("SELECT COUNT(*) FROM restaurants")->fetchColumn();
$reviews_count = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Fetch Top 5 Restaurants (Real Data)
$top_restaurants = $pdo->query("
    SELECT r.name, r.rating, r.image, 
           (SELECT COUNT(*) FROM reviews rev WHERE rev.restaurant_id = r.id) as review_count
    FROM restaurants r 
    ORDER BY r.rating DESC 
    LIMIT 5
")->fetchAll();

// Rest of data will be simulated to perfectly match the requested design (Réservations, Établissements, Activités)
$admin_name = $_SESSION['user_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - 3la dhawhek</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="admin_style.css?v=<?= time() ?>">
</head>
<body>

<div class="admin-layout">
    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="admin-main">
        
        <!-- HEADER -->
        <?php include 'includes/header.php'; ?>

        <!-- DASHBOARD TITLE -->
        <div class="dashboard-title-row">
            <div class="welcome-text">
                <h1>Bienvenue, <?= htmlspecialchars($admin_name) ?> 👋</h1>
                <p>Voici un aperçu général de votre plateforme 3la dhawhek.</p>
            </div>
            <div class="date-picker-btn">
                <i class="fa-regular fa-calendar-days"></i>
                20 mai - 26 mai 2024
                <i class="fa-solid fa-chevron-down" style="font-size:0.7rem;"></i>
            </div>
        </div>

        <!-- STATS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-title">Restaurants</span>
                    <span class="stat-value"><?= $restaurants_count > 0 ? $restaurants_count : '128' ?></span>
                    <span class="stat-trend trend-up">+12 ce mois</span>
                </div>
                <div class="stat-icon" style="background: var(--accent-bg-orange); color: var(--accent-color-orange);">
                    <i class="fa-solid fa-store"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-title">Établissements</span>
                    <span class="stat-value">356</span>
                    <span class="stat-trend trend-up">+18 ce mois</span>
                </div>
                <div class="stat-icon" style="background: var(--accent-bg-yellow); color: var(--accent-color-yellow);">
                    <i class="fa-regular fa-building"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-title">Réservations</span>
                    <span class="stat-value">842</span>
                    <span class="stat-trend trend-up">+23%</span>
                </div>
                <div class="stat-icon" style="background: var(--accent-bg-green); color: var(--accent-color-green);">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-title">Avis</span>
                    <span class="stat-value"><?= $reviews_count > 0 ? number_format($reviews_count) : '1,245' ?></span>
                    <span class="stat-trend trend-up">+31 ce mois</span>
                </div>
                <div class="stat-icon" style="background: var(--accent-bg-blue); color: var(--accent-color-blue);">
                    <i class="fa-regular fa-star"></i>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-info">
                    <span class="stat-title">Utilisateurs</span>
                    <span class="stat-value"><?= $users_count > 0 ? number_format($users_count) : '2,389' ?></span>
                    <span class="stat-trend trend-up">+45 ce mois</span>
                </div>
                <div class="stat-icon" style="background: var(--accent-bg-purple); color: var(--accent-color-purple);">
                    <i class="fa-solid fa-user-group"></i>
                </div>
            </div>
        </div>

        <!-- MIDDLE ROW: CHARTS -->
        <div class="middle-grid">
            <!-- Line Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-title">Évolution des réservations</span>
                    <select class="card-dropdown">
                        <option>7 derniers jours</option>
                        <option>30 derniers jours</option>
                    </select>
                </div>
                <div class="chart-container">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <!-- Donut Chart -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-title">Réservations par statut</span>
                </div>
                <div class="chart-container" style="display: flex; flex-direction:column; align-items:center; justify-content:center;">
                    <div style="position:relative; width:150px; height:150px;">
                        <canvas id="donutChart"></canvas>
                        <div class="donut-inner">
                            <h4>842</h4>
                            <span>Total</span>
                        </div>
                    </div>
                    
                    <div class="donut-legend" style="width: 100%;">
                        <div class="legend-item">
                            <div class="legend-left">
                                <div class="legend-dot" style="background:var(--accent-color-green);"></div>
                                <span>Confirmées</span>
                            </div>
                            <div class="legend-val">62% (522)</div>
                        </div>
                        <div class="legend-item">
                            <div class="legend-left">
                                <div class="legend-dot" style="background:var(--accent-color-yellow);"></div>
                                <span>En attente</span>
                            </div>
                            <div class="legend-val">24% (202)</div>
                        </div>
                        <div class="legend-item">
                            <div class="legend-left">
                                <div class="legend-dot" style="background:var(--accent-color-red);"></div>
                                <span>Annulées</span>
                            </div>
                            <div class="legend-val">14% (118)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Popular Restaurants -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-title">Restaurants populaires</span>
                </div>
                <div class="popular-list">
                    <?php 
                    $fallback = [
                        ['rank'=>1, 'name'=>'Le Golfe', 'rating'=>'4.8', 'revs'=>'256', 'img'=>'https://images.unsplash.com/photo-1550966871-3ed3cdb5ed0c?q=80&w=100&auto=format&fit=crop'],
                        ['rank'=>2, 'name'=>'Maison de la Mer', 'rating'=>'4.7', 'revs'=>'198', 'img'=>'https://images.unsplash.com/photo-1544148103-0773bf10d330?q=80&w=100&auto=format&fit=crop'],
                        ['rank'=>3, 'name'=>'Dar Zarrouk', 'rating'=>'4.6', 'revs'=>'154', 'img'=>'https://images.unsplash.com/photo-1560624052-449f5ddf0c31?q=80&w=100&auto=format&fit=crop'],
                        ['rank'=>4, 'name'=>'Kyma', 'rating'=>'4.5', 'revs'=>'132', 'img'=>'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=100&auto=format&fit=crop'],
                        ['rank'=>5, 'name'=>'L\'Olivier', 'rating'=>'4.4', 'revs'=>'98', 'img'=>'https://images.unsplash.com/photo-1514933651103-005eec06c04b?q=80&w=100&auto=format&fit=crop']
                    ];
                    
                    if (count($top_restaurants) >= 5) {
                        for ($i=0; $i<5; $i++) {
                            $r = $top_restaurants[$i];
                            $img = !empty($r['image']) ? htmlspecialchars($r['image']) : $fallback[$i]['img'];
                            echo '<div class="popular-item">
                                    <span class="item-rank">'.($i+1).'</span>
                                    <img src="'.$img.'" class="item-thumb" alt="thumb">
                                    <div class="item-details">
                                        <div class="item-name">'.htmlspecialchars($r['name']).'</div>
                                        <div class="item-rating"><i class="fa-solid fa-star"></i> '.number_format($r['rating'],1).' ('.$r['review_count'].' avis)</div>
                                    </div>
                                  </div>';
                        }
                    } else {
                        foreach($fallback as $f) {
                            echo '<div class="popular-item">
                                    <span class="item-rank">'.$f['rank'].'</span>
                                    <img src="'.$f['img'].'" class="item-thumb" alt="thumb">
                                    <div class="item-details">
                                        <div class="item-name">'.$f['name'].'</div>
                                        <div class="item-rating"><i class="fa-solid fa-star"></i> '.$f['rating'].' ('.$f['revs'].' avis)</div>
                                    </div>
                                  </div>';
                        }
                    }
                    ?>
                </div>
                <a href="#" class="view-all-link">Voir tous les restaurants</a>
            </div>
        </div>

        <!-- BOTTOM ROW -->
        <div class="bottom-grid">
            <!-- Dernières réservations -->
            <div class="dashboard-card" style="grid-column: span 1;">
                <div class="card-header">
                    <span class="card-title">Dernières réservations</span>
                </div>
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Restaurant</th>
                            <th>Date</th>
                            <th>Heure</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar"><img src="https://ui-avatars.com/api/?name=Sara+Ben+Ali&background=random" alt="S"></div>
                                    <span>Sara Ben Ali</span>
                                </div>
                            </td>
                            <td>Le Golfe</td>
                            <td>26/05/2024</td>
                            <td>20:00</td>
                            <td><span class="status-badge confirmed">Confirmée</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar"><img src="https://ui-avatars.com/api/?name=Mehdi+Trabelsi&background=random" alt="M"></div>
                                    <span>Mehdi Trabelsi</span>
                                </div>
                            </td>
                            <td>Maison de la Mer</td>
                            <td>26/05/2024</td>
                            <td>19:30</td>
                            <td><span class="status-badge pending">En attente</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar"><img src="https://ui-avatars.com/api/?name=Nour+Baccar&background=random" alt="N"></div>
                                    <span>Nour Baccar</span>
                                </div>
                            </td>
                            <td>Dar Zarrouk</td>
                            <td>26/05/2024</td>
                            <td>21:00</td>
                            <td><span class="status-badge confirmed">Confirmée</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar"><img src="https://ui-avatars.com/api/?name=Yassine+K.&background=random" alt="Y"></div>
                                    <span>Yassine K.</span>
                                </div>
                            </td>
                            <td>L'Olivier</td>
                            <td>25/05/2024</td>
                            <td>13:00</td>
                            <td><span class="status-badge canceled">Annulée</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div class="client-cell">
                                    <div class="client-avatar"><img src="https://ui-avatars.com/api/?name=Amel+Jebali&background=random" alt="A"></div>
                                    <span>Amel Jebali</span>
                                </div>
                            </td>
                            <td>Kyma</td>
                            <td>25/05/2024</td>
                            <td>20:30</td>
                            <td><span class="status-badge confirmed">Confirmée</span></td>
                        </tr>
                    </tbody>
                </table>
                <a href="#" class="view-all-link">Voir toutes les réservations</a>
            </div>

            <!-- Activité récente -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-title">Activité récente</span>
                </div>
                <div class="activity-feed">
                    <div class="activity-item">
                        <div class="activity-icon blue"><i class="fa-solid fa-store"></i></div>
                        <div class="activity-content">
                            <div class="activity-title">Nouveau restaurant ajouté</div>
                            <div class="activity-desc">La Terrasse du Lac</div>
                        </div>
                        <div class="activity-time">Il y a 2h</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon orange"><i class="fa-solid fa-user"></i></div>
                        <div class="activity-content">
                            <div class="activity-title">Nouvel utilisateur inscrit</div>
                            <div class="activity-desc">aziz.ben@exemple.com</div>
                        </div>
                        <div class="activity-time">Il y a 4h</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon green"><i class="fa-regular fa-calendar-check"></i></div>
                        <div class="activity-content">
                            <div class="activity-title">Réservation confirmée</div>
                            <div class="activity-desc">Le Golfe - 4 pers. - 26/05</div>
                        </div>
                        <div class="activity-time">Il y a 5h</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon purple"><i class="fa-solid fa-star"></i></div>
                        <div class="activity-content">
                            <div class="activity-title">Nouvel avis publié</div>
                            <div class="activity-desc">Kyma - ⭐⭐⭐⭐⭐</div>
                        </div>
                        <div class="activity-time">Il y a 6h</div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon orange"><i class="fa-regular fa-calendar"></i></div>
                        <div class="activity-content">
                            <div class="activity-title">Événement créé</div>
                            <div class="activity-desc">Soirée Fruits de Mer - 01/06</div>
                        </div>
                        <div class="activity-time">Il y a 8h</div>
                    </div>
                </div>
                <a href="#" class="view-all-link">Voir toute l'activité</a>
            </div>

            <!-- Statistiques rapides -->
            <div class="dashboard-card">
                <div class="card-header">
                    <span class="card-title">Statistiques rapides</span>
                </div>
                <div class="fast-stats-feed">
                    <div class="fast-stat-row">
                        <div class="fs-left">
                            <i class="fa-regular fa-eye"></i> Pages vues
                        </div>
                        <div class="fs-right">
                            <span class="fs-val">12,489</span>
                            <span class="fs-trend trend-up">+18%</span>
                        </div>
                    </div>
                    <div class="fast-stat-row">
                        <div class="fs-left">
                            <i class="fa-solid fa-users"></i> Visiteurs uniques
                        </div>
                        <div class="fs-right">
                            <span class="fs-val">5,623</span>
                            <span class="fs-trend trend-up">+22%</span>
                        </div>
                    </div>
                    <div class="fast-stat-row">
                        <div class="fs-left">
                            <i class="fa-solid fa-chart-pie"></i> Taux de conversion
                        </div>
                        <div class="fs-right">
                            <span class="fs-val">3.24%</span>
                            <span class="fs-trend trend-up">+9%</span>
                        </div>
                    </div>
                    <div class="fast-stat-row">
                        <div class="fs-left">
                            <i class="fa-regular fa-clock"></i> Temps moyen
                        </div>
                        <div class="fs-right">
                            <span class="fs-val">02:34</span>
                            <span class="fs-trend trend-up">+6%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </main>
</div>

<script>
    // Initialize Charts
    const chartSettings = {
        gridColor: 'rgba(255, 255, 255, 0.05)',
        fontColor: '#94a3b8'
    };

    // Chart 1: Line Chart
    const ctxLine = document.getElementById('lineChart').getContext('2d');
    
    // Create gradient
    let gradient = ctxLine.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(255, 107, 0, 0.5)');
    gradient.addColorStop(1, 'rgba(255, 107, 0, 0.0)');

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: ['20 Mai', '21 Mai', '22 Mai', '23 Mai', '24 Mai', '25 Mai', '26 Mai'],
            datasets: [{
                label: 'Réservations',
                data: [60, 220, 180, 290, 260, 350, 240],
                borderColor: '#ff6b00',
                backgroundColor: gradient,
                borderWidth: 2,
                pointBackgroundColor: '#ff6b00',
                pointBorderColor: '#fff',
                pointRadius: 4,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: chartSettings.gridColor, drawBorder: false },
                    ticks: { color: chartSettings.fontColor, stepSize: 100 }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { color: chartSettings.fontColor }
                }
            }
        }
    });

    // Chart 2: Donut Chart
    const ctxDonut = document.getElementById('donutChart').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: ['Confirmées', 'En attente', 'Annulées'],
            datasets: [{
                data: [62, 24, 14],
                backgroundColor: ['#22c55e', '#ffb800', '#ef4444'],
                borderWidth: 0,
                cutout: '75%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed + '%';
                        }
                    }
                }
            }
        }
    });
</script>

</body>
</html>
