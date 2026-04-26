<?php
/**
 * model/AI/GraphEngine.php
 * Algorithmes de graphes (BFS et DFS) pour la recommandation et l'exploration.
 * 
 * Les nœuds sont des restaurants. Les arêtes relient des restaurants
 * qui partagent la même catégorie OU la même ville (tags).
 */

class GraphEngine {
    private PDO $pdo;
    private array $nodes = [];
    private array $adjList = [];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->buildGraph();
    }

    /**
     * Construit le graphe à partir de la base de données
     */
    private function buildGraph() {
        $stmt = $this->pdo->query("SELECT id, name, category, tags, rating, image FROM restaurants WHERE status = 'active'");
        $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($restaurants as $r) {
            $this->nodes[$r['id']] = $r;
            $this->adjList[$r['id']] = [];
        }

        // Création des arêtes (edges)
        // Deux restaurants sont connectés s'ils partagent la même catégorie ou s'ils sont dans la même ville (tag)
        $nodeIds = array_keys($this->nodes);
        for ($i = 0; $i < count($nodeIds); $i++) {
            for ($j = $i + 1; $j < count($nodeIds); $j++) {
                $id1 = $nodeIds[$i];
                $id2 = $nodeIds[$j];
                $r1 = $this->nodes[$id1];
                $r2 = $this->nodes[$id2];

                $weight = 0;
                // Si même catégorie, forte connexion
                if ($r1['category'] === $r2['category']) {
                    $weight += 2;
                }
                
                // Si même tag/ville, connexion moyenne
                $tags1 = array_map('trim', explode(',', strtolower($r1['tags'])));
                $tags2 = array_map('trim', explode(',', strtolower($r2['tags'])));
                if (!empty(array_intersect($tags1, $tags2))) {
                    $weight += 1;
                }

                if ($weight > 0) {
                    $this->adjList[$id1][$id2] = $weight;
                    $this->adjList[$id2][$id1] = $weight; // Graphe non orienté
                }
            }
        }
    }

    /**
     * Breadth-First Search (BFS)
     * Trouve les restaurants similaires en explorant les voisins de proche en proche.
     * Utile pour la fonctionnalité "Vous aimerez aussi..."
     *
     * @param int $startNodeId ID du restaurant de départ
     * @param int $maxDepth Profondeur maximale de recherche (ex: 2 degrés de séparation)
     * @param int $limit Nombre max de résultats
     * @return array
     */
    public function getSimilarBFS(int $startNodeId, int $maxDepth = 2, int $limit = 5): array {
        if (!isset($this->adjList[$startNodeId])) {
            return [];
        }

        $visited = [$startNodeId => true];
        $queue = new SplQueue();
        // Format dans la file : [nodeId, depth]
        $queue->enqueue([$startNodeId, 0]);

        $results = [];

        while (!$queue->isEmpty() && count($results) < $limit) {
            [$currentNodeId, $depth] = $queue->dequeue();

            // Si on dépasse la profondeur voulue, on arrête d'explorer cette branche
            if ($depth >= $maxDepth) {
                continue;
            }

            // Trier les voisins par poids de connexion (le plus fort d'abord)
            $neighbors = $this->adjList[$currentNodeId] ?? [];
            arsort($neighbors);

            foreach ($neighbors as $neighborId => $weight) {
                if (!isset($visited[$neighborId])) {
                    $visited[$neighborId] = true;
                    $queue->enqueue([$neighborId, $depth + 1]);
                    
                    // Ajouter au résultat final
                    $res = $this->nodes[$neighborId];
                    $res['bfs_depth'] = $depth + 1;
                    $res['bfs_connection_strength'] = $weight;
                    $results[] = $res;
                    
                    if (count($results) >= $limit) break;
                }
            }
        }

        return $results;
    }

    /**
     * Depth-First Search (DFS)
     * Explore une chaîne de restaurants partageant une thématique commune.
     * Utile pour générer un "Parcours" (ex: un parcours Tunisien : Dîner -> Café -> Désert)
     *
     * @param int $startNodeId ID de départ
     * @param int $limit Longueur du parcours
     * @return array
     */
    public function getJourneyDFS(int $startNodeId, int $limit = 4): array {
        if (!isset($this->adjList[$startNodeId])) {
            return [];
        }

        $visited = [];
        $journey = [];
        
        $this->dfsRecursive($startNodeId, $visited, $journey, $limit);

        return $journey;
    }

    private function dfsRecursive(int $nodeId, array &$visited, array &$journey, int $limit) {
        if (count($journey) >= $limit) return;
        
        $visited[$nodeId] = true;
        // On n'ajoute pas le point de départ dans le parcours, ou si on le veut, on l'ajoute :
        if (count($journey) < $limit) {
            $journey[] = $this->nodes[$nodeId];
        }

        $neighbors = $this->adjList[$nodeId] ?? [];
        // Pour un parcours varié, on peut mélanger les voisins
        $keys = array_keys($neighbors);
        shuffle($keys);

        foreach ($keys as $neighborId) {
            if (!isset($visited[$neighborId])) {
                $this->dfsRecursive($neighborId, $visited, $journey, $limit);
                if (count($journey) >= $limit) return; // Stop si on a atteint la limite
            }
        }
    }

    /**
     * Retourne des statistiques sur le graphe (pour le rapport)
     */
    public function getGraphStats(): array {
        $edgesCount = 0;
        foreach ($this->adjList as $neighbors) {
            $edgesCount += count($neighbors);
        }
        return [
            'nodes' => count($this->nodes),
            'edges' => $edgesCount / 2 // car graphe non orienté
        ];
    }
}
?>
