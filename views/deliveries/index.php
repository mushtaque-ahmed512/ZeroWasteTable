<?php
$role = user()['role'];
echo page_header($role === 'volunteer' ? 'Pickup tasks' : 'Pickups & deliveries', $role === 'volunteer' ? 'Claim open tasks and complete your deliveries.' : 'Scheduled hand-overs and their progress.');

$table = function (array $rows, bool $withClaim) use ($role) { ob_start(); ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Food</th><th>From</th><th>To</th><th>Pickup time</th><th>Volunteer</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><a class="fw-semibold text-decoration-none" href="<?= e(url('deliveries/' . $r['id'])) ?>"><?= e($r['title']) ?></a>
                        <div class="small text-body-secondary"><?= e(qty((float)$r['quantity_needed'], $r['unit'])) ?></div></td>
                    <td><?= e($r['donor_name']) ?></td>
                    <td><?= e($r['ngo_name']) ?></td>
                    <td class="text-nowrap"><?= e(fmt_dt($r['pickup_at'], 'd M, h:i A')) ?></td>
                    <td><?= $r['volunteer_name'] ? e($r['volunteer_name']) : '<span class="text-body-tertiary">Unassigned</span>' ?></td>
                    <td><?= status_badge($r['status']) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ($withClaim): ?>
                            <form class="d-inline" method="post" action="<?= e(url('deliveries/' . $r['id'] . '/claim')) ?>"><?= csrf_field() ?><button class="btn btn-sm btn-primary">Claim</button></form>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('deliveries/' . $r['id'])) ?>">Open</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php return ob_get_clean(); };
?>

<?php if ($role === 'volunteer'): ?>
    <div class="card mb-4">
        <div class="card-header">Open tasks <span class="text-body-secondary fw-normal">(nobody has claimed these yet)</span></div>
        <?= $open ? $table($open, true) : '<div class="card-body">' . empty_state('No open tasks right now. You will be notified when one appears.', '🚴') . '</div>' ?>
    </div>
    <div class="card">
        <div class="card-header">My tasks</div>
        <?= $rows ? $table($rows, false) : '<div class="card-body">' . empty_state('You have not claimed any tasks yet.', '🚚') . '</div>' ?>
    </div>
<?php else: ?>
    <div class="card">
        <?= $rows ? $table($rows, false) : '<div class="card-body">' . empty_state('No pickups scheduled yet.', '🚚') . '</div>' ?>
    </div>
<?php endif; ?>
