// Données chargées depuis la base de données MySQL via PHP
let mockRestaurants = [];

const getSafeFavorites = () => {
    try {
        const parsed = JSON.parse(localStorage.getItem('atng_favorites'));
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
};

const getSafeHistory = () => {
    try {
        const parsed = JSON.parse(localStorage.getItem('atng_history'));
        return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
        return [];
    }
};

const app = {
    map: null,
    markers: [],
    currentFilter: 'all',
    currentTagFilter: null,
    currentBudget: 'all',
    searchQuery: '',
    favorites: getSafeFavorites(),
    history: getSafeHistory(),
    adminCharts: {},
    trendingSwiper: null,
    userLocation: null,
    userMarker: null,
    addMap: null,
    addMarker: null,


    init() {
        try {
            this.loadData();
            this.renderHomeGrid();
            this.renderExploreList();
            this.initAddForm();
            this.initSearch();
            this.renderTagsFilter();
            this.renderAdminDashboard(); // Initialiser les stats admin si dispos
            this.initTheme(); // Appliquer le thème sauvegardé proprement
            this.showHome();

            this.initScrollEffects();
            this.initScrollReveal();

            // Raccourci clavier global pour la recherche (Ctrl+K ou Cmd+K)
            window.addEventListener('keydown', (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    this.toggleSearchOverlay();
                }
                if (e.key === 'Escape' && document.getElementById('search-overlay').classList.contains('active')) {
                    this.toggleSearchOverlay();
                }
            });
        } catch (e) {
            console.error("App init error:", e);
        }
    },

    toggleSearchOverlay() {
        const overlay = document.getElementById('search-overlay');
        const input = document.getElementById('overlay-search-input');
        if (!overlay) return;

        overlay.classList.toggle('active');
        if (overlay.classList.contains('active')) {
            overlay.style.display = 'flex';
            setTimeout(() => input.focus(), 100);
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
            setTimeout(() => overlay.style.display = 'none', 500);
        }
    },

    initScrollEffects() {
        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    },

    initScrollReveal() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Apply to any element with .reveal class
        document.querySelectorAll('.reveal').forEach(el => observer.observe(el));
    },

    showToast(message, type = 'info') {
        const toastId = 'app-toast-container';
        let container = document.getElementById(toastId);
        if (!container) {
            container = document.createElement('div');
            container.id = toastId;
            container.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        let iconColor = 'var(--text-primary)';
        let icon = 'fa-circle-info';
        
        if (type === 'error') {
            iconColor = '#ff4444';
            icon = 'fa-circle-exclamation';
        } else if (type === 'success') {
            iconColor = '#00ff88';
            icon = 'fa-circle-check';
        } else if (type === 'warning') {
            iconColor = '#ffb03a';
            icon = 'fa-circle-exclamation';
        }

        toast.style.cssText = `
            background: var(--bg-card);
            color: var(--text-primary);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            animation: slideInRightToast 0.3s ease forwards;
            border-left: 4px solid ${iconColor};
        `;

        toast.innerHTML = `<i class="fa-solid ${icon}" style="color: ${iconColor}; font-size: 1.2rem;"></i> <span>${message}</span>`;
        
        container.appendChild(toast);

        if (!document.getElementById('toast-styles')) {
            const style = document.createElement('style');
            style.id = 'toast-styles';
            style.textContent = `
                @keyframes slideInRightToast {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes fadeOutRightToast {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }

        setTimeout(() => {
            toast.style.animation = 'fadeOutRightToast 0.3s ease forwards';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 3500);
    },

    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Rayon de la terre en km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        return R * c; 
    },

    getUserLocation() {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition((position) => {
                this.userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                if (this.map) {
                    if (this.userMarker) this.map.removeLayer(this.userMarker);
                    const userIcon = L.divIcon({
                        className: 'custom-user-marker',
                        html: '<i class="fa-solid fa-street-view"></i>',
                        iconSize: [40, 40],
                        iconAnchor: [20, 20]
                    });
                    this.userMarker = L.marker([this.userLocation.lat, this.userLocation.lng], {icon: userIcon, zIndexOffset: 1000}).addTo(this.map);
                    this.map.flyTo([this.userLocation.lat, this.userLocation.lng], 14);
                }
                
                // Once position is known, re-render the list
                if (document.getElementById('filter-sort') && document.getElementById('filter-sort').value === 'distance') {
                    this.renderExploreList();
                }
            }, (error) => {
                console.warn("Géolocalisation refusée:", error.message);
                // Reset sort to rating silently, no annoying alert
                const sel = document.getElementById('filter-sort');
                if (sel) sel.value = 'rating';
                this.renderExploreList();
            });
        } else {
            console.warn("Géolocalisation non supportée.");
            const sel = document.getElementById('filter-sort');
            if (sel) sel.value = 'rating';
            this.renderExploreList();
        }
    },

    loadData() {
        if (!window.APP_RESTAURANTS || window.APP_RESTAURANTS.length === 0) {
            console.warn("Aucune donnée reçue depuis le serveur.");
            return;
        }
        mockRestaurants = window.APP_RESTAURANTS.map(r => {
            const count = parseInt(r.reviews_count) || 0;
            const menu = (r.menu || []).map(m => ({
                name: m.name,
                price: parseFloat(m.price),
                isBestSeller: parseInt(m.is_best_seller) === 1
            }));

            return {
                id: parseInt(r.id),
                name: r.name,
                category: r.category,
                rating: parseFloat(r.rating),
                reviewsCount: count,
                image: (r.image && !r.image.startsWith('http')) ? '../' + r.image : r.image,
                description: r.description,
                tags: r.tags ? r.tags.split(',').map(t => t.trim()) : [],
                location: {
                    lat: parseFloat(r.location_lat),
                    lng: parseFloat(r.location_lng)
                },
                menu: menu.map(m => ({
                    ...m,
                    price: m.price % 1 === 0 ? parseInt(m.price) + ' DT' : m.price + ' DT'
                })),
                reviews: (r.reviews || []).map(rev => ({
                    user: rev.user_name || 'Anonyme',
                    date: rev.created_at
                        ? new Date(rev.created_at).toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })
                        : 'Récemment',
                    text: rev.text,
                    rating: parseInt(rev.rating)
                })),
                stats: {
                    food: parseFloat(r.stat_food || 4.0),
                    service: parseFloat(r.stat_service || 4.0),
                    price: parseFloat(r.stat_price || 4.0),
                    ambiance: parseFloat(r.stat_ambiance || 4.0),
                    location: parseFloat(r.stat_location || 4.0)
                }
            };
        });
    },

    toggleFavorite(id, event) {
        if (event) event.stopPropagation(); // Évite d'ouvrir la modale du restaurant
        
        id = parseInt(id);
        const index = this.favorites.findIndex(favId => parseInt(favId) === id);
        const isBecomingFav = (index === -1);
        
        if (!isBecomingFav) {
            this.favorites.splice(index, 1);
        } else {
            this.favorites.push(id);
        }
        localStorage.setItem('atng_favorites', JSON.stringify(this.favorites));
        
        // Mettre à jour visuellement UNIQUEMENT les boutons coeurs (pour ne pas casser le Slider Swiper)
        // On cherche tous les boutons qui appellent app.toggleFavorite avec cet id
        const buttons = document.querySelectorAll(`button[onclick*="toggleFavorite(${id}"]`);
        buttons.forEach(btn => {
            if (isBecomingFav) {
                btn.classList.add('active');
                btn.innerHTML = '<i class="fa-solid fa-heart"></i>';
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '<i class="fa-regular fa-heart"></i>';
            }
        });
        
        // Mettre à jour la vue profil complet uniquement si on est dessus
        const profileView = document.getElementById('profile-view');
        if (profileView && !profileView.classList.contains('hidden')) {
            this.renderFavorites();
        }
    },

    initTheme() {
        // Le script inline dans le head l'applique déjà, mais on s'assure de la cohérence ici
        const theme = localStorage.getItem('atng_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', theme);
    },

    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('atng_theme', newTheme);
        
        // Mettre à jour les composants externes (Maps, Charts)
        this.updateMapTiles(newTheme);
        this.updateChartTheme(newTheme);
        
        this.showToast(`Mode ${newTheme === 'light' ? 'Clair' : 'Sombre'} activé`, 'success');
    },

    updateMapTiles(theme) {
        const tileUrl = theme === 'light' 
            ? 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
            : 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
            
        if (this.map) {
            this.map.eachLayer(layer => {
                if (layer instanceof L.TileLayer) this.map.removeLayer(layer);
            });
            L.tileLayer(tileUrl).addTo(this.map);
        }
        
        if (this.addMap) {
            this.addMap.eachLayer(layer => {
                if (layer instanceof L.TileLayer) this.addMap.removeLayer(layer);
            });
            L.tileLayer(tileUrl).addTo(this.addMap);
        }
    },

    updateChartTheme(theme) {
        const textColor = theme === 'light' ? '#111827' : '#ffffff';
        const gridColor = theme === 'light' ? 'rgba(0,0,0,0.1)' : 'rgba(255,255,255,0.1)';
        
        // Mettre à jour les instances globales de Chart.js si elles existent
        if (window.radarChartInstance) {
            window.radarChartInstance.options.scales.r.grid.color = gridColor;
            window.radarChartInstance.options.scales.r.angleLines.color = gridColor;
            window.radarChartInstance.options.scales.r.pointLabels.color = textColor;
            window.radarChartInstance.update();
        }
        
        // Pour les graphiques admin
        Object.values(this.adminCharts).forEach(chart => {
            if (chart.options.scales && chart.options.scales.x) {
                chart.options.scales.x.grid.color = gridColor;
                chart.options.scales.y.grid.color = gridColor;
                chart.options.scales.x.ticks.color = textColor;
                chart.options.scales.y.ticks.color = textColor;
                chart.update();
            }
        });
    },

    handleSearch(event) {
        this.searchQuery = event.target.value.toLowerCase().trim();
        // If we are not in explore view, go there to see results? 
        // Let's just update home and explore if they are visible
        if (!document.getElementById('explore-view').classList.contains('hidden')) {
            this.renderExploreList();
        } else if (!document.getElementById('home-view').classList.contains('hidden')) {
            this.renderHomeGrid();
        } else {
            this.showExplore();
            this.renderExploreList();
        }
    },

    setBudget(budget) {
        this.currentBudget = budget;
        // Mettre à jour visuellement les boutons
        document.querySelectorAll('.budget-btn').forEach(btn => {
            btn.classList.remove('active');
            if(btn.innerText.includes(budget) || (budget === 'all' && btn.innerText === 'Tous')) {
                btn.classList.add('active');
            }
        });
        this.renderExploreList();
    },

    showProfile() {
        if (!window.IS_LOGGED_IN) {
            window.location.href = 'login.php';
            return;
        }
        document.querySelectorAll('.view').forEach(v => v.classList.add('hidden'));
        document.getElementById('profile-view').classList.remove('hidden');
        document.querySelectorAll('.nav-links a').forEach(l => l.classList.remove('active'));
        if (document.getElementById('nav-profile')) document.getElementById('nav-profile').classList.add('active');
        
        this.renderStats();
        this.renderFavorites();
        this.renderHistory();
        this.renderBadges();
    },

    renderFavorites() {
        const list = document.getElementById('profile-favorites-list');
        if (!list) return;
        
        const favRests = mockRestaurants.filter(r => this.favorites.some(favId => parseInt(favId) === r.id));
        
        if (favRests.length === 0) {
            list.innerHTML = `<p style="color: var(--text-secondary); grid-column: 1/-1;">Vous n'avez pas encore ajouté de favoris.</p>`;
            list.style.display = 'block';
            return;
        }
        list.style.display = 'grid';
        list.style.gridTemplateColumns = 'repeat(auto-fill, minmax(350px, 1fr))';
        list.style.gap = '20px';
        
        list.innerHTML = favRests.map(rest => `
            <div class="list-card" onclick="app.openModal(${rest.id})">
                <div class="list-card-img-wrapper" style="position: relative;">
                    <button class="fav-btn active" onclick="app.toggleFavorite(${rest.id}, event)">
                        <i class="fa-solid fa-heart"></i>
                    </button>
                    <img src="${rest.image}" alt="${rest.name}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 12px;">
                </div>
                <div class="list-card-content">
                    <h3>${rest.name}</h3>
                    <p>${rest.category} • ${rest.tags.join(', ')}</p>
                    <div style="display: flex; gap: 15px; align-items: center; margin-top: 10px;">
                        <div class="list-card-rating" style="color: #ffb03a; font-weight: bold;"><i class="fa-solid fa-star"></i> ${rest.rating} (${rest.reviewsCount} avis)</div>
                    </div>
                </div>
            </div>
        `).join('');
    },

    addToHistory(id) {
        id = parseInt(id);
        const index = this.history.indexOf(id);
        if (index !== -1) {
            this.history.splice(index, 1);
        }
        this.history.unshift(id);
        if (this.history.length > 8) this.history.pop();
        localStorage.setItem('atng_history', JSON.stringify(this.history));
    },

    renderHistory() {
        const container = document.getElementById('profile-history-list');
        if (!container) return;

        const historyRests = this.history.map(id => mockRestaurants.find(r => r.id === parseInt(id))).filter(r => r);

        if (historyRests.length === 0) {
            container.innerHTML = `<p style="color: var(--text-secondary);">Aucun historique disponible pour le moment.</p>`;
            return;
        }

        container.style.display = 'grid';
        container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(280px, 1fr))';
        container.style.gap = '15px';

        container.innerHTML = historyRests.map(rest => `
            <div class="list-card mini" onclick="app.openModal(${rest.id})" style="padding: 10px; height: auto; gap: 12px; align-items: center; background: rgba(255,255,255,0.02);">
                <img src="${rest.image}" style="width: 60px; height: 60px; border-radius: 10px; object-fit: cover; flex-shrink: 0;">
                <div style="overflow: hidden;">
                    <h4 style="margin: 0; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${rest.name}</h4>
                    <p style="margin: 2px 0 0; font-size: 0.75rem; color: var(--text-secondary);">${rest.category}</p>
                </div>
            </div>
        `).join('');
    },

    renderStats() {
        const container = document.getElementById('profile-stats-grid');
        if (!container) return;

        const totalFavs = this.favorites.length;
        const totalVisits = this.history.length;
        
        // Calculer l'exploration (villes différentes)
        const exploredVilles = new Set(this.history.map(id => {
            const r = mockRestaurants.find(res => res.id === id);
            return r ? r.category : null;
        }).filter(v => v));
        
        const explorationScore = Math.min(100, Math.round((exploredVilles.size / 24) * 100)); // Sur les 24 gouvernorats
        
        let level = "Gourmet Curieux";
        if (totalFavs >= 10) level = "Critique Gastronomique";
        else if (totalFavs >= 5) level = "Habitué des Bonnes Tables";

        container.innerHTML = `
            <div class="profile-stat-item">
                <div class="val">${totalFavs}</div>
                <div class="lab">Favoris</div>
            </div>
            <div class="profile-stat-item">
                <div class="val">${explorationScore}%</div>
                <div class="lab">Exploration</div>
            </div>
            <div class="profile-stat-item">
                <div class="val">${totalVisits}</div>
                <div class="lab">Visites</div>
            </div>
            <div class="profile-stat-item" style="grid-column: span 1; background: rgba(255, 107, 0, 0.1); border-color: rgba(255,107,0,0.2);">
                <div class="val" style="font-size: 0.9rem; color: var(--accent-primary);">${level}</div>
                <div class="lab">Rang Actuel</div>
            </div>
        `;
    },

    renderBadges() {
        const container = document.getElementById('profile-badges-list');
        if (!container) return;

        const badges = [
            { id: 'fav5', name: 'Le Fidèle', icon: 'fa-gem', desc: '5+ Favoris', unlocked: this.favorites.length >= 5 },
            { id: 'explorer', name: 'Voyageur', icon: 'fa-map-location-dot', desc: '3+ Villes visitées', 
              unlocked: new Set(this.history.map(id => mockRestaurants.find(r => r.id === id)?.category)).size >= 3 },
            { id: 'critique', name: 'Expert', icon: 'fa-feather-pointed', desc: 'Avoir laissé un avis', unlocked: false }, // Pourrait être lié à la DB
            { id: 'tunis', name: 'Citadin', icon: 'fa-mosque', desc: 'Favori à Tunis centre', 
              unlocked: this.favorites.some(id => mockRestaurants.find(r => r.id === id)?.category.includes('Tunis')) }
        ];

        container.innerHTML = badges.map(b => `
            <div class="badge-item ${b.unlocked ? 'unlocked' : 'locked'}">
                <div class="badge-icon"><i class="fa-solid ${b.icon}"></i></div>
                <div class="badge-name">${b.name}</div>
                <div class="badge-desc">${b.desc}</div>
            </div>
        `).join('');
    },

    renderHomeGrid(town = null) {
        const grid = document.getElementById('trending-restaurants');
        if (!grid) return;
        
        // Update currentFilter if a town is passed
        if (town) this.currentFilter = town;

        let filtered = mockRestaurants.filter(r => {
            let matchesSearch = true;
            if (this.searchQuery) {
                matchesSearch = r.name.toLowerCase().includes(this.searchQuery) || r.tags.some(t => t.toLowerCase().includes(this.searchQuery));
            }
            
            let matchesTown = true;
            if (this.currentFilter !== 'all') {
                matchesTown = r.category.toLowerCase().includes(this.currentFilter.toLowerCase());
            }
            
            return matchesSearch && matchesTown;
        });

        grid.innerHTML = filtered.map((rest, index) => {
            const isFav = this.favorites.includes(rest.id) ? 'active' : '';
            return `
            <div class="swiper-slide reveal">
                <div class="restaurant-card" onclick="app.openModal(${rest.id})">
                    <button class="fav-btn ${isFav}" onclick="app.toggleFavorite(${rest.id}, event)">
                        <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart"></i>
                    </button>
                    <div class="card-rating-badge">${rest.rating} <i class="fa-solid fa-star"></i></div>
                    <img src="${rest.image}" alt="${rest.name}" class="card-image">
                    <div class="card-content">
                        <div class="card-title">
                            <h3>${rest.name}</h3>
                        </div>
                        <p class="card-tags">${rest.tags.join(' • ')}</p>
                        <div class="card-footer">
                            <span><i class="fa-solid fa-location-dot"></i> ${rest.category}</span>
                            <span>${rest.reviewsCount} avis</span>
                        </div>
                    </div>
                </div>
            </div>`;
        }).join('');
        
        // Initialize Swiper first so cloned slides exist in DOM
        this.initSwiper();
        this.initScrollReveal();
        
        // Déclencher l'animation d'apparition progressive et initialiser Tilt
        setTimeout(() => {
            const cards = document.querySelectorAll('.trending-swiper .restaurant-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.classList.add('show');
                }, index * 50);
            });

            // Initialisation de l'effet premium Tilt
            if (window.VanillaTilt) {
                VanillaTilt.init(document.querySelectorAll(".restaurant-card"), {
                    max: 15,
                    speed: 400,
                    glare: true,
                    "max-glare": 0.2,
                    perspective: 1000,
                    scale: 1.05
                });
            }
        }, 100);
    },

    initSwiper() {
        if (this.trendingSwiper) {
            this.trendingSwiper.destroy(true, true);
        }

        this.trendingSwiper = new Swiper('.trending-swiper', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            loop: true,
            speed: 600,
            coverflowEffect: {
                rotate: 0,
                stretch: 0,
                depth: 100,
                modifier: 2.5,
                slideShadows: false,
            },
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
                pauseOnMouseEnter: true
            },
            pagination: {
                el: '.swiper-pagination',
                type: 'progressbar',
            },
            breakpoints: {
                // Rétablissement de la densité équilibrée d'origine
                320: { slidesPerView: 1.2, spaceBetween: 20 },
                768: { slidesPerView: 2, spaceBetween: 30 },
                1024: { slidesPerView: 2.8, spaceBetween: 35 },
                1400: { slidesPerView: 3.2, spaceBetween: 40 }
            }




        });
    },


    renderExploreList() {
        const list = document.getElementById('explore-restaurants');
        if (!list) return;
        
        const sortSelect = document.getElementById('filter-sort');
        const sortValue = sortSelect ? sortSelect.value : 'rating';
        
        let filtered = [...mockRestaurants];
        
        if (this.searchQuery !== '') {
            filtered = filtered.filter(r => r.name.toLowerCase().includes(this.searchQuery) || r.tags.some(t => t.toLowerCase().includes(this.searchQuery)));
        }
        
        if (this.currentFilter !== 'all') filtered = filtered.filter(r => r.category.toLowerCase().includes(this.currentFilter.toLowerCase()));
        if (this.currentTagFilter) filtered = filtered.filter(r => r.tags.includes(this.currentTagFilter));
        
        // Simuler le filtre de prix (car pas explicitement dans DB, on va utiliser une heuristique basique id % 3 pour la démo)
        if (this.currentBudget !== 'all') {
            filtered = filtered.filter(r => {
                const b = (r.id % 3 === 0) ? '$$$' : (r.id % 2 === 0 ? '$$' : '$');
                return b === this.currentBudget;
            });
        }
        
        // Calculer les distances si on a la position
        if (this.userLocation) {
            filtered.forEach(r => {
                r.distance = this.calculateDistance(this.userLocation.lat, this.userLocation.lng, r.location.lat, r.location.lng);
            });
        }
        
        // Logique de tri
        if (sortValue === 'distance') {
            if (this.userLocation) {
                filtered.sort((a, b) => a.distance - b.distance);
            } else {
                this.getUserLocation();
                return; // Attendre la position
            }
        } else if (sortValue === 'reviews') {
            filtered.sort((a, b) => b.reviewsCount - a.reviewsCount);
        } else {
            filtered.sort((a, b) => b.rating - a.rating);
        }

        list.innerHTML = filtered.map(rest => {
            const isFav = this.favorites.includes(rest.id) ? 'active' : '';
            return `
            <div class="list-card" onclick="app.openModal(${rest.id})" onmouseenter="app.highlightMarker(${rest.id})" onmouseleave="app.resetMarkers()">
                <div class="list-card-img-wrapper" style="position: relative;">
                    <button class="fav-btn ${isFav}" onclick="app.toggleFavorite(${rest.id}, event)">
                        <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart"></i>
                    </button>
                    <img src="${rest.image}" alt="${rest.name}">
                </div>
                <div class="list-card-content">
                    <h3>${rest.name}</h3>
                    <p>${rest.category} • ${rest.tags.join(', ')}</p>
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <div class="list-card-rating"><i class="fa-solid fa-star"></i> ${rest.rating} (${rest.reviewsCount})</div>
                        ${rest.distance !== undefined ? `<div class="distance-badge"><i class="fa-solid fa-location-arrow"></i> ${rest.distance.toFixed(1)} km</div>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');
    },

    openModal(id) {
        id = parseInt(id);
        this.addToHistory(id);

        const rest = mockRestaurants.find(r => r.id === id);
        if (!rest) return;
        const modalBody = document.getElementById('modal-body');
        
        const canReview = window.IS_LOGGED_IN;
        
        modalBody.innerHTML = `
            <div style="position: relative; height: 400px; overflow: hidden; border-radius: 16px 16px 0 0;">
                <div style="position: absolute; inset: 0; background: url('${rest.image}') center/cover; filter: brightness(0.6) saturate(1.2) blur(3px); transform: scale(1.05);"></div>
                <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(15,15,15,0) 0%, var(--bg-dark) 100%);"></div>
                
                <div style="position: absolute; bottom: 40px; left: 40px; right: 40px; display: flex; align-items: flex-end; justify-content: space-between; gap: 20px; z-index: 2;">
                    <div>
                        <div style="display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
                            <span style="background: rgba(255, 107, 0, 0.2); color: var(--accent-primary); border: 1px solid var(--accent-primary); padding: 8px 16px; border-radius: 30px; font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; backdrop-filter: blur(8px);"><i class="fa-solid fa-utensils" style="margin-right: 6px;"></i> ${rest.category}</span>
                            <span style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 30px; font-size: 0.9rem; font-weight: 700; backdrop-filter: blur(8px);"><i class="fa-solid fa-star" style="color: #ffb03a; margin-right: 6px;"></i> ${rest.rating} (${rest.reviewsCount} avis)</span>
                        </div>
                        <h1 style="font-size: 3.8rem; font-family: 'Outfit', sans-serif; font-weight: 800; margin: 0; line-height: 1.1; text-shadow: 0 4px 25px rgba(0,0,0,0.6); color: #fff;">${rest.name}</h1>
                    </div>
                </div>
            </div>
            
            <div style="padding: 0 40px 40px;">
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 40px; margin-top: -10px; position: relative; z-index: 3;">
                    
                    <!-- Colonne Principale -->
                    <div>
                        <div style="background: var(--bg-card); border: 1px solid rgba(255,255,255,0.05); padding: 35px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); margin-bottom: 40px;">
                            <h3 style="font-size: 1.5rem; margin-bottom: 20px; color: var(--text-primary); display: flex; align-items: center; gap: 12px; font-family: 'Outfit', sans-serif;"><i class="fa-solid fa-quote-left" style="color: var(--accent-primary); opacity: 0.6;"></i> L'Expérience</h3>
                            <p style="color: var(--text-secondary); font-size: 1.1rem; line-height: 1.8; text-align: justify; margin: 0;">${rest.description}</p>
                        </div>
                        
                        <!-- Le Bouton Menu -->
                        <div style="background: linear-gradient(135deg, rgba(255,107,0,0.08) 0%, rgba(255,176,58,0.03) 100%); border: 1px solid rgba(255,107,0,0.15); padding: 40px; border-radius: 20px; text-align: center; margin-bottom: 40px;">
                            <h3 style="font-size: 1.6rem; margin-bottom: 10px; font-family: 'Outfit', sans-serif;"><i class="fa-solid fa-book-open" style="color: var(--accent-primary); margin-right: 10px;"></i> Carte des mets & Boissons</h3>
                            <p style="color: var(--text-secondary); margin-bottom: 25px; font-size: 1.05rem;">Découvrez ce que le chef vous a préparé dans notre menu exclusif.</p>
                            ${rest.menu.length > 0 ? `
                                <a href="menu.php?id=${rest.id}" class="btn-primary" style="display: inline-flex; align-items: center; gap: 12px; padding: 15px 40px; font-size: 1.1rem; border-radius: 30px; box-shadow: 0 8px 25px rgba(255,107,0,0.3); text-decoration: none;">
                                    Consulter le menu <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            ` : '<p style="font-style: italic; opacity: 0.6; margin: 0;">Le menu n\'est pas encore disponible pour cet établissement.</p>'}
                        </div>
                        
                        <!-- Avis et Reviews -->
                        <div class="reviews-section" style="margin-top: 0; padding-top: 0; border: none;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 25px;">
                                <h3 style="font-size: 1.5rem; color: var(--text-primary); margin: 0; font-family: 'Outfit', sans-serif;"><i class="fa-solid fa-comments" style="color: var(--accent-primary); margin-right: 10px;"></i> Avis de la communauté</h3>
                                <div style="background: rgba(255,255,255,0.05); padding: 5px 15px; border-radius: 20px; font-weight: bold; color: #ffb03a;">
                                    ${rest.rating} <i class="fa-solid fa-star"></i>
                                </div>
                            </div>

                            <!-- Formulaire d'ajout d'avis -->
                            <div class="add-review-container" style="background: rgba(255,107,0,0.03); border-radius: 16px; padding: 25px; margin-bottom: 30px;">
                                ${canReview ? `
                                    <div class="add-review-form" id="review-form">
                                        <h4 style="margin-top: 0; color: #fff;">Laisser un avis</h4>
                                        <div class="star-rating-input">
                                            <span class="star" data-value="1" onclick="app.setRating(1)"><i class="fa-regular fa-star"></i></span>
                                            <span class="star" data-value="2" onclick="app.setRating(2)"><i class="fa-regular fa-star"></i></span>
                                            <span class="star" data-value="3" onclick="app.setRating(3)"><i class="fa-regular fa-star"></i></span>
                                            <span class="star" data-value="4" onclick="app.setRating(4)"><i class="fa-regular fa-star"></i></span>
                                            <span class="star" data-value="5" onclick="app.setRating(5)"><i class="fa-regular fa-star"></i></span>
                                            <input type="hidden" id="review-rating" value="0">
                                        </div>
                                        <textarea id="review-text" placeholder="Partagez votre expérience gastronomique..."></textarea>
                                        <button class="btn-primary" onclick="app.submitReview(${rest.id})">Publier l'avis</button>
                                    </div>
                                ` : `
                                    <div class="login-to-review" style="padding: 20px; text-align: center;">
                                        <i class="fa-solid fa-lock" style="font-size: 2rem; color: var(--accent-primary); opacity: 0.5; margin-bottom: 10px; display: block;"></i>
                                        <p style="margin: 0;">Vous devez être <a href="login.php" style="color: var(--accent-primary); font-weight: bold;">connecté</a> pour laisser un avis.</p>
                                    </div>
                                `}
                            </div>

                            <div class="reviews-list">
                                ${rest.reviews.length > 0 ? rest.reviews.map(rev => `
                                    <div class="review-item" style="background: var(--bg-card); border-left: 3px solid var(--accent-primary); border-radius: 0 12px 12px 0; padding: 20px; margin-bottom: 15px;">
                                        <div class="review-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                            <div class="user-info" style="display: flex; align-items: center; gap: 12px;">
                                                <div class="user-avatar" style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--accent-primary), var(--accent-hover)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold;">${rev.user.charAt(0).toUpperCase()}</div>
                                                <div style="display: flex; flex-direction: column;">
                                                    <strong style="color: #fff;">${rev.user}</strong>
                                                    <span style="font-size: 0.8rem; color: var(--text-secondary);">${rev.date}</span>
                                                </div>
                                            </div>
                                            <div class="review-meta" style="color: #ffb03a; letter-spacing: 2px;">
                                                ${'★'.repeat(rev.rating)}${'☆'.repeat(5-rev.rating)}
                                            </div>
                                        </div>
                                        <p class="review-text" style="color: var(--text-secondary); line-height: 1.6; margin: 0;">${rev.text}</p>
                                    </div>
                                `).join('') : '<div style="text-align:center; padding: 30px; background: rgba(255,255,255,0.02); border-radius: 12px;"><p style="opacity:0.5; margin: 0;">Aucun avis pour le moment. Soyez le premier !</p></div>'}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Colonne Latérale -->
                    <div style="position: sticky; top: 20px; align-self: start;">
                        <div style="background: var(--bg-card); padding: 30px; border-radius: 20px; margin-bottom: 25px; box-shadow: 0 10px 40px rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.04);">
                            <h4 style="text-align: center; margin-bottom: 25px; font-family: 'Outfit', sans-serif; color: var(--text-primary); font-size: 1.1rem; letter-spacing: 1px;"><i class="fa-solid fa-chart-pie" style="color: var(--accent-primary); margin-right: 8px;"></i> PROFIL GUSTATIF</h4>
                            <div style="position: relative; width: 100%; aspect-ratio: 1; display: flex; align-items: center; justify-content: center;">
                                <canvas id="restaurantRadar"></canvas>
                            </div>
                        </div>
                        
                        <div style="background: var(--bg-card); padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.04); box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
                            <div style="display: flex; flex-direction: column; gap: 20px;">
                                <div style="display: flex; align-items: center; gap: 15px; color: var(--text-secondary);">
                                    <div style="width: 45px; height: 45px; border-radius: 12px; background: rgba(255,107,0,0.1); display: flex; align-items: center; justify-content: center; color: var(--accent-primary); font-size: 1.2rem;"><i class="fa-solid fa-camera"></i></div>
                                    <span style="font-weight: 500;">Photos certifiées</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 15px; color: var(--text-secondary);">
                                    <div style="width: 45px; height: 45px; border-radius: 12px; background: rgba(255,107,0,0.1); display: flex; align-items: center; justify-content: center; color: var(--accent-primary); font-size: 1.2rem;"><i class="fa-solid fa-map-location-dot"></i></div>
                                    <span style="font-weight: 500;">Géolocalisation vérifiée</span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 15px; color: var(--text-secondary);">
                                    <div style="width: 45px; height: 45px; border-radius: 12px; background: rgba(255,107,0,0.1); display: flex; align-items: center; justify-content: center; color: var(--accent-primary); font-size: 1.2rem;"><i class="fa-solid fa-check-double"></i></div>
                                    <span style="font-weight: 500;">Partenaire de confiance</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Section BFS : Restaurants Similaires -->
                <div class="similar-section" style="margin-top: 50px;">
                    <h3 class="section-title"><i class="fa-solid fa-circle-nodes"></i> Vous aimerez aussi</h3>
                    <div id="similar-restaurants-container">
                        <div class="ai-loading"><i class="fa-solid fa-circle-nodes fa-spin"></i> Analyse BFS...</div>
                    </div>
                </div>

            </div>
        `;
        document.getElementById('restaurant-modal').classList.add('active');
        document.body.style.overflow = 'hidden';

        // Initialiser le graphique Radar
        setTimeout(() => {
            const ctx = document.getElementById('restaurantRadar');
            if (ctx) {
                const theme = document.documentElement.getAttribute('data-theme') || 'dark';
                const textColor = theme === 'light' ? '#111827' : '#ffffff';
                const gridColor = theme === 'light' ? 'rgba(0,0,0,0.1)' : 'rgba(255,255,255,0.1)';

                if (window.radarChartInstance) window.radarChartInstance.destroy();

                window.radarChartInstance = new Chart(ctx, {
                    type: 'radar',
                    data: {
                        labels: ['EXC (TEQ)', 'ACC (AT)', 'PRIX (DEF)', 'AMB (TEC)', 'LOC (VEL)'],
                        datasets: [{
                            label: 'Score Restaurant',
                            data: [rest.stats.food, rest.stats.service, rest.stats.price, rest.stats.ambiance, rest.stats.location],
                            backgroundColor: 'rgba(255, 107, 0, 0.2)',
                            borderColor: '#ff6b00',
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#ff6b00',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        scales: {
                            r: {
                                beginAtZero: true,
                                max: 5,
                                grid: { color: gridColor },
                                angleLines: { color: gridColor },
                                pointLabels: { color: textColor, font: { size: 10, weight: 'bold' } },
                                ticks: { display: false }
                            }
                        },
                        plugins: { legend: { display: false } }
                    }
                });
            }
        }, 100);

        // Lancer BFS pour "Vous aimerez aussi" (asynchrone, non bloquant)
        if (typeof loadSimilarRestaurants === 'function') {
            loadSimilarRestaurants(id);
        }
    },

    setRating(val) {
        const stars = document.querySelectorAll('.star-rating-input .star');
        document.getElementById('review-rating').value = val;
        stars.forEach((s, idx) => {
            const icon = s.querySelector('i');
            if (idx < val) {
                icon.className = 'fa-solid fa-star';
                s.classList.add('active');
            } else {
                icon.className = 'fa-regular fa-star';
                s.classList.remove('active');
            }
        });
    },

    async submitReview(restaurantId) {
        const text = document.getElementById('review-text').value;
        const rating = document.getElementById('review-rating').value;

        if (rating == 0) {
            app.showToast("Veuillez donner une note entre 1 et 5 étoiles.", "warning");
            return;
        }
        if (!text.trim()) {
            app.showToast("Veuillez écrire un commentaire.", "warning");
            return;
        }

        const btn = document.querySelector('#review-form button');
        const originalText = btn.innerText;
        btn.disabled = true;
        btn.innerText = "Envoi...";

        try {
            const response = await fetch('../controller/add_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ restaurant_id: restaurantId, text, rating })
            });

            const result = await response.json();
            if (result.success) {
                app.showToast(result.message, "success");
                setTimeout(() => location.reload(), 1500); // Recharger pour voir l'avis
            } else {
                app.showToast(result.message, "error");
            }
        } catch (error) {
            console.error("Error submitting review:", error);
            app.showToast("Une erreur est survenue.", "error");
        } finally {
            if (!btn.disabled) { // In case the page is reloading, keep button visually disabled
                btn.disabled = false;
                btn.innerText = originalText;
            }
        }
    },

    closeModal() { document.getElementById('restaurant-modal').classList.remove('active'); document.body.style.overflow = 'auto'; },

    initSearch() {
        // Handled via onkeyup="app.handleSearch(event)" in the HTML - no duplicate listener needed
    },

    showHome() { this.toggleView('home-view'); },
    showExplore() { this.toggleView('explore-view'); if (!this.map) setTimeout(() => this.initMap(), 100); else this.map.invalidateSize(); },
    showAdd() { 
        this.toggleView('add-view'); 
        setTimeout(() => this.initAddMap(), 100);
    },
    showAdmin() { 
        this.toggleView('admin-view'); 
        this.renderAdminDashboard();
    },

    toggleView(id) {
        document.querySelectorAll('.view').forEach(v => v.classList.add('hidden'));
        document.getElementById(id)?.classList.remove('hidden');
    },

    initMap() {
        if (this.map) return;
        this.map = L.map('map', { zoomControl: false }).setView([33.8869, 9.5375], 6.5);
        const theme = document.documentElement.getAttribute('data-theme') || 'dark';
        const tileUrl = theme === 'light' 
            ? 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
            : 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
            
        L.tileLayer(tileUrl).addTo(this.map);
        this.addMarkersToMap();
    },

    addMarkersToMap() {
        this.markers = [];
        mockRestaurants.forEach(r => {
            const marker = L.marker([r.location.lat, r.location.lng]).addTo(this.map);
            marker.restaurantId = r.id;
            marker.on('click', () => this.openModal(r.id));
            this.markers.push(marker);
        });
    },

    highlightMarker(id) {
        this.markers.forEach(m => {
            if (m.restaurantId === id) { m.openPopup(); this.map.panTo(m.getLatLng()); }
        });
    },

    resetMarkers() { this.markers.forEach(m => m.closePopup()); },

    renderTagsFilter() {
        const container = document.getElementById('tags-filter-container');
        if (!container || mockRestaurants.length === 0) return;
        const allTags = [...new Set(mockRestaurants.flatMap(r => r.tags))].filter(Boolean);
        container.innerHTML = allTags.map(tag =>
            `<span class="tag-filter-item" onclick="app.setTagFilter('${tag}')">${tag}</span>`
        ).join('');
    },
    setTagFilter(tag) { this.currentTagFilter = tag; this.renderExploreList(); },
    toggleDropdown(id) { document.getElementById(id)?.classList.toggle('active'); },
    selectTown(val, label) {
        this.currentFilter = val;
        document.getElementById('selected-town').textContent = label;
        this.renderHomeGrid(); // currentFilter is already updated above
    },
    initAddMap() {
        if (this.addMap) {
            this.addMap.invalidateSize();
            return;
        }
        
        const center = [36.8065, 10.1815]; // Tunis center
        this.addMap = L.map('add-map-picker').setView(center, 13);
        const theme = document.documentElement.getAttribute('data-theme') || 'dark';
        const tileUrl = theme === 'light' 
            ? 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'
            : 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
            
        L.tileLayer(tileUrl).addTo(this.addMap);
        
        this.addMarker = L.marker(center, { draggable: true }).addTo(this.addMap);
        
        const cityCoords = {
            'Tunis': [36.8065, 10.1815],
            'Ariana': [36.8625, 10.1956],
            'Ben Arous': [36.7531, 10.2222],
            'Manouba': [36.8078, 10.0864],
            'Nabeul': [36.4561, 10.7376],
            'Sousse': [35.8256, 10.6369],
            'Monastir': [35.7780, 10.8262],
            'Mahdia': [35.5047, 11.0622],
            'Sfax': [34.7406, 10.7603],
            'Bizerte': [37.2744, 9.8739],
            'Béja': [36.7256, 9.1817],
            'Jendouba': [36.5011, 8.7802],
            'Le Kef': [36.1822, 8.7147],
            'Siliana': [36.0840, 9.3708],
            'Kairouan': [35.6781, 10.0963],
            'Kasserine': [35.1676, 8.8365],
            'Sidi Bouzid': [35.0382, 9.4849],
            'Gabès': [33.8815, 10.0982],
            'Medenine': [33.3549, 10.5055],
            'Tataouine': [32.9297, 10.4518],
            'Gafsa': [34.4250, 8.7842],
            'Tozeur': [33.9197, 8.1332],
            'Kebili': [33.7044, 8.9690],
            'Zaghouan': [36.4029, 10.1429]
        };

        const updateCoords = (lat, lng, cityName = null) => {
            document.getElementById('add-lat').value = lat.toFixed(6);
            document.getElementById('add-lng').value = lng.toFixed(6);
            const label = cityName ? cityName : `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
            document.getElementById('latlng-display').innerHTML = `<i class="fa-solid fa-check"></i> Position : ${label}`;
        };

        // Listen for city change to auto-pan
        const categorySelect = document.getElementById('add-category');
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => {
                const city = e.target.value;
                if (cityCoords[city]) {
                    const coords = cityCoords[city];
                    this.addMap.flyTo(coords, 15, { animate: true, duration: 1.5 });
                    this.addMarker.setLatLng(coords);
                    updateCoords(coords[0], coords[1], city);
                }
            });
        }

        this.addMap.on('click', (e) => {
            const { lat, lng } = e.latlng;
            this.addMarker.setLatLng([lat, lng]);
            updateCoords(lat, lng);
        });

        this.addMarker.on('dragend', () => {
            const pos = this.addMarker.getLatLng();
            updateCoords(pos.lat, pos.lng);
        });
    },

    async submitRestaurantForm() {
        const form = document.getElementById('add-restaurant-form');
        const submitBtn = document.querySelector('#add-view .btn-primary');
        const originalBtnHtml = submitBtn.innerHTML;

        // Validation basique
        const name = document.getElementById('add-name').value;
        const description = document.getElementById('add-description').value;
        if (!name || !description) {
            this.showToast("Le nom et la description sont requis.", "error");
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement...';

        try {
            const formData = new FormData();
            formData.append('name', name);
            formData.append('category', document.getElementById('add-category').value);
            formData.append('lat', document.getElementById('add-lat').value);
            formData.append('lng', document.getElementById('add-lng').value);
            formData.append('description', description);
            formData.append('tags', document.getElementById('add-tags').value);

            // Menu JSON
            const menu = [];
            document.querySelectorAll('.menu-row').forEach(row => {
                const mName = row.querySelector('.item-name').value;
                const mPrice = row.querySelector('.item-price').value;
                if (mName && mPrice) menu.push({ name: mName, price: mPrice });
            });
            formData.append('menu', JSON.stringify(menu));

            // Image handling (File priority over URL)
            const fileInput = document.getElementById('add-image-file');
            const urlInput = document.getElementById('add-image-url');
            
            if (fileInput.files.length > 0) {
                formData.append('image_file', fileInput.files[0]);
            } else if (urlInput.value) {
                formData.append('image_url', urlInput.value);
            } else {
                this.showToast("Une image (fichier ou URL) est requise.", "error");
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
                return;
            }

            const response = await fetch('../controller/add_restaurant.php', {
                method: 'POST',
                body: formData // Pas de JSON headers car FormData gère les boundary
            });

            const result = await response.json();
            if (result.success) {
                this.showToast(result.message, "success");
                form.reset();
                this.addMarker.setLatLng([36.8065, 10.1815]);
                setTimeout(() => location.reload(), 1500);
            } else {
                this.showToast("Erreur : " + result.message, "error");
            }
        } catch (error) {
            console.error("Submission error:", error);
            this.showToast("Une erreur critique est survenue.", "error");
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
        }
    },


    addMenuRow() {
        const container = document.getElementById('menu-rows-container');
        if (!container) return;

        const div = document.createElement('div');
        div.className = 'menu-row-premium';
        div.style.cssText = `
            display: grid;
            grid-template-columns: 1fr 140px 40px;
            gap: 20px;
            background: rgba(255,255,255,0.03);
            padding: 15px 20px;
            border-radius: 16px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.06);
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        `;
        div.innerHTML = `
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 32px; height: 32px; background: rgba(255,107,0,0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                    <i class="fa-solid fa-utensils" style="color: var(--accent-primary); font-size: 0.9rem;"></i>
                </div>
                <input type="text" placeholder="Nom du plat (ex: Couscous Royal)" class="item-name" 
                    style="flex: 1; background: transparent; border: none; color: #fff; font-family: 'Inter', sans-serif; font-size: 0.95rem; outline: none;">
            </div>
            <div style="display: flex; align-items: center; background: rgba(255,255,255,0.03); padding: 8px 15px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.05);">
                <input type="number" placeholder="0.00" class="item-price" 
                    style="width: 100%; background: transparent; border: none; color: var(--accent-primary); font-weight: 800; text-align: right; font-family: 'Outfit', sans-serif; font-size: 1rem; outline: none; -moz-appearance: textfield;">
                <span style="margin-left: 8px; font-weight: 700; font-size: 0.75rem; color: var(--text-secondary); opacity: 0.8;">DT</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="btn-icon" 
                style="background: rgba(255, 68, 68, 0.1); color: #ff4444; width: 32px; height: 32px; border-radius: 50%; opacity: 0.6; transition: all 0.2s;">
                <i class="fa-solid fa-xmark" style="font-size: 0.8rem;"></i>
            </button>
        `;
        
        div.onmouseover = () => {
            div.style.background = 'rgba(255,255,255,0.05)';
            div.style.borderColor = 'rgba(255,107,0,0.2)';
            div.style.transform = 'translateX(5px)';
        };
        div.onmouseout = () => {
            div.style.background = 'rgba(255,255,255,0.03)';
            div.style.borderColor = 'rgba(255,255,255,0.06)';
            div.style.transform = 'translateX(0)';
        };
        
        container.appendChild(div);
    },

    switchAdminTab(tabName, event) {
        // Update nav items
        document.querySelectorAll('.admin-nav-item').forEach(item => item.classList.remove('active'));
        if (event) event.currentTarget.classList.add('active');

        // Update content
        document.querySelectorAll('.admin-tab-content').forEach(tab => tab.classList.remove('active'));
        document.getElementById(`admin-tab-${tabName}`)?.classList.add('active');
    },

    login() {
        // Redirection vers la page de login car elle gère déjà tout le flux PHP
        window.location.href = 'login.php';
    },

    renderAdminDashboard() {
        const stats = window.APP_STATS;
        const container = document.getElementById('admin-stats-summary');
        if (!stats || !container) return;

        container.innerHTML = `
            <div class="admin-stat-card">
                <i class="fa-solid fa-utensils"></i>
                <div class="val">${stats.restaurants}</div>
                <div class="lab">Restaurants</div>
            </div>
            <div class="admin-stat-card">
                <i class="fa-solid fa-star"></i>
                <div class="val">${stats.reviews}</div>
                <div class="lab">Avis</div>
            </div>
            <div class="admin-stat-card">
                <i class="fa-solid fa-users"></i>
                <div class="val">${stats.users}</div>
                <div class="lab">Utilisateurs</div>
            </div>
            <div class="admin-stat-card">
                <i class="fa-solid fa-chart-line"></i>
                <div class="val">${stats.avg_rating}</div>
                <div class="lab">Note Moyenne</div>
            </div>
        `;

        // Render mini charts if Chart.js is available
        if (typeof Chart !== 'undefined') {
            this.renderAdminCharts();
        }
    },

    renderAdminCharts() {
        const ctxRev = document.getElementById('reviews-chart');
        if (ctxRev && !this.adminCharts.reviews) {
            this.adminCharts.reviews = new Chart(ctxRev, {
                type: 'line',
                data: {
                    labels: ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
                    datasets: [{
                        label: 'Nouveaux Avis',
                        data: [12, 19, 15, 8, 22, 30, 25],
                        borderColor: '#ff6b00',
                        backgroundColor: 'rgba(255, 107, 0, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } } }
            });
        }

        const ctxCity = document.getElementById('city-chart');
        if (ctxCity && !this.adminCharts.city) {
            this.adminCharts.city = new Chart(ctxCity, {
                type: 'doughnut',
                data: {
                    labels: ['Tunis', 'Sousse', 'Djerba', 'Autres'],
                    datasets: [{
                        data: [45, 25, 15, 15],
                        backgroundColor: ['#ff6b00', '#ffb03a', '#00ccff', '#00ff88']
                    }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => app.init());
