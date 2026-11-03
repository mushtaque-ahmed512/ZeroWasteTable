<?php
class Notification extends Model
{
    public function add(int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        $this->exec('INSERT INTO notifications (user_id, type, title, message, link) VALUES (?,?,?,?,?)',
            [$userId, $type, $title, $message, $link]);
    }

    public function addMany(array $userIds, string $type, string $title, string $message, ?string $link = null): void
    {
        foreach ($userIds as $uid) {
            $this->add((int)$uid, $type, $title, $message, $link);
        }
    }

    public function forUser(int $userId, int $limit = 60): array
    {
        return $this->rows('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . (int)$limit, [$userId]);
    }

    public function unreadCount(int $userId): int
    {
        return (int)$this->val('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
    }

    public function find(int $id, int $userId): ?array
    {
        return $this->row('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->exec('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [$id, $userId]);
    }

    public function markAllRead(int $userId): void
    {
        $this->exec('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', [$userId]);
    }

    /**
     * Expiry sweep: expires overdue listings and warns about the ones close to expiry.
     * Called from index.php (throttled) and from cron/expiry_check.php.
     */
    public function runExpiryChecks(bool $throttled = false): void
    {
        if ($throttled) {
            $stamp = UPLOAD_PATH . '/.expiry_check';
            if (is_file($stamp) && time() - filemtime($stamp) < EXPIRY_CHECK_INTERVAL) {
                return;
            }
            @touch($stamp);
        }

        $donations = new Donation();

        foreach ($donations->overdue() as $d) {
            $donations->markExpired((int)$d['id']);
            $this->add((int)$d['donor_user_id'], 'expiry', 'Donation expired',
                '"' . $d['title'] . '" passed its expiry time and was closed.', 'donations/' . $d['id']);
        }

        foreach ($donations->expiringSoon(EXPIRY_ALERT_HOURS) as $d) {
            $msg = '"' . $d['title'] . '" expires ' . fmt_dt($d['expiry_at']) . '. Please complete the hand-over in time.';
            $this->add((int)$d['donor_user_id'], 'expiry', 'Food expiring soon', $msg, 'donations/' . $d['id']);
            if (!empty($d['ngo_user_id'])) {
                $this->add((int)$d['ngo_user_id'], 'expiry', 'Food expiring soon', $msg, 'donations/' . $d['id']);
            }
            $donations->markAlerted((int)$d['id']);
        }
    }
}
