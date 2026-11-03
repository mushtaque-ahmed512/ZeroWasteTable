<?php
class DonationController extends Controller
{
    private Donation $donations;

    public function __construct()
    {
        $this->donations = new Donation();
    }

    public function index(): void
    {
        $u = user();
        $categories = (new Category())->all();

        if ($u['role'] === 'ngo') {
            $list = $this->donations->browse(get('q'), (int)get('category'));
            $org = (new User())->organization($u['org_id']);
            foreach ($list as &$d) {
                $d['distance'] = $org && $org['latitude'] !== null
                    ? distance_km((float)$org['latitude'], (float)$org['longitude'], (float)$d['latitude'], (float)$d['longitude']) : null;
            }
            unset($d);
            if (get('sort') === 'distance') {
                usort($list, fn($a, $b) => ($a['distance'] ?? 9999) <=> ($b['distance'] ?? 9999));
            }
            $this->view('donations/browse', [
                'title' => 'Browse food', 'list' => $list, 'categories' => $categories, 'libs' => ['leaflet'],
                'q' => get('q'), 'category' => (int)get('category'), 'sort' => get('sort'),
                'verified' => $u['verified'],
            ]);
            return;
        }

        $status = get('status');
        $valid = ['available', 'requested', 'approved', 'scheduled', 'collected', 'delivered', 'expired', 'cancelled'];
        $status = in_array($status, $valid, true) ? $status : '';
        $list = $u['role'] === 'admin' ? $this->donations->all($status) : $this->donations->forOrg($u['org_id'], $status);
        $this->view('donations/index', ['title' => $u['role'] === 'admin' ? 'All donations' : 'My donations', 'list' => $list, 'status' => $status, 'statuses' => $valid]);
    }

    public function create(): void
    {
        $org = (new User())->organization(user()['org_id']);
        $this->view('donations/form', [
            'title' => 'New donation', 'libs' => ['leaflet'], 'donation' => null, 'org' => $org,
            'categories' => (new Category())->all(), 'images' => [],
        ]);
    }

    public function store(): void
    {
        [$data, $errors] = $this->validated();
        if ($errors) $this->fail('donations/create', $errors);

        try {
            $paths = save_images($_FILES['images'] ?? null);
        } catch (RuntimeException $e) {
            $this->fail('donations/create', [$e->getMessage()]);
        }

        $id = $this->donations->create(user()['org_id'], $data);
        $this->donations->addImages($id, $paths);

        // let NGOs know something new is available
        $ngoUsers = array_column((new User())->listAll('ngo'), 'id');
        (new Notification())->addMany($ngoUsers, 'info', 'New food available',
            $data['title'] . ' (' . qty((float)$data['quantity'], $data['unit']) . ') was just listed by ' . user()['org_name'] . '.', 'donations/' . $id);

        flash('success', 'Donation listed. Nearby NGOs have been notified.');
        redirect('donations/' . $id);
    }

    public function show(string $id): void
    {
        $d = $this->load((int)$id);
        $u = user();
        $requests = [];
        $mine = null;

        if ($u['role'] === 'ngo') {
            $mine = (new FoodRequest())->existing($d['id'], $u['org_id']);
            $open = in_array($d['status'], ['available', 'requested'], true) && strtotime($d['expiry_at']) > time();
            if (!$mine && !$open) $this->abort(404, 'This donation is no longer available.');
        } else {
            $requests = (new FoodRequest())->forDonation($d['id']);
        }

        $this->view('donations/show', [
            'title' => $d['title'], 'd' => $d, 'images' => $this->donations->images($d['id']), 'requests' => $requests,
            'mine' => $mine, 'libs' => ['leaflet'],
        ]);
    }

    public function edit(string $id): void
    {
        $d = $this->load((int)$id, true);
        $this->view('donations/form', [
            'title' => 'Edit donation', 'libs' => ['leaflet'], 'donation' => $d, 'org' => null,
            'categories' => (new Category())->all(), 'images' => $this->donations->images($d['id']),
        ]);
    }

