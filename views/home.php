<section class="hero">
    <div class="hero-floaters" aria-hidden="true">🍛<br>🥖 🍚</div>
    <div class="container position-relative">
        <p class="fw-bold text-uppercase small mb-2" style="letter-spacing:.1em;opacity:.9">Food rescue platform</p>
        <h1 class="mb-3">Good food shouldn't end up in the bin.</h1>
        <p class="lead mb-4">Bakeries, restaurants and hotels post what they can't sell. NGOs claim it, volunteers deliver it, and every hand-over is confirmed with a QR code.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= e(url('register', ['role' => 'donor'])) ?>" class="btn btn-light btn-lg">🍛 I have surplus food</a>
            <a href="<?= e(url('register', ['role' => 'ngo'])) ?>" class="btn btn-outline-light btn-lg">🤝 We feed people</a>
        </div>
    </div>
</section>

<div class="container">
    <div class="hero-stats p-3 p-md-4">
        <div class="row text-center g-3">
            <div class="col-6 col-md-3"><div class="num"><?= number_format($stats['donations']) ?></div><div class="small text-body-secondary">Donations listed</div></div>
            <div class="col-6 col-md-3"><div class="num"><?= number_format($stats['delivered']) ?></div><div class="small text-body-secondary">Delivered</div></div>
            <div class="col-6 col-md-3"><div class="num"><?= number_format($stats['people']) ?></div><div class="small text-body-secondary">People served</div></div>
            <div class="col-6 col-md-3"><div class="num"><?= number_format($stats['partners']) ?></div><div class="small text-body-secondary">Partners</div></div>
        </div>
    </div>
</div>

<section class="container py-5" id="how">
    <h2 class="h3 fw-bold mb-4">How it works</h2>
    <div class="row g-4">
        <?php foreach ([
            ['🍛', 'Post', 'Donors list what is left, how much, and when it expires.'],
            ['🙋', 'Request', 'Verified NGOs see what is nearby and ask for what they can use.'],
            ['📅', 'Schedule', 'The donor approves one NGO and picks a time. A QR code appears.'],
            ['📱', 'Scan & serve', 'The donor scans the QR on pickup. The NGO records people fed.'],
        ] as [$em, $t, $d]): ?>
            <div class="col-6 col-lg-3">
                <div class="step-emoji mb-3"><?= $em ?></div>
                <h3 class="h6 fw-bold mb-1"><?= e($t) ?></h3>
                <p class="text-body-secondary small mb-0"><?= e($d) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="container pb-5">
    <div class="row g-3">
        <?php foreach ([
            ['🏪', 'Food donors', 'Cut waste and disposal costs, and see exactly where your food went.'],
            ['🤝', 'NGOs & charities', 'Find fresh surplus near you and track every meal you distribute.'],
            ['🚴', 'Volunteers', 'Claim short pickup tasks that fit your schedule and vehicle.'],
        ] as [$em, $t, $d]): ?>
            <div class="col-md-4">
                <div class="card h-100"><div class="card-body">
                    <div class="hero-emoji mb-2" style="font-size:2.6rem"><?= $em ?></div>
                    <h3 class="h5 fw-bold"><?= e($t) ?></h3>
                    <p class="text-body-secondary mb-0"><?= e($d) ?></p>
                </div></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
