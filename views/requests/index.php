<?php
$role = user()['role'];
echo page_header($title, $role === 'ngo' ? 'Track the food you have asked for.' : 'Review NGO requests, approve one, then schedule the pickup.');
?>
<div class="card">
<?php if (!$rows): ?>
    <div class="card-body"><?= empty_state($role === 'ngo' ? 'You have not requested any food yet.' : 'No requests yet.', '📥',
        $role === 'ngo' ? '<a class="btn btn-primary btn-sm" href="' . e(url('donations')) . '">Browse food</a>' : '') ?></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Food</th><?php if ($role !== 'donor'): ?><th>Donor</th><?php endif; ?><?php if ($role !== 'ngo'): ?><th>NGO</th><?php endif; ?>
                <th>Requested</th><th>Status</th><th>Sent</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><a class="fw-semibold text-decoration-none" href="<?= e(url('donations/' . $r['donation_id'])) ?>"><?= e($r['title']) ?></a>
                        <div class="small text-body-secondary">Donation: <?= status_badge($r['donation_status']) ?></div></td>
                    <?php if ($role !== 'donor'): ?><td><?= e($r['donor_name']) ?></td><?php endif; ?>
                    <?php if ($role !== 'ngo'): ?><td><?= e($r['ngo_name']) ?></td><?php endif; ?>
                    <td><?= e(qty((float)$r['quantity_needed'], $r['unit'])) ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="text-nowrap small"><?= e(fmt_dt($r['created_at'], 'd M, h:i A')) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ($role === 'donor' && $r['status'] === 'pending'): ?>
                            <form class="d-inline" method="post" action="<?= e(url('requests/' . $r['id'] . '/approve')) ?>" data-confirm="Approve this NGO? Other pending requests will be declined."><?= csrf_field() ?><button class="btn btn-sm btn-primary">Approve</button></form>
                            <form class="d-inline" method="post" action="<?= e(url('requests/' . $r['id'] . '/reject')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-outline-secondary">Decline</button></form>
                        <?php elseif ($role === 'donor' && $r['status'] === 'approved' && !$r['delivery_id']): ?>
                            <a class="btn btn-sm btn-primary" href="<?= e(url('requests/' . $r['id'] . '/schedule')) ?>">Schedule pickup</a>
                        <?php elseif ($role === 'ngo' && $r['status'] === 'pending'): ?>
                            <form class="d-inline" method="post" action="<?= e(url('requests/' . $r['id'] . '/cancel')) ?>" data-confirm="Cancel this request?"><?= csrf_field() ?><button class="btn btn-sm btn-outline-danger">Cancel</button></form>
                        <?php endif; ?>
                        <?php if ($r['delivery_id']): ?><a class="btn btn-sm btn-outline-primary" href="<?= e(url('deliveries/' . $r['delivery_id'])) ?>">Pickup</a><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
