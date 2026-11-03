<?php
$markers = array_map(fn($d) => [
    'lat' => (float)$d['latitude'], 'lng' => (float)$d['longitude'],
    'html' => '<strong>' . e(food_emoji($d['title'], $d['category'])) . ' ' . e($d['title']) . '</strong><br>' . e($d['org_name']) . '<br><a href="' . e(url('donations/' . $d['id'])) . '">View details</a>',
], $list);
// filter links keep the other filters
$link = fn(array $over) => url('donations', array_filter(array_merge(['q' => $q, 'category' => $category ?: null, 'sort' => $sort ?: null], $over), fn($v) => $v !== null && $v !== ''));
?>
<div class="mb-3">
    <h1 class="h3 fw-bold mb-1">What would you like to rescue? 🍽️</h1>
    <p class="text-body-secondary mb-0">Fresh surplus near you, soonest expiry first.</p>
</div>

<?php if (NGO_REQUIRES_VERIFICATION && !$verified): ?>
    <div class="alert alert-warning py-2">⏳ You can browse now. Requests unlock once an admin verifies your organization.</div>
<?php endif; ?>

<form class="search-pill mb-3" method="get" action="<?= e(url('donations')) ?>">
    <?php if (!PRETTY_URLS): ?><input type="hidden" name="route" value="donations"><?php endif; ?>
    <?php if ($category): ?><input type="hidden" name="category" value="<?= (int)$category ?>"><?php endif; ?>
    <?php if ($sort): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><?php endif; ?>
    <i class="bi bi-search"></i>
    <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search biryani, bread, a bakery...">
</form>

<div class="chip-row">
    <a class="cat-chip <?= !$category ? 'active' : '' ?>" href="<?= e($link(['category' => null])) ?>"><span class="em">🍽️</span>All</a>
    <?php foreach ($categories as $c): ?>
        <a class="cat-chip <?= $category === (int)$c['id'] ? 'active' : '' ?>" href="<?= e($link(['category' => $c['id']])) ?>"><span class="em"><?= category_emoji($c['name']) ?></span><?= e($c['name']) ?></a>
    <?php endforeach; ?>
</div>

<div class="d-flex justify-content-between align-items-center my-3 gap-2">
    <div class="d-flex gap-2">
        <a class="pill <?= $sort === '' ? 'active' : '' ?>" href="<?= e($link(['sort' => null])) ?>"><i class="bi bi-alarm"></i> Expiring soon</a>
        <a class="pill <?= $sort === 'distance' ? 'active' : '' ?>" href="<?= e($link(['sort' => 'distance'])) ?>"><i class="bi bi-geo-alt"></i> Nearest</a>
    </div>
    <ul class="nav view-toggle" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#view-list" type="button" role="tab"><i class="bi bi-grid"></i> List</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#view-map" type="button" role="tab"><i class="bi bi-map"></i> Map</button></li>
    </ul>
</div>

<?php if (!$list): ?>
    <div class="card"><div class="card-body"><?= empty_state('Nothing matches right now. New food appears here as soon as donors post it.', '🥖') ?></div></div>
<?php else: ?>
<div class="tab-content">
    <div class="tab-pane fade show active" id="view-list" role="tabpanel">
        <div class="row g-3">
        <?php foreach ($list as $d):
            [$left, $tone] = time_left($d['expiry_at']); ?>
            <div class="col-12 col-sm-6 col-xl-4">
                <a class="card food-card h-100" href="<?= e(url('donations/' . $d['id'])) ?>">
                    <div class="food-cover">
                        <?php if ($d['cover']): ?><img src="<?= e(upload_url($d['cover'])) ?>" alt="" loading="lazy"><?php else: ?><?= food_emoji($d['title'], $d['category']) ?><?php endif; ?>
                        <span class="badge text-bg-<?= $tone ?> tag">⏱ <?= e($left) ?></span>
                        <?php if ($d['cover']): ?><span class="em-corner"><?= food_emoji($d['title'], $d['category']) ?></span><?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h2 class="h6 fw-bold mb-1"><?= e($d['title']) ?></h2>
                        <div class="food-meta mb-2"><?= e($d['org_name']) ?></div>
                        <div class="d-flex justify-content-between food-meta">
                            <span>📦 <?= e(qty((float)$d['quantity'], $d['unit'])) ?></span>
                            <?php if ($d['distance'] !== null): ?><span>📍 <?= e($d['distance']) ?> km</span><?php endif; ?>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <div class="tab-pane fade" id="view-map" role="tabpanel">
        <div class="map map-lg" data-map-markers="<?= e(json_encode($markers)) ?>"></div>
    </div>
</div>
<?php endif; ?>
