<?php
/**
 * Application configuration.
 * Change the values below to suit your environment.
 */
define('APP_NAME', 'SmartFood Rescue');
define('APP_ENV', 'development');          // 'development' shows errors, 'production' hides them
define('APP_TIMEZONE', 'Asia/Karachi');

define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('UPLOAD_MAX_BYTES', 3 * 1024 * 1024); // per image
define('UPLOAD_MAX_FILES', 4);

// Pretty URLs (/donations/create) need mod_rewrite + the bundled .htaccess.
// If your server doesn't honour .htaccess, set this to false and URLs become index.php?route=donations/create
define('PRETTY_URLS', true);

define('EXPIRY_ALERT_HOURS', 6);            // warn donors/NGOs this many hours before expiry
define('EXPIRY_CHECK_INTERVAL', 300);       // seconds between automatic expiry sweeps
define('NGO_REQUIRES_VERIFICATION', true);  // NGOs must be verified by an admin before requesting food

// Default map centre used when a user has not pinned a location yet (lat, lng, zoom)
define('MAP_DEFAULT', json_encode([24.8607, 67.0011, 11]));

define('SESSION_NAME', 'smartfood_sid');

// Works from http://localhost/smartfood-rescue/ or a virtual host root
define('BASE_URL', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'));

date_default_timezone_set(APP_TIMEZONE);

if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}
