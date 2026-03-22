<?php
/**
 * src/Controllers/RestaurantController.php
 */

require_once __DIR__ . '/../../config/constants.php';

class RestaurantController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── Get all with filters + pagination ────────────────────
    public function getAll(array $filters = [], int $page = 1): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['category'])) {
            $where[]  = 'category = ?';
            $params[] = $filters['category'];
        }
        if (!empty($filters['city'])) {
            $where[]  = 'city LIKE ?';
            $params[] = '%' . $filters['city'] . '%';
        }

        $offset = ($page - 1) * PER_PAGE;
        $sql = 'SELECT r.*, u.user_name AS owner_name
                FROM restaurants r
                JOIN users u ON u.id = r.owner_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?';

        $params[] = PER_PAGE;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ── Count (for pagination) ───────────────────────────────
    public function count(array $filters = []): int
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status']))   { $where[] = 'status = ?';      $params[] = $filters['status']; }
        if (!empty($filters['category'])) { $where[] = 'category = ?';    $params[] = $filters['category']; }
        if (!empty($filters['city']))     { $where[] = 'city LIKE ?';     $params[] = '%' . $filters['city'] . '%'; }

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM restaurants WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    // ── Get pending ──────────────────────────────────────────
    public function getPending(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.user_name AS owner_name, u.email AS owner_email
             FROM restaurants r
             JOIN users u ON u.id = r.owner_id
             WHERE r.status = ? ORDER BY r.created_at ASC'
        );
        $stmt->execute([STATUS_PENDING]);
        return $stmt->fetchAll();
    }

    // ── Approve ──────────────────────────────────────────────
    public function approve(int $id, int $adminId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE restaurants SET status = 'active' WHERE id = ?"
        );
        $ok = $stmt->execute([$id]);
        if ($ok) $this->log($adminId, 'APPROVE', 'restaurant', $id);
        return $ok;
    }

    // ── Reject ───────────────────────────────────────────────
    public function reject(int $id, int $adminId, string $reason = ''): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE restaurants SET status = 'rejected' WHERE id = ?"
        );
        $ok = $stmt->execute([$id]);
        if ($ok) $this->log($adminId, 'REJECT', 'restaurant', $id, $reason);
        return $ok;
    }

    // ── Delete ───────────────────────────────────────────────
    public function delete(int $id, int $adminId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM restaurants WHERE id = ?');
        $ok = $stmt->execute([$id]);
        if ($ok) $this->log($adminId, 'DELETE', 'restaurant', $id);
        return $ok;
    }

    // ── Update status ────────────────────────────────────────
    public function updateStatus(int $id, string $status, int $adminId): bool
    {
        $allowed = [STATUS_ACTIVE, STATUS_INACTIVE, STATUS_PENDING, STATUS_REJECTED];
        if (!in_array($status, $allowed)) return false;

        $stmt = $this->pdo->prepare('UPDATE restaurants SET status = ? WHERE id = ?');
        $ok = $stmt->execute([$status, $id]);
        if ($ok) $this->log($adminId, 'UPDATE', 'restaurant', $id, "status→$status");
        return $ok;
    }

    // ── Top restaurants by rating ────────────────────────────
    public function getTop(int $limit = 5): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM restaurants WHERE status = 'active'
             ORDER BY avg_rating DESC, review_count DESC LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // ── Private: write activity log ──────────────────────────
    private function log(int $adminId, string $action, string $type, int $targetId, string $details = ''): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO activity_logs (admin_id, action, target_type, target_id, details)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$adminId, $action, $type, $targetId, $details]);
    }
}
