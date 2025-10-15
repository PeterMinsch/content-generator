<?php
/**
 * Quick Image Matching Diagnostic Page
 *
 * Access via: /wp-admin/admin.php?page=seo-generator-image-diagnostic
 */

// Load WordPress
// Path: plugins/content-generator-disabled -> plugins -> wp-content -> public -> wp-load.php
$wp_load_path = dirname(dirname(dirname(__DIR__))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    die('WordPress not found. Expected at: ' . $wp_load_path);
}
require_once $wp_load_path;

// Check if user is logged in and has permission
if (!current_user_can('edit_posts')) {
    wp_die('You do not have permission to access this page.');
}

// Get test parameters
$focus_keyword = isset($_GET['focus_keyword']) ? sanitize_text_field($_GET['focus_keyword']) : 'platinum wedding bands';
$page_title = isset($_GET['page_title']) ? sanitize_text_field($_GET['page_title']) : 'Platinum Wedding Rings';
$topic = isset($_GET['topic']) ? sanitize_text_field($_GET['topic']) : '';

// Build context
$context = array(
    'focus_keyword' => $focus_keyword,
    'page_title' => $page_title,
    'topic' => $topic,
);

// Extract keywords (same logic as ImageMatchingService)
function extract_keywords_for_diagnostic($context) {
    $keywords = array();

    if (!empty($context['focus_keyword'])) {
        $keywords[] = $context['focus_keyword'];
    }
    if (!empty($context['topic'])) {
        $keywords[] = $context['topic'];
    }

    $tags = array();
    foreach ($keywords as $keyword) {
        $words = preg_split('/[\s\-_]+/', $keyword);
        foreach ($words as $word) {
            $slug = sanitize_title($word);
            if (!empty($slug) && strlen($slug) > 2) {
                $tags[] = $slug;
            }
        }
    }

    return array_unique($tags);
}

$extracted_tags = extract_keywords_for_diagnostic($context);

// Check image library stats
$all_images = new WP_Query(array(
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'post_mime_type' => 'image',
    'posts_per_page' => -1,
    'fields' => 'ids',
));

$tagged_images = new WP_Query(array(
    'post_type' => 'attachment',
    'post_status' => 'inherit',
    'post_mime_type' => 'image',
    'posts_per_page' => -1,
    'fields' => 'ids',
    'tax_query' => array(
        array(
            'taxonomy' => 'image_tag',
            'operator' => 'EXISTS',
        ),
    ),
));

$all_tags = get_terms(array(
    'taxonomy' => 'image_tag',
    'hide_empty' => false,
));

