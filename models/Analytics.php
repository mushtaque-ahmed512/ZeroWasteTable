<?php
/**
 * Numbers for the dashboard cards and Chart.js charts, scoped to the logged-in role.
 */
class Analytics extends Model
{
    /** SQL fragment limiting food_donations (alias fd) to what the role may see. */
    private function scope(string $role, ?int $orgId, ?int $volunteerId): array
    {
        return match ($role) {
            'donor' => [' AND fd.org_id = ?', [$orgId]],
            'ngo' => [' AND fd.id IN (SELECT donation_id FROM food_requests WHERE ngo_id = ?)', [$orgId]],
            'volunteer' => [' AND fd.id IN (SELECT fr.donation_id FROM deliveries dl2
                                JOIN pickup_schedules ps2 ON ps2.id = dl2.schedule_id
                                JOIN food_requests fr ON fr.id = ps2.request_id WHERE dl2.volunteer_id = ?)', [$volunteerId]],
            default => ['', []],
        };
    }

    public function publicStats(): array
    {
        return [
            'donations' => (int)$this->val("SELECT COUNT(*) FROM food_donations WHERE status NOT IN ('cancelled')"),
            'delivered' => (int)$this->val("SELECT COUNT(*) FROM food_donations WHERE status = 'delivered'"),
            'people'    => (int)$this->val("SELECT COALESCE(SUM(people_served),0) FROM deliveries WHERE status = 'delivered'"),
            'partners'  => (int)$this->val("SELECT COUNT(*) FROM organizations"),
        ];
    }

    /** Headline numbers for the dashboard cards. */
    public function stats(array $u): array
    {
        [$sc, $sp] = $this->scope($u['role'], $u['org_id'], $u['volunteer_id']);

        $byStatus = [];
        foreach ($this->rows("SELECT fd.status, COUNT(*) AS c FROM food_donations fd WHERE 1=1 $sc GROUP BY fd.status", $sp) as $r) {
            $byStatus[$r['status']] = (int)$r['c'];
        }
        $people = (int)$this->val(
            "SELECT COALESCE(SUM(dl.people_served),0) FROM deliveries dl
             JOIN pickup_schedules ps ON ps.id = dl.schedule_id JOIN food_requests fr ON fr.id = ps.request_id
             JOIN food_donations fd ON fd.id = fr.donation_id
             WHERE dl.status = 'delivered' $sc", $sp);

        $s = [
            'total'     => array_sum($byStatus),
            'open'      => ($byStatus['available'] ?? 0) + ($byStatus['requested'] ?? 0) + ($byStatus['approved'] ?? 0) + ($byStatus['scheduled'] ?? 0),
            'delivered' => $byStatus['delivered'] ?? 0,
            'expired'   => $byStatus['expired'] ?? 0,
            'people'    => $people,
        ];

        switch ($u['role']) {
            case 'admin':
                $s['users']        = (int)$this->val("SELECT COUNT(*) FROM users WHERE role <> 'admin'");
                $s['unverified']   = (int)$this->val('SELECT COUNT(*) FROM organizations WHERE verified = 0');
                break;
            case 'donor':
                $s['pending_requests'] = (int)$this->val(
                    "SELECT COUNT(*) FROM food_requests r JOIN food_donations d ON d.id = r.donation_id WHERE d.org_id = ? AND r.status = 'pending'", [$u['org_id']]);
                break;
            case 'ngo':
                $s['my_pending']  = (int)$this->val("SELECT COUNT(*) FROM food_requests WHERE ngo_id = ? AND status = 'pending'", [$u['org_id']]);
                $s['my_approved'] = (int)$this->val("SELECT COUNT(*) FROM food_requests WHERE ngo_id = ? AND status = 'approved'", [$u['org_id']]);
                $s['available']   = (int)$this->val("SELECT COUNT(*) FROM food_donations WHERE status IN ('available','requested') AND expiry_at > NOW()");
                break;
            case 'volunteer':
                $s['open_tasks'] = (int)$this->val("SELECT COUNT(*) FROM deliveries WHERE volunteer_id IS NULL AND status = 'scheduled'");
                $s['mine_active'] = (int)$this->val("SELECT COUNT(*) FROM deliveries WHERE volunteer_id = ? AND status IN ('scheduled','picked_up')", [$u['volunteer_id']]);
                $s['mine_done']   = (int)$this->val("SELECT COUNT(*) FROM deliveries WHERE volunteer_id = ? AND status = 'delivered'", [$u['volunteer_id']]);
                break;
        }
        return $s;
    }

    /** Data series for the four dashboard charts. */
    public function charts(array $u): array
    {
        [$sc, $sp] = $this->scope($u['role'], $u['org_id'], $u['volunteer_id']);

        // last 6 calendar months, including empty ones
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $months[date('Y-m', strtotime("first day of -$i month"))] = date('M Y', strtotime("first day of -$i month"));
        }
        $from = date('Y-m-01 00:00:00', strtotime('first day of -5 month'));

        $posted = array_fill_keys(array_keys($months), 0);
        foreach ($this->rows("SELECT DATE_FORMAT(fd.created_at,'%Y-%m') AS m, COUNT(*) AS c FROM food_donations fd
                              WHERE fd.created_at >= ? $sc GROUP BY m", array_merge([$from], $sp)) as $r) {
            if (isset($posted[$r['m']])) $posted[$r['m']] = (int)$r['c'];
        }

        $served = array_fill_keys(array_keys($months), 0);
        foreach ($this->rows("SELECT DATE_FORMAT(dl.delivered_at,'%Y-%m') AS m, COALESCE(SUM(dl.people_served),0) AS c FROM deliveries dl
                              JOIN pickup_schedules ps ON ps.id = dl.schedule_id JOIN food_requests fr ON fr.id = ps.request_id
                              JOIN food_donations fd ON fd.id = fr.donation_id
                              WHERE dl.status = 'delivered' AND dl.delivered_at >= ? $sc GROUP BY m", array_merge([$from], $sp)) as $r) {
            if (isset($served[$r['m']])) $served[$r['m']] = (int)$r['c'];
        }

        $cat = $this->rows("SELECT c.name, COUNT(*) AS cnt FROM food_donations fd JOIN food_categories c ON c.id = fd.category_id
                            WHERE 1=1 $sc GROUP BY c.id, c.name ORDER BY cnt DESC", $sp);
        $status = $this->rows("SELECT fd.status AS name, COUNT(*) AS c FROM food_donations fd WHERE 1=1 $sc GROUP BY fd.status", $sp);

        return [
            'months'   => array_values($months),
            'posted'   => array_values($posted),
            'served'   => array_values($served),
            'category' => ['labels' => array_column($cat, 'name'),    'values' => array_map('intval', array_column($cat, "cnt"))],
            'status'   => ['labels' => array_map(fn($s) => ucfirst($s), array_column($status, 'name')), 'values' => array_map('intval', array_column($status, 'c'))],
        ];
    }
}
