<?php
$u = user();
$first = explode(' ', $u['name'])[0];

// One clear "do this next" card per role - the single most useful action right now
switch ($u['role']) {
    case 'donor':
        $next = ($stats['pending_requests'] ?? 0) > 0
            ? ['📥', $stats['pending_requests'] . ' NGO request' . ($stats['pending_requests'] > 1 ? 's' : '') . ' waiting', 'Approve one so the pickup can be scheduled before the food expires.', 'Review requests', 'requests']
            : ['🍛', 'Got surplus food today?', 'Post it in under a minute. Verified NGOs are alerted straight away.', 'Post food', 'donations/create'];
        break;
    case 'ngo':
        $next = (NGO_REQUIRES_VERIFICATION && !$u['verified'])
            ? ['⏳', 'Verification in progress', 'An admin is checking your organization. You can already browse what is available.', 'Browse food', 'donations']
            : (($stats['my_approved'] ?? 0) > 0
                ? ['✅', $stats['my_approved'] . ' approved request' . ($stats['my_approved'] > 1 ? 's' : ''), 'Show the QR code at pickup, then record how many people you fed.', 'See pickups', 'deliveries']
                : ['🍱', ($stats['available'] ?? 0) . ' listing' . (($stats['available'] ?? 0) == 1 ? '' : 's') . ' ready now', 'Request what you can use. The ones expiring soonest come first.', 'Browse food', 'donations']);
        break;
    case 'volunteer':
        $next = ($stats['open_tasks'] ?? 0) > 0
            ? ['🚴', $stats['open_tasks'] . ' pickup' . ($stats['open_tasks'] > 1 ? 's' : '') . ' need a hand', 'Claim one that is on your way. It takes a few minutes.', 'See tasks', 'deliveries']
            : ['🌿', 'You are all caught up', 'We will notify you the moment a pickup needs a volunteer.', 'My tasks', 'deliveries'];
        break;
    default:
        $next = ($stats['unverified'] ?? 0) > 0
            ? ['🛡️', $stats['unverified'] . ' organization' . ($stats['unverified'] > 1 ? 's' : '') . ' to verify', 'NGOs cannot request food until you verify them.', 'Review now', 'admin/users']
            : ['✅', 'Everything is in order', 'No organizations are waiting for verification.', 'Manage users', 'admin/users'];
}
?>
<div class="page-header mb-3">
    <h1 class="h3 mb-0">Hi, <?= e($first) ?> 👋</h1>
    <p class="text-body-secondary mb-0"><?= e($u['org_name'] ?: role_label($u['role'])) ?></p>
</div>

<div class="next-card mb-4" data-emoji="<?= $next[0] ?>">
    <h2><?= e($next[1]) ?></h2>
    <p><?= e($next[2]) ?></p>
    <a class="btn btn-light" href="<?= e(url($next[4])) ?>"><?= e($next[3]) ?> <i class="bi bi-arrow-right"></i></a>
</div>

<div class="row g-3 mb-4">
<?php
switch ($u['role']) {
    case 'admin':
        echo stat_card('👥', 'Platform users', $stats['users']);
        echo stat_card('🍱', 'Total donations', $stats['total'], 'warning');
        echo stat_card('✅', 'Delivered', $stats['delivered'], 'success');
        echo stat_card('🙌', 'People served', number_format($stats['people']), 'info');
        break;
    case 'donor':
        echo stat_card('🍱', 'Total donations', $stats['total']);
        echo stat_card('⏳', 'Open listings', $stats['open'], 'warning');
        echo stat_card('📥', 'Pending requests', $stats['pending_requests'], 'info');
        echo stat_card('🙌', 'People served', number_format($stats['people']), 'success');
        break;
    case 'ngo':
        echo stat_card('🍛', 'Available now', $stats['available']);
        echo stat_card('⏳', 'Requests pending', $stats['my_pending'], 'warning');
        echo stat_card('✅', 'Approved', $stats['my_approved'], 'success');
        echo stat_card('🙌', 'People served', number_format($stats['people']), 'info');
        break;
    case 'volunteer':
        echo stat_card('🚴', 'Open tasks', $stats['open_tasks']);
        echo stat_card('🛵', 'My active', $stats['mine_active'], 'warning');
        echo stat_card('✅', 'Completed', $stats['mine_done'], 'success');
        echo stat_card('🙌', 'People served', number_format($stats['people']), 'info');
        break;
}
?>
</div>

<div class="card mb-4">
    <div class="card-header"><?= e($panelTitle) ?></div>
    <?php if (!$panel): ?>
        <div class="card-body"><?= empty_state('Nothing here yet.', '🍽️') ?></div>
    <?php else: foreach ($panel as $row): ?>
        <a class="list-row" href="<?= e(url($row['href'])) ?>">
            <span class="emoji-bubble"><?= food_emoji($row['title']) ?></span>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold text-truncate"><?= e($row['title']) ?></div>
                <div class="meta text-truncate"><?= e($row['meta']) ?></div>
            </div>
            <?php if ($row['flag']): ?><span class="badge text-bg-light border d-none d-sm-inline"><?= e($row['flag']) ?></span><?php endif; ?>
            <?= status_badge($row['status']) ?>
        </a>
    <?php endforeach; endif; ?>
</div>

<h2 class="h5 fw-bold mb-3">Insights</h2>
<div class="row g-3">
    <div class="col-lg-8"><div class="card h-100"><div class="card-header">Donations posted (6 months)</div>
        <div class="card-body"><div class="chart-box"><canvas id="chart-monthly"></canvas></div></div></div></div>
    <div class="col-lg-4"><div class="card h-100"><div class="card-header">By category</div>
        <div class="card-body"><div class="chart-box"><canvas id="chart-category"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">People served per month</div>
        <div class="card-body"><div class="chart-box" style="height:200px"><canvas id="chart-served"></canvas></div></div></div></div>
    <div class="col-lg-6"><div class="card h-100"><div class="card-header">Donations by status</div>
        <div class="card-body"><div class="chart-box" style="height:200px"><canvas id="chart-status"></canvas></div></div></div></div>
</div>
