# Alt Text Generation Implementation Plan

**Goal:** Auto-generate alt text for images in a user-friendly way that avoids blocking/latency issues during image selection and "Generate All Blocks" operations.

**Strategy:** Tiered approach - start simple, add complexity only if needed.

---

## ✅ Tier 1: Deduplication & Caching (COMPLETE)

**Status:** ✅ **IMPLEMENTED**

**Implementation Time:** 2-3 hours

### What It Does

- First time an image is used → Generate alt text with AI (takes 5-10 seconds)
- Save the result in metadata (`_ai_generated_alt_text`)
- Next time same image is used → Read from metadata (instant, free)
- Prevents duplicate API calls for the same image

### Code Implementation

```php
// In ImageMatchingService::assignImageWithMetadata()
private function generateAltTextWithAI(int $image_id, array $context): string {
    // Check cache first
    $cached_alt = get_post_meta($image_id, '_ai_generated_alt_text', true);

    if (!empty($cached_alt)) {
        return $cached_alt; // ✅ Use cached
    }

    // Generate fresh alt text
    $alt_text = $this->openai_service->generateAltText($metadata);

    // Cache it
    update_post_meta($image_id, '_ai_generated_alt_text', $alt_text);
    update_post_meta($image_id, '_ai_alt_text_generated_at', time());

    return $alt_text;
}
```

### Benefits

- ✅ Prevents duplicate API calls for same image
- ✅ "Generate All Blocks" on 10 pages reusing same images = 9x cost savings
- ✅ Simple, no dependencies, minimal code
- ✅ Still uses context-aware generation (better quality)

### Tradeoffs

- ❌ First generation still takes 5-10 seconds during block creation
- ⚠️ Does NOT auto-generate on upload

### User Experience Timeline

1. User uploads 100 images → Nothing happens (instant upload)
2. User runs "Generate All Blocks" → Generates alt text for images it uses (5-10 sec delay)
3. User runs "Generate All Blocks" again on another page using same images → Instant (uses cached alt text)

---

## Tier 2: Manual Bulk Pre-Generation

**Status:** 📋 **PLANNED**

**Implementation Time:** 5-8 hours total (includes Tier 1)

### What It Adds

Everything from Tier 1, plus:
- Bulk action in Media Library: "Generate AI Alt Text"
- User manually selects images and triggers generation
- Process in batches (10-20 at a time)
- Progress bar showing status
- Badge indicators in Media Library showing alt text status

### Implementation Steps

#### 2.1 Metadata Status Field

Add status tracking to metadata:
- `_ai_alt_text_status`: Values: `none`, `pending`, `processing`, `completed`, `failed`

```php
function get_ai_alt_text_status($attachment_id) {
    return get_post_meta($attachment_id, '_ai_alt_text_status', true) ?: 'none';
}

function set_ai_alt_text_status($attachment_id, $status) {
    update_post_meta($attachment_id, '_ai_alt_text_status', $status);
}
```

#### 2.2 Bulk Action in Media Library

```php
// Add bulk action dropdown option
add_filter('bulk_actions-upload', function($actions) {
    $actions['generate_ai_alt_text'] = __('Generate AI Alt Text', 'content-generator');
    return $actions;
});

// Handle bulk action
add_filter('handle_bulk_actions-upload', function($redirect, $action, $post_ids) {
    if ($action !== 'generate_ai_alt_text') {
        return $redirect;
    }

    // Queue images for processing
    foreach ($post_ids as $attachment_id) {
        set_ai_alt_text_status($attachment_id, 'pending');
    }

    // Schedule background processing
    wp_schedule_single_event(time(), 'process_ai_alt_text_queue', [$post_ids]);

    return add_query_arg('generated_alt_text', count($post_ids), $redirect);
}, 10, 3);
```

#### 2.3 Background Processing

