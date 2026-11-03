<?php
/* ---------- output / urls ---------- */
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '', array $query = []): string
{
    $path = trim($path, '/');
    if (PRETTY_URLS) {
        $u = BASE_URL . '/' . $path;
    } else {
        $u = BASE_URL . '/index.php';
        if ($path !== '') {
            $query = ['route' => $path] + $query;
        }
    }
    return $query ? $u . (str_contains($u, '?') ? '&' : '?') . http_build_query($query) : $u;
}

function asset(string $path): string      { return BASE_URL . '/assets/' . ltrim($path, '/'); }
function upload_url(string $path): string { return BASE_URL . '/uploads/' . ltrim($path, '/'); }

function redirect(string $path, array $query = []): void
{
    header('Location: ' . url($path, $query));
    exit;
}

/* ---------- request input ---------- */
function post(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function get(string $key, string $default = ''): string
{
    $v = $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

function old(string $key, $default = '')
{
    return $GLOBALS['__old'][$key] ?? $default;
}

/* ---------- flash messages ---------- */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function pull_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    $sent = $_POST['_csrf'] ?? '';
    return is_string($sent) && hash_equals($_SESSION['csrf'] ?? '', $sent);
}

/* ---------- auth ---------- */
function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function has_role(string ...$roles): bool
{
    return user() !== null && in_array(user()['role'], $roles, true);
}

function login_user(array $sessionData): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = $sessionData;
}

/* ---------- formatting ---------- */
function fmt_dt(?string $dt, string $format = 'd M Y, h:i A'): string
{
    return $dt ? date($format, strtotime($dt)) : '—';
}

/** @return array{0:string,1:string} label + bootstrap colour */
function time_left(string $dt): array
{
    $s = strtotime($dt) - time();
    if ($s <= 0) {
        return ['Expired', 'danger'];
    }
    $h = intdiv($s, 3600);
    $m = intdiv($s % 3600, 60);
    $label = $h >= 24 ? intdiv($h, 24) . 'd ' . ($h % 24) . 'h' : "{$h}h {$m}m";
    return [$label . ' left', $h < 2 ? 'danger' : ($h < 6 ? 'warning' : 'success')];
}

function qty(float $n, string $unit): string
{
    return rtrim(rtrim(number_format($n, 2), '0'), '.') . ' ' . $unit;
}

function distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
}

function valid_coords($lat, $lng): bool
{
    return is_numeric($lat) && is_numeric($lng) && abs((float)$lat) <= 90 && abs((float)$lng) <= 180
        && !((float)$lat === 0.0 && (float)$lng === 0.0);
}

/* ---------- uploads ---------- */
/**
 * Validates and stores uploaded images from a multi-file input.
 * @return string[] paths relative to /uploads
 */
function save_images(?array $files): array
{
    if (!$files || empty($files['name']) || !is_array($files['name'])) {
        return [];
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $dir = UPLOAD_PATH . '/donations';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $saved = [];
    foreach ($files['name'] as $i => $original) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if (count($saved) >= UPLOAD_MAX_FILES) {
            break;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK || $files['size'][$i] > UPLOAD_MAX_BYTES) {
            throw new RuntimeException('Each image must be under ' . (UPLOAD_MAX_BYTES / 1048576) . ' MB.');
        }
        $mime = $finfo->file($files['tmp_name'][$i]);
        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Only JPG, PNG or WebP images are allowed.');
        }
        $name = bin2hex(random_bytes(10)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($files['tmp_name'][$i], "$dir/$name")) {
            throw new RuntimeException('Could not save the uploaded image.');
        }
        $saved[] = "donations/$name";
    }
    return $saved;
}

/* ---------- mail ---------- */
function absolute_url(string $path = '', array $query = []): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($path, $query);
}

/** Plain-text mail via PHP's mail(). XAMPP has no mail server by default, so this usually returns false locally. */
function send_mail(string $to, string $subject, string $body): bool
{
    $headers = 'From: ' . APP_NAME . ' <no-reply@localhost>' . "\r\n" . 'Content-Type: text/plain; charset=UTF-8';
    return (bool)@mail($to, $subject, $body, $headers);
}
