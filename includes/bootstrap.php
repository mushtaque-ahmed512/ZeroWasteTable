<?php
/**
 * Boots the application: config, autoloader, helpers, session.
 */
require_once __DIR__ . '/../config/config.php';

spl_autoload_register(function (string $class): void {
    foreach (['includes', 'controllers', 'models', 'middleware'] as $dir) {
        $file = ROOT_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/components.php';

session_name(SESSION_NAME);
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

// Old form input survives exactly one redirect
$GLOBALS['__old'] = $_SESSION['old'] ?? [];
unset($_SESSION['old']);
