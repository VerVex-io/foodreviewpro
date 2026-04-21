<?php
/**
 * ============================================================
 *  Moteur de Graphe — BFS & DFS pour la Similarité
 * ============================================================
 *
 * Les restaurants sont modélisés comme des NŒUDS d'un graphe non orienté.
 * Deux restaurants sont reliés par une ARÊTE s'ils partagent au moins
 * un tag commun. Le poids de l'arête = nombre de tags partagés.
 *
 * ALGORITHMES IMPLÉMENTÉS :
 *
 *  ① BFS (Breadth-First Search / Parcours en Largeur) :
 *     → Part d'un restaurant source et explore les similaires
 *       couche par couche (même niveau de similarité d'abord).
 *     → Garantit que les résultats les plus proches (plus de tags
 *       communs) arrivent en premier.
 *     → Utilisé pour : "Vous aimerez aussi..."
 *
 *  ② DFS (Depth-First Search / Parcours en Profondeur) :
 *     → Explore une branche de catégorie jusqu'au bout avant
 *       de revenir (backtrack).
 *     → Utilisé pour : construire l'arbre de navigation des
 *       catégories et trouver tous les restaurants d'une catégorie,
 *       même indirectement liés.
 */
class GraphEngine
{
    /**
     * Graphe d'adjacence : [restaurant_id => [voisin_id => poids]]
     * Le poids = nombre de tags partagés entre les deux restaurants.
     *
     * @var array<int, array<int, int>>
     */
    private array $adjacencyList = [];

    /** @var array Données complètes des restaurants */
    private array $restaurants = [];

    /** @var array<int, string[]> Tags par restaurant */
    private array $tagMap = [];

    /**
     * Constructeur — charge les restaurants et construit le graphe.
     *
     * @param PDO $pdo Connexion à la base de données
     */
    public function __construct(PDO $pdo)
    {
        $this->loadRestaurants($pdo);
        $this->buildGraph();
    }

    // ——————————————————————————————————————————
    //  1. CHARGEMENT ET CONSTRUCTION DU GRAPHE
    // ——————————————————————————————————————————

    /**
     * Charge les restaurants actifs depuis la BDD.
     */
    private function loadRestaurants(PDO $pdo): void
    {
        $stmt = $pdo->query("
            SELECT id, name, category, tags, rating, image, description
            FROM restaurants
            WHERE status = 'active'
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $this->restaurants[$row['id']] = $row;
            $rawTags = strtolower($row['tags'] ?? '') . ',' . strtolower($row['category']);
            $this->tagMap[$row['id']] = array_filter(
                array_map('trim', explode(',', $rawTags))
            );
            $this->adjacencyList[$row['id']] = [];
        }
    }

