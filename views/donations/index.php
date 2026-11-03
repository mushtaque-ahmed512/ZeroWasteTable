<?php
$isAdmin = has_role('admin');
echo page_header($isAdmin ? 'All donations' : 'My donations', $isAdmin ? 'Every listing on the platform.' : 'Food you have listed for NGOs.',
    $isAdmin ? '' : '<a class="btn btn-primary" href="' . e(url('donations/create')) . '"><i class="bi bi-plus-lg"></i> New donation</a>');
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-sm <?= $status === '' ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(url('donations')) ?>">All</a>
    <?php foreach ($statuses as $s): ?>
        <a class="btn btn-sm <?= $status === $s ? 'btn-primary' : 'btn-outline-secondary' ?>" href="<?= e(url('donations', ['status' => $s])) ?>"><?= e(ucfirst($s)) ?></a>
    <?php endforeach; ?>
</div>

<div class="card">
<?php if (!$list): ?>
    <div class="card-body"><?= empty_state('No donations found.', '🍽️', $isAdmin ? '' : '<a class="btn btn-primary btn-sm" href="' . e(url('donations/create')) . '">Post your first donation</a>') ?></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead><tr>
                <th>Donation</th><?php if ($isAdmin): ?><th>Donor</th><?php endif; ?><th>Quantity</th><th>Expiry</th><th>Status</th><th class="text-center">Pending requests</th><th></th>
            </tr></thead>
            <tbody>
            <?php foreach ($list as $d): ?>
                <tr>
                    <td>
                        <a class="fw-semibold text-decoration-none" href="<?= e(url('donations/' . $d['id'])) ?>"><?= food_emoji($d['title'], $d['category']) ?> <?= e($d['title']) ?></a>
                        <div class="small text-body-secondary"><?= e($d['category']) ?></div>
                    </td>
                    <?php if ($isAdmin): ?><td><?= e($d['org_name']) ?></td><?php endif; ?>
                    <td><?= e(qty((float)$d['quantity'], $d['unit'])) ?></td>
                    <td>
                        <div><?= e(fmt_dt($d['expiry_at'])) ?></div>
                        <?php if (in_array($d['status'], ['available', 'requested', 'approved', 'scheduled'], true)): ?><?= expiry_badge($d['expiry_at']) ?><?php endif; ?>
                    </td>
                    <td><?= status_badge($d['status']) ?></td>
                    <td class="text-center"><?= (int)$d['pending_requests'] ?: '<span class="text-body-tertiary">–</span>' ?></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?= e(url('donations/' . $d['id'])) ?>">Open</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
