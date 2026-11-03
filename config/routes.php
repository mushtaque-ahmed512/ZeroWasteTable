<?php
/**
 * Route table. Format: $r->get|post(pattern, [Controller::class, 'method'], [middleware...])
 * Middleware: auth, guest, role:donor,ngo   (CSRF is verified automatically on every POST)
 */
return function (Router $r): void {
    $auth = ['auth'];

    // Public
    $r->get('/',          [HomeController::class, 'index']);
    $r->get('/login',     [AuthController::class, 'showLogin'],    ['guest']);
    $r->post('/login',    [AuthController::class, 'login'],        ['guest']);
    $r->get('/register',  [AuthController::class, 'showRegister'], ['guest']);
    $r->post('/register', [AuthController::class, 'register'],     ['guest']);
    $r->get('/forgot-password',   [AuthController::class, 'showForgot'],   ['guest']);
    $r->post('/forgot-password',  [AuthController::class, 'forgot'],       ['guest']);
    $r->get('/reset-password',    [AuthController::class, 'showReset'],    ['guest']);
    $r->post('/reset-password',   [AuthController::class, 'reset'],        ['guest']);
    $r->post('/logout',   [AuthController::class, 'logout'],       $auth);

    // Dashboard + analytics
    $r->get('/dashboard',      [DashboardController::class, 'index'], $auth);
    $r->get('/analytics/data', [DashboardController::class, 'chartData'], $auth);

    // Profile
    $r->get('/profile',           [ProfileController::class, 'edit'],     $auth);
    $r->post('/profile',          [ProfileController::class, 'update'],   $auth);
    $r->post('/profile/password', [ProfileController::class, 'password'], $auth);

    // Donations
    $r->get('/donations',                 [DonationController::class, 'index'],   ['auth', 'role:donor,ngo,admin']);
    $r->get('/donations/create',          [DonationController::class, 'create'],  ['auth', 'role:donor']);
    $r->post('/donations/store',          [DonationController::class, 'store'],   ['auth', 'role:donor']);
    $r->get('/donations/{id}',            [DonationController::class, 'show'],    ['auth', 'role:donor,ngo,admin']);
    $r->get('/donations/{id}/edit',       [DonationController::class, 'edit'],    ['auth', 'role:donor']);
    $r->post('/donations/{id}/update',    [DonationController::class, 'update'],  ['auth', 'role:donor']);
    $r->post('/donations/{id}/images/{imageId}/delete', [DonationController::class, 'deleteImage'], ['auth', 'role:donor']);
    $r->post('/donations/{id}/cancel',    [DonationController::class, 'cancel'],  ['auth', 'role:donor']);

    // Food requests (NGO asks, donor decides)
    $r->get('/requests',                  [RequestController::class, 'index'],    ['auth', 'role:donor,ngo,admin']);
    $r->post('/donations/{id}/request',   [RequestController::class, 'store'],    ['auth', 'role:ngo']);
    $r->post('/requests/{id}/approve',    [RequestController::class, 'approve'],  ['auth', 'role:donor']);
    $r->post('/requests/{id}/reject',     [RequestController::class, 'reject'],   ['auth', 'role:donor']);
    $r->post('/requests/{id}/cancel',     [RequestController::class, 'cancel'],   ['auth', 'role:ngo']);
    $r->get('/requests/{id}/schedule',    [RequestController::class, 'scheduleForm'], ['auth', 'role:donor']);
    $r->post('/requests/{id}/schedule',   [RequestController::class, 'schedule'], ['auth', 'role:donor']);

    // Deliveries, QR verification, distribution tracking
    $r->get('/deliveries',                [DeliveryController::class, 'index'],   ['auth', 'role:donor,ngo,volunteer,admin']);
    $r->get('/deliveries/{id}',           [DeliveryController::class, 'show'],    ['auth', 'role:donor,ngo,volunteer,admin']);
    $r->post('/deliveries/{id}/claim',    [DeliveryController::class, 'claim'],   ['auth', 'role:volunteer']);
    $r->post('/deliveries/{id}/assign',   [DeliveryController::class, 'assign'],  ['auth', 'role:ngo,donor,admin']);
    $r->post('/deliveries/{id}/deliver',  [DeliveryController::class, 'deliver'], ['auth', 'role:ngo,volunteer']);
    $r->post('/deliveries/{id}/review',   [DeliveryController::class, 'review'],  ['auth', 'role:donor,ngo']);
    $r->get('/verify',                    [DeliveryController::class, 'verifyForm'], ['auth', 'role:donor,admin']);
    $r->post('/verify',                   [DeliveryController::class, 'verify'],  ['auth', 'role:donor,admin']);

    // Notifications
    $r->get('/notifications',             [NotificationController::class, 'index'],   $auth);
    $r->post('/notifications/read-all',   [NotificationController::class, 'readAll'], $auth);
    $r->get('/notifications/{id}/open',   [NotificationController::class, 'open'],    $auth);

    // Admin
    $r->get('/admin/users',                   [AdminController::class, 'users'],         ['auth', 'role:admin']);
    $r->post('/admin/users/{id}/status',      [AdminController::class, 'userStatus'],    ['auth', 'role:admin']);
    $r->post('/admin/organizations/{id}/verify', [AdminController::class, 'verifyOrg'],  ['auth', 'role:admin']);
    $r->get('/admin/categories',              [AdminController::class, 'categories'],    ['auth', 'role:admin']);
    $r->post('/admin/categories',             [AdminController::class, 'categoryStore'], ['auth', 'role:admin']);
    $r->post('/admin/categories/{id}/delete', [AdminController::class, 'categoryDelete'],['auth', 'role:admin']);
};
