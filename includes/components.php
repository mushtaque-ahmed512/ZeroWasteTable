<?php
/**
 * Small reusable view components (return HTML strings).
 */
function status_badge(string $status): string
{
    $map = [
        'available' => 'success', 'requested' => 'info', 'approved' => 'primary', 'scheduled' => 'primary',
        'collected' => 'warning', 'picked_up' => 'warning', 'delivered' => 'success', 'expired' => 'danger',
        'cancelled' => 'secondary', 'pending' => 'warning', 'rejected' => 'danger', 'active' => 'success',
        'suspended' => 'danger',
    ];
    $colour = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $colour . ' status-badge">' . e(ucwords(str_replace('_', ' ', $status))) . '</span>';
}

/** $icon may be an emoji or a Bootstrap Icons name. */
function stat_card(string $icon, string $label, $value, string $tone = 'primary'): string
{
    $glyph = preg_match('/^[a-z0-9-]+$/', $icon) ? '<i class="bi bi-' . e($icon) . '"></i>' : e($icon);
    return '<div class="col-6 col-xl-3"><div class="card stat-card tone-' . e($tone) . ' h-100"><div class="card-body">'
        . '<span class="stat-icon">' . $glyph . '</span>'
        . '<div class="stat-value">' . e($value) . '</div><div class="stat-label">' . e($label) . '</div>'
        . '</div></div></div>';
}

/** Emoji for a food category, matched by keyword so admin-created categories still get one. */
function category_emoji(string $name): string
{
    $map = ['cooked' => '🍛', 'meal' => '🍛', 'rice' => '🍚', 'bakery' => '🥖', 'bread' => '🥖', 'fruit' => '🍎',
            'vegetable' => '🥦', 'dairy' => '🥛', 'egg' => '🥚', 'packaged' => '📦', 'beverage' => '🥤', 'drink' => '🥤',
            'catering' => '🍱', 'snack' => '🍿', 'dessert' => '🍰', 'sweet' => '🍰', 'meat' => '🍗', 'salad' => '🥗'];
    $n = strtolower($name);
    foreach ($map as $key => $emoji) {
        if (str_contains($n, $key)) return $emoji;
    }
    return '🍽️';
}

/** Emoji for a listing: the title wins (biryani, rice, samosa...), then the category. */
function food_emoji(string $title, string $category = ''): string
{
    $map = ['biryani' => '🍛', 'pulao' => '🍚', 'rice' => '🍚', 'curry' => '🍛', 'karahi' => '🍛', 'bread' => '🥖', 'bun' => '🥖',
            'naan' => '🫓', 'roti' => '🫓', 'paratha' => '🫓', 'samosa' => '🥟', 'pizza' => '🍕', 'burger' => '🍔',
            'sandwich' => '🥪', 'chicken' => '🍗', 'kebab' => '🍢', 'cake' => '🍰', 'pastry' => '🥐', 'cookie' => '🍪',
            'fruit' => '🍎', 'banana' => '🍌', 'vegetable' => '🥦', 'salad' => '🥗', 'milk' => '🥛', 'yoghurt' => '🥛',
            'yogurt' => '🥛', 'egg' => '🥚', 'juice' => '🥤', 'noodle' => '🍜', 'pasta' => '🍝', 'soup' => '🍲', 'sweet' => '🍮'];
    $t = strtolower($title);
    foreach ($map as $key => $emoji) {
        if (str_contains($t, $key)) return $emoji;
    }
    return $category !== '' ? category_emoji($category) : '🍽️';
}

function page_header(string $title, string $subtitle = '', string $actionsHtml = ''): string
{
    return '<div class="page-header d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">'
        . '<div><h1 class="h3 mb-1">' . e($title) . '</h1>' . ($subtitle ? '<p class="text-body-secondary mb-0">' . e($subtitle) . '</p>' : '') . '</div>'
        . ($actionsHtml ? '<div class="d-flex gap-2">' . $actionsHtml . '</div>' : '') . '</div>';
}

function empty_state(string $message, string $icon = '🍽️', string $actionHtml = ''): string
{
    $glyph = preg_match('/^[a-z0-9-]+$/', $icon) ? '<i class="bi bi-' . e($icon) . ' display-5 text-body-tertiary"></i>' : '<span class="em">' . e($icon) . '</span>';
    return '<div class="empty-state text-center py-5">' . $glyph
        . '<p class="mt-3 mb-3 text-body-secondary">' . e($message) . '</p>' . $actionHtml . '</div>';
}

function expiry_badge(string $expiryAt): string
{
    [$label, $tone] = time_left($expiryAt);
    return '<span class="badge text-bg-' . $tone . '"><i class="bi bi-clock me-1"></i>' . e($label) . '</span>';
}

function star_rating(float $avg): string
{
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<i class="bi bi-star' . ($avg >= $i ? '-fill' : ($avg >= $i - 0.5 ? '-half' : '')) . ' text-warning"></i>';
    }
    return $html;
}

function role_label(string $role): string
{
    return ['admin' => 'Administrator', 'donor' => 'Food donor', 'ngo' => 'NGO / Charity', 'volunteer' => 'Volunteer'][$role] ?? $role;
}
