<?php
/**
 * ============================================================
 *  Moteur MinMax — Recommandation Multicritère Personnalisée
 * ============================================================
 *
 * L'algorithme MinMax est issu de la théorie des jeux (Game Theory).
 * Dans le contexte des recommandations de restaurants, on le modélise
 * comme un jeu à deux joueurs adverses :
 *
 *  ┌─────────────────────────────────────────────────────────┐
 *  │  JOUEUR MAX (Utilisateur) : veut MAXIMISER la qualité   │
 *  │  → Critères MAX : Note globale, Qualité cuisine (TEQ),  │
 *  │                   Ambiance (TEC), Accueil (AT)           │
 *  │                                                         │
 *  │  JOUEUR MIN (Budget)      : veut MINIMISER le coût      │
 *  │  → Critères MIN : Prix moyen (DEF)                      │
 *  └─────────────────────────────────────────────────────────┘
 *
 * L'algorithme explore un ARBRE DE DÉCISION :
 *   - Nœud racine = choix global
 *   - Chaque niveau alterne entre MAX (choisit le meilleur score)
 *     et MIN (choisit le moins cher)
 *   - Les nœuds feuilles sont les restaurants individuels
 *   - Le score MinMax remonte en propagation arrière (backpropagation)
 *
 * Résultat : le restaurant avec le MEILLEUR COMPROMIS qualité/prix
 * selon les PRÉFÉRENCES pondérées de l'utilisateur.
 */
class MinMaxEngine
{
    /** @var array Restaurants chargés depuis la BDD */
    private array $restaurants = [];

    /**
     * Critères avec leur rôle (MAX = on veut maximiser, MIN = on veut minimiser)
     * et leur poids par défaut.
     */
    private const CRITERIA = [
        'rating'       => ['role' => 'MAX', 'weight' => 0.35, 'label' => 'Note globale'],
        'stat_food'    => ['role' => 'MAX', 'weight' => 0.25, 'label' => 'Qualité cuisine (TEQ)'],
        'stat_ambiance'=> ['role' => 'MAX', 'weight' => 0.15, 'label' => 'Ambiance (TEC)'],
        'stat_service' => ['role' => 'MAX', 'weight' => 0.10, 'label' => 'Accueil (AT)'],
        'stat_price'   => ['role' => 'MIN', 'weight' => 0.15, 'label' => 'Rapport qualité/prix (DEF)'],
    ];

    /**
     * Constructeur — charge les restaurants avec leurs statistiques.
     *
     * @param PDO $pdo Connexion à la base de données
     */
    public function __construct(PDO $pdo)
    {
        $this->loadRestaurants($pdo);
    }

    // ——————————————————————————————————————————
    //  1. CHARGEMENT DES DONNÉES
    // ——————————————————————————————————————————