    /**
     * Construit le graphe de similarité entre tous les restaurants.
     *
     * Complexité : O(n²) — acceptable car le nombre de restaurants
     * est borné (quelques centaines max).
     *
     * Critère de connexion : ≥ 1 tag en commun (catégorie incluse).
     * Poids de l'arête     : nombre de tags partagés.
     */
    private function buildGraph(): void
    {
        $ids = array_keys($this->restaurants);
        $n   = count($ids);

        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $idA = $ids[$i];
                $idB = $ids[$j];

                // Intersection des tags : tags communs
                $sharedTags = array_intersect($this->tagMap[$idA], $this->tagMap[$idB]);
                $weight = count($sharedTags);

                if ($weight > 0) {
                    $this->adjacencyList[$idA][$idB] = $weight;
                    $this->adjacencyList[$idB][$idA] = $weight; // non orienté
                }
            }
        }
    }

    // ——————————————————————————————————————————
    //  2. BFS — PARCOURS EN LARGEUR
    //     "Restaurants Similaires (Vous aimerez aussi)"
    // ——————————————————————————————————————————

    /**
     * BFS depuis un restaurant source.
     *
     * Principe :
     *  - File d'attente FIFO initialisée avec le nœud source.
     *  - À chaque étape, on visite tous les voisins non encore visités
     *    et on les ajoute à la file.
     *  - On s'arrête quand la file est vide ou qu'on a assez de résultats.
     *
     * Les voisins sont triés par poids décroissant à chaque niveau
     * → les plus similaires apparaissent en premier.
     *
     * @param int $sourceId    Identifiant du restaurant de départ
     * @param int $maxResults  Nombre de suggestions retournées
     * @param int $maxDepth    Profondeur maximale du parcours
     * @return array           Liste de restaurants suggérés avec leur niveau et poids
     */
    public function bfs(int $sourceId, int $maxResults = 4, int $maxDepth = 3): array
    {
        if (!isset($this->adjacencyList[$sourceId])) return [];

        $visited   = [$sourceId => true]; // Noeuds déjà visités
        $queue     = [[$sourceId, 0]];    // File FIFO : [id, profondeur]
        $results   = [];

        while (!empty($queue) && count($results) < $maxResults) {
            [$currentId, $depth] = array_shift($queue); // Défile le premier élément

            if ($depth >= $maxDepth) continue;

            // Trier les voisins par poids décroissant (plus similaires d'abord)
            $neighbors = $this->adjacencyList[$currentId] ?? [];
            arsort($neighbors);

            foreach ($neighbors as $neighborId => $weight) {
                if (!isset($visited[$neighborId])) {
                    $visited[$neighborId] = true;
                    $queue[] = [$neighborId, $depth + 1]; // Enfile le voisin

                    if ($neighborId !== $sourceId) {
                        $results[] = [
                            ...$this->restaurants[$neighborId],
                            'bfs_depth'    => $depth + 1,
                            'shared_tags'  => $weight,
                            'algorithm'    => 'BFS',
                        ];
                    }

                    if (count($results) >= $maxResults) break;
                }
            }
        }

        return $results;
    }

    // ——————————————————————————————————————————
    //  3. DFS — PARCOURS EN PROFONDEUR
    //     "Explorer une catégorie en profondeur"
    // ——————————————————————————————————————————

    /**
     * DFS depuis un restaurant source ou depuis une catégorie.
     *
     * Principe :
     *  - Pile LIFO initialisée avec le nœud source.
     *  - On visite toujours le voisin le plus profond en premier
     *    (exploration exhaustive d'une branche avant backtrack).
     *
     * Utilisé pour explorer tous les restaurants d'une même "famille"
     * de manière exhaustive (ex: toutes les variantes d'un style culinaire).
     *
     * @param int    $sourceId    Restaurant de départ (ou premier de la catégorie)
     * @param int    $maxResults  Nombre max de résultats
     * @return array              Restaurants trouvés dans l'ordre DFS
     */
    public function dfs(int $sourceId, int $maxResults = 10): array
    {
        if (!isset($this->adjacencyList[$sourceId])) return [];

        $visited = [];
        $stack   = [$sourceId]; // Pile LIFO
        $results = [];

        while (!empty($stack) && count($results) < $maxResults) {
            $currentId = array_pop($stack); // Dépile le sommet

            if (isset($visited[$currentId])) continue;
            $visited[$currentId] = true;

            if ($currentId !== $sourceId) {
                $results[] = [
                    ...$this->restaurants[$currentId],
                    'algorithm' => 'DFS',
                ];
            }

            // Empiler les voisins non visités (triés par poids pour guide le DFS)
            $neighbors = $this->adjacencyList[$currentId] ?? [];
            arsort($neighbors);
            foreach (array_keys($neighbors) as $neighborId) {
                if (!isset($visited[$neighborId])) {
                    $stack[] = $neighborId;
                }
            }
        }

        return $results;
    }

    /**
     * Trouve tous les restaurants d'une catégorie donnée en utilisant DFS.
     * Le point d'entrée est le premier restaurant de cette catégorie.
     *
     * @param string $category  Nom de la catégorie
     * @param int    $limit
     * @return array
     */
    public function getRestaurantsByCategory(string $category, int $limit = 10): array
    {
        // Trouver le nœud source = premier restaurant de cette catégorie
        $sourceId = null;
        foreach ($this->restaurants as $id => $restaurant) {
            if (stripos($restaurant['category'], $category) !== false) {
                $sourceId = $id;
                break;
            }
        }

        if ($sourceId === null) return [];

        // Lancer le DFS depuis ce nœud
        $dfsResults = $this->dfs($sourceId, $limit * 2);

        // Filtrer : garder seulement la catégorie demandée
        return array_values(array_filter(
            $dfsResults,
            fn($r) => stripos($r['category'], $category) !== false
        ));
    }

    /**
     * Retourne le graphe complet (pour visualisation ou débogage).
     *
     * @return array
     */
    public function getGraph(): array
    {
        return $this->adjacencyList;
    }

    /**
     * Retourne les statistiques du graphe (pour le rapport de PFA).
     *
     * @return array ['nodes', 'edges', 'density', 'avg_degree']
     */
    public function getGraphStats(): array
    {
        $nodes     = count($this->adjacencyList);
        $edges     = array_sum(array_map('count', $this->adjacencyList)) / 2;
        $maxEdges  = $nodes * ($nodes - 1) / 2;
        $density   = $maxEdges > 0 ? round($edges / $maxEdges, 4) : 0;
        $avgDegree = $nodes > 0 ? round(($edges * 2) / $nodes, 2) : 0;

        return [
            'nodes'      => $nodes,
            'edges'      => (int) $edges,
            'density'    => $density,
            'avg_degree' => $avgDegree,
        ];
    }
}
