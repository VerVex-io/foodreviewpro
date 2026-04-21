<?php
/**
 * ============================================================
 *  Contrôleur AJAX — Point d'entrée des algorithmes IA
 * ============================================================
 *
 * Ce contrôleur est appelé par le front-end JavaScript via fetch().
 * Il route les requêtes vers les bons moteurs IA et retourne du JSON.
 *
 * Endpoints :
 *   POST /controller/ai_engine.php
 *   Paramètre JSON 'action' :
 *     - "search"       → TF-IDF search
 *     - "similar"      → BFS similar restaurants
 *     - "explore"      → DFS category exploration
 *     - "recommend"    → MinMax recommendation
 *     - "graph_stats"  → Statistiques du graphe (pour le rapport)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/AI/TFIDFEngine.php';
require_once __DIR__ . '/../model/AI/GraphEngine.php';
require_once __DIR__ . '/../model/AI/MinMaxEngine.php';

// — Entêtes CORS + JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// — Lecture de la requête JSON
$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? '');

// ——————————————————————————————————————————
//  Routeur
// ——————————————————————————————————————————
try {
    switch ($action) {

        // —— TF-IDF : Recherche sémantique ——————————————————————
        case 'search':
            $query = trim($input['query'] ?? '');
            if (empty($query)) {
                echo json_encode(['success' => false, 'error' => 'Requête vide']);
                exit;
            }

            $engine  = new TFIDFEngine($pdo);
            $results = $engine->search($query, limit: 10);

            echo json_encode([
                'success'   => true,
                'action'    => 'search',
                'algorithm' => 'TF-IDF',
                'query'     => $query,
                'count'     => count($results),
                'results'   => $results,
            ]);
            break;

        // —— BFS : Restaurants similaires ——————————————————————
        case 'similar':
            $restaurantId = (int) ($input['restaurant_id'] ?? 0);
            if ($restaurantId <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID restaurant invalide']);
                exit;
            }

            $engine  = new GraphEngine($pdo);
            $results = $engine->bfs($restaurantId, maxResults: 4, maxDepth: 3);

            echo json_encode([
                'success'      => true,
                'action'       => 'similar',
                'algorithm'    => 'BFS (Breadth-First Search)',
                'source_id'    => $restaurantId,
                'count'        => count($results),
                'results'      => $results,
                'graph_stats'  => $engine->getGraphStats(),
            ]);
            break;

        // —— DFS : Explorer par catégorie ——————————————————————
        case 'explore':
            $category = trim($input['category'] ?? '');
            if (empty($category)) {
                echo json_encode(['success' => false, 'error' => 'Catégorie manquante']);
                exit;
            }

            $engine  = new GraphEngine($pdo);
            $results = $engine->getRestaurantsByCategory($category, limit: 10);

            echo json_encode([
                'success'   => true,
                'action'    => 'explore',
                'algorithm' => 'DFS (Depth-First Search)',
                'category'  => $category,
                'count'     => count($results),
                'results'   => $results,
            ]);
            break;

        // —— MinMax : Recommandation personnalisée ——————————————
        case 'recommend':
            // Pondérations personnalisées optionnelles envoyées par l'utilisateur
            $userWeights = $input['weights'] ?? [];
            $topN        = (int) ($input['top_n'] ?? 5);

            $engine  = new MinMaxEngine($pdo);
            $results = $engine->recommend($userWeights, $topN);

            echo json_encode([
                'success'         => true,
                'action'          => 'recommend',
                'algorithm'       => 'Min-Max (Théorie des Jeux)',
                'user_weights'    => $userWeights ?: $engine->getDefaultWeights(),
                'count'           => count($results),
                'results'         => $results,
                'top_pick'        => $results[0] ?? null,
            ]);
            break;

        // —— Statistiques du graphe (pour rapport PFA) ——————————
        case 'graph_stats':
            $engine = new GraphEngine($pdo);
            echo json_encode([
                'success' => true,
                'action'  => 'graph_stats',
                'stats'   => $engine->getGraphStats(),
                'graph'   => $engine->getGraph(),
            ]);
            break;

        // —— Mots-clés TF-IDF d'un restaurant ————————————————
        case 'keywords':
            $restaurantId = (int) ($input['restaurant_id'] ?? 0);
            if ($restaurantId <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID invalide']);
                exit;
            }
            $engine   = new TFIDFEngine($pdo);
            $keywords = $engine->getTopKeywords($restaurantId, n: 8);

            echo json_encode([
                'success'       => true,
                'action'        => 'keywords',
                'algorithm'     => 'TF-IDF Keywords',
                'restaurant_id' => $restaurantId,
                'keywords'      => $keywords,
            ]);
            break;

        default:
            echo json_encode([
                'success'    => false,
                'error'      => 'Action inconnue : ' . htmlspecialchars($action),
                'available'  => ['search', 'similar', 'explore', 'recommend', 'graph_stats', 'keywords'],
            ]);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur serveur : ' . $e->getMessage(),
        'file'    => basename($e->getFile()),
        'line'    => $e->getLine(),
    ]);
}
