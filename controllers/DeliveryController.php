<?php
class DeliveryController extends Controller
{
    private Delivery $deliveries;
    private Notification $notify;

    public function __construct()
    {
        $this->deliveries = new Delivery();
        $this->notify = new Notification();
    }

    public function index(): void
    {
        $u = user();
        $this->view('deliveries/index', [
            'title' => 'Pickups & deliveries',
            'rows'  => $this->deliveries->listFor($u['role'], $u['org_id'], $u['volunteer_id']),
            'open'  => $u['role'] === 'volunteer' ? $this->deliveries->openTasks() : [],
        ]);
    }

    public function show(string $id): void
    {
        $dl = $this->access((int)$id);
        $u = user();
        $reviews = new Review();
        $this->view('deliveries/show', [
            'title' => 'Delivery #' . $dl['id'], 'dl' => $dl, 'libs' => ['leaflet', 'qrcode'],
            'canSeeQr'  => $dl['status'] === 'scheduled' && ($u['role'] === 'ngo' || ($u['role'] === 'volunteer' && (int)$dl['volunteer_id'] === $u['volunteer_id'])),
            'canDeliver'=> $dl['status'] === 'picked_up' && $this->mayDeliver($dl),
            'canReview' => $dl['status'] === 'delivered' && in_array($u['role'], ['donor', 'ngo'], true) && !$reviews->exists($dl['id'], $u['id']),
            'reviews'   => $reviews->forDelivery($dl['id']),
            'canAssign' => $dl['status'] === 'scheduled' && in_array($u['role'], ['ngo', 'donor', 'admin'], true),
            'volunteers'=> $dl['status'] === 'scheduled' && in_array($u['role'], ['ngo', 'donor', 'admin'], true) ? (new User())->volunteerList() : [],
        ]);
    }

    public function claim(string $id): void
    {
        $dl = $this->access((int)$id, true);
        if ($this->deliveries->claim($dl['id'], user()['volunteer_id'])) {
            $msg = user()['name'] . ' will handle the pickup of "' . $dl['title'] . '".';
            $this->notify->add((int)$dl['donor_user_id'], 'pickup', 'Volunteer assigned', $msg, 'deliveries/' . $dl['id']);
            $this->notify->add((int)$dl['ngo_user_id'], 'pickup', 'Volunteer assigned', $msg, 'deliveries/' . $dl['id']);
            flash('success', 'Task claimed. Show the QR code to the donor at pickup.');
        } else {
            flash('warning', 'Someone else already claimed this task.');
        }
        redirect('deliveries/' . $dl['id']);
    }

    /** NGO / donor / admin picks the volunteer who will collect, or leaves the task open for anyone. */
    public function assign(string $id): void
    {
        $dl = $this->access((int)$id);
        if ($dl['status'] !== 'scheduled') {
            flash('warning', 'The volunteer can only be changed before the food is collected.');
            redirect('deliveries/' . $dl['id']);
        }
        $vid = (int)post('volunteer_id');
        $users = new User();
        $link = 'deliveries/' . $dl['id'];

        if ($vid === 0) {
            $this->deliveries->setVolunteer($dl['id'], null);
            if ($dl['volunteer_user_id']) {
                $this->notify->add((int)$dl['volunteer_user_id'], 'pickup', 'Pickup unassigned', 'You were removed from the pickup of "' . $dl['title'] . '".', $link);
            }
            flash('info', 'No volunteer assigned. Any volunteer can claim this pickup, or you can collect it yourselves with the QR code.');
            redirect($link);
        }

        $vol = $users->volunteerById($vid);
        if (!$vol) {
            $this->fail($link, ['That volunteer is not available.']);
        }
        $this->deliveries->setVolunteer($dl['id'], $vid);
        $this->notify->add((int)$vol['user_id'], 'pickup', 'You were assigned a pickup',
            'Collect "' . $dl['title'] . '" from ' . $dl['donor_name'] . ' on ' . fmt_dt($dl['pickup_at']) . ' and take it to ' . $dl['ngo_name'] . '.', $link);
        if ($dl['volunteer_user_id'] && (int)$dl['volunteer_user_id'] !== (int)$vol['user_id']) {
            $this->notify->add((int)$dl['volunteer_user_id'], 'pickup', 'Pickup reassigned', 'The pickup of "' . $dl['title'] . '" was given to another volunteer.', $link);
        }
        foreach ([$dl['donor_user_id'], $dl['ngo_user_id']] as $uid) {
            if ((int)$uid !== user()['id']) {
                $this->notify->add((int)$uid, 'pickup', 'Volunteer assigned', $vol['name'] . ' will handle the pickup of "' . $dl['title'] . '".', $link);
            }
        }
        flash('success', $vol['name'] . ' has been assigned and notified.');
        redirect($link);
    }

