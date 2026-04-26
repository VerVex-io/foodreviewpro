/**
 * ============================================================
 *  Client IA — Interface JavaScript avec les algorithmes PHP
 * ============================================================
 *
 * Ce module fait le pont entre l'interface utilisateur et les
 * moteurs IA côté serveur (TF-IDF, BFS, DFS, MinMax).
 *
 * Tous les appels sont asynchrones (fetch + async/await).
 */

const AIClient = {

    baseUrl: '../controller/ai_engine.php',

    // ——————————————————————————————————————————
    //  Utilitaire : appel AJAX générique
    // ——————————————————————————————————————————

    async call(action, payload = {}) {
        try {
            const response = await fetch(this.baseUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ...payload }),
            });
            return await response.json();
        } catch (error) {
            console.error(`[AIClient] Erreur action "${action}":`, error);
            return { success: false, error: error.message };
        }
    },

    // ——————————————————————————————————————————
    //  1. TF-IDF — Recherche sémantique
    // ——————————————————————————————————————————

    async search(query) {
        return this.call('search', { query });
    },

    // ——————————————————————————————————————————
    //  2. BFS — Restaurants similaires
    // ——————————————————————————————————————————

    async getSimilar(restaurantId) {
        return this.call('similar', { restaurant_id: restaurantId });
    },

    // ——————————————————————————————————————————
    //  3. DFS — Explorer par catégorie
    // ——————————————————————————————————————————

    async exploreCategory(category) {
        return this.call('explore', { category });
    },

    // ——————————————————————————————————————————
    //  4. MinMax — Recommandation personnalisée
    // ——————————————————————————————————————————

    async recommend(weights = {}, topN = 5) {
        return this.call('recommend', { weights, top_n: topN });
    },

    // ——————————————————————————————————————————
    //  5. TF-IDF — Mots-clés d'un restaurant
    // ——————————————————————————————————————————

    async getKeywords(restaurantId) {
        return this.call('keywords', { restaurant_id: restaurantId });
    },
};

// ——————————————————————————————————————————————————————
//  Intégration UI : Fonctions appelées depuis app.js / HTML
// ——————————————————————————————————————————————————————

/**
 * Lance une recherche TF-IDF depuis la barre de recherche et
 * affiche les résultats dans l'onglet Explorer avec scores de pertinence.
 *
 * @param {string} query
 */
