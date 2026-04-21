<?php
/**
 * src/Controllers/UserController.php
 */

require_once __DIR__ . '/../../config/constants.php';

class UserController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── Get all users ─────────────────────────────────────────
    public function getAll(int $page = 1): array
    {
        $offset = ($page - 1) * PER_PAGE;
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.user_name, u.email, u.role, u.status, u.created_at,
                    COUNT(rv.id) AS review_count
             FROM users u
             LEFT JOIN reviews rv ON rv.user_id = u.id AND rv.status = "approved"
             GROUP BY u.id
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([PER_PAGE, $offset]);
        return $stmt->fetchAll();
    }

    // ── Count ────────────────────────────────────────────────
    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    // ── Suspend / reactivate ──────────────────────────────────
    public function toggleStatus(int $id, int $adminId): bool
    {
        $stmt = $this->pdo->prepare('SELECT status FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) return false;

        $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
        $stmt = $this->pdo->prepare('UPDATE users SET status = ? WHERE id = ?');
        $ok = $stmt->execute([$newStatus, $id]);

        if ($ok) $this->log($adminId, 'UPDATE', $id, "status→$newStatus");
        return $ok;
    }

    // ── Ban ──────────────────────────────────────────────────
    public function ban(int $id, int $adminId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET status = 'banned' WHERE id = ? AND role != 'admin'");
        $ok = $stmt->execute([$id]);
        if ($ok) $this->log($adminId, 'BAN', $id);
        return $ok;
    }

    // ── Stats summary ─────────────────────────────────────────
    public function getStats(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
               COUNT(*)                          AS total,
               SUM(status = 'active')            AS active,
               SUM(status = 'suspended')         AS suspended,
               SUM(status = 'banned')            AS banned,
               SUM(role = 'admin')               AS admins
             FROM users"
        );
        return $stmt->fetch();
    }

    // ── Private log ──────────────────────────────────────────
    private function log(int $adminId, string $action, int $targetId, string $details = ''): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO activity_logs (admin_id, action, target_type, target_id, details)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$adminId, $action, 'user', $targetId, $details]);
    }
}