    public function verifyForm(): void
    {
        $this->view('deliveries/verify', ['title' => 'Verify collection', 'libs' => ['scanner'], 'token' => get('token')]);
    }

    /** Donor scans the collector's QR code: confirms the right person is taking the right food. */
    public function verify(): void
    {
        $token = strtolower(post('token'));
        $dl = preg_match('/^[a-f0-9]{32}$/', $token) ? $this->deliveries->findByToken($token) : null;

        if (!$dl) $this->fail('verify', ['That code is not recognised. Ask the collector to show the QR code again.']);
        if (has_role('donor') && (int)$dl['donor_org_id'] !== user()['org_id']) {
            $this->fail('verify', ['That code belongs to another donor\'s pickup.']);
        }
        if ($dl['status'] === 'picked_up' || $dl['status'] === 'delivered') {
            $this->fail('verify', ['This pickup was already verified on ' . fmt_dt($dl['picked_up_at']) . '.']);
        }
        if ($dl['status'] !== 'scheduled') $this->fail('verify', ['This pickup was cancelled.']);
        if (strtotime($dl['expiry_at']) <= time()) $this->fail('verify', ['This food has expired and cannot be handed over.']);

        $this->deliveries->markPickedUp($dl);
        $msg = '"' . $dl['title'] . '" was collected from ' . $dl['donor_name'] . '.';
        $this->notify->add((int)$dl['ngo_user_id'], 'pickup', 'Food collected', $msg, 'deliveries/' . $dl['id']);
        if ($dl['volunteer_user_id']) $this->notify->add((int)$dl['volunteer_user_id'], 'pickup', 'Pickup verified', $msg, 'deliveries/' . $dl['id']);

        flash('success', 'Collection verified for "' . $dl['title'] . '" (' . $dl['ngo_name'] . ').');
        redirect('deliveries/' . $dl['id']);
    }

    /** Distribution tracking: how many people were served. */
    public function deliver(string $id): void
    {
        $dl = $this->access((int)$id);
        if ($dl['status'] !== 'picked_up' || !$this->mayDeliver($dl)) {
            flash('warning', 'This delivery cannot be completed right now.');
            redirect('deliveries/' . $dl['id']);
        }
        $people = (int)post('people_served');
        if ($people < 1 || $people > 100000) {
            $this->fail('deliveries/' . $dl['id'], ['Enter how many people were served (at least 1).']);
        }
        $this->deliveries->markDelivered($dl, $people, post('distribution_notes'));
        $this->notify->add((int)$dl['donor_user_id'], 'delivery', 'Your donation reached people',
            '"' . $dl['title'] . '" was distributed to ' . $people . ' people by ' . $dl['ngo_name'] . '. Thank you!', 'deliveries/' . $dl['id']);
        flash('success', 'Distribution recorded. Thank you for reducing food waste.');
        redirect('deliveries/' . $dl['id']);
    }

    public function review(string $id): void
    {
        $dl = $this->access((int)$id);
        $u = user();
        $reviews = new Review();
        $rating = (int)post('rating');
        if ($dl['status'] !== 'delivered' || $reviews->exists($dl['id'], $u['id'])) {
            flash('warning', 'You cannot review this delivery.');
            redirect('deliveries/' . $dl['id']);
        }
        if ($rating < 1 || $rating > 5) $this->fail('deliveries/' . $dl['id'], ['Pick a rating from 1 to 5.']);
        $reviewee = $u['role'] === 'donor' ? $dl['ngo_user_id'] : $dl['donor_user_id'];
        $reviews->create($dl['id'], $u['id'], (int)$reviewee, $rating, post('comment'));
        flash('success', 'Thanks for your feedback.');
        redirect('deliveries/' . $dl['id']);
    }

    /* ---------- helpers ---------- */

    /** Loads the delivery and checks the logged-in user is a party to it. */
    private function access(int $id, bool $claimMode = false): array
    {
        $dl = $this->deliveries->find($id);
        if (!$dl) $this->abort(404);
        $u = user();
        $ok = match ($u['role']) {
            'admin'     => true,
            'donor'     => (int)$dl['donor_org_id'] === $u['org_id'],
            'ngo'       => (int)$dl['ngo_id'] === $u['org_id'],
            'volunteer' => $claimMode || (int)$dl['volunteer_id'] === $u['volunteer_id']
                           || ($dl['volunteer_id'] === null && $dl['status'] === 'scheduled'),
            default     => false,
        };
        if (!$ok) $this->abort(403);
        return $dl;
    }

    private function mayDeliver(array $dl): bool
    {
        $u = user();
        return ($u['role'] === 'ngo' && (int)$dl['ngo_id'] === $u['org_id'])
            || ($u['role'] === 'volunteer' && (int)$dl['volunteer_id'] === $u['volunteer_id']);
    }
}
