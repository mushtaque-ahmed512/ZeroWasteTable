<?php
/**
 * Pickup schedules + deliveries (the scheduling, QR verification and distribution-tracking steps).
 */
class Delivery extends Model
{
    private const BASE = 'SELECT dl.*, ps.pickup_at, ps.notes AS pickup_notes, ps.request_id,
                                 fr.ngo_id, fr.quantity_needed,
                                 fd.id AS donation_id, fd.title, fd.quantity, fd.unit, fd.expiry_at, fd.pickup_address,
                                 fd.latitude AS donor_lat, fd.longitude AS donor_lng, fd.status AS donation_status,
                                 donor.id AS donor_org_id, donor.org_name AS donor_name, donor.user_id AS donor_user_id,
                                 ngo.org_name AS ngo_name, ngo.user_id AS ngo_user_id, ngo.address AS ngo_address,
                                 ngo.latitude AS ngo_lat, ngo.longitude AS ngo_lng,
                                 vu.name AS volunteer_name, vu.phone AS volunteer_phone, v.user_id AS volunteer_user_id
                          FROM deliveries dl
                          JOIN pickup_schedules ps ON ps.id = dl.schedule_id
                          JOIN food_requests fr ON fr.id = ps.request_id
                          JOIN food_donations fd ON fd.id = fr.donation_id
                          JOIN organizations donor ON donor.id = fd.org_id
                          JOIN organizations ngo ON ngo.id = fr.ngo_id
                          LEFT JOIN volunteers v ON v.id = dl.volunteer_id
                          LEFT JOIN users vu ON vu.id = v.user_id';

    /** Creates the schedule, the delivery (with its QR token) and moves the donation to "scheduled". */
    public function schedule(array $req, string $pickupAt, string $notes, int $userId): int
    {
        return $this->transaction(function () use ($req, $pickupAt, $notes, $userId) {
            $this->exec('INSERT INTO pickup_schedules (request_id, pickup_at, notes, created_by) VALUES (?,?,?,?)',
                [$req['id'], $pickupAt, $notes ?: null, $userId]);
            $scheduleId = $this->insertId();
            $this->exec('INSERT INTO deliveries (schedule_id, qr_token) VALUES (?, ?)', [$scheduleId, bin2hex(random_bytes(16))]);
            $deliveryId = $this->insertId();
            $this->exec("UPDATE food_donations SET status = 'scheduled' WHERE id = ?", [$req['donation_id']]);
            return $deliveryId;
        });
    }

    public function find(int $id): ?array
    {
        return $this->row(self::BASE . ' WHERE dl.id = ?', [$id]);
    }

    public function findByToken(string $token): ?array
    {
        return $this->row(self::BASE . ' WHERE dl.qr_token = ?', [$token]);
    }

    public function listFor(string $role, ?int $orgId, ?int $volunteerId): array
    {
        $sql = self::BASE;
        $p = [];
        if ($role === 'donor')          { $sql .= ' WHERE donor.id = ?'; $p[] = $orgId; }
        elseif ($role === 'ngo')        { $sql .= ' WHERE ngo.id = ?';   $p[] = $orgId; }
        elseif ($role === 'volunteer')  { $sql .= ' WHERE dl.volunteer_id = ?'; $p[] = $volunteerId; }
        return $this->rows($sql . ' ORDER BY FIELD(dl.status,\'scheduled\',\'picked_up\',\'delivered\',\'cancelled\'), ps.pickup_at', $p);
    }

    /** Scheduled deliveries nobody has claimed yet. */
    public function openTasks(): array
    {
        return $this->rows(self::BASE . " WHERE dl.volunteer_id IS NULL AND dl.status = 'scheduled' AND fd.expiry_at > NOW() ORDER BY ps.pickup_at");
    }

    public function claim(int $id, int $volunteerId): bool
    {
        return $this->exec("UPDATE deliveries SET volunteer_id = ? WHERE id = ? AND volunteer_id IS NULL AND status = 'scheduled'",
            [$volunteerId, $id]) === 1;
    }

    /** Assign (or clear, with null) the volunteer for a pickup that has not happened yet. */
    public function setVolunteer(int $id, ?int $volunteerId): void
    {
        $this->exec("UPDATE deliveries SET volunteer_id = ? WHERE id = ? AND status = 'scheduled'", [$volunteerId, $id]);
    }

    public function markPickedUp(array $dl): void
    {
        $this->transaction(function () use ($dl) {
            $this->exec("UPDATE deliveries SET status='picked_up', picked_up_at=NOW() WHERE id=? AND status='scheduled'", [$dl['id']]);
            $this->exec("UPDATE food_donations SET status='collected' WHERE id=?", [$dl['donation_id']]);
        });
    }

    public function markDelivered(array $dl, int $people, string $notes): void
    {
        $this->transaction(function () use ($dl, $people, $notes) {
            $this->exec("UPDATE deliveries SET status='delivered', delivered_at=NOW(), people_served=?, distribution_notes=? WHERE id=? AND status='picked_up'",
                [$people, $notes ?: null, $dl['id']]);
            $this->exec("UPDATE food_donations SET status='delivered' WHERE id=?", [$dl['donation_id']]);
        });
    }
}
