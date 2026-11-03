<?php
$u = user();
$v = fn(string $k, $fallback = '') => old($k, $fallback);
echo page_header('My profile', role_label($u['role']) . ' · ' . $account['email']);
$types = $u['role'] === 'donor'
    ? ['restaurant' => 'Restaurant', 'bakery' => 'Bakery', 'hotel' => 'Hotel', 'grocery' => 'Grocery store']
    : ['ngo' => 'NGO', 'charity' => 'Charity'];
?>
<div class="row g-3">
    <div class="col-lg-8">
        <form method="post" action="<?= e(url('profile')) ?>">
            <?= csrf_field() ?>
            <div class="card mb-3"><div class="card-header">Your details</div><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label" for="name">Full name</label>
                        <input class="form-control" id="name" name="name" value="<?= e($v('name', $account['name'])) ?>" required></div>
                    <div class="col-md-6"><label class="form-label" for="phone">Phone</label>
                        <input class="form-control" id="phone" name="phone" type="tel" value="<?= e($v('phone', $account['phone'])) ?>" required></div>
                    <div class="col-12"><label class="form-label">Email</label>
                        <input class="form-control" value="<?= e($account['email']) ?>" disabled>
                        <div class="form-text">Your email is your login and can't be changed here.</div></div>
                </div>
            </div></div>

            <?php if ($org): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between"><span>Organization</span>
                    <?= $org['verified'] ? '<span class="badge text-bg-success"><i class="bi bi-patch-check-fill"></i> Verified</span>' : '<span class="badge text-bg-warning">Not verified</span>' ?></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="org_name">Name</label>
                            <input class="form-control" id="org_name" name="org_name" value="<?= e($v('org_name', $org['org_name'])) ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="org_type">Type</label>
                            <select class="form-select" id="org_type" name="org_type" required>
                                <?php foreach ($types as $k => $l): ?><option value="<?= $k ?>" <?= $v('org_type', $org['org_type']) === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-6"><label class="form-label" for="registration_no">Registration / tax no.</label>
                            <input class="form-control" id="registration_no" name="registration_no" value="<?= e($v('registration_no', $org['registration_no'])) ?>"></div>
                        <div class="col-md-6"><label class="form-label" for="city">City</label>
                            <input class="form-control" id="city" name="city" value="<?= e($v('city', $org['city'])) ?>"></div>
                        <div class="col-12"><label class="form-label" for="address">Address</label>
                            <input class="form-control" id="address" name="address" value="<?= e($v('address', $org['address'])) ?>" required></div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-2"><span class="form-label mb-0">Location</span>
                                <button type="button" id="locate-me" class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt"></i> Use my location</button></div>
                            <div class="map" data-map-picker data-lat="#latitude" data-lng="#longitude" data-locate="#locate-me"></div>
                            <input type="hidden" id="latitude" name="latitude" value="<?= e($v('latitude', $org['latitude'])) ?>">
                            <input type="hidden" id="longitude" name="longitude" value="<?= e($v('longitude', $org['longitude'])) ?>">
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif ($vol): ?>
            <div class="card mb-3"><div class="card-header">Volunteer profile</div><div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label" for="vehicle">Vehicle</label>
                        <select class="form-select" id="vehicle" name="vehicle">
                            <?php foreach (['none' => 'On foot / public transport', 'bike' => 'Bike or motorbike', 'car' => 'Car', 'van' => 'Van'] as $k => $l): ?>
                                <option value="<?= $k ?>" <?= $v('vehicle', $vol['vehicle']) === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-4"><label class="form-label" for="availability">Availability</label>
                        <select class="form-select" id="availability" name="availability">
                            <?php foreach (['available' => 'Available', 'busy' => 'Busy', 'offline' => 'Offline'] as $k => $l): ?>
                                <option value="<?= $k ?>" <?= $v('availability', $vol['availability']) === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-4"><label class="form-label" for="service_area">Area you cover</label>
                        <input class="form-control" id="service_area" name="service_area" value="<?= e($v('service_area', $vol['service_area'])) ?>"></div>
                </div>
            </div></div>
            <?php endif; ?>

            <button class="btn btn-primary">Save changes</button>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card"><div class="card-header">Change password</div><div class="card-body">
            <form method="post" action="<?= e(url('profile/password')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3"><label class="form-label" for="current_password">Current password</label>
                    <input class="form-control" type="password" id="current_password" name="current_password" required autocomplete="current-password"></div>
                <div class="mb-3"><label class="form-label" for="new_password">New password</label>
                    <input class="form-control" type="password" id="new_password" name="new_password" minlength="8" required autocomplete="new-password"></div>
                <div class="mb-3"><label class="form-label" for="new_password_confirm">Confirm new password</label>
                    <input class="form-control" type="password" id="new_password_confirm" name="new_password_confirm" minlength="8" required autocomplete="new-password"></div>
                <button class="btn btn-outline-primary w-100">Update password</button>
            </form>
        </div></div>
    </div>
</div>
