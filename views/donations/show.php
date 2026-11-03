<?php
$u = user();
$isOwner = $u['role'] === 'donor';
$isNgo = $u['role'] === 'ngo';
$editable = $isOwner && $d['status'] === 'available';
$cancellable = $isOwner && in_array($d['status'], ['available', 'requested', 'approved', 'scheduled'], true);
$actions = '';
if ($editable) $actions .= '<a class="btn btn-outline-primary" href="' . e(url('donations/' . $d['id'] . '/edit')) . '"><i class="bi bi-pencil"></i> Edit</a>';
if ($cancellable) $actions .= '<form method="post" action="' . e(url('donations/' . $d['id'] . '/cancel')) . '" data-confirm="Cancel this donation? Any requests will be cancelled too.">' . csrf_field() . '<button class="btn btn-outline-danger"><i class="bi bi-x-circle"></i> Cancel</button></form>';
echo page_header(food_emoji($d['title'], $d['category']) . ' ' . $d['title'], $d['org_name'] . ' · ' . $d['category'], $actions);
$open = in_array($d['status'], ['available', 'requested'], true) && strtotime($d['expiry_at']) > time();
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card mb-3">
            <?php if ($images): ?>
                <div class="card-body pb-0"><div class="d-flex flex-wrap gap-2">
                    <?php foreach ($images as $img): ?><a href="<?= e(upload_url($img['image_path'])) ?>" target="_blank" rel="noopener"><img class="thumb" style="width:140px;height:105px" src="<?= e(upload_url($img['image_path'])) ?>" alt="Photo of <?= e($d['title']) ?>"></a><?php endforeach; ?>
                </div></div>
            <?php endif; ?>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Status</dt><dd class="col-sm-8"><?= status_badge($d['status']) ?></dd>
                    <dt class="col-sm-4">Quantity</dt><dd class="col-sm-8"><?= e(qty((float)$d['quantity'], $d['unit'])) ?></dd>
                    <dt class="col-sm-4">Expires</dt><dd class="col-sm-8"><?= e(fmt_dt($d['expiry_at'])) ?> <?php if (in_array($d['status'], ['available', 'requested', 'approved', 'scheduled'], true)): ?><?= expiry_badge($d['expiry_at']) ?><?php endif; ?></dd>
                    <dt class="col-sm-4">Pickup address</dt><dd class="col-sm-8"><?= e($d['pickup_address']) ?></dd>
                    <?php if ($d['description']): ?><dt class="col-sm-4">Notes</dt><dd class="col-sm-8"><?= nl2br(e($d['description'])) ?></dd><?php endif; ?>
                    <dt class="col-sm-4">Listed</dt><dd class="col-sm-8 mb-0"><?= e(fmt_dt($d['created_at'])) ?></dd>
                </dl>
            </div>
        </div>
        <div class="map" data-map-markers="<?= e(json_encode([['lat' => (float)$d['latitude'], 'lng' => (float)$d['longitude'], 'html' => e($d['pickup_address'])]])) ?>"></div>
    </div>

    <div class="col-lg-5">
    <?php if ($isNgo): ?>
        <div class="card">
            <div class="card-header">Request this food</div>
            <div class="card-body">
            <?php if ($mine && $mine['status'] !== 'cancelled'): ?>
                <p class="mb-2">Your request: <?= status_badge($mine['status']) ?></p>
                <p class="text-body-secondary small mb-3">You asked for <?= e(qty((float)$mine['quantity_needed'], $d['unit'])) ?>.</p>
                <a class="btn btn-outline-primary btn-sm" href="<?= e(url('requests')) ?>">View my requests</a>
            <?php elseif (!$open): ?>
                <p class="text-body-secondary mb-0">This donation is no longer open for requests.</p>
            <?php elseif (NGO_REQUIRES_VERIFICATION && !$u['verified']): ?>
                <p class="text-body-secondary mb-0">Your organization needs to be verified by an administrator before you can send requests.</p>
            <?php else: ?>
                <form method="post" action="<?= e(url('donations/' . $d['id'] . '/request')) ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label class="form-label" for="quantity_needed">How much can you use?</label>
                        <div class="input-group"><input class="form-control" id="quantity_needed" name="quantity_needed" type="number" step="0.01" min="0.01" max="<?= e($d['quantity']) ?>" value="<?= e(old('quantity_needed', $d['quantity'])) ?>" required>
                        <span class="input-group-text"><?= e($d['unit']) ?></span></div></div>
                    <div class="mb-3"><label class="form-label" for="message">Message to donor <span class="text-body-secondary">(optional)</span></label>
                        <textarea class="form-control" id="message" name="message" rows="3" maxlength="500" placeholder="Who will this feed? When can you collect?"><?= e(old('message')) ?></textarea></div>
                    <button class="btn btn-primary w-100"><i class="bi bi-send"></i> Send request</button>
                </form>
            <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between"><span>Requests</span><span class="text-body-secondary fw-normal"><?= count($requests) ?></span></div>
            <?php if (!$requests): ?>
                <div class="card-body"><?= empty_state('No NGO has requested this yet.', 'inbox') ?></div>
            <?php else: foreach ($requests as $r): ?>
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between gap-2">
                        <div>
                            <div class="fw-semibold"><?= e($r['org_name']) ?> <?php if ($r['verified']): ?><i class="bi bi-patch-check-fill text-primary" title="Verified"></i><?php endif; ?></div>
                            <div class="small text-body-secondary"><?= e(qty((float)$r['quantity_needed'], $d['unit'])) ?> · <?= e($r['city'] ?: $r['address']) ?> · <?= e($r['phone']) ?></div>
                        </div>
                        <?= status_badge($r['status']) ?>
                    </div>
                    <?php if ($r['message']): ?><p class="small mt-2 mb-0">“<?= e($r['message']) ?>”</p><?php endif; ?>
                    <?php if ($isOwner): ?>
                        <div class="d-flex gap-2 mt-2">
                        <?php if ($r['status'] === 'pending'): ?>
                            <form method="post" action="<?= e(url('requests/' . $r['id'] . '/approve')) ?>" data-confirm="Approve this NGO? Other pending requests will be declined."><?= csrf_field() ?><button class="btn btn-sm btn-primary">Approve</button></form>
                            <form method="post" action="<?= e(url('requests/' . $r['id'] . '/reject')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Decline</button></form>
                        <?php elseif ($r['status'] === 'approved' && !$r['schedule_id']): ?>
                            <a class="btn btn-sm btn-primary" href="<?= e(url('requests/' . $r['id'] . '/schedule')) ?>"><i class="bi bi-calendar-plus"></i> Schedule pickup</a>
                        <?php elseif ($r['delivery_id']): ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= e(url('deliveries/' . $r['delivery_id'])) ?>">View pickup</a>
                        <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>
