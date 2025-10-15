<?php
/**
 * Quick diagnostic script to check hero image assignment
 *
 * Usage: Place this in the plugin root and visit:
 * /wp-content/plugins/content-generator-disabled/check-hero-image.php?post_id=123
 */

// Load WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Check if user is logged in
if (!is_user_logged_in()) {
    die('Please log in to WordPress first');
}

// Get post ID from URL
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if (!$post_id) {
    die('Please provide post_id parameter. Example: ?post_id=123');
}

// Get the post
$post = get_post($post_id);

if (!$post) {
    die('Post not found');
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hero Image Diagnostic - Post <?php echo $post_id; ?></title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .section { background: white; padding: 15px; margin: 15px 0; border-left: 4px solid #2271b1; }
        .success { border-left-color: #00a32a; }
        .error { border-left-color: #d63638; }
        pre { background: #1d2327; color: #f0f0f1; padding: 15px; overflow-x: auto; }
        img { max-width: 300px; border: 2px solid #ddd; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Hero Image Diagnostic</h1>

    <div class="section">
        <h2>Post Information</h2>
        <p><strong>Post ID:</strong> <?php echo $post_id; ?></p>
        <p><strong>Post Title:</strong> <?php echo esc_html($post->post_title); ?></p>
        <p><strong>Post Type:</strong> <?php echo esc_html($post->post_type); ?></p>
        <p><strong>Post Status:</strong> <?php echo esc_html($post->post_status); ?></p>
    </div>

    <?php
    // Check if ACF is active
    if (!function_exists('get_field')) {
        echo '<div class="section error">';
        echo '<h2>❌ ACF Not Active</h2>';
        echo '<p>Advanced Custom Fields plugin is not active or get_field() function not available.</p>';
        echo '</div>';
        die();
    }

    // Get hero_image field value
    $hero_image_id = get_field('hero_image', $post_id);

    echo '<div class="section ' . ($hero_image_id ? 'success' : 'error') . '">';
    echo '<h2>' . ($hero_image_id ? '✅' : '❌') . ' Hero Image Field Value</h2>';

    if ($hero_image_id) {
        echo '<p><strong>Image ID:</strong> ' . $hero_image_id . '</p>';

        // Get image details
        $image_url = wp_get_attachment_url($hero_image_id);
        $image_title = get_the_title($hero_image_id);
        $image_alt = get_post_meta($hero_image_id, '_wp_attachment_image_alt', true);

        echo '<p><strong>Image Title:</strong> ' . esc_html($image_title) . '</p>';
        echo '<p><strong>Image Alt:</strong> ' . esc_html($image_alt) . '</p>';
        echo '<p><strong>Image URL:</strong> <a href="' . esc_url($image_url) . '" target="_blank">' . esc_html($image_url) . '</a></p>';

        if ($image_url) {
            echo '<p><strong>Image Preview:</strong></p>';
            echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '">';
        }
    } else {
        echo '<p><strong>Value:</strong> ' . var_export($hero_image_id, true) . '</p>';
        echo '<p style="color: #d63638;">No image assigned to hero_image field.</p>';
    }
    echo '</div>';

    // Check raw post meta
    echo '<div class="section">';
    echo '<h2>Raw Post Meta</h2>';
    $all_meta = get_post_meta($post_id);

    // Filter for hero-related meta
    $hero_meta = array();
    foreach ($all_meta as $key => $value) {
        if (stripos($key, 'hero') !== false) {
            $hero_meta[$key] = $value;
        }
    }

    if (!empty($hero_meta)) {
        echo '<pre>' . print_r($hero_meta, true) . '</pre>';
    } else {
        echo '<p>No hero-related meta found.</p>';
    }
    echo '</div>';

    // Check all ACF fields for this post
    echo '<div class="section">';
    echo '<h2>All ACF Fields</h2>';
    $all_fields = get_fields($post_id);

    if ($all_fields) {
        echo '<pre>' . print_r($all_fields, true) . '</pre>';
    } else {
        echo '<p>No ACF fields found for this post.</p>';
    }
    echo '</div>';

    // Check auto-assignment logs
    echo '<div class="section">';
    echo '<h2>Recent Error Logs (Last 50 Lines)</h2>';
    $log_file = WP_CONTENT_DIR . '/debug.log';

    if (file_exists($log_file)) {
        $logs = file($log_file);
        $recent_logs = array_slice($logs, -50);

        // Filter for SEO Generator Auto-Assignment logs
        $filtered_logs = array_filter($recent_logs, function($line) use ($post_id) {
            return stripos($line, 'Auto-Assignment') !== false ||
                   stripos($line, 'Post: ' . $post_id) !== false;
        });

        if (!empty($filtered_logs)) {
            echo '<pre>' . implode('', $filtered_logs) . '</pre>';
        } else {
            echo '<p>No auto-assignment logs found for this post.</p>';
        }
    } else {
        echo '<p>Debug log not found. Enable WP_DEBUG_LOG in wp-config.php to see logs.</p>';
    }
    echo '</div>';
    ?>

    <div class="section">
        <h2>📋 Next Steps</h2>
        <?php if (!$hero_image_id): ?>
            <p><strong>Image is NOT assigned.</strong> Possible reasons:</p>
            <ul>
                <li>Auto-assignment is not finding matching images</li>
                <li>No images with matching tags exist</li>
                <li>Auto-assignment is disabled in settings</li>
            </ul>
            <p>Try running the image diagnostic tool or check debug logs above.</p>
        <?php else: ?>
            <p><strong>Image IS assigned in database!</strong></p>
            <p>If you don't see it in the admin editor, this is a frontend React issue.</p>
            <p>Check if the image appears on the actual page frontend:</p>
            <p><a href="<?php echo get_permalink($post_id); ?>" target="_blank">View Page →</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