```php
add_action('process_ai_alt_text_queue', function($attachment_ids) {
    $batch_size = 10;
    $batches = array_chunk($attachment_ids, $batch_size);

    foreach ($batches as $batch) {
        // Process batch in parallel
        foreach ($batch as $attachment_id) {
            set_ai_alt_text_status($attachment_id, 'processing');

            try {
                $alt_text = generate_ai_alt_text_for_image($attachment_id);
                save_ai_alt_text($attachment_id, $alt_text);
                set_ai_alt_text_status($attachment_id, 'completed');
            } catch (Exception $e) {
                set_ai_alt_text_status($attachment_id, 'failed');
                update_post_meta($attachment_id, '_ai_alt_text_error', $e->getMessage());
            }
        }
    }
});
```

#### 2.4 UI Indicators

**Media Library Grid View - Badge Overlay:**

```php
add_filter('wp_prepare_attachment_for_js', function($response, $attachment) {
    $status = get_ai_alt_text_status($attachment->ID);
    $response['aiAltTextStatus'] = $status;
    return $response;
}, 10, 2);
```

```css
/* Badge styles */
.attachment .ai-alt-text-badge {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
}

.ai-alt-text-badge.completed {
    background: #00a32a;
    color: white;
}

.ai-alt-text-badge.processing {
    background: #f0b849;
    color: white;
}

.ai-alt-text-badge.failed {
    background: #d63638;
    color: white;
}
```

**Media Library List View - Status Column:**

```php
add_filter('manage_media_columns', function($columns) {
    $columns['ai_alt_text'] = __('AI Alt Text', 'content-generator');
    return $columns;
});

add_action('manage_media_custom_column', function($column_name, $attachment_id) {
    if ($column_name === 'ai_alt_text') {
        $status = get_ai_alt_text_status($attachment_id);
        $labels = [
            'none' => '—',
            'pending' => '⏳ Pending',
            'processing' => '⏳ Generating...',
            'completed' => '✅ Ready',
            'failed' => '⚠️ Failed'
        ];
        echo $labels[$status] ?? '—';
    }
}, 10, 2);
```

#### 2.5 Progress Notification

```php
add_action('admin_notices', function() {
    if (isset($_GET['generated_alt_text'])) {
        $count = intval($_GET['generated_alt_text']);
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . sprintf(__('Queued %d images for AI alt text generation.', 'content-generator'), $count) . '</p>';
        echo '</div>';
    }
});
```

### Benefits

- ✅ User controls when to spend money on API calls
- ✅ Only pre-generates for images you'll actually use
- ✅ "Generate All Blocks" becomes instant (all alt text already cached)
- ✅ Clear visual indicators of status
- ✅ Still relatively simple (8 hours vs 27 hours)

### Tradeoffs

- ⚠️ Requires manual user action (not fully automatic)
- ⚠️ Users must remember to run bulk action before "Generate All Blocks"

### User Experience Timeline

1. User uploads 100 images to SEO Library → Upload completes instantly
2. User selects all images in Media Library → Clicks "Generate AI Alt Text" bulk action
3. Background processing starts → Takes 1-2 minutes, shows progress
4. Badges update to show ✅ when complete
5. User runs "Generate All Blocks" → Instant (all alt text already cached)

---

## Tier 3: Automatic Background Generation

**Status:** 📋 **PLANNED** (Only if needed for high-volume use cases)

**Implementation Time:** 20-27 hours

### What It Adds

Everything from Tier 2, plus:
- Automatic generation immediately after upload (no manual triggering)
- Real-time progress indicators
- Advanced queue management
- Priority queue system for "Generate All Blocks" vs background jobs

### When to Use This

Only implement if you have one of these use cases:
- ✅ Building CSV bulk import feature (generating 100+ pages automatically)
- ✅ Budget for auto-generating alt text for every upload
- ✅ Want premium "set it and forget it" experience
- ✅ High-volume multi-user environment

### Implementation Phases

#### Phase 1: Metadata Storage Infrastructure (2-3 hours)

**1.1 Enhanced metadata structure**
- `_ai_generated_alt_text`: The generated alt text
- `_ai_alt_text_status`: `pending`, `processing`, `completed`, `failed`
- `_ai_alt_text_generated_at`: Timestamp
- `_ai_alt_text_error`: Error message if failed
- `_ai_alt_text_priority`: `low` (background), `high` ("Generate All Blocks")

