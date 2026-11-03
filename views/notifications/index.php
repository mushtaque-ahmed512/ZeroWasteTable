<?php
echo page_header('Notifications', 'Requests, approvals, pickups and expiry alerts.',
    $items ? '<form method="post" action="' . e(url('notifications/read-all')) . '">' . csrf_field() . '<button class="btn btn-outline-primary btn-sm"><i class="bi bi-check2-all"></i> Mark all read</button></form>' : '');
$icons = ['info' => 'info-circle', 'request' => 'inbox', 'approval' => 'check-circle', 'pickup' => 'truck', 'delivery' => 'heart', 'expiry' => 'alarm'];
?>
<div class="card">
    <?php if (!$items): ?>
        <div class="card-body"><?= empty_state('You are all caught up.', '🔔') ?></div>
    <?php else: foreach ($items as $n): ?>
        <a class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= e(url('notifications/' . $n['id'] . '/open')) ?>">
            <span class="notif-icon"><i class="bi bi-<?= $icons[$n['type']] ?? '🔔' ?>"></i></span>
            <div class="flex-grow-1">
                <div class="fw-semibold"><?= e($n['title']) ?></div>
                <div class="text-body-secondary"><?= e($n['message']) ?></div>
                <div class="small text-body-tertiary mt-1"><?= e(fmt_dt($n['created_at'])) ?></div>
            </div>
            <?php if (!$n['is_read']): ?><span class="badge text-bg-primary align-self-start">New</span><?php endif; ?>
        </a>
    <?php endforeach; endif; ?>
</div>
