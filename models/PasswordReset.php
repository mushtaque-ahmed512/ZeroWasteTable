<?php
/**
 * One-time password reset tokens. Only a SHA-256 hash is stored, so a leaked database can't be used to reset accounts.
 */
class PasswordReset extends Model
{
    private const TTL_MINUTES = 60;

    /** Creates a fresh token (invalidating older ones) and returns the plain token to put in the link. */
    public function create(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->exec('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
        $this->exec('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ' . self::TTL_MINUTES . ' MINUTE))',
            [$userId, hash('sha256', $token)]);
        return $token;
    }

    /** @return array|null the reset row (with user_id) if the token is valid, unused and unexpired */
    public function findValid(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return $this->row('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()', [hash('sha256', $token)]);
    }

    public function consume(int $userId): void
    {
        $this->exec('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
    }
}