**1.2 Helper functions**
```php
function has_ai_alt_text($attachment_id);
function get_ai_alt_text($attachment_id);
function save_ai_alt_text($attachment_id, $alt_text);
function get_ai_alt_text_status($attachment_id);
function set_ai_alt_text_status($attachment_id, $status);
function get_ai_alt_text_priority($attachment_id);
function set_ai_alt_text_priority($attachment_id, $priority);
```

#### Phase 2: Background Processing Queue (3-4 hours)

**2.1 Hook into upload process**
```php
add_action('add_attachment', function($attachment_id) {
    // Only auto-generate for SEO Library images (optional filter)
    if (has_term('seo-library', 'category', $attachment_id)) {
        set_ai_alt_text_status($attachment_id, 'pending');
        set_ai_alt_text_priority($attachment_id, 'low');

        // Queue for background processing
        wp_schedule_single_event(time() + 10, 'process_ai_alt_text_background');
    }
});
```

**2.2 Background worker**
```php
add_action('process_ai_alt_text_background', function() {
    // Get pending images (low priority)
    $pending = get_posts([
        'post_type' => 'attachment',
        'posts_per_page' => 10,
        'meta_query' => [
            [
                'key' => '_ai_alt_text_status',
                'value' => 'pending'
            ],
            [
                'key' => '_ai_alt_text_priority',
                'value' => 'low'
            ]
        ]
    ]);

    foreach ($pending as $attachment) {
        set_ai_alt_text_status($attachment->ID, 'processing');

        try {
            $alt_text = generate_ai_alt_text_for_image($attachment->ID);
            save_ai_alt_text($attachment->ID, $alt_text);
            set_ai_alt_text_status($attachment->ID, 'completed');
        } catch (Exception $e) {
            set_ai_alt_text_status($attachment->ID, 'failed');
            update_post_meta($attachment->ID, '_ai_alt_text_error', $e->getMessage());
        }
    }

    // Reschedule if more pending
    $remaining = count_pending_ai_alt_text();
    if ($remaining > 0) {
        wp_schedule_single_event(time() + 60, 'process_ai_alt_text_background');
    }
});
```

#### Phase 3: Parallel Processing (2-3 hours)

**3.1 Concurrent API calls**
- Use WordPress HTTP API with async requests
- Process 10-20 images simultaneously
- Respect rate limits

**3.2 Implementation**
```php
function process_batch_parallel($attachment_ids) {
    $requests = [];

    foreach ($attachment_ids as $attachment_id) {
        $requests[] = [
            'url' => rest_url('/content-generator/v1/generate-alt-text/' . $attachment_id),
            'type' => 'GET'
        ];
    }

    // Send all requests in parallel
    $responses = wp_remote_multi($requests);

    // Process responses
    foreach ($responses as $index => $response) {
        $attachment_id = $attachment_ids[$index];

        if (is_wp_error($response)) {
            set_ai_alt_text_status($attachment_id, 'failed');
        } else {
            $alt_text = json_decode(wp_remote_retrieve_body($response))->alt_text;
            save_ai_alt_text($attachment_id, $alt_text);
            set_ai_alt_text_status($attachment_id, 'completed');
        }
    }
}
```

#### Phase 4: UI Indicators (4-5 hours)

**4.1 Real-time progress notification**
```php
// Admin bar indicator
add_action('admin_bar_menu', function($wp_admin_bar) {
    $pending_count = count_pending_ai_alt_text();
    $processing_count = count_processing_ai_alt_text();

    if ($pending_count > 0 || $processing_count > 0) {
        $wp_admin_bar->add_node([
            'id' => 'ai-alt-text-progress',
            'title' => sprintf(
                '⏳ Generating alt text... %d/%d',
                $processing_count,
                $pending_count + $processing_count
            ),
            'href' => admin_url('upload.php')
        ]);
    }
});
```

**4.2 AJAX polling for updates**
```javascript
// Update UI every 5 seconds
setInterval(() => {
    fetch('/wp-json/content-generator/v1/alt-text-status')
        .then(res => res.json())
        .then(data => {
            updateProgressBar(data.completed, data.total);
            updateMediaLibraryBadges(data.images);
        });
}, 5000);
```

