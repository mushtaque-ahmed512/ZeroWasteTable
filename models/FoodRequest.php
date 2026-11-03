<?php
class FoodRequest extends Model
{
    public function find(int $id): ?array
    {
        return $this->row(
            'SELECT r.*, d.title, d.org_id AS donor_org_id, d.status AS donation_status, d.quantity AS donation_qty, d.unit, d.expiry_at,
                    ngo.org_name AS ngo_name, ngo.user_id AS ngo_user_id, dn.user_id AS donor_user_id, dn.org_name AS donor_name,
                    ps.id AS schedule_id
             FROM food_requests r
             JOIN food_donations d ON d.id = r.donation_id
             JOIN organizations ngo ON ngo.id = r.ngo_id
             JOIN organizations dn ON dn.id = d.org_id
             LEFT JOIN pickup_schedules ps ON ps.request_id = r.id
             WHERE r.id = ?', [$id]);
    }

    public function existing(int $donationId, int $ngoId): ?array
    {
        return $this->row('SELECT * FROM food_requests WHERE donation_id = ? AND ngo_id = ?', [$donationId, $ngoId]);
    }

    public function create(int $donationId, int $ngoId, float $qty, string $message): int
    {
        return $this->transaction(function () use ($donationId, $ngoId, $qty, $message) {
            $this->exec('INSERT INTO food_requests (donation_id, ngo_id, quantity_needed, message) VALUES (?,?,?,?)',
                [$donationId, $ngoId, $qty, $message ?: null]);
            $id = $this->insertId();
            $this->exec("UPDATE food_donations SET status = 'requested' WHERE id = ? AND status = 'available'", [$donationId]);
            return $id;
        });
    }

    /** A cancelled request may be re-opened by the same NGO. */
    public function reopen(int $id, int $donationId, float $qty, string $message): void
    {
        $this->transaction(function () use ($id, $donationId, $qty, $message) {
            $this->exec("UPDATE food_requests SET status='pending', quantity_needed=?, message=?, responded_at=NULL WHERE id=?",
                [$qty, $message ?: null, $id]);
            $this->exec("UPDATE food_donations SET status = 'requested' WHERE id = ? AND status = 'available'", [$donationId]);
        });
    }

    public function forDonation(int $donationId): array
    {
        return $this->rows(
            'SELECT r.*, o.org_name, o.address, o.city, u.phone, o.verified, dl.id AS delivery_id, ps.id AS schedule_id
             FROM food_requests r JOIN organizations o ON o.id = r.ngo_id JOIN users u ON u.id = o.user_id
             LEFT JOIN pickup_schedules ps ON ps.request_id = r.id LEFT JOIN deliveries dl ON dl.schedule_id = ps.id
             WHERE r.donation_id = ? ORDER BY FIELD(r.status, \'approved\', \'pending\', \'rejected\', \'cancelled\'), r.created_at', [$donationId]);
    }

    public function listFor(string $role, ?int $orgId): array
    {
        $sql = 'SELECT r.*, d.title, d.unit, d.quantity AS donation_qty, d.status AS donation_status, d.expiry_at,
                       ngo.org_name AS ngo_name, dn.org_name AS donor_name, dl.id AS delivery_id
                FROM food_requests r
                JOIN food_donations d ON d.id = r.donation_id
                JOIN organizations ngo ON ngo.id = r.ngo_id
                JOIN organizations dn ON dn.id = d.org_id
                LEFT JOIN pickup_schedules ps ON ps.request_id = r.id
                LEFT JOIN deliveries dl ON dl.schedule_id = ps.id';
        $p = [];
        if ($role === 'ngo')   { $sql .= ' WHERE r.ngo_id = ?'; $p[] = $orgId; }
        if ($role === 'donor') { $sql .= ' WHERE d.org_id = ?';  $p[] = $orgId; }
        return $this->rows($sql . ' ORDER BY FIELD(r.status, \'pending\', \'approved\', \'rejected\', \'cancelled\'), r.created_at DESC', $p);
    }

    /** Approve one request; every other pending request on the same donation is rejected. */
    public function approve(array $req): void
    {
        $this->transaction(function () use ($req) {
            $this->exec("UPDATE food_requests SET status='approved', responded_at=NOW() WHERE id=?", [$req['id']]);
            $this->exec("UPDATE food_requests SET status='rejected', responded_at=NOW() WHERE donation_id=? AND id<>? AND status='pending'",
                [$req['donation_id'], $req['id']]);
            $this->exec("UPDATE food_donations SET status='approved' WHERE id=?", [$req['donation_id']]);
        });
    }

    public function close(array $req, string $status): void
    {
        $this->transaction(function () use ($req, $status) {
            $this->exec('UPDATE food_requests SET status=?, responded_at=NOW() WHERE id=?', [$status, $req['id']]);
            $pending = (int)$this->val("SELECT COUNT(*) FROM food_requests WHERE donation_id=? AND status='pending'", [$req['donation_id']]);
            if ($pending === 0) {
                $this->exec("UPDATE food_donations SET status='available' WHERE id=? AND status='requested'", [$req['donation_id']]);
            }
        });
    }
}
