<?php $oldRole = old('role', in_array(get('role'), ['donor', 'ngo', 'volunteer'], true) ? get('role') : 'donor'); ?>
<div class="container">
    <div class="auth-wrap wide">
        <div class="card"><div class="card-body p-3 p-md-4">
            <h1 class="h4 fw-bold mb-1">Create your account</h1>
            <p class="text-body-secondary mb-4">Pick how you want to take part. You can't change this later, so choose carefully.</p>

            <form method="post" action="<?= e(url('register')) ?>">
                <?= csrf_field() ?>

                <div class="row g-2 role-pick mb-4">
                    <?php foreach ([
                        'donor'     => ['🏪', 'Food donor', 'Restaurant, bakery, hotel, store'],
                        'ngo'       => ['🤝', 'NGO / charity', 'Request and distribute food'],
                        'volunteer' => ['🚴', 'Volunteer', 'Help move food around'],
                    ] as $key => [$icon, $label, $sub]): ?>
                        <div class="col-12 col-md-4">
                            <input type="radio" class="btn-check" name="role" id="role-<?= $key ?>" value="<?= $key ?>" data-role-toggle <?= $oldRole === $key ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary w-100 py-3" for="role-<?= $key ?>">
                                <span class="em"><?= $icon ?></span><strong><?= $label ?></strong><br><small class="opacity-75"><?= $sub ?></small>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h2 class="h6 text-uppercase text-body-secondary mb-3">Your details</h2>
                <div class="row g-3 mb-4">
                    <div class="col-md-6"><label class="form-label" for="name">Full name</label>
                        <input class="form-control" id="name" name="name" value="<?= e(old('name')) ?>" required autocomplete="name"></div>
                    <div class="col-md-6"><label class="form-label" for="phone">Phone</label>
                        <input class="form-control" id="phone" name="phone" type="tel" value="<?= e(old('phone')) ?>" required autocomplete="tel"></div>
                    <div class="col-12"><label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= e(old('email')) ?>" required autocomplete="email"></div>
                    <div class="col-md-6"><label class="form-label" for="password">Password</label>
                        <input class="form-control" id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
                        <div class="form-text">At least 8 characters.</div></div>
                    <div class="col-md-6"><label class="form-label" for="password_confirm">Confirm password</label>
                        <input class="form-control" id="password_confirm" name="password_confirm" type="password" minlength="8" required autocomplete="new-password"></div>
                </div>

                <div data-role-section="donor ngo">
                    <h2 class="h6 text-uppercase text-body-secondary mb-3">Organization</h2>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6"><label class="form-label" for="org_name" id="org-name-label">Business name</label>
                            <input class="form-control" id="org_name" name="org_name" value="<?= e(old('org_name')) ?>" required></div>
                        <div class="col-md-6"><label class="form-label" for="org_type">Type</label>
                            <select class="form-select" id="org_type" name="org_type" data-selected="<?= e(old('org_type')) ?>" required><option value="">Select type</option></select></div>
                        <div class="col-md-6"><label class="form-label" for="registration_no">Registration / tax no. <span class="text-body-secondary">(optional)</span></label>
                            <input class="form-control" id="registration_no" name="registration_no" value="<?= e(old('registration_no')) ?>"></div>
                        <div class="col-md-6"><label class="form-label" for="city">City</label>
                            <input class="form-control" id="city" name="city" value="<?= e(old('city')) ?>"></div>
                        <div class="col-12"><label class="form-label" for="address">Address</label>
                            <input class="form-control" id="address" name="address" value="<?= e(old('address')) ?>" required></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label mb-0">Pin your location on the map</label>
                        <button type="button" id="locate-me" class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt"></i> Use my location</button>
                    </div>
                    <div id="reg-map" class="map mb-2" data-map-picker data-lat="#latitude" data-lng="#longitude" data-locate="#locate-me"></div>
                    <input type="hidden" id="latitude" name="latitude" value="<?= e(old('latitude')) ?>">
                    <input type="hidden" id="longitude" name="longitude" value="<?= e(old('longitude')) ?>">
                    <div class="form-text mb-4">Tap the map to drop a pin. It is used to show distances between donors and NGOs.</div>
                </div>

                <div data-role-section="volunteer" class="mb-4">
                    <h2 class="h6 text-uppercase text-body-secondary mb-3">Volunteer profile</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" for="vehicle">How will you travel?</label>
                            <select class="form-select" id="vehicle" name="vehicle">
                                <?php foreach (['none' => 'On foot / public transport', 'bike' => 'Bike or motorbike', 'car' => 'Car', 'van' => 'Van'] as $v => $l): ?>
                                    <option value="<?= $v ?>" <?= old('vehicle') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-md-6"><label class="form-label" for="service_area">Area you can cover</label>
                            <input class="form-control" id="service_area" name="service_area" value="<?= e(old('service_area')) ?>" placeholder="e.g. North side, city centre"></div>
                    </div>
                </div>

                <button class="btn btn-primary w-100 py-2">Create account</button>
            </form>
            <p class="text-center small mt-3 mb-0">Already registered? <a href="<?= e(url('login')) ?>">Log in</a></p>
        </div></div>
    </div>
</div>
