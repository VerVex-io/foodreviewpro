<?php
/**
 * model/AI/TFIDFEngine.php
 * Moteur de recherche sémantique basé sur TF-IDF (Term Frequency - Inverse Document Frequency)
 * 
 * Permet de trouver les restaurants les plus pertinents en fonction d'une requête textuelle.
 */

class TFIDFEngine {
    private PDO $pdo;
    private array $documents = [];
    private array $corpusTerms = [];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->loadCorpus();
    }

    /**
     * Charge tous les restaurants actifs pour construire le corpus.
     * Le "document" de chaque restaurant est constitué de son nom, sa catégorie, ses tags et sa description.
     */
    private function loadCorpus() {
        $stmt = $this->pdo->query("SELECT id, name, category, description, tags, rating, image FROM restaurants WHERE status = 'active'");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $text = strtolower($row['name'] . ' ' . $row['category'] . ' ' . $row['tags'] . ' ' . $row['description']);
            // Nettoyage basique (ponctuation)
            $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
            $words = array_filter(explode(' ', $text), function($w) {
                return strlen(trim($w)) > 2; // Ignorer les mots très courts
            });
            
            $this->documents[$row['id']] = [
                'id' => $row['id'],
                'data' => $row,
                'words' => array_values($words),
                'word_count' => array_count_values($words),
                'total_words' => count($words)
            ];

            foreach (array_unique($words) as $word) {
                if (!isset($this->corpusTerms[$word])) {
                    $this->corpusTerms[$word] = 0;
                }
                $this->corpusTerms[$word]++;
            }
        }
    }

    /**
     * Recherche sémantique
     *
     * @param string $query La requête de l'utilisateur
     * @param int $limit Le nombre de résultats à retourner
     * @return array
     */
    public function search(string $query, int $limit = 5): array {
        $query = strtolower($query);
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        $queryWords = array_filter(explode(' ', $query), function($w) {
            return strlen(trim($w)) > 2;
        });

        if (empty($queryWords)) {
            return [];
        }

        $scores = [];
        $totalDocs = count($this->documents);

        foreach ($this->documents as $docId => $doc) {
            $score = 0;
            foreach ($queryWords as $qWord) {
                // Term Frequency (TF)
                $tf = ($doc['word_count'][$qWord] ?? 0) / max(1, $doc['total_words']);
                
                // Inverse Document Frequency (IDF)
                $docsWithWord = $this->corpusTerms[$qWord] ?? 0;
                $idf = $docsWithWord > 0 ? log($totalDocs / $docsWithWord) : 0;
                
                $score += $tf * $idf;
            }

            if ($score > 0) {
                // Booster légèrement avec le rating réel pour départager les ex aequo
                $finalScore = $score + ($doc['data']['rating'] * 0.001);
                $scores[$docId] = $finalScore;
            }
        }

        arsort($scores);
        $results = [];
        $count = 0;

        foreach ($scores as $docId => $score) {
            if ($count >= $limit) break;
            $res = $this->documents[$docId]['data'];
            $res['tfidf_score'] = round($score, 4);
            $results[] = $res;
            $count++;
        }

        return $results;
    }
}
?>
