<?php
/**
 * src/Controllers/ReviewController.php
 */

require_once __DIR__ . '/../../config/constants.php';

class ReviewController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── Get all with filter ───────────────────────────────────
    public function getAll(string $status = '', int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if ($status) {
            $where[]  = 'rv.status = ?';
            $params[] = $status;
        }

        $offset   = ($page - 1) * PER_PAGE;
        $params[] = PER_PAGE;
        $params[] = $offset;

        $stmt = $this->pdo->prepare(
            'SELECT rv.*, u.user_name, r.name AS restaurant_name
             FROM reviews rv
             JOIN users u       ON u.id = rv.user_id
             JOIN restaurants r ON r.id = rv.restaurant_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY rv.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Count ────────────────────────────────────────────────
    public function count(string $status = ''): int
    {
        if ($status) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM reviews WHERE status = ?');
            $stmt->execute([$status]);
        } else {
            $stmt = $this->pdo->query('SELECT COUNT(*) FROM reviews');
        }
        return (int) $stmt->fetchColumn();
    }

    // ── Approve ──────────────────────────────────────────────
    public function approve(int $id, int $adminId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $ok = $stmt->execute([$id]);
        if ($ok) $this->log($adminId, 'APPROVE', $id);
        return $ok;
    }

    // ── Flag ─────────────────────────────────────────────────
    public function flag(int $id, int $adminId, string $reason = ''): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE reviews SET status = 'flagged', flag_reason = ? WHERE id = ?"
        );
        $ok = $stmt->execute([$reason, $id]);
        if ($ok) $this->log($adminId, 'FLAG', $id, $reason);
        return $ok;
    }

    // ── Delete ───────────────────────────────────────────────
    public function delete(int $id, int $adminId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE reviews SET status = 'deleted' WHERE id = ?");
        $ok = $stmt->execute([$id]);
        if ($ok) $this->log($adminId, 'DELETE', $id);
        return $ok;
    }

    // ── Stats ────────────────────────────────────────────────
    public function getStats(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
               COUNT(*)                                        AS total,
               SUM(status = 'pending')                        AS pending,
               SUM(status = 'approved')                       AS approved,
               SUM(status = 'flagged')                        AS flagged,
               SUM(status = 'deleted')                        AS deleted,
               ROUND(AVG(CASE WHEN status='approved' THEN rating END), 2) AS avg_rating
             FROM reviews"
        );
        return $stmt->fetch();
    }

    // ── Rating distribution ───────────────────────────────────
    public function getRatingDistribution(): array
    {
        $stmt = $this->pdo->query(
            "SELECT rating, COUNT(*) AS cnt
             FROM reviews WHERE status = 'approved'
             GROUP BY rating ORDER BY rating DESC"
        );
        return $stmt->fetchAll();
    }

    // ── Private log ──────────────────────────────────────────
    private function log(int $adminId, string $action, int $targetId, string $details = ''): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO activity_logs (admin_id, action, target_type, target_id, details)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$adminId, $action, 'review', $targetId, $details]);
    }
}
