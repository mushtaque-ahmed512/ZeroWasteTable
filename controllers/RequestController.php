<?php
class RequestController extends Controller
{
    private FoodRequest $requests;
    private Notification $notify;

    public function __construct()
    {
        $this->requests = new FoodRequest();
        $this->notify = new Notification();
    }

    public function index(): void
    {
        $u = user();
        $this->view('requests/index', [
            'title' => $u['role'] === 'ngo' ? 'My requests' : ($u['role'] === 'donor' ? 'Incoming requests' : 'All requests'),
            'rows'  => $this->requests->listFor($u['role'], $u['org_id']),
        ]);
    }

    /** NGO asks for a donation. */
    public function store(string $id): void
    {
        $u = user();
        $donation = (new Donation())->find((int)$id);
        if (!$donation) $this->abort(404);

        if (NGO_REQUIRES_VERIFICATION && !$u['verified']) {
            flash('warning', 'Your organization must be verified by an administrator before you can request food.');
            redirect('donations/' . $id);
        }
        if (!in_array($donation['status'], ['available', 'requested'], true) || strtotime($donation['expiry_at']) <= time()) {
            flash('warning', 'This donation is no longer available.');
            redirect('donations');
        }

        $qty = (float)post('quantity_needed');
        if ($qty <= 0 || $qty > (float)$donation['quantity']) {
            $this->fail('donations/' . $id, ['Enter a quantity between 0 and ' . qty((float)$donation['quantity'], $donation['unit']) . '.']);
        }

        $existing = $this->requests->existing((int)$id, $u['org_id']);
        if ($existing && $existing['status'] !== 'cancelled') {
            flash('info', 'You have already requested this donation.');
            redirect('donations/' . $id);
        }

        if ($existing) {
            $this->requests->reopen((int)$existing['id'], (int)$id, $qty, post('message'));
        } else {
            $this->requests->create((int)$id, $u['org_id'], $qty, post('message'));
        }

        $this->notify->add((int)$donation['donor_user_id'], 'request', 'New food request',
            $u['org_name'] . ' requested ' . qty($qty, $donation['unit']) . ' of "' . $donation['title'] . '".', 'donations/' . $id);
        flash('success', 'Request sent. The donor will review it shortly.');
        redirect('requests');
    }

    public function approve(string $id): void
    {
        $req = $this->ownRequest((int)$id);
        if ($req['status'] !== 'pending' || !in_array($req['donation_status'], ['available', 'requested'], true)) {
            flash('warning', 'This request can no longer be approved.');
            redirect('requests');
        }
        if (strtotime($req['expiry_at']) <= time()) {
            flash('warning', 'This donation has already expired.');
            redirect('requests');
        }
        $this->requests->approve($req);
        $this->notify->add((int)$req['ngo_user_id'], 'approval', 'Request approved',
            $req['donor_name'] . ' approved your request for "' . $req['title'] . '". A pickup time will be set shortly.', 'requests');
        flash('success', 'Request approved. Now set a pickup time.');
        redirect('requests/' . $id . '/schedule');
    }

    public function reject(string $id): void
    {
        $req = $this->ownRequest((int)$id);
        if ($req['status'] !== 'pending') redirect('requests');
        $this->requests->close($req, 'rejected');
        $this->notify->add((int)$req['ngo_user_id'], 'info', 'Request declined',
            $req['donor_name'] . ' could not fulfil your request for "' . $req['title'] . '".', 'requests');
        flash('info', 'Request declined.');
        redirect('requests');
    }

    public function cancel(string $id): void
    {
        $req = $this->requests->find((int)$id);
        if (!$req) $this->abort(404);
        if ((int)$req['ngo_id'] !== user()['org_id']) $this->abort(403);
        if ($req['status'] !== 'pending') {
            flash('warning', 'Only pending requests can be cancelled.');
            redirect('requests');
        }
        $this->requests->close($req, 'cancelled');
        flash('info', 'Request cancelled.');
        redirect('requests');
    }

    public function scheduleForm(string $id): void
    {
        $req = $this->schedulable((int)$id);
        $this->view('requests/schedule', ['title' => 'Schedule pickup', 'req' => $req]);
    }

    public function schedule(string $id): void
    {
        $req = $this->schedulable((int)$id);
        $ts = strtotime(post('pickup_at'));
        $err = [];
        if (!$ts || $ts <= time())                          $err[] = 'Pickup must be in the future.';
        elseif ($ts >= strtotime($req['expiry_at']))        $err[] = 'Pickup must be before the food expires (' . fmt_dt($req['expiry_at']) . ').';
        if ($err) $this->fail("requests/$id/schedule", $err);

        $deliveryId = (new Delivery())->schedule($req, date('Y-m-d H:i:s', $ts), post('notes'), user()['id']);

        $link = 'deliveries/' . $deliveryId;
        $when = fmt_dt(date('Y-m-d H:i:s', $ts));
        $this->notify->add((int)$req['ngo_user_id'], 'pickup', 'Pickup scheduled',
            '"' . $req['title'] . '" can be collected on ' . $when . '. Open it to assign a volunteer, or collect it yourselves with the QR code.', $link);
        $this->notify->addMany((new User())->volunteerUserIds(), 'pickup', 'New pickup task',
            $req['donor_name'] . ' → ' . $req['ngo_name'] . ' on ' . $when . '. Claim it if you can help.', $link);

        flash('success', 'Pickup scheduled. Volunteers were notified and the NGO can also assign one.');
        redirect($link);
    }

    /* ---------- helpers ---------- */
    private function ownRequest(int $id): array
    {
        $req = $this->requests->find($id);
        if (!$req) $this->abort(404);
        if ((int)$req['donor_org_id'] !== user()['org_id']) $this->abort(403);
        return $req;
    }

    private function schedulable(int $id): array
    {
        $req = $this->ownRequest($id);
        if ($req['status'] !== 'approved' || $req['donation_status'] !== 'approved') {
            flash('warning', $req['schedule_id'] ? 'A pickup is already scheduled for this request.' : 'Approve the request first.');
            redirect('requests');
        }
        return $req;
    }
}
