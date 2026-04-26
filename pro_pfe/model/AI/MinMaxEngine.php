<?php
/**
 * model/AI/MinMaxEngine.php
 * Algorithme Min-Max (Théorie des Jeux) pour la recommandation multi-critères.
 * 
 * Oppose les préférences de l'utilisateur (Max) aux "faiblesses" potentielles
 * du restaurant (Min) afin de trouver le choix le plus sûr et le plus équilibré.
 */

class MinMaxEngine {
    private PDO $pdo;
    private array $restaurants = [];

    // Les critères évalués par l'IA (normalisés de 1 à 5)
    // Dans la table restaurants, on utilise des colonnes simulées pour les stats :
    // stat_food, stat_service, stat_price, stat_ambiance, stat_location
    private $criteria = [
        'food' => 'stat_food',
        'service' => 'stat_service',
        'price' => 'stat_price',
        'ambiance' => 'stat_ambiance',
        'location' => 'stat_location'
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->loadData();
    }

    private function loadData() {
        // Sélectionne uniquement les restaurants qui ont les champs statistiques remplis
        // Si ces champs n'existent pas ou sont nuls, on les simule pour l'algorithme
        $stmt = $this->pdo->query("SELECT * FROM restaurants WHERE status = 'active'");
        $this->restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Poids par défaut si l'utilisateur n'en spécifie pas.
     */
    public function getDefaultWeights(): array {
        return [
            'food' => 0.4,       // La nourriture est le critère le plus important (40%)
            'service' => 0.2,    // Service (20%)
            'price' => 0.2,      // Prix (20%)
            'ambiance' => 0.1,   // Ambiance (10%)
            'location' => 0.1    // Emplacement (10%)
        ];
    }

    /**
     * Algorithme Min-Max pour la recommandation
     * 
     * @param array $userWeights Les poids donnés par l'utilisateur (ex: ['food'=>0.5, 'price'=>0.3...])
     * @param int $limit Le nombre de recommandations à retourner
     * @return array
     */
    public function recommend(array $userWeights = [], int $limit = 5): array {
        if (empty($userWeights)) {
            $userWeights = $this->getDefaultWeights();
        }

        $scoredRestaurants = [];

        foreach ($this->restaurants as $r) {
            // L'étape "MIN" : l'IA cherche la plus grande "faiblesse" du restaurant 
            // par rapport aux attentes (poids) de l'utilisateur.
            // Formule : Écart = (Note maximale possible 5 - Note réelle) * Poids utilisateur
            
            $maxWeakness = -1; // Plus cet écart est grand, plus la faiblesse est pénalisante
            $totalWeightedScore = 0; // Le score "Max" global

            foreach ($this->criteria as $key => $dbField) {
                // Si la colonne n'existe pas ou est nulle, on simule une note basée sur le rating global
                $score = isset($r[$dbField]) && $r[$dbField] > 0 ? (float)$r[$dbField] : (float)$r['rating'];
                
                $weight = $userWeights[$key] ?? 0.2;
                
                // Calcul de la "Perte" (Loss) pour le MinMax
                $loss = (5.0 - $score) * $weight;
                
                if ($loss > $maxWeakness) {
                    $maxWeakness = $loss;
                }

                $totalWeightedScore += ($score * $weight);
            }

            // Le score final (Max) cherche à minimiser la plus grande faiblesse (Min)
            // On fait donc : Score Global pondéré - Pénalité de la pire faiblesse
            $minMaxScore = $totalWeightedScore - ($maxWeakness * 0.5); // Le facteur 0.5 adoucit la pénalité stricte

            $scoredRestaurants[] = [
                'restaurant' => $r,
                'minmax_score' => round($minMaxScore, 3),
                'worst_weakness_penalty' => round($maxWeakness, 3),
                'weighted_score' => round($totalWeightedScore, 3)
            ];
        }

        // Tri décroissant : On veut les restaurants avec le plus grand MinMax score
        usort($scoredRestaurants, function($a, $b) {
            return $b['minmax_score'] <=> $a['minmax_score'];
        });

        // Extraire juste les données pour le front-end
        $results = [];
        foreach (array_slice($scoredRestaurants, 0, $limit) as $item) {
            $res = $item['restaurant'];
            $res['ai_score'] = $item['minmax_score'];
            $results[] = $res;
        }

        return $results;
    }
}
?>
