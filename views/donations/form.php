<?php
$editing = $donation !== null;
$val = function (string $key, $fallback = '') use ($donation, $org) {
    $base = $donation[$key] ?? $fallback;
    return old($key, $base);
};
$lat = $val('latitude', $org['latitude'] ?? '');
$lng = $val('longitude', $org['longitude'] ?? '');
$addr = $val('pickup_address', $org['address'] ?? '');
$expiry = old('expiry_at', $editing ? date('Y-m-d\TH:i', strtotime($donation['expiry_at'])) : '');
echo page_header($editing ? 'Edit donation' : 'New donation', 'Describe the food clearly so NGOs can decide quickly.');
?>
<form method="post" enctype="multipart/form-data" action="<?= e(url($editing ? 'donations/' . $donation['id'] . '/update' : 'donations/store')) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card mb-3"><div class="card-header">Food details</div><div class="card-body">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label" for="title">Title</label>
                        <input class="form-control" id="title" name="title" maxlength="150" value="<?= e($val('title')) ?>" placeholder="e.g. Chicken biryani trays" required></div>
                    <div class="col-12"><label class="form-label">What is it?</label>
                        <div class="cat-tiles">
                            <?php foreach ($categories as $c): ?>
                                <input type="radio" name="category_id" id="cat<?= $c['id'] ?>" value="<?= $c['id'] ?>" <?= (int)$val('category_id') === (int)$c['id'] ? 'checked' : '' ?> required>
                                <label for="cat<?= $c['id'] ?>"><span class="em"><?= category_emoji($c['name']) ?></span><?= e($c['name']) ?></label>
                            <?php endforeach; ?>
                        </div></div>
                    <div class="col-6 col-md-6"><label class="form-label" for="quantity">Quantity</label>
                        <input class="form-control" id="quantity" name="quantity" type="number" min="0.01" step="0.01" value="<?= e($val('quantity')) ?>" required></div>
                    <div class="col-6 col-md-6"><label class="form-label" for="unit">Unit</label>
                        <select class="form-select" id="unit" name="unit" required>
                            <?php foreach (Donation::UNITS as $un): ?><option value="<?= $un ?>" <?= $val('unit', 'portions') === $un ? 'selected' : '' ?>><?= $un ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-12"><label class="form-label" for="expiry_at">Safe to collect until</label>
                        <input class="form-control" id="expiry_at" name="expiry_at" type="datetime-local" data-future value="<?= e($expiry) ?>" required>
                        <div class="form-text">You and the NGO get an alert <?= EXPIRY_ALERT_HOURS ?> hours before this time, and the listing closes automatically after it.</div></div>
                    <div class="col-12"><label class="form-label" for="description">Notes <span class="text-body-secondary">(storage, allergens, packaging)</span></label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= e($val('description')) ?></textarea></div>
                </div>
            </div></div>

            <div class="card"><div class="card-header">Photos</div><div class="card-body">
                <?php if ($images): ?>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <?php foreach ($images as $img): ?>
                            <div class="text-center">
                                <img class="thumb d-block mb-1" src="<?= e(upload_url($img['image_path'])) ?>" alt="">
                                <button type="submit" class="btn btn-sm btn-outline-danger" formnovalidate
                                        formaction="<?= e(url('donations/' . $donation['id'] . '/images/' . $img['id'] . '/delete')) ?>"
                                        onclick="return confirm('Remove this photo?')"><i class="bi bi-trash"></i></button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (count($images) < UPLOAD_MAX_FILES): ?>
                    <input class="form-control" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
                    <div class="form-text">Up to <?= UPLOAD_MAX_FILES ?> photos total, JPG/PNG/WebP, max <?= UPLOAD_MAX_BYTES / 1048576 ?> MB each.</div>
                <?php else: ?>
                    <div class="text-body-secondary small">Maximum number of photos reached.</div>
                <?php endif; ?>
            </div></div>
        </div>

        <div class="col-lg-5">
            <div class="card"><div class="card-header">Pickup location</div><div class="card-body">
                <label class="form-label" for="pickup_address">Address</label>
                <input class="form-control mb-3" id="pickup_address" name="pickup_address" value="<?= e($addr) ?>" required>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="form-label mb-0">Pin on map</span>
                    <button type="button" id="locate-me" class="btn btn-sm btn-outline-primary"><i class="bi bi-geo-alt"></i> Use my location</button>
                </div>
                <div class="map" data-map-picker data-lat="#latitude" data-lng="#longitude" data-locate="#locate-me"></div>
                <input type="hidden" id="latitude" name="latitude" value="<?= e($lat) ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?= e($lng) ?>">
                <div class="form-text">Tap the map to move the pin to the exact collection point.</div>
            </div></div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary"><?= $editing ? 'Save changes' : 'Publish donation' ?></button>
        <a class="btn btn-outline-secondary" href="<?= e(url($editing ? 'donations/' . $donation['id'] : 'donations')) ?>">Cancel</a>
    </div>
</form>
