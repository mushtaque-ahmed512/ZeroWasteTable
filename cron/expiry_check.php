<?php
/**
 * Optional: run from Windows Task Scheduler or cron every 10-15 minutes.
 *   php C:\xampp\htdocs\smartfood-rescue\cron\expiry_check.php
 * (The app also runs this sweep automatically while people use the site.)
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/../includes/bootstrap.php';
(new Notification())->runExpiryChecks();
echo "Expiry sweep finished at " . date('c') . PHP_EOL;
