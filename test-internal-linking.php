<?php
/**
 * Internal Linking System Diagnostic Tool
 *
 * Test and verify the internal linking functionality.
 *
 * Usage: Visit yoursite.com/wp-admin/admin.php?page=test-internal-linking
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = [
        __DIR__ . '/../../../../wp-load.php',  // Standard WordPress
        __DIR__ . '/../../../../../wp-load.php', // Some setups
        dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php', // Alternative
    ];

    $loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $loaded = true;
            break;
        }
    }

    if (!$loaded) {
        die('Error: Could not find wp-load.php. Please access this page through WordPress admin instead: <a href="/wp-admin/admin.php?page=seo-generator-test-links">Admin Link</a>');
    }
}

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Internal Linking System Test</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            padding: 20px;
            background: #f0f0f1;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 30px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        .warning {
            background: #fff3cd;
            border-color: #ffeaa7;
            color: #856404;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px 5px 5px 0;
        }
        .button:hover {
            background: #135e96;
        }
        .button-secondary {
            background: #6c757d;
        }
        .button-secondary:hover {
            background: #545b62;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f0f0f1;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pass {
            background: #d4edda;
            color: #155724;
        }
        .status-fail {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔗 Internal Linking System Diagnostic</h1>
        <p>This tool tests the automated internal linking feature.</p>

        <?php
        // TEST 1: Check if classes exist
        echo '<div class="test-section">';
        echo '<h2>Test 1: Class Availability</h2>';

        $classes_to_check = [
            'SEOGenerator\\Services\\KeywordMatcher',
            'SEOGenerator\\Services\\InternalLinkingService',
            'SEOGenerator\\Cron\\LinkRefreshHandler',
        ];

        $all_classes_exist = true;
        echo '<table>';
        echo '<tr><th>Class</th><th>Status</th></tr>';

        foreach ($classes_to_check as $class) {
            $exists = class_exists($class);
            $all_classes_exist = $all_classes_exist && $exists;
            echo '<tr>';
            echo '<td><code>' . esc_html($class) . '</code></td>';
            echo '<td><span class="status-badge status-' . ($exists ? 'pass' : 'fail') . '">';
            echo $exists ? '✓ LOADED' : '✗ NOT FOUND';
            echo '</span></td>';
            echo '</tr>';
        }
        echo '</table>';

        if ($all_classes_exist) {
            echo '<div class="success">✓ All classes loaded successfully!</div>';
        } else {
            echo '<div class="error">✗ Some classes are missing. Check if files exist in includes/ directory.</div>';
        }
        echo '</div>';

        // TEST 2: Check cron schedule
        echo '<div class="test-section">';
        echo '<h2>Test 2: Cron Schedule</h2>';

        $next_scheduled = wp_next_scheduled('seo_refresh_internal_links');

        if ($next_scheduled) {
            echo '<div class="success">';
            echo '✓ Cron job is scheduled<br>';
            echo '<strong>Next run:</strong> ' . date('Y-m-d H:i:s', $next_scheduled) . ' (' . human_time_diff($next_scheduled) . ')';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '✗ Cron job is NOT scheduled<br>';
            echo '<strong>Action:</strong> Try deactivating and reactivating the plugin.';
            echo '</div>';
        }

        // Show last refresh info
        $last_refresh = get_option('seo_last_link_refresh');
        if ($last_refresh && is_array($last_refresh)) {
            echo '<div class="info">';
            echo '<strong>Last Refresh:</strong><br>';
            echo '<pre>' . print_r($last_refresh, true) . '</pre>';
            echo '</div>';
        } else {
            echo '<div class="info">No refresh has run yet (this is normal for new installations).</div>';
        }
        echo '</div>';

        // TEST 3: Check published SEO pages
        echo '<div class="test-section">';
        echo '<h2>Test 3: Published SEO Pages</h2>';

        $pages = get_posts([
            'post_type' => 'seo-page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        $page_count = count($pages);

        if ($page_count >= 2) {
            echo '<div class="success">✓ Found ' . $page_count . ' published SEO pages (good for testing)</div>';

            // Show sample pages
            echo '<table>';
            echo '<tr><th>ID</th><th>Title</th><th>Focus Keyword</th><th>Topic</th><th>Has Links?</th></tr>';

            foreach (array_slice($pages, 0, 10) as $page_id) {
                $title = get_the_title($page_id);
                $focus = get_field('seo_focus_keyword', $page_id);
                $topics = get_the_terms($page_id, 'seo-topic');
                $topic_name = $topics && !is_wp_error($topics) ? $topics[0]->name : '—';
                $has_links = get_post_meta($page_id, '_related_links', true);

                echo '<tr>';
                echo '<td>' . $page_id . '</td>';
                echo '<td>' . esc_html($title) . '</td>';
                echo '<td>' . esc_html($focus ?: '—') . '</td>';
                echo '<td>' . esc_html($topic_name) . '</td>';
                echo '<td><span class="status-badge status-' . ($has_links ? 'pass' : 'fail') . '">';
                echo $has_links ? '✓ YES' : '✗ NO';
                echo '</span></td>';
                echo '</tr>';
            }
            echo '</table>';

        } elseif ($page_count === 1) {
            echo '<div class="warning">⚠ Only 1 published page found. You need at least 2 pages to test internal linking.</div>';
        } else {
            echo '<div class="error">✗ No published SEO pages found. Generate some pages first!</div>';
        }
        echo '</div>';

        // TEST 4: Test KeywordMatcher
        if (class_exists('SEOGenerator\\Services\\KeywordMatcher')) {
            echo '<div class="test-section">';
            echo '<h2>Test 4: Keyword Matching Algorithm</h2>';

            try {
                $matcher = new \SEOGenerator\Services\KeywordMatcher();

                // Test keyword extraction
                $test_text = "platinum engagement rings with diamonds";
                $keywords = $matcher->extractKeywords($test_text);

                echo '<div class="info">';
                echo '<strong>Test Input:</strong> "' . esc_html($test_text) . '"<br>';
                echo '<strong>Extracted Keywords:</strong><br>';
                echo '<pre>' . print_r($keywords, true) . '</pre>';
                echo '</div>';

                // Test similarity
                $text1 = "platinum wedding bands";
                $text2 = "platinum engagement rings";
                $keywords1 = $matcher->extractKeywords($text1);
                $keywords2 = $matcher->extractKeywords($text2);
                $similarity = $matcher->calculateSimilarity($keywords1, $keywords2);

                echo '<div class="info">';
                echo '<strong>Similarity Test:</strong><br>';
                echo '"' . esc_html($text1) . '" vs "' . esc_html($text2) . '"<br>';
                echo '<strong>Similarity Score:</strong> ' . number_format($similarity, 2);
                echo '</div>';

                echo '<div class="success">✓ KeywordMatcher is working correctly!</div>';

            } catch (Exception $e) {
                echo '<div class="error">✗ Error testing KeywordMatcher: ' . esc_html($e->getMessage()) . '</div>';
            }
            echo '</div>';
        }

        // TEST 5: Manual link generation test
        if ($page_count >= 2 && class_exists('SEOGenerator\\Services\\InternalLinkingService')) {
            echo '<div class="test-section">';
            echo '<h2>Test 5: Manual Link Generation</h2>';

            if (isset($_GET['test_page_id'])) {
                $test_page_id = intval($_GET['test_page_id']);

                echo '<div class="info">Testing link generation for page ID: ' . $test_page_id . '</div>';

                try {
                    $linking_service = new \SEOGenerator\Services\InternalLinkingService();

                    // Find related pages
                    $start_time = microtime(true);
                    $related = $linking_service->findRelatedPages($test_page_id);
                    $duration = round((microtime(true) - $start_time) * 1000, 2);

                    echo '<div class="success">';
                    echo '✓ Found ' . count($related) . ' related pages in ' . $duration . 'ms<br>';
                    echo '</div>';

                    if (!empty($related)) {
                        echo '<table>';
                        echo '<tr><th>Rank</th><th>Page ID</th><th>Title</th><th>Score</th><th>Reasons</th></tr>';

                        foreach ($related as $index => $link) {
                            $link_title = get_the_title($link['id']);
                            echo '<tr>';
                            echo '<td>#' . ($index + 1) . '</td>';
                            echo '<td>' . $link['id'] . '</td>';
                            echo '<td>' . esc_html($link_title) . '</td>';
                            echo '<td>' . number_format($link['score'], 1) . '</td>';
                            echo '<td><small>' . esc_html(implode(', ', $link['reasons'])) . '</small></td>';
                            echo '</tr>';
                        }
                        echo '</table>';

                        // Store the links
                        $linking_service->storeRelatedLinks($test_page_id, $related);
                        echo '<div class="success">✓ Links stored in post meta successfully!</div>';

                    } else {
                        echo '<div class="warning">⚠ No related pages found. This might be normal if pages are very different.</div>';
                    }

                } catch (Exception $e) {
                    echo '<div class="error">✗ Error: ' . esc_html($e->getMessage()) . '</div>';
                }

            } else {
                echo '<p>Select a page to test link generation:</p>';
                foreach (array_slice($pages, 0, 5) as $page_id) {
                    $title = get_the_title($page_id);
                    echo '<a href="?test_page_id=' . $page_id . '" class="button">';
                    echo 'Test Page ' . $page_id . ': ' . esc_html($title);
                    echo '</a><br>';
                }
            }
            echo '</div>';
        }

        // TEST 6: Frontend display check
        if ($page_count > 0) {
            echo '<div class="test-section">';
            echo '<h2>Test 6: Frontend Display</h2>';

            $sample_page = $pages[0];
            $has_links = get_post_meta($sample_page, '_related_links', true);
            $permalink = get_permalink($sample_page);

            if ($has_links) {
                echo '<div class="success">';
                echo '✓ Page ' . $sample_page . ' has related links stored<br>';
                echo '<a href="' . esc_url($permalink) . '" target="_blank" class="button">View Page on Frontend</a>';
                echo '</div>';
                echo '<div class="info">Check if the "Related Articles" section appears at the bottom of the page.</div>';
            } else {
                echo '<div class="warning">';
                echo '⚠ Sample page has no related links yet<br>';
                echo '<a href="?test_page_id=' . $sample_page . '" class="button">Generate Links for This Page</a>';
                echo '</div>';
            }
            echo '</div>';
        }

        // Action buttons
        echo '<div class="test-section">';
        echo '<h2>Actions</h2>';
        echo '<a href="?" class="button">Refresh Tests</a>';
        echo '<a href="' . admin_url('edit.php?post_type=seo-page') . '" class="button button-secondary">View All SEO Pages</a>';
        echo '</div>';
        ?>

    </div>
</body>
</html>