    /**
     * Charge les restaurants actifs avec toutes leurs statistiques.
     */
    private function loadRestaurants(PDO $pdo): void
    {
        $stmt = $pdo->query("
            SELECT id, name, category, rating, image, tags, description,
                   COALESCE(stat_food, 3.0)     AS stat_food,
                   COALESCE(stat_service, 3.0)  AS stat_service,
                   COALESCE(stat_price, 3.0)    AS stat_price,
                   COALESCE(stat_ambiance, 3.0) AS stat_ambiance,
                   COALESCE(stat_location, 3.0) AS stat_location,
                   reviews_count
            FROM restaurants
            WHERE status = 'active'
        ");
        $this->restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ——————————————————————————————————————————
    //  2. NORMALISATION DES SCORES
    // ——————————————————————————————————————————

    /**
     * Normalise une valeur dans [0, 1] par rapport au min/max du groupe.
     * Permet de comparer des critères d'échelles différentes.
     *
     * @param float $value
     * @param float $min
     * @param float $max
     * @return float
     */
    private function normalize(float $value, float $min, float $max): float
    {
        if ($max === $min) return 0.5;
        return ($value - $min) / ($max - $min);
    }

    /**
     * Calcule le min et le max de chaque critère sur l'ensemble des restaurants.
     * Nécessaire pour la normalisation.
     *
     * @return array<string, array{min: float, max: float}>
     */
    private function computeCriteriaBounds(): array
    {
        $bounds = [];
        foreach (array_keys(self::CRITERIA) as $criterion) {
            $values = array_column($this->restaurants, $criterion);
            $values = array_map('floatval', $values);
            $bounds[$criterion] = [
                'min' => min($values),
                'max' => max($values),
            ];
        }
        return $bounds;
    }

    // ——————————————————————————————————————————
    //  3. SCORE D'UN NŒUD FEUILLE (restaurant)
    // ——————————————————————————————————————————

    /**
     * Calcule le score pondéré d'un restaurant selon les critères MinMax.
     *
     * Pour les critères MAX : score normalisé (plus c'est haut, mieux c'est)
     * Pour les critères MIN : score inversé  (1 - score normalisé)
     *   (un prix élevé normalisé = 1.0 devient 0.0 → pénalisé)
     *
     * @param array  $restaurant   Données du restaurant
     * @param array  $bounds       Bornes min/max des critères
     * @param array  $userWeights  Pondérations personnalisées de l'utilisateur
     * @return float
     */
    private function computeLeafScore(
        array $restaurant,
        array $bounds,
        array $userWeights
    ): float {
        $totalScore  = 0.0;
        $totalWeight = 0.0;

        foreach (self::CRITERIA as $criterion => $config) {
            $value      = floatval($restaurant[$criterion] ?? 3.0);
            $normalized = $this->normalize(
                $value,
                $bounds[$criterion]['min'],
                $bounds[$criterion]['max']
            );

            // Inversion pour les critères MIN (ex: prix élevé = mauvais)
            if ($config['role'] === 'MIN') {
                $normalized = 1.0 - $normalized;
            }

            // Poids personnalisé ou poids par défaut
            $weight       = $userWeights[$criterion] ?? $config['weight'];
            $totalScore  += $normalized * $weight;
            $totalWeight += $weight;
        }

        // Normalisation finale pour que le score soit toujours dans [0, 1]
        return $totalWeight > 0 ? $totalScore / $totalWeight : 0.0;
    }

    // ——————————————————————————————————————————
    //  4. ALGORITHME MINMAX (RÉCURSIF)
    // ——————————————————————————————————————————

    /**
     * Implémentation récursive de MinMax sur un sous-ensemble de restaurants.
     *
     * Structure de l'arbre :
     *   Niveau 0 (racine) : MAX — choisit le groupe avec le meilleur max
     *   Niveau 1          : MIN — choisit le groupe avec le meilleur min
     *   Niveau 2 (feuille): Score pondéré du restaurant individuel
     *
     * @param array  $subset        Sous-ensemble de restaurants à évaluer
     * @param int    $depth         Profondeur actuelle dans l'arbre
     * @param bool   $isMaximizing  true = joueur MAX, false = joueur MIN
     * @param array  $bounds        Bornes pour la normalisation
     * @param array  $userWeights   Préférences de l'utilisateur
     * @return float                Meilleur score trouvé pour ce nœud
     */
    private function minmax(
        array $subset,
        int   $depth,
        bool  $isMaximizing,
        array $bounds,
        array $userWeights
    ): float {
        // Cas de base : nœud feuille (1 restaurant) ou profondeur maximale
        if (count($subset) === 1 || $depth === 0) {
            // Score moyen du sous-ensemble
            $scores = array_map(
                fn($r) => $this->computeLeafScore($r, $bounds, $userWeights),
                $subset
            );
            return array_sum($scores) / count($scores);
        }

        // Diviser le sous-ensemble en 2 groupes (par score médian)
        usort($subset, fn($a, $b) =>
            $this->computeLeafScore($a, $bounds, $userWeights) <=>
            $this->computeLeafScore($b, $bounds, $userWeights)
        );
        $mid    = (int) ceil(count($subset) / 2);
        $groupA = array_slice($subset, 0, $mid);       // Moitié "basse"
        $groupB = array_slice($subset, $mid);           // Moitié "haute"

        $scoreA = $this->minmax($groupA, $depth - 1, !$isMaximizing, $bounds, $userWeights);
        $scoreB = $this->minmax($groupB, $depth - 1, !$isMaximizing, $bounds, $userWeights);

        // MAX choisit le groupe avec le score le plus élevé
        // MIN choisit le groupe avec le score le plus bas (le moins cher)
        return $isMaximizing ? max($scoreA, $scoreB) : min($scoreA, $scoreB);
    }

    // ——————————————————————————————————————————
    //  5. RECOMMANDATION FINALE
    // ——————————————————————————————————————————

    /**
     * Point d'entrée principal : recommande les meilleurs restaurants
     * selon l'algorithme MinMax avec préférences personnalisées.
     *
     * @param array $userWeights  Pondérations personnalisées :
     *                            ['rating' => 0.4, 'stat_food' => 0.3, ...]
     *                            Les valeurs manquantes utilisent les défauts.
     * @param int   $topN         Nombre de recommandations à retourner
     * @return array              Restaurants triés par score MinMax décroissant
     */
    public function recommend(array $userWeights = [], int $topN = 5): array
    {
        if (empty($this->restaurants)) return [];

        $bounds = $this->computeCriteriaBounds();
        $scored = [];

        foreach ($this->restaurants as $restaurant) {
            // Score individuel du restaurant (nœud feuille)
            $leafScore = $this->computeLeafScore($restaurant, $bounds, $userWeights);

            // Application de MinMax sur un sous-ensemble centré sur ce restaurant
            // pour simuler la décision de l'adversaire (budget)
            $neighborhood = $this->getNearbyRestaurants($restaurant, 5);
            $minmaxScore  = $this->minmax(
                array_merge([$restaurant], $neighborhood),
                depth: 2,
                isMaximizing: true,
                bounds: $bounds,
                userWeights: $userWeights
            );

            // Score final = combinaison score individuel + validation MinMax
            $finalScore = ($leafScore * 0.7) + ($minmaxScore * 0.3);

            $scored[] = [
                'id'              => (int) $restaurant['id'],
                'name'            => $restaurant['name'],
                'category'        => $restaurant['category'],
                'image'           => $restaurant['image'],
                'rating'          => (float) $restaurant['rating'],
                'minmax_score'    => round($finalScore, 4),
                'leaf_score'      => round($leafScore, 4),
                'score_breakdown' => $this->buildBreakdown($restaurant, $bounds, $userWeights),
                'algorithm'       => 'MinMax',
            ];
        }

        // Trier par score MinMax décroissant
        usort($scored, fn($a, $b) => $b['minmax_score'] <=> $a['minmax_score']);

        return array_slice($scored, 0, $topN);
    }

    /**
     * Retourne des restaurants de catégorie similaire pour constituer
     * l'arbre de décision MinMax.
     *
     * @param array $restaurant Restaurant source
     * @param int   $n          Nombre de voisins
     * @return array
     */
    private function getNearbyRestaurants(array $restaurant, int $n): array
    {
        return array_slice(
            array_filter(
                $this->restaurants,
                fn($r) => $r['id'] !== $restaurant['id'] &&
                          $r['category'] === $restaurant['category']
            ),
            0, $n
        );
    }

    /**
     * Génère le détail du score par critère (pour affichage pédagogique).
     *
     * @param array $restaurant
     * @param array $bounds
     * @param array $userWeights
     * @return array<string, array{label, raw, normalized, role, contribution}>
     */
    private function buildBreakdown(array $restaurant, array $bounds, array $userWeights): array
    {
        $breakdown = [];
        foreach (self::CRITERIA as $criterion => $config) {
            $value      = floatval($restaurant[$criterion] ?? 3.0);
            $normalized = $this->normalize($value, $bounds[$criterion]['min'], $bounds[$criterion]['max']);
            $weight     = $userWeights[$criterion] ?? $config['weight'];
            $contrib    = ($config['role'] === 'MIN' ? 1 - $normalized : $normalized) * $weight;

            $breakdown[$criterion] = [
                'label'        => $config['label'],
                'role'         => $config['role'],
                'raw_value'    => round($value, 2),
                'normalized'   => round($normalized, 3),
                'weight'       => round($weight, 2),
                'contribution' => round($contrib, 4),
            ];
        }
        return $breakdown;
    }

    /**
     * Retourne les pondérations par défaut (utile pour le front-end).
     *
     * @return array
     */
    public function getDefaultWeights(): array
    {
        $out = [];
        foreach (self::CRITERIA as $criterion => $config) {
            $out[$criterion] = [
                'label'  => $config['label'],
                'role'   => $config['role'],
                'weight' => $config['weight'],
            ];
        }
        return $out;
    }
}
