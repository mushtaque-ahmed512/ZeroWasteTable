<?php
class ProfileController extends Controller
{
    public function edit(): void
    {
        $u = user();
        $users = new User();
        $this->view('profile/edit', [
            'title' => 'My profile', 'libs' => ['leaflet'],
            'account' => $users->find($u['id']),
            'org'     => $u['org_id'] ? $users->organization($u['org_id']) : null,
            'vol'     => $u['role'] === 'volunteer' ? $users->volunteer($u['id']) : null,
        ]);
    }

    public function update(): void
    {
        $u = user();
        $users = new User();
        $err = [];

        $name = post('name');
        $phone = post('phone');
        if (mb_strlen($name) < 2)                               $err[] = 'Please enter your full name.';
        if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $phone))      $err[] = 'Enter a valid phone number.';

        $org = $vol = null;
        if ($u['org_id']) {
            $types = $u['role'] === 'donor' ? ['restaurant', 'bakery', 'hotel', 'grocery'] : ['ngo', 'charity'];
            $org = [
                'org_name' => post('org_name'), 'org_type' => post('org_type'), 'registration_no' => post('registration_no'),
                'address' => post('address'), 'city' => post('city'), 'latitude' => post('latitude'), 'longitude' => post('longitude'),
            ];
            if ($org['org_name'] === '')                          $err[] = 'Organization name is required.';
            if (!in_array($org['org_type'], $types, true))        $err[] = 'Select a valid organization type.';
            if ($org['address'] === '')                           $err[] = 'Address is required.';
            if (!valid_coords($org['latitude'], $org['longitude'])) $err[] = 'Tap your location on the map.';
        } elseif ($u['role'] === 'volunteer') {
            $vol = ['vehicle' => post('vehicle', 'none'), 'service_area' => post('service_area'), 'availability' => post('availability', 'available')];
            if (!in_array($vol['vehicle'], ['none', 'bike', 'car', 'van'], true))              $err[] = 'Select a valid vehicle option.';
            if (!in_array($vol['availability'], ['available', 'busy', 'offline'], true))       $err[] = 'Select a valid availability.';
        }

        if ($err) $this->fail('profile', $err);

        $users->updateProfile($u['id'], $name, $phone);
        if ($org) $users->updateOrganization($u['id'], $org);
        if ($vol) $users->updateVolunteer($u['id'], $vol);
        flash('success', 'Profile updated.');
        redirect('profile');
    }

    public function password(): void
    {
        $users = new User();
        $row = $users->find(user()['id']);
        $new = $_POST['new_password'] ?? '';

        if (!password_verify($_POST['current_password'] ?? '', $row['password_hash'])) $this->fail('profile', ['Your current password is incorrect.']);
        if (strlen($new) < 8)                                                          $this->fail('profile', ['New password must be at least 8 characters.']);
        if ($new !== ($_POST['new_password_confirm'] ?? ''))                           $this->fail('profile', ['New passwords do not match.']);

        $users->setPassword($row['id'], $new);
        session_regenerate_id(true);
        flash('success', 'Password changed.');
        redirect('profile');
    }
}
