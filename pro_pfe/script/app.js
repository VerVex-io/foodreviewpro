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
            this.initSearch();
            this.renderTagsFilter();
            this.switchView('home-view');
            this.initScrollEffects();

            // Init admin dashboard data if admin is logged in
            if (window.APP_ADMIN_DATA) {
                this.renderAdminDashboard();
            }
        } catch (e) {
            console.error("App init error:", e);
        }
    },



    initScrollEffects() {
        console.log("Scroll Effects Initialized v2.0");
        const nav = document.querySelector('.navbar');
        let lastScroll = 0;

        window.addEventListener('scroll', () => {
            const currentScroll = window.scrollY;

            // Background opaque au scroll
            if (currentScroll > 20) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }

            // Disparition totale au scroll vers le bas
            if (currentScroll > lastScroll && currentScroll > 10) {
                // On descend -> Cache
                nav.classList.add('hidden');
            } else {
                // On remonte ou tout en haut -> Montre
                nav.classList.remove('hidden');
            }
            
            lastScroll = currentScroll <= 0 ? 0 : currentScroll; // Avoid negative scroll
        });
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



    handleSearch(event) {
        this.searchQuery = event.target.value.toLowerCase().trim();
        
        // Si on appuie sur Entrée, on lance la recherche IA (TF-IDF)
        if (event.key === 'Enter' && typeof aiSearch === 'function') {
            aiSearch(this.searchQuery);
            return;
        }

        // Si on est sur l'accueil, on bascule sur l'exploration pour voir les résultats
        if (!document.getElementById('home-view').classList.contains('hidden') && this.searchQuery.length > 0) {
            this.showExplore();
        }

        // Mettre à jour la vue active
        if (!document.getElementById('explore-view').classList.contains('hidden')) {
            this.renderExploreList();
        } else if (!document.getElementById('home-view').classList.contains('hidden')) {
            this.renderHomeGrid();
        }
    },

    setBudget(budget) {
        this.currentBudget = budget;
        // Mettre à jour visuellement les boutons via l'attribut data-budget
        document.querySelectorAll('.budget-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.budget === budget) {
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
        this.switchView('profile-view');
        
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
            // Generate initials for the circle logo
            const initials = rest.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
            
            return `
            <div class="swiper-slide">
                <div class="restaurant-card premium-card" onclick="app.openModal(${rest.id})">
                    <!-- Floating Rating -->
                    <div class="premium-rating-badge">
                        <i class="fa-solid fa-star"></i>
                        <span>${rest.rating}</span>
                    </div>

                    <!-- Floating Heart -->
                    <button class="premium-fav-btn ${isFav}" onclick="app.toggleFavorite(${rest.id}, event)">
                        <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart"></i>
                    </button>

                    <div class="premium-card-image-container">
                        <img src="${rest.image}" alt="${rest.name}" class="premium-card-image">
                    </div>

                    <div class="premium-card-content">
                        <!-- Circle Logo -->
                        <div class="premium-circle-logo">
                            <span>${initials}</span>
                        </div>

                        <h3 class="premium-restaurant-name">${rest.name}</h3>
                        <div class="premium-tags">${rest.tags.slice(0, 2).join(' • ').toUpperCase()}</div>
                        
                        <div class="premium-card-footer">
                            <span><i class="fa-solid fa-location-dot"></i> ${rest.category}</span>
                            <span><i class="fa-solid fa-users"></i> ${rest.reviewsCount} avis</span>
                        </div>

                        <button class="premium-discover-btn">DÉCOUVRIR <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>
            </div>`;
        }).join('');
        
        // Initialize Swiper first so cloned slides exist in DOM
        this.initSwiper();

        // Initialisation de l'effet premium Tilt (optionnel mais déjà présent à l'origine)
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
            speed: 1000,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            coverflowEffect: {
                rotate: 5,
                stretch: 0,
                depth: 200,
                modifier: 1.2,
                slideShadows: false,
            },
            pagination: {
                el: '.swiper-pagination',
                type: 'progressbar',
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
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


    showHome() {
        this.switchView('home-view');
        this.renderHomeGrid();
    },

    showExplore() {
        this.switchView('explore-view');
        this.renderExploreList();
        if (this.map) setTimeout(() => this.map.invalidateSize(), 300);
    },

    showAdd() {
        if (!window.IS_LOGGED_IN) {
            window.location.href = 'login.php';
            return;
        }
        this.switchView('add-view');
        if (this.addMap) setTimeout(() => this.addMap.invalidateSize(), 300);
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
        
        // Initials for logo
        const initials = rest.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);

        modalBody.innerHTML = `
            <div class="premium-detail-container">
                <!-- HEADER PREMIUM -->
                <header class="premium-detail-header" style="background-image: url('${rest.image}');">
                    <div class="header-main-content">
                        <div class="restaurant-logo-premium">
                            ${initials}
                        </div>
                        <div class="header-text-info">
                            <div class="badge-coup-de-coeur">
                                <i class="fa-solid fa-crown"></i> Coup de cœur
                            </div>
                            <h1 class="premium-restaurant-title">${rest.name}</h1>
                            <div class="premium-restaurant-subtitle">
                                LUXE • VUE MER • PATRIMOINE
                            </div>
                            <div class="header-stats">
                                <div class="stat-item-premium">
                                    <i class="fa-solid fa-star"></i>
                                    <span>${rest.rating}</span>
                                    <span style="color: var(--text-secondary); font-size: 0.8rem; font-weight: 400;">(${rest.reviewsCount} avis)</span>
                                </div>
                                <div class="stat-item-premium">
                                    <i class="fa-solid fa-location-dot" style="color: var(--accent-primary);"></i>
                                    <span style="font-weight: 400; color: var(--text-secondary);">${rest.category}, Tunisie</span>
                                </div>
                                <div class="stat-item-premium">
                                    <div class="status-badge-premium open">
                                        <i class="fa-solid fa-circle" style="font-size: 0.5rem; vertical-align: middle; margin-right: 5px;"></i> Ouvert • 12h00 – 23h30
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- TAB BAR PREMIUM -->
                <nav class="premium-tab-bar">
                    <div class="premium-tab-item active" onclick="app.switchModalTab('apercu', event)">
                        <i class="fa-solid fa-house"></i> Aperçu
                    </div>
                    <div class="premium-tab-item" onclick="app.switchModalTab('menu', event)">
                        <i class="fa-solid fa-utensils"></i> Menu
                    </div>
                    <div class="premium-tab-item" onclick="app.switchModalTab('avis', event)">
                        <i class="fa-solid fa-star"></i> Avis
                    </div>
                    <div class="premium-tab-item" onclick="app.switchModalTab('photos', event)">
                        <i class="fa-solid fa-image"></i> Photos
                    </div>
                    <div class="premium-tab-item" onclick="app.switchModalTab('about', event)">
                        <i class="fa-solid fa-info-circle"></i> À propos
                    </div>
                </nav>

                <!-- BODY GRID -->
                <div class="premium-body-grid">
                    <!-- LEFT CONTENT -->
                    <div class="premium-main-content">
                        <!-- SECTION APERCU -->
                        <div class="premium-tab-content active" id="modal-section-apercu">
                            <h2 class="premium-section-title"><i class="fa-solid fa-quote-left"></i> À propos du restaurant</h2>
                            <div class="premium-about-content">
                                ${rest.description}
                            </div>
                            
                            <!-- FEATURE ICONS -->
                            <div class="feature-icons-grid">
                                <div class="feature-icon-item">
                                    <div class="feature-icon-circle"><i class="fa-solid fa-water"></i></div>
                                    <div class="feature-icon-label">Vue sur mer</div>
                                </div>
                                <div class="feature-icon-item">
                                    <div class="feature-icon-circle"><i class="fa-solid fa-bell-concierge"></i></div>
                                    <div class="feature-icon-label">Service premium</div>
                                </div>
                                <div class="feature-icon-item">
                                    <div class="feature-icon-circle"><i class="fa-solid fa-couch"></i></div>
                                    <div class="feature-icon-label">Ambiance élégante</div>
                                </div>
                                <div class="feature-icon-item">
                                    <div class="feature-icon-circle"><i class="fa-solid fa-utensils"></i></div>
                                    <div class="feature-icon-label">Cuisine raffinée</div>
                                </div>
                            </div>

                            <!-- SECTION PHOTOS PREVIEW -->
                            <div class="premium-photo-grid">
                                <div class="photo-item-main" onclick="app.openLightbox('${rest.image}')" style="cursor: zoom-in;">
                                    <img src="${rest.image}" alt="Main">
                                </div>
                                <div class="photo-item-small" onclick="app.openLightbox('https://images.unsplash.com/photo-1550966841-3ee71448f520?q=80&w=800')" style="cursor: zoom-in;">
                                    <img src="https://images.unsplash.com/photo-1550966841-3ee71448f520?q=80&w=800" alt="S1">
                                </div>
                                <div class="photo-item-small" onclick="app.openLightbox('https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?q=80&w=800')" style="cursor: zoom-in;">
                                    <img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?q=80&w=800" alt="S2">
                                    <div class="photo-overlay-more">+18</div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION MENU -->
                        <div class="premium-tab-content" id="modal-section-menu" style="display: none;">
                            <h2 class="premium-section-title"><i class="fa-solid fa-utensils"></i> Carte & Menu</h2>
                            <div class="menu-preview-row" style="grid-template-columns: repeat(2, 1fr);">
                                ${rest.menu.map(m => `
                                    <div class="menu-card-premium">
                                        <img src="https://images.unsplash.com/photo-1544025162-d76694265947?q=80&w=800" class="menu-card-img">
                                        <div class="menu-card-info">
                                            <div class="menu-card-name">${m.name}</div>
                                            <div class="menu-card-price">${m.price}</div>
                                        </div>
                                    </div>
                                `).join('')}
                                ${rest.menu.length === 0 ? '<p style="color: var(--text-secondary); font-style: italic;">Le menu n\'est pas encore disponible.</p>' : ''}
                            </div>
                        </div>

                        <!-- SECTION AVIS -->
                        <div class="premium-tab-content" id="modal-section-avis" style="display: none;">
                            <h2 class="premium-section-title"><i class="fa-solid fa-star"></i> Avis des clients</h2>

                            ${canReview ? `
                            <!-- REVIEW FORM (logged in) -->
                            <div id="review-form" style="background: rgba(255,107,0,0.04); border: 1px solid rgba(255,107,0,0.15); padding: 28px; border-radius: 20px; margin-bottom: 35px;">
                                <h3 style="color: #fff; font-size: 1.1rem; margin-bottom: 20px; font-family: 'Outfit', sans-serif;">
                                    <i class="fa-solid fa-pen-to-square" style="color: var(--accent-primary); margin-right: 10px;"></i>
                                    Votre avis
                                </h3>
                                <input type="hidden" id="review-rating" value="0">
                                <div class="star-rating-input" style="display: flex; gap: 8px; margin-bottom: 20px;">
                                    ${[1,2,3,4,5].map(i => `
                                        <span class="star" data-val="${i}" onclick="app.setRating(${i})" style="font-size: 2rem; cursor: pointer; color: rgba(255,255,255,0.2); transition: color 0.2s ease; user-select: none;">
                                            <i class="fa-regular fa-star"></i>
                                        </span>
                                    `).join('')}
                                </div>
                                <textarea id="review-text" placeholder="Partagez votre expérience avec la communauté..." rows="4"
                                    style="width: 100%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 16px; color: #fff; font-family: 'Inter', sans-serif; font-size: 0.95rem; resize: vertical; outline: none; transition: border-color 0.3s ease;"
                                    onfocus="this.style.borderColor='var(--accent-primary)'" onblur="this.style.borderColor='rgba(255,255,255,0.1)'"></textarea>
                                <button onclick="app.submitReview(${rest.id})" class="btn-primary" style="margin-top: 16px; padding: 14px 30px; font-weight: 800; border-radius: 12px;">
                                    <i class="fa-solid fa-paper-plane"></i> Publier mon avis
                                </button>
                            </div>
                            ` : `
                            <!-- LOGIN PROMPT (guest) -->
                            <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,255,255,0.1); padding: 30px; border-radius: 20px; margin-bottom: 35px; text-align: center;">
                                <i class="fa-solid fa-lock" style="font-size: 2.5rem; color: var(--accent-primary); margin-bottom: 15px; display: block;"></i>
                                <h4 style="color: #fff; margin-bottom: 10px; font-family: 'Outfit', sans-serif;">Connectez-vous pour laisser un avis</h4>
                                <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 20px;">Rejoignez la communauté Ala Dhawkek et partagez vos expériences.</p>
                                <a href="login.php" class="btn-primary" style="display: inline-flex; padding: 13px 28px; font-weight: 700; border-radius: 12px; text-decoration: none;">
                                    <i class="fa-solid fa-user"></i> Se connecter
                                </a>
                            </div>
                            `}

                            <!-- REVIEWS LIST -->
                            <div class="reviews-list-full">
                                ${rest.reviews.map(rev => `
                                    <div class="review-item-premium" style="background: rgba(255,255,255,0.02); padding: 25px; border-radius: 20px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.03);">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                                            <div style="display: flex; align-items: center; gap: 15px;">
                                                <div class="user-avatar" style="width: 45px; height: 45px; border-radius: 12px; background: var(--accent-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem;">${rev.user.charAt(0)}</div>
                                                <div>
                                                    <div style="font-weight: 700; font-size: 1.1rem; color: #fff;">${rev.user}</div>
                                                    <div style="font-size: 0.85rem; color: var(--text-secondary);">${rev.date}</div>
                                                </div>
                                            </div>
                                            <div style="color: #ffb03a; font-size: 1rem;">
                                                ${'★'.repeat(rev.rating)}${'☆'.repeat(5-rev.rating)}
                                            </div>
                                        </div>
                                        <p style="color: var(--text-secondary); font-size: 1.05rem; margin: 0; line-height: 1.7;">${rev.text}</p>
                                    </div>
                                `).join('')}
                                ${rest.reviews.length === 0 ? '<p style="color: var(--text-secondary); font-style: italic; text-align: center; padding: 20px 0;">Aucun avis pour le moment. Soyez le premier !</p>' : ''}
                            </div>
                        </div>

                        <!-- SECTION PHOTOS -->
                        <div class="premium-tab-content" id="modal-section-photos" style="display: none;">
                            <h2 class="premium-section-title"><i class="fa-solid fa-image"></i> Galerie Photos</h2>
                            <div class="premium-photo-grid" style="grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(2, 200px);">
                                <div class="photo-item-small" onclick="app.openLightbox('${rest.image}')" style="cursor: zoom-in;"><img src="${rest.image}" alt="P1"></div>
                                <div class="photo-item-small" onclick="app.openLightbox('https://images.unsplash.com/photo-1550966841-3ee71448f520?q=80&w=800')" style="cursor: zoom-in;"><img src="https://images.unsplash.com/photo-1550966841-3ee71448f520?q=80&w=800" alt="P2"></div>
                                <div class="photo-item-small" onclick="app.openLightbox('https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?q=80&w=800')" style="cursor: zoom-in;"><img src="https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?q=80&w=800" alt="P3"></div>
                                <div class="photo-item-small" onclick="app.openLightbox('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=800')" style="cursor: zoom-in;"><img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=800" alt="P4"></div>
                                <div class="photo-item-small" onclick="app.openLightbox('https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=800')" style="cursor: zoom-in;"><img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?q=80&w=800" alt="P5"></div>
                                <div class="photo-item-small" onclick="app.openLightbox('https://images.unsplash.com/photo-1552566626-52f8b828add9?q=80&w=800')" style="cursor: zoom-in;"><img src="https://images.unsplash.com/photo-1552566626-52f8b828add9?q=80&w=800" alt="P6"></div>
                            </div>
                        </div>

                        <!-- SECTION ABOUT -->
                        <div class="premium-tab-content" id="modal-section-about" style="display: none;">
                            <h2 class="premium-section-title"><i class="fa-solid fa-info-circle"></i> À propos de l'établissement</h2>
                            <div class="premium-about-content">
                                <p>${rest.description}</p>
                                <p>Situé au cœur de ${rest.category}, notre établissement vous accueille dans un cadre raffiné et élégant. Notre chef met un point d'honneur à utiliser des produits locaux et de saison pour vous offrir une expérience culinaire inoubliable.</p>
                                <div style="margin-top: 30px;">
                                    <h4 style="color: #fff; margin-bottom: 15px;">Équipements</h4>
                                    <ul style="list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                        <li><i class="fa-solid fa-check" style="color: var(--accent-primary); margin-right: 10px;"></i> Wi-Fi Gratuit</li>
                                        <li><i class="fa-solid fa-check" style="color: var(--accent-primary); margin-right: 10px;"></i> Terrasse panoramique</li>
                                        <li><i class="fa-solid fa-check" style="color: var(--accent-primary); margin-right: 10px;"></i> Accès handicapé</li>
                                        <li><i class="fa-solid fa-check" style="color: var(--accent-primary); margin-right: 10px;"></i> Parking privé</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- RIGHT SIDEBAR -->
                    <div class="premium-sidebar">


                        <!-- RADAR CHART -->
                        <div style="background: #111112; padding: 25px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.05); margin-top: 30px;">
                            <h4 style="text-align: center; margin-bottom: 20px; font-size: 0.8rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Profil Gustatif</h4>
                            <div style="position: relative; width: 100%; aspect-ratio: 1;">
                                <canvas id="restaurantRadar"></canvas>
                            </div>
                        </div>

                        <!-- PRACTICAL INFO -->
                        <div class="info-sidebar-premium">
                            <div class="info-sidebar-card">
                                <h4 style="margin-bottom: 20px; font-size: 1rem; color: #fff;">Informations pratiques</h4>
                                <div class="info-item">
                                    <i class="fa-solid fa-location-dot"></i>
                                    <div class="info-text">
                                        <strong>Adresse</strong>
                                        <span>${rest.category}, Tunisie</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="fa-solid fa-phone"></i>
                                    <div class="info-text">
                                        <strong>Téléphone</strong>
                                        <span>+216 71 123 456</span>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <i class="fa-solid fa-clock"></i>
                                    <div class="info-text">
                                        <strong>Horaires</strong>
                                        <span>12h00 – 23h30 (Tous les jours)</span>
                                    </div>
                                </div>
                                <a href="#" class="view-map-link"><i class="fa-solid fa-map-marked-alt"></i> Voir sur la carte</a>
                            </div>
                        </div>

                        <!-- RATING SUMMARY -->
                        <div class="rating-summary-card">
                            <h4 style="margin-bottom: 10px; font-size: 1rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px;">Moyenne des avis</h4>
                            <div class="rating-large-number">${rest.rating}</div>
                            <div class="rating-large-stars">
                                ${'★'.repeat(Math.floor(rest.rating))}${'☆'.repeat(5-Math.floor(rest.rating))}
                            </div>
                            <div class="rating-large-count">Basé sur ${rest.reviewsCount} avis certifiés</div>
                        </div>
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
                        labels: ['FOOD', 'SERV', 'PRIC', 'AMB', 'LOC'],
                        datasets: [{
                            label: 'Score',
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
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        }, 100);

        // Lancer BFS pour "Vous aimerez aussi"
        if (typeof loadSimilarRestaurants === 'function') {
            loadSimilarRestaurants(id);
        }
    },

    switchModalTab(tabName, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Update active tab button
        const tabs = document.querySelectorAll('.premium-tab-item');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        } else {
            // Find tab by text content if no event
            tabs.forEach(tab => {
                if (tab.innerText.toLowerCase().includes(tabName.toLowerCase())) {
                    tab.classList.add('active');
                }
            });
        }
        
        // Show/Hide sections
        const sections = document.querySelectorAll('.premium-tab-content');
        sections.forEach(sec => sec.style.display = 'none');
        
        const targetSec = document.getElementById(`modal-section-${tabName}`);
        if (targetSec) {
            targetSec.style.display = 'block';
        }
    },

    openLightbox(src) {
        const lightbox = document.getElementById('lightbox');
        const img = document.getElementById('lightbox-img');
        if (lightbox && img) {
            img.src = src;
            lightbox.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    closeLightbox() {
        const lightbox = document.getElementById('lightbox');
        if (lightbox) {
            lightbox.classList.remove('active');
            // Restore scroll only if restaurant modal is NOT open
            if (!document.getElementById('restaurant-modal').classList.contains('active')) {
                document.body.style.overflow = '';
            }
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
        // Raccourci clavier CMD/CTRL + K pour focaliser la recherche
        document.addEventListener('keydown', (e) => {
            if (e.key === 'k' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                document.getElementById('global-search')?.focus();
            }
        });
    },

    showHome() { this.toggleView('home-view'); },
    showExplore() { this.toggleView('explore-view'); if (!this.map) setTimeout(() => this.initMap(), 100); else this.map.invalidateSize(); },
    showAdd() { 
        this.toggleView('add-view'); 
        setTimeout(() => this.initAddMap(), 100);
    },
    showAdmin() {
        this.toggleView('admin-view');
        // Hide the main navbar when in admin view
        const navbar = document.querySelector('.navbar');
        if (navbar) navbar.style.display = 'none';
        this.renderAdminDashboard();
    },

    showHome() {
        this.switchView('home-view');
        const navbar = document.querySelector('.navbar');
        if (navbar) navbar.style.display = '';
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
        const allTags = [...new Set(mockRestaurants.flatMap(r => r.tags))].filter(Boolean).sort();
        
        const resetBtn = `<button class="tag-filter-reset" onclick="app.resetTagFilter()"><i class="fa-solid fa-xmark"></i> Tout voir</button>`;
        
        container.innerHTML = resetBtn + allTags.map(tag =>
            `<span class="tag-filter-item ${this.currentTagFilter === tag ? 'active' : ''}" onclick="app.setTagFilter('${tag}')">${tag}</span>`
        ).join('');
    },
    resetTagFilter() {
        this.currentTagFilter = null;
        this.renderTagsFilter();
        this.renderExploreList();
    },
    setTagFilter(tag) {
        // Toggle : cliquer sur le tag actif le désactive
        this.currentTagFilter = (this.currentTagFilter === tag) ? null : tag;
        this.renderTagsFilter();
        this.renderExploreList();
    },
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
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png').addTo(this.addMap);
        
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

    

    login() {
        // Redirection vers la page de login car elle gère déjà tout le flux PHP
        window.location.href = 'login.php';
    },

    renderAdminDashboard() {
        const stats = window.APP_STATS;
        const data = window.APP_ADMIN_DATA;
        if (!stats || !data) {
            console.warn('Admin data not available');
            return;
        }

        // 1. Stats Row — update KPI cards
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.innerText = val; };
        set('pro-stat-restaurants', stats.restaurants || 0);
        set('pro-stat-users', stats.users || 0);
        set('pro-stat-reviews', stats.reviews || 0);
        set('pro-stat-rating', stats.avg_rating || '–');

        // 2. Restaurants Table
        const restTable = document.getElementById('pro-restaurants-table');
        if (restTable && data.restaurants) {
            restTable.innerHTML = data.restaurants.slice(0, 8).map(r => {
                const imgSrc = r.image ? (r.image.startsWith('http') ? r.image : '../' + r.image) : 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=80';
                const statusLabel = r.status === 'active' ? 'Actif' : 'En attente';
                const statusClass = r.status === 'active' ? 'active' : 'pending';
                return `
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="${imgSrc}" class="avatar-pro" onerror="this.src='https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=80'">
                            <span style="font-weight:600;">${r.name}</span>
                        </div>
                    </td>
                    <td style="color:rgba(255,255,255,0.6)">${r.category || '–'}</td>
                    <td style="color:rgba(255,255,255,0.6)">${r.city || '–'}</td>
                    <td><span class="badge-pro ${statusClass}">${statusLabel}</span></td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <button class="action-btn-pro" onclick="app.openModal(${r.id})" title="Voir"><i class="fa-solid fa-eye"></i></button>
                            <button class="action-btn-pro" onclick="app.approveRestaurant(${r.id})" title="Approuver"><i class="fa-solid fa-check"></i></button>
                            <button class="action-btn-pro" style="color:#ff4444" onclick="app.deleteRestaurant(${r.id})" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        // 3. Recent Activity Feed (mini list)
        const activityList = document.getElementById('pro-recent-activity-list');
        if (activityList && data.reviews) {
            activityList.innerHTML = data.reviews.slice(0, 5).map(rev => {
                const initial = (rev.user_name || '?').charAt(0).toUpperCase();
                const colors = ['#ff6b00','#00ccff','#00ff88','#ffb03a','#9b6aff'];
                const color = colors[initial.charCodeAt(0) % colors.length];
                return `
                <div style="display:flex;align-items:center;gap:12px;padding:10px;background:rgba(255,255,255,0.02);border-radius:10px;margin-bottom:8px;">
                    <div style="width:34px;height:34px;background:${color};border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:800;flex-shrink:0;">${initial}</div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${rev.user_name || 'Anonyme'}</div>
                        <div style="font-size:0.75rem;color:rgba(255,255,255,0.4);">${rev.restaurant_name || ''}</div>
                    </div>
                    <div style="font-size:0.85rem;font-weight:700;color:#ffb03a;white-space:nowrap;">${rev.rating}★</div>
                </div>`;
            }).join('');
        }

        // 4. Charts
        if (typeof Chart !== 'undefined') {
            this.renderAdminCharts();
        }
    },

    approveRestaurant(id) {
        if (!confirm('Approuver ce restaurant ?')) return;
        fetch('../controller/approve_restaurant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action: 'approve' })
        }).then(r => r.json()).then(d => {
            if (d.success) { this.showToast('Restaurant approuvé !', 'success'); location.reload(); }
            else this.showToast(d.message || 'Erreur', 'error');
        });
    },

    deleteRestaurant(id) {
        if (!confirm('Supprimer ce restaurant définitivement ?')) return;
        fetch('../controller/approve_restaurant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, action: 'delete' })
        }).then(r => r.json()).then(d => {
            if (d.success) { this.showToast('Restaurant supprimé.', 'success'); location.reload(); }
            else this.showToast(d.message || 'Erreur', 'error');
        });
    },

    renderAdminRestaurants() {
        const container = document.getElementById('admin-restaurants-list');
        const data = window.APP_ADMIN_DATA?.restaurants;
        if (!container || !data) return;

        container.innerHTML = data.map(r => `
            <tr>
                <td>
                    <div class="table-user">
                        <img src="${r.image || 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?q=80&w=200&auto=format&fit=crop'}" class="table-avatar" alt="${r.name}">
                        <div style="display: flex; flex-direction: column;">
                            <strong style="color: #fff;">${r.name}</strong>
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.4);"><i class="fa-solid fa-location-dot"></i> ${r.city}</span>
                        </div>
                    </div>
                </td>
                <td><span style="color: rgba(255,255,255,0.6); font-weight: 600;">${r.category}</span></td>
                <td>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-star" style="color: #ffb03a;"></i>
                        <span style="font-weight: 700;">${r.rating}</span>
                    </div>
                </td>
                <td>
                    ${r.status === 'pending' ? '<span class="status-badge pending">En attente</span>' : 
                      r.status === 'active' ? '<span class="status-badge active"><span class="status-dot"></span> Actif</span>' : 
                      '<span class="status-badge rejected">Refusé</span>'}
                </td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        ${r.status === 'pending' ? `
                            <button onclick="app.approveRestaurant(${r.id})" class="btn-icon approve" title="Approuver"><i class="fa-solid fa-check"></i></button>
                            <button onclick="app.rejectRestaurant(${r.id})" class="btn-icon delete" title="Refuser"><i class="fa-solid fa-xmark"></i></button>
                        ` : ''}
                        <button onclick="app.openModal(${r.id})" class="btn-icon" style="color: #00ccff;" title="Voir"><i class="fa-solid fa-eye"></i></button>
                        <button onclick="app.deleteRestaurant(${r.id})" class="btn-icon delete" title="Supprimer"><i class="fa-solid fa-trash-can"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    renderAdminReviews() {
        const container = document.getElementById('admin-reviews-list');
        const data = window.APP_ADMIN_DATA?.reviews;
        if (!container || !data) return;

        if (data.length === 0) {
            container.innerHTML = '<div style="grid-column: 1/-1; text-align: center; opacity: 0.5; padding: 60px;"><i class="fa-solid fa-ghost" style="font-size: 3rem; margin-bottom: 20px; display: block;"></i> Aucun avis à modérer.</div>';
            return;
        }

        container.innerHTML = data.map(rev => `
            <div class="admin-review-card">
                <div class="review-info-main">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                        <div class="table-avatar" style="width: 35px; height: 35px; background: rgba(255,107,0,0.1); color: var(--accent-primary); display: flex; align-items: center; justify-content: center; font-weight: 800;">${rev.user_name.charAt(0).toUpperCase()}</div>
                        <div>
                            <h4 style="margin: 0; color: #fff;">${rev.user_name}</h4>
                            <span style="font-size: 0.8rem; color: rgba(255,255,255,0.4);">sur <span class="review-target-name">${rev.restaurant_name}</span></span>
                        </div>
                    </div>
                    <div style="color: #ffb03a; font-size: 0.8rem; margin-bottom: 10px;">
                        ${'★'.repeat(rev.rating)}${'☆'.repeat(5-rev.rating)}
                    </div>
                    <p style="margin: 0; color: rgba(255,255,255,0.7); line-height: 1.6; font-style: italic;">"${rev.text}"</p>
                </div>
                <div class="review-actions-btns">
                    <button onclick="app.deleteReview(${rev.id})" class="btn-icon delete" title="Supprimer l'avis"><i class="fa-solid fa-trash-can"></i> Supprimer</button>
                    <button onclick="app.showToast('Avis validé', 'success')" class="btn-icon approve" title="Valider"><i class="fa-solid fa-check"></i> Valider</button>
                </div>
            </div>
        `).join('');
    },

    renderAdminUsers() {
        const container = document.getElementById('admin-users-list');
        const data = window.APP_ADMIN_DATA?.users;
        if (!container || !data) return;

        container.innerHTML = data.map(u => `
            <tr>
                <td>
                    <div class="table-avatar" style="background: rgba(255,255,255,0.05); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800;">
                        ${u.name.charAt(0).toUpperCase()}
                    </div>
                </td>
                <td><strong>${u.name}</strong></td>
                <td style="color: rgba(255,255,255,0.5);">${u.email}</td>
                <td><span class="role-badge ${u.role === 'admin' ? 'role-admin' : 'role-user'}">${u.role === 'admin' ? 'Administrateur' : 'Membre'}</span></td>
                <td>
                    <div class="status-badge ${u.status === 'active' ? 'status-active' : 'status-inactive'}">
                        <span class="status-dot"></span>
                        ${u.status === 'active' ? 'Actif' : 'Bloqué'}
                    </div>
                </td>
                <td>
                    <div style="display: flex; gap: 8px;">
                        <button onclick="app.toggleUserStatus(${u.id})" class="btn-icon" title="${u.status === 'active' ? 'Bloquer' : 'Débloquer'}">
                            <i class="fa-solid ${u.status === 'active' ? 'fa-user-slash' : 'fa-user-check'}"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    },

    async approveRestaurant(id) {
        if (!confirm("Voulez-vous approuver ce restaurant ?")) return;
        try {
            const response = await fetch(`../controller/approve_restaurant.php?id=${id}&ajax=1`);
            const res = await response.json().catch(() => ({ success: true })); // Fallback if old controller doesn't return JSON
            this.showToast("Restaurant approuvé !", "success");
            setTimeout(() => location.reload(), 1000);
        } catch (e) {
            this.showToast("Erreur lors de l'approbation", "error");
        }
    },

    async rejectRestaurant(id) {
        const reason = prompt("Raison du refus :");
        if (reason === null) return;
        try {
            const response = await fetch(`../controller/reject_restaurant.php?id=${id}&reason=${encodeURIComponent(reason)}&ajax=1`);
            this.showToast("Restaurant refusé", "warning");
            setTimeout(() => location.reload(), 1000);
        } catch (e) {
            this.showToast("Erreur lors du refus", "error");
        }
    },

    async deleteRestaurant(id) {
        if (!confirm("Supprimer définitivement ce restaurant ?")) return;
        try {
            await fetch(`../controller/delete_restaurant.php?id=${id}&ajax=1`);
            this.showToast("Restaurant supprimé", "success");
            setTimeout(() => location.reload(), 1000);
        } catch (e) {
            this.showToast("Erreur lors de la suppression", "error");
        }
    },

    async deleteReview(id) {
        if (!confirm("Supprimer cet avis ?")) return;
        try {
            await fetch(`../controller/delete_review.php?id=${id}&ajax=1`);
            this.showToast("Avis supprimé", "success");
            setTimeout(() => location.reload(), 1000);
        } catch (e) {
            this.showToast("Erreur lors de la suppression", "error");
        }
    },

    async toggleUserStatus(id) {
        try {
            await fetch(`../controller/toggle_user.php?id=${id}&ajax=1`);
            this.showToast("Statut utilisateur mis à jour", "success");
            setTimeout(() => location.reload(), 1000);
        } catch (e) {
            this.showToast("Erreur lors de la mise à jour", "error");
        }
    },

    renderAdminCharts() {
        const ctx = document.getElementById('pro-main-chart');
        if (ctx && !this.adminCharts.main) {
            this.adminCharts.main = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Féb', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil'],
                    datasets: [{
                        label: 'Inscriptions',
                        data: [65, 59, 80, 81, 56, 55, 40],
                        borderColor: '#ff6b00',
                        backgroundColor: 'rgba(255, 107, 0, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0
                    }, {
                        label: 'Avis',
                        data: [28, 48, 40, 19, 86, 27, 90],
                        borderColor: '#00ff88',
                        backgroundColor: 'rgba(0, 255, 136, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { display: false },
                        x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.3)' } }
                    }
                }
            });
        }
    },
    switchView(viewId) {
        document.querySelectorAll('.view').forEach(v => v.classList.add('hidden'));
        const target = document.getElementById(viewId);
        if (target) {
            target.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            // Sync navbar active state
            document.querySelectorAll('.nav-links a').forEach(l => l.classList.remove('active'));
            if (viewId === 'admin-view') {
                document.querySelector('.admin-link')?.classList.add('active');
            }
        }
    },
};

document.addEventListener('DOMContentLoaded', () => app.init());
