<?php
/**
 * Front controller - every request enters here.
 */
require __DIR__ . '/includes/bootstrap.php';

$router = new Router();
(require ROOT_PATH . '/config/routes.php')($router);

// Lightweight "cron": sweep expiring/expired donations at most every few minutes
if (user()) {
    (new Notification())->runExpiryChecks(true);
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_GET['route'] ?? '');
