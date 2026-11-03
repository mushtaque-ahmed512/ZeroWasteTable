<?php echo page_header('Users & organizations', 'Verify NGOs and donors, suspend accounts that break the rules.'); ?>
<form class="card card-body mb-3" method="get" action="<?= e(url('admin/users')) ?>">
    <?php if (!PRETTY_URLS): ?><input type="hidden" name="route" value="admin/users"><?php endif; ?>
    <div class="row g-2">
        <div class="col-12 col-md-6"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name, email or organization"></div>
        <div class="col-6 col-md-3"><select class="form-select" name="role">
            <option value="">All roles</option>
            <?php foreach (['donor' => 'Donors', 'ngo' => 'NGOs', 'volunteer' => 'Volunteers', 'admin' => 'Admins'] as $k => $l): ?><option value="<?= $k ?>" <?= $role === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-6 col-md-3"><button class="btn btn-primary w-100">Search</button></div>
    </div>
</form>
<div class="card"><div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead><tr><th>User</th><th>Role</th><th>Organization</th><th>Verified</th><th>Status</th><th>Joined</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><div class="fw-semibold"><?= e($r['name']) ?></div><div class="small text-body-secondary"><?= e($r['email']) ?></div></td>
                <td><?= e(role_label($r['role'])) ?></td>
                <td><?= $r['org_name'] ? e($r['org_name']) . '<div class="small text-body-secondary">' . e(ucfirst($r['org_type'])) . '</div>' : '<span class="text-body-tertiary">–</span>' ?></td>
                <td>
                    <?php if ($r['org_id']): ?>
                        <form method="post" action="<?= e(url('admin/organizations/' . $r['org_id'] . '/verify')) ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="verified" value="<?= $r['verified'] ? '0' : '1' ?>">
                            <?php if ($r['verified']): ?><button class="btn btn-sm btn-link text-success p-0" title="Click to remove verification"><i class="bi bi-patch-check-fill"></i> Verified</button>
                            <?php else: ?><button class="btn btn-sm btn-outline-primary">Verify</button><?php endif; ?>
                        </form>
                    <?php else: ?><span class="text-body-tertiary">–</span><?php endif; ?>
                </td>
                <td><?= status_badge($r['status']) ?></td>
                <td class="small text-nowrap"><?= e(fmt_dt($r['created_at'], 'd M Y')) ?></td>
                <td class="text-end">
                    <?php if ((int)$r['id'] !== user()['id']): ?>
                    <form method="post" action="<?= e(url('admin/users/' . $r['id'] . '/status')) ?>" <?= $r['status'] === 'active' ? 'data-confirm="Suspend this account?"' : '' ?>>
                        <?= csrf_field() ?>
                        <input type="hidden" name="status" value="<?= $r['status'] === 'active' ? 'suspended' : 'active' ?>">
                        <button class="btn btn-sm <?= $r['status'] === 'active' ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= $r['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7"><?= empty_state('No users match.', '👥') ?></td></tr><?php endif; ?>
        </tbody>
    </table>
</div></div>
