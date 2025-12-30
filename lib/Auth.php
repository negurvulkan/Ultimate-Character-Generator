<?php
namespace Ucg;

use PDO;

class Auth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login(string $email, string $password): bool
    {
        $stmt = $this->pdo->prepare('SELECT * FROM admin_user WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user'] = ['id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']];
            return true;
        }
        return false;
    }

    public function ensure(): void
    {
        if (!$this->user()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
