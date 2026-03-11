<?php

class Auth
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function currentUser(): ?string
    {
        return $_SESSION['name'] ?? null;
    }

    public function isLoggedIn(): bool
    {
        return isset($_SESSION['loggedin']);
    }

    public function isAdmin(): bool
    {
        $admins = ['fet', 'ika', 'y4m', 'adm', 'plm'];
        return $this->isLoggedIn() && in_array($this->currentUser(), $admins, true);
    }

    public function requireLogin(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login', true, 302);
            exit;
        }
    }

    public function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            header('Location: /login', true, 302);
            exit;
        }
    }

    public function login(string $username, string $password): array
    {
        $name = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $username));
        $pass = $password;

        if (strlen($name) < 1 || strlen($name) > 3 || strlen($pass) < 1 || strlen($pass) > 3) {
            return ['success' => false, 'error' => 'Username and password must be 1-3 alphanumeric characters.'];
        }

        $stmt = $this->db->prepare('SELECT mv_name, mv_password FROM players WHERE mv_name = ?');
        $stmt->execute([$name]);
        $user = $stmt->fetch();

        if ($user) {
            if (!password_verify($pass, $user['mv_password'])) {
                return ['success' => false, 'error' => "Sorry, \"{$name}\" is already associated with a different password."];
            }

            $_SESSION['loggedin'] = 'YES';
            $_SESSION['name'] = $user['mv_name'];
            return ['success' => true];
        }

        return $this->register($name, $pass);
    }

    private function register(string $name, string $pass): array
    {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $head = rand(2, 13);
        $body = rand(2, 13);

        $stmt = $this->db->prepare(
            'INSERT INTO players (mv_name, mv_password, x, y, avatar_head, avatar_body) VALUES (?, ?, 30, 43, ?, ?)'
        );
        $stmt->execute([$name, $hash, $head, $body]);

        $_SESSION['loggedin'] = 'YES';
        $_SESSION['name'] = $name;
        return ['success' => true];
    }

    public function logout(): void
    {
        session_destroy();
        header('Location: /login', true, 302);
        exit;
    }

    public function migratePassword(string $name, string $plainPass): void
    {
        $stmt = $this->db->prepare('SELECT mv_password FROM players WHERE mv_name = ?');
        $stmt->execute([$name]);
        $user = $stmt->fetch();

        if ($user && password_get_info($user['mv_password'])['algo'] === null) {
            $hash = password_hash($plainPass, PASSWORD_BCRYPT);
            $stmt = $this->db->prepare('UPDATE players SET mv_password = ? WHERE mv_name = ?');
            $stmt->execute([$hash, $name]);
        }
    }
}
