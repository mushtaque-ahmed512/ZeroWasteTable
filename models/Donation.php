<?php
class Donation extends Model
{
    public const UNITS = ['kg', 'portions', 'packs', 'liters', 'items'];

    private const BASE = 'SELECT d.*, c.name AS category, o.org_name, o.address AS org_address, o.user_id AS donor_user_id,
                                 (SELECT image_path FROM donation_images i WHERE i.donation_id = d.id ORDER BY i.id LIMIT 1) AS cover,
                                 (SELECT COUNT(*) FROM food_requests r WHERE r.donation_id = d.id AND r.status = \'pending\') AS pending_requests
                          FROM food_donations d
                          JOIN food_categories c ON c.id = d.category_id
                          JOIN organizations o ON o.id = d.org_id';

    public function create(int $orgId, array $d): int
    {
        $this->exec(
            'INSERT INTO food_donations (org_id, category_id, title, description, quantity, unit, expiry_at, pickup_address, latitude, longitude)
             VALUES (?,?,?,?,?,?,?,?,?,?)',
            [$orgId, $d['category_id'], $d['title'], $d['description'] ?: null, $d['quantity'], $d['unit'],
             $d['expiry_at'], $d['pickup_address'], $d['latitude'], $d['longitude']]
        );
        return $this->insertId();
    }

    public function update(int $id, array $d): void
    {
        $this->exec(
            'UPDATE food_donations SET category_id=?, title=?, description=?, quantity=?, unit=?, expiry_at=?,
                    pickup_address=?, latitude=?, longitude=?, expiry_alerted=0 WHERE id=?',
            [$d['category_id'], $d['title'], $d['description'] ?: null, $d['quantity'], $d['unit'], $d['expiry_at'],
             $d['pickup_address'], $d['latitude'], $d['longitude'], $id]
        );
    }

    public function find(int $id): ?array
    {
        return $this->row(self::BASE . ' WHERE d.id = ?', [$id]);
    }

    public function images(int $id): array
    {
        return $this->rows('SELECT * FROM donation_images WHERE donation_id = ? ORDER BY id', [$id]);
    }

    public function imageCount(int $id): int
    {
        return (int)$this->val('SELECT COUNT(*) FROM donation_images WHERE donation_id = ?', [$id]);
    }

    public function addImages(int $id, array $paths): void
    {
        foreach ($paths as $p) {
            $this->exec('INSERT INTO donation_images (donation_id, image_path) VALUES (?, ?)', [$id, $p]);
        }
    }

    public function findImage(int $donationId, int $imageId): ?array
    {
        return $this->row('SELECT * FROM donation_images WHERE id = ? AND donation_id = ?', [$imageId, $donationId]);
    }

    public function deleteImage(int $imageId): void
    {
        $this->exec('DELETE FROM donation_images WHERE id = ?', [$imageId]);
    }

    /** Listings an NGO can still request. */
    public function browse(string $q = '', int $categoryId = 0): array
    {
        $sql = self::BASE . " WHERE d.status IN ('available','requested') AND d.expiry_at > NOW()";
        $p = [];
        if ($q !== '')        { $sql .= ' AND (d.title LIKE ? OR o.org_name LIKE ?)'; array_push($p, "%$q%", "%$q%"); }
        if ($categoryId > 0)  { $sql .= ' AND d.category_id = ?'; $p[] = $categoryId; }
        return $this->rows($sql . ' ORDER BY d.expiry_at ASC', $p);
    }

    public function forOrg(int $orgId, string $status = ''): array
    {
        $sql = self::BASE . ' WHERE d.org_id = ?';
        $p = [$orgId];
        if ($status !== '') { $sql .= ' AND d.status = ?'; $p[] = $status; }
        return $this->rows($sql . ' ORDER BY d.created_at DESC', $p);
    }

    public function all(string $status = ''): array
    {
        $sql = self::BASE;
        $p = [];
        if ($status !== '') { $sql .= ' WHERE d.status = ?'; $p[] = $status; }
        return $this->rows($sql . ' ORDER BY d.created_at DESC', $p);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->exec('UPDATE food_donations SET status = ? WHERE id = ?', [$status, $id]);
    }

    /** Cancel a listing and everything hanging off it. */
    public function cancel(int $id): void
    {
        $this->transaction(function () use ($id) {
            $this->setStatus($id, 'cancelled');
            $this->exec("UPDATE food_requests SET status = 'cancelled' WHERE donation_id = ? AND status IN ('pending','approved')", [$id]);
            $this->exec("UPDATE deliveries SET status = 'cancelled' WHERE status = 'scheduled' AND schedule_id IN (
                            SELECT ps.id FROM pickup_schedules ps JOIN food_requests fr ON fr.id = ps.request_id WHERE fr.donation_id = ?)", [$id]);
        });
    }

    /** Open donations whose expiry time has passed (with donor + approved NGO user ids). */
    public function overdue(): array
    {
        return $this->rows(
            "SELECT d.id, d.title, o.user_id AS donor_user_id
             FROM food_donations d JOIN organizations o ON o.id = d.org_id
             WHERE d.status IN ('available','requested','approved','scheduled') AND d.expiry_at <= NOW()"
        );
    }

    public function markExpired(int $id): void
    {
        $this->transaction(function () use ($id) {
            $this->setStatus($id, 'expired');
            $this->exec("UPDATE food_requests SET status = 'cancelled' WHERE donation_id = ? AND status = 'pending'", [$id]);
            $this->exec("UPDATE deliveries SET status = 'cancelled' WHERE status = 'scheduled' AND schedule_id IN (
                            SELECT ps.id FROM pickup_schedules ps JOIN food_requests fr ON fr.id = ps.request_id WHERE fr.donation_id = ?)", [$id]);
        });
    }

    /** Not yet alerted, expiring inside the warning window. */
    public function expiringSoon(int $hours): array
    {
        return $this->rows(
            "SELECT d.id, d.title, d.expiry_at, d.status, o.user_id AS donor_user_id, n.user_id AS ngo_user_id
             FROM food_donations d
             JOIN organizations o ON o.id = d.org_id
             LEFT JOIN food_requests fr ON fr.donation_id = d.id AND fr.status = 'approved'
             LEFT JOIN organizations n ON n.id = fr.ngo_id
             WHERE d.status IN ('available','requested','approved','scheduled') AND d.expiry_alerted = 0
               AND d.expiry_at > NOW() AND d.expiry_at <= DATE_ADD(NOW(), INTERVAL " . (int)$hours . " HOUR)"
        );
    }

    public function markAlerted(int $id): void
    {
        $this->exec('UPDATE food_donations SET expiry_alerted = 1 WHERE id = ?', [$id]);
    }
}
