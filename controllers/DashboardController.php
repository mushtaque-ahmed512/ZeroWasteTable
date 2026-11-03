<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $u = user();
        $analytics = new Analytics();
        $stats = $analytics->stats($u);
        $donations = new Donation();
        $deliveries = new Delivery();

        // A short "what needs attention" list per role: title, meta, badge status, link
        $panelTitle = 'Recent activity';
        $panel = [];

        switch ($u['role']) {
            case 'donor':
                $panelTitle = 'Your latest listings';
                foreach (array_slice($donations->forOrg($u['org_id']), 0, 6) as $d) {
                    $panel[] = ['title' => $d['title'], 'meta' => qty((float)$d['quantity'], $d['unit']) . ' · expires ' . fmt_dt($d['expiry_at']),
                                'status' => $d['status'], 'href' => 'donations/' . $d['id'], 'flag' => (int)$d['pending_requests'] ? $d['pending_requests'] . ' pending' : ''];
                }
                break;
            case 'ngo':
                $panelTitle = 'Food available now';
                foreach (array_slice($donations->browse(), 0, 6) as $d) {
                    $panel[] = ['title' => $d['title'], 'meta' => $d['org_name'] . ' · ' . qty((float)$d['quantity'], $d['unit']),
                                'status' => $d['status'], 'href' => 'donations/' . $d['id'], 'flag' => time_left($d['expiry_at'])[0]];
                }
                break;
            case 'volunteer':
                $panelTitle = 'Open pickup tasks';
                foreach (array_slice($deliveries->openTasks(), 0, 6) as $t) {
                    $panel[] = ['title' => $t['title'], 'meta' => $t['donor_name'] . ' → ' . $t['ngo_name'] . ' · pickup ' . fmt_dt($t['pickup_at']),
                                'status' => $t['status'], 'href' => 'deliveries/' . $t['id'], 'flag' => ''];
                }
                break;
            case 'admin':
                $panelTitle = 'Latest listings across the platform';
                foreach (array_slice($donations->all(), 0, 6) as $d) {
                    $panel[] = ['title' => $d['title'], 'meta' => $d['org_name'] . ' · ' . qty((float)$d['quantity'], $d['unit']),
                                'status' => $d['status'], 'href' => 'donations/' . $d['id'], 'flag' => ''];
                }
                break;
        }

        $this->view('dashboard/index', [
            'title' => 'Dashboard', 'stats' => $stats, 'panelTitle' => $panelTitle, 'panel' => $panel, 'libs' => ['chart'],
        ]);
    }

    /** JSON feed for the Chart.js widgets. */
    public function chartData(): void
    {
        $this->json((new Analytics())->charts(user()));
    }
}
