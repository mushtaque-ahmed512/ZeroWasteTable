<?php
/**
 * Shared page header. Logged-in users get the sidebar app shell, visitors get a plain top bar.
 * Expects: $title (string), $libs (array of optional front-end libraries).
 */
$u      = user();
$route  = trim($_GET['route'] ?? '', '/');
$unread = $u ? (new Notification())->unreadCount($u['id']) : 0;

$nav = [];
if ($u) {
    $nav[] = ['Dashboard', 'dashboard', 'speedometer2'];
    switch ($u['role']) {
        case 'donor':
            $nav[] = ['My donations', 'donations', 'basket2'];
            $nav[] = ['New donation', 'donations/create', 'plus-circle'];
            $nav[] = ['Requests', 'requests', 'inbox'];
            $nav[] = ['Pickups', 'deliveries', 'truck'];
            $nav[] = ['Verify pickup', 'verify', 'qr-code-scan'];
            break;
        case 'ngo':
            $nav[] = ['Browse food', 'donations', 'search-heart'];
            $nav[] = ['My requests', 'requests', 'hand-index-thumb'];
            $nav[] = ['Deliveries', 'deliveries', 'truck'];
            break;
        case 'volunteer':
            $nav[] = ['Pickup tasks', 'deliveries', 'bicycle'];
            break;
        case 'admin':
            $nav[] = ['Donations', 'donations', 'basket'];
            $nav[] = ['Requests', 'requests', 'inbox'];
            $nav[] = ['Deliveries', 'deliveries', 'truck'];
            $nav[] = ['Verify pickup', 'verify', 'qr-code-scan'];
            $nav[] = ['Users & orgs', 'admin/users', 'people'];
            $nav[] = ['Categories', 'admin/categories', 'tags'];
            break;
    }
    $nav[] = ['Notifications', 'notifications', 'bell'];
    $nav[] = ['My profile', 'profile', 'person-circle'];

    // the most specific matching item is the active one
    $activePath = '';
    foreach ($nav as [, $p]) {
        if (($route === $p || str_starts_with($route, $p . '/')) && strlen($p) > strlen($activePath)) {
            $activePath = $p;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> | <?= e(APP_NAME) ?></title>
    <meta name="theme-color" content="#f0502b">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <?php if (in_array('leaflet', $libs, true)): ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" rel="stylesheet">
    <?php endif; ?>
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
</head>
<body class="<?= $u ? 'is-app' : 'is-public' ?>">
<?php if ($u): ?>
<div class="app-shell">
    <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebar" aria-label="Main navigation">
        <div class="offcanvas-header d-lg-none">
            <span class="brand"><span class="brand-mark">🍛</span> <?= e(APP_NAME) ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <a class="brand d-none d-lg-inline-flex" href="<?= e(url('dashboard')) ?>"><span class="brand-mark">🍛</span> <?= e(APP_NAME) ?></a>
            <nav class="nav flex-column side-nav">
                <?php foreach ($nav as [$label, $path, $icon]): ?>
                    <a class="nav-link <?= $path === $activePath ? 'active' : '' ?>" href="<?= e(url($path)) ?>">
                        <i class="bi bi-<?= e($icon) ?>"></i> <?= e($label) ?>
                        <?php if ($path === 'notifications' && $unread): ?><span class="badge rounded-pill text-bg-warning ms-auto"><?= $unread ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="side-user mt-auto">
                <div class="fw-semibold text-truncate"><?= e($u['name']) ?></div>
                <div class="small opacity-75 text-truncate"><?= e($u['org_name'] ?: role_label($u['role'])) ?></div>
                <form method="post" action="<?= e(url('logout')) ?>" class="mt-2">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-secondary w-100"><i class="bi bi-box-arrow-right"></i> Log out</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="app-main">
        <header class="topbar">
            <button class="btn btn-light d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Open menu">
                <i class="bi bi-list fs-5"></i>
            </button>
            <span class="brand d-lg-none"><span class="brand-mark">🍛</span></span>
            <span class="topbar-title text-truncate"><?= e($title) ?></span>
            <a class="btn btn-light position-relative ms-auto" href="<?= e(url('notifications')) ?>" aria-label="Notifications">
                <i class="bi bi-bell"></i>
                <?php if ($unread): ?><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill text-bg-danger"><?= $unread ?></span><?php endif; ?>
            </a>
        </header>
        <main class="content">
<?php else: ?>
<nav class="navbar navbar-expand-md public-nav">
    <div class="container">
        <a class="navbar-brand brand" href="<?= e(url('')) ?>"><span class="brand-mark">🍛</span> <?= e(APP_NAME) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#pubnav" aria-label="Toggle menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="pubnav">
            <ul class="navbar-nav ms-auto align-items-md-center gap-md-2">
                <li class="nav-item"><a class="nav-link" href="<?= e(url('')) ?>#how">How it works</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= e(url('login')) ?>">Log in</a></li>
                <li class="nav-item"><a class="btn btn-primary" href="<?= e(url('register')) ?>">Join now</a></li>
            </ul>
        </div>
    </div>
</nav>
<main class="public-main">
<?php endif; ?>
<?php $flashes = pull_flashes(); if ($flashes): ?>
    <div class="<?= $u ? '' : 'container pt-3' ?>">
    <?php foreach ($flashes as $f): ?>
        <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
            <?= e($f['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>
