<?php
/**
 * controller/ai_engine.php
 * Contrôleur AJAX — Point d'entrée des algorithmes IA
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/AI/TFIDFEngine.php';
require_once __DIR__ . '/../model/AI/GraphEngine.php';
require_once __DIR__ . '/../model/AI/MinMaxEngine.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$input  = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_GET['action'] ?? '');

try {
    switch ($action) {
        case 'search':
            $query  = $input['query'] ?? '';
            $limit  = (int) ($input['limit'] ?? 10);
            
            if (empty($query)) {
                echo json_encode(['success' => false, 'message' => 'Requête vide.']);
                exit;
            }

            $engine = new TFIDFEngine($pdo);
            $results = $engine->search($query, $limit);

            echo json_encode([
                'success' => true,
                'action'  => 'search',
                'query'   => $query,
                'count'   => count($results),
                'results' => $results,
            ]);
            break;

        case 'similar':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID manquant.']);
                exit;
            }

            $engine = new GraphEngine($pdo);
            $results = $engine->getSimilarBFS($id, 2, 5); // Profondeur 2, max 5 résultats

            echo json_encode([
                'success' => true,
                'action'  => 'similar',
                'target_id' => $id,
                'count'   => count($results),
                'results' => $results,
            ]);
            break;

        case 'explore':
            $id = (int) ($input['id'] ?? 0);
            if ($id <= 0) {
                // Si pas d'ID, on prend un restaurant au hasard pour lancer le parcours
                $stmt = $pdo->query("SELECT id FROM restaurants WHERE status='active' ORDER BY RAND() LIMIT 1");
                $id = $stmt->fetchColumn();
            }

            $engine = new GraphEngine($pdo);
            $journey = $engine->getJourneyDFS($id, 4); // Parcours de 4 étapes

            echo json_encode([
                'success' => true,
                'action'  => 'explore',
                'start_id'=> $id,
                'count'   => count($journey),
                'results' => $journey,
            ]);
            break;

        case 'recommend':
            $userWeights = $input['weights'] ?? [];
            $topN        = (int) ($input['top_n'] ?? 5);

            $engine  = new MinMaxEngine($pdo);
            $results = $engine->recommend($userWeights, $topN);

            echo json_encode([
                'success'      => true,
                'action'       => 'recommend',
                'user_weights' => $userWeights ?: $engine->getDefaultWeights(),
                'count'        => count($results),
                'results'      => $results,
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