async function aiSearch(query) {
    if (!query || query.length < 2) return;

    const badge = document.getElementById('ai-search-badge');
    if (badge) { badge.style.display = 'flex'; badge.textContent = '🔍 Analyse IA...'; }

    const data = await AIClient.search(query);

    if (!data.success || !data.results?.length) {
        if (badge) badge.style.display = 'none';
        return;
    }

    // Créer un index de scores TF-IDF : {restaurant_id -> score}
    const scoreMap = {};
    data.results.forEach(r => { scoreMap[r.id] = r.tfidf_score; });

    // Réordonner mockRestaurants selon les scores TF-IDF
    const rankedIds = data.results.map(r => r.id);
    const ranked = rankedIds
        .map(id => mockRestaurants.find(r => r.id === id))
        .filter(Boolean);

    // Affiche les résultats dans la sidebar explore
    const list = document.getElementById('explore-restaurants');
    if (!list) return;

    list.innerHTML = ranked.map(rest => {
        const score = scoreMap[rest.id] || 0;
        const isFav = app.favorites.includes(rest.id) ? 'active' : '';
        return `
        <div class="list-card ai-result" onclick="app.openModal(${rest.id})">
            <div class="list-card-img-wrapper" style="position: relative;">
                <button class="fav-btn ${isFav}" onclick="app.toggleFavorite(${rest.id}, event)">
                    <i class="fa-${isFav ? 'solid' : 'regular'} fa-heart"></i>
                </button>
                <img src="${rest.image}" alt="${rest.name}">
                <div class="ai-score-badge">
                    <i class="fa-solid fa-brain"></i> ${(score * 100).toFixed(0)}%
                </div>
            </div>
            <div class="list-card-content">
                <h3>${rest.name}</h3>
                <p>${rest.category} • ${rest.tags.join(', ')}</p>
                <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                    <div class="list-card-rating"><i class="fa-solid fa-star"></i> ${rest.rating}</div>
                    <div class="tfidf-relevance-bar" title="Score TF-IDF : ${score}">
                        <div class="tfidf-fill" style="width: ${Math.min(score * 300, 100)}%"></div>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');

    if (badge) {
        badge.textContent = `🧠 TF-IDF — ${data.count} résultat(s)`;
        badge.style.display = 'flex';
    }

    // Naviguer vers l'onglet Explorer si on n'y est pas
    if (document.getElementById('explore-view').classList.contains('hidden')) {
        app.showExplore();
    }
}

/**
 * Charge et affiche les restaurants similaires (BFS) dans la modale.
 *
 * @param {number} restaurantId
 */
async function loadSimilarRestaurants(restaurantId) {
    const container = document.getElementById('similar-restaurants-container');
    if (!container) return;

    container.innerHTML = `
        <div class="ai-loading">
            <i class="fa-solid fa-circle-nodes fa-spin"></i>
            Parcours BFS du graphe de similarité...
        </div>`;

    const data = await AIClient.getSimilar(restaurantId);

    if (!data.success || !data.results?.length) {
        container.innerHTML = '<p style="color: var(--text-secondary); font-size: 0.9rem;">Aucun restaurant similaire trouvé.</p>';
        return;
    }

    container.innerHTML = `
        <div class="ai-algo-badge bfs-badge">
            <i class="fa-solid fa-circle-nodes"></i> BFS · ${data.graph_stats?.nodes || '?'} nœuds · ${data.graph_stats?.edges || '?'} arêtes
        </div>
        <div class="similar-grid">
            ${data.results.map(r => `
                <div class="similar-card" onclick="app.openModal(${r.id})">
                    <img src="${r.image}" alt="${r.name}">
                    <div class="similar-card-info">
                        <strong>${r.name}</strong>
                        <span>${r.shared_tags} tag(s) commun(s) — Niveau BFS ${r.bfs_depth}</span>
                    </div>
                </div>
            `).join('')}
        </div>`;
}

/**
 * Affiche la recommandation MinMax avec un widget interactif.
 *
 * @param {object} weights  Pondérations personnalisées (optionnel)
 */
async function showMinMaxRecommendation(weights = {}) {
    const panel = document.getElementById('ai-recommendation-panel');
    if (!panel) return;

    panel.innerHTML = `
        <div class="ai-loading">
            <i class="fa-solid fa-chess fa-spin"></i>
            Calcul MinMax en cours — évaluation des compromis qualité/prix...
        </div>`;
    panel.classList.remove('hidden');

    const data = await AIClient.recommend(weights, 3);

    if (!data.success || !data.results?.length) {
        panel.innerHTML = '<p>Recommandation indisponible.</p>';
        return;
    }

    const top = data.results[0];
    const others = data.results.slice(1);

    panel.innerHTML = `
        <div class="ai-algo-badge minmax-badge">
            <i class="fa-solid fa-chess"></i> Algorithme Min-Max — Théorie des Jeux
        </div>
        <div class="minmax-result">
            <div class="minmax-winner" onclick="app.openModal(${top.id})">
                <img src="${top.image}" alt="${top.name}">
                <div class="minmax-winner-info">
                    <div class="minmax-crown">👑 Notre recommandation IA</div>
                    <h3>${top.name}</h3>
                    <p>${top.category}</p>
                    <div class="minmax-score-bar">
                        <div class="minmax-fill" style="width: ${Math.round(top.minmax_score * 100)}%"></div>
                        <span>${Math.round(top.minmax_score * 100)}/100</span>
                    </div>
                    <div class="score-breakdown">
                        ${Object.entries(top.score_breakdown || {}).map(([key, s]) => `
                            <div class="breakdown-item">
                                <span class="breakdown-label">${s.label}</span>
                                <span class="breakdown-role ${s.role === 'MAX' ? 'role-max' : 'role-min'}">${s.role}</span>
                                <span class="breakdown-val">${s.raw_value}/5</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
            ${others.length > 0 ? `
            <div class="minmax-runners">
                ${others.map(r => `
                    <div class="runner-card" onclick="app.openModal(${r.id})">
                        <img src="${r.image}" alt="${r.name}">
                        <div>
                            <strong>${r.name}</strong>
                            <span>${Math.round(r.minmax_score * 100)}/100</span>
                        </div>
                    </div>
                `).join('')}
            </div>` : ''}
        </div>`;
}
