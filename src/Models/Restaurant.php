<?php
/**
 * src/Models/Restaurant.php
 * Low-level DB queries for restaurants table.
 */
class Restaurant
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT r.*, u.user_name AS owner_name, u.email AS owner_email
             FROM restaurants r JOIN users u ON u.id = r.owner_id
             WHERE r.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO restaurants
             (owner_id, name, description, category, city, address, phone, price_range, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['owner_id'], $data['name'],        $data['description'] ?? '',
            $data['category'], $data['city'],         $data['address']     ?? '',
            $data['phone']  ?? '', $data['price_range'] ?? '€€',
            $data['status'] ?? 'pending',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $values = [];
        $allowed = ['name','description','category','city','address','phone','price_range','status'];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) {
                $fields[] = "$f = ?";
                $values[] = $data[$f];
            }
        }
        if (empty($fields)) return false;
        $values[] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE restaurants SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        return $this->pdo->prepare('DELETE FROM restaurants WHERE id = ?')->execute([$id]);
    }

    public function countByCategory(): array
    {
        $stmt = $this->pdo->query(
            "SELECT category, COUNT(*) AS cnt FROM restaurants
             WHERE status = 'active' GROUP BY category ORDER BY cnt DESC"
        );
        return $stmt->fetchAll();
    }

    public function countByCity(): array
    {
        $stmt = $this->pdo->query(
            "SELECT city, COUNT(*) AS cnt FROM restaurants
             WHERE status = 'active' GROUP BY city ORDER BY cnt DESC LIMIT 10"
        );
        return $stmt->fetchAll();
    }
}