#### Phase 5: Integration with "Generate All Blocks" (1-2 hours)

**5.1 Check metadata first, generate if missing**
```php
// In ImageMatchingService
private function generateAltTextWithAI(int $image_id, array $context): string {
    // Check metadata first
    if (has_ai_alt_text($image_id)) {
        return get_ai_alt_text($image_id); // ✅ Use cached
    }

    // Not cached - generate with high priority
    set_ai_alt_text_priority($image_id, 'high');
    $alt_text = $this->openai_service->generateAltText($metadata);
    save_ai_alt_text($image_id, $alt_text);

    return $alt_text;
}
```

#### Phase 6: Deduplication & Error Handling (2-3 hours)

**6.1 Prevent duplicate generation**
```php
function generate_ai_alt_text_for_image($attachment_id) {
    // Check if already completed or processing
    $status = get_ai_alt_text_status($attachment_id);

    if (in_array($status, ['completed', 'processing'])) {
        return get_ai_alt_text($attachment_id);
    }

    // Generate...
}
```

**6.2 Retry failed generations**
```php
// Add "Retry" button in media library for failed images
add_action('attachment_submitbox_misc_actions', function($post) {
    $status = get_ai_alt_text_status($post->ID);

    if ($status === 'failed') {
        echo '<div class="misc-pub-section">';
        echo '<button type="button" class="button" onclick="retryAltTextGeneration(' . $post->ID . ')">';
        echo __('Retry Alt Text Generation', 'content-generator');
        echo '</button>';
        echo '</div>';
    }
});
```

#### Phase 7: Priority Queue Management (2-3 hours)

**7.1 Throttle background when high-priority jobs running**
```php
function process_ai_alt_text_background() {
    // Check if high-priority jobs are running
    $high_priority_running = count_processing_ai_alt_text('high');

    if ($high_priority_running > 0) {
        // Throttle: only process 2-3 low-priority images
        $batch_size = 3;
    } else {
        // No conflicts: process 10-20 images
        $batch_size = 10;
    }

    // Process batch...
}
```

#### Phase 8: Settings & Configuration (3-4 hours)

**8.1 Admin settings page**
```php
add_settings_section(
    'ai_alt_text_settings',
    __('AI Alt Text Settings', 'content-generator'),
    null,
    'content-generator'
);

add_settings_field(
    'auto_generate_on_upload',
    __('Auto-generate on upload', 'content-generator'),
    'render_checkbox_field',
    'content-generator',
    'ai_alt_text_settings',
    ['default' => false]
);

add_settings_field(
    'batch_size',
    __('Batch size (concurrent images)', 'content-generator'),
    'render_number_field',
    'content-generator',
    'ai_alt_text_settings',
    ['default' => 10, 'min' => 1, 'max' => 20]
);
```

**8.2 Bulk regeneration**
```php
// Settings page: "Regenerate All" button
add_action('admin_init', function() {
    if (isset($_POST['regenerate_all_alt_text'])) {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'posts_per_page' => -1
        ]);

        foreach ($attachments as $attachment) {
            delete_post_meta($attachment->ID, '_ai_generated_alt_text');
            set_ai_alt_text_status($attachment->ID, 'pending');
        }

        wp_schedule_single_event(time(), 'process_ai_alt_text_background');
    }
});
```

### Benefits

- ✅ Fully automatic - no user intervention needed
- ✅ Upload 100 images → alt text ready in 30-60 seconds
- ✅ "Generate All Blocks" is instant (most images pre-cached)
- ✅ Real-time progress indicators
- ✅ Premium user experience

### Tradeoffs

- ❌ Complex implementation (20-27 hours)
- ❌ Higher API costs (generates for all uploads, not just used images)
- ❌ More maintenance and potential bugs
- ❌ Requires careful rate limit management

### User Experience Timeline

1. User uploads 100 images → Upload completes instantly
2. Background processing starts automatically (no user action needed)
3. Progress notification shows: "Generating alt text for 100 images... 47/100 complete"
4. Within 30-60 seconds, all alt text is ready
5. User runs "Generate All Blocks" → Instant (all alt text already cached)

---

## Cost-Benefit Analysis