    public function update(string $id): void
    {
        $d = $this->load((int)$id, true);
        [$data, $errors] = $this->validated();
        if ($errors) $this->fail("donations/{$d['id']}/edit", $errors);

        try {
            $room = UPLOAD_MAX_FILES - $this->donations->imageCount($d['id']);
            $paths = $room > 0 ? array_slice(save_images($_FILES['images'] ?? null), 0, $room) : [];
        } catch (RuntimeException $e) {
            $this->fail("donations/{$d['id']}/edit", [$e->getMessage()]);
        }

        $this->donations->update($d['id'], $data);
        $this->donations->addImages($d['id'], $paths);
        flash('success', 'Donation updated.');
        redirect('donations/' . $d['id']);
    }

    public function deleteImage(string $id, string $imageId): void
    {
        $d = $this->load((int)$id, true);
        $img = $this->donations->findImage($d['id'], (int)$imageId);
        if (!$img) $this->abort(404);
        $this->donations->deleteImage((int)$img['id']);
        $file = UPLOAD_PATH . '/' . $img['image_path'];
        if (str_starts_with($img['image_path'], 'donations/') && is_file($file)) {
            @unlink($file);
        }
        flash('info', 'Photo removed.');
        redirect('donations/' . $d['id'] . '/edit');
    }

    public function cancel(string $id): void
    {
        $d = $this->load((int)$id, true, ['available', 'requested', 'approved', 'scheduled']);
        $this->donations->cancel($d['id']);

        $n = new Notification();
        foreach ((new FoodRequest())->forDonation($d['id']) as $r) {
            $ngo = (new User())->organization((int)$r['ngo_id']);
            $n->add((int)$ngo['user_id'], 'info', 'Donation cancelled', '"' . $d['title'] . '" was cancelled by the donor.', 'requests');
        }
        flash('info', 'Donation cancelled.');
        redirect('donations');
    }

    /* ---------- helpers ---------- */

    /** Loads a donation and enforces access. $mustOwn = donor-only edit access; $allowed = permitted statuses. */
    private function load(int $id, bool $mustOwn = false, array $allowed = ['available']): array
    {
        $d = $this->donations->find($id);
        if (!$d) $this->abort(404);
        $u = user();
        if ($u['role'] === 'donor' && (int)$d['org_id'] !== $u['org_id']) $this->abort(403);
        if ($mustOwn && !in_array($d['status'], $allowed, true)) {
            flash('warning', 'This donation can no longer be changed because requests or pickups are already in progress.');
            redirect('donations/' . $id);
        }
        return $d;
    }

    private function validated(): array
    {
        $err = [];
        $d = [
            'category_id'    => (int)post('category_id'),
            'title'          => post('title'),
            'description'    => post('description'),
            'quantity'       => (float)post('quantity'),
            'unit'           => post('unit'),
            'pickup_address' => post('pickup_address'),
            'latitude'       => post('latitude'),
            'longitude'      => post('longitude'),
        ];
        $ts = strtotime(post('expiry_at'));

        if (!(new Category())->exists($d['category_id']))  $err[] = 'Choose a food category.';
        if (mb_strlen($d['title']) < 3)                    $err[] = 'Give the donation a short title (at least 3 characters).';
        if ($d['quantity'] <= 0)                           $err[] = 'Quantity must be greater than zero.';
        if (!in_array($d['unit'], Donation::UNITS, true))  $err[] = 'Choose a unit.';
        if (!$ts || $ts <= time())                         $err[] = 'Expiry must be a date and time in the future.';
        if ($d['pickup_address'] === '')                   $err[] = 'Pickup address is required.';
        if (!valid_coords($d['latitude'], $d['longitude'])) $err[] = 'Tap the pickup location on the map.';

        $d['expiry_at'] = $ts ? date('Y-m-d H:i:s', $ts) : '';
        return [$d, $err];
    }
}
