<?php
// ============================================================
// includes/auth.php  — Authentication helpers
// ============================================================
require_once __DIR__ . '/config.php';

class Auth {

    // ── Register new user ────────────────────────────────────
    public static function register(array $data): array {
        $errors = self::validateRegistration($data);
        if ($errors) return ['success' => false, 'errors' => $errors];

        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        try {
            $pdo = db();
            $pdo->beginTransaction();

            $st = $pdo->prepare('
                INSERT INTO users (name, email, phone, password, role)
                VALUES (:name, :email, :phone, :password, :role)
            ');
            $st->execute([
                ':name'     => clean($data['name']),
                ':email'    => strtolower(clean($data['email'])),
                ':phone'    => clean($data['phone'] ?? ''),
                ':password' => $hash,
                ':role'     => in_array($data['role'] ?? '', ['customer','artisan']) ? $data['role'] : 'customer',
            ]);
            $userId = (int) $pdo->lastInsertId();

            // If artisan, create profile row
            if (($data['role'] ?? '') === 'artisan') {
                $st2 = $pdo->prepare('
                    INSERT INTO artisans (user_id, county, speciality, mpesa_number)
                    VALUES (:uid, :county, :spec, :mpesa)
                ');
                $st2->execute([
                    ':uid'    => $userId,
                    ':county' => clean($data['county'] ?? ''),
                    ':spec'   => clean($data['speciality'] ?? ''),
                    ':mpesa'  => clean($data['phone'] ?? ''),
                ]);
            }

            $pdo->commit();

            // Auto-login
            self::startSession($userId);
            return ['success' => true, 'user_id' => $userId];

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() === '23000') {
                return ['success' => false, 'errors' => ['email' => 'Email address already registered.']];
            }
            return ['success' => false, 'errors' => ['general' => 'Registration failed. Please try again.']];
        }
    }

    // ── Login ────────────────────────────────────────────────
    public static function login(string $email, string $password): array {
        $st = db()->prepare('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1');
        $st->execute([strtolower(trim($email))]);
        $user = $st->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Invalid email or password.'];
        }

        self::startSession($user['id']);
        return ['success' => true, 'role' => $user['role']];
    }

    // ── Password reset request ───────────────────────────────
    public static function requestPasswordReset(string $email): array {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Please enter a valid email address.'];
        }

        $st = db()->prepare('SELECT id FROM users WHERE email = ? AND status = "active" LIMIT 1');
        $st->execute([strtolower(trim($email))]);
        $user = $st->fetch();

        if (!$user) {
            return ['success' => true];
        }

        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        $resetLink = APP_URL . '/reset-password.php?token=' . $token;

        db()->prepare('UPDATE users SET password_reset_token = ?, password_reset_expires_at = ? WHERE id = ?')
            ->execute([$token, $expires, $user['id']]);

        return ['success' => true, 'reset_link' => $resetLink];
    }

    public static function resetPassword(string $token, string $password, string $confirmPassword): array {
        if (empty($token)) {
            return ['success' => false, 'errors' => ['general' => 'Invalid reset token.']];
        }

        $expiresAt = date('Y-m-d H:i:s');
        $st = db()->prepare('SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires_at >= ? LIMIT 1');
        $st->execute([$token, $expiresAt]);
        $user = $st->fetch();
        if (!$user) {
            return ['success' => false, 'errors' => ['general' => 'Reset token is invalid or expired.']];
        }

        $errors = [];
        if (empty($password) || strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        db()->prepare('UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires_at = NULL WHERE id = ?')
            ->execute([$hash, $user['id']]);

        return ['success' => true];
    }

    // ── Logout ───────────────────────────────────────────────
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── Start session after auth ─────────────────────────────
    private static function startSession(int $userId): void {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // ── Validation ───────────────────────────────────────────
    private static function validateRegistration(array $d): array {
        $errors = [];
        if (empty($d['name']) || strlen(trim($d['name'])) < 2) {
            $errors['name'] = 'Name must be at least 2 characters.';
        }
        if (empty($d['email']) || !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        if (empty($d['password']) || strlen($d['password']) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }
        if (($d['password'] ?? '') !== ($d['confirm_password'] ?? '')) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        // Phone for artisans
        if (($d['role'] ?? '') === 'artisan' && empty($d['phone'])) {
            $errors['phone'] = 'M-Pesa phone number is required for artisans.';
        }
        return $errors;
    }

    // ── Get artisan profile for logged-in user ───────────────
    public static function getArtisanProfile(int $userId): ?array {
        $st = db()->prepare('SELECT a.*, u.name, u.email, u.phone FROM artisans a
                             JOIN users u ON u.id = a.user_id WHERE a.user_id = ?');
        $st->execute([$userId]);
        return $st->fetch() ?: null;
    }
}
