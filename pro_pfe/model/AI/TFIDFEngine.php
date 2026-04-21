<?php
/**
 * ============================================================
 *  Moteur TF-IDF — Analyse Sémantique des Avis
 * ============================================================
 *
 * TF-IDF (Term Frequency - Inverse Document Frequency) est une
 * mesure statistique qui évalue la pertinence d'un mot pour un
 * document (ici un restaurant) dans un corpus (tous les restaurants).
 *
 * - TF  = nb occurrences du terme dans le doc / nb total de mots du doc
 * - IDF = log(nb total docs / nb docs contenant le terme)
 * - Score final = TF × IDF
 *
 * Usage dans ce projet :
 *   - Indexer les avis de chaque restaurant
 *   - Calculer la pertinence d'une requête de recherche
 *   - Retourner des restaurants triés par score sémantique
 */
class TFIDFEngine
{
    /** @var array Corpus indexé : [restaurant_id => ['term' => tf_idf_score]] */
    private array $index = [];

    /** @var array Tous les restaurants en mémoire */
    private array $restaurants = [];

    /** @var array Mots non significatifs à ignorer (Stop Words) */
    private array $stopWords = [
        'le','la','les','un','une','des','de','du','et','en','au','aux',
        'que','qui','ne','se','ce','je','tu','il','elle','nous','vous','ils',
        'est','sont','être','avoir','à','par','pour','sur','dans','avec',
        'mais','ou','donc','or','ni','car','très','bien','plus','aussi',
        'the','a','an','is','in','of','to','and','for','this','that'
    ];

    /**
     * Constructeur — charge tous les restaurants et leurs avis,
     * puis construit l'index TF-IDF complet.
     *
     * @param PDO $pdo Connexion à la base de données
     */
    public function __construct(PDO $pdo)
    {
        $this->loadRestaurants($pdo);
        $this->buildIndex();
    }

    // ——————————————————————————————————————————
    //  1. CHARGEMENT DES DONNÉES
    // ——————————————————————————————————————————

    /**
     * Charge les restaurants actifs avec leurs avis depuis la BDD.
     */
    private function loadRestaurants(PDO $pdo): void
    {
        $stmt = $pdo->query("
            SELECT r.id, r.name, r.category, r.description, r.tags,
                   GROUP_CONCAT(rv.text ORDER BY rv.id SEPARATOR ' ') AS all_reviews
            FROM restaurants r
            LEFT JOIN reviews rv ON rv.restaurant_id = r.id AND rv.status = 'approved'
            WHERE r.status = 'active'
            GROUP BY r.id
        ");
        $this->restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Construit le "document" textuel complet d'un restaurant.
     * = nom + catégorie + description + tags + tous les avis.
     */
    private function buildDocument(array $restaurant): string
    {
        return implode(' ', [
            str_repeat($restaurant['name'], 3),        // Le nom compte triple
            str_repeat($restaurant['category'], 2),    // La catégorie compte double
            $restaurant['description'] ?? '',
            $restaurant['tags'] ?? '',
            $restaurant['all_reviews'] ?? ''
        ]);
    }

    // ——————————————————————————————————————————
    //  2. TOKENISATION
    // ——————————————————————————————————————————

    /**
     * Transforme un texte brut en tableau de tokens nettoyés.
     * - Minuscules
     * - Suppression de la ponctuation
     * - Élimination des stop words
     * - Suppression des tokens trop courts (< 3 chars)
     *
     * @param string $text
     * @return array<string>
     */
    public function tokenize(string $text): array
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text); // garde lettres, chiffres
        $tokens = preg_split('/\s+/', trim($text));

        return array_values(array_filter($tokens, function (string $token): bool {
            return strlen($token) >= 3 && !in_array($token, $this->stopWords, true);
        }));
    }

    // ——————————————————————————————————————————
    //  3. CALCUL TF, IDF, TF-IDF
    // ——————————————————————————————————————————