// Try matching
$matching_results = array();
if (!empty($extracted_tags)) {
    $query = new WP_Query(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_mime_type' => 'image',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'image_tag',
                'field' => 'slug',
                'terms' => $extracted_tags,
                'operator' => 'AND',
            ),
        ),
    ));
    $matching_results['all_tags'] = $query->posts;

    if (count($extracted_tags) >= 2) {
        $query2 = new WP_Query(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'image_tag',
                    'field' => 'slug',
                    'terms' => array_slice($extracted_tags, 0, 2),
                    'operator' => 'AND',
                ),
            ),
        ));
        $matching_results['first_2'] = $query2->posts;
    }

    if (count($extracted_tags) >= 1) {
        $query3 = new WP_Query(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'image_tag',
                    'field' => 'slug',
                    'terms' => array_slice($extracted_tags, 0, 1),
                    'operator' => 'AND',
                ),
            ),
        ));
        $matching_results['first_1'] = $query3->posts;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Matching Diagnostic</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f0f0f1;
            padding: 20px;
        }
        .wrap {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 { color: #1d2327; border-bottom: 2px solid #2271b1; padding-bottom: 10px; }
        h2 { color: #1d2327; margin-top: 30px; }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f6f7f7;
            border-left: 4px solid #2271b1;
            border-radius: 4px;
        }
        .success { border-left-color: #00a32a; }
        .error { border-left-color: #d63638; }
        .warning { border-left-color: #dba617; }
        .stat {
            display: inline-block;
            margin: 10px 20px 10px 0;
            padding: 10px 20px;
            background: white;
            border-radius: 4px;
            border: 1px solid #dcdcde;
        }
        .stat strong {
            display: block;
            font-size: 24px;
            color: #2271b1;
        }
        .tag {
            display: inline-block;
            background: #2271b1;
            color: white;
            padding: 4px 12px;
            margin: 4px;
            border-radius: 12px;
            font-size: 12px;
        }
        form {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin: 5px 0 15px 0;
            border: 1px solid #8c8f94;
            border-radius: 4px;
        }
        button {
            background: #2271b1;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover { background: #135e96; }
        .image-sample {
            display: inline-block;
            margin: 10px;
            padding: 10px;
            background: white;
            border: 1px solid #dcdcde;
            border-radius: 4px;
            text-align: center;
        }
        .image-sample img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>🔍 Image Matching Diagnostic</h1>

        <form method="get">
            <input type="hidden" name="page" value="seo-generator-image-diagnostic">
            <label>
                <strong>Focus Keyword:</strong>
                <input type="text" name="focus_keyword" value="<?php echo esc_attr($focus_keyword); ?>">
            </label>
            <label>
                <strong>Page Title:</strong>
                <input type="text" name="page_title" value="<?php echo esc_attr($page_title); ?>">
            </label>
            <label>
                <strong>Topic:</strong>
                <input type="text" name="topic" value="<?php echo esc_attr($topic); ?>">
            </label>
            <button type="submit">Run Diagnostic</button>
        </form>

        <h2>📋 Input Context</h2>
        <div class="section">
            <p><strong>Focus Keyword:</strong> <?php echo esc_html($focus_keyword); ?></p>
            <p><strong>Page Title:</strong> <?php echo esc_html($page_title); ?></p>
            <p><strong>Topic:</strong> <?php echo esc_html($topic ?: 'None'); ?></p>
        </div>

        <h2>🏷️ Extracted Tags</h2>
        <div class="section">
            <p>The system extracted <strong><?php echo count($extracted_tags); ?></strong> tags:</p>
            <?php foreach ($extracted_tags as $tag): ?>
                <span class="tag"><?php echo esc_html($tag); ?></span>
            <?php endforeach; ?>
        </div>

        <h2>📚 Image Library Status</h2>
        <div class="section <?php echo count($tagged_images->posts) > 0 ? 'success' : 'error'; ?>">
            <div class="stat">
                <strong><?php echo count($all_images->posts); ?></strong>
                <span>Total Images</span>
            </div>
            <div class="stat">
                <strong><?php echo count($tagged_images->posts); ?></strong>
                <span>Tagged Images</span>
            </div>

            <?php if (count($all_images->posts) === 0): ?>
                <p style="color: #d63638; margin-top: 15px;">
                    <strong>⚠️ No images in media library!</strong> Upload images first.
                </p>
            <?php elseif (count($tagged_images->posts) === 0): ?>
                <p style="color: #d63638; margin-top: 15px;">
                    <strong>⚠️ No images with tags!</strong> You need to add tags to images using the "image_tag" taxonomy.
                </p>
                <p>To fix: Go to <a href="<?php echo admin_url('admin.php?page=seo-generator-image-library'); ?>">SEO Image Library</a> and upload images with tags.</p>
            <?php endif; ?>

            <h3>Available Tags (<?php echo count($all_tags); ?>):</h3>
            <?php if (!empty($all_tags)): ?>
                <?php foreach ($all_tags as $term): ?>
                    <span class="tag"><?php echo esc_html($term->name); ?> (<?php echo $term->count; ?>)</span>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="color: #d63638;">No tags found! Upload images through the SEO Image Library to create tags.</p>
            <?php endif; ?>
        </div>

        <h2>🎯 Matching Results</h2>
        <?php
        $found_match = false;
        foreach ($matching_results as $attempt => $images) {
            if (!empty($images)) {
                $found_match = true;
                break;
            }
        }
        ?>
        <div class="section <?php echo $found_match ? 'success' : 'error'; ?>">
            <?php if ($found_match): ?>
                <p style="color: #00a32a;"><strong>✅ SUCCESS! Found matching images</strong></p>
            <?php else: ?>
                <p style="color: #d63638;"><strong>❌ No matching images found</strong></p>
            <?php endif; ?>

            <h3>Matching Attempts:</h3>
            <ul>
                <li>
                    <strong>All tags (<?php echo implode(', ', $extracted_tags); ?>):</strong>
                    Found <?php echo count($matching_results['all_tags'] ?? []); ?> images
                </li>
                <?php if (isset($matching_results['first_2'])): ?>
                    <li>
                        <strong>First 2 tags (<?php echo implode(', ', array_slice($extracted_tags, 0, 2)); ?>):</strong>
                        Found <?php echo count($matching_results['first_2']); ?> images
                    </li>
                <?php endif; ?>
                <?php if (isset($matching_results['first_1'])): ?>
                    <li>
                        <strong>First 1 tag (<?php echo implode(', ', array_slice($extracted_tags, 0, 1)); ?>):</strong>
                        Found <?php echo count($matching_results['first_1']); ?> images
                    </li>
                <?php endif; ?>
            </ul>

            <?php if ($found_match): ?>
                <h3>Matching Images:</h3>
                <?php
                $display_images = array();
                foreach ($matching_results as $images) {
                    if (!empty($images)) {
                        $display_images = array_merge($display_images, $images);
                    }
                }
                $display_images = array_unique($display_images);
                $display_images = array_slice($display_images, 0, 10); // Show first 10

                foreach ($display_images as $image_id):
                    $image_url = wp_get_attachment_url($image_id);
                    $image_title = get_the_title($image_id);
                    $image_tags = wp_get_object_terms($image_id, 'image_tag');
                ?>
                    <div class="image-sample">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($image_title); ?>">
                        <p><strong><?php echo esc_html($image_title); ?></strong></p>
                        <p><small>ID: <?php echo $image_id; ?></small></p>
                        <?php if (!empty($image_tags)): ?>
                            <?php foreach ($image_tags as $tag): ?>
                                <span class="tag"><?php echo esc_html($tag->name); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h2>💡 What To Do</h2>
        <div class="section">
            <?php if (count($all_images->posts) === 0): ?>
                <p><strong>Step 1:</strong> Upload images to your media library.</p>
            <?php elseif (count($tagged_images->posts) === 0): ?>
                <p><strong>Step 1:</strong> Go to <a href="<?php echo admin_url('admin.php?page=seo-generator-image-library'); ?>">SEO Image Library</a></p>
                <p><strong>Step 2:</strong> Upload images and assign tags like "platinum", "wedding", "bands", etc.</p>
                <p><strong>Step 3:</strong> Make sure tags match the keywords in your focus keyword.</p>
            <?php elseif (!$found_match): ?>
                <p><strong>Problem:</strong> You have tagged images, but none match your keywords.</p>
                <p><strong>Your keywords:</strong> <?php echo implode(', ', $extracted_tags); ?></p>
                <p><strong>Available tags:</strong> <?php echo implode(', ', wp_list_pluck($all_tags, 'name')); ?></p>
                <p><strong>Solution:</strong> Upload images with tags that match your keywords, or adjust your focus keyword to match existing tags.</p>
            <?php else: ?>
                <p><strong>✅ Everything looks good!</strong> Images should auto-assign when you generate content.</p>
                <p>If auto-assignment still isn't working, check the debug.log file for errors.</p>
            <?php endif; ?>
        </div>

        <p style="margin-top: 30px;">
            <a href="<?php echo admin_url('admin.php?page=seo-generator-image-library'); ?>" class="button button-primary">Go to Image Library</a>
            <a href="<?php echo admin_url('upload.php'); ?>" class="button">Go to Media Library</a>
        </p>
    </div>
</body>
</html>
