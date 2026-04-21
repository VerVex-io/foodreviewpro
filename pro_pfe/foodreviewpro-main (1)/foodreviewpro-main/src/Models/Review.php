<?php
/**
 * src/Models/Review.php
 */
class Review
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT rv.*, u.user_name, r.name AS restaurant_name
             FROM reviews rv
             JOIN users u       ON u.id = rv.user_id
             JOIN restaurants r ON r.id = rv.restaurant_id
             WHERE rv.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reviews (restaurant_id, user_id, rating, comment, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['restaurant_id'],
            $data['user_id'],
            $data['rating'],
            $data['comment'] ?? '',
            $data['status']  ?? 'pending',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateStatus(int $id, string $status, string $flagReason = ''): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE reviews SET status = ?, flag_reason = ? WHERE id = ?'
        );
        return $stmt->execute([$status, $flagReason, $id]);
    }

    public function recentActivity(int $days = 7): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(created_at) AS day, COUNT(*) AS submitted,
                    SUM(status = 'approved') AS approved
             FROM reviews
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(created_at) ORDER BY day ASC"
        );
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
}