    /**
     * Calcule la fréquence normalisée d'un terme dans un document.
     * TF(t, d) = count(t in d) / count(all terms in d)
     *
     * @param string $term
     * @param array  $tokens  Liste des tokens du document
     * @return float
     */
    private function termFrequency(string $term, array $tokens): float
    {
        if (empty($tokens)) return 0.0;
        $count = count(array_filter($tokens, fn($t) => $t === $term));
        return $count / count($tokens);
    }

    /**
     * Calcule l'Inverse Document Frequency d'un terme.
     * IDF(t, D) = log(N / df(t) + 1)  [+1 pour éviter division par zéro]
     *
     * @param string $term
     * @param array  $allTokenSets  Tableau des tokens de chaque document
     * @return float
     */
    private function inverseDocFrequency(string $term, array $allTokenSets): float
    {
        $totalDocs = count($allTokenSets);
        $docsWithTerm = array_sum(array_map(
            fn($tokens) => in_array($term, $tokens, true) ? 1 : 0,
            $allTokenSets
        ));
        return log(($totalDocs + 1) / ($docsWithTerm + 1)) + 1;
    }

    // ——————————————————————————————————————————
    //  4. CONSTRUCTION DE L'INDEX
    // ——————————————————————————————————————————

    /**
     * Construit l'index TF-IDF complet pour tous les restaurants.
     * L'index est stocké en mémoire : [id => ['term' => score]].
     */
    private function buildIndex(): void
    {
        $allTokenSets = [];

        // Étape 1 : Tokeniser chaque document
        foreach ($this->restaurants as $restaurant) {
            $doc = $this->buildDocument($restaurant);
            $allTokenSets[$restaurant['id']] = $this->tokenize($doc);
        }

        // Étape 2 : Calculer TF-IDF pour chaque (restaurant, terme)
        foreach ($this->restaurants as $restaurant) {
            $id = $restaurant['id'];
            $tokens = $allTokenSets[$id];
            $uniqueTerms = array_unique($tokens);
            $this->index[$id] = [];

            foreach ($uniqueTerms as $term) {
                $tf  = $this->termFrequency($term, $tokens);
                $idf = $this->inverseDocFrequency($term, $allTokenSets);
                $this->index[$id][$term] = $tf * $idf;
            }
        }
    }

    // ——————————————————————————————————————————
    //  5. RECHERCHE PAR REQUÊTE
    // ——————————————————————————————————————————

    /**
     * Recherche les restaurants les plus pertinents pour une requête donnée.
     * Utilise la similarité cosinus entre le vecteur TF-IDF de la requête
     * et ceux des restaurants.
     *
     * @param string $query   Texte saisi par l'utilisateur
     * @param int    $limit   Nombre max de résultats
     * @return array          Restaurants triés par score décroissant, avec leur score
     */
    public function search(string $query, int $limit = 10): array
    {
        $queryTokens = $this->tokenize($query);
        if (empty($queryTokens)) return [];

        $scores = [];

        foreach ($this->restaurants as $restaurant) {
            $id = $restaurant['id'];
            $score = 0.0;

            // Score = somme des TF-IDF de chaque terme de la requête dans ce restaurant
            foreach ($queryTokens as $term) {
                $score += $this->index[$id][$term] ?? 0.0;
            }

            if ($score > 0) {
                $scores[] = [
                    'id'          => $id,
                    'name'        => $restaurant['name'],
                    'tfidf_score' => round($score, 4),
                ];
            }
        }

        // Trier par score décroissant
        usort($scores, fn($a, $b) => $b['tfidf_score'] <=> $a['tfidf_score']);

        return array_slice($scores, 0, $limit);
    }

    /**
     * Retourne l'index complet (utile pour le débogage ou les logs).
     *
     * @return array
     */
    public function getIndex(): array
    {
        return $this->index;
    }

    /**
     * Retourne les top N mots-clés d'un restaurant (ses "thèmes dominants").
     *
     * @param int $restaurantId
     * @param int $n
     * @return array<string, float>
     */
    public function getTopKeywords(int $restaurantId, int $n = 5): array
    {
        if (!isset($this->index[$restaurantId])) return [];
        $terms = $this->index[$restaurantId];
        arsort($terms);
        return array_slice($terms, 0, $n, true);
    }
}
