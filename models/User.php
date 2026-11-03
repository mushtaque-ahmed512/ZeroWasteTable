<?php
class User extends Model
{
    public function find(int $id): ?array
    {
        return $this->row('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->row('SELECT * FROM users WHERE email = ?', [strtolower(trim($email))]);
    }

    public function emailExists(string $email): bool
    {
        return (bool)$this->val('SELECT COUNT(*) FROM users WHERE email = ?', [strtolower(trim($email))]);
    }

    public function organization(int $orgId): ?array
    {
        return $this->row('SELECT * FROM organizations WHERE id = ?', [$orgId]);
    }

    /** What we keep in $_SESSION['user'] */
    public function sessionData(array $u): array
    {
        $org = $this->row('SELECT id, org_name, verified, latitude, longitude, address FROM organizations WHERE user_id = ?', [$u['id']]);
        $vol = $this->row('SELECT id FROM volunteers WHERE user_id = ?', [$u['id']]);
        return [
            'id'           => (int)$u['id'],
            'name'         => $u['name'],
            'email'        => $u['email'],
            'role'         => $u['role'],
            'org_id'       => $org ? (int)$org['id'] : null,
            'org_name'     => $org['org_name'] ?? null,
            'verified'     => (bool)($org['verified'] ?? false),
            'volunteer_id' => $vol ? (int)$vol['id'] : null,
        ];
    }

    public function register(array $d): int
    {
        return $this->transaction(function () use ($d) {
            $this->exec(
                'INSERT INTO users (name, email, password_hash, phone, role) VALUES (?,?,?,?,?)',
                [$d['name'], strtolower($d['email']), password_hash($d['password'], PASSWORD_DEFAULT), $d['phone'], $d['role']]
            );
            $uid = $this->insertId();

            if (in_array($d['role'], ['donor', 'ngo'], true)) {
                $this->exec(
                    'INSERT INTO organizations (user_id, org_name, org_type, registration_no, address, city, latitude, longitude)
                     VALUES (?,?,?,?,?,?,?,?)',
                    [$uid, $d['org_name'], $d['org_type'], $d['registration_no'] ?: null, $d['address'], $d['city'] ?: null,
                     $d['latitude'], $d['longitude']]
                );
            } elseif ($d['role'] === 'volunteer') {
                $this->exec('INSERT INTO volunteers (user_id, vehicle, service_area) VALUES (?,?,?)',
                    [$uid, $d['vehicle'], $d['service_area'] ?: null]);
            }
            return $uid;
        });
    }

    public function touchLogin(int $id): void
    {
        $this->exec('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$id]);
    }

    public function listAll(string $role = '', string $q = ''): array
    {
        $sql = 'SELECT u.*, o.id AS org_id, o.org_name, o.org_type, o.verified, o.city, v.vehicle
                FROM users u
                LEFT JOIN organizations o ON o.user_id = u.id
                LEFT JOIN volunteers v ON v.user_id = u.id
                WHERE 1=1';
        $p = [];
        if ($role !== '') { $sql .= ' AND u.role = ?'; $p[] = $role; }
        if ($q !== '')    { $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR o.org_name LIKE ?)'; array_push($p, "%$q%", "%$q%", "%$q%"); }
        return $this->rows($sql . ' ORDER BY u.created_at DESC', $p);
    }

    public function setStatus(int $id, string $status): void
    {
        $this->exec('UPDATE users SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function setVerified(int $orgId, bool $verified): void
    {
        $this->exec('UPDATE organizations SET verified = ? WHERE id = ?', [(int)$verified, $orgId]);
    }

    /** Active volunteers that can be assigned to a pickup. */
    public function volunteerList(): array
    {
        return $this->rows("SELECT v.id, u.id AS user_id, u.name, u.phone, v.vehicle, v.service_area, v.availability
                            FROM volunteers v JOIN users u ON u.id = v.user_id
                            WHERE u.status = 'active'
                            ORDER BY FIELD(v.availability, 'available', 'busy', 'offline'), u.name");
    }

    public function volunteerById(int $id): ?array
    {
        return $this->row("SELECT v.id, u.id AS user_id, u.name FROM volunteers v JOIN users u ON u.id = v.user_id
                           WHERE v.id = ? AND u.status = 'active'", [$id]);
    }

    /** Active volunteer user ids (for broadcast notifications) */
    public function volunteerUserIds(): array
    {
        return array_column($this->rows("SELECT id FROM users WHERE role = 'volunteer' AND status = 'active'"), 'id');
    }

    public function volunteer(int $userId): ?array
    {
        return $this->row('SELECT * FROM volunteers WHERE user_id = ?', [$userId]);
    }

    public function updateProfile(int $id, string $name, string $phone): void
    {
        $this->exec('UPDATE users SET name = ?, phone = ? WHERE id = ?', [$name, $phone, $id]);
    }

    public function updateOrganization(int $userId, array $d): void
    {
        $this->exec('UPDATE organizations SET org_name=?, org_type=?, registration_no=?, address=?, city=?, latitude=?, longitude=? WHERE user_id=?',
            [$d['org_name'], $d['org_type'], $d['registration_no'] ?: null, $d['address'], $d['city'] ?: null, $d['latitude'], $d['longitude'], $userId]);
    }

    public function updateVolunteer(int $userId, array $d): void
    {
        $this->exec('UPDATE volunteers SET vehicle=?, service_area=?, availability=? WHERE user_id=?',
            [$d['vehicle'], $d['service_area'] ?: null, $d['availability'], $userId]);
    }

    public function setPassword(int $id, string $plain): void
    {
        $this->exec('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($plain, PASSWORD_DEFAULT), $id]);
    }
}
