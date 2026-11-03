<?php
$u = user();
echo page_header($dl['title'], $dl['donor_name'] . ' → ' . $dl['ngo_name'], status_badge($dl['status']));

$steps = [
    ['Pickup scheduled', 'Set by ' . $dl['donor_name'] . ' for ' . fmt_dt($dl['pickup_at']), true],
    ['Volunteer assigned', $dl['volunteer_name'] ?: 'Optional: the NGO can also collect it', (bool)$dl['volunteer_id']],
    ['Collected & verified', $dl['picked_up_at'] ? fmt_dt($dl['picked_up_at']) : 'QR scanned by the donor at pickup', in_array($dl['status'], ['picked_up', 'delivered'], true)],
    ['Distributed', $dl['status'] === 'delivered' ? $dl['people_served'] . ' people served · ' . fmt_dt($dl['delivered_at']) : 'NGO records people served', $dl['status'] === 'delivered'],
];
$markers = [['lat' => (float)$dl['donor_lat'], 'lng' => (float)$dl['donor_lng'], 'html' => '<strong>Pickup</strong><br>' . e($dl['donor_name']) . '<br>' . e($dl['pickup_address'])]];
if ($dl['ngo_lat'] !== null) $markers[] = ['lat' => (float)$dl['ngo_lat'], 'lng' => (float)$dl['ngo_lng'], 'html' => '<strong>Drop-off</strong><br>' . e($dl['ngo_name']) . '<br>' . e($dl['ngo_address'])];
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3"><div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-4">Quantity</dt><dd class="col-sm-8"><?= e(qty((float)$dl['quantity_needed'], $dl['unit'])) ?></dd>
                <dt class="col-sm-4">Pickup time</dt><dd class="col-sm-8"><?= e(fmt_dt($dl['pickup_at'])) ?></dd>
                <dt class="col-sm-4">Food expires</dt><dd class="col-sm-8"><?= e(fmt_dt($dl['expiry_at'])) ?> <?php if (in_array($dl['status'], ['scheduled', 'picked_up'], true)): ?><?= expiry_badge($dl['expiry_at']) ?><?php endif; ?></dd>
                <dt class="col-sm-4">Pick up from</dt><dd class="col-sm-8"><?= e($dl['pickup_address']) ?></dd>
                <dt class="col-sm-4">Deliver to</dt><dd class="col-sm-8"><?= e($dl['ngo_address']) ?></dd>
                <?php if ($dl['volunteer_name']): ?><dt class="col-sm-4">Volunteer</dt><dd class="col-sm-8"><?= e($dl['volunteer_name']) ?> · <?= e($dl['volunteer_phone']) ?></dd><?php endif; ?>
                <?php if ($dl['pickup_notes']): ?><dt class="col-sm-4">Instructions</dt><dd class="col-sm-8"><?= nl2br(e($dl['pickup_notes'])) ?></dd><?php endif; ?>
                <?php if ($dl['distribution_notes']): ?><dt class="col-sm-4">Distribution notes</dt><dd class="col-sm-8"><?= nl2br(e($dl['distribution_notes'])) ?></dd><?php endif; ?>
            </dl>
        </div></div>
        <div class="map" data-map-markers="<?= e(json_encode($markers)) ?>" data-map-line="1"></div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-header">Progress</div><div class="card-body pb-1">
            <ul class="timeline">
                <?php foreach ($steps as [$t, $s, $done]): ?>
                    <li class="<?= $done ? 'done' : '' ?>"><div class="fw-semibold"><?= e($t) ?></div><div class="small"><?= e($s) ?></div></li>
                <?php endforeach; ?>
            </ul>
        </div></div>

        <?php if ($canAssign): ?>
            <div class="card mb-3"><div class="card-header">🚴 Who will collect it?</div><div class="card-body">
                <?php if ($dl['volunteer_name']): ?>
                    <p class="mb-2">Assigned to <strong><?= e($dl['volunteer_name']) ?></strong>.</p>
                <?php else: ?>
                    <p class="text-body-secondary small mb-2">Nobody yet. Every volunteer can see this task and claim it. You can also pick one below, or collect it yourselves with the QR code.</p>
                <?php endif; ?>
                <form method="post" action="<?= e(url('deliveries/' . $dl['id'] . '/assign')) ?>">
                    <?= csrf_field() ?>
                    <select class="form-select mb-2" name="volunteer_id" aria-label="Volunteer">
                        <option value="0">Leave open / we collect it ourselves</option>
                        <?php foreach ($volunteers as $v): ?>
                            <option value="<?= (int)$v['id'] ?>" <?= (int)$dl['volunteer_id'] === (int)$v['id'] ? 'selected' : '' ?>>
                                <?= e($v['name']) ?> · <?= e(ucfirst($v['vehicle'] === 'none' ? 'on foot' : $v['vehicle'])) ?><?= $v['service_area'] ? ' · ' . e($v['service_area']) : '' ?> · <?= e(ucfirst($v['availability'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary w-100">Save</button>
                </form>
            </div></div>
        <?php endif; ?>

        <?php if ($u['role'] === 'volunteer' && !$dl['volunteer_id'] && $dl['status'] === 'scheduled'): ?>
            <div class="card mb-3"><div class="card-body">
                <p class="mb-2">Nobody has claimed this pickup yet.</p>
                <form method="post" action="<?= e(url('deliveries/' . $dl['id'] . '/claim')) ?>"><?= csrf_field() ?><button class="btn btn-primary w-100"><i class="bi bi-hand-thumbs-up"></i> Claim this task</button></form>
            </div></div>
        <?php endif; ?>

        <?php if ($canSeeQr): ?>
            <div class="card mb-3 text-center"><div class="card-header">Collection QR code</div><div class="card-body">
                <div class="qr-box mb-3"><div data-qr="<?= e($dl['qr_token']) ?>"></div></div>
                <p class="small text-body-secondary mb-1">Show this to <?= e($dl['donor_name']) ?> when you arrive. They scan it to hand over the food.</p>
                <div class="token small">Code: <?= e($dl['qr_token']) ?></div>
            </div></div>
        <?php endif; ?>

        <?php if (in_array($u['role'], ['donor', 'admin'], true) && $dl['status'] === 'scheduled'): ?>
            <div class="card mb-3"><div class="card-body">
                <p class="mb-2">When the collector arrives, verify their QR code.</p>
                <a class="btn btn-primary w-100" href="<?= e(url('verify')) ?>"><i class="bi bi-qr-code-scan"></i> Verify collection</a>
            </div></div>
        <?php endif; ?>

        <?php if ($canDeliver): ?>
            <div class="card mb-3"><div class="card-header">Record distribution</div><div class="card-body">
                <form method="post" action="<?= e(url('deliveries/' . $dl['id'] . '/deliver')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label" for="people_served">People served</label>
                        <input class="form-control" id="people_served" name="people_served" type="number" min="1" value="<?= e(old('people_served')) ?>" required></div>
                    <div class="mb-3"><label class="form-label" for="distribution_notes">Notes <span class="text-body-secondary">(optional)</span></label>
                        <textarea class="form-control" id="distribution_notes" name="distribution_notes" rows="2" maxlength="500" placeholder="Where and how the food was distributed"><?= e(old('distribution_notes')) ?></textarea></div>
                    <button class="btn btn-primary w-100">Mark as delivered</button>
                </form>
            </div></div>
        <?php endif; ?>

        <?php if ($reviews || $canReview): ?>
            <div class="card"><div class="card-header">Feedback</div><div class="card-body">
                <?php foreach ($reviews as $r): ?>
                    <div class="mb-2"><?= star_rating((float)$r['rating']) ?> <span class="small text-body-secondary">by <?= e($r['reviewer_name']) ?></span>
                        <?php if ($r['comment']): ?><div class="small">“<?= e($r['comment']) ?>”</div><?php endif; ?></div>
                <?php endforeach; ?>
                <?php if ($canReview): ?>
                    <form method="post" action="<?= e(url('deliveries/' . $dl['id'] . '/review')) ?>" class="<?= $reviews ? 'border-top pt-3 mt-3' : '' ?>">
                        <?= csrf_field() ?>
                        <div class="mb-2">Rate <?= e($u['role'] === 'donor' ? $dl['ngo_name'] : $dl['donor_name']) ?></div>
                        <div class="rating-input mb-2">
                            <?php for ($i = 5; $i >= 1; $i--): ?><input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>"><label for="star<?= $i ?>" title="<?= $i ?> star(s)"><i class="bi bi-star-fill"></i></label><?php endfor; ?>
                        </div>
                        <textarea class="form-control mb-2" name="comment" rows="2" maxlength="500" placeholder="Comment (optional)"></textarea>
                        <button class="btn btn-outline-primary btn-sm">Submit feedback</button>
                    </form>
                <?php endif; ?>
            </div></div>
        <?php endif; ?>
    </div>
</div>