| Approach | Dev Time  | API Cost Savings | User Experience | Complexity | Auto on Upload |
|----------|-----------|------------------|-----------------|------------|----------------|
| Tier 1 ✅ | 2-3 hrs   | ⭐⭐⭐ High (reuse) | Good           | Low        | ❌ No          |
| Tier 2   | 5-8 hrs   | ⭐⭐⭐⭐⭐ Highest    | Excellent      | Medium     | ❌ No          |
| Tier 3   | 20-27 hrs | ⭐⭐ Medium        | Premium        | Very High  | ✅ Yes         |

---

## Recommended Path

1. **✅ Tier 1 is complete** - Test it for 1-2 weeks with real usage
2. **Evaluate:**
   - Is the 5-10 second delay during "Generate All Blocks" acceptable?
   - Are you reusing images frequently? (Tier 1 saves tons of money if yes)
   - Do you need alt text ready before using images?
3. **Decide:**
   - If Tier 1 is working fine → **Stop here**
   - If you want manual control and faster blocks → **Implement Tier 2**
   - If you need full automation (e.g., CSV bulk import) → **Implement Tier 3**

---

## Implementation Checklist

### Tier 1 ✅
- [x] Add metadata caching (`_ai_generated_alt_text`)
- [x] Check cache before generating
- [x] Save after generating
- [x] Test: Generate same page twice, second should be instant

### Tier 2 ⬜
- [ ] Add metadata status field (`_ai_alt_text_status`)
- [ ] Add bulk action to Media Library
- [ ] Implement background processing queue
- [ ] Add UI badges to Media Library grid view
- [ ] Add status column to Media Library list view
- [ ] Add progress notification
- [ ] Add retry button for failed generations
- [ ] Test bulk generation with 50+ images

### Tier 3 ⬜
- [ ] Hook into upload process (`add_attachment`)
- [ ] Set up automatic background worker
- [ ] Implement parallel processing (10-20 concurrent)
- [ ] Add real-time progress indicators (admin bar)
- [ ] AJAX polling for UI updates
- [ ] Priority queue management
- [ ] Integration with "Generate All Blocks"
- [ ] Deduplication logic
- [ ] Error handling and retry mechanism
- [ ] Admin settings page
- [ ] Bulk regeneration option
- [ ] Cost estimation display
- [ ] Test with 100+ image upload

---

## Testing Scenarios

### Tier 1 Testing
1. Upload image → Use in "Generate All Blocks" → Verify alt text generated
2. Reuse same image on another page → Verify instant (no API call)
3. Check metadata: `wp_postmeta` table has `_ai_generated_alt_text`

### Tier 2 Testing
1. Upload 50 images → Select all → Bulk action "Generate AI Alt Text"
2. Verify progress notification appears
3. Check badges in Media Library (grid view) update to ✅
4. Run "Generate All Blocks" → Verify instant (uses cached)
5. Test failed generation → Retry button works

### Tier 3 Testing
1. Upload 100 images → Verify background processing starts automatically
2. Monitor admin bar progress indicator
3. Verify processing completes in 30-60 seconds
4. Upload images + immediately run "Generate All Blocks" → Verify no conflicts
5. Test rate limiting behavior
6. Test with settings disabled → Verify no auto-generation

---

## Notes & Considerations

### API Rate Limits
- **OpenAI GPT-4 Vision:** 500 requests/min (Tier 3)
- Monitor for 429 errors
- Implement exponential backoff

### Cost Estimates (per 100 images)
- **GPT-4 Vision:** ~$1-3
- **Claude with vision:** ~$1-2
- **Google Cloud Vision:** ~$0.10-0.50

### WordPress Cron Limitations
- WordPress cron requires site visits to trigger
- For Tier 3, consider real cron job: `*/5 * * * * wp cron event run --due-now`
- Or use Action Scheduler plugin for more reliable background processing

### Storage Considerations
- Alt text metadata stored in `wp_postmeta`
- Average ~50-200 characters per alt text
- 1000 images ≈ 50-200 KB additional database storage

---

**Last Updated:** 2025-10-09
**Current Status:** Tier 1 Complete, Ready to Test
