<?php
/**
 * src/Controllers/AuthController.php
 * Handles signup, login, and logout logic.
 */

class AuthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ── Sign up ──────────────────────────────────────────────
    public function signup(string $userName, string $email, string $password): array
    {
        // Validation
        if (empty($userName) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Tous les champs sont obligatoires.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Email invalide.'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Le mot de passe doit contenir au moins 8 caractères.'];
        }

        // Check duplicate email
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Cet email est déjà utilisé.'];
        }

        // Insert
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (user_name, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userName, $email, $hash, 'user']);

        return ['success' => true];
    }

    // ── Login ────────────────────────────────────────────────
    public function login(string $email, string $password): array
    {
        if (empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'Email et mot de passe requis.'];
        }

        $stmt = $this->pdo->prepare(
            'SELECT id, user_name, password_hash, role, status FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Email ou mot de passe incorrect.'];
        }

        if ($user['status'] !== 'active') {
            return ['success' => false, 'error' => 'Ce compte est suspendu ou banni.'];
        }

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['user_name'];
        $_SESSION['role']      = $user['role'];

        return ['success' => true, 'role' => $user['role']];
    }

    // ── Logout ───────────────────────────────────────────────
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
