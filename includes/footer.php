<?php
/** Closes the layout opened in header.php and loads scripts. */
$libUrls = [
    'leaflet' => 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js',
    'chart'   => 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js',
    'qrcode'  => 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
    'scanner' => 'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js',
];
?>
<?php if (user()): ?>
        </main>
    </div>
</div>
<?php
// Phone tab bar: the 4 things each role does most (donors get a big "+" in the middle)
$tabs = [
    'donor'     => ['dashboard', 'donations', '+', 'requests', 'deliveries'],
    'ngo'       => ['dashboard', 'donations', 'requests', 'deliveries'],
    'volunteer' => ['dashboard', 'deliveries', 'notifications'],
    'admin'     => ['dashboard', 'donations', 'admin/users', 'notifications'],
][user()['role']] ?? ['dashboard'];
$short = ['dashboard' => 'Home', 'donations' => user()['role'] === 'ngo' ? 'Browse' : 'Donations', 'requests' => 'Requests',
          'deliveries' => user()['role'] === 'volunteer' ? 'Tasks' : 'Pickups', 'notifications' => 'Alerts', 'admin/users' => 'Users'];
$tabIcons = [];
foreach ($nav ?? [] as [, $np, $ni]) { $tabIcons[$np] = $ni; }
?>
<nav class="bottom-nav d-lg-none" aria-label="Quick navigation">
    <?php foreach ($tabs as $t): ?>
        <?php if ($t === '+'): ?>
            <a class="fab" href="<?= e(url('donations/create')) ?>" aria-label="Post food"><i class="bi bi-plus-lg"></i></a>
        <?php else: ?>
            <a href="<?= e(url($t)) ?>" class="<?= ($activePath ?? '') === $t ? 'active' : '' ?>">
                <i class="bi bi-<?= e($tabIcons[$t] ?? 'circle') ?>"></i><?= e($short[$t] ?? $t) ?>
                <?php if ($t === 'notifications' && !empty($unread)): ?><span class="dot"><?= (int)$unread ?></span><?php endif; ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
<?php else: ?>
</main>
<footer class="public-footer">
    <div class="container d-flex flex-wrap justify-content-between gap-2 small">
        <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Surplus food should feed people, not landfills.</span>
        <span><a href="<?= e(url('login')) ?>">Log in</a> · <a href="<?= e(url('register')) ?>">Register</a></span>
    </div>
</footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach ($libs as $lib): if (isset($libUrls[$lib])): ?>
<script src="<?= e($libUrls[$lib]) ?>"></script>
<?php endif; endforeach; ?>
<script>
    window.SF = {
        base: <?= json_encode(BASE_URL) ?>,
        mapDefault: <?= MAP_DEFAULT ?>,
        chartUrl: <?= json_encode(url('analytics/data')) ?>
    };
</script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
