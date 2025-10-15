<?php
/**
 * Quick script to check focus keyword for a post
 *
 * Access via: /wp-content/plugins/content-generator-disabled/check-focus-keyword.php?post_id=123
 */

// Load WordPress
$wp_load_path = dirname(dirname(dirname(__DIR__))) . '/wp-load.php';
if (!file_exists($wp_load_path)) {
    die('WordPress not found. Expected at: ' . $wp_load_path);
}
require_once $wp_load_path;

// Check if user is logged in
if (!current_user_can('edit_posts')) {
    wp_die('You do not have permission to access this page.');
}

// Get post ID
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if (!$post_id) {
    die('Please provide post_id parameter. Example: ?post_id=123');
}

// Get post
$post = get_post($post_id);

if (!$post) {
    die('Post not found');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Focus Keyword Check - Post <?php echo $post_id; ?></title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f0f0f1;
        }
        .section {
            background: white;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #2271b1;
        }
        pre {
            background: #1d2327;
            color: #f0f0f1;
            padding: 15px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>Focus Keyword Check</h1>

    <div class="section">
        <h2>Post Information</h2>
        <p><strong>Post ID:</strong> <?php echo $post_id; ?></p>
        <p><strong>Post Title:</strong> <?php echo esc_html($post->post_title); ?></p>
        <p><strong>Post Type:</strong> <?php echo esc_html($post->post_type); ?></p>
    </div>

    <div class="section">
        <h2>Focus Keyword (post_meta)</h2>
        <?php
        $focus_keyword = get_post_meta($post_id, 'focus_keyword', true);
        if ($focus_keyword) {
            echo '<p style="color: #00a32a;"><strong>✅ Found:</strong> ' . esc_html($focus_keyword) . '</p>';
        } else {
            echo '<p style="color: #d63638;"><strong>❌ Not found or empty</strong></p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>Topic (taxonomy)</h2>
        <?php
        $topic_terms = wp_get_object_terms($post_id, 'seo-topic');
        if (!is_wp_error($topic_terms) && !empty($topic_terms)) {
            echo '<p><strong>✅ Found:</strong> ' . esc_html($topic_terms[0]->name) . '</p>';
        } else {
            echo '<p style="color: #d63638;"><strong>❌ Not found</strong></p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>What PromptTemplateEngine would build:</h2>
        <?php
        // Simulate what buildContext() does
        $topic_terms = wp_get_object_terms($post_id, 'seo-topic');
        $page_topic = '';
        if (!is_wp_error($topic_terms) && !empty($topic_terms)) {
            $page_topic = $topic_terms[0]->name;
        }

        $focus_keyword = get_post_meta($post_id, 'focus_keyword', true);
        if (empty($focus_keyword)) {
            $focus_keyword = '';
        }

        $context = array(
            'page_title' => $post->post_title,
            'page_topic' => $page_topic,
            'focus_keyword' => $focus_keyword,
        );
        ?>
        <pre><?php echo htmlspecialchars(print_r($context, true)); ?></pre>
    </div>

    <div class="section">
        <h2>What buildImageContext() would create:</h2>
        <?php
        $image_context = array();

        if (!empty($context['focus_keyword'])) {
            $image_context['focus_keyword'] = $context['focus_keyword'];
        }

        if (!empty($context['page_topic'])) {
            $image_context['topic'] = $context['page_topic'];
        }

        $categories = get_the_terms($post_id, 'category');
        if ($categories && !is_wp_error($categories)) {
            $image_context['category'] = $categories[0]->name;
        }
        ?>
        <pre><?php echo htmlspecialchars(print_r($image_context, true)); ?></pre>
    </div>

    <div class="section">
        <h2>What tags would be extracted:</h2>
        <?php
        function extract_test_keywords($context) {
            $keywords = array();

            if (!empty($context['focus_keyword'])) {
                $keywords[] = $context['focus_keyword'];
            }
            if (!empty($context['topic'])) {
                $keywords[] = $context['topic'];
            }
            if (!empty($context['category'])) {
                $keywords[] = $context['category'];
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

        $extracted_tags = extract_test_keywords($image_context);
        if (!empty($extracted_tags)) {
            echo '<p><strong>Tags:</strong> ' . implode(', ', $extracted_tags) . '</p>';
        } else {
            echo '<p style="color: #d63638;"><strong>❌ No tags extracted!</strong></p>';
            echo '<p>This means no focus_keyword, topic, or category was found.</p>';
        }
        ?>
    </div>

    <div class="section">
        <h2>💡 Action Items</h2>
        <?php if (empty($focus_keyword)): ?>
            <p style="color: #d63638;"><strong>Problem: Focus keyword is not saved!</strong></p>
            <p>The focus keyword needs to be saved in post_meta with key 'focus_keyword'.</p>
            <p>Make sure you fill in the "Focus Keyword" field in the Basic Information section before generating.</p>
        <?php elseif (empty($extracted_tags)): ?>
            <p style="color: #d63638;"><strong>Problem: No keywords could be extracted!</strong></p>
        <?php else: ?>
            <p style="color: #00a32a;"><strong>Context looks good!</strong></p>
            <p>If images still aren't being assigned, check:</p>
            <ul>
                <li>Auto-assignment is enabled in settings</li>
                <li>Images with matching tags exist (use diagnostic tool)</li>
                <li>Check debug.log for error messages</li>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>
